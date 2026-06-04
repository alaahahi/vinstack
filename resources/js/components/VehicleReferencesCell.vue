<template>
    <div class="references">
        <template v-if="items.length">
            <div v-for="item in items" :key="item.label" class="ref-row">
                <span class="label">{{ item.label }}</span>
                <span class="value">{{ item.value }}</span>
            </div>
        </template>
        <span v-else class="empty">—</span>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { vehicleReferences } from '../utils/vehicleMeta';

const props = defineProps({
    vehicle: {
        type: Object,
        required: true,
    },
});

const items = computed(() => vehicleReferences(props.vehicle));
</script>

<style scoped>
.references {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    min-width: 10rem;
    max-width: 16rem;
}

.ref-row {
    display: flex;
    flex-direction: column;
    gap: 0.05rem;
    line-height: 1.25;
}

.label {
    color: #71717a;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.value {
    font-size: 0.88rem;
    word-break: break-word;
}

.empty {
    color: #a1a1aa;
}
</style>
