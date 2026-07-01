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
                <IconField>
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="search"
                        :placeholder="t('vehicles.searchPlaceholder')"
                        @keyup.enter="resetAndLoad"
                    />
                </IconField>
                <Select
                    v-model="statusFilter"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="t('vehicles.statusFilter')"
                    show-clear
                    @change="resetAndLoad"
                />
                <Select
                    v-model="sourceFilter"
                    :options="sourceOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="t('vehicles.sourceFilter')"
                    show-clear
                    @change="resetAndLoad"
                />
                <Button icon="pi pi-refresh" :label="t('actions.refresh')" outlined :loading="loading" @click="resetAndLoad" />
                <DealerFilterBadges
                    :dealers="dealerSummary"
                    :selected-id="dealerFilter"
                    count-key="vehicles_count"
                    :total-count="dealerFilter ? null : total"
                    @select="onDealerBadgeSelect"
                />
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

        <Dialog v-model:visible="assignVisible" :header="t('vehicles.assignDialog')" modal style="width: min(420px, 100vw)">
            <Select
                v-model="selectedDealerId"
                :options="dealers"
                option-label="company_name"
                option-value="id"
                :placeholder="t('vehicles.selectDealer')"
                class="w-full"
            />
            <template #footer>
                <Button :label="t('actions.cancel')" text @click="assignVisible = false" />
                <Button :label="t('actions.confirm')" class="btn-assign" :loading="assigning" @click="confirmAssign" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
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

const { t } = useI18n();
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

    if (isFirstPage) {
        loading.value = true;
    } else {
        loadingMore.value = true;
    }

    try {
        const { data } = await api.get('/admin/vehicles', {
            params: listParams(nextPage),
        });
        const rows = data.data ?? [];

        vehicles.value = append ? [...vehicles.value, ...rows] : rows;
        total.value = data.meta?.total ?? data.total ?? rows.length;
        hasMore.value = Boolean(data.meta?.has_more);
        trackingAvailable.value = Boolean(data.tracking_available);
        page.value = nextPage;
    } finally {
        loading.value = false;
        loadingMore.value = false;
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
    const dealerName = vehicle.active_assignment?.dealer?.company_name ?? t('common.dealer');

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

onMounted(async () => {
    await loadDealers();
    await resetAndLoad();
});
</script>

<style scoped>
.w-full {
    width: 100%;
}
</style>
