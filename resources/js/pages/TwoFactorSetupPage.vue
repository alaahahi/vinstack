<template>
    <AuthShell>
        <Card class="login-card">
            <template #title>تفعيل المصادقة الثنائية</template>
            <template #content>
                <p class="hint">
                    امسح رمز QR بتطبيق
                    <a
                        class="hint__link"
                        :href="googleAuthenticatorStoreUrl"
                        target="_blank"
                        rel="noopener"
                    >Google Authenticator</a>
                    أو Authy، ثم أدخل الرمز المكوّن من 6 أرقام.
                </p>

                <div v-if="loadingSetup" class="center">
                    <ProgressSpinner style="width: 36px; height: 36px" />
                </div>

                <template v-else>
                    <div v-if="qrSvg" class="qr-wrap" v-html="qrSvg" />

                    <form @submit.prevent="submitForm">
                        <div class="field">
                            <label>رمز التحقق</label>
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
                        <Button type="submit" label="تفعيل والدخول" :loading="auth.loading" class="w-full" />
                    </form>
                </template>
            </template>
        </Card>
    </AuthShell>

    <RecoveryCodesDialog
        v-model:visible="recoveryDialogVisible"
        :codes="recoveryCodes"
        subtitle="استخدم أحد هذه الرموز إذا فقدت الوصول لتطبيق المصادقة."
        :closable="false"
        @closed="afterRecoverySaved"
    />
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Card from 'primevue/card';
import InputOtp from 'primevue/inputotp';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import AuthShell from '../components/AuthShell.vue';
import RecoveryCodesDialog from '../components/RecoveryCodesDialog.vue';
import { useAuthStore } from '../stores/auth';
import { useOtpSubmit } from '../composables/useOtpSubmit';
import { getGoogleAuthenticatorStoreUrl } from '../utils/googleAuthenticatorStoreUrl';

const googleAuthenticatorStoreUrl = getGoogleAuthenticatorStoreUrl();

const auth = useAuthStore();
const router = useRouter();
const toast = useToast();

const setupToken = ref(sessionStorage.getItem('setup_token'));
const qrSvg = ref('');
const recoveryCodes = ref([]);
const recoveryDialogVisible = ref(false);
const code = ref('');
const loadingSetup = ref(true);

onMounted(async () => {
    if (! setupToken.value) {
        await router.replace({ name: 'login' });

        return;
    }

    try {
        const data = await auth.fetchTwoFactorSetup(setupToken.value);
        qrSvg.value = data.qr_svg;
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'تعذّر تحميل إعداد 2FA',
            life: 4000,
        });
        await router.replace({ name: 'login' });
    } finally {
        loadingSetup.value = false;
    }
});

async function verify() {
    try {
        const data = await auth.confirmTwoFactor(setupToken.value, String(code.value));
        recoveryCodes.value = data.recovery_codes ?? [];

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
    afterSuccess: async () => {
        if (recoveryCodes.value.length > 0) {
            recoveryDialogVisible.value = true;
        } else {
            await afterRecoverySaved();
        }
    },
});

function submitForm() {
    if (String(code.value).length !== 6) {
        toast.add({ severity: 'warn', summary: 'أدخل 6 أرقام', life: 3000 });

        return;
    }

    void submit();
}

async function afterRecoverySaved() {
    await router.push({ name: 'dealer.vehicles' });
}
</script>

<style scoped>
.login-card {
    width: 100%;
    max-width: 420px;
}

.hint {
    color: var(--login-form-muted);
    font-size: 0.9rem;
    line-height: 1.5;
    margin: 0 0 1rem;
}

.hint__link {
    color: var(--login-accent);
    text-decoration: underline;
    text-underline-offset: 2px;
}

.hint__link:hover {
    opacity: 0.85;
}

.qr-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: #fff;
    border: 1px solid #ececef;
    border-radius: 12px;
}

.field {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: center;
}

.center {
    display: grid;
    place-items: center;
    padding: 2rem;
}

.w-full {
    width: 100%;
}
</style>
