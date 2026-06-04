<template>

    <div
        class="manual-vehicle-form"
        :class="{ 'manual-vehicle-form--blocked': vinBlocked || vinTrashed }"
    >

        <nav class="step-nav" aria-label="خطوات الإدخال">

            <button

                v-for="(label, index) in stepLabels"

                :key="index"

                type="button"

                class="step-nav__item"

                :class="{

                    'step-nav__item--active': step === index,

                    'step-nav__item--done': step > index,

                    'step-nav__item--locked': vinBlocked || vinTrashed || index > maxReachableStep,

                }"

                @click="goToStep(index)"

            >

                <span class="step-nav__num">{{ index + 1 }}</span>

                <span class="step-nav__label">{{ label }}</span>

            </button>

        </nav>



        <div v-show="step === 0" class="step-panel">

            <p class="vs-card-subtitle">

                أدخل رقم الشاصي (17 حرفاً) ثم اضغط فك الشاصي لجلب البيانات من NHTSA.

            </p>

            <div class="field">

                <label for="manual-vin" class="vs-form-label">رقم الشاصي (VIN)</label>

                <div class="vin-row">

                    <InputText

                        id="manual-vin"

                        v-model="form.vin"

                        class="w-full vin-input"

                        maxlength="17"

                        dir="ltr"

                        placeholder="مثال: 4T1DAACK5SU031835"

                        :invalid="!!vinError || vinBlocked"

                        @input="onVinInput"

                        @blur="onVinBlur"

                    />

                    <Button

                        type="button"

                        class="decode-btn"

                        :class="{

                            'decode-btn--ready': decodeBtnReady,

                            'decode-btn--duplicate': vinDuplicateVisible,

                        }"

                        label="فك الشاصي"

                        :icon="vinDuplicateVisible ? 'pi pi-exclamation-triangle' : 'pi pi-barcode'"

                        :title="vinDuplicateVisible ? vinDuplicateMessage : undefined"

                        :loading="decoding"

                        @click="decode"

                    />

                </div>

                <small v-if="checkingVin" class="field-hint">جاري التحقق من رقم الشاصي…</small>

                <small v-else-if="vinError" class="field-error">{{ vinError }}</small>

                <Message v-if="vinBlocked" severity="error" :closable="false" class="mt-sm">

                    {{ vinDuplicateMessage }}

                </Message>

                <div v-else-if="vinTrashed" class="trashed-vin-panel mt-sm">

                    <Message severity="warn" :closable="false">

                        هذه السيارة محذوفة — استعِدها للمتابعة أو لتعديل بياناتها.

                    </Message>

                    <Button

                        type="button"

                        label="استعادة السيارة"

                        icon="pi pi-replay"

                        class="trashed-vin-panel__restore"

                        :loading="restoringVin"

                        @click="restoreTrashedVin"

                    />

                </div>

                <Message v-else-if="decodeWarning" severity="warn" :closable="false" class="mt-sm">

                    {{ decodeWarning }}

                </Message>

                <Message v-else-if="decodeSuccess" severity="success" :closable="false" class="mt-sm">

                    تم فك الشاصي — راجع الخطوة التالية.

                </Message>

            </div>

        </div>



        <fieldset v-show="step === 1" class="step-panel">

            <p class="vs-card-subtitle">راجع أو عدّل مواصفات المركبة.</p>

            <div class="form-grid">

                <div class="field">

                    <label class="vs-form-label">سنة الصنع</label>

                    <InputNumber v-model="form.year" class="w-full" :min="1900" :max="2100" :use-grouping="false" />

                </div>

                <div class="field">

                    <label class="vs-form-label">الصانع</label>

                    <InputText v-model="form.make" class="w-full" />

                </div>

                <div class="field">

                    <label class="vs-form-label">الموديل</label>

                    <InputText v-model="form.model" class="w-full" />

                </div>

                <div class="field">

                    <label class="vs-form-label">نوع الوقود</label>

                    <InputText v-model="form.fuel_type" class="w-full" />

                </div>

                <div class="field">

                    <label class="vs-form-label">نوع المركبة</label>

                    <InputText v-model="form.vehicle_type" class="w-full" />

                </div>

                <div class="field">

                    <label class="vs-form-label">اللون (اختياري)</label>

                    <InputText v-model="form.color" class="w-full" />

                </div>

                <div class="field">

                    <label class="vs-form-label">السعر (اختياري)</label>

                    <InputNumber v-model="form.price" class="w-full" mode="currency" currency="USD" locale="en-US" />

                </div>

            </div>

        </fieldset>



        <fieldset v-show="step === 2" class="step-panel">

            <p class="vs-card-subtitle">الشحن والبيع — اختر من القوائم المعرّفة في الإعدادات.</p>

            <div class="form-grid">

                <div class="field">

                    <label class="vs-form-label">نقطة التحميل</label>

                    <Select

                        v-model="form.loading_point"

                        :options="options.loading_points"

                        placeholder="اختر نقطة التحميل"

                        show-clear

                        class="w-full"

                    />

                </div>

                <div class="field">

                    <label class="vs-form-label">الوجهة</label>

                    <Select

                        v-model="form.destination"

                        :options="options.shipping_destinations"

                        placeholder="اختر الوجهة"

                        show-clear

                        class="w-full"

                    />

                </div>

                <div class="field">

                    <label class="vs-form-label">المزاد</label>

                    <Select

                        v-model="form.auction"

                        :options="options.auctions"

                        placeholder="اختر المزاد"

                        show-clear

                        class="w-full"

                    />

                </div>

                <div class="field">

                    <label class="vs-form-label">رقم اللوت</label>

                    <InputText v-model="form.lot" class="w-full" dir="ltr" />

                </div>

                <div class="field">

                    <label class="vs-form-label">طريقة الشحن</label>

                    <Select

                        v-model="form.shipping_method"

                        :options="options.shipping_methods"

                        show-clear

                        class="w-full"

                    />

                </div>

                <div class="field">

                    <label class="vs-form-label">نوع التسليم</label>

                    <Select

                        v-model="form.delivery_type"

                        :options="options.delivery_types"

                        show-clear

                        class="w-full"

                    />

                </div>

                <div class="field">

                    <label class="vs-form-label">نوع السند</label>

                    <Select v-model="form.title_type" :options="options.title_types" show-clear class="w-full" />

                </div>

                <div class="field">

                    <label class="vs-form-label">رقم الحاوية</label>

                    <InputText v-model="form.container_number" class="w-full" dir="ltr" />

                </div>

                <div class="field">

                    <label class="vs-form-label">رقم الحجز</label>

                    <InputText v-model="form.booking_number" class="w-full" dir="ltr" />

                </div>

                <div class="field">

                    <label class="vs-form-label">تاريخ الشراء</label>

                    <DatePicker v-model="form.purchase_date" date-format="yy-mm-dd" show-icon class="w-full" />

                </div>

                <div class="field">

                    <label class="vs-form-label">تاريخ الوصول للمحطة</label>

                    <DatePicker v-model="form.arrived_terminal_date" date-format="yy-mm-dd" show-icon class="w-full" />

                </div>

                <div class="field field--full">

                    <label class="vs-form-label">ملاحظات</label>

                    <Textarea v-model="form.notes" rows="3" class="w-full" auto-resize />

                </div>

            </div>

        </fieldset>



        <div v-show="step === 3" class="step-panel">

            <p class="vs-card-subtitle">راجع البيانات قبل الحفظ.</p>

            <dl class="review-list">

                <div class="review-row">

                    <dt>الشاصي</dt>

                    <dd dir="ltr">{{ form.vin }}</dd>

                </div>

                <div class="review-row">

                    <dt>المركبة</dt>

                    <dd>{{ reviewTitle }}</dd>

                </div>

                <div class="review-row">

                    <dt>الوقود</dt>

                    <dd>{{ form.fuel_type || '—' }}</dd>

                </div>

                <div class="review-row">

                    <dt>المسار</dt>

                    <dd>{{ form.loading_point || '—' }} → {{ form.destination || '—' }}</dd>

                </div>

                <div class="review-row">

                    <dt>المزاد / اللوت</dt>

                    <dd>{{ form.auction || '—' }} / {{ form.lot || '—' }}</dd>

                </div>

            </dl>

        </div>



        <footer class="form-footer">

            <Button

                v-if="isEdit"

                label="حذف السيارة"

                severity="danger"

                outlined

                class="form-footer__btn form-footer__btn--delete"

                :loading="deleting"

                @click="confirmDelete"

            />

            <Button

                v-if="isEdit"

                label="إلغاء"

                severity="secondary"

                text

                class="form-footer__btn form-footer__btn--cancel"

                @click="$emit('cancel')"

            />

            <Button

                v-if="step > 0"

                label="السابق"

                icon="pi pi-arrow-right"

                icon-pos="right"

                severity="secondary"

                outlined

                class="form-footer__btn form-footer__btn--prev"

                @click="prev"

            />

            <div class="form-footer__spacer" />

            <Button

                v-if="step < 3"

                label="التالي"

                icon="pi pi-arrow-left"

                icon-pos="left"

                class="form-footer__btn form-footer__btn--next btn-pill--filled"

                :class="{ 'form-footer__btn--soft-disabled': !canAdvance }"

                @click="next"

            />

            <Button

                v-else

                :label="isEdit ? 'حفظ التعديلات' : 'حفظ السيارة'"

                icon="pi pi-check"

                class="form-footer__btn form-footer__btn--save btn-pill--filled"

                :class="{ 'form-footer__btn--soft-disabled': !canAdvance }"

                :loading="saving"

                @click="save"

            />

        </footer>

    </div>

