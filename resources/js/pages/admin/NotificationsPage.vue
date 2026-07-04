<template>
    <div class="admin-page dealer-notifications-page">
        <div class="notifications-stack">
            <!-- ربط WA Queue -->
            <section class="admin-surface notif-card notif-card--wa">
                <header class="notif-card__head">
                    <span class="notif-card__icon notif-card__icon--wa">
                        <i class="pi pi-whatsapp" />
                    </span>
                    <div class="notif-card__titles">
                        <h2 class="vs-card-title">{{ t('dealerNotifications.waQueueTitle') }}</h2>
                        <p class="vs-card-subtitle">{{ t('dealerNotifications.waQueueSub') }}</p>
                    </div>
                    <Tag
                        class="notif-card__badge"
                        :severity="settings.configured ? 'success' : 'warn'"
                        :value="settings.configured ? t('dealerNotifications.configured') : t('dealerNotifications.notConfigured')"
                    />
                </header>

                <div class="notif-card__body">
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

                    <div class="field-grid">
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
                            <div class="toggle-wrap">
                                <ToggleSwitch v-model="form.wa_queue_enabled" />
                            </div>
                        </div>
                    </div>

                    <div v-if="connectionResult" class="connection-result" :class="connectionResult.ok ? 'connection-result--ok' : 'connection-result--error'">
                        <i class="pi" :class="connectionResult.ok ? 'pi-check-circle' : 'pi-times-circle'" />
                        <span>{{ connectionResult.message }}</span>
                    </div>

                    <div v-if="connectionResult?.senders?.length" class="senders-list">
                        <h3 class="senders-list__title">{{ t('dealerNotifications.senders') }}</h3>
                        <div class="senders-grid">
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
                </div>

                <footer class="notif-card__footer">
                    <Button
                        :label="t('dealerNotifications.testConnection')"
                        icon="pi pi-bolt"
                        severity="secondary"
                        outlined
                        :loading="testing"
                        @click="testConnection"
                    />
                    <Button
                        :label="t('dealerNotifications.saveSettings')"
                        icon="pi pi-save"
                        :loading="savingSettings"
                        @click="saveSettings"
                    />
                </footer>
            </section>

            <!-- إرسال يدوي -->
            <section class="admin-surface notif-card">
                <header class="notif-card__head">
                    <span class="notif-card__icon notif-card__icon--send">
                        <i class="pi pi-send" />
                    </span>
                    <div class="notif-card__titles">
                        <h2 class="vs-card-title">{{ t('dealerNotifications.sendTitle') }}</h2>
                        <p class="vs-card-subtitle">{{ t('dealerNotifications.sendSub') }}</p>
                    </div>
                </header>

                <div class="notif-card__body">
                    <div class="field-grid field-grid--send">
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
                        <div class="field field--grow">
                            <label for="message-body" class="vs-form-label">{{ t('dealerNotifications.message') }}</label>
                            <Textarea
                                id="message-body"
                                v-model="sendForm.message"
                                rows="4"
                                class="w-full"
                                :placeholder="t('dealerNotifications.messagePlaceholder')"
                                auto-resize
                            />
                        </div>
                    </div>
                </div>

                <footer class="notif-card__footer">
                    <Button
                        :label="t('dealerNotifications.sendNow')"
                        icon="pi pi-whatsapp"
                        :loading="sending"
                        :disabled="!canSend"
                        @click="sendNotification"
                    />
                </footer>
            </section>

            <!-- السجل -->
            <section class="admin-surface notif-card">
                <header class="notif-card__head">
                    <span class="notif-card__icon notif-card__icon--log">
                        <i class="pi pi-list" />
                    </span>
                    <div class="notif-card__titles">
                        <h2 class="vs-card-title">{{ t('dealerNotifications.logTitle') }}</h2>
                        <p class="vs-card-subtitle">{{ t('dealerNotifications.logSub') }}</p>
                    </div>
                    <Button
                        class="notif-card__refresh"
                        icon="pi pi-refresh"
                        text
                        rounded
                        :loading="loadingLog"
                        @click="loadLog"
                    />
                </header>

                <div class="notif-card__body notif-card__body--log">
                    <div v-if="loadingLog" class="log-loading">
                        <ProgressSpinner style="width: 32px; height: 32px" />
                    </div>
                    <div v-else-if="!logs.length" class="log-empty">
                        <i class="pi pi-inbox" />
                        <p>{{ t('dealerNotifications.logEmpty') }}</p>
                    </div>
                    <ul v-else class="log-list">
                        <li v-for="row in logs" :key="row.id" class="log-item" :class="{ 'log-item--failed': !row.success }">
                            <div class="log-item__top">
                                <strong>{{ row.dealer_name || t('notifications.dealerFallback') }}</strong>
                                <Tag :severity="row.success ? 'success' : 'danger'" :value="statusLabel(row)" />
                            </div>
                            <p class="log-item__message">{{ row.message }}</p>
                            <div class="log-item__meta">
                                <span dir="ltr"><i class="pi pi-phone" /> {{ row.phone }}</span>
                                <span><i class="pi pi-clock" /> {{ formatDateTime(row.created_at) }}</span>
                                <span v-if="row.author_name"><i class="pi pi-user" /> {{ row.author_name }}</span>
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
        const msg = e.response?.data?.message || t('dealerNotifications.sendFailed');
        toast.add({
            severity: 'error',
            summary: msg,
            detail: e.response?.data?.errors
                ? Object.values(e.response.data.errors).flat().join(' · ')
                : undefined,
            life: 6000,
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
.dealer-notifications-page {
    padding: 0.25rem 0 1.5rem;
}

.notifications-stack {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    max-width: 960px;
}

/* ── Card shell ── */
.notif-card {
    overflow: hidden;
}

.notif-card--wa {
    border-color: color-mix(in srgb, #25d366 22%, var(--vs-border));
}

.notif-card__head {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--vs-border);
    background: var(--vs-surface-elevated);
}

.notif-card__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 10px;
    flex-shrink: 0;
    font-size: 1.1rem;
}

.notif-card__icon--wa {
    background: rgba(37, 211, 102, 0.14);
    color: #128c7e;
}

.notif-card__icon--send {
    background: color-mix(in srgb, var(--admin-accent, #7c3aed) 14%, transparent);
    color: var(--admin-accent, #7c3aed);
}

.notif-card__icon--log {
    background: rgba(59, 130, 246, 0.12);
    color: #2563eb;
}

.notif-card__titles {
    flex: 1;
    min-width: 0;
}

.notif-card__badge {
    flex-shrink: 0;
    margin-top: 0.15rem;
}

.notif-card__refresh {
    flex-shrink: 0;
    margin-top: -0.15rem;
}

.notif-card__body {
    padding: 1.35rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.15rem;
}

.notif-card__body--log {
    padding-top: 1rem;
    padding-bottom: 1.25rem;
}

.notif-card__footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.65rem;
    padding: 1rem 1.5rem 1.25rem;
    border-top: 1px solid var(--vs-border);
    background: var(--vs-surface-elevated);
}

/* ── Fields ── */
.field {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.field-grid {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1.25rem;
    align-items: end;
}

.field-grid--send {
    grid-template-columns: minmax(220px, 1fr) 2fr;
    align-items: start;
}

.field--toggle {
    padding-bottom: 0.4rem;
}

.toggle-wrap {
    min-height: 2.25rem;
    display: flex;
    align-items: center;
}

.field-hint {
    display: block;
    color: var(--vs-text-muted);
    font-size: 0.78rem;
    line-height: 1.4;
}

.w-full {
    width: 100%;
}

/* ── Connection result ── */
.connection-result {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    padding: 0.9rem 1.1rem;
    border-radius: 10px;
    font-size: 0.88rem;
    line-height: 1.5;
}

.connection-result > i {
    margin-top: 0.1rem;
    flex-shrink: 0;
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

/* ── Senders ── */
.senders-list {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.senders-list__title {
    margin: 0;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--vs-text-secondary);
}

.senders-grid {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

.sender-card {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border: 1px solid var(--vs-border);
    border-radius: 10px;
    background: var(--vs-surface);
    font-size: 0.84rem;
}

.sender-card--online {
    border-color: #bbf7d0;
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

/* ── Log ── */
.log-loading {
    display: flex;
    justify-content: center;
    padding: 2rem 0;
}

.log-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 2.5rem 1rem;
    text-align: center;
    color: var(--vs-text-muted);
}

.log-empty i {
    font-size: 1.75rem;
    opacity: 0.45;
}

.log-empty p {
    margin: 0;
    font-size: 0.88rem;
}

.log-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.log-item {
    padding: 1rem 1.15rem;
    border: 1px solid var(--vs-border);
    border-radius: 10px;
    background: var(--vs-surface);
}

.log-item--failed {
    border-color: #fecaca;
    background: #fffbfb;
}

.log-item__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.log-item__message {
    margin: 0 0 0.6rem;
    white-space: pre-wrap;
    line-height: 1.5;
    font-size: 0.88rem;
}

.log-item__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.85rem;
    font-size: 0.76rem;
    color: var(--vs-text-muted);
}

.log-item__meta span {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.log-item__meta i {
    font-size: 0.7rem;
    opacity: 0.7;
}

@media (max-width: 768px) {
    .notif-card__head,
    .notif-card__body,
    .notif-card__footer {
        padding-inline: 1.1rem;
    }

    .notif-card__head {
        padding-block: 1rem;
    }

    .notif-card__body {
        padding-block: 1.1rem;
    }

    .field-grid,
    .field-grid--send {
        grid-template-columns: 1fr;
    }

    .field--toggle {
        padding-bottom: 0;
    }
}
</style>
