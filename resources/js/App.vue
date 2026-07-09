<template>
    <Toast />
    <ConfirmDialog />
    <VehicleUploadProgressDock />
    <RouterView />
</template>

<script setup>
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import VehicleUploadProgressDock from './components/VehicleUploadProgressDock.vue';
</script>