</template>



<script setup>

import { computed, onMounted, reactive, ref, watch } from 'vue';

import { useConfirm } from 'primevue/useconfirm';

import { useToast } from 'primevue/usetoast';

import InputText from 'primevue/inputtext';

import InputNumber from 'primevue/inputnumber';

import Select from 'primevue/select';

import DatePicker from 'primevue/datepicker';

import Textarea from 'primevue/textarea';

import Button from 'primevue/button';

import Message from 'primevue/message';

import {

    checkVinExists,

    createManualVehicle,

    decodeVin,

    deleteManualVehicle,

    fetchVehicleOptions,

    restoreVehicle,

    updateManualVehicle,

} from '../api/vehicles';



const props = defineProps({

    vehicle: {

        type: Object,

        default: null,

    },

});



const emit = defineEmits(['saved', 'cancel', 'deleted']);



const toast = useToast();

const confirm = useConfirm();

const step = ref(0);

const maxReachableStep = ref(0);

const decoding = ref(false);

const saving = ref(false);

const deleting = ref(false);

const restoringVin = ref(false);

const checkingVin = ref(false);

const vinError = ref('');

const vinBlocked = ref(false);

const vinTrashed = ref(false);

const trashedVehicleId = ref(null);

const vinDuplicateMessage = ref('رقم الشاصي مسجّل مسبقاً في النظام — لا يمكن إضافة نفس المركبة مرتين.');

