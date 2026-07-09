<template>
    <div class="admin-page">
        <AdminPageHeader :count="total">
            <template #actions>
                <Button
                    :label="t('vehicles.importNujoom')"
                    icon="pi pi-file-import"
                    severity="secondary"
                    outlined
                    @click="nujoomImportVisible = true"
                />
                <Button
                    :label="t('vehicles.addManual')"
                    icon="pi pi-plus"
                    class="btn-add"
                    @click="openCreateManual"
                />
            </template>
            <template #filters>
                <div class="vehicles-filters">
                    <div class="vehicles-filters__row">
                        <IconField class="vehicles-filters__search">
                            <InputIcon class="pi pi-search" />
                            <InputText
                                v-model="search"
                                size="small"
                                :placeholder="t('vehicles.searchPlaceholder')"
                                :disabled="loadingMore"
                                @keyup.enter="resetAndLoad"
                            />
                        </IconField>
                        <Select
                            v-model="statusFilter"
                            class="vehicles-filters__select"
                            size="small"
                            :options="statusOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="t('vehicles.statusFilter')"
                            show-clear
                            @change="resetAndLoad"
                        />
                        <Select
                            v-model="sourceFilter"
                            class="vehicles-filters__select"
                            size="small"
                            :options="sourceOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="t('vehicles.sourceFilter')"
                            show-clear
                            @change="resetAndLoad"
                        />
                        <Button
                            icon="pi pi-refresh"
                            :label="t('actions.refresh')"
                            size="small"
                            outlined
                            :loading="loading"
                            @click="resetAndLoad"
                        />
                    </div>
                    <DealerFilterBadges
                        class="vehicles-filters__dealers"
                        :dealers="dealerSummary"
                        :selected-id="dealerFilter"
                        count-key="vehicles_count"
                        :total-count="dealerFilter ? null : total"
                        @select="onDealerBadgeSelect"
                    />
                </div>
            </template>
        </AdminPageHeader>

        <VehicleListPanel
            :vehicles="vehicles"
            :loading="loading"
            :loading-more="loadingMore"
            :total="total"
            :page="page"
            :per-page="perPage"
            mode="admin"
            infinite-scroll
            :has-more="hasMore"
            :tracking-available="trackingAvailable"
            :empty-text="t('vehicles.empty')"
            :empty-hint="t('vehicles.emptyHint')"
            :empty-action-label="t('actions.refreshList')"
            @assign="openAssign"
            @unassign="confirmUnassign"
            @delete="confirmDeleteVehicle"
            @edit="openEdit"
            @open-chat="openChat"
            @open-detail="openDetail"
            @load-more="loadMore"
            @empty-action="resetAndLoad"
        />

        <VehicleDetailDrawer
            v-model:visible="detailVisible"
            :vehicle-id="detailVehicleId"
            mode="admin"
            @deleted="onVehicleDeleted"
        />

        <VehicleChatDialog
            v-model:visible="chatVisible"
            :vehicle="chatVehicle"
            mode="admin"
            @read="onChatRead"
            @sent="resetAndLoad"
        />

        <Drawer
            v-model:visible="manualFormVisible"
            position="right"
            class="manual-vehicle-drawer"
            :header="manualFormHeader"
            :style="{ width: 'min(720px, 95vw)' }"
            @hide="onManualDrawerHide"
        >
            <ManualVehicleForm
                :vehicle="editingVehicle"
                @saved="onManualSaved"
                @deleted="onManualDeleted"
                @cancel="closeManualForm"
            />
        </Drawer>

        <NujoomImportDialog v-model:visible="nujoomImportVisible" @applied="resetAndLoad" />

        <Dialog v-model:visible="assignVisible" :header="t('vehicles.assignDialog')" modal style="width: min(460px, 100vw)">
            <div class="assign-dialog">
                <div v-if="selectedVehicle" class="assign-dialog__vehicle">
                    <div class="assign-dialog__label">{{ t('vehicles.assignDialog') }}</div>
                    <div class="assign-dialog__title">{{ selectedVehicleLabel }}</div>
                    <div v-if="selectedVehicle?.vin" class="assign-dialog__meta">{{ selectedVehicle.vin }}</div>
                </div>
                <Select
                    v-model="selectedDealerId"
                    :options="dealersForAssign"
                    option-label="assign_label"
                    option-value="id"
                    :placeholder="t('vehicles.selectDealer')"
                    filter
                    :filter-fields="['assign_label', 'company_name', 'user_name', 'phone']"
                    class="w-full"
                >
                    <template #option="{ option }">
                        <div class="assign-option">
                            <div class="assign-option__title">{{ option.assign_label }}</div>
                            <div v-if="option.phone" class="assign-option__meta">{{ option.phone }}</div>
                        </div>
                    </template>
                    <template #value="{ value, placeholder }">
                        <span v-if="value && selectedDealerOption">{{ selectedDealerOption.assign_label }}</span>
                        <span v-else>{{ placeholder }}</span>
                    </template>
                </Select>
            </div>
            <template #footer>
                <Button :label="t('actions.cancel')" text @click="assignVisible = false" />
                <Button :label="t('actions.confirm')" class="btn-assign" :loading="assigning" @click="confirmAssign" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Drawer from 'primevue/drawer';
