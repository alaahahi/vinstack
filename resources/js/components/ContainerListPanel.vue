<template>
    <div class="container-list-panel">
        <div v-if="showHeader" class="list-header">
            <span>{{ t('containers.columns.images') }}</span>
            <span>{{ t('containers.columns.refs') }}</span>
            <span>{{ t('containers.columns.customer') }}</span>
            <span>{{ t('containers.columns.route') }}</span>
            <span>{{ t('containers.columns.shippingLine') }}</span>
            <span>{{ t('containers.columns.dates') }}</span>
            <span>{{ t('containers.columns.vehicles') }}</span>
            <span>{{ t('containers.columns.status') }}</span>
            <span>{{ showInvoice ? t('containers.columns.bolInvoice') : t('containers.columns.bol') }}</span>
            <span />
        </div>

        <div v-if="loading && !containers.length" class="list-loading">
            <ProgressSpinner style="width: 36px; height: 36px" />
        </div>

        <div v-else-if="!containers.length" class="list-empty">
            <i class="pi pi-box" />
            <span>{{ resolvedEmptyText }}</span>
            <Button
                v-if="emptyActionLabel"
                :label="emptyActionLabel"
                icon="pi pi-car"
                outlined
                size="small"
                @click="emit('empty-action')"
            />
        </div>

        <div v-else class="list-body">
            <ContainerListRow
                v-for="(container, index) in containers"
                :key="containerRowKey(container, index)"
                :container="container"
                :tracking-available="trackingAvailable"
                :show-invoice="showInvoice"
                :show-zip-upload="showZipUpload"
                :direct-image-gallery="directImageGallery"
                :link-container-detail="linkContainerDetail"
                :show-vehicle-thumbs="showVehicleThumbs"
                :api-prefix="apiPrefix"
                @track="$emit('track', $event)"
                @show-cars="$emit('show-cars', $event)"
            />
            <div v-if="infiniteScroll && hasMore" ref="sentinelRef" class="list-sentinel" aria-hidden="true">
                <ProgressSpinner v-if="loadingMore" style="width: 28px; height: 28px" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, toRef } from 'vue';
import { useI18n } from 'vue-i18n';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import ContainerListRow from './ContainerListRow.vue';
import { containerRowKey } from '../utils/containerMeta';
import { useInfiniteScroll } from '../composables/useInfiniteScroll';

const { t } = useI18n();

const props = defineProps({
    containers: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    loadingMore: {
        type: Boolean,
        default: false,
    },
    trackingAvailable: {
        type: Boolean,
        default: false,
    },
    emptyText: {
        type: String,
        default: '',
    },
    showHeader: {
        type: Boolean,
        default: true,
    },
    showInvoice: {
        type: Boolean,
        default: true,
    },
    showZipUpload: {
        type: Boolean,
        default: false,
    },
    directImageGallery: {
        type: Boolean,
        default: false,
    },
    linkContainerDetail: {
        type: Boolean,
        default: false,
    },
    showVehicleThumbs: {
        type: Boolean,
        default: false,
    },
    apiPrefix: {
        type: String,
        default: '/admin',
    },
    emptyActionLabel: {
        type: String,
        default: '',
    },
    infiniteScroll: {
        type: Boolean,
        default: false,
    },
    hasMore: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['track', 'show-cars', 'empty-action', 'load-more']);

const resolvedEmptyText = computed(() => props.emptyText || t('containers.empty'));

const { sentinel } = useInfiniteScroll({
    enabled: toRef(props, 'infiniteScroll'),
    hasMore: toRef(props, 'hasMore'),
    loading: toRef(props, 'loadingMore'),
    onLoadMore: () => emit('load-more'),
});

const sentinelRef = sentinel;
</script>

<style scoped>
.container-list-panel {
    background: var(--admin-surface);
    border: 1px solid var(--vs-border);
    border-radius: var(--admin-radius);
    overflow: hidden;
    box-shadow: var(--admin-shadow);
}

.list-header {
    display: grid;
    grid-template-columns: 64px minmax(150px, 1.1fr) minmax(120px, 0.9fr) minmax(130px, 1fr) minmax(110px, 0.85fr) minmax(120px, 0.8fr) minmax(180px, 1.15fr) minmax(90px, 0.55fr) minmax(100px, 0.7fr) minmax(48px, 0.4fr);
    gap: 1rem;
    padding: 0.75rem 1.25rem;
    background: var(--vs-surface-elevated);
    border-bottom: 1px solid var(--vs-border);
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--vs-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.list-body :deep(.container-row:last-child) {
    border-bottom: none;
}

.list-loading,
.list-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 3rem 1rem;
    color: var(--vs-text-muted);
}

.list-empty i {
    font-size: 2rem;
    opacity: 0.4;
}

.list-sentinel {
    display: flex;
    justify-content: center;
    padding: 1rem;
    min-height: 3rem;
}

@media (max-width: 1200px) {
    .list-header {
        display: none;
    }
}
</style>
