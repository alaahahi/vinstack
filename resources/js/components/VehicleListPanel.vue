<template>
    <div class="vehicle-list-panel">
        <div v-if="showHeader" class="list-header">
            <span>Vehicle</span>
            <span>ID &amp; source</span>
            <span>Route &amp; status</span>
            <span>References</span>
            <span>Dates</span>
            <span>{{ mode === 'admin' ? 'Assignment' : 'Actions' }}</span>
        </div>

        <div v-if="loading" class="list-loading">
            <ProgressSpinner style="width: 36px; height: 36px" />
        </div>

        <div v-else-if="!vehicles.length" class="list-empty">
            <i class="pi pi-car" />
            <span>{{ emptyText }}</span>
            <p v-if="emptyHint" class="empty-hint">{{ emptyHint }}</p>
            <Button
                v-if="emptyActionLabel"
                :label="emptyActionLabel"
                icon="pi pi-refresh"
                outlined
                size="small"
                @click="$emit('empty-action')"
            />
        </div>

        <div v-else class="list-body">
            <VehicleListRow
                v-for="vehicle in vehicles"
                :key="vehicle.id"
                :vehicle="vehicle"
                :mode="mode"
                @assign="$emit('assign', $event)"
                @update-status="$emit('update-status', $event)"
                @open-detail="$emit('open-detail', $event)"
            />
        </div>

        <Paginator
            v-if="total > perPage"
            :rows="perPage"
            :total-records="total"
            :first="(page - 1) * perPage"
            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
            class="list-paginator"
            @page="$emit('page', $event)"
        />
    </div>
</template>

<script setup>
import Button from 'primevue/button';
import Paginator from 'primevue/paginator';
import ProgressSpinner from 'primevue/progressspinner';
import VehicleListRow from './VehicleListRow.vue';

defineProps({
    vehicles: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    total: {
        type: Number,
        default: 0,
    },
    page: {
        type: Number,
        default: 1,
    },
    perPage: {
        type: Number,
        default: 15,
    },
    mode: {
        type: String,
        default: 'admin',
    },
    showHeader: {
        type: Boolean,
        default: true,
    },
    emptyText: {
        type: String,
        default: 'لا توجد سيارات مسندة إليك',
    },
    emptyHint: {
        type: String,
        default: '',
    },
    emptyActionLabel: {
        type: String,
        default: '',
    },
});

defineEmits(['assign', 'update-status', 'open-detail', 'page', 'empty-action']);
</script>

<style scoped>
.vehicle-list-panel {
    background: var(--admin-surface);
    border: 1px solid var(--vs-border);
    border-radius: var(--admin-radius);
    overflow: hidden;
    box-shadow: var(--admin-shadow);
}

.list-header {
    display: grid;
    grid-template-columns: minmax(240px, 1.5fr) minmax(110px, 0.85fr) minmax(130px, 1fr) minmax(150px, 1.05fr) minmax(140px, 0.9fr) minmax(120px, 0.75fr);
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

.list-body :deep(.vehicle-row:last-child) {
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

.empty-hint {
    margin: 0;
    font-size: 0.85rem;
    color: var(--vs-text-subtle);
    text-align: center;
    max-width: 28rem;
}

.list-paginator {
    border-top: 1px solid var(--vs-border);
    padding: 0.5rem;
}

@media (max-width: 1100px) {
    .list-header {
        display: none;
    }
}
</style>
