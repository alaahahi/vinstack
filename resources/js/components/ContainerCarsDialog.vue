<template>
    <Dialog
        v-model:visible="dialogVisible"
        modal
        :draggable="false"
        :style="{ width: 'min(1100px, 96vw)' }"
        :pt="{ root: { class: 'container-cars-dialog' } }"
        @hide="onHide"
        @show="onShow"
    >
        <template #header>
            <div class="cars-dialog-header">
                <div>
                    <div class="cars-dialog-kicker">سيارات الحاوية</div>
                    <h2 class="cars-dialog-title">{{ headerContainer }}</h2>
                </div>
                <div v-if="headerMeta" class="cars-dialog-meta">
                    <span v-if="headerMeta.booking_number" class="meta-chip">
                        <span class="meta-label">BKG</span>
                        {{ headerMeta.booking_number }}
                    </span>
                    <span v-if="headerMeta.invoice_ref" class="meta-chip">
                        <i class="pi pi-receipt" />
                        {{ headerMeta.invoice_ref }}
                    </span>
                    <span class="status-pill" :class="statusClass">
                        <span class="status-dot" />
                        {{ statusLabel }}
                    </span>
                    <span v-if="headerMeta.eta" class="meta-chip meta-chip--eta">
                        ETA {{ formatContainerDate(headerMeta.eta) }}
                    </span>
                    <span class="meta-chip meta-chip--count">
                        <i class="pi pi-car" />
                        {{ vehicleRows.length }}
                    </span>
                </div>
            </div>
        </template>

        <div class="cars-dialog-toolbar">
            <input
                ref="zipInputRef"
                type="file"
                accept=".zip,application/zip"
                class="zip-input-hidden"
                @change="onZipSelected"
            />
            <Button
                v-if="showZipUpload"
                icon="pi pi-file-import"
                label="اختيار ZIP"
                size="small"
                outlined
                :loading="zipLoading"
                :disabled="loading || uploadProgress !== null"
                @click="triggerZipUpload"
            />
            <Button
                v-if="pendingZipImages?.length"
                icon="pi pi-cloud-upload"
                label="رفع إلى Cloudinary"
                size="small"
                :loading="zipLoading || uploadProgress !== null"
                :disabled="loading"
                @click="uploadPendingZip"
            />
            <Button
                v-if="showZipUpload && apiRole === 'admin'"
                icon="pi pi-cog"
                label="فحص الإعدادات"
                size="small"
                severity="secondary"
                outlined
                :loading="settingsCheckLoading"
                :disabled="loading || zipLoading"
                @click="checkCloudinarySettings"
            />
            <span v-if="pendingZipImages?.length" class="zip-pending-count">
                <i class="pi pi-images" />
                {{ pendingZipImages.length }} صورة جاهزة للرفع
            </span>
            <ContainerImageGallery
                v-if="containerManageImages.length"
                :images="containerManageImages"
                variant="row"
                show-button
                label="معرض الحاوية"
            />
            <span v-else-if="zipMeta" class="zip-meta">
                <i class="pi pi-images" />
                {{ zipMeta.count }} صورة ({{ zipMeta.matched }} مطابقة)
            </span>
            <span v-if="settingsCheckResult" class="zip-settings-result" :class="settingsCheckResult.ok ? 'zip-settings-result--ok' : 'zip-settings-result--error'">
                {{ settingsCheckResult.summary }}
            </span>
            <span v-if="uploadProgress !== null" class="zip-upload-progress">
                <ProgressSpinner style="width: 1.1rem; height: 1.1rem" />
                رفع إلى Cloudinary {{ uploadProgress }}%
            </span>
        </div>

        <div v-if="loading" class="cars-dialog-loading">
            <ProgressSpinner style="width: 36px; height: 36px" />
            <span>جاري تحميل السيارات…</span>
        </div>

        <div v-else-if="error" class="cars-dialog-error">
            <i class="pi pi-exclamation-circle" />
            <p>{{ error }}</p>
            <Button label="إعادة المحاولة" size="small" icon="pi pi-refresh" @click="load" />
        </div>

        <DataTable
            v-else
            :value="vehicleRows"
            size="small"
            striped-rows
            scrollable
            scroll-height="flex"
            class="container-cars-table"
            :row-class="() => 'container-cars-row'"
        >
            <Column header="تسلسل" style="width: 4rem; text-align: center">
                <template #body="{ index }">
                    <span class="seq-no">{{ index + 1 }}</span>
                </template>
            </Column>

            <Column header="صورة" style="width: 5.5rem">
                <template #body="{ data }">
                    <VehicleImageGallery
                        :vehicle="displayVehicle(data)"
                        variant="table"
                        :api-mode="apiRole"
                    />
                </template>
            </Column>

            <Column header="التفاصيل">
                <template #body="{ data }">
                    <div class="vehicle-detail-cell">
                        <span class="vehicle-title">{{ vehicleTitle(data) }}</span>
                        <VinCopyLabel v-if="data.vin" :vin="data.vin" size="compact" />
                    </div>
                </template>
            </Column>

            <Column header="Lot & VIN">
                <template #body="{ data }">
                    <div class="lot-cell">
                        <span>{{ data.lot || data.raw_data?.lot || '—' }}</span>
                        <span class="lot-vin-sub">{{ data.vin || '—' }}</span>
                    </div>
                </template>
            </Column>

            <Column header="المزاد">
                <template #body="{ data }">
                    {{ data.auction || data.raw_data?.auction || '—' }}
                </template>
            </Column>

            <Column header="الوجهة">
                <template #body="{ data }">
                    {{ data.destination || data.raw_data?.destination || '—' }}
                </template>
            </Column>

            <Column header="تاريخ الشراء">
                <template #body="{ data }">
                    {{ formatPurchaseDate(data) }}
                </template>
            </Column>

            <Column header="صور" style="width: 4.5rem; text-align: center">
                <template #body="{ data }">
                    <button
                        type="button"
                        class="images-truck-btn"
                        :class="{ 'images-truck-btn--active': hasGallery(data) }"
                        :disabled="!hasGallery(data)"
                        :title="galleryButtonTitle(data)"
                        @click="openVehicleGallery(data)"
                    >
                        <i class="pi pi-truck" />
                        <span v-if="galleryImageCount(data)" class="images-truck-count">
                            {{ galleryImageCount(data) }}
                        </span>
                    </button>
                </template>
            </Column>
        </DataTable>

        <VehicleGalleryLightbox
            v-model:visible="galleryVisible"
            :vehicle="galleryVehicle"
            :api-mode="apiRole"
        />

        <VueEasyLightbox
            v-if="apiRole === 'admin'"
            :visible="containerGalleryVisible"
            :imgs="containerLightboxSlide"
            :index="0"
            :rtl="true"
            teleport="body"
            @hide="closeContainerGallery"
        />

        <ContainerGalleryLightbox
            v-else
            v-model:visible="containerGalleryVisible"
            :images="containerManageImages"
            :start-index="containerGalleryIndex"
        />

        <Dialog
            v-model:visible="containerManageVisible"
            modal
            :draggable="false"
            :header="apiRole === 'admin' ? 'معرض الحاوية' : 'صور الحاوية'"
            :style="{ width: 'min(920px, 96vw)' }"
            :pt="{ root: { class: 'container-gallery-manage-dialog' } }"
        >
            <p v-if="apiRole === 'dealer'" class="container-gallery-view-note">
                <i class="pi pi-info-circle" />
                معرض للعرض فقط — صور مرفوعة من الإدارة
            </p>
            <p v-if="! containerManageImages.length" class="container-gallery-empty">
                لا توجد صور في معرض الحاوية
            </p>
            <div v-else class="container-gallery-grid">
                <div
                    v-for="(image, index) in containerManageImages"
                    :key="image.id ?? image.url"
                    class="container-gallery-thumb"
                >
                    <ContainerImageGallery
                        :images="[image]"
                        variant="table"
                        :label="image.vin || `صورة ${index + 1}`"
                    />
                    <span v-if="image.vin" class="container-gallery-vin">{{ image.vin }}</span>
                    <Button
                        v-if="apiRole === 'admin' && image.id"
                        icon="pi pi-trash"
                        severity="danger"
                        rounded
                        size="small"
                        class="container-gallery-delete"
                        :loading="deletingContainerImageId === image.id"
                        aria-label="حذف الصورة"
                        @click="confirmDeleteContainerImage(image)"
                    />
                </div>
            </div>
        </Dialog>

        <Teleport v-if="apiRole === 'admin'" to="body">
            <div
                v-if="containerGalleryVisible && containerGalleryImgs.length > 1"
                class="gallery-nav-bar"
                role="navigation"
                aria-label="تنقل صور الحاوية"
            >
                <button
                    type="button"
                    class="gallery-nav-btn"
                    :disabled="containerGalleryIndex === 0"
                    aria-label="الصورة السابقة"
                    @click="containerGalleryPrev"
                >
                    <i class="pi pi-chevron-right" />
                </button>
                <span class="gallery-nav-counter">
                    {{ containerGalleryIndex + 1 }} / {{ containerGalleryImgs.length }}
                </span>
                <button
                    type="button"
                    class="gallery-nav-btn"
                    :disabled="containerGalleryIndex >= containerGalleryImgs.length - 1"
                    aria-label="الصورة التالية"
                    @click="containerGalleryNext"
                >
                    <i class="pi pi-chevron-left" />
                </button>
            </div>
            <div
                v-if="containerGalleryVisible"
                class="gallery-stage-bar"
                role="status"
            >
                <span class="gallery-stage-tab active">
                    <span class="gallery-stage-label">معرض الحاوية</span>
                    <span class="gallery-stage-count">{{ containerGalleryImgs.length }}</span>
                </span>
            </div>
        </Teleport>
    </Dialog>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import VueEasyLightbox from 'vue-easy-lightbox';
