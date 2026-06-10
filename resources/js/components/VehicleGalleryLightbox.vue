<template>
    <VueEasyLightbox
        :visible="visible"
        :imgs="lightboxImages"
        :index="0"
        :rtl="true"
        :move-disabled="false"
        :rotate-disabled="false"
        :zoom-disabled="false"
        :pinch-disabled="false"
        :scroll-disabled="false"
        teleport="body"
        @hide="onHide"
    />

    <Teleport to="body">
        <div v-if="visible" class="gallery-stage-bar" role="tablist" aria-label="Photo stages">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                role="tab"
                class="gallery-stage-tab"
                :class="{ active: activeStage === tab.key }"
                :aria-selected="activeStage === tab.key"
                @click="selectStage(tab.key)"
            >
                <span class="gallery-stage-label">{{ tab.label }}</span>
                <span class="gallery-stage-count">{{ tab.count }}</span>
            </button>
        </div>

        <div v-if="visible && activeImages.length > 1" class="gallery-nav-bar" role="navigation" aria-label="تنقل الصور">
            <button
                type="button"
                class="gallery-nav-btn"
                :disabled="activeIndex === 0"
                aria-label="الصورة السابقة"
                @click="goPrev"
            >
                <i class="pi pi-chevron-right" />
            </button>
            <span class="gallery-nav-counter">{{ activeIndex + 1 }} / {{ activeImages.length }}</span>
            <button
                type="button"
                class="gallery-nav-btn"
                :disabled="activeIndex >= activeImages.length - 1"
                aria-label="الصورة التالية"
                @click="goNext"
            >
                <i class="pi pi-chevron-left" />
            </button>
        </div>

        <div v-if="visible" class="gallery-actions-bar" role="toolbar" aria-label="إجراءات الصور">
            <span v-if="currentIsLocal" class="gallery-local-badge">مرفوعة محلياً</span>
            <button
                type="button"
                class="gallery-action-btn"
                :disabled="!currentUrl || busyCurrent"
                @click="onDownloadCurrent"
            >
                <i class="pi" :class="busyCurrent ? 'pi-spin pi-spinner' : 'pi-download'" />
                <span>تحميل</span>
            </button>
            <button
                type="button"
                class="gallery-action-btn"
                :disabled="!allHdCount || busyAll"
                @click="onDownloadAll"
            >
                <i class="pi" :class="busyAll ? 'pi-spin pi-spinner' : 'pi-images'" />
                <span>{{ busyAll ? bulkProgressLabel : 'تحميل الكل' }}</span>
            </button>
            <button
                type="button"
                class="gallery-action-btn"
                :disabled="!allHdCount || busyShare"
                @click="onShareAll"
            >
                <i class="pi" :class="busyShare ? 'pi-spin pi-spinner' : 'pi-share-alt'" />
                <span>{{ busyShare ? shareProgressLabel : 'مشاركة الكل' }}</span>
            </button>
            <!-- TODO: remove after gallery debug -->
            <button
                type="button"
                class="gallery-action-btn gallery-action-btn--debug"
                :disabled="galleryTestLoading"
                @click="runGalleryTest"
            >
                <i class="pi" :class="galleryTestLoading ? 'pi-spin pi-spinner' : 'pi-bolt'" />
                <span>TEST API</span>
            </button>
        </div>

        <div v-if="visible && galleryTestResult" class="gallery-debug-panel" role="status">
            <div class="gallery-debug-title">نتيجة API المعرض (مؤقت)</div>
            <div class="gallery-debug-row">
                <span>fresh:</span>
                <strong>{{ galleryTestResult.gallery_fresh ? 'نعم' : 'لا' }}</strong>
            </div>
            <div v-if="galleryTestResult.gallery_error" class="gallery-debug-row gallery-debug-row--error">
                <span>خطأ:</span>
                <strong>{{ galleryTestResult.gallery_error }}</strong>
            </div>
            <div class="gallery-debug-row">
                <span>API خام:</span>
                <strong>
                    T {{ galleryTestResult.api_raw.terminal }} /
                    P {{ galleryTestResult.api_raw.pickup }} /
                    D {{ galleryTestResult.api_raw.destination }}
                    ({{ galleryTestResult.total_api_stages }})
                </strong>
            </div>
            <div class="gallery-debug-row">
                <span>images_by_stage:</span>
                <strong>
                    T {{ galleryTestResult.images_by_stage.terminal }} /
                    P {{ galleryTestResult.images_by_stage.pickup }} /
                    D {{ galleryTestResult.images_by_stage.destination }}
                    ({{ galleryTestResult.total_merged }})
                </strong>
            </div>
            <div class="gallery-debug-row">
                <span>يعرض UI الآن:</span>
                <strong>
                    T {{ galleryTestResult.ui_display.terminal }} /
                    P {{ galleryTestResult.ui_display.pickup }} /
                    D {{ galleryTestResult.ui_display.destination }}
                    ({{ galleryTestResult.ui_display.total }})
                </strong>
            </div>
            <div class="gallery-debug-row">
                <span>flat / urls:</span>
                <strong>{{ galleryTestResult.flat_images }} / {{ galleryTestResult.top_level_urls }}</strong>
            </div>
            <button type="button" class="gallery-debug-close" @click="galleryTestResult = null">إخفاء</button>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import VueEasyLightbox from 'vue-easy-lightbox';
