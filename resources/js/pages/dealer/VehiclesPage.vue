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
            empty-hint="عند إسناد سيارات من الإدارة ستظهر هنا تلقائياً."
            empty-action-label="تحديث القائمة"
            @update-status="openStatus"
            @open-detail="openDetail"
            @page="onPage"
            @empty-action="load"
        />

        <VehicleDetailDrawer
            v-model:visible="detailVisible"
            :vehicle-id="detailVehicleId"
            mode="dealer"
        />

        <Dialog v-model:visible="statusVisible" header="تحديث الحالة" modal style="width: min(480px, 100vw)">
            <VehiclePhotosPanel v-if="selectedVehicle" :vehicle="selectedVehicle" api-mode="dealer" />
            <Select
                v-model="selectedStatus"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                class="w-full mt"
            />
            <Textarea v-model="notes" rows="4" class="w-full mt" placeholder="ملاحظات" />
            <template #footer>
                <Button label="إلغاء" text @click="statusVisible = false" />
                <Button label="حفظ" class="btn-cta" :loading="saving" @click="saveStatus" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import DealerDashboardCards from '../../components/DealerDashboardCards.vue';
import VehicleListPanel from '../../components/VehicleListPanel.vue';
import VehicleDetailDrawer from '../../components/VehicleDetailDrawer.vue';
import VehiclePhotosPanel from '../../components/VehiclePhotosPanel.vue';
import api from '../../api/client';

const toast = useToast();
const vehicles = ref([]);
const loading = ref(false);
const search = ref('');
const page = ref(1);
const perPage = ref(15);
const total = ref(0);

const statusVisible = ref(false);
const detailVisible = ref(false);
const detailVehicleId = ref(null);
const selectedVehicle = ref(null);
const selectedStatus = ref('assigned');
const notes = ref('');
const saving = ref(false);

const statusOptions = [
    { label: 'مسندة', value: 'assigned' },
    { label: 'محجوزة', value: 'reserved' },
    { label: 'مستوردة', value: 'imported' },
];

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

function openStatus(vehicle) {
    selectedVehicle.value = vehicle;
    selectedStatus.value = vehicle.status;
    notes.value = vehicle.notes || '';
    statusVisible.value = true;
}

function openDetail(vehicle) {
    detailVehicleId.value = vehicle.id;
    detailVisible.value = true;
}

async function saveStatus() {
    saving.value = true;

    try {
        await api.patch(`/dealer/vehicles/${selectedVehicle.value.id}/status`, {
            status: selectedStatus.value,
            notes: notes.value,
        });
        statusVisible.value = false;
        toast.add({ severity: 'success', summary: 'تم التحديث', life: 3000 });
        await load();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل التحديث',
            life: 4000,
        });
    } finally {
        saving.value = false;
    }
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

.w-full {
    width: 100%;
}

.mt {
    margin-top: 0.75rem;
}
</style>
