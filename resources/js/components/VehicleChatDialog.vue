<template>
    <Dialog
        v-model:visible="visibleProxy"
        :header="dialogTitle"
        modal
        class="vehicle-chat-dialog"
        :style="{ width: 'min(680px, 94vw)' }"
        :content-style="{ padding: '0 1rem 1rem' }"
        @show="onOpen"
        @hide="onClose"
    >
        <div v-if="vehicle" class="vehicle-chat">
            <div class="vehicle-chat__header" :dir="textDir">
                <div class="vehicle-chat__title">{{ vehicleTitle(vehicle) }}</div>
                <VinCopyLabel :vin="vehicle.vin" block />
            </div>

            <div ref="scrollEl" class="vehicle-chat__messages" dir="ltr">
                <div v-if="loading" class="vehicle-chat__loading">
                    <ProgressSpinner style="width: 28px; height: 28px" stroke-width="4" />
                </div>
                <div v-else-if="!messages.length" class="vehicle-chat__empty" :dir="textDir">
                    {{ t('chat.empty') }}
                </div>
                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="vehicle-chat__row"
                    :class="message.is_mine ? 'vehicle-chat__row--mine' : 'vehicle-chat__row--theirs'"
                >
                    <div
                        v-if="!message.is_mine"
                        class="vehicle-chat__avatar"
                        :class="message.author_role === 'dealer' ? 'vehicle-chat__avatar--dealer' : 'vehicle-chat__avatar--admin'"
                    >
                        {{ message.author_initial }}
                    </div>

                    <div class="vehicle-chat__bubble-wrap">
                        <div
                            class="vehicle-chat__bubble"
                            :dir="textDir"
                            :class="message.author_role === 'dealer' ? 'vehicle-chat__bubble--dealer' : 'vehicle-chat__bubble--admin'"
                        >
                            <div v-if="!message.is_mine" class="vehicle-chat__sender">{{ message.author_name }}</div>
                            <p v-if="message.body" class="vehicle-chat__text">{{ message.body }}</p>
                            <a
                                v-if="message.attachment_url"
                                :href="message.attachment_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="vehicle-chat__image-link"
                            >
                                <img :src="message.attachment_url" :alt="t('chat.attachment')" class="vehicle-chat__image" loading="lazy" />
                            </a>
                        </div>
                        <div
                            class="vehicle-chat__time"
                            dir="ltr"
                            :class="message.is_mine ? 'vehicle-chat__time--mine' : 'vehicle-chat__time--theirs'"
                        >
                            {{ formatDateTime(message.created_at) }}
                        </div>
                    </div>

                    <div
                        v-if="message.is_mine"
                        class="vehicle-chat__avatar"
                        :class="message.author_role === 'dealer' ? 'vehicle-chat__avatar--dealer' : 'vehicle-chat__avatar--admin'"
                    >
                        {{ message.author_initial }}
                    </div>
                </div>
            </div>

            <div class="vehicle-chat__composer" :dir="textDir">
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    class="vehicle-chat__file-input"
                    @change="onFileChange"
                />
                <div v-if="pendingPreview" class="vehicle-chat__pending">
                    <img :src="pendingPreview" :alt="t('chat.preview')" class="vehicle-chat__pending-img" />
                    <Button icon="pi pi-times" text rounded severity="secondary" @click="clearPendingFile" />
                </div>
                <div class="vehicle-chat__composer-box">
                    <Button
                        icon="pi pi-image"
                        severity="secondary"
                        text
                        rounded
                        class="vehicle-chat__attach"
                        :aria-label="t('chat.attachImage')"
                        :disabled="sending"
                        @click="pickFile"
                    />
                    <Textarea
                        v-model="draft"
                        rows="2"
                        auto-resize
                        class="vehicle-chat__input"
                        :placeholder="t('chat.placeholder')"
                        :disabled="sending"
                        @keydown.enter.exact.prevent="sendMessage"
                    />
                    <Button
                        icon="pi pi-send"
                        class="btn-cta vehicle-chat__send"
                        :loading="sending"
                        :disabled="!canSend"
                        :aria-label="t('chat.send')"
                        @click="sendMessage"
                    />
                </div>
            </div>
        </div>
    </Dialog>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import ProgressSpinner from 'primevue/progressspinner';
import { useToast } from 'primevue/usetoast';
import VinCopyLabel from './VinCopyLabel.vue';
import api from '../api/client';
import { vehicleTitle } from '../utils/vehicleMeta';
import { formatDateTime } from '../utils/formatDateTime';
import { useLocaleStore } from '../stores/locale';