import api from '../api/client';
import VehicleImageGallery from './VehicleImageGallery.vue';
import ContainerImageGallery from './ContainerImageGallery.vue';
import ContainerGalleryLightbox from './ContainerGalleryLightbox.vue';
import VehicleGalleryLightbox from './VehicleGalleryLightbox.vue';
import VinCopyLabel from './VinCopyLabel.vue';
import {
    containerListStatusClass,
    containerListStatusLabel,
    containerRefs,
    formatContainerDate,
} from '../utils/containerMeta';
import { vehicleTitle as buildVehicleTitle } from '../utils/vehicleMeta';
import { hasVehicleGallery, hasVehiclePreview, vehicleGalleryCount } from '../utils/vehicleImages';
import {
    containerGalleryUrls,
    containerRefKey,
    containerZipMeta,
    extractZipImagesForContainer,
    getContainerZipImages,
    mergeZipImagesIntoVehicle,
    applyCloudinaryContainerPayload,
    saveContainerZipImages,
    vehicleZipImageCount,
} from '../utils/containerZipImages';
import {
    deleteContainerCloudImage,
    fetchContainerCloudImages,
    formatCloudinaryUploadError,
    testCloudinaryConnection,
    uploadContainerImagesToCloud,
} from '../utils/containerCloudinaryUpload';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    container: {
        type: Object,
        default: null,
    },
    apiRole: {
        type: String,
        default: 'admin',
        validator: (value) => ['admin', 'dealer'].includes(value),
    },
    showZipUpload: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:visible']);

