<template>
    <Dialog
        v-model:visible="dialogVisible"
        modal
        appendTo="body"
        :draggable="false"
        :closable="false"
        :dismissableMask="!loading"
        :closeOnEscape="!loading"
        :blockScroll="true"
        :style="dialogPanelStyle"
        :pt="{
            mask: { class: 'container-tracking-mask' },
            root: {
                class: 'container-tracking-dialog',
                style: dialogPanelStyle,
            },
            content: { class: 'container-tracking-content' },
        }"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="dialogTitleId"
        :aria-busy="loading"
        @hide="onHide"
        @show="onShow"
    >
        <template #header>
            <div ref="headerRef" class="tracking-header">
                <div class="tracking-header-top">
                    <div>
                        <div class="tracking-kicker">{{ t('containers.tracking.title') }}</div>
                        <h2 :id="dialogTitleId" class="tracking-title">
                            <template v-if="!loading">{{ headerContainer }}</template>
                            <Skeleton v-else width="12rem" height="1.35rem" />
                        </h2>
                    </div>
                    <Button
                        ref="closeBtnRef"
                        icon="pi pi-times"
                        text
                        rounded
                        severity="secondary"
                        :aria-label="t('containers.tracking.close')"
                        :disabled="loading"
                        @click="close"
                    />
                </div>
                <div v-if="loading" class="tracking-meta tracking-meta--skeleton">
                    <Skeleton width="5rem" height="1.5rem" borderRadius="6px" />
                    <Skeleton width="4.5rem" height="1.5rem" borderRadius="6px" />
                    <Skeleton width="6rem" height="1.5rem" borderRadius="999px" />
                </div>
                <div v-else-if="tracking" class="tracking-meta">
                    <p
                        v-if="hasPartialRoute"
                        class="tracking-route-line"
                        :title="`${originLabel} — ${destinationLabel}`"
                    >
                        <span class="tracking-route-origin">{{ originLabel }}</span>
                        <i class="pi pi-arrow-right tracking-route-arrow" aria-hidden="true" />
                        <span class="tracking-route-dest">{{ destinationLabel }}</span>
                    </p>
                    <div class="tracking-meta-chips">
                        <span v-if="tracking.booking_number" class="meta-chip">
                            <span class="meta-label">BKG</span>
                            {{ tracking.booking_number }}
                        </span>
                        <span v-if="tracking.carrier" class="meta-chip meta-chip--carrier">
                            {{ tracking.carrier }}
                        </span>
                        <span class="status-badge" :class="statusBadgeClass">
                            {{ tracking.status_label }}
                        </span>
                    </div>
                </div>
                <p v-if="tracking?.cache_note && tracking.cached" class="cache-note">
                    <i class="pi pi-database" aria-hidden="true" />
                    {{ tracking.cache_note }}
                </p>
                <p v-if="tracking?.disclaimer" class="disclaimer-note" role="note">
                    <i class="pi pi-info-circle" aria-hidden="true" />
                    {{ tracking.disclaimer }}
                </p>
                <p v-if="lowGeocodeConfidence" class="disclaimer-note disclaimer-note--estimate" role="note">
                    <i class="pi pi-map-marker" aria-hidden="true" />
                    {{ t('containers.tracking.estimatedLocation') }}
                </p>
            </div>
        </template>

        <div v-if="loading" class="tracking-skeleton" aria-live="polite" aria-busy="true">
            <div class="tracking-skeleton-map">
                <Skeleton width="100%" height="100%" borderRadius="0" />
            </div>
            <aside class="tracking-skeleton-sidebar">
                <Skeleton width="100%" height="4.5rem" borderRadius="10px" />
                <Skeleton width="100%" height="6.5rem" borderRadius="10px" class="mt-md" />
                <Skeleton width="40%" height="0.85rem" class="mt-lg" />
                <Skeleton width="100%" height="3.5rem" class="mt-sm" />
                <Skeleton width="100%" height="3.5rem" class="mt-sm" />
                <Skeleton width="100%" height="3.5rem" class="mt-sm" />
            </aside>
            <span class="sr-only">{{ t('containers.tracking.loading') }}</span>
        </div>

        <div v-else-if="error" class="tracking-error" role="alert">
            <div class="tracking-error-card">
                <i class="pi pi-exclamation-circle" aria-hidden="true" />
                <p class="tracking-error-title">{{ t('containers.tracking.loadFailed') }}</p>
                <p class="tracking-error-msg">{{ error }}</p>
                <Button :label="t('containers.tracking.retry')" size="small" icon="pi pi-refresh" @click="load" />
            </div>
        </div>

        <div v-else-if="tracking" ref="bodyRef" class="tracking-body">
            <div class="tracking-map-wrap">
                <l-map
                    v-if="mapReady"
                    :zoom="mapZoom"
                    :center="mapCenter"
                    :use-global-leaflet="false"
                    class="tracking-map"
                    @ready="onMapReady"
                >
                    <l-tile-layer
                        :url="mapTileUrl"
                        :attribution="mapTileAttribution"
                        @loading="mapTilesLoading = true"
                        @load="mapTilesLoading = false"
                    />
                    <l-polyline
                        v-if="displayRouteLatLngs.length"
                        :lat-lngs="displayRouteLatLngs"
                        color="#14b8a6"
                        :weight="4"
                        :opacity="0.9"
                    />
                    <l-marker
                        v-if="originLatLng"
                        :lat-lng="originLatLng"
                        :icon="markerIcons.origin"
                    />
                    <l-marker
                        v-for="(wp, index) in waypointLatLngs"
                        :key="`wp-${index}`"
                        :lat-lng="wp"
                        :icon="markerIcons.waypoint"
                    />
                    <l-marker
                        v-if="destinationLatLng"
                        :lat-lng="destinationLatLng"
                        :icon="markerIcons.destination"
                    />
                    <l-marker
                        v-if="currentLatLng"
                        :lat-lng="currentLatLng"
                        :icon="markerIcons.current"
                        :z-index-offset="1000"
                    />
                </l-map>
                <div v-if="mapReady" class="map-legend" :dir="direction">
                    <div
                        v-for="item in legendItems"
                        :key="item.key"
                        class="map-legend-item"
                    >
                        <span class="map-legend-swatch" :class="item.class" aria-hidden="true">
                            <i v-if="item.icon" :class="item.icon" />
                        </span>
                        <span class="map-legend-label">{{ item.label }}</span>
                    </div>
                </div>
                <div
                    v-if="mapReady && mapTilesLoading"
                    class="map-tiles-loading"
                    aria-live="polite"
                    aria-busy="true"
                >
                    <ProgressSpinner style="width: 28px; height: 28px" strokeWidth="4" />
                    <span>{{ t('containers.tracking.loadMap') }}</span>
                </div>
                <div v-else-if="!mapReady" class="map-empty">
                    <div class="map-empty-illus" aria-hidden="true">
                        <i class="pi pi-map" />
                    </div>
                    <p class="map-empty-title">{{ t('containers.tracking.mapEmptyTitle') }}</p>
                    <p v-if="hasPartialRoute" class="map-empty-hint">
                        {{ t('containers.tracking.mapEmptyHintPartial') }}
                    </p>
                    <p v-else class="map-empty-hint">
                        {{ t('containers.tracking.mapEmptyHintDefault') }}
                    </p>
                </div>
                <ul v-if="mapReady" class="sr-only map-a11y-summary">
                    <li v-if="originLabel !== '—'">{{ t('containers.tracking.a11yOrigin', { label: originLabel }) }}</li>
                    <li v-for="(wp, i) in waypointLabels" :key="`a11y-wp-${i}`">{{ t('containers.tracking.a11yWaypoint', { label: wp }) }}</li>
                    <li v-if="destinationLabel !== '—'">{{ t('containers.tracking.a11yDestination', { label: destinationLabel }) }}</li>
                </ul>
            </div>

            <aside class="tracking-sidebar" :dir="direction">
                <div
                    v-if="tracking.eta"
                    class="eta-card"
                    :class="etaUrgencyClass"
                >
                    <div class="eta-label">{{ t('containers.tracking.expectedArrival') }}</div>
                    <div class="eta-value">{{ formatDate(tracking.eta) }}</div>
                    <div v-if="etaDaysLabel" class="eta-countdown">{{ etaDaysLabel }}</div>
                </div>

                <div class="route-card">
                    <div class="route-end">
                        <span class="route-dot route-dot--origin" aria-hidden="true" />
                        <div>
                            <div class="route-end-label">{{ t('containers.tracking.origin') }}</div>
                            <div class="route-end-name" :title="originLabel">{{ originLabel }}</div>
                        </div>
                    </div>
                    <div class="route-arrow" aria-hidden="true">
                        <i class="pi pi-arrow-down" />
                    </div>
                    <div class="route-end">
                        <span class="route-dot route-dot--dest" aria-hidden="true" />
                        <div>
                            <div class="route-end-label">{{ t('containers.tracking.destination') }}</div>
                            <div class="route-end-name" :title="destinationLabel">{{ destinationLabel }}</div>
                        </div>
                    </div>
                </div>

                <section class="events-section">
                    <h3 class="events-title">{{ t('containers.tracking.shipmentEvents') }}</h3>
                    <div v-if="tracking.events?.length" class="events-timeline">
                        <div
                            v-for="(event, index) in tracking.events"
                            :key="`${event.type}-${index}`"
                            class="event-item"
                            :style="{ animationDelay: `${Math.min(index, 12) * 45}ms` }"
                        >
                            <div class="event-marker" :class="`event-marker--${event.type || 'event'}`" />
                            <div class="event-content">
                                <div class="event-title">{{ event.title }}</div>
                                <div
                                    v-if="event.location"
                                    class="event-location"
                                    :title="event.location"
                                >
                                    {{ truncateText(event.location, 48) }}
                                </div>
                                <div v-if="event.date" class="event-date">
                                    <span
                                        v-if="formatRelativeDate(event.date)"
                                        class="event-date-relative"
                                    >
                                        {{ formatRelativeDate(event.date) }}
                                    </span>
                                    <span
                                        class="event-date-abs"
                                        :class="{ 'event-date-abs--solo': !formatRelativeDate(event.date) }"
                                    >
                                        {{ formatDate(event.date) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="events-empty">{{ t('containers.tracking.eventsEmpty') }}</p>
                </section>

                <p v-if="tracking.source" class="source-tag">
                    {{ t('containers.tracking.source') }} {{ sourceLabel }}
                </p>
            </aside>
        </div>
    </Dialog>
</template>

<script setup>
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Skeleton from 'primevue/skeleton';
import ProgressSpinner from 'primevue/progressspinner';
import { LMap, LTileLayer, LPolyline, LMarker } from '@vue-leaflet/vue-leaflet';
import 'leaflet/dist/leaflet.css';
import api from '../api/client';
import { containerRefs, formatContainerDate } from '../utils/containerMeta';
import { createTrackingMarkerIcon } from '../utils/containerMapIcons';
import { useLocale } from '../composables/useLocale';

const { t } = useI18n();
const { direction } = useLocale();

const markerIcons = {
    origin: createTrackingMarkerIcon('origin'),
    waypoint: createTrackingMarkerIcon('waypoint'),
    destination: createTrackingMarkerIcon('destination'),
    current: createTrackingMarkerIcon('current'),
};

const legendItems = computed(() => [
    { key: 'origin', label: t('containers.tracking.origin'), class: 'legend-swatch--origin', icon: 'pi pi-arrow-up' },
    { key: 'waypoint', label: t('containers.tracking.waypoint'), class: 'legend-swatch--waypoint', icon: 'pi pi-send' },
    { key: 'destination', label: t('containers.tracking.destination'), class: 'legend-swatch--destination', icon: 'pi pi-circle' },
    { key: 'current', label: t('containers.tracking.currentLocation'), class: 'legend-swatch--current legend-swatch--current-car' },
]);

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    container: {
        type: Object,
        default: null,
    },
    apiRole: {
        type: String,
        default: 'admin',
        validator: (v) => ['admin', 'dealer'].includes(v),
    },
});

