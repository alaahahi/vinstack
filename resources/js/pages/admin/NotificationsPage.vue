<template>
    <div class="admin-page dealer-notifications-page">
        <div class="notifications-grid">
            <section class="admin-surface settings-card settings-card--wide">
                <header class="settings-card__head">
                    <i class="pi pi-whatsapp" />
                    <div>
                        <h2 class="vs-card-title">{{ t('dealerNotifications.waQueueTitle') }}</h2>
                        <p class="vs-card-subtitle">{{ t('dealerNotifications.waQueueSub') }}</p>
                    </div>
                    <Tag
                        :severity="settings.configured ? 'success' : 'warn'"
                        :value="settings.configured ? t('dealerNotifications.configured') : t('dealerNotifications.notConfigured')"
                    />
                </header>

                <div class="settings-card__body">
                    <div class="field">
                        <label for="wa-base" class="vs-form-label">WA Queue Base URL</label>
                        <InputText
                            id="wa-base"
                            v-model="form.wa_queue_base_url"
                            class="w-full"
                            dir="ltr"
                            placeholder="https://tenant.wa-queue.test/api/v1"
                        />
                        <small class="field-hint">{{ t('dealerNotifications.baseUrlHint') }}</small>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label for="wa-sender" class="vs-form-label">{{ t('dealerNotifications.senderId') }}</label>
                            <InputNumber
                                id="wa-sender"
                                v-model="form.wa_queue_sender_id"
                                class="w-full"
                                :use-grouping="false"
                                input-class="w-full"
                            />
                        </div>
                        <div class="field field--toggle">
                            <label class="vs-form-label">{{ t('dealerNotifications.enable') }}</label>
                            <ToggleSwitch v-model="form.wa_queue_enabled" />
                        </div>
                    </div>

                    <div class="actions-row">
                        <Button
                            :label="t('dealerNotifications.saveSettings')"
                            icon="pi pi-save"
                            :loading="savingSettings"
                            @click="saveSettings"
                        />
                        <Button
                            :label="t('dealerNotifications.testConnection')"
                            icon="pi pi-bolt"
                            severity="secondary"
                            outlined
                            :loading="testing"
                            @click="testConnection"
                        />
                    </div>

                    <div v-if="connectionResult" class="connection-result" :class="connectionResult.ok ? 'connection-result--ok' : 'connection-result--error'">
                        <i class="pi" :class="connectionResult.ok ? 'pi-check-circle' : 'pi-times-circle'" />
                        <span>{{ connectionResult.message }}</span>
                    </div>

                    <div v-if="connectionResult?.senders?.length" class="senders-list">
                        <h3 class="senders-list__title">{{ t('dealerNotifications.senders') }}</h3>
                        <div
                            v-for="sender in connectionResult.senders"
                            :key="sender.id"
                            class="sender-card"
                            :class="{ 'sender-card--online': sender.api_connected }"
                        >
                            <strong>{{ sender.name }}</strong>
                            <span dir="ltr">{{ sender.phone }}</span>
                            <Tag
                                :severity="sender.api_connected ? 'success' : 'danger'"
                                :value="sender.status_label || sender.status"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-surface settings-card">
                <header class="settings-card__head">
                    <i class="pi pi-send" />
                    <div>
                        <h2 class="vs-card-title">{{ t('dealerNotifications.sendTitle') }}</h2>
                        <p class="vs-card-subtitle">{{ t('dealerNotifications.sendSub') }}</p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div class="field">
                        <label for="dealer-select" class="vs-form-label">{{ t('dealerNotifications.selectDealer') }}</label>
                        <Select
                            id="dealer-select"
                            v-model="sendForm.dealer_id"
                            :options="dealers"
                            option-label="company_name"
                            option-value="id"
                            :placeholder="t('dealerNotifications.selectDealerPlaceholder')"
                            class="w-full"
                            filter
                        >
                            <template #option="{ option }">
                                <div class="dealer-option">
                                    <span>{{ option.company_name }}</span>
                                    <span class="dealer-option__phone" dir="ltr">{{ option.phone || '—' }}</span>
                                </div>
                            </template>
                        </Select>
                    </div>

                    <div class="field">
                        <label for="message-body" class="vs-form-label">{{ t('dealerNotifications.message') }}</label>
                        <Textarea
                            id="message-body"
                            v-model="sendForm.message"
                            rows="5"
                            class="w-full"
                            :placeholder="t('dealerNotifications.messagePlaceholder')"
                            auto-resize
                        />
                    </div>

                    <Button
                        :label="t('dealerNotifications.sendNow')"
                        icon="pi pi-whatsapp"
                        :loading="sending"
                        :disabled="!canSend"
                        @click="sendNotification"
                    />
                </div>
            </section>

            <section class="admin-surface settings-card settings-card--wide">
                <header class="settings-card__head">
                    <i class="pi pi-list" />
                    <div>
                        <h2 class="vs-card-title">{{ t('dealerNotifications.logTitle') }}</h2>
                        <p class="vs-card-subtitle">{{ t('dealerNotifications.logSub') }}</p>
                    </div>
                    <Button icon="pi pi-refresh" text rounded :loading="loadingLog" @click="loadLog" />
                </header>

                <div class="settings-card__body">
                    <div v-if="loadingLog" class="log-loading">
                        <ProgressSpinner style="width: 28px; height: 28px" />
                    </div>
                    <p v-else-if="!logs.length" class="log-empty">{{ t('dealerNotifications.logEmpty') }}</p>
                    <ul v-else class="log-list">
                        <li v-for="row in logs" :key="row.id" class="log-item" :class="{ 'log-item--failed': !row.success }">
                            <div class="log-item__top">
                                <strong>{{ row.dealer_name || t('notifications.dealerFallback') }}</strong>
                                <Tag :severity="row.success ? 'success' : 'danger'" :value="statusLabel(row)" />
                            </div>
                            <p class="log-item__message">{{ row.message }}</p>
                            <div class="log-item__meta">
                                <span dir="ltr">{{ row.phone }}</span>
                                <span>{{ formatDateTime(row.created_at) }}</span>
                                <span v-if="row.author_name">{{ row.author_name }}</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import api from '../../api/client';