const decodeWarning = ref('');

const decodeSuccess = ref(false);

const vpicSnapshot = ref(null);

let vinCheckToken = 0;



const isEdit = computed(() => props.vehicle?.id != null);

const editVehicleId = computed(() => props.vehicle?.id ?? null);



const stepLabels = ['الشاصي', 'المواصفات', 'الشحن', 'المراجعة'];



const options = reactive({

    shipping_destinations: [],

    loading_points: [],

    auctions: [],

    shipping_methods: [],

    delivery_types: [],

    title_types: [],

});



const form = reactive({

    vin: '',

    year: null,

    make: '',

    model: '',

    fuel_type: '',

    vehicle_type: '',

    color: '',

    price: null,

    loading_point: null,

    destination: null,

    auction: null,

    lot: '',

    shipping_method: null,

    delivery_type: null,

    title_type: null,

    container_number: '',

    booking_number: '',

    purchase_date: null,

    arrived_terminal_date: null,

    notes: '',

});



const reviewTitle = computed(() =>

    [form.year, form.make, form.model].filter(Boolean).join(' ') || '—',

);



const vinDuplicateVisible = computed(

    () => vinBlocked.value && form.vin.length === 17 && !checkingVin.value,

);



const decodeBtnEnabled = computed(

    () => form.vin.length === 17 && !checkingVin.value && !decoding.value,

);



const decodeBtnReady = computed(

    () => decodeBtnEnabled.value && !vinBlocked.value,

);



const canAdvance = computed(() => {

    if (vinBlocked.value || vinTrashed.value) {

        return false;

    }



    if (step.value === 0) {

        return form.vin.length === 17 && !vinError.value;

    }



    if (step.value === 1) {

        return form.year && form.make?.trim() && form.model?.trim();

    }



    return true;

});



function parseDate(value) {

    if (!value) {

        return null;

    }



    const d = new Date(value);



    return Number.isNaN(d.getTime()) ? null : d;

}



