import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { GALLERY_STAGES } from '../utils/vehicleImages';
import { uploadSingleVehicleImage } from '../utils/vehicleImageUpload';
import { uploadVehicleZipImages } from '../utils/vehicleVinstackZipUpload';

const ACTIVE_STATUSES = ['queued', 'uploading', 'processing', 'refreshing'];

function makeJobId() {
    return `upload-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
}

function stageLabel(stageKey) {
    return GALLERY_STAGES.find((stage) => stage.key === stageKey)?.label ?? stageKey;
}

export const useVehicleUploadStore = defineStore('vehicleUpload', () => {
    const jobs = ref([]);
    const listeners = new Map();

    const activeJobs = computed(() => jobs.value.filter(
        (job) => ! job.dismissed && ACTIVE_STATUSES.includes(job.status),
    ));

    const dockJobs = computed(() => jobs.value.filter((job) => ! job.dismissed));

    const hasActive = computed(() => activeJobs.value.length > 0);

    function subscribe(vehicleId, callback) {
        const key = String(vehicleId);

        if (! listeners.has(key)) {
            listeners.set(key, new Set());
        }

        listeners.get(key).add(callback);

        return () => {
            listeners.get(key)?.delete(callback);
        };
    }

    function notify(vehicleId, payload) {
        const key = String(vehicleId);
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

    function isStageBusy(vehicleId, stage) {
        return activeJobs.value.some(
            (job) => job.vehicleId === vehicleId && job.stage === stage,
        );
    }

    function estimateEta(job) {
        if (! job.completed || job.status !== 'uploading' || job.type !== 'images') {
            return null;
        }

        const elapsed = Date.now() - job.startedAt;
        const perItem = elapsed / job.completed;
        const remaining = Math.max(0, job.total - job.completed);

        return Math.round((perItem * remaining) / 1000);
    }

    async function enqueueImages({ vehicleId, vehicleLabel, stage, files }) {
        const list = [...files];

        if (! list.length || ! vehicleId) {
            return null;
        }

        const jobId = makeJobId();
        const job = {
            id: jobId,
            vehicleId,
            vehicleLabel: vehicleLabel || `سيارة #${vehicleId}`,
            stage,
            stageLabel: stageLabel(stage),
            type: 'images',
            status: 'uploading',
            total: list.length,
            completed: 0,
            failed: 0,
            currentFileName: list[0]?.name ?? '',
            progress: 0,
            fileProgress: 0,
            phase: 'upload',
            message: `جاري الرفع 0 من ${list.length}`,
            error: null,
            dismissed: false,
            expanded: true,
            startedAt: Date.now(),
            finishedAt: null,
        };

        jobs.value.unshift(job);

        let lastVehiclePayload = null;
        let failedCount = 0;
        let completedCount = 0;
        let lastError = null;

        for (let index = 0; index < list.length; index += 1) {
            const file = list[index];

            patchJob(jobId, {
                currentFileName: file.name,
                fileProgress: 0,
                message: `جاري رفع ${index + 1} من ${list.length}`,
            });

            try {
                const result = await uploadSingleVehicleImage(vehicleId, stage, file, (percent) => {
                    patchJob(jobId, {
                        fileProgress: percent,
                        progress: Math.min(99, Math.round(((index + (percent / 100)) / list.length) * 100)),
                    });
                });

                completedCount += 1;
                lastVehiclePayload = result.data?.vehicle ?? result.data;

                patchJob(jobId, {
                    completed: completedCount,
                    progress: Math.min(99, Math.round((completedCount / list.length) * 100)),
                    message: `تم رفع ${completedCount} من ${list.length}`,
                });
            } catch (error) {
                failedCount += 1;
                lastError = error.response?.data?.message || error.message || 'تعذر رفع الصورة';

                patchJob(jobId, {
                    failed: failedCount,
                    message: `فشل رفع ${file.name}`,
                });
            }
        }

        patchJob(jobId, {
            status: 'refreshing',
            phase: 'refresh',
            message: 'جاري تحديث المعرض…',
        });

        if (lastVehiclePayload) {
            notify(vehicleId, {
                type: 'images',
                vehicle: lastVehiclePayload,
            });
        }

        const success = completedCount > 0;
        const finalStatus = success ? 'completed' : 'failed';

        patchJob(jobId, {
            status: finalStatus,
            phase: 'done',
            progress: 100,
            completed: completedCount,
            failed: failedCount,
            finishedAt: Date.now(),
            message: failedCount > 0
                ? `تم رفع ${completedCount} صورة، فشل ${failedCount}`
                : `تم رفع ${completedCount} صورة بنجاح`,
            error: failedCount > 0 ? (lastError ?? 'بعض الصور فشل رفعها') : null,
        });

        return jobId;
    }

    async function enqueueZip({ vehicleId, vehicleLabel, stage, zipFile }) {
        if (! zipFile || ! vehicleId) {
            return null;
        }

        const jobId = makeJobId();

        jobs.value.unshift({
            id: jobId,
            vehicleId,
            vehicleLabel: vehicleLabel || `سيارة #${vehicleId}`,
            stage,
            stageLabel: stageLabel(stage),
            type: 'zip',
            status: 'uploading',
            total: 1,
            completed: 0,
            failed: 0,
            currentFileName: zipFile.name,
            progress: 0,
            fileProgress: 0,
            phase: 'upload',
            message: 'جاري رفع ملف ZIP…',
            error: null,
            dismissed: false,
            expanded: true,
            startedAt: Date.now(),
            finishedAt: null,
        });

        try {
            const result = await uploadVehicleZipImages(vehicleId, stage, zipFile, (percent) => {
                patchJob(jobId, {
                    fileProgress: percent,
                    progress: Math.min(90, Math.round(percent * 0.9)),
                    message: percent < 100 ? `جاري رفع ZIP… ${percent}%` : 'جاري معالجة الصور على الخادم…',
                });
            });

            patchJob(jobId, {
                status: 'refreshing',
                phase: 'refresh',
                progress: 95,
                message: 'جاري تحديث المعرض…',
            });

            const galleryPayload = result.data?.gallery;

            notify(vehicleId, {
                type: 'zip',
                gallery: galleryPayload,
                result,
            });

            const uploaded = Number(result.data?.uploaded ?? galleryPayload?.gallery_new_images_count ?? 0);

            patchJob(jobId, {
                status: 'completed',
                phase: 'done',
                progress: 100,
                completed: uploaded || 1,
                finishedAt: Date.now(),
                message: result.message || `تم رفع ZIP وتحديث المعرض`,
            });
        } catch (error) {
            patchJob(jobId, {
                status: 'failed',
                phase: 'done',
                progress: 100,
                failed: 1,
                finishedAt: Date.now(),
                message: 'فشل رفع ZIP',
                error: error.message || 'تعذّر رفع ملف ZIP إلى Vinstack',
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
        enqueueImages,
        enqueueZip,
        dismissJob,
        dismissAllFinished,
        toggleExpanded,
        isStageBusy,
        estimateEta,
        subscribe,
    };
});
