<template>
    <div class="photos-panel">
        <div v-if="galleryLoading" class="gallery-loading">
            <ProgressSpinner style="width: 28px; height: 28px" />
            <span>جاري تحميل الصور المحدّثة...</span>
        </div>

        <div v-else-if="galleryTokenExpired" class="gallery-warning gallery-warning--danger">
            <i class="pi pi-exclamation-triangle" />
            <span>توكن API المعرض منتهي — راجع الإعدادات. تُعرض الصور المخزّنة إن وُجدت.</span>
        </div>

        <div v-else-if="galleryError" class="gallery-warning gallery-warning--danger">
            <i class="pi pi-exclamation-circle" />
            <span>{{ galleryErrorText }}</span>
        </div>

        <div v-else-if="galleryFresh" class="gallery-warning gallery-warning--ok">
            <i class="pi pi-check-circle" />
            <span v-if="galleryNewImagesCount > 0">
                تم حفظ {{ galleryNewImagesCount }} صورة جديدة من API المعرض
            </span>
            <span v-else>صور محدّثة من API المعرض</span>
        </div>

        <template v-if="adminMode">
            <header class="photos-section-header">
                <h3 class="photos-section-title">إدارة الصور</h3>
                <p class="photos-section-sub">رفع صور جديدة للتاجر حسب المرحلة</p>
            </header>

            <div v-if="preview" class="preview-block">
                <img :src="preview" :alt="label" class="preview-img" loading="lazy" decoding="async" />
                <span class="preview-label">معاينة Vinstack</span>
            </div>

            <section
                v-for="stage in GALLERY_STAGES"
                :key="stage.key"
                class="stage-card"
                :class="{ 'stage-card--dragover': dragOverStage === stage.key }"
                @dragenter.prevent="onDragEnter(stage.key)"
                @dragover.prevent="onDragOver(stage.key)"
                @dragleave="onDragLeave(stage.key, $event)"
                @drop.prevent="onDrop(stage.key, $event)"
            >
                <div class="stage-card-header">
                    <div class="stage-card-title-wrap">
                        <h4 class="stage-title">{{ stage.label }}</h4>
                        <div class="stage-counts">
                            <span class="count-pill count-pill--vinstack" title="صور من Vinstack">
                                <i class="pi pi-cloud" />
                                Vinstack: {{ stageCounts(stage.key).vinstack }}
                            </span>
                            <span class="count-pill count-pill--local" title="صور مرفوعة من الإدارة">
                                <i class="pi pi-upload" />
                                مرفوعة من الإدارة: {{ stageCounts(stage.key).uploaded }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="stage-dropzone-hint">
                    <i class="pi pi-cloud-upload" />
                    <span>اسحب الصور أو ملف ZIP هنا أو استخدم أزرار الرفع</span>
                </div>

                <div class="stage-upload-row">
                    <input
                        :ref="(el) => setFileInput(stage.key, el)"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        multiple
                        class="file-input"
                        @change="onFilesSelected(stage.key, $event)"
                    />
                    <input
                        :ref="(el) => setZipInput(stage.key, el)"
                        type="file"
                        accept=".zip,application/zip,application/x-zip-compressed"
                        class="file-input"
                        @change="onZipSelected(stage.key, $event)"
                    />
                    <Button
                        icon="pi pi-upload"
                        label="رفع صور جديدة"
                        :loading="uploadingStage === stage.key"
                        :disabled="zipUploadingStage === stage.key"
                        class="upload-btn btn-add"
                        @click="triggerUpload(stage.key)"
                    />
                    <Button
                        icon="pi pi-file-import"
                        label="رفع ملف مضغوط"
                        severity="secondary"
                        :loading="zipUploadingStage === stage.key"
                        :disabled="uploadingStage === stage.key"
                        class="upload-btn upload-btn--zip"
                        @click="triggerZipUpload(stage.key)"
                    />
                    <span
                        v-if="zipUploadingStage === stage.key"
                        class="zip-upload-progress"
                    >
                        <i class="pi pi-spin pi-spinner" />
                        جاري رفع ZIP إلى Vinstack…
                    </span>
                </div>

                <div v-if="stageUrls(stage.key).length" class="stage-thumbs">
                    <div
                        v-for="(url, index) in stageUrls(stage.key)"
                        :key="url"
                        class="thumb-card"
                        :class="{
                            'thumb-card--uploaded': isUploadedUrl(url),
                            'thumb-card--dragging': isThumbDragging(stage.key, index),
                            'thumb-card--drag-over': isThumbDragOver(stage.key, index),
                        }"
                        draggable="true"
                        @dragstart="onThumbDragStart(stage.key, index, $event)"
                        @dragover.prevent="onThumbDragOver(stage.key, index, $event)"
                        @dragleave="onThumbDragLeave(stage.key, index, $event)"
                        @drop.prevent="onThumbDrop(stage.key, index, $event)"
                        @dragend="onThumbDragEnd"
                    >
                        <button type="button" class="thumb-btn" draggable="false" @click="openZoom(url)">
                            <img :src="url" :alt="label" loading="lazy" decoding="async" />
                        </button>
                        <span v-if="isUploadedUrl(url)" class="source-tag source-tag--local">مرفوعة من الإدارة</span>
                        <span v-else class="source-tag source-tag--vinstack">Vinstack</span>
                        <Button
                            v-if="isUploadedUrl(url)"
                            icon="pi pi-trash"
                            severity="danger"
                            rounded
                            size="small"
                            class="thumb-delete"
                            draggable="false"
                            :loading="deletingId === uploadedImageId(url)"
                            aria-label="حذف الصورة"
                            @click="confirmRemoveUploaded(url)"
                        />
                    </div>
                </div>

                <p v-else class="stage-empty">
                    <i class="pi pi-image" />
                    لا توجد صور — ارفع صوراً للتاجر
                </p>
            </section>

            <VehicleGalleryLightbox
                v-model:visible="zoomVisible"
                :vehicle="displayVehicle ?? vehicle"
                :start-url="zoomStartUrl"
                :api-mode="apiMode"
            />
        </template>

        <VehicleImageGallery
            v-else-if="compact"
            :vehicle="displayVehicle ?? vehicle"
            :api-mode="apiMode"
            show-button
            @gallery-updated="onCompactGalleryUpdated"
        />

        <section v-else class="dealer-photos-card">
            <header class="photos-section-header photos-section-header--dealer">
                <h3 class="photos-section-title">صور السيارة</h3>
                <p class="photos-section-sub">معرض للعرض فقط — اضغط الصورة لفتح المعرض</p>
            </header>
            <VehicleImageGallery
                :vehicle="displayVehicle ?? vehicle"
                :api-mode="apiMode"
                variant="drawer"
                show-button
                @gallery-updated="onCompactGalleryUpdated"
            />
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import VehicleImageGallery from './VehicleImageGallery.vue';
import VehicleGalleryLightbox from './VehicleGalleryLightbox.vue';
import {
    GALLERY_STAGES,
    vehicleGalleryByStage,
    vehicleLabel,
    vehicleThumbnail,
    vehicleUploadedImages,
} from '../utils/vehicleImages';
import {
    deleteVehicleImage,
    isDeletableUploadedUrl,
    isLocalUploadedUrl,
    localImageIdForUrl,
    reorderVehicleGallery,
    uploadVehicleImages,
} from '../utils/vehicleImageUpload';
import {
    fetchLiveVehicleGallery,
    mergeGalleryIntoVehicle,
    vehicleUsesLiveGallery,
} from '../utils/vehicleGalleryLive';
import {
    isZipFile,
    uploadVehicleZipImages,
} from '../utils/vehicleVinstackZipUpload';