function loadFromVehicle(vehicle) {

    if (!vehicle) {

        return;

    }



    const raw = vehicle.raw_data ?? {};



    Object.assign(form, {

        vin: vehicle.vin ?? '',

        year: vehicle.year ?? raw.year ?? null,

        make: vehicle.make ?? raw.make ?? '',

        model: vehicle.model ?? raw.model ?? '',

        fuel_type: raw.fuel_type ?? '',

        vehicle_type: raw.vehicle_type ?? '',

        color: raw.color ?? '',

        price: vehicle.price != null ? Number(vehicle.price) : null,

        loading_point: raw.loading_point ?? null,

        destination: raw.destination ?? null,

        auction: raw.auction ?? null,

        lot: raw.lot ?? '',

        shipping_method: raw.shipping_method ?? null,

        delivery_type: raw.delivery_type ?? null,

        title_type: raw.title_type ?? null,

        container_number: raw.container_number ?? '',

        booking_number: raw.booking_number ?? '',

        purchase_date: parseDate(raw.purchase_date),

        arrived_terminal_date: parseDate(raw.arrived_terminal_date),

        notes: vehicle.notes ?? '',

    });



    vpicSnapshot.value = raw.vpic ?? null;

    maxReachableStep.value = 3;

}



function onVinInput() {

    form.vin = form.vin.toUpperCase().replace(/[^A-HJ-NPR-Z0-9]/gi, '').slice(0, 17);

    vinError.value = '';

    decodeWarning.value = '';

    decodeSuccess.value = false;



    if (form.vin.length === 17) {

        runVinCheck();

    } else {

        vinBlocked.value = false;

        vinTrashed.value = false;

        trashedVehicleId.value = null;

    }

}



function onVinBlur() {

    if (form.vin.length === 17) {

        runVinCheck();

    }

}



async function runVinCheck() {

    if (form.vin.length !== 17) {

        vinBlocked.value = false;

        vinTrashed.value = false;

        trashedVehicleId.value = null;



        return;

    }



    const token = ++vinCheckToken;

    checkingVin.value = true;

    vinError.value = '';



    try {

        const result = await checkVinExists(form.vin, editVehicleId.value);

        if (token !== vinCheckToken) {

            return;

        }



        if (result.exists) {

            vinBlocked.value = true;

            vinTrashed.value = false;

            trashedVehicleId.value = null;

            vinDuplicateMessage.value =

                'رقم الشاصي مسجّل مسبقاً في النظام — لا يمكن إضافة نفس المركبة مرتين.';

            decodeSuccess.value = false;

            decodeWarning.value = '';

        } else if (result.trashed) {

            vinBlocked.value = false;

            vinTrashed.value = true;

            trashedVehicleId.value = result.vehicle_id ?? null;

            decodeSuccess.value = false;

            decodeWarning.value = '';

        } else {

            vinBlocked.value = false;

            vinTrashed.value = false;

            trashedVehicleId.value = null;

        }

    } catch (e) {

        if (token !== vinCheckToken) {

            return;

        }



        vinError.value = e.response?.data?.message || 'تعذّر التحقق من رقم الشاصي.';

    } finally {

        if (token === vinCheckToken) {

            checkingVin.value = false;

        }

    }

}



function goToStep(index) {

    if (vinBlocked.value || vinTrashed.value) {

        toast.add({

            severity: 'warn',

            summary: vinTrashed.value ? 'سيارة محذوفة' : 'رقم الشاصي مسجّل مسبقاً',

            detail: vinTrashed.value

                ? 'استعِد السيارة أولاً للمتابعة.'

                : vinDuplicateMessage.value,

            life: 3000,

        });



        return;

    }



    if (index > maxReachableStep.value) {

        toast.add({

            severity: 'info',

            summary: 'أكمل الخطوات السابقة',

            detail: 'انتقل خطوة بخطوة أو أكمل فك الشاصي أولاً.',

            life: 3000,

        });



        return;

    }



    step.value = index;

}



function next() {

    if (vinBlocked.value || vinTrashed.value) {

        toast.add({

            severity: 'warn',

            summary: vinTrashed.value ? 'سيارة محذوفة' : 'رقم الشاصي مسجّل مسبقاً',

            detail: vinTrashed.value

                ? 'استعِد السيارة أولاً للمتابعة.'

                : vinDuplicateMessage.value,

            life: 3000,

        });



        return;

    }



    if (!canAdvance.value) {

        if (step.value === 0) {

            toast.add({

                severity: 'warn',

                summary: 'رقم الشاصي',

                detail: vinError.value || 'أدخل 17 حرفاً صالحاً للمتابعة.',

                life: 3000,

            });

        } else if (step.value === 1) {

            toast.add({

                severity: 'warn',

                summary: 'المواصفات',

                detail: 'أكمل سنة الصنع والصانع والموديل.',

                life: 3000,

            });

        }



        return;

    }



    if (step.value < 3) {

        step.value += 1;

        maxReachableStep.value = Math.max(maxReachableStep.value, step.value);

    }

}



