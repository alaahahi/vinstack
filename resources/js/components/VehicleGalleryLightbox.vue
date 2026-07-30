<template>
    <VueEasyLightbox
        :visible="visible"
        :imgs="lightboxImages"
        :index="activeIndex"
        :rtl="true"
        :mask-closable="false"
        :move-disabled="false"
        :rotate-disabled="false"
        :zoom-disabled="false"
        :pinch-disabled="false"
        :scroll-disabled="false"
        teleport="body"
        @hide="onHide"
        @on-index-change="onLightboxIndexChange"
    />

    <Teleport to="body">
        <button
            v-if="visible"
            type="button"
            class="gallery-close-btn"
            aria-label="إغلاق المعرض"
            @click.stop="onHide"
        >
            <i class="pi pi-times" aria-hidden="true" />
        </button>
        <div
            v-if="visible"
            class="gallery-chrome"
            @click.stop
            @touchstart.stop
        >
            <div class="gallery-chrome__row gallery-chrome__row--stages" role="tablist" aria-label="Photo stages">
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

            <div class="gallery-chrome__row gallery-chrome__row--tools">
                <div
                    v-if="activeImages.length > 1"
                    class="gallery-nav-bar"
                    role="navigation"
                    aria-label="تنقل الصور"
                >
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

                <div class="gallery-actions-bar" role="toolbar" aria-label="إجراءات الصور">
                    <button
                        type="button"
                        class="gallery-action-btn"
                        :disabled="!currentUrl || busyCurrent"
                        @click="onDownloadCurrent"
                    >
                        <i class="pi" :class="busyCurrent ? 'pi-spin pi-spinner' : 'pi-download'" />
                        <span class="gallery-action-label">صورة</span>
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
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import VueEasyLightbox from 'vue-easy-lightbox';
import {
    downloadVehicleImage,
    shareAllVehicleImages,
} from '../utils/vehicleImageDownload';
import {
    GALLERY_STAGES,
    vehicleGalleryByStage,
    vehicleGalleryImages,
    vehicleLabel,
} from '../utils/vehicleImages';

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
const busyShare = ref(false);
const shareProgress = ref({ current: 0, total: 0 });
const navLock = ref(false);
const preloadedUrls = new Set();

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

const shareProgressLabel = computed(() => {
    if (! busyShare.value || shareProgress.value.total === 0) {
        return 'جاري التحضير…';
    }

    return `جاري التحضير ${shareProgress.value.current}/${shareProgress.value.total}`;
});

const activeStageLabel = computed(
    () => GALLERY_STAGES.find((t) => t.key === activeStage.value)?.label ?? activeStage.value,
);

const lightboxImages = computed(() =>
    activeImages.value.map((url) => ({
        src: url,
        title: `${label.value} — ${activeStageLabel.value}`,
    })),
);

