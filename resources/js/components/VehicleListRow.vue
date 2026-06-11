<template>
    <div class="vehicle-row">
        <!-- Vehicle -->
        <div class="cell cell-vehicle">
            <VehicleImageGallery :vehicle="vehicle" variant="row" />
            <div class="vehicle-info">
                <div class="title-line">
                    <button type="button" class="title title-link" @click="$emit('open-detail', vehicle)">
                        {{ title || '—' }}
                    </button>
                    <span v-if="fuel" class="fuel-badge" :class="fuelClass">{{ fuel }}</span>
                    <span class="source-pill" :class="sourcePillClass">{{ sourceLabel }}</span>
                    <span
                        v-if="hdGalleryCount > 1"
                        class="gallery-pill"
                        :title="`${hdGalleryCount} صورة HD`"
                    >
                        <i class="pi pi-images" />
                        {{ hdGalleryCount }}
                    </span>
                    <i
                        v-if="mode === 'admin' && hasLocalUploads"
                        class="pi pi-images local-upload-hint"
                        title="يوجد صور مرفوعة محلياً للتاجر"
                        aria-label="صور مرفوعة محلياً"
                    />
                </div>
                <VinCopyLabel :vin="vehicle.vin" class="vehicle-vin-line" />
                <div class="entered-by">Entered by {{ enteredBy }}</div>
            </div>
        </div>

        <!-- Lot & source -->
        <div class="cell cell-lot">
            <div class="lot-id">{{ lot || '—' }}</div>
            <div class="auction">{{ auction || '—' }}</div>
        </div>

        <!-- Route & status -->
        <div class="cell cell-route">
            <div v-if="origin" class="route-line">
                <span class="route-dot route-dot--origin" />
                <span>{{ origin }}</span>
            </div>
            <div v-if="destination" class="route-line">
                <i class="pi pi-map-marker route-pin" />
                <span>{{ destination }}</span>
            </div>
            <span v-if="vinstackStatus" class="status-pill" :class="statusClass">
                <span class="status-dot" />
                {{ vinstackStatus }}
            </span>
        </div>

        <!-- References -->
        <div class="cell cell-refs">
            <div v-if="container" class="ref-line ref-line--container">
                <i class="pi pi-box ref-icon" />
                <span class="ref-container-num">{{ container }}</span>
                <Button
                    icon="pi pi-map-marker"
                    :severity="canTrackContainer ? 'info' : 'secondary'"
                    text
                    rounded
                    size="small"
                    :disabled="!canTrackContainer"
                    class="track-btn"
                    :class="{ 'track-btn--ready': canTrackContainer }"
                    aria-label="تتبع الحاوية"
                    title="تتبع الحاوية"
                    @click.stop="$emit('track-container', vehicle)"
                />
            </div>
            <div v-if="booking" class="ref-line">
                <i class="pi pi-file ref-icon" />
                <span>{{ booking }}</span>
            </div>
            <div class="ref-badges">
                <span
                    v-if="keysInfo.label"
                    class="mini-badge"
                    :class="keysInfo.present ? 'mini-badge--ok' : 'mini-badge--bad'"
                >
                    <i class="pi pi-key" />
                    {{ keysInfo.label }}
                </span>
                <span class="mini-badge mini-badge--neutral">
                    <i class="pi pi-file" />
                    {{ titleStatus }}
                </span>
            </div>
        </div>

        <!-- Dates -->
        <div class="cell cell-dates">
            <div class="date-row">
                <span class="date-label">Purchase</span>
                <span class="date-value">{{ purchaseDate || '—' }}</span>
            </div>
            <div class="date-row">
                <span class="date-label">Arrived terminal</span>
                <span class="date-value">{{ arrivedDate || '—' }}</span>
            </div>
        </div>

        <!-- Admin: dealer + local status -->
        <div v-if="mode === 'admin'" class="cell cell-admin">
            <span
                v-if="vehicle.status"
                class="status-pill assignment-pill"
                :class="assignmentBadgeClass"
            >
                <span class="status-dot" />
                {{ vehicle.status }}
            </span>
            <div v-if="dealerName" class="dealer-tag">
                <span class="dealer-tag__name">{{ dealerName }}</span>
                <button
                    type="button"
                    class="dealer-tag__remove"
                    title="إلغاء الإسناد"
                    aria-label="إلغاء إسناد التاجر"
                    @click.stop="$emit('unassign', vehicle)"
                >
                    <i class="pi pi-times" />
                </button>
            </div>
            <Button
                v-if="isManual"
                icon="pi pi-pencil"
                label="تعديل"
                size="small"
                severity="secondary"
                outlined
                title="تعديل سيارة يدوية"
                @click="$emit('edit', vehicle)"
            />
            <Button label="إسناد" size="small" class="btn-assign" @click="$emit('assign', vehicle)" />
        </div>

        <!-- Dealer: local status + action -->
        <div v-else class="cell cell-actions">
            <Tag :value="vehicle.status" class="local-tag" />
            <Button
                icon="pi pi-pencil"
                severity="secondary"
                text
                rounded
                title="تحديث الحالة"
                @click="$emit('update-status', vehicle)"
            />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import VehicleImageGallery from './VehicleImageGallery.vue';