function prev() {

    if (vinBlocked.value || vinTrashed.value) {

        toast.add({

            severity: 'warn',

            summary: vinTrashed.value ? 'سيارة محذوفة' : 'رقم الشاصي مسجّل مسبقاً',

            detail: vinTrashed.value

                ? 'استعِد السيارة أولاً للمتابعة.'

                : vinDuplicateMessage.value,

            life: 3000,

        });



        return;

    }



    if (step.value > 0) {

        step.value -= 1;

    }

}



function applyMapped(mapped) {

    if (!mapped) {

        return;

    }



    Object.assign(form, {

        year: mapped.year ?? form.year,

        make: mapped.make ?? form.make,

        model: mapped.model ?? form.model,

        fuel_type: mapped.fuel_type ?? form.fuel_type,

        vehicle_type: mapped.vehicle_type ?? form.vehicle_type,

    });

}



async function decode() {

    if (decoding.value) {

        return;

    }



    if (!decodeBtnEnabled.value) {

        if (checkingVin.value) {

            toast.add({

                severity: 'info',

                summary: 'جاري التحقق',

                detail: 'انتظر حتى ينتهي التحقق من رقم الشاصي.',

                life: 2500,

            });

        } else if (form.vin.length < 17) {

            toast.add({

                severity: 'warn',

                summary: 'رقم الشاصي',

                detail: 'أدخل 17 حرفاً كاملة قبل فك الشاصي.',

                life: 3000,

            });

        }



        return;

    }



    if (vinBlocked.value || vinTrashed.value) {

        toast.add({

            severity: 'warn',

            summary: vinTrashed.value ? 'سيارة محذوفة' : 'رقم الشاصي مسجّل مسبقاً',

            life: 3000,

        });



        return;

    }



    await runVinCheck();



    if (vinBlocked.value || vinTrashed.value) {

        toast.add({

            severity: 'warn',

            summary: 'رقم الشاصي مسجّل مسبقاً',

            life: 3000,

        });



        return;

    }



    decoding.value = true;

    vinError.value = '';

    decodeWarning.value = '';

    decodeSuccess.value = false;



    try {

        const result = await decodeVin(form.vin);

        applyMapped(result.mapped);

        vpicSnapshot.value = result.vpic ?? null;



        if (result.trashed) {

            vinTrashed.value = true;

            trashedVehicleId.value = result.vehicle_id ?? null;

            vinBlocked.value = false;

        }



        if (!result.valid) {

            decodeWarning.value = result.error || 'فك الشاصي مع تحذيرات — يمكنك الإكمال يدوياً.';

        } else {

            decodeSuccess.value = true;

        }



        maxReachableStep.value = Math.max(maxReachableStep.value, 1);

    } catch (e) {

        if (e.response?.status === 409) {

            vinBlocked.value = true;

            vinDuplicateMessage.value =

                e.response?.data?.message || vinDuplicateMessage.value;

        } else {

            vinError.value = e.response?.data?.message || 'فشل فك الشاصي.';

        }

    } finally {

        decoding.value = false;

    }

}



function formatDate(value) {

    if (!value) {

        return null;

    }



    if (value instanceof Date) {

        return value.toISOString().slice(0, 10);

    }



    return value;

}



function buildPayload() {

    return {

        vin: form.vin,

        year: form.year,

        make: form.make,

        model: form.model,

        price: form.price,

        notes: form.notes || null,

        fuel_type: form.fuel_type || null,

        vehicle_type: form.vehicle_type || null,

        color: form.color || null,

        loading_point: form.loading_point,

        destination: form.destination,

        auction: form.auction,

        lot: form.lot || null,

        shipping_method: form.shipping_method,

        delivery_type: form.delivery_type,

        title_type: form.title_type,

        container_number: form.container_number || null,

        booking_number: form.booking_number || null,

        purchase_date: formatDate(form.purchase_date),

        arrived_terminal_date: formatDate(form.arrived_terminal_date),

        vpic: vpicSnapshot.value,

    };

}



