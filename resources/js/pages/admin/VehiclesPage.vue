<template>
    <div class="admin-page">
        <AdminPageHeader>
            <template #filters>
                <IconField>
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="search"
                        placeholder="بحث برقم الشاصي أو الموديل..."
                        @keyup.enter="load"
                    />
                </IconField>
                <Select
                    v-model="statusFilter"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="الحالة"
                    show-clear
                    @change="load"
                />
                <Button icon="pi pi-refresh" label="تحديث" outlined :loading="loading" @click="load" />
            </template>
        </AdminPageHeader>

        <VehicleListPanel
                :vehicles="vehicles"
                :loading="loading"
                :total="total"
                :page="page"
                :per-page="perPage"
                mode="admin"
                empty-text="لا توجد سيارات"
                empty-hint="ستظهر السيارات بعد المزامنة من Vinstack أو الإضافة اليدوية."
                empty-action-label="تحديث القائمة"
                @assign="openAssign"
                @open-detail="openDetail"
                @page="onPage"
                @empty-action="load"
            />

        <VehicleDetailDrawer
            v-model:visible="detailVisible"
            :vehicle-id="detailVehicleId"
            mode="admin"
        />

        <Dialog v-model:visible="assignVisible" header="إسناد سيارة" modal style="width: min(420px, 100vw)">
            <Select
                v-model="selectedDealerId"
                :options="dealers"
                option-label="company_name"
                option-value="id"
                placeholder="اختر التاجر"
                class="w-full"
            />
            <template #footer>
                <Button label="إلغاء" text @click="assignVisible = false" />
                <Button label="تأكيد" :loading="assigning" @click="confirmAssign" />
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
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import AdminPageHeader from '../../components/AdminPageHeader.vue';
import VehicleListPanel from '../../components/VehicleListPanel.vue';
import VehicleDetailDrawer from '../../components/VehicleDetailDrawer.vue';
import api from '../../api/client';

const toast = useToast();
const vehicles = ref([]);
const dealers = ref([]);
const loading = ref(false);
const search = ref('');
const statusFilter = ref(null);
const page = ref(1);
const perPage = ref(15);
const total = ref(0);

const assignVisible = ref(false);
const detailVisible = ref(false);
const detailVehicleId = ref(null);
const selectedVehicle = ref(null);
const selectedDealerId = ref(null);
const statusOptions = [
    { label: 'متاحة', value: 'available' },
    { label: 'مسندة', value: 'assigned' },
    { label: 'محجوزة', value: 'reserved' },
    { label: 'مستوردة', value: 'imported' },
];

const assigning = ref(false);

async function load() {
    loading.value = true;

    try {
        const { data } = await api.get('/admin/vehicles', {
            params: {
                page: page.value,
                per_page: perPage.value,
                search: search.value || undefined,
                status: statusFilter.value || undefined,
            },
        });
        vehicles.value = data.data;
        total.value = data.total;
    } finally {
        loading.value = false;
    }
}

async function loadDealers() {
    const { data } = await api.get('/admin/dealers');
    dealers.value = data.data;
}

function onPage(event) {
    page.value = event.page + 1;
    perPage.value = event.rows;
    load();
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
        toast.add({ severity: 'success', summary: 'تم الإسناد', life: 3000 });
        await load();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل الإسناد',
            life: 4000,
        });
    } finally {
        assigning.value = false;
    }
}

onMounted(async () => {
    await loadDealers();
    await load();
});
</script>

<style scoped>
.w-full {
    width: 100%;
}
</style>
