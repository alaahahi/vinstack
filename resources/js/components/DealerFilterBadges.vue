<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    dealers: {
        type: Array,
        default: () => [],
    },
    selectedId: {
        type: [Number, null],
        default: null,
    },
    countKey: {
        type: String,
        default: 'vehicles_count',
    },
    showAll: {
        type: Boolean,
        default: true,
    },
    allLabel: {
        type: String,
        default: '',
    },
    totalCount: {
        type: Number,
        default: null,
    },
    ariaLabel: {
        type: String,
        default: '',
    },
});

defineEmits(['select']);
</script>

<template>
    <div v-if="dealers.length" class="dealer-badges" role="group" :aria-label="ariaLabel || t('dealerFilters.filterByDealer')">
        <button
            v-if="showAll"
            type="button"
            class="dealer-badge"
            :class="{ 'dealer-badge--active': selectedId == null }"
            @click="$emit('select', null)"
        >
            {{ allLabel || t('dealerFilters.all') }}
            <span v-if="totalCount != null" class="dealer-badge__count">{{ totalCount }}</span>
        </button>
        <button
            v-for="dealer in dealers"
            :key="dealer.id"
            type="button"
            class="dealer-badge"
            :class="{ 'dealer-badge--active': selectedId === dealer.id }"
            @click="$emit('select', dealer.id)"
        >
            {{ dealer.company_name }}
            <span class="dealer-badge__count">{{ dealer[countKey] ?? 0 }}</span>
        </button>
    </div>
</template>

<style scoped>
.dealer-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    width: 100%;
    margin-top: 0.25rem;
}

.dealer-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
    border: 1px solid var(--admin-border, rgba(22, 163, 74, 0.2));
    background: var(--admin-surface);
    color: var(--vs-text-secondary);
    font-size: 0.78rem;
    font-weight: 500;
    line-height: 1.4;
    cursor: pointer;
    transition: background 0.12s ease, border-color 0.12s ease, color 0.12s ease;
    min-height: 32px;
}

.dealer-badge:hover {
    background: var(--vs-surface-hover);
    color: var(--vs-text);
}

.dealer-badge--active {
    color: var(--admin-accent, #15803d);
    background: var(--admin-sidebar-active, rgba(22, 163, 74, 0.12));
    border-color: var(--admin-accent, #15803d);
}

.dealer-badge__count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    padding: 0 0.35rem;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 700;
    background: rgba(0, 0, 0, 0.06);
}

.dealer-badge--active .dealer-badge__count {
    background: rgba(22, 163, 74, 0.18);
}
</style>
