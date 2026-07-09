import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import {
    formatCloudinaryUploadError,
    uploadContainerImagesToCloud,
} from '../utils/containerCloudinaryUpload';
import { applyCloudinaryContainerPayload } from '../utils/containerZipImages';

const ACTIVE_STATUSES = ['queued', 'uploading', 'processing', 'refreshing'];

function makeJobId() {
    return `container-upload-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
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
        images,
        apiPrefix = '/admin',
        replace = true,
    }) {
        if (! containerRef || ! images?.length || ! containerKey) {
            return null;
        }

        const jobId = makeJobId();

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
            total: images.length,
            completed: 0,
            failed: 0,
            currentFileName: `${images.length} صورة`,
            progress: 0,
            fileProgress: 0,
            phase: 'upload',
            message: `جاري رفع 0 من ${images.length}`,
            error: null,
            dismissed: false,
            expanded: true,
            startedAt: Date.now(),
            finishedAt: null,
        });

        try {
            const payload = await uploadContainerImagesToCloud({
                containerRef,
                images,
                apiPrefix,
                replace,
                onProgress: ({ percent, done, total }) => {
                    patchJob(jobId, {
                        progress: Math.min(99, percent),
                        completed: done,
                        total,
                        message: percent < 100
                            ? `جاري رفع الصور… ${percent}%`
                            : 'جاري حفظ المعرض…',
                    });
                },
            });

            patchJob(jobId, {
                status: 'refreshing',
                phase: 'refresh',
                progress: 95,
                message: 'جاري تحديث المعرض…',
            });

            const stored = applyCloudinaryContainerPayload(containerKey, payload);

            notify(containerKey, {
                type: 'zip',
                payload: stored,
            });

            const uploaded = Number(payload.uploaded ?? payload.images?.length ?? images.length);

            patchJob(jobId, {
                status: 'completed',
                phase: 'done',
                progress: 100,
                completed: uploaded,
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
                message: 'فشل رفع صور الحاوية',
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
