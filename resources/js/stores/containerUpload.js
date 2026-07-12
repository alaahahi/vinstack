import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import {
    formatCloudinaryUploadError,
    uploadContainerZipToCloud,
} from '../utils/containerCloudinaryUpload';
import { applyCloudinaryContainerPayload } from '../utils/containerZipImages';

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

    function isContainerBusy(containerKey) {
        return activeJobs.value.some((job) => job.containerKey === containerKey);
    }

    async function enqueueZip({
        containerRef,
        containerLabel,
        containerKey,
        zipFile,
        apiPrefix = '/admin',
        replace = true,
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
                ? `جاري رفع ZIP (${sizeLabel})…`
                : 'جاري رفع ملف ZIP…',
            error: null,
            dismissed: false,
            expanded: true,
            startedAt: Date.now(),
            finishedAt: null,
        });

        try {
            const payload = await uploadContainerZipToCloud({
                containerRef,
                zipFile,
                apiPrefix,
                replace,
                onProgress: ({ percent, phase }) => {
                    if (phase === 'upload') {
                        patchJob(jobId, {
                            progress: Math.min(88, percent),
                            message: `جاري رفع ZIP… ${percent}%`,
                        });

                        return;
                    }

                    patchJob(jobId, {
                        status: 'processing',
                        phase: 'processing',
                        progress: Math.min(96, 90 + Math.round(percent / 10)),
                        message: 'جاري استخراج الصور ورفعها على الخادم…',
                    });
                },
            });

            patchJob(jobId, {
                status: 'refreshing',
                phase: 'refresh',
                progress: 98,
                message: 'جاري تحديث المعرض…',
            });

            const stored = applyCloudinaryContainerPayload(containerKey, payload);

            notify(containerKey, {
                type: 'zip',
                payload: stored,
            });

            const uploaded = Number(payload.uploaded ?? payload.images?.length ?? 0);

            patchJob(jobId, {
                status: 'completed',
                phase: 'done',
                progress: 100,
                completed: uploaded,
                total: uploaded,
                finishedAt: Date.now(),
                message: `تم رفع ${uploaded} صورة إلى معرض الحاوية`,
            });
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