const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();

const loading = ref(false);
const error = ref(null);
const headerMeta = ref(null);
const vehicleRows = ref([]);
const zipInputRef = ref(null);
const zipLoading = ref(false);
const uploadProgress = ref(null);
const pendingZipImages = ref(null);
const settingsCheckLoading = ref(false);
const settingsCheckResult = ref(null);
const zipPayload = ref(null);
const galleryVisible = ref(false);
const galleryVehicle = ref(null);
const containerGalleryVisible = ref(false);
const containerGalleryIndex = ref(0);
const containerManageVisible = ref(false);
const deletingContainerImageId = ref(null);

const dialogVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});

const refs = computed(() => containerRefs(props.container ?? headerMeta.value ?? {}));

const headerContainer = computed(() =>
    refs.value.container || refs.value.booking || '—',
);

const statusLabel = computed(() =>
    containerListStatusLabel(headerMeta.value ?? props.container ?? {}, t),
);

const statusClass = computed(() =>
    containerListStatusClass(headerMeta.value ?? props.container ?? {}),
);

const containerKey = computed(() => containerRefKey(props.container ?? headerMeta.value ?? {}));

const zipMeta = computed(() => {
    if (zipPayload.value) {
        return {
            count: zipPayload.value.images?.length ?? 0,
            matched: Object.keys(zipPayload.value.byVin ?? {}).length,
        };
    }

    return containerZipMeta(containerKey.value);
});

const containerGalleryImgs = computed(() => {
    const urls = containerGalleryUrls(zipPayload.value);

    return urls.map((src, index) => ({
        src,
        title: `صورة ${index + 1}`,
    }));
});