import VinCopyLabel from './VinCopyLabel.vue';
import { vehicleGalleryCount } from '../utils/vehicleImages';
import {
    vehicleArrivedDate,
    vehicleAssignmentBadgeClass,
    vehicleAuction,
    vehicleBookingRef,
    vehicleContainerRef,
    vehicleDestination,
    vehicleEnteredBy,
    vehicleFuelClass,
    vehicleFuelType,
    vehicleKeysInfo,
    vehicleLot,
    vehicleOrigin,
    vehiclePurchaseDate,
    vehicleStatusClass,
    vehicleTitle,
    vehicleTitleStatus,
    vehicleVinstackStatus,
} from '../utils/vehicleMeta';

const props = defineProps({
    vehicle: {
        type: Object,
        required: true,
    },
    mode: {
        type: String,
        default: 'admin',
        validator: (v) => ['admin', 'dealer'].includes(v),
    },
    trackingAvailable: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['assign', 'update-status', 'open-detail', 'edit', 'unassign', 'track-container']);

const isManual = computed(() => props.vehicle?.source === 'manual');
const sourceLabel = computed(() => (isManual.value ? 'يدوية' : 'مستوردة'));
const sourcePillClass = computed(() =>
    isManual.value ? 'source-pill--manual' : 'source-pill--vinstack',
);

const title = computed(() => vehicleTitle(props.vehicle));
const hdGalleryCount = computed(() => vehicleGalleryCount(props.vehicle));
const hasLocalUploads = computed(() => (props.vehicle?.uploaded_images?.length ?? 0) > 0);
const fuel = computed(() => vehicleFuelType(props.vehicle));
const fuelClass = computed(() => vehicleFuelClass(fuel.value));
const lot = computed(() => vehicleLot(props.vehicle));
const auction = computed(() => vehicleAuction(props.vehicle));
const origin = computed(() => vehicleOrigin(props.vehicle));
const destination = computed(() => vehicleDestination(props.vehicle));
const vinstackStatus = computed(() => vehicleVinstackStatus(props.vehicle));
const statusClass = computed(() => vehicleStatusClass(vinstackStatus.value));
const container = computed(() => vehicleContainerRef(props.vehicle));
const canTrackContainer = computed(() => {
    if (! container.value) {
        return false;
    }

    return props.trackingAvailable;
});
const booking = computed(() => vehicleBookingRef(props.vehicle));
const keysInfo = computed(() => vehicleKeysInfo(props.vehicle));
const titleStatus = computed(() => vehicleTitleStatus(props.vehicle));
const purchaseDate = computed(() => vehiclePurchaseDate(props.vehicle));
const arrivedDate = computed(() => vehicleArrivedDate(props.vehicle));
const enteredBy = computed(() => vehicleEnteredBy(props.vehicle));
const dealerName = computed(() => props.vehicle.active_assignment?.dealer?.company_name ?? null);
const assignmentBadgeClass = computed(() => vehicleAssignmentBadgeClass(props.vehicle));
</script>

<style scoped>
.vehicle-row {
    display: grid;
    grid-template-columns: minmax(240px, 1.5fr) minmax(110px, 0.85fr) minmax(130px, 1fr) minmax(150px, 1.05fr) minmax(140px, 0.9fr) minmax(120px, 0.75fr);
    gap: 1rem;
    align-items: center;
    padding: 1rem 1.25rem;
    background: var(--admin-surface);
    border-bottom: 1px solid var(--vs-border);
    transition: background 0.12s ease;
}

.vehicle-row:hover {
    background: var(--vs-surface-hover);
}

.cell {
    min-width: 0;
}

.cell-vehicle {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
}

.vehicle-info {
    flex: 1;
    min-width: 0;
}

.title-line {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
    margin-bottom: 0.2rem;
}

.title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--vs-text);
    line-height: 1.3;
}

.title-link {
    padding: 0;
    border: none;
    background: none;
    text-align: start;
    cursor: pointer;
}

.title-link:hover {
    color: #2563eb;
    text-decoration: underline;
}

.local-upload-hint {
    font-size: 0.85rem;
    color: #047857;
    flex-shrink: 0;
}

.fuel-badge {
    display: inline-block;
    padding: 0.12rem 0.45rem;
    border-radius: 4px;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    line-height: 1.4;
}