const emit = defineEmits(['update:visible']);

const dialogVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});

const loading = ref(false);
const error = ref(null);
const tracking = ref(null);
const mapReady = ref(false);
const mapTilesLoading = ref(false);
const leafletMap = ref(null);
const closeBtnRef = ref(null);
const headerRef = ref(null);
const bodyRef = ref(null);

const dialogTitleId = 'container-tracking-title';

/** Inline + pt styles — portaled Dialog ignores scoped CSS width rules. */
const dialogPanelStyle = {
    width: 'calc(100vw - 20px)',
    maxWidth: 'none',
    minHeight: 'min(85vh, calc(100vh - 20px))',
    margin: '10px',
};

const refs = computed(() => (props.container ? containerRefs(props.container) : { container: null }));

const headerContainer = computed(
    () => tracking.value?.container_number || refs.value.container || '—',
);

const statusBadgeClass = computed(() => {
    const status = tracking.value?.status || 'in_transit';

    return {
        'status-badge--transit': status === 'in_transit' || status === 'loading',
        'status-badge--arrived': status === 'arrived' || status === 'delivered',
    };
});

const routeLatLngs = computed(() => {
    const route = tracking.value?.route;

    if (!Array.isArray(route) || route.length < 2) {
        return [];
    }

    return route
        .filter((p) => Array.isArray(p) && p.length >= 2)
        .map((p) => [Number(p[0]), Number(p[1])]);
});

