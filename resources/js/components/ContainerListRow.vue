<template>

    <div class="container-row">

        <div class="cell cell-image">
            <button
                type="button"
                class="thumb-btn"
                :class="{
                    empty: !hasThumbnail,
                    clickable: canOpenImageGallery,
                    'thumb-btn--loading': galleryLoading,
                }"
                :disabled="!canOpenImageGallery || galleryLoading"
                :title="imageTitle"
                :aria-label="imageTitle"
                :aria-busy="galleryLoading"
                @click.stop="openGallery"
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

                <RouterLink
                    v-if="linkContainerDetail && containerDetailLink"
                    :to="containerDetailLink"
                    class="ref-value ref-value--link"
                >
                    {{ refs.container }}
                </RouterLink>

                <button
                    v-else-if="refs.container"
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

            <div v-if="showVehicleThumbs && vehicleCount" class="vehicle-thumbs">
                <VehicleImageGallery
                    v-for="(vehicle, idx) in previewVehicles"
                    :key="vehicle.vin || vehicle.id || `vehicle-${idx}`"
                    :vehicle="vehicle"
                    variant="row"
                    :api-mode="galleryApiMode"
                    class="vehicle-thumb-item"
                />
                <span v-if="extraVehicleCount" class="vehicle-thumbs-more">+{{ extraVehicleCount }}</span>
            </div>

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
                :loading="containerUploadStore.isContainerBusy(containerRowKey)"
                :disabled="containerUploadStore.isContainerBusy(containerRowKey)"
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
</template>



<script setup>

import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import ContainerGalleryLightbox from './ContainerGalleryLightbox.vue';
import VehicleImageGallery from './VehicleImageGallery.vue';
import {
    containerRefKey,
} from '../utils/containerZipImages';
import {
    fetchContainerCloudImages,
} from '../utils/containerCloudinaryUpload';
import { useContainerUploadStore } from '../stores/containerUpload';

import {

    containerDestination,

    containerDetailRoute,

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

        default: true,

    },

    apiPrefix: {

        type: String,

        default: '/admin',

    },

    linkContainerDetail: {

        type: Boolean,

        default: false,

    },

    showVehicleThumbs: {

        type: Boolean,

        default: false,

    },

});



const emit = defineEmits(['track', 'show-cars']);

const { t } = useI18n();
const toast = useToast();
const containerUploadStore = useContainerUploadStore();
const zipInputRef = ref(null);
const galleryVisible = ref(false);
const galleryImages = ref([]);
const galleryStartIndex = ref(0);
const galleryLoading = ref(false);
let galleryAbortController = null;
let unsubscribeContainerUpload = null;



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

const previewVehicles = computed(() => (props.container?.vehicles ?? []).slice(0, 4));

const extraVehicleCount = computed(() => Math.max(vehicleCount.value - previewVehicles.value.length, 0));

const containerDetailLink = computed(() => {
    if (! props.linkContainerDetail) {
        return null;
    }

    const role = props.apiPrefix === '/dealer' ? 'dealer' : 'admin';

    return containerDetailRoute(props.container, role);
});

const galleryApiMode = computed(() => (props.apiPrefix === '/dealer' ? 'dealer' : 'admin'));

const showVehicleThumbs = computed(() => props.showVehicleThumbs);

const containerRowKey = computed(() => containerRefKey(props.container));

const imageCount = computed(() => Number(props.container?.image_count ?? 0));

const thumbnailUrl = computed(() => {
    const url = props.container?.thumbnail_url;

    return typeof url === 'string' && url !== '' ? url : null;
});

const hasThumbnail = computed(() => thumbnailUrl.value !== null);

const hasImages = computed(() => imageCount.value > 0);

const canOpenImageGallery = computed(() => {
    if (! props.directImageGallery) {
        return hasImages.value;
    }

    return hasImages.value || hasThumbnail.value;
});

const showCountBadge = computed(() => imageCount.value > 1);

const imageTitle = computed(() => {
    if (galleryLoading.value) {
        return t('containers.galleryLoadingList');
    }

    if (hasImages.value || (props.directImageGallery && hasThumbnail.value)) {
        return props.directImageGallery
            ? t('containers.openGalleryDirect', { count: imageCount.value || 1 })
            : t('containers.showImages', { count: imageCount.value });
    }

    return t('containers.noImages');
});

const directImageGallery = computed(() => props.directImageGallery);

async function openGallery() {
    if (! canOpenImageGallery.value || galleryLoading.value) {
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
        }
    }
}

function bindContainerUploadListener() {
    unsubscribeContainerUpload?.();

    const key = containerRefKey(props.container);

    if (! key) {
        return;
    }

    unsubscribeContainerUpload = containerUploadStore.subscribe(key, ({ payload }) => {
        if (! payload) {
            return;
        }

        const count = payload.meta?.count ?? payload.images?.length ?? 0;
        props.container.image_count = count;
        props.container.thumbnail_url = payload.images?.[0]?.url ?? null;
    });
}

onMounted(bindContainerUploadListener);

onBeforeUnmount(() => {
    galleryAbortController?.abort();
    unsubscribeContainerUpload?.();
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

    const name = String(file.name || '').toLowerCase();
    const isZip = name.endsWith('.zip')
        || file.type === 'application/zip'
        || file.type === 'application/x-zip-compressed';

    if (! isZip) {
        toast.add({
            severity: 'warn',
            summary: t('common.error'),
            detail: 'يُقبل ملف ZIP فقط (.zip)',
            life: 3500,
        });

        return;
    }

    const refs = containerRefs(props.container);
    const containerRef = refs.container || refs.booking || '';
    const key = containerRefKey(props.container);

    if (! containerRef || ! key) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: 'لا يوجد رقم حاوية أو حجز',
            life: 4000,
        });

        return;
    }

    if (containerUploadStore.isContainerBusy(key)) {
        toast.add({
            severity: 'info',
            summary: t('containers.zipUploadInProgress'),
            detail: t('containers.zipUploadInProgressDetail'),
            life: 3500,
        });

        return;
    }

    const jobId = containerUploadStore.enqueueZip({
        containerRef,
        containerLabel: containerRef,
        containerKey: key,
        zipFile: file,
        apiPrefix: props.apiPrefix,
        replace: true,
        onAccepted: (message) => {
            toast.add({
                severity: 'success',
                summary: 'تم الرفع',
                detail: message || t('containers.zipUploadStartedDetail'),
                life: 5000,
            });
        },
    });

    if (! jobId) {
        toast.add({
            severity: 'warn',
            summary: t('common.error'),
            detail: 'تعذّر بدء الرفع',
            life: 4000,
        });
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

.ref-value--link.router-link-active {

    font-weight: 600;

}



.cell-vehicles {

    display: flex;

    flex-direction: column;

    align-items: flex-start;

    gap: 0.45rem;

}

.vehicle-thumbs {

    display: flex;

    flex-wrap: wrap;

    align-items: center;

    gap: 0.35rem;

    max-width: 100%;

}

.vehicle-thumb-item :deep(.thumb-btn--row) {

    width: 44px;

    height: 33px;

    border-radius: 6px;

}

.vehicle-thumb-item :deep(.count-badge--row) {

    font-size: 9px;

    min-width: 14px;

    height: 14px;

    line-height: 14px;

}

.vehicle-thumbs-more {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 28px;

    height: 28px;

    padding: 0 0.35rem;

    border-radius: 6px;

    background: var(--vs-surface-elevated);

    border: 1px solid var(--vs-border);

    color: var(--vs-text-muted);

    font-size: 0.72rem;

    font-weight: 600;

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

