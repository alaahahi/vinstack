<template>
    <Drawer
        v-model:visible="drawerVisible"
        position="right"
        :style="{ width: 'min(480px, 100vw)' }"
        class="vehicle-detail-drawer"
        :showCloseIcon="false"
        @hide="onHide"
    >
        <template #header>
            <div v-if="detail" class="drawer-header">
                <div class="drawer-header-top">
                    <h2 class="drawer-title">{{ detail.title || '—' }}</h2>
                    <Button icon="pi pi-times" text rounded severity="secondary" aria-label="Close" @click="close" />
                </div>
                <div class="drawer-header-meta">
                    <span v-if="detail.status" class="status-pill" :class="statusClass">
                        <span class="status-dot" />
                        {{ detail.status }}
                    </span>
                    <Tag v-if="detail.local_status" :value="detail.local_status" class="local-tag" />
                </div>
                <VinCopyLabel :vin="detail.vin" class="drawer-vin" />
                <div v-if="!detail.vinstack_fresh && detail.source !== 'manual'" class="stale-note">
                    <i class="pi pi-info-circle" />
                    Showing cached data — Vinstack live fetch unavailable.
                </div>
            </div>
            <div v-else class="drawer-header drawer-header--loading">
                <Skeleton width="70%" height="1.4rem" />
                <Skeleton width="40%" height="1rem" class="mt-sm" />
            </div>
        </template>

        <div v-if="loading" class="drawer-loading">
            <ProgressSpinner style="width: 36px; height: 36px" />
        </div>

        <div v-else-if="error" class="drawer-error">
            <i class="pi pi-exclamation-circle" />
            <span>{{ error }}</span>
            <Button label="Retry" size="small" outlined @click="load" />
        </div>

        <div v-else-if="detail" class="drawer-body">
            <VehiclePhotosPanel
                :vehicle="photosVehicle"
                :admin-mode="isAdminPhotos"
                @updated="onPhotosUpdated"
            />

            <section
                v-for="section in detail.sections"
                :key="section.key"
                class="detail-section"
            >
                <h3 class="section-title">{{ section.title }}</h3>
                <dl class="field-grid">
                    <template v-for="field in section.fields" :key="`${section.key}-${field.key}`">
                        <dt>{{ field.label }}</dt>
                        <dd>{{ formatField(field) }}</dd>
                    </template>
                </dl>
            </section>

            <section v-if="detail.assignment?.dealer_name" class="detail-section">
                <h3 class="section-title">Assignment</h3>
                <dl class="field-grid">
                    <dt>Dealer</dt>
                    <dd>{{ detail.assignment.dealer_name }}</dd>
                    <dt>Assigned</dt>
                    <dd>{{ formatDate(detail.assignment.assigned_at) }}</dd>
                </dl>
            </section>

            <section v-if="mode !== 'dealer'" class="detail-section">
                <h3 class="section-title">Invoices</h3>
                <div v-if="detail.invoices?.length" class="record-list">
                    <div v-for="(invoice, index) in detail.invoices" :key="invoice.id ?? index" class="record-item">
                        <div class="record-title">{{ invoiceLabel(invoice) }}</div>
                        <div v-if="invoiceSubtitle(invoice)" class="record-sub">{{ invoiceSubtitle(invoice) }}</div>
                    </div>
                </div>
                <p v-else class="empty-section">—</p>
            </section>

            <section class="detail-section">
                <h3 class="section-title">Documents</h3>
                <div v-if="detail.documents?.length" class="record-list">
                    <a
                        v-for="(doc, index) in detail.documents"
                        :key="doc.id ?? doc.url ?? index"
                        :href="doc.url || doc.link || '#'"
                        class="record-item record-link"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <i class="pi pi-file" />
                        <span>{{ documentLabel(doc) }}</span>
                    </a>
                </div>
                <p v-else class="empty-section">—</p>
            </section>
        </div>
    </Drawer>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import Drawer from 'primevue/drawer';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Skeleton from 'primevue/skeleton';
import ProgressSpinner from 'primevue/progressspinner';
import VehiclePhotosPanel from './VehiclePhotosPanel.vue';
import VinCopyLabel from './VinCopyLabel.vue';
import api from '../api/client';
import { formatVehicleDate, vehicleStatusClass } from '../utils/vehicleMeta';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    vehicleId: {
        type: [Number, String],
        default: null,
    },
    mode: {
        type: String,
        default: 'admin',
        validator: (v) => ['admin', 'dealer'].includes(v),
    },
});

const emit = defineEmits(['update:visible']);

const loading = ref(false);
const error = ref(null);
const detail = ref(null);

const drawerVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});

const statusClass = computed(() => vehicleStatusClass(detail.value?.status));

const isAdminPhotos = computed(() => props.mode === 'admin');

const photosVehicle = computed(() => {
    const data = detail.value ?? {};

    return {
        id: data.id ?? props.vehicleId,
        vin: data.vin,
        year: data.year,
        make: data.make,
        model: data.model,
        images: data.images ?? [],
        images_by_stage: data.images_by_stage,
        uploaded_images: data.uploaded_images ?? [],
        raw_data: {
            thumbnail_url: data.thumbnail_url,
            images: data.images,
            images_by_stage: data.images_by_stage,
            uploaded_images: data.uploaded_images,
        },
    };
});

