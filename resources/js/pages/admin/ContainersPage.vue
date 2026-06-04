<template>
    <div class="admin-page">
        <AdminPageHeader>
            <template #actions>
                <Button icon="pi pi-refresh" label="تحديث" outlined :loading="loading" @click="load" />
            </template>
        </AdminPageHeader>

        <ContainerListPanel
            :containers="items"
            :loading="loading"
            :tracking-available="trackingAvailable"
            @track="openTracking"
        />

        <ContainerTrackingDialog
            v-model:visible="trackingVisible"
            :container="trackingContainer"
            api-role="admin"
        />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import AdminPageHeader from '../../components/AdminPageHeader.vue';
import ContainerListPanel from '../../components/ContainerListPanel.vue';
import ContainerTrackingDialog from '../../components/ContainerTrackingDialog.vue';
import api from '../../api/client';

const toast = useToast();
const items = ref([]);
const loading = ref(false);
const trackingAvailable = ref(false);
const trackingVisible = ref(false);
const trackingContainer = ref(null);

async function load() {
    loading.value = true;

    try {
        const { data } = await api.get('/admin/containers');
        items.value = data.data ?? [];
        trackingAvailable.value = Boolean(data.tracking_available);
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'تعذّر جلب الحاويات',
            life: 4000,
        });
    } finally {
        loading.value = false;
    }
}

function openTracking(container) {
    trackingContainer.value = container;
    trackingVisible.value = true;
}

onMounted(load);
</script>

