<template>
    <VueEasyLightbox
        :visible="visible"
        :imgs="lightboxSlides"
        :index="0"
        :rtl="true"
        teleport="body"
        @hide="onHide"
    />

    <Teleport to="body">
        <div
            v-if="visible && images.length > 1"
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

        <div v-if="visible" class="gallery-stage-bar" role="status">
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

const lightboxSlides = computed(() => {
    const slide = props.images[activeIndex.value];

    if (! slide?.url) {
        return [];
    }

    return [{
        src: slide.url,
        title: slide.vin || slide.name || `صورة ${activeIndex.value + 1}`,
    }];
});

const currentVin = computed(() => props.images[activeIndex.value]?.vin ?? null);

watch(
    () => [props.visible, props.startIndex],
    ([visible, startIndex]) => {
        if (visible) {
            activeIndex.value = Math.min(
                Math.max(0, startIndex),
                Math.max(0, props.images.length - 1),
            );
        }
    },
    { immediate: true },
);

function onHide() {
    emit('update:visible', false);
    activeIndex.value = 0;
}

function goPrev() {
    if (activeIndex.value > 0) {
        activeIndex.value -= 1;
    }
}

function goNext() {
    if (activeIndex.value < props.images.length - 1) {
        activeIndex.value += 1;
    }
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

<style scoped>
.gallery-stage-bar {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 11050;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    pointer-events: none;
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
    bottom: 88px;
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
</style>