import Select from 'primevue/select';
import AdminPageHeader from '../../components/AdminPageHeader.vue';
import DealerFilterBadges from '../../components/DealerFilterBadges.vue';
import ManualVehicleForm from '../../components/ManualVehicleForm.vue';
import VehicleListPanel from '../../components/VehicleListPanel.vue';
import VehicleDetailDrawer from '../../components/VehicleDetailDrawer.vue';
import VehicleChatDialog from '../../components/VehicleChatDialog.vue';
import NujoomImportDialog from '../../components/NujoomImportDialog.vue';
import api from '../../api/client';
import { deleteVehicle } from '../../api/vehicles';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();
const vehicles = ref([]);
const dealers = ref([]);
const dealerSummary = ref([]);
const loading = ref(false);
const loadingMore = ref(false);
const search = ref('');
const statusFilter = ref(null);
const sourceFilter = ref(null);
const dealerFilter = ref(null);
const page = ref(1);
const perPage = ref(50);
const total = ref(0);
const hasMore = ref(false);
const trackingAvailable = ref(false);
const searchDebounceMs = 350;
let searchDebounceTimer = null;
let activeFetchToken = 0;

const manualFormVisible = ref(false);
const editingVehicle = ref(null);
const manualFormHeader = computed(() =>
    editingVehicle.value ? t('vehicles.editManualHeader') : t('vehicles.addManualHeader'),
);
const nujoomImportVisible = ref(false);
const assignVisible = ref(false);
const detailVisible = ref(false);
const detailVehicleId = ref(null);
const chatVisible = ref(false);
const chatVehicle = ref(null);
const selectedVehicle = ref(null);
const selectedDealerId = ref(null);
const statusOptions = computed(() => [
    { label: t('vehicles.status.available'), value: 'available' },
    { label: t('vehicles.status.assigned'), value: 'assigned' },
    { label: t('vehicles.status.reserved'), value: 'reserved' },
]);
const sourceOptions = computed(() => [
    { label: t('vehicles.source.vinstack'), value: 'vinstack' },
    { label: t('vehicles.source.manual'), value: 'manual' },
    { label: t('vehicles.source.nujoom'), value: 'nujoom_al_jazeera' },
]);

const assigning = ref(false);
const dealersForAssign = computed(() => dealers.value.map((dealer) => {
    const userName = dealer.user?.name?.trim() || '';
    const companyName = dealer.company_name?.trim() || '';
    const phone = dealer.phone?.trim() || '';
    const assignLabel = userName || companyName || '—';

    return {
        ...dealer,
        user_name: userName,
        phone,
        assign_label: assignLabel,
    };
}));
const selectedDealerOption = computed(() =>
    dealersForAssign.value.find((dealer) => dealer.id === selectedDealerId.value) ?? null,
);
const selectedVehicleLabel = computed(() => {
    if (! selectedVehicle.value) {
        return '';
    }

    return [
        selectedVehicle.value.year,
        selectedVehicle.value.make,
        selectedVehicle.value.model,
    ].filter(Boolean).join(' ') || selectedVehicle.value.vin || `#${selectedVehicle.value.id}`;
});