const originLatLng = computed(() => pointFromLocation(tracking.value?.origin));
const destinationLatLng = computed(() => pointFromLocation(tracking.value?.destination));

const currentLatLng = computed(() => pointFromLocation(tracking.value?.current_position));

const mapTileUrl = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';

const mapTileAttribution = '&copy; OpenStreetMap &copy; CARTO';

const waypointLatLngs = computed(() => {
    const wps = tracking.value?.waypoints;

    if (!Array.isArray(wps)) {
        return [];
    }

    return wps.map((wp) => pointFromLocation(wp)).filter(Boolean);
});

const straightRouteLatLngs = computed(() => {
    const points = [
        originLatLng.value,
        ...waypointLatLngs.value,
        destinationLatLng.value,
    ].filter(Boolean);

    return points.length >= 2 ? points : [];
});

const waypointLabels = computed(() => {
    const wps = tracking.value?.waypoints;

    if (!Array.isArray(wps)) {
        return [];
    }

    return wps
        .map((wp) => wp?.label || wp?.name)
        .filter(Boolean);
});

const mapCenter = computed(() => {
    if (displayRouteLatLngs.value.length) {
        const mid = displayRouteLatLngs.value[Math.floor(displayRouteLatLngs.value.length / 2)];

        return mid;
    }

    return originLatLng.value || destinationLatLng.value || [25, 45];
});

const mapZoom = computed(() => (displayRouteLatLngs.value.length ? 3 : 2));

const originLabel = computed(
    () => tracking.value?.origin?.label
        || tracking.value?.origin?.name
        || props.container?.loading_point
        || '—',
);

const destinationLabel = computed(
    () => tracking.value?.destination?.label
        || tracking.value?.destination?.name
        || props.container?.destination
        || '—',
);

const hasPartialRoute = computed(
    () => originLabel.value !== '—' || destinationLabel.value !== '—',
);

const lowGeocodeConfidence = computed(() => {
    const origin = tracking.value?.origin?.geocode_confidence;
    const dest = tracking.value?.destination?.geocode_confidence;

    return origin === 'low' || dest === 'low';
});

const shouldRenderStraightRoute = computed(() => (
    Boolean(tracking.value?.route_is_estimated)
    && straightRouteLatLngs.value.length >= 2
));

