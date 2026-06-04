<template>

    <div class="profile-page">

        <p class="subtitle page-intro">تحديث اسم الشركة / معرض ورقم هاتف تسجيل الدخول</p>



        <Card v-if="loading" class="profile-card">

            <template #content>

                <div class="card-loading">

                    <ProgressSpinner style="width: 36px; height: 36px" />

                </div>

            </template>

        </Card>



        <Card v-else class="profile-card">

            <template #content>

                <form class="profile-form" @submit.prevent="save">

                    <div class="field">

                        <label for="company_name">اسم الشركة / معرض</label>

                        <InputText

                            id="company_name"

                            v-model="form.company_name"

                            class="w-full"

                            :invalid="Boolean(errors.company_name)"

                        />

                        <small v-if="errors.company_name" class="error">{{ errors.company_name }}</small>

                    </div>



                    <div class="field">

                        <label for="phone">رقم الهاتف</label>

                        <InputText

                            id="phone"

                            v-model="form.phone"

                            class="w-full"

                            dir="ltr"

                            inputmode="tel"

                            placeholder="07XXXXXXXXX"

                            :invalid="Boolean(errors.phone)"

                        />

                        <small class="hint">يُستخدم لتسجيل الدخول والمصادقة الثنائية</small>

                        <small v-if="errors.phone" class="error">{{ errors.phone }}</small>

                    </div>



                    <div v-if="profile.two_factor_enabled" class="twofa-block">

                        <Message severity="info" icon="pi pi-shield" class="twofa-msg">

                            المصادقة الثنائية مفعّلة

                        </Message>

                        <Button

                            type="button"

                            label="رموز الاسترداد"

                            icon="pi pi-key"

                            severity="secondary"

                            outlined

                            :loading="regeneratingCodes"

                            @click="confirmRegenerateCodes"

                        />

                    </div>



                    <div class="actions">

                        <Button type="submit" label="حفظ التغييرات" icon="pi pi-check" :loading="saving" />

                    </div>

                </form>

            </template>

        </Card>



        <RecoveryCodesDialog

            v-model:visible="recoveryDialogVisible"

            :codes="recoveryCodes"

            subtitle="الرموز السابقة لم تعد صالحة. احفظ هذه القائمة في مكان آمن."

        />

    </div>

</template>



<script setup>

import { onMounted, reactive, ref } from 'vue';

import { useToast } from 'primevue/usetoast';

import { useConfirm } from 'primevue/useconfirm';

import Card from 'primevue/card';

import InputText from 'primevue/inputtext';

import Button from 'primevue/button';

import Message from 'primevue/message';

import ProgressSpinner from 'primevue/progressspinner';

import RecoveryCodesDialog from '../../components/RecoveryCodesDialog.vue';

import api from '../../api/client';

import { useAuthStore } from '../../stores/auth';



const toast = useToast();

const confirm = useConfirm();

const auth = useAuthStore();

const loading = ref(true);

const saving = ref(false);

const regeneratingCodes = ref(false);

const recoveryDialogVisible = ref(false);

const recoveryCodes = ref([]);

const profile = ref({});

const errors = reactive({ company_name: '', phone: '' });



const form = reactive({

    company_name: '',

    phone: '',

});



function clearErrors() {

    errors.company_name = '';

    errors.phone = '';

}



function applyValidationErrors(payload) {

    clearErrors();



    if (! payload || typeof payload !== 'object') {

        return;

    }



    for (const [key, messages] of Object.entries(payload)) {

        if (key in errors && Array.isArray(messages) && messages[0]) {

            errors[key] = messages[0];

        }

    }

}



async function load() {

    loading.value = true;



    try {

        const { data } = await api.get('/dealer/profile');

        profile.value = data.data ?? {};

        form.company_name = profile.value.company_name ?? '';

        form.phone = profile.value.phone ?? '';

    } catch (e) {

        toast.add({

            severity: 'error',

            summary: 'خطأ',

            detail: e.response?.data?.message || 'تعذّر تحميل الملف الشخصي',

            life: 4000,

        });

    } finally {

        loading.value = false;

    }

}



function confirmRegenerateCodes() {

    confirm.require({

        message:

            'سيتم إنشاء رموز استرداد جديدة وإلغاء الرموز القديمة. هل تريد المتابعة؟',

        header: 'رموز الاسترداد',

        icon: 'pi pi-exclamation-triangle',

        rejectLabel: 'إلغاء',

        acceptLabel: 'إنشاء رموز جديدة',

        accept: regenerateRecoveryCodes,

    });

}



async function regenerateRecoveryCodes() {

    regeneratingCodes.value = true;



    try {

        const { data } = await api.post('/dealer/two-factor/recovery-codes');

        recoveryCodes.value = data.recovery_codes ?? [];

        recoveryDialogVisible.value = recoveryCodes.value.length > 0;



        if (data.message) {

            toast.add({

                severity: 'info',

                summary: 'رموز جديدة',

                detail: data.message,

                life: 4000,

            });

        }

    } catch (e) {

        toast.add({

            severity: 'error',

            summary: 'خطأ',

            detail: e.response?.data?.message || 'تعذّر إنشاء رموز الاسترداد',

            life: 4000,

        });

    } finally {

        regeneratingCodes.value = false;

    }

}



async function save() {

    saving.value = true;

    clearErrors();



    try {

        const { data } = await api.put('/dealer/profile', {

            company_name: form.company_name.trim(),

            phone: form.phone.trim(),

        });



        profile.value = data.data ?? profile.value;



        if (data.user) {

            auth.setSession({ token: auth.token, user: data.user });

        }



        toast.add({

            severity: 'success',

            summary: 'تم الحفظ',

            detail: data.message || 'تم تحديث الملف الشخصي',

            life: 3000,

        });

    } catch (e) {

        applyValidationErrors(e.response?.data?.errors);

        toast.add({

            severity: 'error',

            summary: 'خطأ',

            detail: e.response?.data?.message || 'تعذّر حفظ التغييرات',

            life: 4000,

        });

    } finally {

        saving.value = false;

    }

}



onMounted(load);

</script>



<style scoped>

.profile-page {

    max-width: 520px;

}



.page-intro {

    margin: 0 0 1rem;

}



.subtitle {

    margin: 0;

    font-size: 0.85rem;

    color: #71717a;

}



.profile-card {

    border: 1px solid #ececef;

    box-shadow: 0 1px 2px rgb(0 0 0 / 4%);

}



.card-loading {

    display: flex;

    justify-content: center;

    padding: 2rem;

}



.profile-form {

    display: flex;

    flex-direction: column;

    gap: 1rem;

}



.field label {

    display: block;

    margin-bottom: 0.35rem;

    font-size: 0.85rem;

    font-weight: 600;

    color: #3f3f46;

}



.w-full {

    width: 100%;

}



.hint {

    display: block;

    margin-top: 0.3rem;

    color: #a1a1aa;

    font-size: 0.78rem;

}



.error {

    display: block;

    margin-top: 0.3rem;

    color: #dc2626;

    font-size: 0.78rem;

}



.twofa-block {

    display: flex;

    flex-direction: column;

    gap: 0.65rem;

}



.twofa-msg {

    margin: 0;

}



.actions {

    padding-top: 0.25rem;

}

@media (max-width: 640px) {

    .profile-page {

        max-width: 100%;

    }

    .actions :deep(.p-button) {

        width: 100%;

        min-height: 44px;

        justify-content: center;

    }

}

</style>

