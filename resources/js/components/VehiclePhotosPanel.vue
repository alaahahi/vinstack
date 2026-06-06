<template>
    <div class="photos-panel">
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
                                مرفوعة محلياً: {{ stageCounts(stage.key).local }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="stage-dropzone-hint">
                    <i class="pi pi-cloud-upload" />
                    <span>اسحب الصور هنا أو استخدم زر الرفع</span>
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
                    <Button
                        icon="pi pi-upload"
                        label="رفع صور جديدة"
                        :loading="uploadingStage === stage.key"
                        class="upload-btn btn-add"
                        @click="triggerUpload(stage.key)"
                    />
                </div>

                <div v-if="stageUrls(stage.key).length" class="stage-thumbs">
                    <div
                        v-for="url in stageUrls(stage.key)"
                        :key="url"
                        class="thumb-card"
                        :class="{ 'thumb-card--local': isLocalUrl(url) }"
                    >
                        <button type="button" class="thumb-btn" @click="openZoom(url)">
                            <img :src="url" :alt="label" loading="lazy" decoding="async" />
                        </button>
                        <span v-if="isLocalUrl(url)" class="source-tag source-tag--local">مرفوعة محلياً</span>
                        <span v-else class="source-tag source-tag--vinstack">Vinstack</span>
                        <Button
                            v-if="isLocalUrl(url)"
                            icon="pi pi-trash"
                            severity="danger"
                            rounded
                            size="small"
                            class="thumb-delete"
                            :loading="deletingId === localId(url)"
                            aria-label="حذف الصورة"
                            @click="confirmRemoveLocal(url)"
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
                :vehicle="vehicle"
                :start-url="zoomStartUrl"
            />
        </template>

        <VehicleImageGallery v-else-if="compact" :vehicle="vehicle" show-button />

        <template v-else>
            <header class="photos-section-header photos-section-header--dealer">
                <h3 class="photos-section-title">صور السيارة</h3>
            </header>

            <div v-if="preview" class="preview-block">
                <img :src="preview" :alt="label" class="preview-img" loading="lazy" decoding="async" />
                <span class="preview-label">معاينة</span>
            </div>

            <div v-if="galleryImages.length" class="gallery-block">
                <div class="gallery-main">
                    <button
                        type="button"
                        class="gallery-nav gallery-nav--prev"
                        :disabled="galleryIndex === 0"
                        aria-label="الصورة السابقة"
                        @click="goGalleryPrev"
                    >
                        <i class="pi pi-chevron-left" />
                    </button>

                    <button type="button" class="main-photo" @click="openZoom(currentGalleryUrl)">
                        <img
                            v-if="currentGalleryUrl"
                            :key="currentGalleryUrl"
                            :src="currentGalleryUrl"
                            :alt="label"
                            decoding="async"
                        />
                        <span v-if="isLocalUrl(currentGalleryUrl)" class="local-badge-inline">مرفوعة محلياً</span>
                        <span class="zoom-hint"><i class="pi pi-search-plus" /> تكبير</span>
                    </button>

                    <button
                        type="button"
                        class="gallery-nav gallery-nav--next"
                        :disabled="galleryIndex >= galleryImages.length - 1"
                        aria-label="الصورة التالية"
                        @click="goGalleryNext"
                    >
                        <i class="pi pi-chevron-right" />
                    </button>
                </div>

                <div v-if="galleryImages.length > 1" class="gallery-thumb-strip">
                    <button
                        v-for="(url, index) in galleryImages"
                        :key="`${index}-${url}`"
                        type="button"
                        class="gallery-thumb-btn"
                        :class="{ active: index === galleryIndex }"
                        @click="setGalleryIndex(index)"
                    >
                        <img :src="url" :alt="`${label} thumbnail`" loading="lazy" decoding="async" />
                        <span v-if="isLocalUrl(url)" class="local-dot" title="مرفوعة محلياً" />
                    </button>
                </div>

                <p class="gallery-counter">{{ galleryIndex + 1 }} / {{ galleryImages.length }}</p>
            </div>

            <div v-else-if="preview" class="no-hd">
                <i class="pi pi-info-circle" />
                <span>لا توجد صور عالية الدقة — المعاينة فقط متاحة</span>
            </div>

            <div v-else class="no-photos">
                <i class="pi pi-image" />
                <span>لا توجد صور لهذه السيارة</span>
            </div>

            <VehicleGalleryLightbox
                v-model:visible="zoomVisible"
                :vehicle="vehicle"
                :start-url="zoomStartUrl"
            />
        </template>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import VehicleImageGallery from './VehicleImageGallery.vue';
import VehicleGalleryLightbox from './VehicleGalleryLightbox.vue';
import {
    GALLERY_STAGES,
    vehicleGalleryByStage,
    vehicleGalleryImages,
    vehicleLabel,
    vehicleThumbnail,
    vehicleUploadedImages,
} from '../utils/vehicleImages';
import {
    deleteVehicleImage,
    isLocalUploadedUrl,
    localImageIdForUrl,
    uploadVehicleImages,
} from '../utils/vehicleImageUpload';

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
});

const emit = defineEmits(['updated']);

const toast = useToast();
const confirm = useConfirm();

const zoomVisible = ref(false);
const zoomStartUrl = ref(null);
const uploadingStage = ref(null);
const deletingId = ref(null);
const fileInputs = ref({});
const dragOverStage = ref(null);
const galleryIndex = ref(0);
const galleryNavLock = ref(false);

