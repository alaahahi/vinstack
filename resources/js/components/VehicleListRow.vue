<template>
    <div class="vehicle-row">
        <!-- Vehicle -->
        <div class="cell cell-vehicle">
            <div class="vehicle-thumb-wrap">
                <VehicleImageGallery :vehicle="vehicle" variant="row" :api-mode="mode" />
                <span
                    v-if="hasUnreadMessages"
                    class="message-unread-badge"
                    :title="unreadMessagesTitle"
                    :aria-label="unreadMessagesTitle"
                >{{ unreadMessagesLabel }}</span>
            </div>
            <div class="vehicle-info">
                <div class="title-line">
                    <button type="button" class="title title-link" @click="$emit('open-detail', vehicle)">
                        {{ title || '—' }}
                    </button>
                    <span v-if="hasUnreadMessages" class="message-pill">{{ unreadMessagesLabel }}</span>
                    <span v-if="fuel" class="fuel-badge" :class="fuelClass">{{ fuel }}</span>
                    <span class="source-pill" :class="sourcePillClass">{{ sourceLabel }}</span>
                    <i
                        v-if="mode === 'admin' && hasLocalUploads"
                        class="pi pi-images local-upload-hint"
                        :title="t('vehicles.localUploads')"
                        :aria-label="t('vehicles.localUploadsAria')"
                    />
                </div>
                <VinCopyLabel :vin="vehicle.vin" class="vehicle-vin-line" />
                <div class="entered-by">{{ t('vehicles.enteredBy', { name: enteredBy }) }}</div>
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
                <div class="ref-line__meta">
                    <i class="pi pi-box ref-icon" />
                    <span class="ref-container-num">{{ container }}</span>
                </div>
                <Button
                    icon="pi pi-map-marker"
                    :severity="canTrackContainer ? 'help' : 'secondary'"
                    :text="!canTrackContainer"
                    rounded
                    size="small"
                    :disabled="!canTrackContainer"
                    class="track-btn"
                    :class="{
                        'track-btn--ready': canTrackContainer,
                        'track-btn--dealer': mode === 'dealer',
                        'track-btn--pulse': mode === 'dealer' && canTrackContainer,
                    }"
                    :aria-label="t('vehicles.trackContainer')"
                    :title="canTrackContainer ? t('vehicles.trackContainer') : t('containers.trackUnavailable')"
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
                <span class="date-label">{{ t('vehicles.purchase') }}</span>
                <span class="date-value">{{ purchaseDate || '—' }}</span>
            </div>
            <div class="date-row">
                <span class="date-label">{{ t('vehicles.arrivedTerminal') }}</span>
                <span class="date-value">{{ arrivedDate || '—' }}</span>
            </div>
        </div>

        <!-- Admin: dealer + local status -->
        <div v-if="mode === 'admin'" class="cell cell-admin">
            <span
                v-if="vehicle.status && !isAssigned"
                class="status-pill assignment-pill"
                :class="assignmentBadgeClass"
            >
                <span class="status-dot" />
                {{ vehicle.status }}
            </span>
            <div v-if="dealerDisplayName" class="dealer-tag">
                <span class="dealer-tag__name">{{ dealerDisplayName }}</span>
                <span v-if="dealerCompanyName && dealerCompanyName !== dealerDisplayName" class="dealer-tag__sub">
                    {{ dealerCompanyName }}
                </span>
                <button
                    type="button"
                    class="dealer-tag__remove"
                    :title="t('vehicles.unassignTitle')"
                    :aria-label="t('vehicles.unassignDealer')"
                    @click.stop="$emit('unassign', vehicle)"
                >
                    <i class="pi pi-times" />
                </button>
            </div>
            <span v-if="isAssigned" class="chat-btn-wrap">
                <Button
                    icon="pi pi-comments"
                    :severity="hasUnreadMessages ? 'info' : 'secondary'"
                    :class="{ 'chat-btn--unread': hasUnreadMessages }"
                    text
                    rounded
                    :title="unreadMessagesTitle"
                    :aria-label="unreadMessagesTitle"
                    @click="$emit('open-chat', vehicle)"
                />
                <span v-if="hasUnreadMessages" class="chat-count-badge" aria-hidden="true">{{ unreadMessagesLabel }}</span>
            </span>
            <Button
                v-if="isManual"
                icon="pi pi-pencil"
                :label="t('vehicles.editManual')"
                size="small"
                severity="secondary"
                outlined
                :title="t('vehicles.editManualTitle')"
                @click="$emit('edit', vehicle)"
            />
            <Button
                v-if="!isAssigned"
                :label="t('vehicles.assign')"
                size="small"
                class="btn-assign"
                @click="$emit('assign', vehicle)"
            />
            <Button
                v-if="!isAssigned"
                icon="pi pi-trash"
                :label="t('vehicles.deleteVehicle')"
                size="small"
                severity="danger"
                outlined
                :title="t('vehicles.deleteTitle')"
                @click="$emit('delete', vehicle)"
            />
        </div>

        <!-- Dealer: local status + action -->
        <div v-else class="cell cell-actions">
            <Tag v-if="vehicle.status && vehicle.status !== 'assigned'" :value="vehicle.status" class="local-tag" />
            <span class="chat-btn-wrap">
                <Button
                    icon="pi pi-comments"
                    :severity="hasUnreadMessages ? 'info' : 'secondary'"
                    :class="{ 'chat-btn--unread': hasUnreadMessages }"
                    text
                    rounded
                    :title="unreadMessagesTitle"
                    :aria-label="unreadMessagesTitle"
                    @click="$emit('open-chat', vehicle)"
                />
                <span v-if="hasUnreadMessages" class="chat-count-badge" aria-hidden="true">{{ unreadMessagesLabel }}</span>
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import VehicleImageGallery from './VehicleImageGallery.vue';
import VinCopyLabel from './VinCopyLabel.vue';
import {
    vehicleArrivedDate,
    vehicleAssignmentBadgeClass,
    vehicleAuction,
    vehicleBookingRef,
    vehicleContainerRef,
    vehicleDestination,
    vehicleEnteredBy,
    vehicleIsAssigned,
    vehicleFuelClass,
    vehicleFuelType,
    vehicleKeysInfo,
    vehicleLot,
    vehicleOrigin,
    vehiclePurchaseDate,
    vehicleSourceLabel,
    vehicleSourcePillClass,
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

defineEmits(['assign', 'update-status', 'open-chat', 'open-detail', 'edit', 'unassign', 'track-container', 'delete']);

const { t } = useI18n();

const isManual = computed(() => props.vehicle?.source === 'manual');
const isAssigned = computed(() => vehicleIsAssigned(props.vehicle));
const hasUnreadMessages = computed(() => unreadMessagesCount.value > 0);

const unreadMessagesCount = computed(() => Number(props.vehicle?.unread_messages_count || 0));

const unreadMessagesLabel = computed(() => {
    const count = unreadMessagesCount.value;

    return count > 99 ? '99+' : String(count);
});

const unreadMessagesTitle = computed(() => {
    if (! hasUnreadMessages.value) {
        return props.mode === 'admin' ? t('vehicles.chatAdmin') : t('vehicles.chatDealer');
    }

    return t('vehicles.unreadMessagesTitle', { count: unreadMessagesCount.value });
});
const sourceLabel = computed(() => vehicleSourceLabel(props.vehicle, t));
const sourcePillClass = computed(() => vehicleSourcePillClass(props.vehicle));

const title = computed(() => vehicleTitle(props.vehicle));
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
const keysInfo = computed(() => vehicleKeysInfo(props.vehicle, t));
const titleStatus = computed(() => vehicleTitleStatus(props.vehicle, t));
const purchaseDate = computed(() => vehiclePurchaseDate(props.vehicle));
const arrivedDate = computed(() => vehicleArrivedDate(props.vehicle));
const enteredBy = computed(() => vehicleEnteredBy(props.vehicle, t));
const dealerUserName = computed(() => props.vehicle.active_assignment?.dealer?.user?.name?.trim() ?? '');
const dealerCompanyName = computed(() => props.vehicle.active_assignment?.dealer?.company_name?.trim() ?? '');
const dealerDisplayName = computed(() => dealerUserName.value || dealerCompanyName.value || null);
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

.source-pill--nujoom {
    background: #fef3c7;
    color: #b45309;
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
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    gap: 0.5rem;
}

.ref-line__meta {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    min-width: 0;
    flex: 1;
}

.ref-container-num {
    min-width: 0;
    overflow-wrap: anywhere;
}

.track-btn {
    flex-shrink: 0;
    margin-inline-start: auto;
    margin-inline-end: 0;
}

/* Dealer: solid purple, prominent, icon-only. The PrimeVue button root itself
   carries these classes, so target it directly (no descendant .p-button). */
.track-btn--dealer.track-btn--ready:deep(.p-button),
.track-btn--dealer.track-btn--ready {
    width: 2.35rem;
    height: 2.35rem;
    color: #fff !important;
    border: 1.5px solid #7c3aed !important;
    background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%) !important;
    box-shadow: 0 2px 10px rgb(139 92 246 / 0.45);
}

