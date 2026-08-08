<template>
    <VueEasyLightbox
        :visible="visible"
        :imgs="lightboxSlides"
        :index="activeIndex"
        :rtl="true"
        :mask-closable="false"
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
            <div class="gallery-chrome__row" role="status">
                <span class="gallery-stage-tab active">
                    <span class="gallery-stage-label">معرض الحاوية</span>
                    <span class="gallery-stage-count">{{ images.length }}</span>
                </span>
                <span v-if="currentVin" class="container-gallery-vin-badge">{{ currentVin }}</span>

                <div
                    v-if="images.length > 1"
                    class="gallery-nav-bar"
                    role="navigation"
                    aria-label="تنقل صور الحاوية"
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
                    <span class="gallery-nav-counter">
                        {{ activeIndex + 1 }} / {{ images.length }}
                    </span>
                    <button
                        type="button"
                        class="gallery-nav-btn"
                        :disabled="activeIndex >= images.length - 1"
                        aria-label="الصورة التالية"
                        @click="goNext"
                    >
                        <i class="pi pi-chevron-left" />
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    images: {
        type: Array,
        default: () => [],
    },
    startIndex: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['update:visible']);

const activeIndex = ref(0);
const navLock = ref(false);

const lightboxSlides = computed(() =>
    props.images
        .filter((slide) => Boolean(slide?.url))
        .map((slide, index) => ({
            src: slide.url,
            title: slide.vin || slide.name || `صورة ${index + 1}`,
        })),
);

const currentVin = computed(() => props.images[activeIndex.value]?.vin ?? null);

watch(
    () => [props.visible, props.startIndex, props.images.length],
    ([visible, startIndex]) => {
        if (visible) {
            const lastIndex = Math.max(0, lightboxSlides.value.length - 1);
            activeIndex.value = Math.min(Math.max(0, startIndex), lastIndex);
        }
    },
    { immediate: true },
);

function onHide() {
    if (navLock.value) {
        return;
    }

    emit('update:visible', false);
    activeIndex.value = 0;
}

function onLightboxIndexChange(_oldIndex, newIndex) {
    if (activeIndex.value === newIndex || navLock.value) {
        return;
    }

    navLock.value = true;
    activeIndex.value = newIndex;

    window.setTimeout(() => {
        navLock.value = false;
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
    }, 120);
}

function goNext() {
    const lastIndex = Math.max(0, lightboxSlides.value.length - 1);

    if (navLock.value || activeIndex.value >= lastIndex) {
        return;
    }

    navLock.value = true;
    activeIndex.value += 1;

    window.setTimeout(() => {
        navLock.value = false;
    }, 120);
}

function onKeydown(event) {
    if (! props.visible) {
        return;
    }

    if (event.key === 'ArrowLeft') {
        goNext();
    } else if (event.key === 'ArrowRight') {
        goPrev();
    }
}

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
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
    gap: 0.35rem;
    padding:
        max(8px, env(safe-area-inset-top, 0px))
        max(52px, calc(env(safe-area-inset-end, 0px) + 40px))
        8px
        max(8px, env(safe-area-inset-start, 0px));
    background: linear-gradient(180deg, rgb(9 9 11 / 0.97) 0%, rgb(9 9 11 / 0.88) 100%);
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

.gallery-stage-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.4rem 0.75rem;
    border: 1px solid rgb(255 255 255 / 18%);
    border-radius: 999px;
    background: rgb(24 24 27 / 72%);
    color: rgb(255 255 255 / 92%);
    font-size: 0.8rem;
    font-weight: 600;
    backdrop-filter: blur(8px);
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
    background: #18181b;
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 1.25rem;
    text-align: center;
}

.container-gallery-vin-badge {
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    background: rgb(24 24 27 / 72%);
    color: rgb(255 255 255 / 92%);
    font-size: 0.72rem;
    font-family: ui-monospace, monospace;
    backdrop-filter: blur(8px);
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
    width: 44px;
    height: 44px;
    border: 1px solid rgb(255 255 255 / 18%);
    border-radius: 999px;
    background: rgb(24 24 27 / 72%);
    color: #fff;
    cursor: pointer;
    display: grid;
    place-items: center;
    backdrop-filter: blur(8px);
    transition: background 0.15s ease;
}

.gallery-nav-btn:hover:not(:disabled) {
    background: rgb(39 39 42 / 88%);
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
</style>

<style>
body > .vel-modal {
    z-index: 11000 !important;
}

.vel-modal .vel-img-wrapper {
    top: calc(50% + 12px) !important;
}

.vel-modal .vel-toolbar {
    display: none !important;
}

.vel-modal .btn__prev,
.vel-modal .btn__next {
    display: none !important;
}

.vel-modal .vel-img {
    max-width: calc(100vw - 16px) !important;
    max-height: calc(100dvh - 96px) !important;
    object-fit: contain;
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

    .gallery-nav-bar {
        justify-content: center;
        width: 100%;
    }

    .gallery-nav-btn {
        width: 44px;
        height: 44px;
    }

    .vel-modal .vel-img-wrapper {
        top: calc(50% + 28px) !important;
    }

    .vel-modal .vel-img-title {
        bottom: max(8px, env(safe-area-inset-bottom, 0px)) !important;
        max-width: 96vw;
        white-space: normal;
        line-height: 1.35;
    }

    .vel-modal .vel-img {
        max-width: calc(100vw - 8px) !important;
        max-height: calc(100dvh - 128px) !important;
    }
}
</style>
