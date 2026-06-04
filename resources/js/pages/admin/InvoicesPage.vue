<template>
    <div class="admin-page">
        <AdminPageHeader>
            <template #actions>
                <Button icon="pi pi-refresh" label="تحديث" outlined :loading="loading" @click="load" />
            </template>
        </AdminPageHeader>

        <section class="admin-surface admin-surface--flush invoices-panel">
            <div v-if="loading && !items.length" class="invoices-loading">
                <ProgressSpinner style="width: 36px; height: 36px" />
            </div>

            <div v-else-if="!items.length" class="admin-empty">
                <i class="pi pi-file" />
                <p class="admin-empty-title">لا توجد فواتير</p>
                <p class="admin-empty-hint">
                    تأكد من إعدادات Vinstack والمزامنة، ثم حدّث القائمة.
                </p>
                <Button icon="pi pi-refresh" label="تحديث" outlined @click="load" />
            </div>

            <DataTable
                v-else
                :value="items"
                :loading="loading"
                data-key="id"
                striped-rows
                class="invoices-table"
            >
                <Column field="invoice_number" header="رقم الفاتورة" />
                <Column field="status" header="الحالة">
                    <template #body="{ data }">
                        <Tag :value="data.status" :severity="statusSeverity(data.status)" />
                    </template>
                </Column>
                <Column field="currency" header="العملة" />
                <Column field="subtotal" header="المجموع" />
                <Column field="invoice_balance" header="الرصيد" />
                <Column header="تاريخ الاستحقاق">
                    <template #body="{ data }">
                        {{ formatDate(data.due_date) }}
                    </template>
                </Column>
                <Column header="البنود">
                    <template #body="{ data }">
                        <span class="line-count">{{ data.fields?.length ?? 0 }}</span>
                    </template>
                </Column>
            </DataTable>
        </section>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import AdminPageHeader from '../../components/AdminPageHeader.vue';
import api from '../../api/client';

const toast = useToast();
const items = ref([]);
const loading = ref(false);

function formatDate(value) {
    if (! value) {
        return '—';
    }

    return new Date(value).toLocaleDateString('ar');
}

function statusSeverity(status) {
    const s = String(status || '').toLowerCase();

    if (s.includes('paid') || s.includes('مدفوع')) {
        return 'success';
    }

    if (s.includes('pending') || s.includes('معلق')) {
        return 'warn';
    }

    return 'secondary';
}

async function load() {
    loading.value = true;

    try {
        const { data } = await api.get('/admin/vinstack/invoices');
        items.value = data.data;
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'تعذّر جلب الفواتير',
            life: 4000,
        });
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<style scoped>
.invoices-panel {
    min-height: 10rem;
}

.invoices-loading {
    display: flex;
    justify-content: center;
    padding: 3rem;
}

.invoices-table :deep(.p-datatable-thead > tr > th) {
    background: var(--vs-surface-elevated);
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--vs-text-muted);
}

.line-count {
    display: inline-flex;
    min-width: 1.5rem;
    justify-content: center;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: var(--vs-surface-elevated);
    font-size: 0.82rem;
    font-weight: 600;
}
</style>