const containerLightboxSlide = computed(() => {
    const slide = containerGalleryImgs.value[containerGalleryIndex.value];

    return slide ? [slide] : [];
});

const containerManageImages = computed(() => zipPayload.value?.images ?? []);

const apiPrefix = computed(() => (props.apiRole === 'dealer' ? '/dealer' : '/admin'));

function vehicleTitle(vehicle) {
    return buildVehicleTitle(vehicle) || vehicle?.title || vehicle?.vin || '—';
}

function formatPurchaseDate(vehicle) {
    const raw = vehicle?.purchase_date ?? vehicle?.raw_data?.purchase_date;

    return formatContainerDate(raw) || '—';
}

function displayVehicle(vehicle) {
    return mergeZipImagesIntoVehicle(vehicle, zipPayload.value);
}

function hasGallery(vehicle) {
    const merged = displayVehicle(vehicle);

    return hasVehicleGallery(merged) || hasVehiclePreview(merged);
}

function galleryImageCount(vehicle) {
    const merged = displayVehicle(vehicle);

    return vehicleGalleryCount(merged);
}

function galleryButtonTitle(vehicle) {
    const count = galleryImageCount(vehicle);

    if (! count) {
        return 'لا توجد صور';
    }

    const zipCount = vehicleZipImageCount(vehicle, zipPayload.value);

    if (zipCount && zipCount < count) {
        return `عرض ${count} صورة (${zipCount} من ZIP)`;
    }

    return `عرض ${count} صورة`;
}

function openVehicleGallery(vehicle) {
    if (! hasGallery(vehicle)) {
        return;
    }

    galleryVehicle.value = displayVehicle(vehicle);
    galleryVisible.value = true;
}

function openContainerGallery() {
    if (! containerManageImages.value.length) {
        toast.add({
            severity: 'warn',
            summary: 'لا توجد صور',
            detail: 'لا توجد صور مرفوعة لهذه الحاوية بعد',
            life: 3500,
        });

        return;
    }

    containerGalleryIndex.value = 0;

    if (props.apiRole === 'admin') {
        containerManageVisible.value = true;

        return;
    }

    containerGalleryVisible.value = true;
}

function confirmDeleteContainerImage(image) {
    if (! image?.id || props.apiRole !== 'admin') {
        return;
    }

    confirm.require({
        message: 'هل أنت متأكد من حذف هذه الصورة من معرض الحاوية؟',
        header: 'حذف الصورة',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'إلغاء',
        acceptLabel: 'حذف',
        acceptClass: 'p-button-danger',
        accept: () => removeContainerImage(image),
    });
}

async function removeContainerImage(image) {
    const ref = containerApiRef();

    if (! ref || ! image?.id) {
        return;
    }

    deletingContainerImageId.value = image.id;

    try {
        const result = await deleteContainerCloudImage(ref, image.id, apiPrefix.value);
        zipPayload.value = applyCloudinaryContainerPayload(containerKey.value, result.data);

        if (! containerManageImages.value.length) {
            containerManageVisible.value = false;
            closeContainerGallery();
        } else if (containerGalleryIndex.value >= containerGalleryImgs.value.length) {
            containerGalleryIndex.value = Math.max(0, containerGalleryImgs.value.length - 1);
        }

        toast.add({
            severity: result.cloudinary_warning ? 'warn' : 'success',
            summary: 'تم الحذف',
            detail: result.message || result.cloudinary_warning || 'تم حذف الصورة من معرض الحاوية',
            life: 3500,
        });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'فشل الحذف',
            detail: e.response?.data?.message || 'تعذر حذف الصورة',
            life: 4000,
        });
    } finally {
        deletingContainerImageId.value = null;
    }
}

function closeContainerGallery() {
    containerGalleryVisible.value = false;
    containerGalleryIndex.value = 0;
}

function containerGalleryPrev() {
    if (containerGalleryIndex.value > 0) {
        containerGalleryIndex.value -= 1;
    }
}

function containerGalleryNext() {
    if (containerGalleryIndex.value < containerGalleryImgs.value.length - 1) {
        containerGalleryIndex.value += 1;
    }
}

function onContainerGalleryKeydown(event) {
    if (! containerGalleryVisible.value) {
        return;
    }

    if (event.key === 'ArrowLeft') {
        containerGalleryNext();
    } else if (event.key === 'ArrowRight') {
        containerGalleryPrev();
    }
}

