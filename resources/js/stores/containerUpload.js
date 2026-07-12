import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import {
    formatCloudinaryUploadError,
    uploadContainerZipToCloud,
} from '../utils/containerCloudinaryUpload';
import { applyCloudinaryContainerPayload } from '../utils/containerZipImages';
import { watchBackgroundTransfer } from '../utils/imageTransfer';

const ACTIVE_STATUSES = ['queued', 'uploading', 'processing', 'refreshing'];

function makeJobId() {
    return `container-upload-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
}

function formatZipSize(bytes) {
    if (! bytes || bytes <= 0) {
        return '';
    }

    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    return `${Math.round(bytes / 1024)} KB`;
}

export const useContainerUploadStore = defineStore('containerUpload', () => {
    const jobs = ref([]);
    const listeners = new Map();
    const backgroundKeys = ref(new Set());
    const backgroundStops = new Map();

    const activeJobs = computed(() => jobs.value.filter(
        (job) => ! job.dismissed && ACTIVE_STATUSES.includes(job.status),
    ));

    const dockJobs = computed(() => jobs.value.filter((job) => ! job.dismissed));

    const hasActive = computed(() => activeJobs.value.length > 0);

    function subscribe(containerKey, callback) {
        const key = String(containerKey);

        if (! listeners.has(key)) {
            listeners.set(key, new Set());
        }

        listeners.get(key).add(callback);

        return () => {
            listeners.get(key)?.delete(callback);
        };
    }

    function notify(containerKey, payload) {
        const key = String(containerKey);
        const callbacks = listeners.get(key);

        if (! callbacks) {
            return;
        }

        callbacks.forEach((callback) => callback(payload));
    }

    function findJob(jobId) {
        return jobs.value.find((job) => job.id === jobId) ?? null;
    }

    function patchJob(jobId, patch) {
        const index = jobs.value.findIndex((job) => job.id === jobId);

        if (index === -1) {
            return null;
        }

        jobs.value[index] = {
            ...jobs.value[index],
            ...patch,
        };

        return jobs.value[index];
    }

    function markBackgroundBusy(containerKey) {
        const key = String(containerKey);
        const next = new Set(backgroundKeys.value);
        next.add(key);
        backgroundKeys.value = next;
    }

    function clearBackgroundBusy(containerKey) {
        const key = String(containerKey);
        const next = new Set(backgroundKeys.value);

        if (! next.has(key)) {
            return;
        }

        next.delete(key);
        backgroundKeys.value = next;
    }

    function isContainerBusy(containerKey) {
        if (backgroundKeys.value.has(String(containerKey))) {
            return true;
        }

        return activeJobs.value.some((job) => job.containerKey === containerKey);
    }

    function acceptAsyncTransfer({
        jobId,
        containerKey,
        transfer,
        message,
        apiPrefix,
        onAccepted,
    }) {
        markBackgroundBusy(containerKey);

        patchJob(jobId, {
            status: 'completed',
            phase: 'done',
            progress: 100,
            total: transfer?.total_images ?? 0,
            completed: 0,
            finishedAt: Date.now(),
            message: message || 'تم الرفع',
            transferId: transfer?.id ?? null,
            dismissed: true,
        });

        onAccepted?.(message || 'تم الرفع');

        const transferId = transfer?.id;

        if (! transferId) {
            clearBackgroundBusy(containerKey);

            return;
        }

        backgroundStops.get(transferId)?.();
        const stop = watchBackgroundTransfer({
            transferId,
            apiPrefix,
            onComplete: (result) => {
                backgroundStops.delete(transferId);
                clearBackgroundBusy(containerKey);

                if (result.gallery) {
                    const stored = applyCloudinaryContainerPayload(containerKey, result.gallery);

                    notify(containerKey, {
                        type: 'zip',
                        payload: stored,
                    });
                }
            },
            onFailed: () => {
                backgroundStops.delete(transferId);
                clearBackgroundBusy(containerKey);
            },
        });

        backgroundStops.set(transferId, stop);
    }

    async function runZipUpload(jobId, {
        containerRef,
        containerKey,
        zipFile,
        apiPrefix,
        replace,
        onAccepted,
    }) {
        try {
            const result = await uploadContainerZipToCloud({
                containerRef,
                zipFile,
                apiPrefix,
                replace,
                onProgress: ({ percent, phase }) => {
                    if (phase === 'upload') {
                        patchJob(jobId, {
                            progress: Math.min(99, percent),
                            message: `جاري الرفع… ${percent}%`,
                        });

                        return;
                    }

                    patchJob(jobId, {
                        status: 'processing',
                        phase: 'staging',
                        progress: Math.min(99, 42 + Math.round(percent / 20)),
                        message: 'جاري استلام الملف…',
                    });
                },
            });

            if (result?.async && result?.transfer?.id) {
                acceptAsyncTransfer({
                    jobId,
                    containerKey,
                    transfer: result.transfer,
                    message: result.message,
                    apiPrefix,
                    onAccepted,
                });

                return;
            }

            patchJob(jobId, {
                status: 'refreshing',
                phase: 'refresh',
                progress: 98,
                message: 'جاري تحديث المعرض…',
            });

            const stored = applyCloudinaryContainerPayload(containerKey, result);
            const uploaded = Number(result.uploaded ?? result.images?.length ?? 0);

            notify(containerKey, {
                type: 'zip',
                payload: stored,
            });

            patchJob(jobId, {
                status: 'completed',
                phase: 'done',
                progress: 100,
                completed: uploaded,
                total: uploaded,
                finishedAt: Date.now(),
                message: `تم رفع ${uploaded} صورة إلى معرض الحاوية`,
                dismissed: true,
            });

            onAccepted?.('تم الرفع');
        } catch (error) {
            patchJob(jobId, {
                status: 'failed',
                phase: 'done',
                progress: 100,
                failed: 1,
                finishedAt: Date.now(),
                message: 'فشل رفع ملف ZIP',
                error: formatCloudinaryUploadError(error),
            });
        }
    }

    function enqueueZip({
        containerRef,
        containerLabel,
        containerKey,
        zipFile,
        apiPrefix = '/admin',
        replace = true,
        onAccepted,
    }) {
        if (! containerRef || ! zipFile || ! containerKey) {
            return null;
        }

        const jobId = makeJobId();
        const sizeLabel = formatZipSize(zipFile.size);

        jobs.value.unshift({
            id: jobId,
            kind: 'container',
            containerRef,
            containerKey,
            containerLabel: containerLabel || containerRef,
            vehicleLabel: containerLabel || containerRef,
            stageLabel: 'معرض الحاوية',
            type: 'zip',
            status: 'uploading',
            total: 0,
            completed: 0,
            failed: 0,
            currentFileName: zipFile.name,
            progress: 0,
            fileProgress: 0,
            phase: 'upload',
            message: sizeLabel
                ? `جاري الرفع (${sizeLabel})…`
                : 'جاري رفع ملف ZIP…',
            error: null,
            dismissed: false,
            expanded: true,
            startedAt: Date.now(),
            finishedAt: null,
            transferId: null,
        });

        void runZipUpload(jobId, {
            containerRef,
            containerKey,
            zipFile,
            apiPrefix,
            replace,
            onAccepted,
        });

        return jobId;
    }

    function dismissJob(jobId) {
        const job = findJob(jobId);

        if (! job || ACTIVE_STATUSES.includes(job.status)) {
            return;
        }

        patchJob(jobId, { dismissed: true });
    }

    function dismissAllFinished() {
        jobs.value = jobs.value.map((job) => (
            ['completed', 'failed'].includes(job.status)
                ? { ...job, dismissed: true }
                : job
        ));
    }

    function toggleExpanded(jobId) {
        const job = findJob(jobId);

        if (! job) {
            return;
        }

        patchJob(jobId, { expanded: ! job.expanded });
    }

    return {
        jobs,
        activeJobs,
        dockJobs,
        hasActive,
        enqueueZip,
        dismissJob,
        dismissAllFinished,
        toggleExpanded,
        isContainerBusy,
        subscribe,
    };
});