const displayRouteLatLngs = computed(() => (
    shouldRenderStraightRoute.value ? straightRouteLatLngs.value : routeLatLngs.value
));

const sourceLabel = computed(() => {
    const source = tracking.value?.source;

    if (source === 'vinstack') {
        return t('containers.tracking.sourceVinstack');
    }

    if (source === 'derived') {
        return t('containers.tracking.sourceDerived');
    }

    return source || '—';
});

const etaDaysLeft = computed(() => {
    const raw = tracking.value?.eta;

    if (!raw) {
        return null;
    }

    const date = parseDateOnly(raw);

    if (!date) {
        return null;
    }

    const today = startOfDay(new Date());
    const eta = startOfDay(date);

    return Math.round((eta.getTime() - today.getTime()) / 86400000);
});

const etaDaysLabel = computed(() => {
    const days = etaDaysLeft.value;

    if (days == null) {
        return null;
    }

    if (days < 0) {
        return t('containers.tracking.etaOverdue', { count: Math.abs(days) });
    }

    if (days === 0) {
        return t('containers.tracking.etaToday');
    }

    if (days === 1) {
        return t('containers.tracking.etaOneDay');
    }

    if (days === 2) {
        return t('containers.tracking.etaTwoDays');
    }

    if (days <= 10) {
        return t('containers.tracking.etaDaysLeft', { count: days });
    }

    return t('containers.tracking.etaDaysLeft', { count: days });
});

const etaUrgencyClass = computed(() => {
    const days = etaDaysLeft.value;

    if (days == null) {
        return '';
    }

    if (days < 0) {
        return 'eta-card--overdue';
    }

    if (days <= 3) {
        return 'eta-card--urgent';
    }

    if (days <= 7) {
        return 'eta-card--soon';
    }

    return 'eta-card--calm';
});

watch(
    () => [props.visible, props.container?.container_number],
    ([visible]) => {
        if (visible && props.container?.container_number) {
            load();
        }

        if (!visible) {
            resetState();
        }
    },
);

watch([displayRouteLatLngs, currentLatLng], () => {
    if (leafletMap.value && mapReady.value) {
        fitMapBounds(leafletMap.value);
    }
});

function resetState() {
    tracking.value = null;
    error.value = null;
    mapReady.value = false;
    mapTilesLoading.value = false;
    leafletMap.value = null;
}

async function load() {
    const number = props.container?.container_number?.trim();

    if (!number) {
        error.value = t('containers.tracking.containerMissing');

        return;
    }

    loading.value = true;
    error.value = null;
    mapReady.value = false;
    mapTilesLoading.value = false;
    leafletMap.value = null;

    try {
        const { data } = await api.get(
            `/${props.apiRole}/containers/${encodeURIComponent(number)}/tracking`,
        );

        tracking.value = data;
        mapReady.value = hasMapData(data);
    } catch (e) {
        tracking.value = null;
        const serverMsg = e.response?.data?.message;

        error.value = serverMsg
            || t('containers.tracking.connectionFailed');
    } finally {
        loading.value = false;
    }
}

function hasMapData(data) {
    const route = data?.route;

    if (Array.isArray(route) && route.length >= 2) {
        return true;
    }

    return Boolean(pointFromLocation(data?.origin) && pointFromLocation(data?.destination));
}

function pointFromLocation(loc) {
    if (!loc || loc.lat == null || loc.lng == null) {
        return null;
    }

    return [Number(loc.lat), Number(loc.lng)];
}

function formatDate(value) {
    return formatContainerDate(value) || value || '—';
}

function parseDateOnly(value) {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return date;
}

function startOfDay(date) {
    const d = new Date(date);

    d.setHours(0, 0, 0, 0);

    return d;
}

function formatRelativeDate(value) {
    const date = parseDateOnly(value);

    if (!date) {
        return '';
    }

    const today = startOfDay(new Date());
    const target = startOfDay(date);
    const diff = Math.round((target.getTime() - today.getTime()) / 86400000);

    if (diff === 0) {
        return t('containers.tracking.relativeToday');
    }

    if (diff === 1) {
        return t('containers.tracking.relativeTomorrow');
    }

    if (diff === -1) {
        return t('containers.tracking.relativeYesterday');
    }

    if (diff > 1 && diff <= 14) {
        return t('containers.tracking.relativeInDays', { count: diff });
    }

    if (diff < -1 && diff >= -14) {
        return t('containers.tracking.relativeDaysAgo', { count: Math.abs(diff) });
    }

    return '';
}

function truncateText(text, max) {
    if (!text || text.length <= max) {
        return text;
    }

    return `${text.slice(0, max - 1)}…`;
}

function onMapReady(map) {
    leafletMap.value = map?.leafletObject ?? null;
    mapTilesLoading.value = true;
    fitMapBounds(leafletMap.value);
}

function fitMapBounds(map) {
    const points = [
        ...displayRouteLatLngs.value,
        ...(originLatLng.value ? [originLatLng.value] : []),
        ...(destinationLatLng.value ? [destinationLatLng.value] : []),
        ...(currentLatLng.value ? [currentLatLng.value] : []),
        ...waypointLatLngs.value,
    ];

    if (points.length >= 2 && map?.fitBounds) {
        map.fitBounds(points, { padding: [32, 32], maxZoom: 8 });
    }
}