function containerApiRef() {
    const r = containerRefs(props.container ?? {});

    return r.container || r.booking || '';
}

async function load() {
    const ref = containerApiRef();

    if (! ref) {
        error.value = 'لا يوجد رقم حاوية أو حجز';

        return;
    }

    loading.value = true;
    error.value = null;

    try {
        const { data } = await api.get(`${apiPrefix.value}/containers/${encodeURIComponent(ref)}/vehicles`);
        headerMeta.value = data.data?.container ?? props.container;
        vehicleRows.value = data.data?.vehicles ?? [];
        hydrateZipFromMemory();
        await loadCloudImages();
    } catch (e) {
        error.value = e.response?.data?.message || 'تعذّر جلب سيارات الحاوية';
        vehicleRows.value = [];
    } finally {
        loading.value = false;
    }
}

function hydrateZipFromMemory() {
    zipPayload.value = getContainerZipImages(containerKey.value);
}

async function loadCloudImages() {
    const ref = containerApiRef();

    if (! ref) {
        return;
    }

    try {
        const payload = await fetchContainerCloudImages(ref, apiPrefix.value);

        if (payload?.images?.length) {
            zipPayload.value = applyCloudinaryContainerPayload(containerKey.value, payload);
        }
    } catch {
        // Cloudinary may be unconfigured or no images yet
    }
}

function onShow() {
    headerMeta.value = props.container;
    vehicleRows.value = props.container?.vehicles ?? [];
    hydrateZipFromMemory();
    load();
}

function onHide() {
    error.value = null;
    galleryVisible.value = false;
    closeContainerGallery();
    containerManageVisible.value = false;
    clearPendingZipImages();
    settingsCheckResult.value = null;
}

function clearPendingZipImages() {
    if (! pendingZipImages.value?.length) {
        pendingZipImages.value = null;

        return;
    }

    for (const image of pendingZipImages.value) {
        if (image.url?.startsWith('blob:')) {
            URL.revokeObjectURL(image.url);
        }
    }

    pendingZipImages.value = null;
}

function triggerZipUpload() {
    zipInputRef.value?.click();
}

async function onZipSelected(event) {
    const file = event.target?.files?.[0];

    if (event.target) {
        event.target.value = '';
    }

    if (! file) {
        return;
    }

    zipLoading.value = true;
    settingsCheckResult.value = null;

    try {
        clearPendingZipImages();
        const extracted = await extractZipImagesForContainer(file, vehicleRows.value);
        pendingZipImages.value = extracted.images;

        if (! extracted.images.length) {
            toast.add({
                severity: 'warn',
                summary: 'ملف ZIP فارغ',
                detail: 'لم يُعثر على صور داخل الملف',
                life: 4000,
            });

            return;
        }

        toast.add({
            severity: 'info',
            summary: 'تم تحليل ZIP',
            detail: `${extracted.images.length} صورة جاهزة — اضغط «فحص الإعدادات» ثم «رفع إلى Cloudinary»`,
            life: 5000,
        });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'تعذّر قراءة ZIP',
            detail: e.message || 'تحقق من صحة الملف',
            life: 5000,
        });
    } finally {
        zipLoading.value = false;
    }
}

async function checkCloudinarySettings() {
    settingsCheckLoading.value = true;
    settingsCheckResult.value = null;

    const readyCount = pendingZipImages.value?.length ?? 0;

    try {
        const result = await testCloudinaryConnection(apiPrefix.value);
        const parts = [
            result.configured === false ? 'غير مهيّأ' : `مهيّأ (${result.cloud_name || '—'})`,
            result.test_upload === 'ok' ? 'اختبار الرفع: ناجح' : null,
            result.test_upload === 'failed' ? `اختبار الرفع: ${result.test_upload_error}` : null,
            readyCount ? `${readyCount} صورة جاهزة للرفع` : null,
        ].filter(Boolean);

        settingsCheckResult.value = {
            ok: Boolean(result.ok),
            summary: parts.join(' · '),
        };

        toast.add({
            severity: result.ok ? 'success' : 'warn',
            summary: 'فحص Cloudinary',
            detail: parts.join(' · '),
            life: 7000,
        });
    } catch (e) {
        const detail = e.response?.data?.message
            || e.response?.data?.data?.message
            || e.message
            || 'تحقق من إعدادات Cloudinary في صفحة الإعدادات';

        settingsCheckResult.value = {
            ok: false,
            summary: readyCount
                ? `${detail} · ${readyCount} صورة جاهزة للرفع`
                : detail,
        };

        toast.add({
            severity: 'warn',
            summary: 'فحص Cloudinary',
            detail: settingsCheckResult.value.summary,
            life: 7000,
        });
    } finally {
        settingsCheckLoading.value = false;
    }
}