const props = defineProps({
    vehicle: {
        type: Object,
        required: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
    adminMode: {
        type: Boolean,
        default: false,
    },
    apiMode: {
        type: String,
        default: 'admin',
        validator: (value) => ['admin', 'dealer'].includes(value),
    },
});

const emit = defineEmits(['updated']);

const toast = useToast();
const confirm = useConfirm();

const THUMB_DRAG_MIME = 'application/x-vinstack-thumb';

const zoomVisible = ref(false);
const zoomStartUrl = ref(null);
const uploadingStage = ref(null);
const zipUploadingStage = ref(null);
const deletingId = ref(null);
const fileInputs = ref({});
const zipInputs = ref({});
const dragOverStage = ref(null);
const galleryLoading = ref(false);
const displayVehicle = ref(null);
const galleryFresh = ref(false);
const galleryTokenExpired = ref(false);
const galleryError = ref(null);
const galleryNewImagesCount = ref(0);
const orderOverrides = ref({});
const dragThumb = ref(null);
const dragOverThumb = ref(null);
const reorderingStage = ref(null);

const GALLERY_ERROR_MESSAGES = {
    gallery_token_missing: 'توكن المعرض غير مضبوط — أضف Gallery Token في الإعدادات أو استخدم توكن المزامنة.',
    gallery_token_expired: 'توكن المعرض منتهي — حدّثه من الإعدادات.',
};

const galleryErrorText = computed(() => {
    const code = galleryError.value;

    if (! code) {
        return '';
    }

    return GALLERY_ERROR_MESSAGES[code] ?? code;
});

const preview = computed(() => vehicleThumbnail(displayVehicle.value ?? props.vehicle));
const label = computed(() => vehicleLabel(displayVehicle.value ?? props.vehicle));
const stages = computed(() => vehicleGalleryByStage(displayVehicle.value ?? props.vehicle));
const uploadedList = computed(() => vehicleUploadedImages(displayVehicle.value ?? props.vehicle));

async function loadLiveGallery() {
    const vehicleId = props.vehicle?.id;
    const vin = props.vehicle?.vin;

    if (! vehicleId || ! vin || ! vehicleUsesLiveGallery(props.vehicle)) {
        displayVehicle.value = props.vehicle;

        return;
    }

    galleryLoading.value = true;
    galleryFresh.value = false;
    galleryTokenExpired.value = false;
    galleryError.value = null;
    galleryNewImagesCount.value = 0;

    try {
        const payload = await fetchLiveVehicleGallery(vehicleId, props.apiMode);
        displayVehicle.value = mergeGalleryIntoVehicle(props.vehicle, payload);
        clearOrderOverrides();
        galleryFresh.value = Boolean(payload.gallery_fresh);
        galleryTokenExpired.value = Boolean(payload.gallery_token_expired);
        galleryError.value = payload.gallery_error ?? null;
        galleryNewImagesCount.value = Number(payload.gallery_new_images_count ?? 0);
        emit('updated', displayVehicle.value);
    } catch (e) {
        displayVehicle.value = props.vehicle;
        galleryError.value = e.response?.data?.message || 'تعذّر الاتصال بـ API المعرض — تُعرض الصور المخزّنة.';
    } finally {
        galleryLoading.value = false;
    }
}

function onCompactGalleryUpdated(galleryPayload, vehicleId) {
    displayVehicle.value = mergeGalleryIntoVehicle(
        props.vehicle,
        galleryPayload,
    );
    emit('updated', displayVehicle.value);
}

onMounted(loadLiveGallery);

watch(
    () => [props.vehicle?.id, props.vehicle?.vin],
    () => {
        loadLiveGallery();
    },
);

function setFileInput(stageKey, el) {
    if (el) {
        fileInputs.value[stageKey] = el;
    }
}

function setZipInput(stageKey, el) {
    if (el) {
        zipInputs.value[stageKey] = el;
    }
}

function stageUrls(stageKey) {
    if (Array.isArray(orderOverrides.value[stageKey])) {
        return orderOverrides.value[stageKey];
    }

    return stages.value[stageKey] ?? [];
}

function clearOrderOverrides() {
    orderOverrides.value = {};
}

function stageCounts(stageKey) {
    const urls = stageUrls(stageKey);
    const uploaded = urls.filter((url) => isUploadedUrl(url)).length;

    return {
        total: urls.length,
        uploaded,
        vinstack: urls.length - uploaded,
    };
}

function isUploadedUrl(url) {
    return isDeletableUploadedUrl(url, uploadedList.value);
}

function isLocalUrl(url) {
    return isLocalUploadedUrl(url, uploadedList.value);
}

function uploadedImageId(url) {
    return localImageIdForUrl(url, uploadedList.value);
}

function triggerUpload(stageKey) {
    fileInputs.value[stageKey]?.click();
}

function triggerZipUpload(stageKey) {
    zipInputs.value[stageKey]?.click();
}

function onDragEnter(stageKey) {
    dragOverStage.value = stageKey;
}

function onDragOver(stageKey) {
    dragOverStage.value = stageKey;
}

function onDragLeave(stageKey, event) {
    if (event.currentTarget?.contains(event.relatedTarget)) {
        return;
    }

    if (dragOverStage.value === stageKey) {
        dragOverStage.value = null;
    }
}

function onDrop(stageKey, event) {
    if (event.dataTransfer?.types?.includes(THUMB_DRAG_MIME)) {
        return;
    }

    dragOverStage.value = null;

    const dropped = [...(event.dataTransfer?.files ?? [])];
    const zipFile = dropped.find((file) => isZipFile(file));

    if (zipFile) {
        uploadZipFile(stageKey, zipFile);

        return;
    }

    const files = dropped.filter((file) => file.type.startsWith('image/'));

    if (! files.length) {
        toast.add({
            severity: 'warn',
            summary: 'ملف غير مدعوم',
            detail: 'اسحب صوراً أو ملف ZIP فقط',
            life: 3500,
        });

        return;
    }

    uploadFiles(stageKey, files);
}

function isThumbDragging(stageKey, index) {
    return dragThumb.value?.stage === stageKey && dragThumb.value?.index === index;
}

function isThumbDragOver(stageKey, index) {
    return dragOverThumb.value?.stage === stageKey && dragOverThumb.value?.index === index;
}

function onThumbDragStart(stageKey, index, event) {
    dragThumb.value = { stage: stageKey, index };
    dragOverThumb.value = null;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData(THUMB_DRAG_MIME, String(index));
}

function onThumbDragOver(stageKey, index, event) {
    if (! dragThumb.value || dragThumb.value.stage !== stageKey) {
        return;
    }

    event.dataTransfer.dropEffect = 'move';
    dragOverThumb.value = { stage: stageKey, index };
}

function onThumbDragLeave(stageKey, index, event) {
    if (event.currentTarget?.contains(event.relatedTarget)) {
        return;
    }

    if (dragOverThumb.value?.stage === stageKey && dragOverThumb.value?.index === index) {
        dragOverThumb.value = null;
    }
}

async function onThumbDrop(stageKey, dropIndex, event) {
    event.stopPropagation();

    const fromIndex = dragThumb.value?.stage === stageKey ? dragThumb.value.index : null;

    dragThumb.value = null;
    dragOverThumb.value = null;

    if (fromIndex === null || fromIndex === dropIndex || ! props.vehicle?.id) {
        return;
    }

    const urls = [...stageUrls(stageKey)];
    const [moved] = urls.splice(fromIndex, 1);

    urls.splice(dropIndex, 0, moved);

    const previousOverride = orderOverrides.value[stageKey];

    orderOverrides.value = {
        ...orderOverrides.value,
        [stageKey]: urls,
    };

    reorderingStage.value = stageKey;

    try {
        const result = await reorderVehicleGallery(props.vehicle.id, stageKey, urls);
        const vehiclePayload = result.data ?? result;

        displayVehicle.value = mergeGalleryIntoVehicle(props.vehicle, {
            images_by_stage: vehiclePayload.images_by_stage,
            images: vehiclePayload.images,
            uploaded_images: vehiclePayload.uploaded_images,
            gallery_fresh: true,
        });
        clearOrderOverrides();
        emit('updated', vehiclePayload);

        toast.add({
            severity: 'success',
            summary: 'تم تحديث الترتيب',
            detail: 'تم حفظ ترتيب الصور بنجاح',
            life: 2500,
        });
    } catch (e) {
        if (previousOverride) {
            orderOverrides.value = {
                ...orderOverrides.value,
                [stageKey]: previousOverride,
            };
        } else {
            const { [stageKey]: _removed, ...rest } = orderOverrides.value;

            orderOverrides.value = rest;
        }

        toast.add({
            severity: 'error',
            summary: 'فشل إعادة الترتيب',
            detail: e.response?.data?.message || 'تعذر حفظ ترتيب الصور',
            life: 4000,
        });
    } finally {
        reorderingStage.value = null;
    }
}

function onThumbDragEnd() {
    dragThumb.value = null;
    dragOverThumb.value = null;
}

async function onFilesSelected(stageKey, event) {
    const input = event.target;
    const files = [...(input.files ?? [])];

    input.value = '';

    if (! files.length) {
        return;
    }

    await uploadFiles(stageKey, files);
}

async function onZipSelected(stageKey, event) {
    const input = event.target;
    const file = input.files?.[0];

    input.value = '';

    if (! file) {
        return;
    }

    if (! isZipFile(file)) {
        toast.add({
            severity: 'warn',
            summary: 'ملف غير مدعوم',
            detail: 'يُقبل ملف ZIP فقط',
            life: 3500,
        });

        return;
    }

    await uploadZipFile(stageKey, file);
}

async function uploadZipFile(stageKey, zipFile) {
    if (! zipFile || ! props.vehicle?.id) {
        return;
    }

    zipUploadingStage.value = stageKey;

    try {
        const result = await uploadVehicleZipImages(props.vehicle.id, stageKey, zipFile);
        const galleryPayload = result.data?.gallery;

        if (galleryPayload) {
            displayVehicle.value = mergeGalleryIntoVehicle(props.vehicle, galleryPayload);
            galleryFresh.value = Boolean(galleryPayload.gallery_fresh);
            galleryTokenExpired.value = Boolean(galleryPayload.gallery_token_expired);
            galleryError.value = galleryPayload.gallery_error ?? null;
            galleryNewImagesCount.value = Number(galleryPayload.gallery_new_images_count ?? result.data?.uploaded ?? 0);
            emit('updated', displayVehicle.value);
        } else {
            await loadLiveGallery();
        }

        toast.add({
            severity: 'success',
            summary: 'تم رفع ZIP',
            detail: result.message || 'تم رفع الصور إلى Vinstack وتحديث المعرض',
            life: 4500,
        });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'فشل رفع ZIP',
            detail: e.message || 'تعذّر رفع ملف ZIP إلى Vinstack',
            life: 5000,
        });
    } finally {
        zipUploadingStage.value = null;
    }
}

