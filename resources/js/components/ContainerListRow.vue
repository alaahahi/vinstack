<template>

    <div class="container-row">

        <div class="cell cell-image">
            <button
                type="button"
                class="thumb-btn"
                :class="{
                    empty: !hasThumbnail,
                    clickable: hasImages,
                    'thumb-btn--loading': galleryLoading,
                }"
                :disabled="!hasImages || galleryLoading"
                :title="imageTitle"
                :aria-label="imageTitle"
                :aria-busy="galleryLoading"
                @click="openGallery"
            >
                <img
                    v-if="thumbnailUrl && !galleryLoading"
                    :src="thumbnailUrl"
                    :alt="t('containers.containerImages')"
                    class="thumb-img"
                    loading="lazy"
                    decoding="async"
                />
                <span v-else-if="galleryLoading" class="thumb-loading" aria-hidden="true">
                    <ProgressSpinner style="width: 1.25rem; height: 1.25rem" />
                </span>
                <span v-else class="thumb-empty">
                    <i class="pi pi-image" />
                </span>
                <span v-if="showCountBadge && !galleryLoading" class="count-badge">{{ imageCount }}</span>
            </button>
        </div>

        <div class="cell cell-refs">

            <div v-if="refs.container" class="ref-line">

                <span class="ref-label">CNTR</span>

                <button
                    v-if="refs.container"
                    type="button"
                    class="ref-value ref-value--link"
                    @click="$emit('show-cars', container)"
                >
                    {{ refs.container }}
                </button>

            </div>

            <div v-if="refs.booking" class="ref-line">

                <span class="ref-label">BKG</span>

                <span class="ref-value">{{ refs.booking }}</span>

            </div>

            <div v-if="refs.seal" class="ref-line">

                <span class="ref-label">SEAL</span>

                <span class="ref-value">{{ refs.seal }}</span>

            </div>

            <span v-if="container.source === 'vehicles'" class="source-hint">{{ t('containers.fromVehicles') }}</span>

        </div>



        <div class="cell cell-customer">

            <div class="customer-name">{{ container.customer_name || '—' }}</div>

        </div>



        <div class="cell cell-route">

            <div v-if="routeFrom" class="route-line">

                <span class="route-dot route-dot--origin" />

                <span>{{ routeFrom }}</span>

            </div>

            <div v-if="routeTo" class="route-line">

                <i class="pi pi-map-marker route-pin" />

                <span>{{ routeTo }}</span>

            </div>

            <div v-if="!routeFrom && !routeTo" class="muted">—</div>

        </div>



        <div class="cell cell-line">

            <div class="line-text">{{ lineText || '—' }}</div>

        </div>



        <div class="cell cell-dates">

            <div class="date-row">

                <span class="date-label">{{ t('containers.loading') }}</span>

                <span class="date-value">{{ loadingDate || '—' }}</span>

            </div>

            <div class="date-row">

                <span class="date-label">ETA</span>

                <span class="date-value">{{ etaDate || '—' }}</span>

            </div>

        </div>



        <div class="cell cell-vehicles">

            <button
                type="button"
                class="vehicle-count-badge"
                :class="{ 'vehicle-count-badge--empty': !vehicleCount }"
                :disabled="!vehicleCount"
                :title="vehicleCount ? t('containers.showVehicles') : t('containers.noVehicles')"
                @click="vehicleCount && $emit('show-cars', container)"
            >
                <i class="pi pi-car" />
                <span class="vehicle-count-num">{{ vehicleCount }}</span>
                <span class="vehicle-count-label">{{ t('containers.vehicle') }}</span>
            </button>

        </div>



        <div class="cell cell-status">

            <span class="status-pill" :class="statusClass">

                <span class="status-dot" />

                <span class="status-pill-text">{{ statusLabel }}</span>

                <span v-if="statusEta" class="status-pill-eta">· {{ statusEta }}</span>

            </span>

        </div>



        <div class="cell cell-docs">

            <a

                v-if="container.bol_url"

                :href="container.bol_url"

                target="_blank"

                rel="noopener noreferrer"

                class="doc-link"

            >

                <i class="pi pi-file-pdf" />

                BOL

            </a>

            <span v-else class="muted">{{ t('containers.bolMissing') }}</span>

            <template v-if="showInvoice">
                <div v-if="container.invoice_ref" class="invoice-ref">

                    <i class="pi pi-receipt" />

                    {{ container.invoice_ref }}

                </div>

                <span v-else class="muted">{{ t('containers.invoiceMissing') }}</span>
            </template>

        </div>



        <div class="cell cell-actions">

            <input
                v-if="showZipUpload"
                ref="zipInputRef"
                type="file"
                accept=".zip,application/zip"
                class="zip-input-hidden"
                @change="onZipSelected"
            />

            <Button
                v-if="showZipUpload"
                icon="pi pi-upload"
                severity="secondary"
                text
                rounded
                class="zip-btn"
                :aria-label="t('containers.uploadZip')"
                :title="t('containers.uploadZip')"
                :loading="zipLoading"
                @click="triggerZipUpload"
            />

            <Button
                icon="pi pi-map-marker"
                :severity="canTrack ? 'info' : 'secondary'"
                text
                rounded
                :disabled="!canTrack"
                class="track-btn"
                :class="{ 'track-btn--ready': canTrack }"
                :aria-label="trackingTitle"
                :title="trackingTitle"
                @click="$emit('track', container)"
            />

        </div>

    </div>

    <ContainerGalleryLightbox
        v-if="directImageGallery"
        v-model:visible="galleryVisible"
        :images="galleryImages"
        :start-index="galleryStartIndex"
    />

    <Teleport to="body">
        <div
            v-if="galleryLoading"
            class="container-gallery-load-overlay"
            role="status"
            aria-live="polite"
            :aria-label="galleryLoadingTitle"
        >
            <div class="container-gallery-load-card">
                <ProgressSpinner style="width: 2.25rem; height: 2.25rem" />
                <p class="container-gallery-load-title">{{ galleryLoadingTitle }}</p>
                <p
                    v-if="galleryLoadPhase === 'preload' && galleryLoadProgress.total > 0"
                    class="container-gallery-load-count"
                >
                    {{ t('containers.galleryLoadingProgress', {
                        done: galleryLoadProgress.done,
                        total: galleryLoadProgress.total,
                    }) }}
                </p>
                <p
                    v-if="galleryLoadPhase === 'preload' && galleryLoadProgress.remaining > 0"
                    class="container-gallery-load-remaining"
                >
                    {{ t('containers.galleryLoadingRemaining', {
                        count: galleryLoadProgress.remaining,
                    }) }}
                </p>
                <div
                    v-if="galleryLoadPhase === 'preload' && galleryLoadProgress.total > 0"
                    class="container-gallery-load-track"
                    role="progressbar"
                    :aria-valuenow="galleryLoadProgress.percent"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <div
                        class="container-gallery-load-fill"
                        :style="{ width: `${galleryLoadProgress.percent}%` }"
                    />
                </div>
                <span
                    v-if="galleryLoadPhase === 'preload' && galleryLoadProgress.total > 0"
                    class="container-gallery-load-percent"
                >
                    {{ galleryLoadProgress.percent }}%
                </span>
            </div>
        </div>
    </Teleport>