.fuel-hybrid {
    background: #dcfce7;
    color: #15803d;
}

.fuel-gas {
    background: #ffe4e6;
    color: #be123c;
}

.fuel-electric {
    background: #dbeafe;
    color: #1d4ed8;
}

.fuel-default {
    background: #f4f4f5;
    color: #52525b;
}

.source-pill {
    display: inline-block;
    padding: 0.12rem 0.45rem;
    border-radius: 4px;
    font-size: 0.62rem;
    font-weight: 700;
    line-height: 1.4;
}

.source-pill--vinstack {
    background: #dbeafe;
    color: #1d4ed8;
}

.source-pill--manual {
    background: #e0e7ff;
    color: #3730a3;
}

.gallery-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.12rem 0.45rem;
    border-radius: 999px;
    border: 1px solid var(--vs-border);
    background: var(--vs-surface-hover);
    font-size: 0.62rem;
    font-weight: 600;
    line-height: 1.4;
    color: var(--vs-text-muted);
}

.gallery-pill i {
    font-size: 0.6rem;
}

.vehicle-vin-line {
    margin-bottom: 0.15rem;
}

.entered-by {
    font-size: 0.72rem;
    color: var(--vs-text-subtle);
}

.lot-id {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--vs-text);
    margin-bottom: 0.15rem;
}

.auction {
    font-size: 0.78rem;
    color: var(--vs-text-muted);
    line-height: 1.35;
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

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    align-self: flex-start;
    margin-top: 0.15rem;
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

.assignment-pill--assigned {
    background: #ccfbf1;
    color: #0f766e;
}

.assignment-pill--unassigned {
    background: #f4f4f5;
    color: #52525b;
}

.ref-line {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.8rem;
    color: var(--vs-text-secondary);
    font-family: ui-monospace, monospace;
    margin-bottom: 0.2rem;
}

.ref-line--container {
    flex-wrap: wrap;
}

.ref-container-num {
    flex: 1;
    min-width: 0;
    word-break: break-all;
}

.track-btn {
    flex-shrink: 0;
    margin-inline-start: auto;
}

.track-btn--ready :deep(.p-button-icon) {
    color: #0ea5e9;
}

.track-btn--ready:hover :deep(.p-button-icon) {
    color: #0284c7;
}

.track-btn:focus-visible :deep(.p-button) {
    outline: 2px solid #14b8a6;
    outline-offset: 2px;
}

.track-btn:not(.track-btn--ready) :deep(.p-button-icon) {
    opacity: 0.45;
}

.ref-icon {
    font-size: 0.75rem;
    color: var(--vs-text-subtle);
}

.ref-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-top: 0.15rem;
}

.mini-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.15rem 0.45rem;
    border-radius: 999px;
    border: 1px solid var(--vs-border);
    font-size: 0.68rem;
    color: var(--vs-text-muted);
    background: var(--admin-surface);
}

.mini-badge i {
    font-size: 0.65rem;
}

.mini-badge--ok {
    border-color: #bbf7d0;
    color: #15803d;
    background: #f0fdf4;
}

.mini-badge--bad {
    border-color: #fecaca;
    color: #dc2626;
    background: #fef2f2;
}

.mini-badge--neutral {
    background: var(--vs-surface-hover);
}

.date-row {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
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

.cell-admin {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
}

.cell-admin .assignment-pill {
    align-self: center;
    margin-top: 0;
    flex-shrink: 0;
}

.cell-admin .dealer-tag {
    margin-top: 0;
}

.cell-admin :deep(.p-button) {
    flex-shrink: 0;
}

.cell-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.4rem;
}

.dealer-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    max-width: 100%;
    padding: 0.15rem 0.35rem 0.15rem 0.55rem;
    border-radius: 999px;
    border: 1px solid var(--vs-border);
    background: var(--vs-surface-hover);
    font-size: 0.72rem;
    color: var(--vs-text-secondary);
}

.dealer-tag__name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 8rem;
}

.dealer-tag__remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.35rem;
    height: 1.35rem;
    padding: 0;
    border: none;
    border-radius: 999px;
    background: transparent;
    color: var(--vs-text-muted);
    cursor: pointer;
    flex-shrink: 0;
}

.dealer-tag__remove:hover {
    background: #fee2e2;
    color: #dc2626;
}

.dealer-tag__remove i {
    font-size: 0.65rem;
}

.local-tag {
    font-size: 0.72rem;
}

@media (max-width: 1100px) {
    .vehicle-row {
        grid-template-columns: 1fr;
        gap: 0.75rem;
        padding: 1rem;
    }

    .cell-admin {
        justify-content: flex-start;
    }

    .cell-actions {
        flex-direction: row;
        align-items: center;
        justify-content: flex-start;
    }
}
</style>