function onPhotosUpdated(vehiclePayload) {
    if (! vehiclePayload || ! detail.value) {
        return;
    }

    detail.value = {
        ...detail.value,
        images: vehiclePayload.images ?? detail.value.images,
        images_by_stage: vehiclePayload.images_by_stage ?? detail.value.images_by_stage,
        uploaded_images: vehiclePayload.uploaded_images ?? detail.value.uploaded_images,
        thumbnail_url: vehiclePayload.thumbnail_url ?? detail.value.thumbnail_url,
    };
}

const detailsPath = computed(() => {
    const prefix = props.mode === 'dealer' ? '/dealer/vehicles' : '/admin/vehicles';

    return `${prefix}/${props.vehicleId}/details`;
});

watch(
    () => [props.visible, props.vehicleId],
    ([visible, vehicleId]) => {
        if (visible && vehicleId) {
            load();
        }

        if (! visible) {
            detail.value = null;
            error.value = null;
        }
    },
);

async function load() {
    if (! props.vehicleId) {
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        const { data } = await api.get(detailsPath.value);
        detail.value = data.data;
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to load vehicle details.';
        detail.value = null;
    } finally {
        loading.value = false;
    }
}

function close() {
    drawerVisible.value = false;
}

function onHide() {
    detail.value = null;
    error.value = null;
}

function formatDate(value) {
    return formatVehicleDate(value) || '—';
}

function formatField(field) {
    if (field.value === null || field.value === undefined || field.value === '') {
        return '—';
    }

    if (field.type === 'date') {
        return formatVehicleDate(field.value) || '—';
    }

    if (field.type === 'money') {
        const num = Number(field.value);

        if (Number.isFinite(num)) {
            return new Intl.NumberFormat(undefined, {
                style: 'currency',
                currency: 'USD',
                maximumFractionDigits: 0,
            }).format(num);
        }
    }

    return String(field.value);
}

function invoiceLabel(invoice) {
    return invoice.number || invoice.invoice_number || invoice.id || 'Invoice';
}

function invoiceSubtitle(invoice) {
    const parts = [invoice.status, invoice.amount, invoice.date || invoice.created_at].filter(Boolean);

    return parts.length ? parts.join(' · ') : null;
}

function documentLabel(doc) {
    return doc.name || doc.title || doc.filename || doc.type || 'Document';
}
</script>

<style scoped>
.drawer-header {
    width: 100%;
    padding-right: 0.25rem;
}

.drawer-header-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

.drawer-title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: #18181b;
    line-height: 1.35;
}

.drawer-header-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.65rem;
}

.drawer-vin {
    margin-top: 0.45rem;
}

.stale-note {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-top: 0.65rem;
    padding: 0.45rem 0.6rem;
    border-radius: 8px;
    background: #fff7ed;
    color: #9a3412;
    font-size: 0.75rem;
}

.drawer-loading,
.drawer-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 2rem 1rem;
    color: #71717a;
}

.drawer-body {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    padding-bottom: 1rem;
}

.detail-section {
    border-top: 1px solid #ececef;
    padding-top: 1rem;
}

.section-title {
    margin: 0 0 0.75rem;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #71717a;
}

.field-grid {
    display: grid;
    grid-template-columns: minmax(120px, 42%) 1fr;
    gap: 0.45rem 0.75rem;
    margin: 0;
}

.field-grid dt {
    margin: 0;
    font-size: 0.78rem;
    color: #71717a;
}

.field-grid dd {
    margin: 0;
    font-size: 0.84rem;
    color: #18181b;
    word-break: break-word;
}

.empty-section {
    margin: 0;
    color: #a1a1aa;
    font-size: 0.84rem;
}

.record-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.record-item {
    padding: 0.55rem 0.65rem;
    border: 1px solid #ececef;
    border-radius: 8px;
    background: #fafafa;
}

.record-title {
    font-size: 0.84rem;
    font-weight: 600;
    color: #18181b;
}

.record-sub {
    margin-top: 0.15rem;
    font-size: 0.75rem;
    color: #71717a;
}

.record-link {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    color: #2563eb;
    text-decoration: none;
    font-size: 0.84rem;
}

.record-link:hover {
    background: #eff6ff;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 500;
}

.status-terminal,
.status-default {
    background: #eff6ff;
    color: #2563eb;
}

.status-new {
    background: #f0fdf4;
    color: #16a34a;
}

.status-transit {
    background: #fff7ed;
    color: #ea580c;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.local-tag {
    font-size: 0.72rem;
}

.mt-sm {
    margin-top: 0.5rem;
}

@media (max-width: 640px) {
    .drawer-title {
        font-size: 1rem;
    }

    .field-grid {
        grid-template-columns: 1fr;
        gap: 0.2rem 0;
    }

    .field-grid dt {
        font-weight: 600;
        margin-top: 0.35rem;
    }

    .field-grid dt:first-of-type {
        margin-top: 0;
    }
}
</style>
