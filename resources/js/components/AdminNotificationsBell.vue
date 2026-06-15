<template>
    <div class="admin-notifications">
        <Button
            icon="pi pi-bell"
            severity="secondary"
            text
            rounded
            class="admin-notifications__trigger"
            aria-label="الإشعارات"
            :badge="badgeLabel"
            badge-severity="danger"
            @click="openList"
        />

        <Dialog
            v-model:visible="listVisible"
            header="إشعارات الملاحظات"
            modal
            :style="{ width: 'min(520px, 100vw)' }"
            @hide="onListHide"
        >
            <div v-if="loadingList" class="admin-notifications__loading">
                <ProgressSpinner style="width: 28px; height: 28px" stroke-width="4" />
            </div>
            <div v-else-if="!items.length" class="admin-notifications__empty">
                لا توجد إشعارات حالياً.
            </div>
            <ul v-else class="admin-notifications__list">
                <li
                    v-for="item in items"
                    :key="item.id"
                    class="admin-notifications__item"
                    :class="{ 'admin-notifications__item--unread': !item.read_at }"
                >
                    <button type="button" class="admin-notifications__item-btn" @click="openDetail(item)">
                        <span class="admin-notifications__item-top">
                            <strong>{{ item.vehicle?.title || 'سيارة' }}</strong>
                            <span v-if="!item.read_at" class="admin-notifications__dot" aria-hidden="true" />
                        </span>
                        <span class="admin-notifications__dealer">{{ item.dealer_name || 'تاجر' }}</span>
                        <span class="admin-notifications__preview">{{ preview(item.message) }}</span>
                        <span class="admin-notifications__time" dir="ltr">{{ formatDateTime(item.created_at) }}</span>
                    </button>
                </li>
            </ul>
        </Dialog>

        <Dialog
            v-model:visible="detailVisible"
            header="رسالة التاجر"
            modal
            :style="{ width: 'min(560px, 100vw)' }"
        >
            <div v-if="loadingDetail" class="admin-notifications__loading">
                <ProgressSpinner style="width: 28px; height: 28px" stroke-width="4" />
            </div>
            <div v-else-if="selected" class="admin-notifications__detail">
                <div class="admin-notifications__vehicle-card">
                    <div class="admin-notifications__vehicle-title">{{ selected.vehicle?.title || '—' }}</div>
                    <div class="admin-notifications__vehicle-meta">
                        <span v-if="selected.vehicle?.year">{{ selected.vehicle.year }}</span>
                        <span v-if="selected.dealer_name">{{ selected.dealer_name }}</span>
                    </div>
                    <div class="admin-notifications__vin-row">
                        <span class="admin-notifications__vin-label">الشانصي</span>
                        <VinCopyLabel :vin="selected.vehicle?.vin" block />
                    </div>
                </div>

                <div class="admin-notifications__message-box">
                    <div class="admin-notifications__message-label">الرسالة</div>
                    <p class="admin-notifications__message-text">{{ selected.message }}</p>
                    <div class="admin-notifications__message-meta" dir="ltr">
                        {{ formatDateTime(selected.created_at) }}
                        <span v-if="selected.author_name"> · {{ selected.author_name }}</span>
                    </div>
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import VinCopyLabel from './VinCopyLabel.vue';
import api from '../api/client';
import { formatDateTime } from '../utils/formatDateTime';

const items = ref([]);
const unreadCount = ref(0);
const loadingList = ref(false);
const loadingDetail = ref(false);
const listVisible = ref(false);
const detailVisible = ref(false);
const selected = ref(null);

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
        const { data } = await api.get('/admin/notifications/unread-count');
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

function onListHide() {
    fetchUnreadCount();
}

async function openDetail(item) {
    detailVisible.value = true;
    loadingDetail.value = true;
    selected.value = null;

    try {
        const { data } = await api.post(`/admin/notifications/${item.id}/read`);
        selected.value = data.data;
        unreadCount.value = Number(data.unread_count || 0);

        const index = items.value.findIndex((row) => row.id === item.id);

        if (index !== -1) {
            items.value[index] = data.data;
        }
    } catch {
        selected.value = item;
    } finally {
        loadingDetail.value = false;
    }
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
    border-color: var(--vs-border-strong, var(--vs-border));
}

.admin-notifications__item--unread .admin-notifications__item-btn {
    border-color: rgb(20 184 166 / 0.45);
    background: rgb(20 184 166 / 0.06);
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

.admin-notifications__dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    background: #14b8a6;
    flex-shrink: 0;
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

.admin-notifications__vehicle-card {
    border: 1px solid var(--vs-border);
    border-radius: 0.75rem;
    padding: 0.85rem 1rem;
    margin-bottom: 0.85rem;
    background: var(--vs-surface-hover);
}

.admin-notifications__vehicle-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--vs-text);
    margin-bottom: 0.25rem;
}

.admin-notifications__vehicle-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 0.85rem;
    font-size: 0.78rem;
    color: var(--vs-text-muted);
    margin-bottom: 0.65rem;
}

.admin-notifications__vin-row {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.admin-notifications__vin-label {
    font-size: 0.72rem;
    color: var(--vs-text-muted);
}

.admin-notifications__message-box {
    border: 1px solid var(--vs-border);
    border-radius: 0.75rem;
    padding: 0.85rem 1rem;
}

.admin-notifications__message-label {
    font-size: 0.72rem;
    color: var(--vs-text-muted);
    margin-bottom: 0.35rem;
}

.admin-notifications__message-text {
    margin: 0;
    white-space: pre-wrap;
    line-height: 1.55;
    color: var(--vs-text);
}

.admin-notifications__message-meta {
    margin-top: 0.65rem;
    font-size: 0.72rem;
    color: var(--vs-text-subtle);
}
</style>
