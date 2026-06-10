<template>

    <div class="container-row">

        <div class="cell cell-refs">

            <div v-if="refs.container" class="ref-line">

                <span class="ref-label">CNTR</span>

                <span class="ref-value">{{ refs.container }}</span>

            </div>

            <div v-if="refs.booking" class="ref-line">

                <span class="ref-label">BKG</span>

                <span class="ref-value">{{ refs.booking }}</span>

            </div>

            <div v-if="refs.seal" class="ref-line">

                <span class="ref-label">SEAL</span>

                <span class="ref-value">{{ refs.seal }}</span>

            </div>

            <span v-if="container.source === 'vehicles'" class="source-hint">من بيانات السيارات</span>

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

                <span class="date-label">تحميل</span>

                <span class="date-value">{{ loadingDate || '—' }}</span>

            </div>

            <div class="date-row">

                <span class="date-label">ETA</span>

                <span class="date-value">{{ etaDate || '—' }}</span>

            </div>

        </div>



        <div class="cell cell-vehicles">

            <div v-for="(vehicle, index) in container.vehicles" :key="vehicle.vin ?? index" class="vehicle-line">

                <span class="vehicle-title">{{ vehicle.title }}</span>

                <VinCopyLabel
                    v-if="vehicle.vin"
                    :vin="vehicle.vin"
                    size="compact"
                    block
                    class="vehicle-vin"
                />

            </div>

            <div v-if="!container.vehicles?.length" class="muted">—</div>

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

            <span v-else class="muted">BOL —</span>

            <template v-if="showInvoice">
                <div v-if="container.invoice_ref" class="invoice-ref">

                    <i class="pi pi-receipt" />

                    {{ container.invoice_ref }}

                </div>

                <span v-else class="muted">فاتورة —</span>
            </template>

        </div>



        <div class="cell cell-actions">

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

</template>



<script setup>

import { computed } from 'vue';

import Button from 'primevue/button';

import VinCopyLabel from './VinCopyLabel.vue';

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

});



defineEmits(['track']);



const refs = computed(() => containerRefs(props.container));

const routeFrom = computed(() => containerOrigin(props.container));

const routeTo = computed(() => containerDestination(props.container));

const lineText = computed(() => containerLineText(props.container));

const loadingDate = computed(() => formatContainerDate(props.container.loading_date));

const etaDate = computed(() => formatContainerDate(props.container.eta));

const statusLabel = computed(() => containerListStatusLabel(props.container));

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
    canTrack.value ? 'تتبع الشحنة' : 'تتبع الشحنة — بيانات المسار غير متوفرة',
);

</script>



<style scoped>

.container-row {

    display: grid;

    grid-template-columns: minmax(150px, 1.1fr) minmax(120px, 0.9fr) minmax(130px, 1fr) minmax(110px, 0.85fr) minmax(120px, 0.8fr) minmax(180px, 1.15fr) minmax(90px, 0.55fr) minmax(100px, 0.7fr) minmax(48px, 0.4fr);

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

    word-break: break-all;

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



.vehicle-line {

    margin-bottom: 0.35rem;

}



.vehicle-title {

    display: block;

    font-size: 0.82rem;

    font-weight: 600;

    color: var(--vs-text);

}



.vehicle-vin {

    display: block;

    margin-top: 0.1rem;

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

