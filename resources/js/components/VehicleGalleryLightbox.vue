<template>

    <VueEasyLightbox

        :visible="visible"

        :imgs="lightboxImages"

        :index="activeIndex"

        :move-disabled="false"

        :rotate-disabled="false"

        :zoom-disabled="false"

        :pinch-disabled="false"

        :scroll-disabled="false"

        teleport="body"

        @hide="onHide"

        @on-index-change="activeIndex = $event"

    />



    <Teleport to="body">

        <div v-if="visible" class="gallery-stage-bar" role="tablist" aria-label="Photo stages">

            <button

                v-for="tab in tabs"

                :key="tab.key"

                type="button"

                role="tab"

                class="gallery-stage-tab"

                :class="{ active: activeStage === tab.key }"

                :aria-selected="activeStage === tab.key"

                @click="selectStage(tab.key)"

            >

                <span class="gallery-stage-label">{{ tab.label }}</span>

                <span class="gallery-stage-count">{{ tab.count }}</span>

            </button>

        </div>



        <div v-if="visible" class="gallery-actions-bar" role="toolbar" aria-label="إجراءات الصور">

            <span v-if="currentIsLocal" class="gallery-local-badge">مرفوعة محلياً</span>

            <button

                type="button"

                class="gallery-action-btn"

                :disabled="!currentUrl || busyCurrent"

                @click="onDownloadCurrent"

            >

                <i class="pi" :class="busyCurrent ? 'pi-spin pi-spinner' : 'pi-download'" />

                <span>تحميل</span>

            </button>

            <button

                type="button"

                class="gallery-action-btn"

                :disabled="!allHdCount || busyAll"

                @click="onDownloadAll"

            >

                <i class="pi" :class="busyAll ? 'pi-spin pi-spinner' : 'pi-images'" />

                <span>{{ busyAll ? bulkProgressLabel : 'تحميل الكل' }}</span>

            </button>

            <button

                type="button"

                class="gallery-action-btn"

                :disabled="!allHdCount || busyShare"

                @click="onShareAll"

            >

                <i class="pi" :class="busyShare ? 'pi-spin pi-spinner' : 'pi-share-alt'" />

                <span>{{ busyShare ? shareProgressLabel : 'مشاركة الكل' }}</span>

            </button>

        </div>

    </Teleport>

</template>



<script setup>

import { computed, ref, watch } from 'vue';

import { useToast } from 'primevue/usetoast';

import VueEasyLightbox from 'vue-easy-lightbox';

import {

    downloadAllVehicleImages,

    downloadVehicleImage,

    shareAllVehicleImages,

} from '../utils/vehicleImageDownload';

import {

    GALLERY_STAGES,

    vehicleGalleryByStage,

    vehicleGalleryImages,

    vehicleLabel,

    vehicleUploadedImages,

} from '../utils/vehicleImages';

import { isLocalUploadedUrl } from '../utils/vehicleImageUpload';



const props = defineProps({

    visible: {

        type: Boolean,

        default: false,

    },

    vehicle: {

        type: Object,

        default: null,

    },

    startUrl: {

        type: String,

        default: null,

    },

});



const emit = defineEmits(['update:visible', 'hide']);



const toast = useToast();



const activeStage = ref('terminal');

const activeIndex = ref(0);

const busyCurrent = ref(false);

const busyAll = ref(false);

const busyShare = ref(false);

const bulkProgress = ref({ current: 0, total: 0 });

const shareProgress = ref({ current: 0, total: 0 });



const stages = computed(() => vehicleGalleryByStage(props.vehicle));



const tabs = computed(() =>

    GALLERY_STAGES.map((tab) => ({

        ...tab,

        count: stages.value[tab.key]?.length ?? 0,

    })),

);



const activeImages = computed(() => stages.value[activeStage.value] ?? []);



const label = computed(() => vehicleLabel(props.vehicle));



const allHdCount = computed(() => vehicleGalleryImages(props.vehicle).length);



const currentUrl = computed(() => activeImages.value[activeIndex.value] ?? null);

const currentIsLocal = computed(() =>
    isLocalUploadedUrl(currentUrl.value, vehicleUploadedImages(props.vehicle)),
);



const bulkProgressLabel = computed(() => {

    if (! busyAll.value || bulkProgress.value.total === 0) {

        return 'جاري التحميل…';

    }



    return `جاري التحميل ${bulkProgress.value.current}/${bulkProgress.value.total}`;

});



const shareProgressLabel = computed(() => {

    if (! busyShare.value || shareProgress.value.total === 0) {

        return 'جاري التحضير…';

    }



    return `جاري التحضير ${shareProgress.value.current}/${shareProgress.value.total}`;

});