function firstStageWithImages() {
    return GALLERY_STAGES.find((tab) => (stages.value[tab.key]?.length ?? 0) > 0)?.key ?? 'terminal';
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

function onLightboxIndexChange(_oldIndex, newIndex) {
    if (activeIndex.value === newIndex || navLock.value) {
        return;
    }

    navLock.value = true;
    activeIndex.value = newIndex;

    window.setTimeout(() => {
        navLock.value = false;
        preloadAdjacentImages();
    }, 120);
}

function onHide() {
    if (navLock.value) {
        return;
    }

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
.gallery-chrome {
    position: fixed;
    top: 0;
    inset-inline: 0;
    z-index: 11050;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    padding:
        max(12px, env(safe-area-inset-top, 0px))
        max(60px, calc(env(safe-area-inset-end, 0px) + 48px))
        12px
        max(12px, env(safe-area-inset-start, 0px));
    background: linear-gradient(180deg, rgb(9 9 11 / 0.97) 0%, rgb(9 9 11 / 0.9) 100%);
    border-bottom: 1px solid rgb(255 255 255 / 0.08);
    pointer-events: auto;
}

.gallery-chrome__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
}

.gallery-chrome__row--tools {
    gap: 0.65rem;
}

.gallery-stage-bar {
    display: contents;
}

.gallery-nav-bar {
    position: static;
    transform: none;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    flex-shrink: 0;
}

.gallery-nav-btn {
    width: 48px;
    height: 48px;
    border: 1px solid rgb(255 255 255 / 18%);
    border-radius: 999px;
    background: rgb(24 24 27 / 72%);
    color: #fff;
    cursor: pointer;
    display: grid;
    place-items: center;
}

.gallery-nav-btn i {
    font-size: 1.15rem;
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
    position: static;
    display: inline-flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    justify-content: center;
    max-width: none;
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

.gallery-action-label {
    white-space: nowrap;
}

.gallery-stage-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.62rem;
    border: 1px solid rgb(255 255 255 / 18%);
    border-radius: 999px;
    background: rgb(24 24 27 / 72%);
    color: rgb(255 255 255 / 82%);
    font-size: 0.74rem;
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
    min-width: 1.1rem;
    height: 1.1rem;
    padding: 0 0.3rem;
    border-radius: 999px;
    background: rgb(0 0 0 / 28%);
    color: inherit;
    font-size: 0.66rem;
    font-weight: 700;
    line-height: 1.1rem;
    text-align: center;
}

.gallery-stage-tab.active .gallery-stage-count {
    background: #18181b;
    color: #fff;
}

.vel-modal {
    z-index: 11000 !important;
}

.vel-modal .vel-img-wrapper {
    top: calc(50% + 72px) !important;
}

.vel-modal .vel-toolbar {
    display: none !important;
}

.vel-modal .vel-img {
    max-width: min(92vw, 1200px);
    max-height: calc(100vh - 220px);
    touch-action: pinch-zoom;
}

.vel-modal .btn__close {
    display: none !important;
}

.gallery-close-btn {
    position: fixed;
    z-index: 11150;
    top: max(12px, env(safe-area-inset-top, 0px));
    inset-inline-end: max(12px, env(safe-area-inset-end, 0px));
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border-radius: 999px;
    border: 1px solid rgb(255 255 255 / 25%);
    background: rgb(24 24 27 / 92%);
    color: #fff;
    cursor: pointer;
    backdrop-filter: blur(8px);
    pointer-events: auto;
    padding: 0;
    line-height: 1;
    transition: background 0.15s ease;
}

.gallery-close-btn:hover {
    background: rgb(39 39 42 / 95%);
}

.gallery-close-btn i {
    font-size: 1.1rem;
}

@media (max-width: 640px) {
    .gallery-chrome {
        padding-inline-end: max(52px, calc(env(safe-area-inset-end, 0px) + 40px));
    }

    .gallery-chrome__row--tools {
        flex-direction: column;
        align-items: stretch;
    }

    .gallery-nav-bar {
        justify-content: center;
        width: 100%;
    }

    .gallery-actions-bar {
        width: 100%;
    }

    .gallery-action-btn {
        min-height: 44px;
        padding: 0.5rem 0.7rem;
    }

    .gallery-action-label,
    .gallery-action-btn span {
        font-size: 0.72rem;
    }

    .gallery-nav-btn {
        width: 44px;
        height: 44px;
    }

    .vel-modal .btn__prev,
    .vel-modal .btn__next {
        display: none !important;
    }

    .vel-modal .vel-img-wrapper {
        top: calc(50% + 88px) !important;
    }

    .vel-modal .vel-img-title {
        bottom: max(16px, env(safe-area-inset-bottom, 0px)) !important;
        max-width: 92vw;
        white-space: normal;
        line-height: 1.35;
    }

    .vel-modal .vel-img {
        max-width: 100vw !important;
        max-height: calc(100dvh - 260px) !important;
    }
}
</style>
