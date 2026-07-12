<template>
    <div class="containers-page">
        <div class="toolbar">
            <div>
                <h2 class="section-title">{{ t('containers.dealer.title') }}</h2>
                <p class="subtitle">{{ t('containers.dealer.subtitle') }}</p>
            </div>
            <Button icon="pi pi-refresh" :label="t('actions.refresh')" outlined :loading="loading" @click="load" />
        </div>

        <div class="filters">
            <IconField class="filter-field">
                <InputIcon class="pi pi-box" />
                <InputText
                    v-model="containerSearch"
                    :placeholder="t('containers.searchContainer')"
                    @keyup.enter="applySearch"
                />
            </IconField>
            <IconField class="filter-field">
                <InputIcon class="pi pi-search" />
                <InputText
                    v-model="chassisSearch"
                    :placeholder="t('containers.searchChassis')"
                    @keyup.enter="applySearch"
                />
            </IconField>
            <Button
                icon="pi pi-search"
                :label="t('actions.search')"
                @click="applySearch"
            />
            <Button
                v-if="hasActiveFilters"
                icon="pi pi-filter-slash"
                :label="t('actions.clearFilters')"
                severity="secondary"
                outlined
                @click="clearFilters"
            />
        </div>

        <ContainerListPanel
            :containers="displayItems"
            :loading="loading"
            :tracking-available="trackingAvailable"
            :show-invoice="false"
            direct-image-gallery
            link-container-detail
            show-vehicle-thumbs
            api-prefix="/dealer"
            :empty-text="emptyText"
            :empty-action-label="t('actions.viewMyVehicles')"
            @track="openTracking"
            @show-cars="openCars"
            @empty-action="goVehicles"
        />

        <ContainerTrackingDialog
            v-model:visible="trackingVisible"
            :container="trackingContainer"
            api-role="dealer"
        />

        <ContainerCarsDialog
            v-model:visible="carsVisible"
            :container="carsContainer"
            api-role="dealer"
            :show-zip-upload="false"
            @hide="onCarsDialogHide"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import ContainerListPanel from '../../components/ContainerListPanel.vue';
import ContainerTrackingDialog from '../../components/ContainerTrackingDialog.vue';
import ContainerCarsDialog from '../../components/ContainerCarsDialog.vue';
import api from '../../api/client';
import {
    containerDetailRoute,
    containerMatchesSearch,
    containerRefs,
    normalizeContainerSearchValue,
} from '../../utils/containerMeta';

const { t } = useI18n();
const toast = useToast();
const router = useRouter();
const route = useRoute();
const items = ref([]);
const loading = ref(false);
const trackingAvailable = ref(false);
const trackingVisible = ref(false);
const trackingContainer = ref(null);
const carsVisible = ref(false);
const carsContainer = ref(null);
const containerSearch = ref('');
const chassisSearch = ref('');
const appliedContainerSearch = ref('');
const appliedChassisSearch = ref('');

const hasActiveFilters = computed(() =>
    Boolean(appliedContainerSearch.value || appliedChassisSearch.value),
);

const displayItems = computed(() => {
    if (! hasActiveFilters.value) {
        return items.value;
    }

    return items.value.filter((container) => containerMatchesSearch(container, {
        containerQuery: appliedContainerSearch.value,
        chassisQuery: appliedChassisSearch.value,
    }));
});

const emptyText = computed(() => {
    if (! items.value.length) {
        return t('containers.dealer.empty');
    }

    if (hasActiveFilters.value) {
        return t('containers.dealer.noSearchResults');
    }

    return t('containers.empty');
});

function findContainerByRef(refValue) {
    const needle = normalizeContainerSearchValue(refValue);

    if (! needle) {
        return null;
    }

    return items.value.find((container) => {
        const refs = containerRefs(container);
        const values = [refs.container, refs.booking]
            .filter(Boolean)
            .map((entry) => normalizeContainerSearchValue(entry));

        return values.includes(needle);
    }) ?? null;
}

async function load() {
    loading.value = true;

    try {
        const { data } = await api.get('/dealer/containers', {
            params: {
                container: appliedContainerSearch.value || undefined,
                chassis: appliedChassisSearch.value || undefined,
            },
        });
        items.value = data.data ?? [];
        trackingAvailable.value = Boolean(data.tracking_available);
        syncRouteContainer();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || t('containers.dealer.loadFailed'),
            life: 4000,
        });
    } finally {
        loading.value = false;
    }
}

function applySearch() {
    appliedContainerSearch.value = containerSearch.value.trim();
    appliedChassisSearch.value = chassisSearch.value.trim();
    load();
}

function clearFilters() {
    containerSearch.value = '';
    chassisSearch.value = '';
    appliedContainerSearch.value = '';
    appliedChassisSearch.value = '';
    load();
}

function openTracking(container) {
    trackingContainer.value = container;
    trackingVisible.value = true;
}

function openCars(container, { updateRoute = true } = {}) {
    carsContainer.value = container;
    carsVisible.value = true;

    if (! updateRoute) {
        return;
    }

    const detailRoute = containerDetailRoute(container, 'dealer');

    if (! detailRoute) {
        return;
    }

    if (
        route.name !== detailRoute.name
        || route.params.containerRef !== detailRoute.params.containerRef
    ) {
        router.push(detailRoute);
    }
}

function onCarsDialogHide() {
    if (route.name === 'dealer.container') {
        router.push({ name: 'dealer.containers' });
    }
}

function syncRouteContainer() {
    const routeRef = route.params.containerRef;

    if (! routeRef || route.name !== 'dealer.container') {
        return;
    }

    const container = findContainerByRef(routeRef);

    if (! container) {
        toast.add({
            severity: 'warn',
            summary: t('containers.dealer.containerNotFound'),
            detail: String(routeRef),
            life: 4000,
        });
        router.replace({ name: 'dealer.containers' });

        return;
    }

    openCars(container, { updateRoute: false });
}

function goVehicles() {
    router.push({ name: 'dealer.vehicles' });
}

watch(
    () => route.params.containerRef,
    () => {
        if (items.value.length) {
            syncRouteContainer();
        }
    },
);

onMounted(load);
</script>

<style scoped>
.containers-page {
    max-width: 1400px;
}

.toolbar {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    margin-bottom: 1rem;
    align-items: center;
}

.filter-field {
    min-width: min(100%, 220px);
    flex: 1 1 220px;
}

.section-title {
    margin: 0 0 0.25rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.subtitle {
    margin: 0;
    font-size: 0.85rem;
    color: var(--vs-text-muted);
    max-width: 36rem;
}
</style>