const lightboxImages = computed(() =>

    activeImages.value.map((src) => ({

        src,

        title: `${label.value} — ${activeStageLabel.value}`,

    })),

);



const activeStageLabel = computed(

    () => GALLERY_STAGES.find((t) => t.key === activeStage.value)?.label ?? activeStage.value,

);



function firstStageWithImages() {

    return GALLERY_STAGES.find((tab) => (stages.value[tab.key]?.length ?? 0) > 0)?.key ?? 'terminal';

}



function applyStartUrl() {

    const url = props.startUrl;



    if (! url) {

        activeStage.value = firstStageWithImages();

        activeIndex.value = 0;



        return;

    }



    for (const { key } of GALLERY_STAGES) {

        const index = stages.value[key]?.indexOf(url) ?? -1;



        if (index >= 0) {

            activeStage.value = key;

            activeIndex.value = index;



            return;

        }

    }



    activeStage.value = firstStageWithImages();

    activeIndex.value = 0;

}



function selectStage(stageKey) {

    if (activeStage.value === stageKey) {

        return;

    }



    activeStage.value = stageKey;

    activeIndex.value = 0;

}



function onHide() {

    emit('update:visible', false);

    emit('hide');

}



async function onDownloadCurrent() {

    if (! currentUrl.value || busyCurrent.value) {

        return;

    }



    busyCurrent.value = true;



    try {

        const result = await downloadVehicleImage(

            currentUrl.value,

            props.vehicle,

            activeIndex.value,

        );



        if (result.method === 'tab') {

            toast.add({

                severity: 'info',

                summary: 'تم فتح الصورة في تبويب جديد',

                detail: 'احفظ الصورة من المتصفح إن لزم',

                life: 4000,

            });

        } else {

            toast.add({

                severity: 'success',

                summary: 'تم تحميل الصورة',

                life: 3000,

            });

        }

    } catch {

        toast.add({

            severity: 'error',

            summary: 'تعذّر تحميل الصورة',

            life: 3000,

        });

    } finally {

        busyCurrent.value = false;

    }

}



async function onDownloadAll() {

    if (! allHdCount.value || busyAll.value) {

        return;

    }



    busyAll.value = true;

    bulkProgress.value = { current: 0, total: allHdCount.value };



    try {

        const result = await downloadAllVehicleImages(props.vehicle, {

            onProgress: (current, total) => {

                bulkProgress.value = { current, total };

            },

        });



        toast.add({

            severity: 'success',

            summary: 'تم تحميل الأرشيف',

            detail: `${result.count} من ${result.total} صورة`,

            life: 4000,

        });

    } catch (error) {

        if (error?.message === 'no_images') {

            toast.add({

                severity: 'warn',

                summary: 'لا توجد صور للتحميل',

                life: 3000,

            });

        } else {

            toast.add({

                severity: 'error',

                summary: 'تعذّر تحميل الصور',

                detail: 'تحقق من الاتصال أو جرّب صورة واحدة',

                life: 4000,

            });

        }

    } finally {

        busyAll.value = false;

        bulkProgress.value = { current: 0, total: 0 };

    }

}



async function onShareAll() {

    if (! allHdCount.value || busyShare.value) {

        return;

    }



    busyShare.value = true;

    shareProgress.value = { current: 0, total: allHdCount.value };



    try {

        const result = await shareAllVehicleImages(props.vehicle, {

            onProgress: (current, total) => {

                shareProgress.value = { current, total };

            },

        });



        if (result.aborted) {

            toast.add({

                severity: 'info',

                summary: 'تم إلغاء المشاركة',

                life: 2500,

            });



            return;

        }



        if (result.method === 'zip_download') {

            toast.add({

                severity: 'success',

                summary: 'تم تحضير الملف للمشاركة',

                detail: `تم تحميل أرشيف ZIP (${result.count} صورة) — شاركه يدوياً من مجلد التحميلات`,

                life: 6000,

            });



            return;

        }



        if (result.method === 'url') {

            toast.add({

                severity: 'success',

                summary: 'تم فتح نافذة المشاركة',

                detail: `${result.count} صورة`,

                life: 4000,

            });



            return;

        }



        if (result.method === 'files_partial') {

            toast.add({

                severity: 'info',

                summary: 'تمت مشاركة أول الصور',

                detail: `${result.count} من ${result.total} (حد المتصفح)`,

                life: 5000,

            });



            return;

        }



        if (result.method === 'zip') {

            toast.add({

                severity: 'success',

                summary: 'تمت مشاركة الأرشيف',

                detail: `${result.count} صورة`,

                life: 4000,

            });



            return;

        }



        toast.add({

            severity: 'success',

            summary: 'تمت مشاركة الصور',

            detail: `${result.count} من ${result.total}`,

            life: 4000,

        });

    } catch (error) {

        const code = error?.message;

        if (code === 'no_images') {

            toast.add({

                severity: 'warn',

                summary: 'لا توجد صور للمشاركة',

                life: 3000,

            });

        } else if (code === 'fetch_failed') {

            toast.add({

                severity: 'error',

                summary: 'تعذّر تحميل الصور للمشاركة',

                detail: 'تحقق من الاتصال أو جرّب صورة واحدة',

                life: 4500,

            });

        } else if (code === 'zip_failed') {

            toast.add({

                severity: 'error',

                summary: 'تعذّر إنشاء أرشيف ZIP',

                detail: 'جرّب «تحميل الكل» ثم شارك الملف يدوياً',

                life: 5000,

            });

        } else {

            toast.add({

                severity: 'warn',

                summary: 'المشاركة غير مدعومة على هذا الجهاز',

                detail: 'استخدم «تحميل الكل» ثم شارك الملف من جهازك',

                life: 5000,

            });

        }

    } finally {

        busyShare.value = false;

        shareProgress.value = { current: 0, total: 0 };

    }

}



