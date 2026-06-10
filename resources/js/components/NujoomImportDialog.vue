<template>
    <Dialog
        v-model:visible="visibleModel"
        header="استيراد من نجوم الجزيرة"
        modal
        :style="{ width: 'min(920px, 96vw)' }"
        @hide="reset"
    >
        <div v-if="! preview" class="import-upload">
            <p class="import-lead">
                ارفع ملف Excel من نجوم الجزيرة (All-Cars.xlsx). سيتم مطابقة السيارات برقم الشاصي: تحديث الموجود وإضافة الجديد بعد التأكيد.
            </p>
            <input
                ref="fileInput"
                type="file"
                accept=".xlsx,.xls"
                class="file-input"
                @change="onFileChange"
            />
            <div v-if="selectedFile" class="selected-file">
                <i class="pi pi-file-excel" />
                <span>{{ selectedFile.name }}</span>
            </div>
        </div>

        <div v-else class="import-preview">
            <div class="import-counts">
                <span class="count-badge count-badge--add">جديد: {{ preview.counts.to_add }}</span>
                <span class="count-badge count-badge--update">تحديث: {{ preview.counts.to_update }}</span>
                <span class="count-badge count-badge--container">حاويات جديدة: {{ preview.counts.containers_new }}</span>
                <span v-if="preview.counts.errors" class="count-badge count-badge--error">أخطاء: {{ preview.counts.errors }}</span>
            </div>

            <div class="apply-mode">
                <h4>طريقة التطبيق</h4>
                <div class="apply-mode-options">
                    <label
                        v-for="option in applyModeOptions"
                        :key="option.value"
                        class="apply-mode-option"
                        :class="{ 'apply-mode-option--active': applyMode === option.value }"
                    >
                        <input
                            v-model="applyMode"
                            type="radio"
                            name="nujoom-apply-mode"
                            :value="option.value"
                            :disabled="previewing || applying"
                        />
                        <span class="apply-mode-label">{{ option.label }}</span>
                        <span class="apply-mode-hint">{{ option.hint }}</span>
                    </label>
                </div>
            </div>

            <div v-if="preview.errors?.length" class="preview-section">
                <h4>أخطاء</h4>
                <ul class="error-list">
                    <li v-for="(err, idx) in preview.errors" :key="`err-${idx}`">
                        صف {{ err.row }}: {{ err.message }}
                    </li>
                </ul>
            </div>

            <div v-if="preview.containers_new?.length" class="preview-section">
                <h4>حاويات جديدة</h4>
                <DataTable :value="preview.containers_new" size="small" striped-rows>
                    <Column field="container_number" header="رقم الحاوية" />
                    <Column field="booking_number" header="الحجز" />
                    <Column field="loading_point" header="ميناء التحميل" />
                    <Column field="destination" header="الوجهة" />
                    <Column field="vehicle_count" header="عدد السيارات" />
                </DataTable>
            </div>

            <div v-if="preview.to_add?.length" class="preview-section">
                <h4>سيارات جديدة ({{ preview.to_add.length }})</h4>
                <DataTable :value="preview.to_add" size="small" striped-rows scrollable scroll-height="200px">
                    <Column field="vin" header="الشاصي" />
                    <Column header="المركبة">
                        <template #body="{ data }">
                            {{ [data.year, data.make, data.model].filter(Boolean).join(' ') }}
                        </template>
                    </Column>
                    <Column field="destination" header="الوجهة" />
                    <Column field="container_number" header="الحاوية" />
                </DataTable>
            </div>

            <div v-if="preview.to_update?.length" class="preview-section">
                <h4>سيارات للتحديث ({{ preview.to_update.length }})</h4>
                <DataTable :value="preview.to_update" size="small" striped-rows scrollable scroll-height="200px">
                    <Column field="vin" header="الشاصي" />
                    <Column header="المركبة">
                        <template #body="{ data }">
                            {{ [data.year, data.make, data.model].filter(Boolean).join(' ') }}
                        </template>
                    </Column>
                    <Column field="existing_source" header="المصدر الحالي" />
                    <Column field="destination" header="الوجهة" />
                </DataTable>
            </div>
        </div>

        <template #footer>
            <Button label="إلغاء" text :disabled="previewing || applying" @click="close" />
            <Button
                v-if="! preview"
                label="معاينة"
                icon="pi pi-eye"
                :loading="previewing"
                :disabled="! selectedFile"
                @click="runPreview"
            />
            <Button
                v-else
                :label="applyButtonLabel"
                icon="pi pi-check"
                class="btn-add"
                :loading="applying"
                :disabled="! canApply"
                @click="confirmApply"
            />
        </template>
    </Dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import api from '../api/client';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:visible', 'applied']);

const confirm = useConfirm();
const toast = useToast();

const visibleModel = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});

const fileInput = ref(null);
const selectedFile = ref(null);
const preview = ref(null);
const previewing = ref(false);
const applying = ref(false);
const applyMode = ref('all');

const applyModeOptions = [
    {
        value: 'all',
        label: 'تطبيق الكل',
        hint: 'إضافة الجديد وتحديث الموجود',
    },
    {
        value: 'updates_only',
        label: 'تحديث السيارات فقط',
        hint: 'تخطي السيارات الجديدة — تحديث الشاصيات الموجودة فقط',
    },
    {
        value: 'add_only',
        label: 'إضافة الجديد فقط',
        hint: 'تخطي التحديثات — إضافة السيارات الجديدة فقط',
    },
];

