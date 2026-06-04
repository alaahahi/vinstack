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
                                رقم الهاتف
                            </button>
                            <button
                                type="button"
                                role="tab"
                                class="login-tab"
                                :class="{ 'login-tab--active': activeTab === 1 }"
                                :aria-selected="activeTab === 1"
                                @click="activeTab = 1"
                            >
                                البريد وكلمة المرور
                            </button>
                        </div>
                        <div v-show="activeTab === 0" class="login-tabpanel" role="tabpanel">
                            <div class="field">
                                <label for="login-phone">رقم الهاتف</label>
                                <InputText
                                    id="login-phone"
                                    v-model="phone"
                                    type="tel"
                                    class="w-full"
                                    placeholder="07XXXXXXXXX"
                                    autocomplete="tel"
                                    :required="activeTab === 0"
                                />
                            </div>
                        </div>
                        <div v-show="activeTab === 1" class="login-tabpanel" role="tabpanel">
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
                                    :required="activeTab === 1"
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
            activeTab.value === 0
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
    border-bottom: 1px solid var(--p-content-border-color, var(--vs-zinc-200));
}

.login-tab {
    flex: 1 1 0;
    margin: 0;
    padding: 1rem 0.75rem;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    background: transparent;
    color: var(--login-form-muted);
    font: inherit;
    font-weight: 600;
    cursor: pointer;
    transition:
        color 0.2s ease,
        border-color 0.2s ease;
}

.login-tab:hover:not(.login-tab--active) {
    color: var(--login-form-title);
}

.login-tab--active {
    color: var(--login-form-title);
    border-bottom-color: var(--login-accent);
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
</style>