</template>



<script setup>

import { computed, onBeforeUnmount, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import ContainerGalleryLightbox from './ContainerGalleryLightbox.vue';
import {
    containerRefKey,
    extractZipImagesForContainer,
    applyCloudinaryContainerPayload,
} from '../utils/containerZipImages';
import {
    fetchContainerCloudImages,
    formatCloudinaryUploadError,
    uploadContainerImagesToCloud,
} from '../utils/containerCloudinaryUpload';
import { preloadImageUrls } from '../utils/imagePreload';

import {

    containerDestination,

    containerLineText,

    containerListStatusClass,

    containerListStatusEta,

    containerListStatusLabel,

    containerOrigin,

    containerRefs,

    formatContainerDate,

} from '../utils/containerMeta';



const props = defineProps({

    container: {

        type: Object,

        required: true,

    },

    trackingAvailable: {

        type: Boolean,

        default: false,

    },

    showInvoice: {

        type: Boolean,

        default: true,

    },

    showZipUpload: {

        type: Boolean,

        default: false,

    },

    directImageGallery: {

        type: Boolean,

        default: false,

    },

    apiPrefix: {

        type: String,

        default: '/admin',

    },

});



const emit = defineEmits(['track', 'show-cars']);

const { t } = useI18n();
const toast = useToast();
const zipInputRef = ref(null);
const zipLoading = ref(false);
const galleryVisible = ref(false);
const galleryImages = ref([]);
const galleryStartIndex = ref(0);
const galleryLoading = ref(false);
const galleryLoadPhase = ref(null);
const galleryLoadProgress = ref({ done: 0, total: 0, percent: 0, remaining: 0 });
let galleryAbortController = null;



const refs = computed(() => containerRefs(props.container));

const routeFrom = computed(() => containerOrigin(props.container));

const routeTo = computed(() => containerDestination(props.container));

const lineText = computed(() => containerLineText(props.container));

const loadingDate = computed(() => formatContainerDate(props.container.loading_date));

const etaDate = computed(() => formatContainerDate(props.container.eta));

const statusLabel = computed(() => containerListStatusLabel(props.container, t));

const statusClass = computed(() => containerListStatusClass(props.container));

const statusEta = computed(() => containerListStatusEta(props.container));



const canTrack = computed(() => {
    const number = refs.value.container;

    if (!number) {
        return false;
    }

    const rowOk = props.container?.tracking_available !== false;

    return rowOk && props.trackingAvailable;
});

const trackingTitle = computed(() =>
    canTrack.value ? t('containers.track') : t('containers.trackUnavailable'),
);

const vehicleCount = computed(() => props.container?.vehicles?.length ?? 0);

const imageCount = computed(() => Number(props.container?.image_count ?? 0));

const thumbnailUrl = computed(() => {
    const url = props.container?.thumbnail_url;

    return typeof url === 'string' && url !== '' ? url : null;
});

const hasThumbnail = computed(() => thumbnailUrl.value !== null);

const hasImages = computed(() => imageCount.value > 0);

const showCountBadge = computed(() => imageCount.value > 1);

const imageTitle = computed(() => {
    if (galleryLoading.value) {
        if (galleryLoadPhase.value === 'preload' && galleryLoadProgress.value.total > 0) {
            return t('containers.galleryLoadingProgress', {
                done: galleryLoadProgress.value.done,
                total: galleryLoadProgress.value.total,
            });
        }

        return galleryLoadPhase.value === 'fetch'
            ? t('containers.galleryLoadingList')
            : t('containers.openingGallery');
    }

    if (hasImages.value) {
        return props.directImageGallery
            ? t('containers.openGalleryDirect', { count: imageCount.value })
            : t('containers.showImages', { count: imageCount.value });
    }

    return t('containers.noImages');
});

const galleryLoadingTitle = computed(() => {
    if (galleryLoadPhase.value === 'fetch') {
        return t('containers.galleryLoadingList');
    }

    if (galleryLoadPhase.value === 'preload') {
        return t('containers.openingGallery');
    }

    return t('containers.openingGallery');
});

const directImageGallery = computed(() => props.directImageGallery);

async function openGallery() {
    if (! hasImages.value || galleryLoading.value) {
        return;
    }

    if (! props.directImageGallery) {
        emit('show-cars', props.container);

        return;
    }

    const containerRef = refs.value.container || refs.value.booking;

    if (! containerRef) {
        return;
    }

    galleryAbortController?.abort();
    galleryAbortController = new AbortController();
    const { signal } = galleryAbortController;

    galleryLoading.value = true;
    galleryLoadPhase.value = 'fetch';
    galleryLoadProgress.value = { done: 0, total: 0, percent: 0, remaining: 0 };

    try {
        const payload = await fetchContainerCloudImages(containerRef, props.apiPrefix);

        if (signal.aborted) {
            return;
        }

        const images = payload?.images ?? [];

        if (! images.length) {
            toast.add({
                severity: 'warn',
                summary: t('containers.noImages'),
                detail: t('containers.galleryEmpty'),
                life: 3500,
            });

            return;
        }

        const urls = images.map((image) => image.url).filter(Boolean);

        galleryLoadPhase.value = 'preload';
        galleryLoadProgress.value = {
            done: 0,
            total: urls.length,
            percent: 0,
            remaining: urls.length,
        };

        const result = await preloadImageUrls(urls, {
            signal,
            onProgress: (progress) => {
                galleryLoadProgress.value = progress;
            },
        });

        if (signal.aborted) {
            return;
        }

        if (result.loaded === 0 && urls.length > 0) {
            toast.add({
                severity: 'error',
                summary: t('common.error'),
                detail: t('containers.galleryLoadFailed'),
                life: 4500,
            });

            return;
        }

        galleryImages.value = images;
        galleryStartIndex.value = 0;
        galleryVisible.value = true;
    } catch (e) {
        if (signal.aborted) {
            return;
        }

        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || t('containers.galleryLoadFailed'),
            life: 4500,
        });
    } finally {
        if (! signal.aborted) {
            galleryLoading.value = false;
            galleryLoadPhase.value = null;
        }
    }
}