async function uploadFiles(stageKey, files) {
    if (! files.length || ! props.vehicle?.id) {
        return;
    }

    uploadingStage.value = stageKey;

    try {
        const result = await uploadVehicleImages(props.vehicle.id, stageKey, files);
        const vehiclePayload = result.data?.vehicle ?? result.data;

        emit('updated', vehiclePayload);
        await loadLiveGallery();

        toast.add({
            severity: 'success',
            summary: 'تم الرفع',
            detail: result.message || 'تم رفع الصور بنجاح وتحديث المعرض',
            life: 3500,
        });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'فشل الرفع',
            detail: e.response?.data?.message || 'تعذر رفع الصور',
            life: 4000,
        });
    } finally {
        uploadingStage.value = null;
    }
}

function confirmRemoveUploaded(url) {
    const imageId = uploadedImageId(url);

    if (! imageId || ! props.vehicle?.id) {
        return;
    }

    confirm.require({
        message: 'هل أنت متأكد من حذف هذه الصورة؟ لن يتمكن التاجر من رؤيتها بعد الحذف.',
        header: 'حذف الصورة',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'إلغاء',
        acceptLabel: 'حذف',
        acceptClass: 'p-button-danger',
        accept: () => removeUploaded(url),
    });
}

async function removeUploaded(url) {
    const imageId = uploadedImageId(url);

    if (! imageId || ! props.vehicle?.id) {
        return;
    }

    deletingId.value = imageId;

    try {
        const result = await deleteVehicleImage(props.vehicle.id, imageId);

        emit('updated', result.data);
        await loadLiveGallery();

        toast.add({
            severity: result.cloudinary_warning ? 'warn' : 'success',
            summary: 'تم الحذف',
            detail: result.message || result.cloudinary_warning || 'تم حذف الصورة من المعرض',
            life: 3000,
        });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'فشل الحذف',
            detail: e.response?.data?.message || 'تعذر حذف الصورة',
            life: 4000,
        });
    } finally {
        deletingId.value = null;
    }
}