const preview = computed(() => vehicleThumbnail(props.vehicle));
const galleryImages = computed(() => vehicleGalleryImages(props.vehicle));
const label = computed(() => vehicleLabel(props.vehicle));
const stages = computed(() => vehicleGalleryByStage(props.vehicle));
const uploadedList = computed(() => vehicleUploadedImages(props.vehicle));
const currentGalleryUrl = computed(() => galleryImages.value[galleryIndex.value] ?? null);

watch(galleryImages, (images) => {
    if (galleryIndex.value >= images.length) {
        galleryIndex.value = Math.max(0, images.length - 1);
    }
});

function setFileInput(stageKey, el) {
    if (el) {
        fileInputs.value[stageKey] = el;
    }
}

function stageUrls(stageKey) {
    return stages.value[stageKey] ?? [];
}

function stageCounts(stageKey) {
    const urls = stageUrls(stageKey);
    const local = urls.filter((url) => isLocalUrl(url)).length;

    return {
        total: urls.length,
        local,
        vinstack: urls.length - local,
    };
}

function isLocalUrl(url) {
    return isLocalUploadedUrl(url, uploadedList.value);
}

function localId(url) {
    return localImageIdForUrl(url, uploadedList.value);
}

function triggerUpload(stageKey) {
    fileInputs.value[stageKey]?.click();
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
    dragOverStage.value = null;

    const files = [...(event.dataTransfer?.files ?? [])].filter((file) => file.type.startsWith('image/'));

    if (! files.length) {
        return;
    }

    uploadFiles(stageKey, files);
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

async function uploadFiles(stageKey, files) {
    if (! files.length || ! props.vehicle?.id) {
        return;
    }

    uploadingStage.value = stageKey;

    try {
        const result = await uploadVehicleImages(props.vehicle.id, stageKey, files);
        const vehiclePayload = result.data?.vehicle ?? result.data;

        emit('updated', vehiclePayload);

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

function confirmRemoveLocal(url) {
    const imageId = localId(url);

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
        accept: () => removeLocal(url),
    });
}

async function removeLocal(url) {
    const imageId = localId(url);

    if (! imageId || ! props.vehicle?.id) {
        return;
    }

    deletingId.value = imageId;

    try {
        const result = await deleteVehicleImage(props.vehicle.id, imageId);

        emit('updated', result.data);

        toast.add({
            severity: 'success',
            summary: 'تم الحذف',
            detail: result.message || 'تم حذف الصورة من المعرض',
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

function setGalleryIndex(index) {
    if (galleryNavLock.value || index === galleryIndex.value) {
        return;
    }

    galleryNavLock.value = true;
    galleryIndex.value = index;

    window.setTimeout(() => {
        galleryNavLock.value = false;
    }, 120);
}

function goGalleryPrev() {
    if (galleryIndex.value > 0) {
        setGalleryIndex(galleryIndex.value - 1);
    }
}

function goGalleryNext() {
    if (galleryIndex.value < galleryImages.value.length - 1) {
        setGalleryIndex(galleryIndex.value + 1);
    }
}
</script>

<style scoped>
.photos-section-header {
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #ececef;
}

.photos-section-header--dealer {
    margin-bottom: 0.75rem;
}

.photos-section-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: #18181b;
}

.photos-section-sub {
    margin: 0.35rem 0 0;
    font-size: 0.8rem;
    color: #71717a;
    line-height: 1.4;
}

.preview-block {
    position: relative;
    margin-bottom: 1rem;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    overflow: hidden;
    background: #fafafa;
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
    width: 32px;
    height: 32px;
    border: 1px solid #e4e4e7;
    border-radius: 999px;
    background: #fff;
    color: #3f3f46;
    cursor: pointer;
    display: grid;
    place-items: center;
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
    background: #fafafa;
}

.gallery-thumb-btn.active {
    border-color: #2563eb;
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
    color: #71717a;
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
    color: #71717a;
    background: #fafafa;
    border: 1px dashed #d4d4d8;
    border-radius: 12px;
}

.stage-card {
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 0.85rem;
    background: #fff;
    box-shadow: 0 1px 2px rgb(0 0 0 / 3%);
    transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}

.stage-card--dragover {
    border-color: #2563eb;
    background: #eff6ff;
    box-shadow: 0 0 0 3px rgb(37 99 235 / 15%);
}

.stage-card-header {
    margin-bottom: 0.75rem;
}

.stage-title {
    margin: 0 0 0.45rem;
    font-size: 0.9rem;
    font-weight: 700;
    color: #18181b;
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
    background: #eff6ff;
    color: #1d4ed8;
}

.count-pill--local {
    background: #ecfdf5;
    color: #047857;
}

.stage-dropzone-hint {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    margin-bottom: 0.65rem;
    padding: 0.55rem 0.75rem;
    border: 1px dashed #d4d4d8;
    border-radius: 10px;
    font-size: 0.78rem;
    color: #71717a;
    background: #fafafa;
}

.stage-card--dragover .stage-dropzone-hint {
    border-color: #2563eb;
    color: #1d4ed8;
    background: #dbeafe;
}

.stage-upload-row {
    margin-bottom: 0.85rem;
}

.upload-btn {
    width: 100%;
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
}

.thumb-card--local {
    outline: 2px solid #a7f3d0;
    outline-offset: 2px;
    border-radius: 10px;
}

.thumb-btn {
    display: block;
    width: 100%;
    padding: 0;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    overflow: hidden;
    cursor: zoom-in;
    background: #fafafa;
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
    color: #047857;
}

.source-tag--vinstack {
    color: #3f3f46;
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
    color: #71717a;
    background: #fafafa;
    border: 1px dashed #d4d4d8;
    border-radius: 10px;
}
</style>
