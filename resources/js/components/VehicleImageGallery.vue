<template>

    <div class="vehicle-gallery">

        <button

            type="button"

            class="thumb-btn"

            :class="{

                empty: !hasPreview,

                clickable: canOpenGallery,

                'thumb-btn--row': variant === 'row',

            }"

            :title="thumbTitle"

            @click="openGallery"

        >

            <img

                v-if="thumbnail"

                :src="thumbnail"

                :alt="label"

                class="thumb-img"

                loading="lazy"

            />

            <span v-else class="thumb-empty">

                <i class="pi pi-image" />

            </span>

            <span v-if="galleryCount > 0" class="count-badge">{{ galleryCount }}</span>

        </button>



        <Button

            v-if="showButton"

            :label="buttonLabel"

            icon="pi pi-images"

            size="small"

            severity="secondary"

            text

            :disabled="!canOpenGallery"

            @click="openGallery"

        />



        <VehicleGalleryLightbox v-model:visible="visible" :vehicle="lightboxVehicle" />

    </div>

</template>



<script setup>

import { computed, ref } from 'vue';
import {
    fetchLiveVehicleGallery,
    mergeGalleryIntoVehicle,
    vehicleUsesLiveGallery,
} from '../utils/vehicleGalleryLive';

import Button from 'primevue/button';

import VehicleGalleryLightbox from './VehicleGalleryLightbox.vue';

import {

    hasVehicleGallery,

    hasVehiclePreview,

    vehicleGalleryCount,

    vehicleLabel,

    vehicleThumbnail,

} from '../utils/vehicleImages';



const props = defineProps({

    vehicle: {

        type: Object,

        required: true,

    },

    showButton: {

        type: Boolean,

        default: false,

    },

    variant: {

        type: String,

        default: 'table',

        validator: (v) => ['table', 'row'].includes(v),

    },

    apiMode: {

        type: String,

        default: 'admin',

        validator: (value) => ['admin', 'dealer'].includes(value),

    },

});



const visible = ref(false);

const lightboxVehicle = ref(null);

const galleryFetching = ref(false);



const thumbnail = computed(() => vehicleThumbnail(props.vehicle));

const galleryCount = computed(() => vehicleGalleryCount(props.vehicle));

const label = computed(() => vehicleLabel(props.vehicle));

const hasPreview = computed(() => hasVehiclePreview(props.vehicle));

const canOpenGallery = computed(() => hasVehicleGallery(props.vehicle));



const thumbTitle = computed(() => {

    if (canOpenGallery.value) {

        return `معاينة — ${galleryCount.value} صورة HD`;

    }



    if (hasPreview.value) {

        return 'معاينة فقط — لا صور عالية الدقة';

    }



    return 'لا توجد صور';

});



const buttonLabel = computed(() =>

    canOpenGallery.value ? `صور HD (${galleryCount.value})` : 'لا صور HD',

);



async function openGallery() {

    if (! canOpenGallery.value || galleryFetching.value) {

        return;

    }

    const vehicleId = props.vehicle?.id;
    const vin = props.vehicle?.vin;

    if (vehicleId && vin && vehicleUsesLiveGallery(props.vehicle)) {
        galleryFetching.value = true;

        try {
            const payload = await fetchLiveVehicleGallery(vehicleId, props.apiMode);
            lightboxVehicle.value = mergeGalleryIntoVehicle(props.vehicle, payload);
        } catch {
            lightboxVehicle.value = props.vehicle;
        } finally {
            galleryFetching.value = false;
        }
    } else {
        lightboxVehicle.value = props.vehicle;
    }

    visible.value = true;

}

</script>



<style scoped>

.vehicle-gallery {

    display: inline-flex;

    align-items: center;

    gap: 0.35rem;

}



.thumb-btn {

    position: relative;

    width: 56px;

    height: 42px;

    padding: 0;

    border: 1px solid #e4e4e7;

    border-radius: 8px;

    overflow: hidden;

    background: #fafafa;

    cursor: default;

    flex-shrink: 0;

    transition: box-shadow 0.15s ease, transform 0.15s ease;

}



.thumb-btn--row {

    width: 88px;

    height: 66px;

    border-radius: 10px;

    border-color: #ececef;

}



.thumb-btn.clickable {

    cursor: pointer;

}



.thumb-btn.clickable:hover {

    box-shadow: 0 4px 12px rgb(0 0 0 / 12%);

    transform: translateY(-1px);

}



.thumb-btn.empty {

    opacity: 0.65;

}



.thumb-img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;

}



.thumb-empty {

    width: 100%;

    height: 100%;

    display: grid;

    place-items: center;

    color: #a1a1aa;

    font-size: 1.1rem;

}



.count-badge {

    position: absolute;

    bottom: 3px;

    inset-inline-end: 3px;

    min-width: 16px;

    height: 16px;

    padding: 0 4px;

    border-radius: 999px;

    background: rgb(24 24 27 / 82%);

    color: #fff;

    font-size: 10px;

    line-height: 16px;

    text-align: center;

}

</style>