onBeforeUnmount(() => {
    galleryAbortController?.abort();
});

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

    try {
        const extracted = await extractZipImagesForContainer(file, props.container?.vehicles ?? []);
        const refs = containerRefs(props.container);
        const containerRef = refs.container || refs.booking || '';

        const payload = await uploadContainerImagesToCloud({
            containerRef,
            images: extracted.images,
            apiPrefix: '/admin',
            replace: true,
        });

        applyCloudinaryContainerPayload(containerRefKey(props.container), payload);

        const count = payload.meta?.count ?? payload.images?.length ?? 0;
        props.container.image_count = count;
        props.container.thumbnail_url = payload.images?.[0]?.url ?? null;

        for (const image of extracted.images) {
            if (image.url?.startsWith('blob:')) {
                URL.revokeObjectURL(image.url);
            }
        }

        toast.add({
            severity: 'success',
            summary: t('containers.zipUploadSuccess'),
            detail: t('containers.zipUploadSuccessDetail', {
                count: payload.meta?.count ?? payload.images?.length ?? 0,
            }),
            life: 4000,
        });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('containers.zipUploadFailed'),
            detail: formatCloudinaryUploadError(e),
            life: 5000,
        });
    } finally {
        zipLoading.value = false;
    }
}

</script>



<style scoped>

