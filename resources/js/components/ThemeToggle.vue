<template>

    <Button

        :icon="toggleIcon"

        :title="toggleTooltip"

        :aria-label="toggleTooltip"

        severity="secondary"

        text

        rounded

        class="theme-toggle"

        @click="theme.toggle()"

    />

</template>



<script setup>

import Button from 'primevue/button';

import { useTheme } from '../composables/useTheme';



const theme = useTheme();

const { toggleIcon, toggleTooltip } = theme;

</script>



<style scoped>

.theme-toggle {

    flex-shrink: 0;

    display: inline-flex;

    justify-content: center;

    align-items: center;

}



.theme-toggle :deep(.p-button) {

    display: inline-flex;

    justify-content: center;

    align-items: center;

    width: 2.5rem;

    height: 2.5rem;

    min-width: 2.5rem;

    min-height: 2.5rem;

    padding: 0;

    border-radius: 50%;

    aspect-ratio: 1;

}



.theme-toggle :deep(.p-button-icon) {

    margin: 0;

}

</style>

