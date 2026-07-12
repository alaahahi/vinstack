import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { GALLERY_STAGES } from '../utils/vehicleImages';
import { uploadVehicleImagesBatch } from '../utils/vehicleImageUpload';
import { uploadVehicleZipImages } from '../utils/vehicleVinstackZipUpload';
import { watchBackgroundTransfer } from '../utils/imageTransfer';

const ACTIVE_STATUSES = ['queued', 'uploading', 'processing', 'refreshing'];

function makeJobId() {
    return `upload-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
}

function stageLabel(stageKey) {
    return GALLERY_STAGES.find((stage) => stage.key === stageKey)?.label ?? stageKey;
}

function vehicleBusyKey(vehicleId, stage) {
    return `${vehicleId}:${stage}`;
}

export const useVehicleUploadStore = defineStore('vehicleUpload', () => {
    const jobs = ref([]);
    const listeners = new Map();
    const backgroundKeys = ref(new Set());
    const backgroundStops = new Map();

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

    function markBackgroundBusy(vehicleId, stage) {
        const key = vehicleBusyKey(vehicleId, stage);
        const next = new Set(backgroundKeys.value);
        next.add(key);
        backgroundKeys.value = next;
    }

    function clearBackgroundBusy(vehicleId, stage) {
        const key = vehicleBusyKey(vehicleId, stage);
        const next = new Set(backgroundKeys.value);

        if (! next.has(key)) {
            return;
        }

        next.delete(key);
        backgroundKeys.value = next;
    }

    function isStageBusy(vehicleId, stage) {
        if (backgroundKeys.value.has(vehicleBusyKey(vehicleId, stage))) {
            return true;
        }

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

    function acceptAsyncTransfer({
        jobId,
        vehicleId,
        stage,
        transfer,
        type,
        message,
        onAccepted,
    }) {
        markBackgroundBusy(vehicleId, stage);

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
            clearBackgroundBusy(vehicleId, stage);

            return;
        }

        backgroundStops.get(transferId)?.();
        const stop = watchBackgroundTransfer({
            transferId,
            onComplete: (result) => {
                backgroundStops.delete(transferId);
                clearBackgroundBusy(vehicleId, stage);

                if (type === 'zip' && result.gallery) {
                    notify(vehicleId, {
                        type: 'zip',
                        gallery: result.gallery,
                        result,
                    });

                    return;
                }

                if (result.vehicle) {
                    notify(vehicleId, {
                        type: 'images',
                        vehicle: result.vehicle,
                    });
                }
            },
            onFailed: () => {
                backgroundStops.delete(transferId);
                clearBackgroundBusy(vehicleId, stage);
            },
        });

        backgroundStops.set(transferId, stop);
    }

    async function enqueueImages({ vehicleId, vehicleLabel, stage, files, onAccepted }) {
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
            message: `جاري الرفع…`,
            error: null,
            dismissed: false,
            expanded: true,
            startedAt: Date.now(),
            finishedAt: null,
        };

        jobs.value.unshift(job);

        try {
            const result = await uploadVehicleImagesBatch(vehicleId, stage, list, (percent) => {
                patchJob(jobId, {
                    progress: Math.min(99, percent),
                    fileProgress: percent,
                    message: `جاري الرفع… ${percent}%`,
                });
            });

            if (result?.async && result?.transfer?.id) {
                acceptAsyncTransfer({
                    jobId,
                    vehicleId,
                    stage,
                    transfer: result.transfer,
                    type: 'images',
                    message: result.message,
                    onAccepted,
                });

                return jobId;
            }

            patchJob(jobId, {
                status: 'refreshing',
                phase: 'refresh',
                message: 'جاري تحديث المعرض…',
            });

            const vehiclePayload = result.data?.vehicle ?? result.data;

            if (vehiclePayload) {
                notify(vehicleId, {
                    type: 'images',
                    vehicle: vehiclePayload,
                });
            }

            const uploaded = Array.isArray(result.data?.uploaded)
                ? result.data.uploaded.length
                : list.length;

            patchJob(jobId, {
                status: 'completed',
                phase: 'done',
                progress: 100,
                completed: uploaded,
                finishedAt: Date.now(),
                message: result.message || `تم رفع ${uploaded} صورة بنجاح`,
                dismissed: true,
            });

            onAccepted?.(result.message || 'تم الرفع');

            return jobId;
        } catch (error) {
            patchJob(jobId, {
                status: 'failed',
                phase: 'done',
                progress: 100,
                failed: list.length,
                finishedAt: Date.now(),
                message: 'فشل رفع الصور',
                error: error.response?.data?.message || error.message || 'تعذر رفع الصور',
            });
        }

        return jobId;
    }

    async function enqueueZip({ vehicleId, vehicleLabel, stage, zipFile, onAccepted }) {
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
                    progress: Math.min(99, percent),
                    message: percent < 100 ? `جاري رفع ZIP… ${percent}%` : 'جاري استلام الملف…',
                });
            });

            if (result?.async && result?.transfer?.id) {
                acceptAsyncTransfer({
                    jobId,
                    vehicleId,
                    stage,
                    transfer: result.transfer,
                    type: 'zip',
                    message: result.message,
                    onAccepted,
                });

                return jobId;
            }

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
                message: result.message || 'تم رفع ZIP وتحديث المعرض',
                dismissed: true,
            });

            onAccepted?.(result.message || 'تم الرفع');
        } catch (error) {
            patchJob(jobId, {
                status: 'failed',
                phase: 'done',
                progress: 100,
                failed: 1,
                finishedAt: Date.now(),
                message: 'فشل رفع ZIP',
                error: error.message || 'تعذّر رفع ملف ZIP',
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
