import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import {
    fetchImageTransferStatus,
    formatCloudinaryUploadError,
    uploadContainerZipToCloud,
} from '../utils/containerCloudinaryUpload';
import { applyCloudinaryContainerPayload } from '../utils/containerZipImages';

const ACTIVE_STATUSES = ['queued', 'uploading', 'processing', 'refreshing'];
const POLL_MS = 3000;

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

function transferStatusMessage(transfer) {
    if (! transfer) {
        return 'جاري النقل إلى Cloudinary…';
    }

    if (transfer.status === 'queued') {
        return `في الانتظار — ${transfer.total_images} صورة`;
    }

    if (transfer.status === 'processing') {
        return `نقل إلى Cloudinary… ${transfer.transferred_count}/${transfer.total_images}`;
    }

    if (transfer.status === 'completed') {
        return `اكتمل النقل — ${transfer.transferred_count} صورة`;
    }

    if (transfer.status === 'partial') {
        return `اكتمل جزئياً — نجح ${transfer.transferred_count} وفشل ${transfer.failed_count}`;
    }

    return transfer.error_message || 'فشل نقل الصور إلى Cloudinary';
}

function mapTransferToJobStatus(transferStatus) {
    if (transferStatus === 'completed' || transferStatus === 'partial') {
        return 'completed';
    }

    if (transferStatus === 'failed') {
        return 'failed';
    }

    if (transferStatus === 'queued') {
        return 'queued';
    }

    return 'processing';
}

export const useContainerUploadStore = defineStore('containerUpload', () => {
    const jobs = ref([]);
    const listeners = new Map();
    const pollTimers = new Map();

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

    function stopPolling(jobId) {
        const timer = pollTimers.get(jobId);

        if (timer) {
            clearTimeout(timer);
            pollTimers.delete(jobId);
        }
    }

    function schedulePoll(jobId, transferId, containerKey, apiPrefix) {
        stopPolling(jobId);

        const tick = async () => {
            try {
                const transfer = await fetchImageTransferStatus(transferId, apiPrefix);

                if (! transfer) {
                    return;
                }

                const mappedStatus = mapTransferToJobStatus(transfer.status);
                const uploadPercent = Math.min(95, Math.max(8, Number(transfer.progress_percent ?? 0)));

                patchJob(jobId, {
                    status: mappedStatus === 'completed' ? 'refreshing' : mappedStatus,
                    phase: mappedStatus === 'completed' ? 'refresh' : 'cloud',
                    progress: mappedStatus === 'completed' ? 98 : uploadPercent,
                    completed: transfer.transferred_count ?? 0,
                    total: transfer.total_images ?? 0,
                    failed: transfer.failed_count ?? 0,
                    message: transferStatusMessage(transfer),
                    transferId,
                });

                if (['completed', 'partial', 'failed'].includes(transfer.status)) {
                    stopPolling(jobId);

                    if (transfer.gallery && mappedStatus !== 'failed') {
                        const stored = applyCloudinaryContainerPayload(containerKey, transfer.gallery);

                        notify(containerKey, {
                            type: 'zip',
                            payload: stored,
                        });
                    }

                    patchJob(jobId, {
                        status: mappedStatus,
                        phase: 'done',
                        progress: 100,
                        finishedAt: Date.now(),
                        message: transferStatusMessage(transfer),
                        error: transfer.status === 'failed' ? (transfer.error_message || 'فشل النقل') : null,
                    });

                    return;
                }

                pollTimers.set(jobId, setTimeout(tick, POLL_MS));
            } catch {
                pollTimers.set(jobId, setTimeout(tick, POLL_MS * 2));
            }
        };

        pollTimers.set(jobId, setTimeout(tick, POLL_MS));
    }

    async function runZipUpload(jobId, {
        containerRef,
        containerKey,
        zipFile,
        apiPrefix,
        replace,
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
                            progress: Math.min(40, percent),
                            message: `جاري استلام ZIP… ${percent}%`,
                        });

                        return;
                    }

                    patchJob(jobId, {
                        status: 'processing',
                        phase: 'staging',
                        progress: Math.min(50, 42 + Math.round(percent / 20)),
                        message: 'جاري تجهيز الصور على الخادم…',
                    });
                },
            });

            if (result?.async && result?.transfer?.id) {
                patchJob(jobId, {
                    status: 'queued',
                    phase: 'cloud',
                    progress: Math.max(52, result.transfer.progress_percent ?? 52),
                    total: result.transfer.total_images ?? 0,
                    completed: 0,
                    message: result.message || transferStatusMessage(result.transfer),
                    transferId: result.transfer.id,
                });

                schedulePoll(jobId, result.transfer.id, containerKey, apiPrefix);

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
    }

    function enqueueZip({
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
                ? `جاري استلام ZIP (${sizeLabel})…`
                : 'جاري استلام ملف ZIP…',
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
        });

        return jobId;
    }

    function dismissJob(jobId) {
        const job = findJob(jobId);

        if (! job || ACTIVE_STATUSES.includes(job.status)) {
            return;
        }

        stopPolling(jobId);
        patchJob(jobId, { dismissed: true });
    }

    function dismissAllFinished() {
        jobs.value.forEach((job) => {
            if (['completed', 'failed'].includes(job.status)) {
                stopPolling(job.id);
            }
        });

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
