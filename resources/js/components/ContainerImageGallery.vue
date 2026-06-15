<template>
    <div class="container-gallery">
        <button
            type="button"
            class="thumb-btn"
            :class="{
                empty: !hasImages,
                clickable: hasImages,
                'thumb-btn--row': variant === 'row',
                'thumb-btn--drawer': variant === 'drawer',
            }"
            :title="thumbTitle"
            :disabled="!hasImages"
            @click="openGallery"
        >
            <img
                v-if="thumbnail"
                :src="thumbnail"
                :alt="altLabel"
                class="thumb-img"
                loading="lazy"
                decoding="async"
            />
            <span v-else class="thumb-empty">
                <i class="pi pi-image" />
            </span>
            <span
                v-if="showCountBadge"
                class="count-badge"
                :class="{
                    'count-badge--row': variant === 'row',
                    'count-badge--drawer': variant === 'drawer',
                }"
            >{{ imageCount }}</span>
        </button>

        <Button
            v-if="showButton && hasImages"
            :label="buttonLabel"
            icon="pi pi-images"
            size="small"
            severity="secondary"
            text
            @click="openGallery"
        />

        <ContainerGalleryLightbox
            v-model:visible="visible"
            :images="images"
            :start-index="startIndex"
        />
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import Button from 'primevue/button';
import ContainerGalleryLightbox from './ContainerGalleryLightbox.vue';

const props = defineProps({
    images: {
        type: Array,
        default: () => [],
    },
    variant: {
        type: String,
        default: 'table',
        validator: (value) => ['table', 'row', 'drawer'].includes(value),
    },
    showButton: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        default: 'معرض الحاوية',
    },
});

const visible = ref(false);
const startIndex = ref(0);

const imageCount = computed(() => props.images?.length ?? 0);
const hasImages = computed(() => imageCount.value > 0);
const thumbnail = computed(() => props.images?.[0]?.url ?? null);
const showCountBadge = computed(() => imageCount.value > 1);

const altLabel = computed(() => {
    if (! hasImages.value) {
        return props.label;
    }

    return `${props.label} — ${imageCount.value} صورة`;
});

const thumbTitle = computed(() => {
    if (! hasImages.value) {
        return 'لا توجد صور للحاوية';
    }

    return `عرض ${imageCount.value} صورة`;
});

const buttonLabel = computed(() => `صور الحاوية (${imageCount.value})`);

function openGallery() {
    if (! hasImages.value) {
        return;
    }

    startIndex.value = 0;
    visible.value = true;
}
</script>

<style scoped>
.container-gallery {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.thumb-btn {
    position: relative;
    width: 56px;
    height: 42px;
    padding: 0;
    border: 1px solid var(--vs-border, #e4e4e7);
    border-radius: 8px;
    overflow: hidden;
    background: var(--vs-surface-elevated, #fafafa);
    cursor: default;
    flex-shrink: 0;
    transition: box-shadow 0.15s ease, transform 0.15s ease;
}

.thumb-btn--row {
    width: 88px;
    height: 66px;
    border-radius: 10px;
}

.thumb-btn--drawer {
    width: 100%;
    height: auto;
    min-height: 160px;
    border-radius: 12px;
    border-color: var(--vs-border);
}

.thumb-btn.clickable {
    cursor: pointer;
}

.thumb-btn.clickable:hover {
    box-shadow: 0 4px 12px rgb(0 0 0 / 12%);
    transform: translateY(-1px);
}

.thumb-btn.empty {
    opacity: 0.65;
}

.thumb-btn--drawer .thumb-img {
    width: 100%;
    height: 180px;
    object-fit: cover;
}

.thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.thumb-empty {
    width: 100%;
    height: 100%;
    min-height: 42px;
    display: grid;
    place-items: center;
    color: var(--vs-text-subtle, #a1a1aa);
    font-size: 1.1rem;
}

.thumb-btn--drawer .thumb-empty {
    min-height: 160px;
}

.count-badge {
    position: absolute;
    bottom: 3px;
    inset-inline-end: 3px;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    border-radius: 999px;
    background: rgb(24 24 27 / 82%);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    line-height: 16px;
    text-align: center;
    pointer-events: none;
    z-index: 2;
    box-shadow: 0 1px 3px rgb(0 0 0 / 0.35);
}

.count-badge--row {
    bottom: 4px;
    inset-inline-end: 4px;
    min-width: 20px;
    height: 20px;
    padding: 0 5px;
    font-size: 11px;
    line-height: 20px;
}

.count-badge--drawer {
    bottom: 10px;
    inset-inline-end: 10px;
    min-width: 24px;
    height: 24px;
    padding: 0 6px;
    font-size: 12px;
    line-height: 24px;
}
</style>