async function uploadPendingZip() {
    if (! pendingZipImages.value?.length) {
        return;
    }

    zipLoading.value = true;

    try {
        const ref = containerApiRef();
        const images = pendingZipImages.value;

        uploadProgress.value = 0;

        const payload = await uploadContainerImagesToCloud({
            containerRef: ref,
            images,
            apiPrefix: apiPrefix.value,
            replace: true,
            onProgress: ({ percent }) => {
                uploadProgress.value = percent;
            },
        });

        applyCloudinaryContainerPayload(containerKey.value, payload);
        zipPayload.value = getContainerZipImages(containerKey.value);
        clearPendingZipImages();

        toast.add({
            severity: 'success',
            summary: 'تم رفع الصور إلى Cloudinary',
            detail: `${payload.uploaded ?? payload.meta?.count ?? payload.images?.length ?? 0} صورة — ${payload.meta?.matched ?? Object.keys(payload.byVin ?? {}).length} مطابقة لشاصي`,
            life: 5000,
        });

        window.setTimeout(() => {
            toast.add({
                severity: 'info',
                summary: 'عرض الصور',
                detail: 'اضغط «معرض الحاوية» أو أيقونة الشاحنة في عمود صور لعرض المعرض',
                life: 7000,
            });
        }, 400);
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'تعذّر رفع الصور',
            detail: formatCloudinaryUploadError(e),
            life: 8000,
        });
    } finally {
        zipLoading.value = false;
        uploadProgress.value = null;
    }
}

watch(
    () => props.container,
    () => {
        if (props.visible) {
            hydrateZipFromMemory();
        }
    },
);

onMounted(() => {
    window.addEventListener('keydown', onContainerGalleryKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onContainerGalleryKeydown);
});
</script>

<style scoped>
.cars-dialog-header {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    width: 100%;
}

.cars-dialog-kicker {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--vs-text-muted);
}

.cars-dialog-title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    font-family: ui-monospace, monospace;
    color: var(--vs-text);
}

.cars-dialog-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    align-items: center;
}

.meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    background: var(--vs-surface-elevated);
    border: 1px solid var(--vs-border);
    font-size: 0.78rem;
    color: var(--vs-text-secondary);
}

.meta-label {
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--vs-text-subtle);
}

.meta-chip--eta {
    font-variant-numeric: tabular-nums;
}

.meta-chip--count {
    color: #2563eb;
    border-color: rgb(37 99 235 / 25%);
    background: rgb(37 99 235 / 8%);
}

.cars-dialog-toolbar {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
    margin-bottom: 0.85rem;
}

.zip-input-hidden {
    display: none;
}

.zip-meta {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.8rem;
    color: var(--vs-text-muted);
}

.zip-meta--link {
    padding: 0.25rem 0.5rem;
    border: 1px solid rgb(37 99 235 / 25%);
    border-radius: 8px;
    background: rgb(37 99 235 / 8%);
    color: #2563eb;
    cursor: pointer;
    font: inherit;
    transition: background 0.12s ease;
}

.zip-meta--link:hover {
    background: rgb(37 99 235 / 15%);
}

.zip-upload-progress {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    color: #2563eb;
}

.zip-pending-count {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #059669;
    padding: 0.2rem 0.55rem;
    border-radius: 8px;
    background: rgb(5 150 105 / 10%);
    border: 1px solid rgb(5 150 105 / 25%);
}

.zip-settings-result {
    flex: 1 1 100%;
    font-size: 0.78rem;
    padding: 0.35rem 0.55rem;
    border-radius: 8px;
    line-height: 1.35;
}

.zip-settings-result--ok {
    color: #047857;
    background: rgb(5 150 105 / 10%);
    border: 1px solid rgb(5 150 105 / 20%);
}

.zip-settings-result--error {
    color: #b45309;
    background: rgb(245 158 11 / 10%);
    border: 1px solid rgb(245 158 11 / 25%);
}