function openZoom(url) {
    if (! url) {
        return;
    }

    zoomStartUrl.value = url;
    zoomVisible.value = true;
}
</script>

<style scoped>
.photos-section-header {
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--vs-border);
}

.photos-section-header--dealer {
    margin-bottom: 0.75rem;
    border-bottom: none;
    padding-bottom: 0;
}

.dealer-photos-card {
    padding: 1rem;
    border: 1px solid var(--vs-border);
    border-radius: 12px;
    background: var(--vs-surface-elevated);
    box-shadow: var(--admin-shadow, 0 1px 3px rgb(0 0 0 / 6%));
}

.photos-section-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text-primary, var(--vs-text));
}

.photos-section-sub {
    margin: 0.35rem 0 0;
    font-size: 0.8rem;
    color: var(--text-muted, var(--vs-text-muted));
    line-height: 1.4;
}

.preview-block {
    position: relative;
    margin-bottom: 1rem;
    border: 1px solid var(--vs-border);
    border-radius: 12px;
    overflow: hidden;
    background: var(--vs-surface-elevated);
    max-width: 320px;
}

.preview-img {
    width: 100%;
    max-height: 180px;
    object-fit: cover;
    display: block;
    filter: saturate(0.92);
}

.preview-label {
    position: absolute;
    top: 8px;
    inset-inline-start: 8px;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: rgb(0 0 0 / 55%);
    color: #fff;
    font-size: 0.75rem;
}

