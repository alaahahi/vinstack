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

                    <div v-if="eventCatalog.length" class="events-panel">
                        <div class="events-panel__head">
                            <h3 class="events-panel__title">{{ t('dealerNotifications.eventsTitle') }}</h3>
                            <p class="events-panel__sub">{{ t('dealerNotifications.eventsSub') }}</p>
                        </div>
                        <ul class="events-list">
                            <li v-for="item in eventCatalog" :key="item.key" class="events-list__item">
                                <div class="events-list__text">
                                    <strong>{{ eventLabel(item.key) }}</strong>
                                    <small>{{ item.key }}</small>
                                </div>
                                <ToggleSwitch v-model="form.dealer_notification_events[item.key]" />
                            </li>
                        </ul>
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
                                class="w-full send-select"
                                size="small"
                                filter
                                :disabled="sendForm.send_to_all"
                            >
                                <template #option="{ option }">
                                    <div class="dealer-option">
                                        <span>{{ option.company_name }}</span>
                                        <span class="dealer-option__phone" dir="ltr">{{ option.phone || '—' }}</span>
                                    </div>
                                </template>
                            </Select>
                            <label class="send-all-toggle">
                                <Checkbox v-model="sendForm.send_to_all" binary input-id="send-to-all" />
                                <span>
                                    {{ t('dealerNotifications.sendToAll') }}
                                    <small v-if="dealersWithPhoneCount">({{ dealersWithPhoneCount }})</small>
                                </span>
                            </label>
                        </div>
                        <div class="field field--grow">
                            <label for="message-body" class="vs-form-label">{{ t('dealerNotifications.message') }}</label>
                            <Textarea
                                id="message-body"
                                v-model="sendForm.message"
                                rows="4"
                                class="w-full"
                                size="small"
                                :placeholder="t('dealerNotifications.messagePlaceholder')"
                                auto-resize
                            />
                        </div>
                    </div>
                </div>

                <footer class="notif-card__footer">
                    <Button
                        :label="sendButtonLabel"
                        icon="pi pi-whatsapp"
                        size="small"
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
                                <span v-if="row.event" class="log-item__event">{{ eventLabel(row.event) }}</span>
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
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
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
    dealer_notification_events: {},
});
const eventCatalog = ref([]);
const sendForm = reactive({
    dealer_id: null,
    message: '',
    send_to_all: false,
});
const dealers = ref([]);
const logs = ref([]);
const connectionResult = ref(null);
const savingSettings = ref(false);
const testing = ref(false);
const sending = ref(false);
const loadingLog = ref(false);

const dealersWithPhoneCount = computed(() =>
    dealers.value.filter((dealer) => dealer.has_phone).length,
);

const canSend = computed(() => {
    if (!settings.value.configured || sendForm.message.trim().length === 0) {
        return false;
    }

    if (sendForm.send_to_all) {
        return dealersWithPhoneCount.value > 0;
    }

    return Boolean(sendForm.dealer_id);
});

const sendButtonLabel = computed(() =>
    sendForm.send_to_all
        ? t('dealerNotifications.sendToAllNow')
        : t('dealerNotifications.sendNow'),
);

watch(
    () => sendForm.send_to_all,
    (sendToAll) => {
        if (sendToAll) {
            sendForm.dealer_id = null;
        }
    },
);