import { formatDateTime } from '../../utils/formatDateTime';

const { t } = useI18n();
const toast = useToast();

const settings = ref({ configured: false });
const form = reactive({
    wa_queue_base_url: '',
    wa_queue_sender_id: null,
    wa_queue_enabled: false,
});
const sendForm = reactive({
    dealer_id: null,
    message: '',
});
const dealers = ref([]);
const logs = ref([]);
const connectionResult = ref(null);
const savingSettings = ref(false);
const testing = ref(false);
const sending = ref(false);
const loadingLog = ref(false);

const canSend = computed(() =>
    Boolean(sendForm.dealer_id)
    && sendForm.message.trim().length > 0
    && settings.value.configured,
);

function statusLabel(row) {
    if (row.error_message) {
        return t('dealerNotifications.statusFailed');
    }

    return row.wa_queue_status || t('dealerNotifications.statusQueued');
}

async function loadSettings() {
    const { data } = await api.get('/admin/wa-queue/settings');
    const payload = data.data ?? {};

    settings.value = payload;
    form.wa_queue_base_url = payload.wa_queue_base_url ?? '';
    form.wa_queue_sender_id = payload.wa_queue_sender_id ?? null;
    form.wa_queue_enabled = Boolean(payload.wa_queue_enabled);
}

async function loadDealers() {
    const { data } = await api.get('/admin/dealer-notifications/dealers');
    dealers.value = data.data ?? [];
}

async function loadLog() {
    loadingLog.value = true;

    try {
        const { data } = await api.get('/admin/dealer-notifications');
        logs.value = data.data ?? [];
    } finally {
        loadingLog.value = false;
    }
}

