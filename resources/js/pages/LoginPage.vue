<template>
    <AuthShell>
        <Card class="login-card">
            <template #title>تسجيل الدخول</template>
            <template #content>
                <form @submit.prevent="submit">
                    <div class="login-tabs">
                        <div class="login-tablist" role="tablist">
                            <button
                                type="button"
                                role="tab"
                                class="login-tab"
                                :class="{ 'login-tab--active': activeTab === 0 }"
                                :aria-selected="activeTab === 0"
                                @click="activeTab = 0"
                            >
                                البريد وكلمة المرور
                            </button>
                            <button
                                type="button"
                                role="tab"
                                class="login-tab"
                                :class="{ 'login-tab--active': activeTab === 1 }"
                                :aria-selected="activeTab === 1"
                                @click="activeTab = 1"
                            >
                                رقم الهاتف
                            </button>
                        </div>
                        <div v-show="activeTab === 0" class="login-tabpanel" role="tabpanel">
                            <div class="field">
                                <label for="login-email">البريد الإلكتروني</label>
                                <InputText
                                    id="login-email"
                                    v-model="email"
                                    type="email"
                                    class="w-full"
                                    placeholder="admin@vinstack.local"
                                    autocomplete="username"
                                    dir="ltr"
                                    :required="activeTab === 0"
                                />
                            </div>
                            <div class="field">
                                <label for="login-password">كلمة المرور</label>
                                <Password
                                    id="login-password"
                                    v-model="password"
                                    toggle-mask
                                    :feedback="false"
                                    input-class="w-full"
                                    class="w-full"
                                    autocomplete="current-password"
                                    :required="activeTab === 0"
                                />
                            </div>
                        </div>
                        <div v-show="activeTab === 1" class="login-tabpanel" role="tabpanel">
                            <div class="field">
                                <label for="login-phone">رقم الهاتف</label>
                                <InputText
                                    id="login-phone"
                                    v-model="phone"
                                    type="tel"
                                    class="w-full"
                                    placeholder="07XXXXXXXXX"
                                    autocomplete="tel"
                                    :required="activeTab === 1"
                                />
                            </div>
                        </div>
                    </div>
                    <Button type="submit" label="دخول" :loading="auth.loading" class="w-full login-submit" />
                </form>
            </template>
        </Card>
    </AuthShell>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import { useAuthStore } from '../stores/auth';
import AuthShell from '../components/AuthShell.vue';

const activeTab = ref(0);
const phone = ref('');
const email = ref('');
const password = ref('');
const auth = useAuthStore();
const router = useRouter();
const toast = useToast();

function loginErrorDetail(error) {
    const errors = error.response?.data?.errors;

    if (errors) {
        const first = Object.values(errors).flat()[0];

        if (first) {
            return first;
        }
    }

    return error.response?.data?.message || 'فشل تسجيل الدخول';
}

async function submit() {
    try {
        const payload =
            activeTab.value === 1
                ? { phone: phone.value }
                : { email: email.value, password: password.value };

        const data = await auth.login(payload);

        if (data.two_factor_setup) {
            sessionStorage.setItem('setup_token', data.setup_token);
            await router.push({ name: 'two-factor.setup' });

            return;
        }

        if (data.two_factor) {
            sessionStorage.setItem('challenge_token', data.challenge_token);
            await router.push({ name: 'two-factor.challenge' });

            return;
        }

        await router.push(
            auth.isAdmin ? { name: 'admin.vehicles' } : { name: 'dealer.vehicles' },
        );
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: loginErrorDetail(e),
            life: 4000,
        });
    }
}
</script>

<style scoped>
.login-card {
    width: 100%;
    max-width: 400px;
}

.login-tablist {
    display: flex;
    direction: rtl;
    gap: 0.25rem;
    padding: 0.2rem;
    border-radius: 0.65rem;
    background: var(--login-tabs-bg, rgba(0, 0, 0, 0.04));
    border-bottom: none;
}

.login-tab {
    flex: 1 1 0;
    margin: 0;
    padding: 0.75rem 0.65rem;
    border: none;
    border-radius: 0.5rem;
    background: transparent;
    color: var(--login-form-muted);
    font: inherit;
    font-weight: 600;
    cursor: pointer;
    transition:
        color 0.2s ease,
        background 0.2s ease,
        box-shadow 0.2s ease;
}

.login-tab:hover:not(.login-tab--active) {
    color: var(--login-form-title);
}

.login-tab--active {
    color: var(--login-form-title);
    background: var(--login-tab-active-bg, var(--p-content-background));
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}

.login-tabpanel {
    padding-top: 1rem;
}

.field {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.login-submit {
    margin-top: 0.25rem;
}

.w-full {
    width: 100%;
}

@media (max-width: 640px) {
    .login-card {
        max-width: 100%;
    }

    .login-tablist {
        margin-bottom: 0.15rem;
    }

    .login-tab {
        min-height: 40px;
        padding: 0.5rem 0.35rem;
        font-size: 0.76rem;
        line-height: 1.25;
        white-space: normal;
        text-align: center;
        hyphens: auto;
    }

    .login-tabpanel {
        padding-top: 0.85rem;
    }

    .field {
        margin-bottom: 0.85rem;
        gap: 0.3rem;
    }

    .field label {
        font-size: 0.88rem;
    }

    .login-submit :deep(.p-button) {
        min-height: 40px;
        width: 100%;
        padding-block: 0.55rem;
    }
}
</style>
