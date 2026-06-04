<template>
    <div class="vin-copy-label" :class="rootClass">
        <span class="vin-copy-label__text">{{ displayVin }}</span>
        <Button
            v-if="copyable"
            icon="pi pi-copy"
            text
            rounded
            severity="secondary"
            class="vin-copy-label__btn"
            aria-label="نسخ رقم الشانصي"
            @click="copyVin"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    vin: {
        type: String,
        default: null,
    },
    size: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'compact'].includes(v),
    },
    block: {
        type: Boolean,
        default: false,
    },
});

const toast = useToast();

const displayVin = computed(() => props.vin?.trim() || '—');
const copyable = computed(() => Boolean(props.vin?.trim()));

const rootClass = computed(() => ({
    'vin-copy-label--compact': props.size === 'compact',
    'vin-copy-label--block': props.block,
}));

async function copyVin() {
    const text = props.vin?.trim();

    if (! text) {
        return;
    }

    try {
        await navigator.clipboard.writeText(text);
        toast.add({
            severity: 'success',
            summary: 'تم نسخ رقم الشانصي',
            life: 3000,
        });
    } catch {
        toast.add({
            severity: 'error',
            summary: 'تعذر نسخ رقم الشانصي',
            life: 3000,
        });
    }
}
</script>

<style scoped>
.vin-copy-label {
    display: inline-flex;
    align-items: center;
    gap: 0.1rem;
    max-width: 100%;
    min-width: 0;
}

.vin-copy-label--block {
    display: flex;
    width: 100%;
}

.vin-copy-label__text {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--vs-text-secondary);
    letter-spacing: 0.02em;
    word-break: break-all;
    line-height: 1.35;
}

.vin-copy-label--compact .vin-copy-label__text {
    font-size: 0.78rem;
}

.vin-copy-label__btn {
    flex-shrink: 0;
    width: 1.65rem !important;
    height: 1.65rem !important;
    padding: 0 !important;
}

.vin-copy-label__btn {
    color: var(--vs-text-muted);
}

.vin-copy-label__btn:hover {
    color: var(--vs-text-secondary);
    background: var(--vs-surface-hover) !important;
}

.vin-copy-label__btn :deep(.p-button-icon) {
    font-size: 0.78rem;
    color: currentColor;
}
</style>