import {
    downloadAllVehicleImages,
    downloadVehicleImage,
    shareAllVehicleImages,
} from '../utils/vehicleImageDownload';
import {
    GALLERY_STAGES,
    vehicleGalleryByStage,
    vehicleGalleryImages,
    vehicleLabel,
    vehicleUploadedImages,
} from '../utils/vehicleImages';
import {
    fetchLiveVehicleGallery,
    mergeGalleryIntoVehicle,
    summarizeGalleryApiResponse,
    vehicleUsesLiveGallery,
} from '../utils/vehicleGalleryLive';
import { isLocalUploadedUrl } from '../utils/vehicleImageUpload';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    vehicle: {
        type: Object,
        default: null,
    },
    startUrl: {
        type: String,
        default: null,
    },
    apiMode: {
        type: String,
        default: 'admin',
        validator: (value) => ['admin', 'dealer'].includes(value),
    },
});

const emit = defineEmits(['update:visible', 'hide']);

const toast = useToast();

const activeStage = ref('terminal');
const activeIndex = ref(0);
const busyCurrent = ref(false);
const busyAll = ref(false);
const busyShare = ref(false);
const bulkProgress = ref({ current: 0, total: 0 });
const shareProgress = ref({ current: 0, total: 0 });
const navLock = ref(false);
const preloadedUrls = new Set();
const galleryTestLoading = ref(false);
const galleryTestResult = ref(null);

const stages = computed(() => vehicleGalleryByStage(props.vehicle));

const tabs = computed(() =>
    GALLERY_STAGES.map((tab) => ({
        ...tab,
        count: stages.value[tab.key]?.length ?? 0,
    })),
);

const activeImages = computed(() => stages.value[activeStage.value] ?? []);

const label = computed(() => vehicleLabel(props.vehicle));

const allHdCount = computed(() => vehicleGalleryImages(props.vehicle).length);

const currentUrl = computed(() => activeImages.value[activeIndex.value] ?? null);

const currentIsLocal = computed(() =>
    isLocalUploadedUrl(currentUrl.value, vehicleUploadedImages(props.vehicle)),
);

const bulkProgressLabel = computed(() => {
    if (! busyAll.value || bulkProgress.value.total === 0) {
        return 'جاري التحميل…';
    }

    return `جاري التحميل ${bulkProgress.value.current}/${bulkProgress.value.total}`;
});

const shareProgressLabel = computed(() => {
    if (! busyShare.value || shareProgress.value.total === 0) {
        return 'جاري التحضير…';
    }

    return `جاري التحضير ${shareProgress.value.current}/${shareProgress.value.total}`;
});

const activeStageLabel = computed(
    () => GALLERY_STAGES.find((t) => t.key === activeStage.value)?.label ?? activeStage.value,
);

const lightboxImages = computed(() => {
    const url = currentUrl.value;

    if (! url) {
        return [];
    }

    return [{
        src: url,
        title: `${label.value} — ${activeStageLabel.value}`,
    }];
});

function firstStageWithImages() {
    return GALLERY_STAGES.find((tab) => (stages.value[tab.key]?.length ?? 0) > 0)?.key ?? 'terminal';
}

