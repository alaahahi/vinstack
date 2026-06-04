<template>
    <div class="route-status">
        <div v-if="route" class="route">{{ route }}</div>
        <div v-else class="empty">—</div>
        <div v-if="status || shipping" class="status-line">
            <Tag v-if="status" :value="status" severity="info" />
            <span v-if="shipping" class="shipping">{{ shipping }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Tag from 'primevue/tag';
import {
    vehicleRouteText,
    vehicleShippingMethod,
    vehicleVinstackStatus,
} from '../utils/vehicleMeta';

const props = defineProps({
    vehicle: {
        type: Object,
        required: true,
    },
});

const route = computed(() => vehicleRouteText(props.vehicle));
const status = computed(() => vehicleVinstackStatus(props.vehicle));
const shipping = computed(() => vehicleShippingMethod(props.vehicle));
</script>

<style scoped>
.route-status {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-width: 9rem;
}

.route {
    font-weight: 500;
    line-height: 1.3;
}

.status-line {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
}

.shipping {
    color: #71717a;
    font-size: 0.82rem;
}

.empty {
    color: #a1a1aa;
}
</style>