function listParams(nextPage = page.value) {
    const dealerId = dealerFilter.value;

    return {
        page: nextPage,
        per_page: perPage.value,
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        source: sourceFilter.value || undefined,
        dealer_id: Number.isFinite(dealerId) && dealerId > 0 ? dealerId : undefined,
    };
}

async function loadDealerSummary() {
    const { data } = await api.get('/admin/dealers/summary');
    dealerSummary.value = data.data ?? [];
}

async function loadDealers() {
    const { data } = await api.get('/admin/dealers');
    dealers.value = data.data;
}

async function fetchPage(nextPage, append = false) {
    const isFirstPage = nextPage === 1 && ! append;
    const fetchToken = ++activeFetchToken;

    if (isFirstPage) {
        loading.value = true;
    } else {
        loadingMore.value = true;
    }

    try {
        const { data } = await api.get('/admin/vehicles', {
            params: listParams(nextPage),
        });

        if (fetchToken !== activeFetchToken) {
            return;
        }

        const rows = data.data ?? [];

        vehicles.value = append ? [...vehicles.value, ...rows] : rows;
        total.value = data.meta?.total ?? data.total ?? rows.length;
        hasMore.value = Boolean(data.meta?.has_more);
        trackingAvailable.value = Boolean(data.tracking_available);
        page.value = nextPage;
    } finally {
        if (fetchToken === activeFetchToken) {
            loading.value = false;
            loadingMore.value = false;
        }
    }
}

async function resetAndLoad() {
    page.value = 1;
    hasMore.value = false;
    await Promise.all([loadDealerSummary(), fetchPage(1)]);
}

async function loadMore() {
    if (loading.value || loadingMore.value || ! hasMore.value) {
        return;
    }

    await fetchPage(page.value + 1, true);
}

function onDealerBadgeSelect(dealerId) {
    dealerFilter.value = dealerId;
    resetAndLoad();
}

function scheduleSearchLoad() {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }

    searchDebounceTimer = window.setTimeout(() => {
        resetAndLoad();
    }, searchDebounceMs);
}

function openCreateManual() {
    editingVehicle.value = null;
    manualFormVisible.value = true;
}

function openEdit(vehicle) {
    if (vehicle?.source !== 'manual') {
        return;
    }

    editingVehicle.value = vehicle;
    manualFormVisible.value = true;
}

function closeManualForm() {
    manualFormVisible.value = false;
}

function onManualDrawerHide() {
    editingVehicle.value = null;
}

async function onManualSaved() {
    closeManualForm();
    editingVehicle.value = null;
    await resetAndLoad();
}

async function onManualDeleted() {
    closeManualForm();
    editingVehicle.value = null;
    await resetAndLoad();
}

async function onVehicleDeleted() {
    detailVisible.value = false;
    detailVehicleId.value = null;
    await resetAndLoad();
}

function confirmDeleteVehicle(vehicle) {
    confirm.require({
        message: t('vehicles.deleteConfirm'),
        header: t('vehicles.deleteTitle'),
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: t('actions.cancel'),
        acceptLabel: t('actions.delete'),
        acceptClass: 'p-button-danger',
        accept: () => performDeleteVehicle(vehicle),
    });
}

async function performDeleteVehicle(vehicle) {
    try {
        const result = await deleteVehicle(vehicle.id);
        toast.add({ severity: 'success', summary: result.message || t('vehicles.deleteSuccess'), life: 3000 });

        if (detailVehicleId.value === vehicle.id) {
            detailVisible.value = false;
            detailVehicleId.value = null;
        }

        await resetAndLoad();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || t('vehicles.deleteFailed'),
            life: 4000,
        });
    }
}

function openAssign(vehicle) {
    selectedVehicle.value = vehicle;
    selectedDealerId.value = null;
    assignVisible.value = true;
}

function openDetail(vehicle) {
    detailVehicleId.value = vehicle.id;
    detailVisible.value = true;
}

function openChat(vehicle) {
    chatVehicle.value = vehicle;
    chatVisible.value = true;
}