.track-btn--dealer.track-btn--ready :deep(.p-button-icon) {
    color: #fff !important;
    font-size: 1.05rem;
}

.track-btn--dealer.track-btn--ready:hover {
    background: linear-gradient(135deg, #9333ea 0%, #6d28d9 100%) !important;
    border-color: #6d28d9 !important;
    transform: translateY(-1px);
}

.track-btn--dealer.track-btn--pulse {
    animation: track-btn-pulse 1.8s ease-in-out infinite;
}

.track-btn--dealer.track-btn--pulse :deep(.p-button-icon) {
    animation: track-icon-bounce 1.8s ease-in-out infinite;
}

@keyframes track-btn-pulse {
    0%,
    100% {
        box-shadow: 0 2px 10px rgb(139 92 246 / 0.45), 0 0 0 0 rgb(168 85 247 / 0.6);
    }

    50% {
        box-shadow: 0 2px 10px rgb(139 92 246 / 0.45), 0 0 0 9px rgb(168 85 247 / 0);
    }
}

@keyframes track-icon-bounce {
    0%,
    100% {
        transform: translateY(0) scale(1);
    }

    35% {
        transform: translateY(-3px) scale(1.12);
    }

    70% {
        transform: translateY(0) scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .track-btn--dealer.track-btn--pulse :deep(.p-button),
    .track-btn--dealer.track-btn--pulse :deep(.p-button-icon) {
        animation: none;
    }
}

.track-btn--ready :deep(.p-button-icon) {
    color: #8b5cf6;
}

.track-btn--ready:hover :deep(.p-button-icon) {
    color: #7c3aed;
}

.track-btn:focus-visible :deep(.p-button) {
    outline: 2px solid #a855f7;
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
    flex-wrap: wrap;
    gap: 0.25rem;
    max-width: 100%;
    padding-block: 0.15rem;
    padding-inline: 0.55rem 0.35rem;
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
    max-width: 11rem;
}

.dealer-tag__sub {
    font-size: 0.66rem;
    color: var(--vs-text-muted);
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

.chat-btn-wrap {
    position: relative;
    display: inline-flex;
}

.vehicle-thumb-wrap {
    position: relative;
    flex-shrink: 0;
}

.message-unread-badge {
    position: absolute;
    top: 4px;
    inset-inline-start: 4px;
    z-index: 3;
    min-width: 20px;
    height: 20px;
    padding: 0 5px;
    border-radius: 999px;
    background: #ef4444;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    line-height: 20px;
    text-align: center;
    pointer-events: none;
    box-shadow: 0 1px 4px rgb(0 0 0 / 0.35);
}

.message-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 0.4rem;
    border-radius: 999px;
    background: #ef4444;
    color: #fff;
    font-size: 0.68rem;
    font-weight: 700;
    line-height: 1;
}

.chat-count-badge {
    position: absolute;
    top: -2px;
    inset-inline-end: -2px;
    z-index: 2;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    border-radius: 999px;
    background: #ef4444;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    line-height: 18px;
    text-align: center;
    pointer-events: none;
    border: 2px solid var(--surface-card, #fff);
    box-shadow: 0 1px 3px rgb(0 0 0 / 0.25);
}

[data-theme='dark'] .chat-count-badge {
    border-color: var(--surface-card, #18181b);
}

.chat-btn-wrap :deep(.chat-btn--unread) {
    color: #0d9488 !important;
    background: rgb(20 184 166 / 0.14) !important;
}

.chat-btn-wrap :deep(.chat-btn--unread .p-button-icon) {
    color: #14b8a6;
    font-weight: 700;
}

.chat-btn-wrap :deep(.chat-btn--unread:hover) {
    color: #0f766e !important;
    background: rgb(20 184 166 / 0.22) !important;
}

[data-theme='dark'] .chat-btn-wrap :deep(.chat-btn--unread) {
    color: #5eead4 !important;
    background: rgb(20 184 166 / 0.2) !important;
}

[data-theme='dark'] .chat-btn-wrap :deep(.chat-btn--unread .p-button-icon) {
    color: #5eead4;
}

@media (max-width: 1100px) {
    .vehicle-row {
        grid-template-columns: 1fr;
        gap: 0.75rem;
        padding: 1rem;
    }

    .ref-line--container {
        justify-content: space-between;
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