.container-row {

    display: grid;

    grid-template-columns: 64px minmax(150px, 1.1fr) minmax(120px, 0.9fr) minmax(130px, 1fr) minmax(110px, 0.85fr) minmax(120px, 0.8fr) minmax(180px, 1.15fr) minmax(90px, 0.55fr) minmax(100px, 0.7fr) minmax(48px, 0.4fr);

    gap: 1rem;

    align-items: center;

    padding: 1rem 1.25rem;

    background: var(--admin-surface);

    border-bottom: 1px solid var(--vs-border);

    transition: background 0.12s ease;

}



.container-row:hover {

    background: var(--vs-surface-hover);

}



.cell {

    min-width: 0;

}



.cell-image {

    display: flex;

    align-items: center;

    justify-content: center;

}

.thumb-btn {

    position: relative;

    width: 56px;

    height: 42px;

    padding: 0;

    border: 1px solid #e4e4e7;

    border-radius: 8px;

    overflow: hidden;

    background: #fafafa;

    cursor: default;

    flex-shrink: 0;

    transition: box-shadow 0.15s ease, transform 0.15s ease;

}

.thumb-btn.clickable {
    cursor: pointer;
}

.thumb-btn.clickable:hover {
    box-shadow: 0 4px 12px rgb(0 0 0 / 12%);
    transform: translateY(-1px);
}

.thumb-btn.clickable:active {
    transform: translateY(0);
}

.thumb-btn--loading {
    cursor: wait;
}