.gallery-block {
    margin-top: 0.25rem;
}

.gallery-main {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.gallery-nav {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    border: 1px solid var(--vs-border);
    border-radius: 999px;
    background: var(--vs-surface);
    color: var(--vs-text-secondary);
    cursor: pointer;
    display: grid;
    place-items: center;
}

.gallery-nav i {
    font-size: 1.15rem;
}

.gallery-nav:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

.main-photo {
    position: relative;
    display: block;
    flex: 1;
    min-width: 0;
    padding: 0;
    border: 0;
    background: #111;
    cursor: zoom-in;
}

.main-photo img {
    width: 100%;
    max-height: 420px;
    object-fit: contain;
    display: block;
}

.gallery-thumb-strip {
    display: flex;
    gap: 0.45rem;
    margin-top: 0.65rem;
    overflow-x: auto;
    padding-bottom: 0.25rem;
    scroll-behavior: smooth;
}

.gallery-thumb-btn {
    position: relative;
    flex-shrink: 0;
    padding: 0;
    border: 2px solid transparent;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    background: var(--vs-surface-elevated);
}

.gallery-thumb-btn.active {
    border-color: var(--admin-accent);
}

.gallery-thumb-btn img {
    width: 72px;
    height: 54px;
    object-fit: cover;
    display: block;
}

.gallery-counter {
    margin: 0.45rem 0 0;
    text-align: center;
    font-size: 0.75rem;
    color: var(--text-muted, var(--vs-text-muted));
}

.zoom-hint {
    position: absolute;
    inset-inline-end: 12px;
    bottom: 12px;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    background: rgb(0 0 0 / 55%);
    color: #fff;
    font-size: 0.85rem;
}

.local-badge-inline {
    position: absolute;
    top: 12px;
    inset-inline-start: 12px;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    background: #ecfdf5;
    color: #047857;
    font-size: 0.72rem;
    font-weight: 600;
}

.local-dot {
    position: absolute;
    top: 4px;
    inset-inline-end: 4px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 2px #fff;
}

.no-hd,
.no-photos {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.25rem;
    color: var(--text-muted, var(--vs-text-muted));
    background: var(--vs-surface-elevated);
    border: 1px dashed var(--vs-border);
    border-radius: 12px;
}

.stage-card {
    border: 1px solid var(--vs-border);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 0.85rem;
    background: var(--vs-surface);
    box-shadow: var(--admin-shadow);
    transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}

.stage-card--dragover {
    border-color: var(--admin-accent);
    background: var(--status-terminal-bg);
    box-shadow: 0 0 0 3px rgb(59 130 246 / 15%);
}

.stage-card-header {
    margin-bottom: 0.75rem;
}

.stage-title {
    margin: 0 0 0.45rem;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-primary, var(--vs-text));
}