async function restoreTrashedVin() {

    if (!trashedVehicleId.value || restoringVin.value) {

        return;

    }



    restoringVin.value = true;



    try {

        const result = await restoreVehicle(trashedVehicleId.value);

        vinTrashed.value = false;

        trashedVehicleId.value = null;

        toast.add({

            severity: 'success',

            summary: result.message || 'تمت استعادة السيارة',

            life: 3000,

        });

        emit('saved', { id: result.data?.id, vin: form.vin });

    } catch (e) {

        toast.add({

            severity: 'error',

            summary: 'خطأ',

            detail: e.response?.data?.message || 'فشل استعادة السيارة',

            life: 4000,

        });

    } finally {

        restoringVin.value = false;

    }

}



function confirmDelete() {

    if (!editVehicleId.value || deleting.value) {

        return;

    }



    confirm.require({

        message: 'سيتم حذف السيارة من القائمة (حذف مؤقت). يمكن استعادتها لاحقاً من المزامنة أو بإدخال نفس رقم الشاصي.',

        header: 'حذف السيارة',

        icon: 'pi pi-exclamation-triangle',

        rejectLabel: 'إلغاء',

        acceptLabel: 'حذف',

        acceptClass: 'p-button-danger',

        accept: () => performDelete(),

    });

}



async function performDelete() {

    deleting.value = true;



    try {

        const result = await deleteManualVehicle(editVehicleId.value);

        toast.add({

            severity: 'success',

            summary: result.message || 'تم حذف السيارة',

            life: 3000,

        });

        emit('deleted');

    } catch (e) {

        toast.add({

            severity: 'error',

            summary: 'خطأ',

            detail: e.response?.data?.message || 'فشل حذف السيارة',

            life: 4000,

        });

    } finally {

        deleting.value = false;

    }

}



async function save() {

    if (saving.value) {

        return;

    }



    if (vinBlocked.value || vinTrashed.value) {

        toast.add({

            severity: 'warn',

            summary: vinTrashed.value ? 'سيارة محذوفة' : 'رقم الشاصي مسجّل مسبقاً',

            detail: vinTrashed.value

                ? 'استعِد السيارة أولاً للمتابعة.'

                : vinDuplicateMessage.value,

            life: 3000,

        });



        return;

    }



    if (!canAdvance.value) {

        toast.add({

            severity: 'warn',

            summary: 'بيانات ناقصة',

            detail: 'أكمل الحقول المطلوبة في الخطوات السابقة.',

            life: 3000,

        });



        return;

    }



    saving.value = true;



    try {

        const payload = buildPayload();

        const result = isEdit.value

            ? await updateManualVehicle(editVehicleId.value, payload)

            : await createManualVehicle(payload);



        toast.add({

            severity: 'success',

            summary: result.message || (isEdit.value ? 'تم التحديث' : 'تم الحفظ'),

            life: 3000,

        });

        emit('saved', result.data);

    } catch (e) {

        const errors = e.response?.data?.errors;

        const detail = errors

            ? Object.values(errors).flat().join(' ')

            : e.response?.data?.message || 'فشل حفظ السيارة';



        toast.add({ severity: 'error', summary: 'خطأ', detail, life: 5000 });

    } finally {

        saving.value = false;

    }

}



watch(

    () => props.vehicle,

    (vehicle) => {

        step.value = 0;

        vinBlocked.value = false;

        vinTrashed.value = false;

        trashedVehicleId.value = null;

        vinError.value = '';

        decodeWarning.value = '';

        decodeSuccess.value = false;



        if (vehicle) {

            loadFromVehicle(vehicle);

        }

    },

    { immediate: true },

);



onMounted(async () => {

    try {

        const data = await fetchVehicleOptions();

        Object.assign(options, data);

    } catch {

        toast.add({

            severity: 'warn',

            summary: 'تنبيه',

            detail: 'تعذّر تحميل قوائم الإعدادات — استخدم الإعدادات لإضافة خيارات.',

            life: 4000,

        });

    }

});

</script>



<style scoped>

.manual-vehicle-form {

    display: flex;

    flex-direction: column;

    gap: 1.25rem;

    min-height: 100%;

}



.step-nav {

    display: grid;

    grid-template-columns: repeat(4, 1fr);

    gap: 0.5rem;

    margin-bottom: 0.15rem;

}



.step-nav__item {

    display: flex;

    flex-direction: column;

    align-items: center;

    gap: 0.25rem;

    padding: 0.5rem 0.35rem;

    border: 1px solid var(--vs-border);

    border-radius: 8px;

    background: var(--admin-surface);

    cursor: pointer;

    font-size: 0.72rem;

    color: var(--vs-text-muted);

}



