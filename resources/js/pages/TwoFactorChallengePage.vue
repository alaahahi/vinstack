<template>
    <AuthShell>
        <Card class="login-card">
            <template #title>رمز التحقق</template>
            <template #content>
                <p class="hint">أدخل الرمز من تطبيق المصادقة الثنائية.</p>

                <form @submit.prevent="submitForm">
                    <div class="field">
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

                    <Button type="submit" label="تأكيد الدخول" :loading="auth.loading" class="w-full" />

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
import Button from 'primevue/button';
import AuthShell from '../components/AuthShell.vue';
import { useAuthStore } from '../stores/auth';
import { useOtpSubmit } from '../composables/useOtpSubmit';

const auth = useAuthStore();
const router = useRouter();
const toast = useToast();

const challengeToken = ref(sessionStorage.getItem('challenge_token'));
const code = ref('');

onMounted(async () => {
    if (! challengeToken.value) {
        await router.replace({ name: 'login' });
    }
});

async function verify() {
    try {
        await auth.challengeTwoFactor(challengeToken.value, String(code.value));

        return true;
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.errors?.code?.[0] || 'رمز غير صحيح',
            life: 4000,
        });

        return false;
    }
}

const { otpState, otpWrapRef, submit } = useOtpSubmit(code, verify, {
    afterSuccess: () => router.push({ name: 'dealer.vehicles' }),
});

function submitForm() {
    if (String(code.value).length !== 6) {
        toast.add({ severity: 'warn', summary: 'أدخل 6 أرقام', life: 3000 });

        return;
    }

    void submit();
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

.w-full {
    width: 100%;
}

.mt {
    margin-top: 0.5rem;
}
</style>