function onChatRead(vehicle) {
    const row = vehicles.value.find((item) => item.id === vehicle.id);

    if (row) {
        row.unread_messages_count = 0;
    }
}

async function confirmAssign() {
    if (! selectedDealerId.value || ! selectedVehicle.value) {
        return;
    }

    assigning.value = true;

    try {
        await api.post(`/admin/vehicles/${selectedVehicle.value.id}/assign`, {
            dealer_id: selectedDealerId.value,
        });
        assignVisible.value = false;
        toast.add({ severity: 'success', summary: t('vehicles.assignedSuccess'), life: 3000 });
        await resetAndLoad();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || t('vehicles.assignFailed'),
            life: 4000,
        });
    } finally {
        assigning.value = false;
    }
}

function confirmUnassign(vehicle) {
    const dealerName = vehicle.active_assignment?.dealer?.user?.name
        ?? vehicle.active_assignment?.dealer?.company_name
        ?? t('common.dealer');

    confirm.require({
        message: t('vehicles.unassignConfirm', { dealer: dealerName }),
        header: t('vehicles.unassignHeader'),
        icon: 'pi pi-times-circle',
        rejectLabel: t('actions.cancel'),
        acceptLabel: t('actions.confirm'),
        acceptClass: 'p-button-danger',
        accept: () => unassignVehicle(vehicle),
    });
}

async function unassignVehicle(vehicle) {
    try {
        await api.delete(`/admin/vehicles/${vehicle.id}/unassign`);
        toast.add({ severity: 'success', summary: t('vehicles.unassignSuccess'), life: 3000 });
        await resetAndLoad();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || t('vehicles.unassignFailed'),
            life: 4000,
        });
    }
}

function openDetailFromQuery() {
    const rawId = route.query.vehicle;

    if (! rawId) {
        return;
    }

    const vehicleId = Number(rawId);

    if (! Number.isFinite(vehicleId) || vehicleId <= 0) {
        return;
    }

    detailVehicleId.value = vehicleId;
    detailVisible.value = true;

    const nextQuery = { ...route.query };
    delete nextQuery.vehicle;
    router.replace({ query: nextQuery });
}

watch(() => route.query.vehicle, () => {
    openDetailFromQuery();
});

watch(search, () => {
    scheduleSearchLoad();
});

onMounted(async () => {
    await loadDealers();
    await resetAndLoad();
    openDetailFromQuery();
});

onUnmounted(() => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }
});
</script>

<style scoped>
.vehicles-filters {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.vehicles-filters__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
    width: 100%;
}

.vehicles-filters__search {
    flex: 1 1 12rem;
    min-width: 10rem;
    max-width: 18rem;
}

.vehicles-filters__select {
    flex: 0 0 auto;
    width: 8.75rem;
    max-width: 100%;
}

.vehicles-filters__dealers {
    width: 100%;
    padding-top: 0.45rem;
    border-top: 1px solid var(--admin-border);
}

.vehicles-filters__dealers :deep(.dealer-badges) {
    width: 100%;
    margin-top: 0;
}

@media (max-width: 640px) {
    .vehicles-filters__row {
        gap: 0.35rem;
    }

    .vehicles-filters__search {
        flex: 1 1 100%;
        max-width: none;
    }

    .vehicles-filters__select {
        flex: 1 1 calc(50% - 0.2rem);
        width: auto;
    }
}

.w-full {
    width: 100%;
}

.assign-dialog {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.assign-dialog__vehicle {
    padding: 0.85rem 1rem;
    border: 1px solid var(--vs-border);
    border-radius: 14px;
    background: var(--vs-surface-elevated);
}

.assign-dialog__label {
    font-size: 0.76rem;
    color: var(--vs-text-muted);
    margin-bottom: 0.2rem;
}

.assign-dialog__title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--vs-text);
}

.assign-dialog__meta {
    margin-top: 0.2rem;
    font-size: 0.8rem;
    color: var(--vs-text-secondary);
}

.assign-option {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.assign-option__title {
    font-weight: 600;
    color: var(--vs-text);
}

.assign-option__sub,
.assign-option__meta {
    font-size: 0.8rem;
    color: var(--vs-text-muted);
}
</style>