.stage-counts {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.count-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
}

.count-pill--vinstack {
    background: var(--status-terminal-bg);
    color: var(--status-terminal-fg);
}

.count-pill--local {
    background: var(--status-new-bg);
    color: var(--status-new-fg);
}

.stage-dropzone-hint {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    margin-bottom: 0.65rem;
    padding: 0.55rem 0.75rem;
    border: 1px dashed var(--vs-border);
    border-radius: 10px;
    font-size: 0.78rem;
    color: var(--text-muted, var(--vs-text-muted));
    background: var(--vs-surface-elevated);
}

.stage-card--dragover .stage-dropzone-hint {
    border-color: var(--admin-accent);
    color: var(--status-terminal-fg);
    background: var(--status-terminal-bg);
}

.stage-upload-row {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 0.85rem;
}

.upload-btn {
    width: 100%;
}

.upload-btn--zip :deep(.p-button-icon) {
    color: var(--vs-text-secondary);
}

.zip-upload-progress {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.78rem;
    color: var(--status-terminal-fg);
}

.file-input {
    display: none;
}

.stage-thumbs {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(88px, 1fr));
    gap: 0.75rem;
}

.thumb-card {
    position: relative;
    cursor: grab;
}

.thumb-card:active {
    cursor: grabbing;
}

