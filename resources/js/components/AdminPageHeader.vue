<template>
    <header class="admin-page-header">
        <div v-if="subtitle || $slots.lead" class="admin-page-header__lead">
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