function close() {
    if (loading.value) {
        return;
    }

    dialogVisible.value = false;
}

function onHide() {
    resetState();
    removeFocusTrap();
}

function onShow() {
    nextTick(() => focusCloseButton());
    attachFocusTrap();
}

function focusCloseButton() {
    const btn = closeBtnRef.value?.$el ?? closeBtnRef.value;

    btn?.focus?.();
}

function getFocusableElements(root) {
    if (!root) {
        return [];
    }

    return [...root.querySelectorAll(
        'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])',
    )].filter((el) => el.offsetParent !== null || el === document.activeElement);
}

function onDialogKeydown(event) {
    if (!dialogVisible.value || loading.value) {
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        close();

        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const panel = document.querySelector('.p-dialog.container-tracking-dialog');

    if (!panel) {
        return;
    }

    const focusables = getFocusableElements(panel);

    if (focusables.length < 2) {
        return;
    }

    const first = focusables[0];
    const last = focusables[focusables.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

function attachFocusTrap() {
    document.addEventListener('keydown', onDialogKeydown);
}

function removeFocusTrap() {
    document.removeEventListener('keydown', onDialogKeydown);
}

onUnmounted(removeFocusTrap);
</script>

<style scoped>
/* Layout inside dialog — width/size live in unscoped block (portal to body). */

:deep(.p-dialog.container-tracking-dialog .p-dialog-header) {
    flex-shrink: 0;
    padding: 0.9rem 1.25rem;
    border-bottom: 1px solid var(--vs-border);
    background: var(--admin-surface);
}

:deep(.p-dialog.container-tracking-dialog .p-dialog-content) {
    flex: 1 1 auto;
    min-height: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--admin-surface);
}

.tracking-header {
    width: 100%;
}

.tracking-header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.tracking-kicker {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--vs-text-secondary);
    margin-bottom: 0.2rem;
}

.tracking-title {
    margin: 0;
    font-size: 1.25rem;
    font-family: ui-monospace, monospace;
    color: var(--vs-text);
}

.tracking-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem 1rem;
    margin-top: 0.65rem;
}

.tracking-route-line {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin: 0;
    flex: 1 1 auto;
    min-width: 0;
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--vs-text-secondary);
}

.tracking-route-origin,
.tracking-route-dest {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: min(14rem, 38vw);
}

.tracking-route-arrow {
    flex-shrink: 0;
    font-size: 0.72rem;
    color: var(--vs-text-subtle);
}

[dir='rtl'] .tracking-route-arrow {
    transform: scaleX(-1);
}

.tracking-meta-chips {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
    flex-shrink: 0;
    margin-inline-start: auto;
}

.tracking-meta--skeleton {
    margin-top: 0.75rem;
}

.meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    background: var(--vs-surface-elevated);
    font-size: 0.78rem;
    color: var(--vs-text);
}

.meta-label {
    font-weight: 700;
    color: var(--vs-text-subtle);
    font-size: 0.65rem;
}

.meta-chip--carrier {
    background: #e0f2fe;
    color: #0369a1;
    font-weight: 600;
}

.status-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
}

.status-badge--transit {
    background: var(--status-transit-bg);
    color: var(--status-transit-fg);
}

.status-badge--arrived {
    background: var(--status-new-bg);
    color: var(--status-new-fg);
}

.cache-note {
    margin: 0.5rem 0 0;
    font-size: 0.75rem;
    color: var(--vs-text-muted);
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.disclaimer-note {
    margin: 0.5rem 0 0;
    font-size: 0.78rem;
    color: var(--vs-text-secondary);
    background: var(--vs-surface-elevated);
    border: 1px solid var(--vs-border);
    border-radius: 8px;
    padding: 0.5rem 0.65rem;
    display: flex;
    align-items: flex-start;
    gap: 0.45rem;
    line-height: 1.45;
}

.disclaimer-note .pi {
    color: var(--vs-text-muted);
    margin-top: 0.1rem;
    flex-shrink: 0;
}

.disclaimer-note--estimate {
    background: #fffbeb;
    border-color: #fde68a;
    color: #92400e;
}

.disclaimer-note--estimate .pi {
    color: #b45309;
}

.tracking-skeleton {
    display: grid;
    grid-template-columns: 1fr minmax(260px, 320px);
    flex: 1 1 auto;
    min-height: 0;
    height: 100%;
}

.tracking-skeleton-map {
    min-height: 0;
    height: 100%;
    background: var(--vs-zinc-200);
}

.tracking-skeleton-sidebar {
    border-inline-start: 1px solid var(--vs-border);
    padding: 1rem 1.1rem;
    background: var(--vs-surface-elevated);
}

.mt-sm {
    margin-top: 0.5rem;
}

.mt-md {
    margin-top: 0.75rem;
}

.mt-lg {
    margin-top: 1.25rem;
}

.tracking-error {
    display: flex;
    flex: 1;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1.5rem;
}

.tracking-error-card {
    text-align: center;
    max-width: 22rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: var(--vs-text-muted);
}

.tracking-error-card > .pi {
    font-size: 2rem;
    color: #f87171;
}

.tracking-error-title {
    margin: 0.25rem 0 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--vs-text);
}

.tracking-error-msg {
    margin: 0 0 0.75rem;
    font-size: 0.86rem;
    line-height: 1.5;
    color: var(--vs-text-muted);
}

