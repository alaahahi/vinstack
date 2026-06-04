<template>
    <div class="profile-page">
        <p class="subtitle page-intro">تحديث اسمك والبريد الإلكتروني ورقم هاتف تسجيل الدخول وكلمة المرور</p>

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
                        <label for="name" class="vs-form-label">الاسم</label>
                        <InputText
                            id="name"
                            v-model="form.name"
                            class="w-full"
                            :invalid="Boolean(errors.name)"
                        />
                        <small v-if="errors.name" class="error">{{ errors.name }}</small>
                    </div>

                    <div class="field">
                        <label for="email" class="vs-form-label">البريد الإلكتروني</label>
                        <InputText
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="w-full"
                            dir="ltr"
                            inputmode="email"
                            autocomplete="email"
                            :invalid="Boolean(errors.email)"
                        />
                        <small class="hint">يُستخدم لتسجيل الدخول بكلمة المرور</small>
                        <small v-if="errors.email" class="error">{{ errors.email }}</small>
                    </div>

                    <div class="field">
                        <label for="phone" class="vs-form-label">رقم الهاتف</label>
                        <InputText
                            id="phone"
                            v-model="form.phone"
                            class="w-full"
                            dir="ltr"
                            inputmode="tel"
                            :invalid="Boolean(errors.phone)"
                        />
                        <small class="hint">يُستخدم لتسجيل الدخول — نفس تنسيق رقم الدخول</small>
                        <small v-if="errors.phone" class="error">{{ errors.phone }}</small>
                    </div>

                    <div class="readonly-block">
                        <div class="readonly-row">
                            <span class="readonly-label">الدور</span>
                            <span>{{ roleLabel }}</span>
                        </div>
                    </div>

                    <div class="actions">
                        <Button type="submit" label="حفظ التغييرات" icon="pi pi-check" :loading="saving" />
                    </div>
                </form>
            </template>
        </Card>

        <Card v-if="!loading" class="profile-card password-card">
            <template #title>تغيير كلمة المرور</template>
            <template #content>
                <form class="profile-form" @submit.prevent="savePassword">
                    <div class="field">
                        <label for="current_password" class="vs-form-label">كلمة المرور الحالية</label>
                        <Password
                            id="current_password"
                            v-model="passwordForm.current_password"
                            toggle-mask
                            :feedback="false"
                            input-class="w-full"
                            class="w-full"
                            autocomplete="current-password"
                            :invalid="Boolean(passwordErrors.current_password)"
                        />
                        <small v-if="passwordErrors.current_password" class="error">
                            {{ passwordErrors.current_password }}
                        </small>
                    </div>

                    <div class="field">
                        <label for="new_password" class="vs-form-label">كلمة المرور الجديدة</label>
                        <Password
                            id="new_password"
                            v-model="passwordForm.password"
                            toggle-mask
                            :feedback="false"
                            input-class="w-full"
                            class="w-full"
                            autocomplete="new-password"
                            :invalid="Boolean(passwordErrors.password)"
                        />
                        <small class="hint">8 أحرف على الأقل</small>
                        <small v-if="passwordErrors.password" class="error">{{ passwordErrors.password }}</small>
                    </div>

                    <div class="field">
                        <label for="password_confirmation" class="vs-form-label">تأكيد كلمة المرور الجديدة</label>
                        <Password
                            id="password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            toggle-mask
                            :feedback="false"
                            input-class="w-full"
                            class="w-full"
                            autocomplete="new-password"
                            :invalid="Boolean(passwordErrors.password_confirmation)"
                        />
                        <small v-if="passwordErrors.password_confirmation" class="error">
                            {{ passwordErrors.password_confirmation }}
                        </small>
                    </div>

                    <div class="actions">
                        <Button
                            type="submit"
                            label="تحديث كلمة المرور"
                            icon="pi pi-lock"
                            severity="secondary"
                            :loading="savingPassword"
                        />
                    </div>
                </form>
            </template>
        </Card>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';

const toast = useToast();
const auth = useAuthStore();
const loading = ref(true);
const saving = ref(false);
const savingPassword = ref(false);
const profile = ref({});
const errors = reactive({ name: '', email: '', phone: '' });
const passwordErrors = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const form = reactive({
    name: '',
    email: '',
    phone: '',
});

const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const roleLabel = computed(() => {
    if (profile.value.role === 'admin') {
        return 'مدير النظام';
    }

    if (profile.value.role === 'dealer') {
        return 'تاجر';
    }

    return profile.value.role || '—';
});

function clearErrors() {
    errors.name = '';
    errors.email = '';
    errors.phone = '';
}

function clearPasswordErrors() {
    passwordErrors.current_password = '';
    passwordErrors.password = '';
    passwordErrors.password_confirmation = '';
}

function applyValidationErrors(payload, target) {
    if (target === errors) {
        clearErrors();
    } else {
        clearPasswordErrors();
    }

    if (! payload || typeof payload !== 'object') {
        return;
    }

    for (const [key, messages] of Object.entries(payload)) {
        if (key in target && Array.isArray(messages) && messages[0]) {
            target[key] = messages[0];
        }
    }
}

function resetPasswordForm() {
    passwordForm.current_password = '';
    passwordForm.password = '';
    passwordForm.password_confirmation = '';
}

async function load() {
    loading.value = true;

    try {
        const { data } = await api.get('/admin/profile');
        profile.value = data.data ?? {};
        form.name = profile.value.name ?? '';
        form.email = profile.value.email ?? '';
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

async function save() {
    saving.value = true;
    clearErrors();

    try {
        const { data } = await api.put('/admin/profile', {
            name: form.name.trim(),
            email: form.email.trim(),
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
        applyValidationErrors(e.response?.data?.errors, errors);
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

async function savePassword() {
    savingPassword.value = true;
    clearPasswordErrors();

    try {
        const { data } = await api.put('/admin/profile/password', {
            current_password: passwordForm.current_password,
            password: passwordForm.password,
            password_confirmation: passwordForm.password_confirmation,
        });

        resetPasswordForm();

        toast.add({
            severity: 'success',
            summary: 'تم التحديث',
            detail: data.message || 'تم تحديث كلمة المرور',
            life: 3000,
        });
    } catch (e) {
        applyValidationErrors(e.response?.data?.errors, passwordErrors);
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'تعذّر تحديث كلمة المرور',
            life: 4000,
        });
    } finally {
        savingPassword.value = false;
    }
}

onMounted(load);
</script>

<style scoped>
.profile-page {
    max-width: 520px;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.page-intro {
    margin: 0;
}

.password-card :deep(.p-card-title) {
    font-size: 1rem;
    font-weight: 600;
}

.subtitle {
    margin: 0;
    font-size: 0.85rem;
    color: var(--vs-text-muted);
}

.profile-card {
    border: 1px solid var(--admin-border);
    box-shadow: var(--admin-shadow);
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

.profile-form .vs-form-label {
    display: block;
    margin-bottom: 0.35rem;
    font-weight: 600;
}

.w-full {
    width: 100%;
}

.hint {
    display: block;
    margin-top: 0.3rem;
    color: var(--vs-text-subtle);
    font-size: 0.78rem;
}

.error {
    display: block;
    margin-top: 0.3rem;
    color: #dc2626;
    font-size: 0.78rem;
}

.readonly-block {
    padding: 0.85rem 1rem;
    background: var(--vs-surface-elevated);
    border-radius: 8px;
    border: 1px solid var(--admin-border);
}

.readonly-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.35rem 0;
    font-size: 0.88rem;
}

.readonly-label {
    color: var(--vs-text-muted);
}

.actions {
    padding-top: 0.25rem;
}
</style>