async function runGalleryTest() {
    const vehicle = props.vehicle;
    const vehicleId = vehicle?.id;

    if (! vehicleId) {
        toast.add({
            severity: 'warn',
            summary: 'TEST API',
            detail: 'لا يوجد معرّف للسيارة',
            life: 5000,
        });

        return;
    }

    if (! vehicleUsesLiveGallery(vehicle)) {
        toast.add({
            severity: 'info',
            summary: 'TEST API',
            detail: 'سيارة يدوية — API المعرض لا ينطبق (source ليس vinstack)',
            life: 6000,
        });

        return;
    }

    galleryTestLoading.value = true;

    try {
        const payload = await fetchLiveVehicleGallery(vehicleId, props.apiMode);
        const summary = summarizeGalleryApiResponse(payload);
        const uiStages = vehicleGalleryByStage(mergeGalleryIntoVehicle(vehicle, payload));

        galleryTestResult.value = {
            ...summary,
            ui_display: {
                terminal: uiStages.terminal?.length ?? 0,
                pickup: uiStages.pickup?.length ?? 0,
                destination: uiStages.destination?.length ?? 0,
                total: (uiStages.terminal?.length ?? 0)
                    + (uiStages.pickup?.length ?? 0)
                    + (uiStages.destination?.length ?? 0),
            },
        };

        toast.add({
            severity: 'info',
            summary: 'TEST API',
            detail: `API: ${summary.total_api_stages} صورة — UI: ${galleryTestResult.value.ui_display.total}`,
            life: 8000,
        });
    } catch (e) {
        const msg = e.response?.data?.message ?? e.message ?? 'فشل الطلب';

        galleryTestResult.value = {
            gallery_fresh: false,
            gallery_error: msg,
            api_raw: { terminal: 0, pickup: 0, destination: 0 },
            images_by_stage: { terminal: 0, pickup: 0, destination: 0 },
            flat_images: 0,
            top_level_urls: 0,
            total_api_stages: 0,
            total_merged: 0,
            ui_display: { terminal: 0, pickup: 0, destination: 0, total: 0 },
        };

        toast.add({
            severity: 'error',
            summary: 'TEST API',
            detail: msg,
            life: 8000,
        });
    } finally {
        galleryTestLoading.value = false;
    }
}

function applyStartUrl() {
    const url = props.startUrl;

    if (! url) {
        activeStage.value = firstStageWithImages();
        activeIndex.value = 0;

        return;
    }

    for (const { key } of GALLERY_STAGES) {
        const index = stages.value[key]?.indexOf(url) ?? -1;

        if (index >= 0) {
            activeStage.value = key;
            activeIndex.value = index;

            return;
        }
    }

    activeStage.value = firstStageWithImages();
    activeIndex.value = 0;
}

function preloadAdjacentImages() {
    const images = activeImages.value;
    const idx = activeIndex.value;

    for (const offset of [-1, 1]) {
        const url = images[idx + offset];

        if (! url || preloadedUrls.has(url)) {
            continue;
        }

        preloadedUrls.add(url);

        const img = new Image();
        img.src = url;
    }
}

function selectStage(stageKey) {
    if (activeStage.value === stageKey || navLock.value) {
        return;
    }

    navLock.value = true;
    activeStage.value = stageKey;
    activeIndex.value = 0;

    window.setTimeout(() => {
        navLock.value = false;
        preloadAdjacentImages();
    }, 120);
}

function goPrev() {
    if (navLock.value || activeIndex.value <= 0) {
        return;
    }

    navLock.value = true;
    activeIndex.value -= 1;

    window.setTimeout(() => {
        navLock.value = false;
        preloadAdjacentImages();
    }, 120);
}

function goNext() {
    if (navLock.value || activeIndex.value >= activeImages.value.length - 1) {
        return;
    }

    navLock.value = true;
    activeIndex.value += 1;

    window.setTimeout(() => {
        navLock.value = false;
        preloadAdjacentImages();
    }, 120);
}

function onHide() {
    emit('update:visible', false);
    emit('hide');
    preloadedUrls.clear();
}

async function onDownloadCurrent() {
    if (! currentUrl.value || busyCurrent.value) {
        return;
    }

    busyCurrent.value = true;

    try {
        const result = await downloadVehicleImage(
            currentUrl.value,
            props.vehicle,
            activeIndex.value,
        );

        if (result.method === 'tab') {
            toast.add({
                severity: 'info',
                summary: 'تم فتح الصورة في تبويب جديد',
                detail: 'احفظ الصورة من المتصفح إن لزم',
                life: 4000,
            });
        } else {
            toast.add({
                severity: 'success',
                summary: 'تم تحميل الصورة',
                life: 3000,
            });
        }
    } catch {
        toast.add({
            severity: 'error',
            summary: 'تعذّر تحميل الصورة',
            life: 3000,
        });
    } finally {
        busyCurrent.value = false;
    }
}

