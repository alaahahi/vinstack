<template>
    <div class="vehicles-page">
        <DealerDashboardCards />

        <div class="toolbar">
            <h2 class="section-title">قائمة السيارات</h2>
            <IconField class="search-field">
                <InputIcon class="pi pi-search" />
                <InputText v-model="search" placeholder="بحث برقم الشاصي أو الموديل..." @keyup.enter="load" />
            </IconField>
            <Button icon="pi pi-refresh" label="تحديث" outlined :loading="loading" @click="load" />
        </div>

        <VehicleListPanel
            :vehicles="vehicles"
            :loading="loading"
            :total="total"
            :page="page"
            :per-page="perPage"
            mode="dealer"
            :tracking-available="trackingAvailable"
            empty-hint="عند إسناد سيارات من الإدارة ستظهر هنا تلقائياً."
            empty-action-label="تحديث القائمة"
            @open-chat="openChat"
            @open-detail="openDetail"
            @page="onPage"
            @empty-action="load"
        />

        <VehicleDetailDrawer
            v-model:visible="detailVisible"
            :vehicle-id="detailVehicleId"
            mode="dealer"
        />

        <VehicleChatDialog
            v-model:visible="chatVisible"
            :vehicle="chatVehicle"
            mode="dealer"
            @read="onChatRead"
            @sent="load"
        />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import DealerDashboardCards from '../../components/DealerDashboardCards.vue';
import VehicleListPanel from '../../components/VehicleListPanel.vue';
import VehicleDetailDrawer from '../../components/VehicleDetailDrawer.vue';
import VehicleChatDialog from '../../components/VehicleChatDialog.vue';
import api from '../../api/client';

const toast = useToast();
const vehicles = ref([]);
const loading = ref(false);
const search = ref('');
const page = ref(1);
const perPage = ref(15);
const total = ref(0);
const trackingAvailable = ref(false);

const chatVisible = ref(false);
const chatVehicle = ref(null);
const detailVisible = ref(false);
const detailVehicleId = ref(null);

async function load() {
    loading.value = true;

    try {
        const { data } = await api.get('/dealer/vehicles', {
            params: {
                page: page.value,
                per_page: perPage.value,
                search: search.value || undefined,
            },
        });
        vehicles.value = data.data;
        total.value = data.total;
        trackingAvailable.value = Boolean(data.tracking_available);
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'تعذّر جلب السيارات',
            life: 4000,
        });
    } finally {
        loading.value = false;
    }
}

function onPage(event) {
    page.value = event.page + 1;
    perPage.value = event.rows;
    load();
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

function openDetail(vehicle) {
    detailVehicleId.value = vehicle.id;
    detailVisible.value = true;
}

onMounted(load);
</script>

<style scoped>
.vehicles-page {
    max-width: 1400px;
}

.toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
    margin-bottom: 1rem;
}

.section-title {
    margin: 0;
    flex: 1;
    min-width: 8rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.search-field {
    flex: 1;
    min-width: min(100%, 220px);
    max-width: 320px;
}
</style>
