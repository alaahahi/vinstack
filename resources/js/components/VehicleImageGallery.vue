<template>

    <div class="vehicle-gallery" :class="{ 'vehicle-gallery--drawer': variant === 'drawer' }">

        <button

            type="button"

            class="thumb-btn"

            :class="{
                clickable: canOpenGallery,
                'thumb-btn--row': variant === 'row',
                'thumb-btn--drawer': variant === 'drawer',
                'thumb-btn--placeholder': !showPhotoThumbnail,
            }"

            :title="thumbTitle"

            @click="openGallery"

        >

            <img
                v-if="showPhotoThumbnail"
                :src="thumbnail"
                :alt="label"
                class="thumb-img"
                loading="lazy"
                @error="onThumbError"
            />

            <div v-else-if="brandLogoUrl" class="thumb-brand" aria-hidden="true">
                <img :src="brandLogoUrl" class="thumb-brand-logo" alt="" />
            </div>

            <span v-else class="thumb-empty thumb-empty--brand">
                <i class="pi pi-car" />
            </span>

            <span
                v-if="showCountBadge"
                class="count-badge"
                :class="{
                    'count-badge--row': variant === 'row',
                    'count-badge--drawer': variant === 'drawer',
                }"
            >{{ galleryCount }}</span>

        </button>



        <Button

            v-if="showGalleryButton"

            :label="buttonLabel"

            icon="pi pi-images"

            size="small"

            severity="secondary"

            text

            :disabled="!canOpenGallery"

            @click="openGallery"

        />



        <VehicleGalleryLightbox
            v-model:visible="visible"
            :vehicle="lightboxVehicle"
            :api-mode="apiMode"
        />

    </div>

</template>



<script setup>

import { computed, ref, watch } from 'vue';
import {
    fetchLiveVehicleGallery,
    mergeGalleryIntoVehicle,
    vehicleUsesLiveGallery,
} from '../utils/vehicleGalleryLive';

import Button from 'primevue/button';

import VehicleGalleryLightbox from './VehicleGalleryLightbox.vue';

import {

    hasVehicleGallery,

    vehicleGalleryCount,

    vehicleLabel,

    vehicleThumbnail,

} from '../utils/vehicleImages';
import { vehicleBrandLogoUrl } from '../utils/vehicleBrandLogo';



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

        validator: (v) => ['table', 'row', 'drawer'].includes(v),

    },

    apiMode: {

        type: String,

        default: 'admin',

        validator: (value) => ['admin', 'dealer'].includes(value),

    },

});

const emit = defineEmits(['gallery-updated']);

const visible = ref(false);

const lightboxVehicle = ref(null);

const galleryFetching = ref(false);

const thumbFailed = ref(false);

const thumbnail = computed(() => vehicleThumbnail(props.vehicle));

const brandLogoUrl = computed(() => vehicleBrandLogoUrl(props.vehicle));

const showPhotoThumbnail = computed(() => Boolean(thumbnail.value) && ! thumbFailed.value);

const galleryCount = computed(() => vehicleGalleryCount(props.vehicle));

const label = computed(() => vehicleLabel(props.vehicle));

const hasPreview = computed(() => showPhotoThumbnail.value || Boolean(brandLogoUrl.value));

const canOpenGallery = computed(() => {
    if (hasVehicleGallery(props.vehicle)) {
        return true;
    }

    return (
        vehicleUsesLiveGallery(props.vehicle)
        && hasPreview.value
        && Boolean(props.vehicle?.id)
        && Boolean(props.vehicle?.vin)
    );
});

const showCountBadge = computed(() => galleryCount.value > 1);

const showGalleryButton = computed(() => props.showButton && galleryCount.value > 1);



const thumbTitle = computed(() => {

    if (canOpenGallery.value) {
        if (galleryCount.value > 0) {
            return `معاينة — ${galleryCount.value} صورة HD`;
        }

        return 'معاينة — فتح صور HD';
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
            const merged = mergeGalleryIntoVehicle(props.vehicle, payload);
            lightboxVehicle.value = merged;
            emit('gallery-updated', payload, vehicleId);
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

function onThumbError() {
    thumbFailed.value = true;
}

watch(
    () => [props.vehicle?.id, props.vehicle?.vin, thumbnail.value],
    () => {
        thumbFailed.value = false;
    },
);

</script>



<style scoped>

.vehicle-gallery {

    display: inline-flex;

    align-items: center;

    gap: 0.35rem;

}

.vehicle-gallery--drawer {

    display: flex;

    flex-direction: column;

    align-items: stretch;

    width: 100%;

    gap: 0.65rem;

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

.thumb-btn--drawer {

    width: 100%;

    height: auto;

    min-height: 160px;

    border-radius: 12px;

    border-color: var(--vs-border, #ececef);

}

.thumb-btn--drawer .thumb-img {

    width: 100%;

    height: 180px;

    object-fit: cover;

}

.thumb-btn--drawer .thumb-empty {

    min-height: 160px;

}



.thumb-btn.clickable {

    cursor: pointer;

}



.thumb-btn.clickable:hover {

    box-shadow: 0 4px 12px rgb(0 0 0 / 12%);

    transform: translateY(-1px);

}



.thumb-btn--placeholder {
    border-color: rgb(139 92 246 / 35%);
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

.thumb-brand {
    width: 100%;
    height: 100%;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
}

.thumb-brand-logo {
    width: 68%;
    height: 68%;
    object-fit: contain;
    filter: brightness(0) invert(1);
    pointer-events: none;
}

.thumb-empty--brand {
    background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
    color: #fff;
    font-size: 1.35rem;
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
    font-weight: 700;
    line-height: 16px;
    text-align: center;
    pointer-events: none;
    z-index: 2;
    box-shadow: 0 1px 3px rgb(0 0 0 / 0.35);
}

.count-badge--row {
    bottom: 4px;
    inset-inline-end: 4px;
    min-width: 20px;
    height: 20px;
    padding: 0 5px;
    font-size: 11px;
    line-height: 20px;
}

.count-badge--drawer {
    bottom: 10px;
    inset-inline-end: 10px;
    min-width: 24px;
    height: 24px;
    padding: 0 6px;
    font-size: 12px;
    line-height: 24px;
}

</style>

