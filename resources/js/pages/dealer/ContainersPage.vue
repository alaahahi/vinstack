<template>

    <div class="containers-page">

        <div class="toolbar">

            <div>

                <h2 class="section-title">حاوياتي</h2>

                <p class="subtitle">حاويات تحتوي على سياراتك المسندة — استخدم زر التتبع لمتابعة المسار</p>

            </div>

            <Button icon="pi pi-refresh" label="تحديث" outlined :loading="loading" @click="load" />

        </div>



        <ContainerListPanel

            :containers="items"

            :loading="loading"

            :tracking-available="trackingAvailable"

            :show-invoice="false"

            empty-text="لا توجد حاويات مرتبطة بسياراتك حالياً"

            empty-action-label="عرض سياراتي"

            @track="openTracking"

            @empty-action="goVehicles"

        />



        <ContainerTrackingDialog

            v-model:visible="trackingVisible"

            :container="trackingContainer"

            api-role="dealer"

        />

    </div>

</template>



<script setup>

import { onMounted, ref } from 'vue';

import { useRouter } from 'vue-router';

import { useToast } from 'primevue/usetoast';

import Button from 'primevue/button';

import ContainerListPanel from '../../components/ContainerListPanel.vue';

import ContainerTrackingDialog from '../../components/ContainerTrackingDialog.vue';

import api from '../../api/client';



const toast = useToast();

const router = useRouter();

const items = ref([]);

const loading = ref(false);

const trackingAvailable = ref(false);

const trackingVisible = ref(false);

const trackingContainer = ref(null);



async function load() {

    loading.value = true;



    try {

        const { data } = await api.get('/dealer/containers');

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



function goVehicles() {

    router.push({ name: 'dealer.vehicles' });

}



onMounted(load);

</script>



<style scoped>

.containers-page {

    max-width: 1400px;

}



.toolbar {

    display: flex;

    align-items: flex-start;

    gap: 1rem;

    margin-bottom: 1rem;

    flex-wrap: wrap;

}



.section-title {

    margin: 0 0 0.25rem;

    font-size: 1.1rem;

    font-weight: 600;

}



.subtitle {

    margin: 0;

    font-size: 0.85rem;

    color: var(--vs-text-muted);

    max-width: 36rem;

}

</style>

