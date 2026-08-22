<template>
    <article class="related-card" @click="$emit('open', vehicle)">
        <div class="related-card__media">
            <img
                v-if="currentSrc"
                :src="currentSrc"
                alt=""
                loading="lazy"
            />
            <div v-else class="related-card__empty">{{ t('auctions.noPhotos') }}</div>
            <span v-if="groupLabel" class="related-card__group">{{ groupLabel }}</span>
            <span v-if="photos.length > 1" class="related-card__count" dir="ltr">
                {{ slide + 1 }}/{{ photos.length }}
            </span>
        </div>
        <div class="related-card__body">
            <div class="related-card__badges">
                <span class="pill" :class="platform === 'iaai' ? 'pill--iaai' : 'pill--copart'">
                    {{ platformLabel }}
                </span>
                <span v-if="damage" class="pill">{{ damage }}</span>
            </div>
            <h4>{{ title }}</h4>
            <p class="related-card__meta">
                <span><i class="pi pi-map-marker" /> {{ location }}</span>
                <span dir="ltr"><i class="pi pi-dollar" /> {{ bid }}</span>
            </p>
        </div>
    </article>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    vehicle: {
        type: Object,
        required: true,
    },
    autoplayMs: {
        type: Number,
        default: 2800,
    },
});

defineEmits(['open']);

const { t } = useI18n();
const slide = ref(0);
let timer = null;

const photos = computed(() => {
    const items = props.vehicle?.media?.items;

    if (Array.isArray(items) && items.length) {
        return items
            .filter((item) => item && (item.type === 'image' || item.thumb || item.large || item.full))
            .map((item) => item.large || item.full || item.thumb)
            .filter(Boolean);
    }

    const thumbs = props.vehicle?.media?.thumbs;

    if (Array.isArray(thumbs)) {
        return thumbs.filter((url) => typeof url === 'string' && url);
    }

    return [];
});

const currentSrc = computed(() => {
    if (! photos.value.length) return null;

    return photos.value[Math.min(slide.value, photos.value.length - 1)];
});

const title = computed(() => {
    const row = props.vehicle;

    if (row?.title) return String(row.title).toUpperCase();

    return `${row?.year || ''} ${row?.make || ''} ${row?.model || ''}`.trim().toUpperCase() || '—';
});

const platform = computed(() => String(props.vehicle?.platform || '').toLowerCase());
const platformLabel = computed(() => platform.value ? platform.value.toUpperCase() : '—');
const damage = computed(() => props.vehicle?.condition?.primary_damage || '');
const location = computed(() => props.vehicle?.location?.display || '—');
const bid = computed(() => {
    const value = props.vehicle?.pricing?.current_bid_usd;

    if (value == null || value === '') return '—';

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(Number(value));
});

const groupLabel = computed(() => {
    const group = props.vehicle?._related_group;

    if (group === 'upcoming') return t('auctions.relatedUpcoming');
    if (group === 'past') return t('auctions.relatedPast');

    return '';
});

function stop() {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
}

function start() {
    stop();
    if (photos.value.length < 2 || props.autoplayMs <= 0) return;

    timer = setInterval(() => {
        slide.value = (slide.value + 1) % photos.value.length;
    }, props.autoplayMs);
}

watch(photos, () => {
    slide.value = 0;
    start();
});

onMounted(start);
onBeforeUnmount(stop);
</script>

<style scoped>
.related-card {
    border: 1px solid var(--admin-border, var(--vs-border));
    border-radius: 14px;
    overflow: hidden;
    background: color-mix(in srgb, var(--admin-surface, #111827) 92%, transparent);
    cursor: pointer;
    display: flex;
    flex-direction: column;
    transition: transform 0.15s ease;
}

.related-card:hover {
    transform: translateY(-2px);
}

.related-card__media {
    position: relative;
    aspect-ratio: 16 / 10;
    background: #0f172a;
    overflow: hidden;
}

.related-card__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.related-card__empty {
    height: 100%;
    display: grid;
    place-items: center;
    color: #94a3b8;
    font-size: 0.8rem;
    padding: 0.75rem;
    text-align: center;
}

.related-card__group {
    position: absolute;
    top: 0.5rem;
    inset-inline-start: 0.5rem;
    background: rgba(15, 23, 42, 0.8);
    color: #e2e8f0;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    border-radius: 999px;
    padding: 0.22rem 0.5rem;
}

.related-card__count {
    position: absolute;
    bottom: 0.4rem;
    inset-inline-end: 0.45rem;
    background: rgba(15, 23, 42, 0.75);
    color: #e2e8f0;
    font-size: 0.65rem;
    font-weight: 700;
    border-radius: 999px;
    padding: 0.12rem 0.4rem;
}

.related-card__body {
    padding: 0.7rem 0.75rem 0.85rem;
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.related-card__badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
}

.pill {
    display: inline-flex;
    border-radius: 999px;
    padding: 0.18rem 0.45rem;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    background: color-mix(in srgb, var(--admin-accent, #4a3558) 12%, transparent);
}

.pill--copart {
    background: color-mix(in srgb, #ea580c 18%, transparent);
    color: #c2410c;
}

.pill--iaai {
    background: color-mix(in srgb, #0284c7 18%, transparent);
    color: #0369a1;
}

.related-card__body h4 {
    margin: 0;
    font-size: 0.88rem;
    line-height: 1.25;
    font-weight: 800;
}

.related-card__meta {
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    color: var(--vs-text-muted);
    font-size: 0.78rem;
}

.related-card__meta span {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
</style>
