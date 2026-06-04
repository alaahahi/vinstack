<template>
    <header class="admin-page-header">
        <div v-if="subtitle || count != null || $slots.lead" class="admin-page-header__lead">
            <div v-if="count != null" class="admin-page-header__count-row">
                <span class="admin-page-header__count-badge">
                    {{ count }} {{ countLabel }}
                </span>
            </div>
            <p v-if="subtitle" class="admin-page-header__subtitle">{{ subtitle }}</p>
            <slot name="lead" />
        </div>
        <div v-if="$slots.actions" class="admin-page-header__actions">
            <slot name="actions" />
        </div>
        <div v-if="$slots.filters" class="admin-page-header__filters">
            <slot name="filters" />
        </div>
    </header>
</template>

<script setup>
defineProps({
    subtitle: { type: String, default: '' },
    count: { type: Number, default: null },
    countLabel: { type: String, default: 'سيارة' },
});
</script>

<style scoped>
.admin-page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.admin-page-header__lead {
    flex: 1;
    min-width: min(100%, 12rem);
}

.admin-page-header__count-row {
    margin-bottom: 0.35rem;
}

.admin-page-header__count-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.65rem;
    border-radius: 999px;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.4;
    color: var(--admin-accent, #15803d);
    background: var(--admin-sidebar-active, rgba(22, 163, 74, 0.12));
    border: 1px solid var(--admin-border, rgba(22, 163, 74, 0.2));
}

.admin-page-header__subtitle {
    margin: 0;
    font-size: 0.875rem;
    color: var(--vs-text-muted);
    line-height: 1.45;
}

.admin-page-header__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-inline-start: auto;
}

.admin-page-header__filters {
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-sm);
}

.admin-page-header__filters :deep(.p-iconfield) {
    flex: 1;
    min-width: min(100%, 200px);
    max-width: 280px;
}
</style>