.cars-dialog-loading,
.cars-dialog-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 2.5rem 1rem;
    color: var(--vs-text-muted);
}

.cars-dialog-error i {
    font-size: 1.75rem;
    color: #ef4444;
}

.seq-no {
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--vs-text-secondary);
}

.vehicle-detail-cell {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.vehicle-title {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--vs-text);
}

.lot-cell {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    font-size: 0.82rem;
}

.lot-vin-sub {
    font-family: ui-monospace, monospace;
    font-size: 0.72rem;
    color: var(--vs-text-muted);
}

.images-truck-btn {
    position: relative;
    width: 2.25rem;
    height: 2.25rem;
    border: 1px solid var(--vs-border);
    border-radius: 8px;
    background: var(--vs-surface-elevated);
    color: var(--vs-text-subtle);
    cursor: not-allowed;
    display: grid;
    place-items: center;
    transition: background 0.12s ease, color 0.12s ease, border-color 0.12s ease;
}

.images-truck-count {
    position: absolute;
    top: -0.35rem;
    inset-inline-end: -0.35rem;
    min-width: 1rem;
    height: 1rem;
    padding: 0 0.2rem;
    border-radius: 999px;
    background: #2563eb;
    color: #fff;
    font-size: 0.62rem;
    font-weight: 700;
    line-height: 1rem;
    text-align: center;
    pointer-events: none;
}

.images-truck-btn--active {
    color: #2563eb;
    border-color: rgb(37 99 235 / 35%);
    background: rgb(37 99 235 / 10%);
    cursor: pointer;
}

.images-truck-btn--active:hover {
    background: rgb(37 99 235 / 18%);
}

.container-gallery-empty {
    margin: 0;
    padding: 1.5rem;
    text-align: center;
    color: var(--vs-text-muted);
}

.container-gallery-view-note {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin: 0 0 0.85rem;
    padding: 0.55rem 0.75rem;
    border-radius: 10px;
    background: rgb(37 99 235 / 8%);
    border: 1px solid rgb(37 99 235 / 20%);
    color: #2563eb;
    font-size: 0.82rem;
}

.container-gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 0.75rem;
}

.container-gallery-thumb {
    position: relative;
}

.container-gallery-thumb-btn {
    display: block;
    width: 100%;
    padding: 0;
    border: 1px solid var(--vs-border);
    border-radius: 8px;
    overflow: hidden;
    cursor: zoom-in;
    background: var(--vs-surface-elevated);
}

.container-gallery-thumb-btn img {
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
    display: block;
}

.container-gallery-vin {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.62rem;
    font-family: ui-monospace, monospace;
    color: var(--vs-text-muted);
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.container-gallery-delete {
    position: absolute;
    top: 4px;
    inset-inline-end: 4px;
    opacity: 0;
    transition: opacity 0.15s ease;
    box-shadow: 0 1px 4px rgb(0 0 0 / 20%);
}

.container-gallery-thumb:hover .container-gallery-delete,
.container-gallery-thumb:focus-within .container-gallery-delete {
    opacity: 1;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 500;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.status-terminal,
.status-default {
    background: var(--status-terminal-bg);
    color: var(--status-terminal-fg);
}

.status-new {
    background: var(--status-new-bg);
    color: var(--status-new-fg);
}

.status-transit {
    background: var(--status-transit-bg);
    color: var(--status-transit-fg);
}
</style>

<style>
.container-cars-dialog .p-dialog-header {
    padding-bottom: 0.5rem;
}

.container-cars-table .p-datatable-thead > tr > th {
    background: #2563eb !important;
    color: #fff !important;
    border-color: #1d4ed8 !important;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 0.65rem 0.75rem;
}

.container-cars-table .p-datatable-tbody > tr > td {
    padding: 0.55rem 0.75rem;
    font-size: 0.82rem;
    vertical-align: middle;
}

.container-cars-table .p-datatable-tbody > tr:nth-child(even) {
    background: var(--vs-surface-hover);
}

[data-theme='dark'] .container-cars-table .p-datatable-thead > tr > th {
    background: #1d4ed8 !important;
    border-color: #1e40af !important;
}

[data-theme='dark'] .container-cars-dialog .p-dialog-content {
    background: var(--vs-surface);
}

.container-cars-dialog ~ .vel-modal,
body > .vel-modal {
    z-index: 11000 !important;
}
</style>