async function onDownloadAll() {
    if (! allHdCount.value || busyAll.value) {
        return;
    }

    busyAll.value = true;
    bulkProgress.value = { current: 0, total: allHdCount.value };

    try {
        const result = await downloadAllVehicleImages(props.vehicle, {
            onProgress: (current, total) => {
                bulkProgress.value = { current, total };
            },
        });

        toast.add({
            severity: 'success',
            summary: 'تم تحميل الأرشيف',
            detail: `${result.count} من ${result.total} صورة`,
            life: 4000,
        });
    } catch (error) {
        if (error?.message === 'no_images') {
            toast.add({
                severity: 'warn',
                summary: 'لا توجد صور للتحميل',
                life: 3000,
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'تعذّر تحميل الصور',
                detail: 'تحقق من الاتصال أو جرّب صورة واحدة',
                life: 4000,
            });
        }
    } finally {
        busyAll.value = false;
        bulkProgress.value = { current: 0, total: 0 };
    }
}

async function onShareAll() {
    if (! allHdCount.value || busyShare.value) {
        return;
    }

    busyShare.value = true;
    shareProgress.value = { current: 0, total: allHdCount.value };

    try {
        const result = await shareAllVehicleImages(props.vehicle, {
            onProgress: (current, total) => {
                shareProgress.value = { current, total };
            },
        });

        if (result.aborted) {
            toast.add({
                severity: 'info',
                summary: 'تم إلغاء المشاركة',
                life: 2500,
            });

            return;
        }

        if (result.method === 'zip_download') {
            toast.add({
                severity: 'success',
                summary: 'تم تحضير الملف للمشاركة',
                detail: `تم تحميل أرشيف ZIP (${result.count} صورة) — شاركه يدوياً من مجلد التحميلات`,
                life: 6000,
            });

            return;
        }

        if (result.method === 'url') {
            toast.add({
                severity: 'success',
                summary: 'تم فتح نافذة المشاركة',
                detail: `${result.count} صورة`,
                life: 4000,
            });

            return;
        }

        if (result.method === 'files_partial') {
            toast.add({
                severity: 'info',
                summary: 'تمت مشاركة أول الصور',
                detail: `${result.count} من ${result.total} (حد المتصفح)`,
                life: 5000,
            });

            return;
        }

        if (result.method === 'zip') {
            toast.add({
                severity: 'success',
                summary: 'تمت مشاركة الأرشيف',
                detail: `${result.count} صورة`,
                life: 4000,
            });

            return;
        }

        toast.add({
            severity: 'success',
            summary: 'تمت مشاركة الصور',
            detail: `${result.count} من ${result.total}`,
            life: 4000,
        });
    } catch (error) {
        const code = error?.message;

        if (code === 'no_images') {
            toast.add({
                severity: 'warn',
                summary: 'لا توجد صور للمشاركة',
                life: 3000,
            });
        } else if (code === 'fetch_failed') {
            toast.add({
                severity: 'error',
                summary: 'تعذّر تحميل الصور للمشاركة',
                detail: 'تحقق من الاتصال أو جرّب صورة واحدة',
                life: 4500,
            });
        } else if (code === 'zip_failed') {
            toast.add({
                severity: 'error',
                summary: 'تعذّر إنشاء أرشيف ZIP',
                detail: 'جرّب «تحميل الكل» ثم شارك الملف يدوياً',
                life: 5000,
            });
        } else {
            toast.add({
                severity: 'warn',
                summary: 'المشاركة غير مدعومة على هذا الجهاز',
                detail: 'استخدم «تحميل الكل» ثم شارك الملف من جهازك',
                life: 5000,
            });
        }
    } finally {
        busyShare.value = false;
        shareProgress.value = { current: 0, total: 0 };
    }
}

function onKeydown(event) {
    if (! props.visible) {
        return;
    }

    if (event.key === 'ArrowLeft') {
        goPrev();
    } else if (event.key === 'ArrowRight') {
        goNext();
    }
}

watch(
    () => [props.visible, props.startUrl],
    ([open]) => {
        if (! open) {
            return;
        }

        applyStartUrl();
        preloadAdjacentImages();
    },
);