watch(

    () => [props.visible, props.startUrl],

    ([open]) => {

        if (! open) {

            return;

        }



        applyStartUrl();

    },

);



watch(stages, () => {

    if (! props.visible) {

        return;

    }



    if (activeImages.value.length === 0) {

        activeStage.value = firstStageWithImages();

        activeIndex.value = 0;

    }

});

</script>



<style>

.gallery-stage-bar {

    position: fixed;

    top: 18px;

    inset-inline-start: 18px;

    z-index: 11050;

    display: flex;

    flex-wrap: wrap;

    gap: 0.45rem;

    pointer-events: auto;

}



.gallery-actions-bar {

    position: fixed;

    top: 18px;

    inset-inline-end: 18px;

    z-index: 11050;

    display: flex;

    flex-wrap: wrap;

    gap: 0.45rem;

    pointer-events: auto;

}



.gallery-local-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    background: #ecfdf5;
    color: #047857;
    font-size: 0.72rem;
    font-weight: 600;
}

.gallery-action-btn {

    display: inline-flex;

    align-items: center;

    gap: 0.4rem;

    padding: 0.45rem 0.85rem;

    border: 1px solid rgb(255 255 255 / 18%);

    border-radius: 999px;

    background: rgb(24 24 27 / 72%);

    color: rgb(255 255 255 / 92%);

    font-size: 0.8rem;

    font-weight: 600;

    cursor: pointer;

    backdrop-filter: blur(8px);

    transition:

        background 0.15s ease,

        color 0.15s ease,

        opacity 0.15s ease;

}



.gallery-action-btn:hover:not(:disabled) {

    background: rgb(39 39 42 / 88%);

    color: #fff;

}



.gallery-action-btn:disabled {

    opacity: 0.45;

    cursor: not-allowed;

}



.gallery-stage-tab {

    display: inline-flex;

    align-items: center;

    gap: 0.45rem;

    padding: 0.4rem 0.75rem;

    border: 1px solid rgb(255 255 255 / 18%);

    border-radius: 999px;

    background: rgb(24 24 27 / 72%);

    color: rgb(255 255 255 / 82%);

    font-size: 0.8rem;

    font-weight: 600;

    cursor: pointer;

    backdrop-filter: blur(8px);

    transition:

        background 0.15s ease,

        color 0.15s ease,

        border-color 0.15s ease;

}



.gallery-stage-tab:hover {

    background: rgb(39 39 42 / 88%);

    color: #fff;

}



.gallery-stage-tab.active {

    background: #fff;

    color: #18181b;

    border-color: #fff;

}



.gallery-stage-count {

    min-width: 1.25rem;

    height: 1.25rem;

    padding: 0 0.35rem;

    border-radius: 999px;

    background: rgb(0 0 0 / 28%);

    color: inherit;

    font-size: 0.72rem;

    font-weight: 700;

    line-height: 1.25rem;

    text-align: center;

}



.gallery-stage-tab.active .gallery-stage-count {

    background: #18181b;

    color: #fff;

}



.vel-modal {

    z-index: 11000 !important;

}



@media (max-width: 640px) {

    .gallery-actions-bar {

        top: auto;

        bottom: 88px;

        inset-inline-end: 12px;

        inset-inline-start: 12px;

        justify-content: center;

    }



    .gallery-action-btn span {

        font-size: 0.75rem;

    }

}

</style>

