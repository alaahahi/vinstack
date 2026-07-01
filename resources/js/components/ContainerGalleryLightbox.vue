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
        <div
            v-if="visible && images.length > 1"
            class="gallery-nav-bar"
            role="navigation"
            aria-label="تنقل صور الحاوية"
            @click.stop
            @touchstart.stop
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

        <div
            v-if="visible"
            class="gallery-stage-bar"
            role="status"
            @click.stop
            @touchstart.stop
        >
            <span class="gallery-stage-tab active">
                <span class="gallery-stage-label">معرض الحاوية</span>
                <span class="gallery-stage-count">{{ images.length }}</span>
            </span>
            <span v-if="currentVin" class="container-gallery-vin-badge">{{ currentVin }}</span>
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
.gallery-stage-bar {
    position: fixed;
    top: max(60px, calc(env(safe-area-inset-top, 0px) + 52px));
    inset-inline-start: 12px;
    inset-inline-end: 12px;
    z-index: 11050;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    pointer-events: none;
    max-width: calc(100vw - 24px);
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

.vel-modal .vel-img {
    max-width: min(92vw, 1200px);
    max-height: min(82vh, 900px);
    touch-action: pinch-zoom;
}

.vel-modal .btn__close {
    z-index: 11120 !important;
    position: fixed !important;
    top: max(12px, env(safe-area-inset-top, 0px)) !important;
    inset-inline-end: max(12px, env(safe-area-inset-end, 0px)) !important;
    inset-inline-start: auto !important;
    right: max(12px, env(safe-area-inset-end, 0px)) !important;
    left: auto !important;
    transform: none !important;
    opacity: 1 !important;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    border: 1px solid rgb(255 255 255 / 18%);
    background: rgb(24 24 27 / 82%);
    backdrop-filter: blur(8px);
    pointer-events: auto;
}

.vel-modal.is-rtl .btn__close {
    right: auto !important;
    left: max(12px, env(safe-area-inset-start, 0px)) !important;
    inset-inline-end: auto !important;
    inset-inline-start: max(12px, env(safe-area-inset-start, 0px)) !important;
}

@media (max-width: 640px) {
    .gallery-stage-bar {
        inset-inline-start: 8px;
        inset-inline-end: 8px;
        max-width: calc(100vw - 16px);
    }

    .gallery-nav-bar {
        top: auto;
        bottom: max(72px, calc(env(safe-area-inset-bottom, 0px) + 56px));
    }

    .vel-modal .btn__prev,
    .vel-modal .btn__next {
        display: none !important;
    }

    .vel-modal .vel-img-title {
        bottom: max(16px, env(safe-area-inset-bottom, 0px)) !important;
        max-width: 92vw;
        white-space: normal;
        line-height: 1.35;
    }

    .vel-modal .vel-img {
        max-width: 100vw !important;
        max-height: calc(100dvh - 180px) !important;
    }
}
</style>