watch(currentUrl, (url) => {
    if (url && props.visible) {
        preloadAdjacentImages();
    }
});

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    preloadedUrls.clear();
});
</script>

<style>
.gallery-stage-bar {
    position: fixed;
    top: 18px;
    inset-inline-start: 18px;
    z-index: 11050;
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    pointer-events: auto;
}

.gallery-nav-bar {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 11050;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    pointer-events: auto;
}

.gallery-nav-btn {
    width: 36px;
    height: 36px;
    border: 1px solid rgb(255 255 255 / 18%);
    border-radius: 999px;
    background: rgb(24 24 27 / 72%);
    color: #fff;
    cursor: pointer;
    display: grid;
    place-items: center;
}

.gallery-nav-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.gallery-nav-counter {
    min-width: 4rem;
    text-align: center;
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    background: rgb(24 24 27 / 72%);
    color: rgb(255 255 255 / 92%);
    font-size: 0.78rem;
    font-weight: 600;
}

.gallery-actions-bar {
    position: fixed;
    top: 18px;
    inset-inline-end: 18px;
    z-index: 11050;
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    pointer-events: auto;
}

.gallery-local-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    background: #ecfdf5;
    color: #047857;
    font-size: 0.72rem;
    font-weight: 600;
}

.gallery-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.85rem;
    border: 1px solid rgb(255 255 255 / 18%);
    border-radius: 999px;
    background: rgb(24 24 27 / 72%);
    color: rgb(255 255 255 / 92%);
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    backdrop-filter: blur(8px);
    transition:
        background 0.15s ease,
        color 0.15s ease,
        opacity 0.15s ease;
}

.gallery-action-btn:hover:not(:disabled) {
    background: rgb(39 39 42 / 88%);
    color: #fff;
}

.gallery-action-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.gallery-action-btn--debug {
    border-color: rgb(251 191 36 / 45%);
    background: rgb(120 53 15 / 78%);
    color: #fef3c7;
}

.gallery-debug-panel {
    position: fixed;
    top: 72px;
    inset-inline-end: 18px;
    z-index: 11060;
    width: min(92vw, 320px);
    padding: 0.75rem 0.85rem;
    border-radius: 10px;
    border: 1px solid rgb(251 191 36 / 35%);
    background: rgb(24 24 27 / 92%);
    color: #fafafa;
    font-size: 0.78rem;
    line-height: 1.45;
    pointer-events: auto;
    backdrop-filter: blur(8px);
}

.gallery-debug-title {
    font-weight: 700;
    margin-bottom: 0.45rem;
    color: #fde68a;
}

.gallery-debug-row {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.2rem;
}

.gallery-debug-row--error strong {
    color: #fca5a5;
}

.gallery-debug-close {
    margin-top: 0.5rem;
    padding: 0.25rem 0.55rem;
    border: 1px solid rgb(255 255 255 / 20%);
    border-radius: 6px;
    background: transparent;
    color: #e4e4e7;
    font-size: 0.72rem;
    cursor: pointer;
}

.gallery-stage-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.4rem 0.75rem;
    border: 1px solid rgb(255 255 255 / 18%);
    border-radius: 999px;
    background: rgb(24 24 27 / 72%);
    color: rgb(255 255 255 / 82%);
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    backdrop-filter: blur(8px);
    transition:
        background 0.15s ease,
        color 0.15s ease,
        border-color 0.15s ease;
}

.gallery-stage-tab:hover {
    background: rgb(39 39 42 / 88%);
    color: #fff;
}

.gallery-stage-tab.active {
    background: #fff;
    color: #18181b;
    border-color: #fff;
}

.gallery-stage-count {
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 0.35rem;
    border-radius: 999px;
    background: rgb(0 0 0 / 28%);
    color: inherit;
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 1.25rem;
    text-align: center;
}

.gallery-stage-tab.active .gallery-stage-count {
    background: #18181b;
    color: #fff;
}

.vel-modal {
    z-index: 11000 !important;
}

@media (max-width: 640px) {
    .gallery-actions-bar {
        top: auto;
        bottom: 88px;
        inset-inline-end: 12px;
        inset-inline-start: 12px;
        justify-content: center;
    }

    .gallery-action-btn span {
        font-size: 0.75rem;
    }

    .gallery-nav-bar {
        bottom: 140px;
    }
}
</style>
