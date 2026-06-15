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
            @update-status="openNotes"
            @open-detail="openDetail"
            @page="onPage"
            @empty-action="load"
        />

        <VehicleDetailDrawer
            v-model:visible="detailVisible"
            :vehicle-id="detailVehicleId"
            mode="dealer"
        />

        <Dialog
            v-model:visible="notesVisible"
            header="رسالة على السيارة"
            modal
            style="width: min(480px, 100vw)"
        >
            <div v-if="selectedVehicle" class="notes-dialog">
                <div class="notes-dialog__vehicle">
                    <div class="notes-dialog__title">{{ vehicleTitle(selectedVehicle) }}</div>
                    <VinCopyLabel :vin="selectedVehicle.vin" block />
                </div>
                <Textarea
                    v-model="notes"
                    rows="5"
                    class="w-full"
                    placeholder="اكتب رسالتك أو ملاحظتك على هذه السيارة..."
                />
            </div>
            <template #footer>
                <Button label="إلغاء" text @click="notesVisible = false" />
                <Button label="إرسال" class="btn-cta" :loading="saving" @click="saveNotes" />
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
import DealerDashboardCards from '../../components/DealerDashboardCards.vue';
import VehicleListPanel from '../../components/VehicleListPanel.vue';
import VehicleDetailDrawer from '../../components/VehicleDetailDrawer.vue';
import VinCopyLabel from '../../components/VinCopyLabel.vue';
import { vehicleTitle } from '../../utils/vehicleMeta';
import api from '../../api/client';

const toast = useToast();
const vehicles = ref([]);
const loading = ref(false);
const search = ref('');
const page = ref(1);
const perPage = ref(15);
const total = ref(0);
const trackingAvailable = ref(false);

const notesVisible = ref(false);
const detailVisible = ref(false);
const detailVehicleId = ref(null);
const selectedVehicle = ref(null);
const notes = ref('');
const saving = ref(false);

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

function openNotes(vehicle) {
    selectedVehicle.value = vehicle;
    notes.value = vehicle.notes || '';
    notesVisible.value = true;
}

function openDetail(vehicle) {
    detailVehicleId.value = vehicle.id;
    detailVisible.value = true;
}

async function saveNotes() {
    const message = notes.value.trim();

    if (! message) {
        toast.add({
            severity: 'warn',
            summary: 'الرسالة فارغة',
            detail: 'اكتب رسالة قبل الإرسال.',
            life: 3500,
        });

        return;
    }

    saving.value = true;

    try {
        await api.patch(`/dealer/vehicles/${selectedVehicle.value.id}/status`, {
            notes: message,
        });
        notesVisible.value = false;
        toast.add({ severity: 'success', summary: 'تم إرسال الرسالة', life: 3000 });
        await load();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل إرسال الرسالة',
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

.notes-dialog__vehicle {
    margin-bottom: 0.85rem;
    padding: 0.75rem 0.85rem;
    border: 1px solid var(--vs-border);
    border-radius: 0.75rem;
    background: var(--vs-surface-hover);
}

.notes-dialog__title {
    font-weight: 700;
    margin-bottom: 0.35rem;
}

.w-full {
    width: 100%;
}
</style>