async function saveSettings() {
    savingSettings.value = true;

    try {
        const { data } = await api.put('/admin/wa-queue/settings', {
            wa_queue_base_url: form.wa_queue_base_url || null,
            wa_queue_sender_id: form.wa_queue_sender_id || null,
            wa_queue_enabled: form.wa_queue_enabled,
        });

        settings.value = data.data ?? settings.value;
        toast.add({ severity: 'success', summary: data.message || t('dealerNotifications.saved'), life: 3000 });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || t('dealerNotifications.saveFailed'),
            life: 4500,
        });
    } finally {
        savingSettings.value = false;
    }
}

async function testConnection() {
    testing.value = true;
    connectionResult.value = null;

    try {
        const { data } = await api.post('/admin/wa-queue/test-connection');
        connectionResult.value = data.data ?? { ok: true, message: data.message };
        toast.add({
            severity: connectionResult.value.ok ? 'success' : 'warn',
            summary: data.message,
            life: 4000,
        });
    } catch (e) {
        connectionResult.value = e.response?.data?.data ?? {
            ok: false,
            message: e.response?.data?.message || t('dealerNotifications.testFailed'),
        };
        toast.add({
            severity: 'error',
            summary: connectionResult.value.message,
            life: 5000,
        });
    } finally {
        testing.value = false;
    }
}

async function sendNotification() {
    if (! canSend.value) {
        return;
    }

    sending.value = true;

    try {
        const { data } = await api.post('/admin/dealer-notifications/send', {
            dealer_id: sendForm.dealer_id,
            message: sendForm.message.trim(),
        });

        toast.add({ severity: 'success', summary: data.message, life: 4000 });
        sendForm.message = '';

        if (data.data) {
            logs.value = [data.data, ...logs.value];
        } else {
            await loadLog();
        }
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || t('dealerNotifications.sendFailed'),
            life: 5000,
        });
    } finally {
        sending.value = false;
    }
}

onMounted(async () => {
    await Promise.all([loadSettings(), loadDealers(), loadLog()]);
});
</script>

<style scoped>
.notifications-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    max-width: 1200px;
}

.settings-card--wide {
    grid-column: 1 / -1;
}

.settings-card__head {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.settings-card__head > i {
    font-size: 1.25rem;
    color: var(--admin-accent, #7c3aed);
    margin-top: 0.15rem;
}

.settings-card__body {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.field-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    align-items: end;
}

.field--toggle {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding-bottom: 0.35rem;
}

.field-hint {
    display: block;
    margin-top: 0.35rem;
    color: var(--vs-text-muted);
    font-size: 0.78rem;
}

.actions-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
}

.connection-result {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.75rem 0.9rem;
    border-radius: 10px;
    font-size: 0.88rem;
    line-height: 1.45;
}

.connection-result--ok {
    background: #ecfdf5;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.connection-result--error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.senders-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.senders-list__title {
    margin: 0;
    font-size: 0.85rem;
    font-weight: 600;
}

.sender-card {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.65rem;
    padding: 0.65rem 0.8rem;
    border: 1px solid var(--vs-border);
    border-radius: 10px;
    background: var(--vs-surface-elevated);
    font-size: 0.84rem;
}

.dealer-option {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.dealer-option__phone {
    font-size: 0.78rem;
    color: var(--vs-text-muted);
}

.log-loading,
.log-empty {
    text-align: center;
    color: var(--vs-text-muted);
    padding: 1rem 0;
}

.log-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.log-item {
    padding: 0.8rem 0.9rem;
    border: 1px solid var(--vs-border);
    border-radius: 10px;
    background: var(--vs-surface-elevated);
}

.log-item--failed {
    border-color: #fecaca;
}

.log-item__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.35rem;
}

.log-item__message {
    margin: 0 0 0.45rem;
    white-space: pre-wrap;
    line-height: 1.45;
    font-size: 0.88rem;
}

.log-item__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    font-size: 0.75rem;
    color: var(--vs-text-muted);
}

@media (max-width: 900px) {
    .notifications-grid {
        grid-template-columns: 1fr;
    }

    .field-row {
        grid-template-columns: 1fr;
    }
}
</style>