.tracking-body {
    display: grid;
    grid-template-columns: 1fr minmax(260px, 320px);
    grid-template-areas: 'map sidebar';
    flex: 1 1 auto;
    min-height: 0;
    height: 100%;
    animation: trackingBodyIn 0.28s ease;
    /* خريطة يسار / لوحة يمين — مثل Vinstack حتى في واجهة RTL */
    direction: ltr;
}

@keyframes trackingBodyIn {
    from {
        opacity: 0;
        transform: translateY(6px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.tracking-map-wrap {
    grid-area: map;
    min-height: 0;
    height: 100%;
    background: var(--vs-zinc-200);
    position: relative;
    display: flex;
    flex-direction: column;
}

.tracking-map {
    width: 100%;
    flex: 1 1 auto;
    min-height: 0;
    z-index: 1;
}

.map-legend {
    flex-shrink: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0.65rem 1.25rem;
    padding: 0.55rem 0.85rem;
    background: var(--vs-surface);
    border-top: 1px solid var(--vs-border);
    z-index: 2;
}

.map-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.72rem;
    color: var(--vs-text-secondary);
}

.map-legend-swatch {
    width: 1.35rem;
    height: 1.35rem;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.62rem;
    color: #fff;
    box-shadow: 0 1px 3px rgb(0 0 0 / 0.2);
}

.legend-swatch--origin {
    background: linear-gradient(145deg, #7c3aed, #a78bfa);
}

.legend-swatch--waypoint {
    background: linear-gradient(145deg, #ea580c, #fb923c);
}

.legend-swatch--destination {
    background: linear-gradient(145deg, #52525b, #71717a);
}

.legend-swatch--current {
    background: linear-gradient(145deg, #0d9488, #14b8a6);
}

.legend-swatch--current-car::before {
    content: '';
    display: block;
    width: 0.72rem;
    height: 0.72rem;
    background-color: #fff;
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M18.92 6.01C18.72 5.42 18.15 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v7c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-7l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z'/%3E%3C/svg%3E");
    mask-size: contain;
    mask-repeat: no-repeat;
    mask-position: center;
}

.map-legend-label {
    font-weight: 600;
}

.map-tiles-loading {
    position: absolute;
    inset: 0;
    z-index: 5;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: rgb(228 228 231 / 0.55);
    font-size: 0.8rem;
    color: var(--vs-text-secondary);
    pointer-events: none;
}

.map-empty {
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    padding: 1.5rem;
    text-align: center;
    color: var(--vs-text-muted);
}

.map-empty-illus {
    width: 4.5rem;
    height: 4.5rem;
    border-radius: 50%;
    background: linear-gradient(145deg, var(--vs-surface-elevated), var(--vs-zinc-200));
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.35rem;
}

.map-empty-illus .pi {
    font-size: 1.75rem;
    color: var(--vs-text-subtle);
}

.map-empty-title {
    margin: 0;
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--vs-text-secondary);
}

.map-empty-hint {
    margin: 0;
    font-size: 0.8rem;
    max-width: 26rem;
    line-height: 1.5;
    color: var(--vs-text-muted);
}

.tracking-sidebar {
    grid-area: sidebar;
    border-inline-start: 1px solid var(--vs-border);
    padding: 1rem 1.1rem;
    overflow-y: auto;
    overflow-x: hidden;
    background: var(--vs-surface-elevated);
    min-height: 0;
}

.eta-card {
    background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
    color: #fff;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: box-shadow 0.2s ease;
}

.eta-card--calm {
    box-shadow: 0 4px 14px rgb(20 184 166 / 0.25);
}

.eta-card--soon {
    background: linear-gradient(135deg, #0d9488 0%, #2dd4bf 100%);
}

.eta-card--urgent {
    background: linear-gradient(135deg, #c2410c 0%, #f97316 100%);
    box-shadow: 0 4px 16px rgb(249 115 22 / 0.35);
}

.eta-card--overdue {
    background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%);
    box-shadow: 0 4px 16px rgb(220 38 38 / 0.3);
}

.eta-label {
    font-size: 0.72rem;
    opacity: 0.9;
    margin-bottom: 0.25rem;
}

.eta-value {
    font-size: 1.1rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.eta-countdown {
    margin-top: 0.45rem;
    font-size: 0.8rem;
    font-weight: 600;
    opacity: 0.95;
}

.route-card {
    background: var(--vs-surface);
    border: 1px solid var(--vs-border);
    border-radius: 10px;
    padding: 0.85rem;
    margin-bottom: 1rem;
}

.route-end {
    display: flex;
    gap: 0.6rem;
    align-items: flex-start;
}

.route-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-top: 0.35rem;
    flex-shrink: 0;
}

.route-dot--origin {
    background: #22c55e;
}

.route-dot--dest {
    background: #ef4444;
}

.route-end-label {
    font-size: 0.68rem;
    color: var(--vs-text-subtle);
    text-transform: uppercase;
    font-weight: 600;
}

.route-end-name {
    font-size: 0.88rem;
    color: var(--vs-text);
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
}

.route-arrow {
    text-align: center;
    color: var(--vs-text-subtle);
    padding: 0.35rem 0;
    padding-inline-start: 0.2rem;
}

.events-title {
    margin: 0 0 0.75rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--vs-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.events-timeline {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.event-item {
    display: flex;
    gap: 0.65rem;
    padding-bottom: 1rem;
    position: relative;
    animation: eventFadeIn 0.4s ease backwards;
}

@keyframes eventFadeIn {
    from {
        opacity: 0;
        transform: translateX(8px);
    }

    to {
        opacity: 1;
        transform: translateX(0);
    }
}

[dir='rtl'] .event-item {
    animation-name: eventFadeInRtl;
}

@keyframes eventFadeInRtl {
    from {
        opacity: 0;
        transform: translateX(-8px);
    }

    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.event-item:not(:last-child)::before {
    content: '';
    position: absolute;
    inset-inline-start: 5px;
    top: 14px;
    bottom: 0;
    width: 2px;
    background: var(--vs-border);
}

.event-marker {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #14b8a6;
    flex-shrink: 0;
    margin-top: 3px;
    z-index: 1;
    box-shadow: 0 0 0 3px var(--vs-surface-elevated);
}

.event-marker--loaded {
    background: #22c55e;
}

.event-marker--eta {
    background: #ef4444;
}

.event-marker--released {
    background: #6366f1;
}

.event-title {
    font-size: 0.86rem;
    font-weight: 600;
    color: var(--vs-text);
}

.event-location {
    font-size: 0.76rem;
    color: var(--vs-text-muted);
    margin-top: 0.15rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: help;
}

.event-date {
    font-size: 0.76rem;
    color: var(--vs-text-muted);
    margin-top: 0.2rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: baseline;
}

.event-date-relative {
    font-weight: 600;
    color: #0f766e;
}

.event-date-abs {
    font-variant-numeric: tabular-nums;
    color: var(--vs-text-subtle);
}

.event-date-abs:not(.event-date-abs--solo)::before {
    content: '·';
    margin-inline-end: 0.35rem;
}

.events-empty {
    font-size: 0.82rem;
    color: var(--vs-text-subtle);
    margin: 0;
}

.source-tag {
    margin-top: 1rem;
    font-size: 0.68rem;
    color: var(--vs-text-subtle);
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

@media (max-width: 900px) {
    .tracking-meta {
        flex-direction: column;
        align-items: stretch;
    }

    .tracking-route-origin,
    .tracking-route-dest {
        max-width: none;
    }

    .tracking-meta-chips {
        margin-inline-start: 0;
    }

    .tracking-skeleton,
    .tracking-body {
        grid-template-columns: 1fr;
        grid-template-areas:
            'map'
            'sidebar';
        min-height: 0;
        height: auto;
    }

    .tracking-map-wrap {
        min-height: min(48vh, 360px);
    }

    .tracking-map,
    .map-empty {
        min-height: min(48vh, 360px);
    }

    .tracking-skeleton-map {
        min-height: min(48vh, 360px);
    }

    .tracking-sidebar,
    .tracking-skeleton-sidebar {
        border-inline-start: none;
        border-top: 1px solid var(--vs-border);
        max-height: none;
        overflow-y: visible;
    }
}
</style>

<style>
/* Portaled to body — unscoped so width/height apply outside component tree */
.p-dialog.container-tracking-dialog {
    width: calc(100vw - 20px) !important;
    max-width: calc(100vw - 20px) !important;
    max-height: calc(100vh - 20px) !important;
    min-height: min(85vh, calc(100vh - 20px)) !important;
    margin: 10px !important;
    border: 1px solid var(--vs-border);
    border-radius: 16px;
    box-shadow:
        0 0 0 1px rgb(255 255 255 / 0.04),
        0 28px 56px rgb(0 0 0 / 0.22),
        0 12px 24px rgb(0 0 0 / 0.12);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: trackingDialogIn 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes trackingDialogIn {
    from {
        opacity: 0;
        transform: translateY(12px) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.p-dialog.container-tracking-dialog .p-dialog-content {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* mask blur + theme */
.p-dialog-mask.container-tracking-mask {
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    background: rgb(15 23 42 / 0.58);
}

[data-theme='dark'] .p-dialog-mask.container-tracking-mask {
    background: rgb(0 0 0 / 0.72);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog,
[data-theme='dark'] .p-dialog.container-tracking-dialog .p-dialog-header,
[data-theme='dark'] .p-dialog.container-tracking-dialog .p-dialog-content {
    background: var(--admin-surface);
    color: var(--vs-text);
    border-color: var(--vs-border);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-kicker {
    color: var(--vs-text-secondary);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-title {
    color: var(--vs-text);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-route-line {
    color: var(--vs-text-secondary);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-route-arrow {
    color: var(--vs-text-subtle);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .meta-chip {
    background: var(--vs-surface-elevated);
    color: var(--vs-text);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .meta-label {
    color: var(--vs-text-subtle);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .meta-chip--carrier {
    background: rgb(14 165 233 / 0.18);
    color: #7dd3fc;
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .status-badge {
    background: var(--status-transit-bg);
    color: var(--status-transit-fg);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .status-badge--transit {
    background: var(--status-transit-bg);
    color: var(--status-transit-fg);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .status-badge--arrived {
    background: var(--status-new-bg);
    color: var(--status-new-fg);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .cache-note {
    color: var(--vs-text-muted);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .disclaimer-note {
    background: var(--vs-surface-elevated);
    border-color: var(--vs-border);
    color: var(--vs-text-secondary);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .disclaimer-note .pi {
    color: var(--vs-text-muted);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .disclaimer-note--estimate {
    background: rgb(245 158 11 / 0.12);
    border-color: rgb(245 158 11 / 0.35);
    color: #fcd34d;
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .disclaimer-note--estimate .pi {
    color: #fbbf24;
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-header-top .p-button {
    color: var(--vs-text-muted);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-header-top .p-button:not(:disabled):hover {
    color: var(--vs-text);
    background: var(--vs-surface-hover);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-skeleton-map,
[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-map-wrap {
    background: var(--vs-zinc-900);
    border: 1px solid var(--vs-border);
    border-inline-end: none;
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-skeleton-sidebar,
[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-sidebar {
    background: var(--vs-surface-elevated);
    border-color: var(--vs-border);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .map-tiles-loading {
    background: rgb(9 9 11 / 0.55);
    color: var(--vs-text-secondary);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .map-empty {
    color: var(--vs-text-muted);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .map-empty-illus {
    background: linear-gradient(145deg, var(--vs-surface-elevated), var(--vs-zinc-900));
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .map-empty-illus .pi {
    color: var(--vs-text-subtle);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .map-empty-title {
    color: var(--vs-text-secondary);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .map-empty-hint {
    color: var(--vs-text-muted);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-map-wrap .leaflet-control-attribution {
    background: rgb(39 39 42 / 0.92);
    color: var(--vs-text-muted);
    border: 1px solid var(--vs-border);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-map-wrap .leaflet-control-attribution a {
    color: #7dd3fc;
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .route-card {
    background: var(--vs-surface);
    border-color: var(--vs-border);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .route-end-label {
    color: var(--vs-text-subtle);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .route-end-name {
    color: var(--vs-text);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .route-arrow {
    color: var(--vs-text-subtle);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .events-title {
    color: var(--vs-text-secondary);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .event-item:not(:last-child)::before {
    background: var(--vs-border);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .event-marker {
    box-shadow: 0 0 0 3px var(--vs-surface-elevated);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .event-title {
    color: var(--vs-text);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .event-location,
[data-theme='dark'] .p-dialog.container-tracking-dialog .event-date {
    color: var(--vs-text-muted);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .event-date-relative {
    color: #5eead4;
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .event-date-abs {
    color: var(--vs-text-subtle);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .events-empty,
[data-theme='dark'] .p-dialog.container-tracking-dialog .source-tag {
    color: var(--vs-text-subtle);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-error-card {
    color: var(--vs-text-muted);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-error-title {
    color: var(--vs-text);
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-error-msg {
    color: var(--vs-text-muted);
}

@media (max-width: 900px) {
    [data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-map-wrap {
        border-inline-end: 1px solid var(--vs-border);
        border-bottom: none;
    }

    [data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-sidebar,
    [data-theme='dark'] .p-dialog.container-tracking-dialog .tracking-skeleton-sidebar {
        border-top-color: var(--vs-border);
    }
}

/* Leaflet divIcon markers (portaled inside map, needs global styles) */
.tracking-map-marker {
    background: transparent !important;
    border: none !important;
}

.tracking-map-marker .tm {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    color: #fff;
    box-shadow: 0 2px 8px rgb(0 0 0 / 0.28);
    border: 2px solid rgb(255 255 255 / 0.85);
}

.tracking-map-marker .tm--origin {
    background: linear-gradient(145deg, #7c3aed, #a78bfa);
    font-size: 0.95rem;
}

.tracking-map-marker .tm--waypoint {
    background: linear-gradient(145deg, #ea580c, #fb923c);
    position: relative;
    font-size: 0.75rem;
}

.tracking-map-marker .tm--waypoint .tm__arrow {
    position: absolute;
    inset-inline-start: 4px;
    font-size: 0.55rem;
    opacity: 0.9;
}

.tracking-map-marker .tm--destination {
    background: linear-gradient(145deg, #52525b, #71717a);
    font-size: 1rem;
}

.tracking-map-marker .tm--destination .pi-circle {
    font-size: 0.55rem;
}

.tracking-map-marker--current .tm--current {
    width: 42px;
    height: 42px;
}

.tracking-map-marker .tm--current {
    position: relative;
    background: linear-gradient(145deg, #0d9488, #14b8a6);
    border-width: 2.5px;
    box-shadow: 0 3px 10px rgb(0 0 0 / 0.32);
}

.tracking-map-marker .tm--current .tm__car-svg {
    width: 1.35rem;
    height: 1.35rem;
    color: #fff;
    filter: drop-shadow(0 1px 1px rgb(0 0 0 / 0.2));
}

.tracking-map-marker .tm--current .tm__pulse {
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    border: 2px solid rgb(20 184 166 / 0.55);
    animation: trackingPulse 2.2s ease-out infinite;
    pointer-events: none;
}

@keyframes trackingPulse {
    0% {
        transform: scale(0.92);
        opacity: 0.85;
    }

    70%,
    100% {
        transform: scale(1.35);
        opacity: 0;
    }
}

[data-theme='dark'] .p-dialog.container-tracking-dialog .map-legend {
    background: var(--vs-surface-elevated);
    border-top-color: var(--vs-border);
}
</style>