const { t } = useI18n();
const localeStore = useLocaleStore();

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    vehicle: {
        type: Object,
        default: null,
    },
    mode: {
        type: String,
        default: 'dealer',
        validator: (v) => ['admin', 'dealer'].includes(v),
    },
});

const emit = defineEmits(['update:visible', 'read', 'sent']);

const toast = useToast();
const messages = ref([]);
const loading = ref(false);
const sending = ref(false);
const draft = ref('');
const pendingFile = ref(null);
const pendingPreview = ref(null);
const scrollEl = ref(null);
const fileInput = ref(null);

const visibleProxy = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});

const apiPrefix = computed(() => (props.mode === 'dealer' ? '/dealer' : '/admin'));

const textDir = computed(() => (localeStore.isRtl ? 'rtl' : 'ltr'));

const dialogTitle = computed(() => (props.mode === 'dealer' ? t('chat.titleDealer') : t('chat.titleAdmin')));

const canSend = computed(() => {
    if (sending.value) {
        return false;
    }

    return draft.value.trim() !== '' || Boolean(pendingFile.value);
});

async function onOpen() {
    await loadMessages();
    await markRead();
}

function onClose() {
    draft.value = '';
    clearPendingFile();
    messages.value = [];
}

async function loadMessages() {
    if (! props.vehicle?.id) {
        return;
    }

    loading.value = true;

    try {
        const { data } = await api.get(`${apiPrefix.value}/vehicles/${props.vehicle.id}/messages`);
        messages.value = data.data ?? [];
        await scrollToBottom();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || t('chat.loadFailed'),
            life: 4000,
        });
    } finally {
        loading.value = false;
    }
}

async function markRead() {
    if (! props.vehicle?.id) {
        return;
    }

    try {
        await api.post(`${apiPrefix.value}/vehicles/${props.vehicle.id}/messages/read`);
        emit('read', props.vehicle);
    } catch {
        // non-blocking
    }
}

async function scrollToBottom() {
    await nextTick();

    if (scrollEl.value) {
        scrollEl.value.scrollTop = scrollEl.value.scrollHeight;
    }
}

function pickFile() {
    fileInput.value?.click();
}

function onFileChange(event) {
    const file = event.target.files?.[0];

    if (! file) {
        return;
    }

    pendingFile.value = file;
    pendingPreview.value = URL.createObjectURL(file);
    event.target.value = '';
}

function clearPendingFile() {
    if (pendingPreview.value) {
        URL.revokeObjectURL(pendingPreview.value);
    }

    pendingFile.value = null;
    pendingPreview.value = null;
}

async function sendMessage() {
    if (! canSend.value || ! props.vehicle?.id) {
        return;
    }

    sending.value = true;
    const form = new FormData();

    if (draft.value.trim()) {
        form.append('body', draft.value.trim());
    }

    if (pendingFile.value) {
        form.append('image', pendingFile.value);
    }

    try {
        const { data } = await api.post(`${apiPrefix.value}/vehicles/${props.vehicle.id}/messages`, form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        messages.value.push(data.data);
        draft.value = '';
        clearPendingFile();
        await scrollToBottom();
        emit('sent', props.vehicle);
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || e.response?.data?.errors?.body?.[0] || t('chat.sendFailed'),
            life: 4000,
        });
    } finally {
        sending.value = false;
    }
}

watch(
    () => props.visible,
    (open) => {
        if (! open) {
            onClose();
        }
    },
);
</script>

<style scoped>
.vehicle-chat {
    display: flex;
    flex-direction: column;
    min-height: 460px;
    max-height: min(78vh, 700px);
}

.vehicle-chat__header {
    padding: 0.75rem 1rem;
    border: 1px solid var(--vs-border);
    border-radius: 0.85rem;
    background: var(--vs-surface-hover);
    margin-bottom: 0.85rem;
}

.vehicle-chat__title {
    font-weight: 700;
    font-size: 1rem;
    margin-bottom: 0.4rem;
}

.vehicle-chat__messages {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 0.5rem 0.35rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    background: var(--vs-surface);
    border: 1px solid var(--vs-border);
    border-radius: 0.85rem;
    scrollbar-width: thin;
    scrollbar-color: var(--vs-border) transparent;
}

.vehicle-chat__messages::-webkit-scrollbar {
    width: 6px;
}

.vehicle-chat__messages::-webkit-scrollbar-thumb {
    background: var(--vs-border);
    border-radius: 999px;
}

.vehicle-chat__loading,
.vehicle-chat__empty {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 200px;
    color: var(--vs-text-muted);
    text-align: center;
    padding: 1rem;
}