.thumb-card--dragging {
    opacity: 0.45;
}

.thumb-card--drag-over {
    outline: 2px dashed var(--admin-accent);
    outline-offset: 3px;
    border-radius: 10px;
}

.thumb-card--uploaded {
    outline: 2px solid #a7f3d0;
    outline-offset: 2px;
    border-radius: 10px;
}

.thumb-btn {
    display: block;
    width: 100%;
    padding: 0;
    border: 1px solid var(--vs-border);
    border-radius: 8px;
    overflow: hidden;
    cursor: zoom-in;
    background: var(--vs-surface-elevated);
}

.thumb-btn img {
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
    display: block;
}

.source-tag {
    display: block;
    margin-top: 0.3rem;
    font-size: 0.62rem;
    font-weight: 600;
    text-align: center;
}

.source-tag--local {
    color: var(--status-new-fg);
}

.source-tag--vinstack {
    color: var(--vs-text-secondary);
}

.thumb-delete {
    position: absolute;
    top: 4px;
    inset-inline-end: 4px;
    opacity: 0;
    transition: opacity 0.15s ease;
    box-shadow: 0 1px 4px rgb(0 0 0 / 20%);
}

.thumb-card:hover .thumb-delete,
.thumb-card:focus-within .thumb-delete {
    opacity: 1;
}

.stage-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    margin: 0;
    padding: 1rem;
    font-size: 0.82rem;
    color: var(--text-muted, var(--vs-text-muted));
    background: var(--vs-surface-elevated);
    border: 1px dashed var(--vs-border);
    border-radius: 10px;
}

.gallery-loading {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.65rem 0.85rem;
    margin-bottom: 0.75rem;
    border-radius: 10px;
    background: var(--vs-surface-elevated);
    color: var(--vs-text-muted);
    font-size: 0.9rem;
}

.gallery-warning {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.65rem 0.85rem;
    margin-bottom: 0.75rem;
    border-radius: 10px;
    font-size: 0.88rem;
    line-height: 1.45;
}

.gallery-warning--danger {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.gallery-warning--ok {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}
</style>
