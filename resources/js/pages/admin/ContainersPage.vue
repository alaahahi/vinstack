<template>

    <div class="admin-page">

        <AdminPageHeader :count="total" :count-label="t('containers.countLabel')">

            <template #actions>

                <Button icon="pi pi-refresh" :label="t('actions.refresh')" outlined :loading="loading" @click="resetAndLoad" />

            </template>

            <template #filters>

                <IconField>

                    <InputIcon class="pi pi-box" />

                    <InputText

                        v-model="containerSearch"

                        :placeholder="t('containers.searchContainer')"

                        @keyup.enter="resetAndLoad"

                    />

                </IconField>

                <IconField>

                    <InputIcon class="pi pi-search" />

                    <InputText

                        v-model="chassisSearch"

                        :placeholder="t('containers.searchChassis')"

                        @keyup.enter="resetAndLoad"

                    />

                </IconField>

                <Button
                    icon="pi pi-search"
                    :label="t('actions.search')"
                    @click="resetAndLoad"
                />

                <Button
                    v-if="hasActiveFilters"
                    icon="pi pi-filter-slash"
                    :label="t('actions.clearFilters')"
                    severity="secondary"
                    outlined
                    @click="clearFilters"
                />

                <DealerFilterBadges

                    :dealers="dealerSummary"

                    :selected-id="dealerFilter"

                    count-key="containers_count"

                    :total-count="total"

                    @select="onDealerBadgeSelect"

                />

            </template>

        </AdminPageHeader>



        <ContainerListPanel

            :containers="items"

            :loading="loading"

            :loading-more="loadingMore"

            infinite-scroll

            :has-more="hasMore"

            :tracking-available="trackingAvailable"

            direct-image-gallery

            show-zip-upload

            @track="openTracking"

            @show-cars="openCars"

            @load-more="loadMore"

        />



        <ContainerTrackingDialog

            v-model:visible="trackingVisible"

            :container="trackingContainer"

            api-role="admin"

        />

        <ContainerCarsDialog

            v-model:visible="carsVisible"

            :container="carsContainer"

            api-role="admin"

            show-zip-upload

        />

    </div>

</template>



<script setup>

import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';

import IconField from 'primevue/iconfield';

import InputIcon from 'primevue/inputicon';

import InputText from 'primevue/inputtext';

import Button from 'primevue/button';

import AdminPageHeader from '../../components/AdminPageHeader.vue';

import DealerFilterBadges from '../../components/DealerFilterBadges.vue';

import ContainerListPanel from '../../components/ContainerListPanel.vue';

import ContainerTrackingDialog from '../../components/ContainerTrackingDialog.vue';

import ContainerCarsDialog from '../../components/ContainerCarsDialog.vue';

import api from '../../api/client';



const { t } = useI18n();
const toast = useToast();

const items = ref([]);

const dealerSummary = ref([]);

const loading = ref(false);

const loadingMore = ref(false);

const chassisSearch = ref('');

const containerSearch = ref('');

const dealerFilter = ref(null);

const page = ref(1);

const perPage = ref(50);

const total = ref(0);

const hasMore = ref(false);

const trackingAvailable = ref(false);

const trackingVisible = ref(false);

const trackingContainer = ref(null);

const carsVisible = ref(false);

const carsContainer = ref(null);

const hasActiveFilters = computed(() =>
    Boolean(containerSearch.value.trim() || chassisSearch.value.trim() || dealerFilter.value),
);

function listParams(nextPage = page.value) {

    return {

        page: nextPage,

        per_page: perPage.value,

        container: containerSearch.value.trim() || undefined,

        chassis: chassisSearch.value.trim() || undefined,

        dealer_id: dealerFilter.value || undefined,

    };

}



async function loadDealerSummary() {

    const { data } = await api.get('/admin/dealers/summary');

    dealerSummary.value = data.data ?? [];

}



async function fetchPage(nextPage, append = false) {

    const isFirstPage = nextPage === 1 && ! append;



    if (isFirstPage) {

        loading.value = true;

    } else {

        loadingMore.value = true;

    }



    try {

        const { data } = await api.get('/admin/containers', {

            params: listParams(nextPage),

        });

        const rows = data.data ?? [];



        items.value = append ? [...items.value, ...rows] : rows;

        total.value = data.meta?.total ?? data.total ?? rows.length;

        hasMore.value = Boolean(data.meta?.has_more);

        trackingAvailable.value = Boolean(data.tracking_available);

        page.value = nextPage;

    } catch (e) {

        if (! append) {

            items.value = [];

            total.value = 0;

            hasMore.value = false;

        }



        toast.add({

            severity: 'error',

            summary: 'خطأ',

            detail: e.response?.data?.message || 'تعذّر جلب الحاويات',

            life: 4000,

        });

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

function clearFilters() {
    containerSearch.value = '';
    chassisSearch.value = '';
    dealerFilter.value = null;
    resetAndLoad();
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



function openTracking(container) {

    trackingContainer.value = container;

    trackingVisible.value = true;

}

function openCars(container) {

    carsContainer.value = container;

    carsVisible.value = true;

}



onMounted(resetAndLoad);

</script>