.step-nav__item--locked {

    opacity: 0.45;

    cursor: not-allowed;

}



.step-nav__item--active {

    border-color: var(--admin-accent);

    color: var(--admin-accent);

    background: var(--admin-sidebar-active);

}



.step-nav__item--done {

    color: var(--vs-text);

}



.step-nav__num {

    font-weight: 700;

}



.step-panel {

    min-height: 200px;

}



.step-panel fieldset {

    border: none;

    margin: 0;

    padding: 0;

    min-width: 0;

}



.vin-row {

    display: flex;

    gap: 0.5rem;

    align-items: stretch;

}



.vin-input {

    flex: 1;

    font-family: ui-monospace, monospace;

    letter-spacing: 0.06em;

}



.decode-btn {

    flex-shrink: 0;

    min-width: 7.5rem;

    font-weight: 600;

}



.decode-btn--ready {

    background: #15803d;

    border-color: #15803d;

    color: #fff;

}



.decode-btn--ready:hover {

    background: #166534;

    border-color: #166534;

    color: #fff;

}



.decode-btn--duplicate {

    background: #fffbeb;

    border: 2px solid #f59e0b;

    color: #b45309;

}



.decode-btn--duplicate:hover {

    background: #fef3c7;

    border-color: #d97706;

    color: #92400e;

}



[data-theme='dark'] .decode-btn--duplicate {

    background: #422006;

    border-color: #f59e0b;

    color: #fcd34d;

}



[data-theme='dark'] .decode-btn--duplicate:hover {

    background: #78350f;

    border-color: #fbbf24;

    color: #fde68a;

}



[data-theme='dark'] .decode-btn--ready {

    background: #166534;

    border-color: #15803d;

    color: #fff;

}



[data-theme='dark'] .decode-btn--ready:hover {

    background: #15803d;

    border-color: #22c55e;

    color: #fff;

}



.field {

    margin-bottom: 0.85rem;

}



.field-hint {

    display: block;

    margin-top: 0.25rem;

    color: var(--vs-text-muted);

    font-size: 0.78rem;

}



.field-error {

    display: block;

    margin-top: 0.25rem;

    color: #dc2626;

    font-size: 0.78rem;

}



.form-grid {

    display: grid;

    grid-template-columns: repeat(2, minmax(0, 1fr));

    gap: 0.75rem 1rem;

}



.field--full {

    grid-column: 1 / -1;

}



.review-list {

    margin: 0;

    padding: 0;

}



.review-row {

    display: flex;

    justify-content: space-between;

    gap: 1rem;

    padding: 0.55rem 0;

    border-bottom: 1px solid var(--vs-border);

    font-size: 0.88rem;

}



.review-row dt {

    color: var(--vs-text-muted);

    margin: 0;

}



.review-row dd {

    margin: 0;

    font-weight: 600;

    text-align: end;

}



.form-footer {

    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 0.65rem;

    margin-top: auto;

    padding-top: 0.75rem;

}



.form-footer__spacer {

    flex: 1;

    min-width: 0.5rem;

}



.form-footer__btn {

    min-width: 9.5rem;

    font-weight: 600;

}



.form-footer__btn--cancel {

    min-width: auto;

    font-weight: 500;

}



.manual-vehicle-form .form-footer :deep(.form-footer__btn--prev.p-button) {

    min-width: 8.5rem;

    border: 2px solid #3b82f6;

    color: #2563eb;

    background: transparent;

    box-shadow: none;

}



.manual-vehicle-form .form-footer :deep(.form-footer__btn--prev.p-button:hover) {

    background: #eff6ff;

    border-color: #2563eb;

    color: #1d4ed8;

}



.manual-vehicle-form .form-footer :deep(.form-footer__btn--prev.p-button:active) {

    background: #dbeafe;

    border-color: #1d4ed8;

    color: #1e40af;

}



.manual-vehicle-form .form-footer :deep(.form-footer__btn--next.p-button),

.manual-vehicle-form .form-footer :deep(.form-footer__btn--save.p-button) {

    min-width: 12rem;

    flex: 1 1 12rem;

    max-width: 16rem;

    background: #15803d;

    border: 1px solid #15803d;

    color: #fff;

    box-shadow: none;

}



.manual-vehicle-form .form-footer :deep(.form-footer__btn--next.p-button:hover),

