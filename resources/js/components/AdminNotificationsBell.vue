<template>
    <div class="admin-notifications">
        <Button
            icon="pi pi-bell"
            severity="secondary"
            text
            rounded
            class="admin-notifications__trigger"
            :aria-label="t('notifications.aria')"
            :badge="badgeLabel"
            badge-severity="danger"
            @click="openList"
        />

        <Dialog
            v-model:visible="listVisible"
            :header="t('notifications.title')"
            modal
            :style="{ width: 'min(520px, 100vw)' }"
            @hide="fetchUnreadCount"
        >
            <div v-if="loadingList" class="admin-notifications__loading">
                <ProgressSpinner style="width: 28px; height: 28px" stroke-width="4" />
            </div>
            <div v-else-if="!items.length" class="admin-notifications__empty">
                {{ t('notifications.empty') }}
            </div>
            <ul v-else class="admin-notifications__list">
                <li
                    v-for="item in items"
                    :key="item.vehicle_id"
                    class="admin-notifications__item"
                >
                    <button type="button" class="admin-notifications__item-btn" @click="openChat(item)">
                        <span class="admin-notifications__item-top">
                            <strong>{{ item.vehicle?.title || t('notifications.vehicleFallback') }}</strong>
                            <span class="admin-notifications__count">{{ item.unread_count }}</span>
                        </span>
                        <span class="admin-notifications__dealer">{{ item.dealer_name || t('notifications.dealerFallback') }}</span>
                        <span class="admin-notifications__preview">{{ preview(item.preview) }}</span>
                        <span class="admin-notifications__time" dir="ltr">{{ formatDateTime(item.created_at) }}</span>
                    </button>
                </li>
            </ul>
        </Dialog>

        <VehicleChatDialog
            v-model:visible="chatVisible"
            :vehicle="chatVehicle"
            mode="admin"
            @read="onChatRead"
            @sent="onChatSent"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import VehicleChatDialog from './VehicleChatDialog.vue';
import api from '../api/client';
import { formatDateTime } from '../utils/formatDateTime';

const { t } = useI18n();
const items = ref([]);
const unreadCount = ref(0);
const loadingList = ref(false);
const listVisible = ref(false);
const chatVisible = ref(false);
const chatVehicle = ref(null);

let pollTimer = null;

const badgeLabel = computed(() => (unreadCount.value > 0 ? String(unreadCount.value) : null));

function preview(message) {
    const text = String(message || '').trim();

    if (text.length <= 90) {
        return text;
    }

    return `${text.slice(0, 90)}…`;
}

async function fetchUnreadCount() {
    try {
        const { data } = await api.get('/admin/messages/unread-count');
        unreadCount.value = Number(data.unread_count || 0);
    } catch {
        // ignore polling errors
    }
}

async function loadList() {
    loadingList.value = true;

    try {
        const { data } = await api.get('/admin/notifications', { params: { limit: 40 } });
        items.value = data.data ?? [];
        unreadCount.value = Number(data.unread_count || 0);
    } finally {
        loadingList.value = false;
    }
}

async function openList() {
    listVisible.value = true;
    await loadList();
}

function openChat(item) {
    chatVehicle.value = item.vehicle;
    listVisible.value = false;
    chatVisible.value = true;
}

function onChatRead() {
    fetchUnreadCount();
    loadList();
}

function onChatSent() {
    fetchUnreadCount();
}

onMounted(() => {
    fetchUnreadCount();
    pollTimer = window.setInterval(fetchUnreadCount, 60000);
});

onUnmounted(() => {
    if (pollTimer) {
        window.clearInterval(pollTimer);
    }
});
</script>

<style scoped>
.admin-notifications__trigger :deep(.p-badge) {
    min-width: 1.1rem;
    height: 1.1rem;
    line-height: 1.1rem;
    font-size: 0.62rem;
}

.admin-notifications__loading {
    display: flex;
    justify-content: center;
    padding: 1.5rem;
}

.admin-notifications__empty {
    padding: 1rem 0.25rem;
    text-align: center;
    color: var(--vs-text-muted);
}

.admin-notifications__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.admin-notifications__item-btn {
    width: 100%;
    border: 1px solid var(--vs-border);
    border-radius: 0.75rem;
    background: var(--vs-surface);
    padding: 0.75rem 0.85rem;
    text-align: start;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.admin-notifications__item-btn:hover {
    background: var(--vs-surface-hover);
    border-color: rgb(20 184 166 / 0.45);
}

.admin-notifications__item-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.2rem;
}

.admin-notifications__item-top strong {
    font-size: 0.92rem;
    color: var(--vs-text);
}

.admin-notifications__count {
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 0.35rem;
    border-radius: 999px;
    background: #14b8a6;
    color: #fff;
    font-size: 0.68rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.admin-notifications__dealer {
    display: block;
    font-size: 0.76rem;
    color: var(--vs-text-muted);
    margin-bottom: 0.25rem;
}

.admin-notifications__preview {
    display: block;
    font-size: 0.82rem;
    color: var(--vs-text-secondary);
    line-height: 1.45;
    margin-bottom: 0.35rem;
}

.admin-notifications__time {
    display: block;
    font-size: 0.72rem;
    color: var(--vs-text-subtle);
}
</style>
