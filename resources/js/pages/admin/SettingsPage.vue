<template>
    <div class="admin-page settings-page">
        <AdminPageHeader>
            <template #actions>
                <Button
                    label="مزامنة الآن"
                    icon="pi pi-sync"
                    severity="secondary"
                    outlined
                    :loading="syncing"
                    @click="syncNow"
                />
            </template>
        </AdminPageHeader>

        <div class="settings-grid">
            <section class="admin-surface settings-card">
                <header class="settings-card__head">
                    <i class="pi pi-link" />
                    <div>
                        <h2 class="vs-card-title">اتصال API</h2>
                        <p class="vs-card-subtitle">عنوان Vinstack والتوكن للمزامنة</p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div class="field">
                        <label for="api-base" class="vs-form-label">Base URL</label>
                        <InputText
                            id="api-base"
                            v-model="form.api_base_url"
                            class="w-full"
                            placeholder="https://app.vinstack.com/api/v1/client"
                        />
                    </div>
                    <div class="field">
                        <label for="api-token" class="vs-form-label">API Token</label>
                        <Password
                            id="api-token"
                            v-model="form.api_token"
                            :placeholder="settings.has_token ? '•••••• (اتركه فارغاً للإبقاء)' : 'أدخل التوكن'"
                            toggle-mask
                            input-class="w-full"
                            class="w-full"
                        />
                    </div>
                </div>
            </section>

            <section class="admin-surface settings-card">
                <header class="settings-card__head">
                    <i class="pi pi-headphones" />
                    <div>
                        <h2 class="vs-card-title">الدعم الفني</h2>
                        <p class="vs-card-subtitle">يظهر في تذييل صفحات التجار وتسجيل الدخول</p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div class="field">
                        <label for="support-phone" class="vs-form-label">رقم دعم فني</label>
                        <InputText
                            id="support-phone"
                            v-model="form.support_phone"
                            class="w-full"
                            placeholder="+966 5xx xxx xxxx"
                            dir="ltr"
                        />
                    </div>
                </div>
            </section>

            <section class="admin-surface settings-card">
                <header class="settings-card__head">
                    <i class="pi pi-sync" />
                    <div>
                        <h2 class="vs-card-title">المزامنة</h2>
                        <p class="vs-card-subtitle">التحديث التلقائي من Vinstack</p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div class="field field--row">
                        <Checkbox v-model="form.sync_enabled" binary input-id="sync" />
                        <label for="sync" class="vs-form-label">تفعيل المزامنة التلقائية</label>
                    </div>
                    <div v-if="settings.last_sync_at" class="vs-sync-status">
                        <i class="pi pi-clock" />
                        <span>آخر مزامنة: <strong>{{ settings.last_sync_at }}</strong></span>
                    </div>
                    <div v-else class="vs-sync-status vs-sync-status--muted">
                        <i class="pi pi-info-circle" />
                        <span>لم تُنفَّذ مزامنة بعد</span>
                    </div>
                </div>
            </section>
        </div>

        <div class="settings-footer">
            <Button label="حفظ الإعدادات" icon="pi pi-check" :loading="saving" @click="save" />
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import AdminPageHeader from '../../components/AdminPageHeader.vue';
import api from '../../api/client';

const toast = useToast();
const settings = ref({ has_token: false, last_sync_at: null });
const saving = ref(false);
const syncing = ref(false);

const form = reactive({
    api_base_url: '',
    api_token: '',
    sync_enabled: true,
    support_phone: '',
});

async function load() {
    const { data } = await api.get('/admin/vinstack/settings');
    settings.value = data.data;
    form.api_base_url = data.data.api_base_url || '';
    form.sync_enabled = data.data.sync_enabled ?? true;
    form.support_phone = data.data.support_phone || '';
    form.api_token = '';
}

async function save() {
    saving.value = true;

    try {
        const payload = {
            api_base_url: form.api_base_url,
            sync_enabled: form.sync_enabled,
            support_phone: form.support_phone,
        };

        if (form.api_token) {
            payload.api_token = form.api_token;
        }

        await api.put('/admin/vinstack/settings', payload);
        toast.add({ severity: 'success', summary: 'تم الحفظ', life: 3000 });
        await load();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل الحفظ',
            life: 4000,
        });
    } finally {
        saving.value = false;
    }
}

async function syncNow() {
    syncing.value = true;

    try {
        const { data } = await api.post('/admin/vinstack/sync');
        toast.add({
            severity: 'success',
            summary: data.message,
            detail: `من Vinstack: ${data.total} · جديد: ${data.created} · محدّث: ${data.updated}`,
            life: 5000,
        });
        await load();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشلت المزامنة',
            life: 4000,
        });
    } finally {
        syncing.value = false;
    }
}

onMounted(load);
</script>

<style scoped>
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr));
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.settings-card__head {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    padding: 1.1rem 1.15rem 0;
}

.settings-card__head > i {
    width: 2.25rem;
    height: 2.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: var(--admin-sidebar-active);
    color: var(--admin-accent);
    font-size: 1rem;
    flex-shrink: 0;
}

.settings-card__body {
    padding: 0.85rem 1.15rem 1.15rem;
}

.field {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.field:last-child {
    margin-bottom: 0;
}

.field--row {
    flex-direction: row;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 0.75rem;
}

.settings-footer {
    display: flex;
    justify-content: flex-start;
}

.w-full {
    width: 100%;
}
</style>