.thumb-loading {
    width: 100%;
    height: 100%;
    min-height: 42px;
    display: grid;
    place-items: center;
    background: var(--vs-surface-elevated, #fafafa);
}

.thumb-btn.empty {

    opacity: 0.65;

}

.thumb-btn:disabled {

    cursor: default;

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

    display: grid;

    place-items: center;

    color: #a1a1aa;

    font-size: 1.1rem;

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

.ref-line {

    display: flex;

    align-items: baseline;

    gap: 0.4rem;

    font-size: 0.8rem;

    margin-bottom: 0.2rem;

}



.ref-label {

    font-size: 0.65rem;

    font-weight: 700;

    letter-spacing: 0.05em;

    color: var(--vs-text-subtle);

    min-width: 2.5rem;

}



.ref-value {

    font-family: ui-monospace, monospace;

    color: var(--vs-text-secondary);

    overflow-wrap: anywhere;

}

.ref-value--link {

    padding: 0;

    border: none;

    background: none;

    cursor: pointer;

    text-align: inherit;

    color: #2563eb;

    font-family: ui-monospace, monospace;

    font-size: inherit;

}

.ref-value--link:hover {

    text-decoration: underline;

}



.source-hint {

    display: block;

    margin-top: 0.25rem;

    font-size: 0.68rem;

    color: var(--vs-text-subtle);

}



.customer-name {

    font-weight: 600;

    font-size: 0.9rem;

    color: var(--vs-text);

    word-break: break-word;

}



.cell-route {

    display: flex;

    flex-direction: column;

    gap: 0.3rem;

}



.route-line {

    display: flex;

    align-items: center;

    gap: 0.4rem;

    font-size: 0.84rem;

    color: var(--vs-text-secondary);

}



.route-dot {

    width: 7px;

    height: 7px;

    border-radius: 50%;

    flex-shrink: 0;

}



.route-dot--origin {

    background: #22c55e;

}



.route-pin {

    font-size: 0.75rem;

    color: #ef4444;

}



.line-text {

    font-size: 0.84rem;

    color: var(--vs-text-secondary);

    word-break: break-word;

}



.date-row {

    display: flex;

    justify-content: space-between;

    gap: 0.5rem;

    font-size: 0.78rem;

    line-height: 1.6;

}



.date-label {

    color: var(--vs-text-muted);

}



.date-value {

    color: var(--vs-text);

    font-variant-numeric: tabular-nums;

}



.vehicle-count-badge {

    display: inline-flex;

    align-items: center;

    gap: 0.35rem;

    padding: 0.35rem 0.65rem;

    border: 1px solid rgb(37 99 235 / 30%);

    border-radius: 999px;

    background: rgb(37 99 235 / 10%);

    color: #2563eb;

    font-size: 0.8rem;

    font-weight: 600;

    cursor: pointer;

    transition: background 0.12s ease, transform 0.12s ease;

}

.vehicle-count-badge:hover:not(:disabled) {

    background: rgb(37 99 235 / 18%);

    transform: translateY(-1px);

}

.vehicle-count-badge--empty,
.vehicle-count-badge:disabled {

    opacity: 0.45;

    cursor: not-allowed;

    border-color: var(--vs-border);

    background: var(--vs-surface-elevated);

    color: var(--vs-text-muted);

}

.vehicle-count-num {

    font-variant-numeric: tabular-nums;

    font-size: 0.95rem;

}

.vehicle-count-label {

    font-weight: 500;

}



.cell-docs {

    display: flex;

    flex-direction: column;

    gap: 0.35rem;

    font-size: 0.78rem;

}



.doc-link {

    display: inline-flex;

    align-items: center;

    gap: 0.3rem;

    color: #2563eb;

    text-decoration: none;

    font-weight: 500;

}



.doc-link:hover {

    text-decoration: underline;

}



.invoice-ref {

    display: inline-flex;

    align-items: center;

    gap: 0.3rem;

    color: var(--vs-text-secondary);

}



.cell-actions {

    display: flex;

    justify-content: flex-end;

    gap: 0.15rem;

}

.zip-input-hidden {

    display: none;

}

.zip-btn :deep(.p-button-icon) {

    color: var(--vs-text-muted);

}

.track-btn--ready :deep(.p-button-icon) {
    color: #0f766e;
}

.track-btn--ready:hover :deep(.p-button-icon) {
    color: #0d9488;
}

.track-btn:focus-visible :deep(.p-button) {
    outline: 2px solid #14b8a6;
    outline-offset: 2px;
}

.track-btn:not(.track-btn--ready) :deep(.p-button-icon) {
    opacity: 0.45;
}

.cell-status {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: wrap;
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
    flex-shrink: 0;
}

.status-pill-eta {
    font-weight: 400;
    opacity: 0.9;
    font-variant-numeric: tabular-nums;
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

.muted {

    font-size: 0.78rem;

    color: var(--vs-text-subtle);

}

.container-gallery-load-overlay {
    position: fixed;
    inset: 0;
    z-index: 12000;
    display: grid;
    place-items: center;
    padding: 1.25rem;
    background: rgb(9 9 11 / 58%);
    backdrop-filter: blur(6px);
}

.container-gallery-load-card {
    width: min(22rem, 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.65rem;
    padding: 1.35rem 1.25rem 1.1rem;
    border-radius: 16px;
    border: 1px solid var(--vs-border);
    background: var(--admin-surface, #fff);
    box-shadow: 0 18px 48px rgb(0 0 0 / 18%);
    text-align: center;
}

.container-gallery-load-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--vs-text);
}

.container-gallery-load-count {
    margin: 0;
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--vs-text-secondary);
    font-variant-numeric: tabular-nums;
}

.container-gallery-load-remaining {
    margin: 0;
    font-size: 0.8rem;
    color: var(--vs-text-muted);
    font-variant-numeric: tabular-nums;
}

.container-gallery-load-track {
    width: 100%;
    height: 8px;
    margin-top: 0.15rem;
    border-radius: 999px;
    background: var(--vs-surface-elevated, #f4f4f5);
    overflow: hidden;
}

.container-gallery-load-fill {
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #2563eb 0%, #3b82f6 100%);
    transition: width 0.18s ease-out;
}

.container-gallery-load-percent {
    font-size: 0.78rem;
    font-weight: 700;
    color: #2563eb;
    font-variant-numeric: tabular-nums;
}

@media (prefers-reduced-motion: reduce) {
    .container-gallery-load-fill {
        transition: none;
    }
}

@media (max-width: 1200px) {

    .container-row {

        grid-template-columns: 1fr;

        gap: 0.65rem;

    }



    .cell-actions {

        justify-content: flex-start;

    }

}

</style>

