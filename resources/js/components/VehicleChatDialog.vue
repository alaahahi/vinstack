<template>
    <Dialog
        v-model:visible="visibleProxy"
        :header="dialogTitle"
        modal
        class="vehicle-chat-dialog"
        :style="{ width: 'min(560px, 100vw)' }"
        @show="onOpen"
        @hide="onClose"
    >
        <div v-if="vehicle" class="vehicle-chat">
            <div class="vehicle-chat__header">
                <div class="vehicle-chat__title">{{ vehicleTitle(vehicle) }}</div>
                <VinCopyLabel :vin="vehicle.vin" block />
            </div>

            <div ref="scrollEl" class="vehicle-chat__messages">
                <div v-if="loading" class="vehicle-chat__loading">
                    <ProgressSpinner style="width: 28px; height: 28px" stroke-width="4" />
                </div>
                <div v-else-if="!messages.length" class="vehicle-chat__empty">
                    ابدأ المحادثة بإرسال رسالة أو صورة.
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
                                <img :src="message.attachment_url" alt="مرفق" class="vehicle-chat__image" loading="lazy" />
                            </a>
                        </div>
                        <div class="vehicle-chat__time" dir="ltr">{{ formatDateTime(message.created_at) }}</div>
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

            <div class="vehicle-chat__composer">
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    class="vehicle-chat__file-input"
                    @change="onFileChange"
                />
                <div v-if="pendingPreview" class="vehicle-chat__pending">
                    <img :src="pendingPreview" alt="معاينة" class="vehicle-chat__pending-img" />
                    <Button icon="pi pi-times" text rounded severity="secondary" @click="clearPendingFile" />
                </div>
                <div class="vehicle-chat__composer-row">
                    <Button
                        icon="pi pi-image"
                        severity="secondary"
                        text
                        rounded
                        aria-label="إرفاق صورة"
                        :disabled="sending"
                        @click="pickFile"
                    />
                    <Textarea
                        v-model="draft"
                        rows="2"
                        auto-resize
                        class="vehicle-chat__input"
                        placeholder="اكتب رسالتك..."
                        :disabled="sending"
                        @keydown.enter.exact.prevent="sendMessage"
                    />
                    <Button
                        icon="pi pi-send"
                        class="btn-cta vehicle-chat__send"
                        :loading="sending"
                        :disabled="!canSend"
                        aria-label="إرسال"
                        @click="sendMessage"
                    />
                </div>
            </div>
        </div>
    </Dialog>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import ProgressSpinner from 'primevue/progressspinner';
import { useToast } from 'primevue/usetoast';
import VinCopyLabel from './VinCopyLabel.vue';
import api from '../api/client';
import { vehicleTitle } from '../utils/vehicleMeta';
import { formatDateTime } from '../utils/formatDateTime';

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

const dialogTitle = computed(() => (props.mode === 'dealer' ? 'محادثة السيارة' : 'محادثة مع التاجر'));

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
            summary: 'خطأ',
            detail: e.response?.data?.message || 'تعذّر تحميل المحادثة',
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
            summary: 'خطأ',
            detail: e.response?.data?.message || e.response?.data?.errors?.body?.[0] || 'فشل إرسال الرسالة',
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
    min-height: 420px;
    max-height: min(72vh, 640px);
}

.vehicle-chat__header {
    padding: 0.65rem 0.85rem;
    border: 1px solid var(--vs-border);
    border-radius: 0.75rem;
    background: var(--vs-surface-hover);
    margin-bottom: 0.75rem;
}

.vehicle-chat__title {
    font-weight: 700;
    margin-bottom: 0.35rem;
}

.vehicle-chat__messages {
    flex: 1;
    overflow-y: auto;
    padding: 0.35rem 0.15rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.vehicle-chat__loading,
.vehicle-chat__empty {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 180px;
    color: var(--vs-text-muted);
    text-align: center;
    padding: 1rem;
}

.vehicle-chat__row {
    display: flex;
    align-items: flex-end;
    gap: 0.45rem;
    max-width: 100%;
}

.vehicle-chat__row--mine {
    justify-content: flex-start;
}

.vehicle-chat__row--theirs {
    justify-content: flex-end;
    flex-direction: row-reverse;
}

.vehicle-chat__avatar {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.82rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}

.vehicle-chat__avatar--dealer {
    background: linear-gradient(145deg, #2563eb, #60a5fa);
}

.vehicle-chat__avatar--admin {
    background: linear-gradient(145deg, #0d9488, #14b8a6);
}

.vehicle-chat__bubble-wrap {
    max-width: min(78%, 360px);
    min-width: 0;
}

.vehicle-chat__bubble {
    border-radius: 1rem;
    padding: 0.55rem 0.75rem;
    box-shadow: 0 1px 2px rgb(0 0 0 / 0.08);
}

.vehicle-chat__bubble--dealer {
    background: #dbeafe;
    color: #0f172a;
}

.vehicle-chat__bubble--admin {
    background: #ccfbf1;
    color: #134e4a;
}

.vehicle-chat__row--mine .vehicle-chat__bubble--dealer,
.vehicle-chat__row--mine .vehicle-chat__bubble--admin {
    border-end-end-radius: 0.25rem;
}

.vehicle-chat__row--theirs .vehicle-chat__bubble--dealer,
.vehicle-chat__row--theirs .vehicle-chat__bubble--admin {
    border-end-start-radius: 0.25rem;
}

[data-theme='dark'] .vehicle-chat__bubble--dealer {
    background: #1e3a5f;
    color: #e2e8f0;
}

[data-theme='dark'] .vehicle-chat__bubble--admin {
    background: #134e4a;
    color: #ecfdf5;
}

.vehicle-chat__sender {
    font-size: 0.68rem;
    font-weight: 700;
    margin-bottom: 0.2rem;
    opacity: 0.85;
}

.vehicle-chat__text {
    margin: 0;
    white-space: pre-wrap;
    line-height: 1.5;
    word-break: break-word;
}

.vehicle-chat__image-link {
    display: block;
    margin-top: 0.35rem;
}

.vehicle-chat__image {
    display: block;
    max-width: 100%;
    border-radius: 0.55rem;
}

.vehicle-chat__time {
    font-size: 0.68rem;
    color: var(--vs-text-subtle);
    margin-top: 0.2rem;
    padding-inline: 0.25rem;
}

.vehicle-chat__composer {
    border-top: 1px solid var(--vs-border);
    padding-top: 0.65rem;
    margin-top: 0.5rem;
}

.vehicle-chat__file-input {
    display: none;
}

.vehicle-chat__pending {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.vehicle-chat__pending-img {
    width: 72px;
    height: 72px;
    object-fit: cover;
    border-radius: 0.5rem;
    border: 1px solid var(--vs-border);
}

.vehicle-chat__composer-row {
    display: flex;
    align-items: flex-end;
    gap: 0.35rem;
}

.vehicle-chat__input {
    flex: 1;
    min-width: 0;
}

.vehicle-chat__send {
    flex-shrink: 0;
}
</style>