.manual-vehicle-form .form-footer :deep(.form-footer__btn--save.p-button:hover) {

    background: #166534;

    border-color: #166534;

    color: #fff;

}



.manual-vehicle-form .form-footer :deep(.form-footer__btn--next.p-button:active),

.manual-vehicle-form .form-footer :deep(.form-footer__btn--save.p-button:active) {

    background: #14532d;

    border-color: #14532d;

    color: #fff;

}



.manual-vehicle-form .form-footer :deep(.form-footer__btn--next.p-button.form-footer__btn--soft-disabled),

.manual-vehicle-form .form-footer :deep(.form-footer__btn--save.p-button.form-footer__btn--soft-disabled) {

    background: #86efac;

    border-color: #4ade80;

    color: #14532d;

    cursor: not-allowed;

    opacity: 1;

}



.manual-vehicle-form .form-footer :deep(.form-footer__btn--next.p-button.form-footer__btn--soft-disabled:hover),

.manual-vehicle-form .form-footer :deep(.form-footer__btn--save.p-button.form-footer__btn--soft-disabled:hover) {

    background: #86efac;

    border-color: #4ade80;

    color: #14532d;

}



[data-theme='dark'] .manual-vehicle-form .form-footer :deep(.form-footer__btn--prev.p-button) {

    border-color: #60a5fa;

    color: #93c5fd;

    background: transparent;

}



[data-theme='dark'] .manual-vehicle-form .form-footer :deep(.form-footer__btn--prev.p-button:hover) {

    background: rgba(59, 130, 246, 0.18);

    border-color: #93c5fd;

    color: #bfdbfe;

}



[data-theme='dark'] .manual-vehicle-form .form-footer :deep(.form-footer__btn--next.p-button),

[data-theme='dark'] .manual-vehicle-form .form-footer :deep(.form-footer__btn--save.p-button) {

    background: #166534;

    border-color: #15803d;

    color: #fff;

}



[data-theme='dark'] .manual-vehicle-form .form-footer :deep(.form-footer__btn--next.p-button:hover),

[data-theme='dark'] .manual-vehicle-form .form-footer :deep(.form-footer__btn--save.p-button:hover) {

    background: #15803d;

    border-color: #22c55e;

    color: #fff;

}



[data-theme='dark'] .manual-vehicle-form .form-footer :deep(.form-footer__btn--next.p-button.form-footer__btn--soft-disabled),

[data-theme='dark'] .manual-vehicle-form .form-footer :deep(.form-footer__btn--save.p-button.form-footer__btn--soft-disabled) {

    background: #14532d;

    border-color: #166534;

    color: #86efac;

}



[data-theme='dark'] .manual-vehicle-form .form-footer :deep(.form-footer__btn--next.p-button.form-footer__btn--soft-disabled:hover),

[data-theme='dark'] .manual-vehicle-form .form-footer :deep(.form-footer__btn--save.p-button.form-footer__btn--soft-disabled:hover) {

    background: #14532d;

    border-color: #166534;

    color: #86efac;

}



.w-full {

    width: 100%;

}



.mt-sm {

    margin-top: 0.5rem;

}



.trashed-vin-panel {

    display: flex;

    flex-direction: column;

    gap: 0.65rem;

}



.trashed-vin-panel__restore {

    align-self: flex-start;

    border: 2px solid #dc2626;

    color: #b91c1c;

    background: transparent;

    font-weight: 600;

}



.trashed-vin-panel__restore:hover {

    background: #fef2f2;

    border-color: #b91c1c;

    color: #991b1b;

}



.form-footer__btn--delete {

    min-width: auto;

    border: 2px solid #dc2626;

    color: #b91c1c;

}



@media (max-width: 640px) {

    .step-nav {

        grid-template-columns: repeat(2, 1fr);

    }



    .form-grid {

        grid-template-columns: 1fr;

    }



    .vin-row {

        flex-direction: column;

    }



    .decode-btn {

        width: 100%;

    }



    .form-footer__btn {

        flex: 1 1 calc(50% - 0.35rem);

        min-width: 7.5rem;

    }



    .form-footer__btn--cancel {

        flex: 0 1 auto;

        min-width: auto;

    }



    .form-footer__btn--next,

    .form-footer__btn--save {

        flex: 1 1 100%;

        max-width: none;

        min-width: 12rem;

    }



    .form-footer__spacer {

        flex-basis: 100%;

        height: 0;

    }

}

</style>