.vehicle-chat__row {
    display: flex;
    align-items: flex-end;
    gap: 0.55rem;
    width: 100%;
}

.vehicle-chat__row--mine {
    justify-content: flex-end;
}

.vehicle-chat__row--theirs {
    justify-content: flex-start;
}

.vehicle-chat__avatar {
    width: 2.15rem;
    height: 2.15rem;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.84rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 1px 3px rgb(0 0 0 / 0.18);
}

.vehicle-chat__avatar--dealer {
    background: linear-gradient(145deg, #2563eb, #60a5fa);
}

.vehicle-chat__avatar--admin {
    background: linear-gradient(145deg, #0d9488, #14b8a6);
}

.vehicle-chat__bubble-wrap {
    max-width: min(72%, 440px);
    min-width: 5.5rem;
}

.vehicle-chat__bubble {
    border-radius: 1.1rem;
    padding: 0.65rem 0.9rem;
    box-shadow: 0 1px 3px rgb(0 0 0 / 0.1);
}

.vehicle-chat__row--mine .vehicle-chat__bubble {
    border-end-end-radius: 0.3rem;
}

.vehicle-chat__row--theirs .vehicle-chat__bubble {
    border-end-start-radius: 0.3rem;
}

.vehicle-chat__bubble--dealer {
    background: linear-gradient(145deg, #dbeafe, #bfdbfe);
    color: #0f172a;
}

.vehicle-chat__bubble--admin {
    background: linear-gradient(145deg, #ccfbf1, #99f6e4);
    color: #134e4a;
}

[data-theme='dark'] .vehicle-chat__bubble--dealer {
    background: linear-gradient(145deg, #1e3a5f, #1d4ed8);
    color: #e2e8f0;
}

[data-theme='dark'] .vehicle-chat__bubble--admin {
    background: linear-gradient(145deg, #134e4a, #0f766e);
    color: #ecfdf5;
}

.vehicle-chat__sender {
    font-size: 0.7rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    opacity: 0.88;
}

.vehicle-chat__text {
    margin: 0;
    white-space: pre-wrap;
    line-height: 1.55;
    word-break: break-word;
    font-size: 0.92rem;
}

.vehicle-chat__image-link {
    display: block;
    margin-top: 0.45rem;
}

.vehicle-chat__image {
    display: block;
    width: 100%;
    max-width: 280px;
    border-radius: 0.65rem;
    border: 1px solid rgb(0 0 0 / 0.08);
}

.vehicle-chat__time {
    font-size: 0.66rem;
    color: var(--vs-text-subtle);
    margin-top: 0.25rem;
    padding-inline: 0.35rem;
}

.vehicle-chat__time--mine {
    text-align: end;
}

.vehicle-chat__time--theirs {
    text-align: start;
}

.vehicle-chat__composer {
    padding-top: 0.75rem;
    margin-top: 0.65rem;
    border-top: 1px solid var(--vs-border);
}

.vehicle-chat__file-input {
    display: none;
}

.vehicle-chat__pending {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.55rem;
    padding-inline: 0.25rem;
}

.vehicle-chat__pending-img {
    width: 76px;
    height: 76px;
    object-fit: cover;
    border-radius: 0.55rem;
    border: 1px solid var(--vs-border);
}

.vehicle-chat__composer-box {
    display: flex;
    align-items: flex-end;
    gap: 0.45rem;
    padding: 0.45rem 0.5rem;
    border: 1px solid var(--vs-border);
    border-radius: 1.25rem;
    background: var(--vs-surface-hover);
}

.vehicle-chat__input {
    flex: 1;
    min-width: 0;
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    padding: 0.35rem 0.15rem !important;
    font-size: 0.92rem;
    line-height: 1.45;
    max-height: 120px;
}

.vehicle-chat__input:focus {
    outline: none;
    box-shadow: none !important;
}

.vehicle-chat__attach,
.vehicle-chat__send {
    flex-shrink: 0;
}

.vehicle-chat__send {
    width: 2.5rem !important;
    height: 2.5rem !important;
    padding: 0 !important;
    border-radius: 50% !important;
}

.vehicle-chat__send :deep(.p-button-icon) {
    transform: scaleX(-1);
}

@media (max-width: 520px) {
    .vehicle-chat {
        min-height: 70vh;
        max-height: 82vh;
    }

    .vehicle-chat__bubble-wrap {
        max-width: 82%;
    }
}
</style>

<style>
.vehicle-chat-dialog .p-dialog-header {
    padding: 1rem 1.25rem 0.65rem;
}

.vehicle-chat-dialog .p-dialog-content {
    overflow: hidden;
}
</style>