function eventLabel(key) {
    if (!key) {
        return '';
    }

    const i18nKey = `dealerNotifications.events.${key}`;

    if (t(i18nKey) !== i18nKey) {
        return t(i18nKey);
    }

    return key;
}

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
    eventCatalog.value = payload.dealer_notification_event_catalog ?? [];
    form.dealer_notification_events = { ...(payload.dealer_notification_events ?? {}) };
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
            dealer_notification_events: form.dealer_notification_events,
        });

        settings.value = data.data ?? settings.value;
        if (data.data?.dealer_notification_events) {
            form.dealer_notification_events = { ...data.data.dealer_notification_events };
        }
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
        const payload = {
            message: sendForm.message.trim(),
            send_to_all: sendForm.send_to_all,
        };

        if (!sendForm.send_to_all) {
            payload.dealer_id = sendForm.dealer_id;
        }

        const { data } = await api.post('/admin/dealer-notifications/send', payload);

        toast.add({
            severity: data.failed > 0 && data.sent > 0 ? 'warn' : 'success',
            summary: data.message,
            life: 5000,
        });
        sendForm.message = '';

        if (Array.isArray(data.data) && data.data.length) {
            logs.value = [...data.data, ...logs.value];
        } else if (data.data) {
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
    background: color-mix(in srgb, #25d366 18%, var(--vs-surface-elevated));
    color: #34d399;
}

.notif-card__icon--send {
    background: color-mix(in srgb, var(--admin-accent, #7c3aed) 14%, transparent);
    color: var(--admin-accent, #7c3aed);
}

.notif-card__icon--log {
    background: color-mix(in srgb, #3b82f6 16%, var(--vs-surface-elevated));
    color: #60a5fa;
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
    grid-template-columns: minmax(200px, 1fr) 2fr;
    align-items: start;
}

.send-select {
    max-width: 14rem;
}

.send-all-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    margin-top: 0.45rem;
    font-size: 0.82rem;
    color: var(--vs-text-secondary);
    cursor: pointer;
    user-select: none;
}

.send-all-toggle small {
    color: var(--vs-text-muted);
    font-size: 0.75rem;
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

.events-panel {
    border: 1px solid var(--vs-border);
    border-radius: 10px;
    padding: 1rem 1.1rem;
    background: color-mix(in srgb, var(--vs-surface-elevated) 88%, transparent);
}

.events-panel__head {
    margin-bottom: 0.85rem;
}

.events-panel__title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
}

.events-panel__sub {
    margin: 0.25rem 0 0;
    font-size: 0.78rem;
    color: var(--vs-text-muted);
}

.events-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

.events-list__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.55rem 0;
    border-top: 1px solid var(--vs-border);
}

.events-list__item:first-child {
    border-top: none;
    padding-top: 0;
}

.events-list__text {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.events-list__text strong {
    font-size: 0.86rem;
    font-weight: 600;
}

.events-list__text small {
    font-size: 0.72rem;
    color: var(--vs-text-muted);
    direction: ltr;
    text-align: start;
}

.log-item__event {
    font-size: 0.75rem;
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--admin-accent, #7c3aed) 12%, transparent);
    color: var(--admin-accent, #7c3aed);
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
    background: var(--vs-alert-success-bg);
    color: var(--vs-alert-success-fg);
    border: 1px solid var(--vs-alert-success-border);
}

.connection-result--error {
    background: var(--vs-alert-danger-bg);
    color: var(--vs-alert-danger-fg);
    border: 1px solid var(--vs-alert-danger-border);
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
    border-color: var(--vs-alert-success-border);
    background: color-mix(in srgb, var(--vs-alert-success-bg) 65%, var(--vs-surface));
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
    background: var(--vs-log-surface);
    color: var(--vs-text);
}

.log-item--failed {
    border-color: var(--vs-alert-danger-border);
    background: var(--vs-log-surface-failed);
}

.log-item__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.log-item__top strong {
    color: var(--text-primary, var(--vs-text));
    font-weight: 700;
}

.log-item__message {
    margin: 0 0 0.6rem;
    white-space: pre-wrap;
    line-height: 1.5;
    font-size: 0.88rem;
    color: var(--vs-text-secondary);
}

.log-item--failed .log-item__message {
    color: var(--vs-text);
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
    color: var(--vs-text-subtle);
    opacity: 1;
}

.log-item :deep(.p-tag.p-tag-danger) {
    background: var(--vs-alert-danger-bg);
    color: var(--vs-alert-danger-fg);
    border: 1px solid var(--vs-alert-danger-border);
}

.log-item :deep(.p-tag.p-tag-success) {
    background: var(--vs-alert-success-bg);
    color: var(--vs-alert-success-fg);
    border: 1px solid var(--vs-alert-success-border);
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