const canApply = computed(() => {
    if (! preview.value) {
        return false;
    }

    const counts = preview.value.counts ?? {};

    if (applyMode.value === 'updates_only') {
        return (counts.to_update ?? 0) > 0;
    }

    if (applyMode.value === 'add_only') {
        return (counts.to_add ?? 0) > 0;
    }

    return (counts.to_add ?? 0) + (counts.to_update ?? 0) > 0;
});

const applyButtonLabel = computed(() => {
    if (applyMode.value === 'updates_only') {
        return 'تحديث السيارات فقط';
    }

    if (applyMode.value === 'add_only') {
        return 'إضافة الجديد فقط';
    }

    return 'تطبيق الاستيراد';
});

watch(() => props.visible, (open) => {
    if (! open) {
        reset();
    }
});

function onFileChange(event) {
    selectedFile.value = event.target.files?.[0] ?? null;
    preview.value = null;
}

function reset() {
    selectedFile.value = null;
    preview.value = null;
    previewing.value = false;
    applying.value = false;
    applyMode.value = 'all';

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function close() {
    visibleModel.value = false;
}

async function runPreview() {
    if (! selectedFile.value) {
        return;
    }

    previewing.value = true;

    try {
        const form = new FormData();
        form.append('file', selectedFile.value);

        const { data } = await api.post('/admin/vehicles/import/nujoom/preview', form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        preview.value = data.data;
        toast.add({ severity: 'info', summary: 'تمت المعاينة', life: 2500 });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'تعذّرت معاينة الملف',
            life: 4000,
        });
    } finally {
        previewing.value = false;
    }
}

function confirmApply() {
    const counts = preview.value?.counts ?? {};

    let message;

    if (applyMode.value === 'updates_only') {
        message = `تأكيد تحديث ${counts.to_update ?? 0} سيارة موجودة فقط (بدون إضافة جديد)؟`;
    } else if (applyMode.value === 'add_only') {
        message = `تأكيد إضافة ${counts.to_add ?? 0} سيارة جديدة و${counts.containers_new ?? 0} حاوية (بدون تحديث)؟`;
    } else {
        message = `تأكيد الاستيراد: ${counts.to_add ?? 0} جديد، ${counts.to_update ?? 0} تحديث، ${counts.containers_new ?? 0} حاوية جديدة؟`;
    }

    confirm.require({
        message,
        header: 'تأكيد الاستيراد',
        icon: 'pi pi-upload',
        rejectLabel: 'إلغاء',
        acceptLabel: 'تطبيق',
        accept: () => runApply(),
    });
}

async function runApply() {
    if (! preview.value?.preview_token) {
        return;
    }

    applying.value = true;

    try {
        const { data } = await api.post('/admin/vehicles/import/nujoom/apply', {
            preview_token: preview.value.preview_token,
            confirmed: true,
            mode: applyMode.value,
        });

        toast.add({
            severity: 'success',
            summary: 'تم الاستيراد',
            detail: data.message,
            life: 5000,
        });

        emit('applied');
        close();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل تطبيق الاستيراد',
            life: 4000,
        });
    } finally {
        applying.value = false;
    }
}
</script>

<style scoped>
.import-lead {
    margin: 0 0 1rem;
    color: var(--vs-text-muted, #64748b);
    line-height: 1.5;
}

.file-input {
    width: 100%;
}

.selected-file {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding: 0.65rem 0.85rem;
    border-radius: 8px;
    background: var(--vs-surface-muted, #f8fafc);
    color: var(--vs-text, #0f172a);
}

.import-counts {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.count-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
}

.count-badge--add {
    background: #dcfce7;
    color: #166534;
}

.count-badge--update {
    background: #dbeafe;
    color: #1d4ed8;
}

.count-badge--container {
    background: #fef3c7;
    color: #b45309;
}

.count-badge--error {
    background: #fee2e2;
    color: #b91c1c;
}

.preview-section {
    margin-bottom: 1rem;
}

.preview-section h4 {
    margin: 0 0 0.5rem;
    font-size: 0.95rem;
}

.error-list {
    margin: 0;
    padding-inline-start: 1.25rem;
    color: #b91c1c;
}

.apply-mode {
    margin-bottom: 1rem;
    padding: 0.85rem 1rem;
    border-radius: 10px;
    background: var(--vs-surface-muted, #f8fafc);
    border: 1px solid var(--vs-border, #e2e8f0);
}

.apply-mode h4 {
    margin: 0 0 0.65rem;
    font-size: 0.95rem;
}

.apply-mode-options {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.apply-mode-option {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem 0.65rem;
    padding: 0.65rem 0.75rem;
    border-radius: 8px;
    border: 1px solid transparent;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}

.apply-mode-option:hover {
    background: #fff;
}

.apply-mode-option--active {
    background: #fff;
    border-color: #93c5fd;
}

.apply-mode-option input {
    margin: 0;
}

.apply-mode-label {
    font-weight: 600;
    color: var(--vs-text, #0f172a);
}

.apply-mode-hint {
    flex-basis: 100%;
    padding-inline-start: 1.35rem;
    font-size: 0.82rem;
    color: var(--vs-text-muted, #64748b);
}
</style>
