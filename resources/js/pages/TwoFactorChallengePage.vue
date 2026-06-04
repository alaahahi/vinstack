<template>
    <AuthShell>
        <Card class="login-card">
            <template #title>رمز التحقق</template>
            <template #content>
                <p class="hint">
                    {{
                        useRecoveryMode
                            ? 'أدخل أحد رموز الاسترداد التي حفظتها عند تفعيل المصادقة الثنائية.'
                            : 'أدخل الرمز من تطبيق المصادقة الثنائية.'
                    }}
                </p>

                <form @submit.prevent="submitForm">
                    <div v-if="!useRecoveryMode" class="field">
                        <label>رمز 6 أرقام</label>
                        <div
                            ref="otpWrapRef"
                            class="otp-ltr"
                            :class="{
                                'otp-ltr--success': otpState === 'success',
                                'otp-ltr--error': otpState === 'error',
                            }"
                            dir="ltr"
                        >
                            <InputOtp v-model="code" :length="6" integer-only class="otp-ltr" />
                        </div>
                    </div>

                    <div v-else class="field field--recovery">
                        <label for="recovery-code">رمز الاسترداد</label>
                        <InputText
                            id="recovery-code"
                            v-model="recoveryCode"
                            type="text"
                            class="w-full recovery-input"
                            dir="ltr"
                            autocomplete="off"
                            autocapitalize="off"
                            spellcheck="false"
                            maxlength="32"
                            placeholder="xxxx-xxxx"
                        />
                    </div>

                    <Button
                        type="submit"
                        label="تأكيد الدخول"
                        :loading="auth.loading"
                        class="w-full login-submit"
                    />

                    <button type="button" class="mode-toggle" @click="toggleMode">
                        {{ useRecoveryMode ? 'استخدم رمز التطبيق' : 'استخدم رمز استرداد' }}
                    </button>

                    <Button
                        type="button"
                        label="العودة لتسجيل الدخول"
                        text
                        class="w-full mt"
                        @click="backToLogin"
                    />
                </form>
            </template>
        </Card>
    </AuthShell>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Card from 'primevue/card';
import InputOtp from 'primevue/inputotp';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import AuthShell from '../components/AuthShell.vue';
import { useAuthStore } from '../stores/auth';
import { useOtpSubmit } from '../composables/useOtpSubmit';

const auth = useAuthStore();
const router = useRouter();
const toast = useToast();

const challengeToken = ref(sessionStorage.getItem('challenge_token'));
const code = ref('');
const recoveryCode = ref('');
const useRecoveryMode = ref(false);

onMounted(async () => {
    if (! challengeToken.value) {
        await router.replace({ name: 'login' });
    }
});

function challengeErrorDetail(e) {
    const errors = e.response?.data?.errors;

    return (
        errors?.recovery_code?.[0]
        || errors?.code?.[0]
        || 'رمز غير صحيح'
    );
}

async function verifyOtp() {
    try {
        await auth.challengeTwoFactor(challengeToken.value, String(code.value));

        return true;
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: challengeErrorDetail(e),
            life: 4000,
        });

        return false;
    }
}

async function verifyRecovery() {
    const trimmed = String(recoveryCode.value).trim();

    if (! trimmed) {
        toast.add({ severity: 'warn', summary: 'أدخل رمز الاسترداد', life: 3000 });

        return false;
    }

    try {
        await auth.challengeTwoFactor(challengeToken.value, null, trimmed);
        await router.push({ name: 'dealer.vehicles' });

        return true;
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: challengeErrorDetail(e),
            life: 4000,
        });
        recoveryCode.value = '';

        return false;
    }
}

const { otpState, otpWrapRef, submit } = useOtpSubmit(code, verifyOtp, {
    afterSuccess: () => router.push({ name: 'dealer.vehicles' }),
});

function submitForm() {
    if (useRecoveryMode.value) {
        void verifyRecovery();

        return;
    }

    if (String(code.value).length !== 6) {
        toast.add({ severity: 'warn', summary: 'أدخل 6 أرقام', life: 3000 });

        return;
    }

    void submit();
}

function toggleMode() {
    useRecoveryMode.value = ! useRecoveryMode.value;
    code.value = '';
    recoveryCode.value = '';
    otpState.value = 'idle';
}

function backToLogin() {
    sessionStorage.removeItem('challenge_token');
    router.push({ name: 'login' });
}
</script>

<style scoped>
.login-card {
    width: 100%;
    max-width: 400px;
}

.hint {
    color: var(--login-form-muted);
    font-size: 0.9rem;
    margin: 0 0 1rem;
}

.field {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: center;
}

.field--recovery {
    align-items: stretch;
    width: 100%;
}

.field--recovery label {
    align-self: flex-start;
}

.recovery-input {
    font-family: ui-monospace, monospace;
    letter-spacing: 0.04em;
    text-align: center;
}

.w-full {
    width: 100%;
}

.login-submit {
    margin-top: 0.25rem;
}

.mode-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 44px;
    margin-top: 0.5rem;
    padding: 0.5rem 0.75rem;
    border: none;
    background: transparent;
    color: var(--login-accent);
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: underline;
    text-underline-offset: 3px;
}

.mode-toggle:hover {
    opacity: 0.85;
}

.mode-toggle:focus-visible {
    outline: 2px solid var(--login-accent);
    outline-offset: 2px;
    border-radius: 4px;
}

.mt {
    margin-top: 0.5rem;
}

@media (max-width: 640px) {
    .login-card {
        max-width: 100%;
    }

    .login-card :deep(.p-card-body) {
        padding: 1rem;
    }

    .field--recovery :deep(.p-inputtext) {
        min-height: 44px;
        font-size: 16px;
    }

    .login-submit :deep(.p-button) {
        min-height: 44px;
        width: 100%;
    }
}
</style>
