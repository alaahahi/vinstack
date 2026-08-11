<template>
    <div class="transfers-page">
        <div class="transfers-toolbar">
            <div>
                <p class="transfers-lead">{{ t('imageTransfers.lead') }}</p>
                <div class="transfers-summary" v-if="meta">
                    <span class="pill">{{ t('imageTransfers.activeCount', { count: meta.active_count ?? 0 }) }}</span>
                    <span v-if="meta.stale_count > 0" class="pill pill--warn">
                        {{ t('imageTransfers.staleCount', { count: meta.stale_count }) }}
                    </span>
                </div>
            </div>
            <div class="transfers-toolbar__actions">
                <Button
                    icon="pi pi-refresh"
                    :label="t('actions.refresh')"
                    severity="secondary"
                    outlined
                    :loading="loading"
                    @click="load({ reset: true })"
                />
            </div>
        </div>

        <div v-if="health" class="health-panel" :class="{ 'health-panel--ok': health.overall_ok, 'health-panel--warn': ! health.overall_ok }">
            <div class="health-panel__title">
                <i :class="health.overall_ok ? 'pi pi-check-circle' : 'pi pi-exclamation-triangle'" />
                <span>{{ health.overall_ok ? t('imageTransfers.healthOk') : t('imageTransfers.healthWarn') }}</span>
            </div>
            <div class="health-grid">
                <div class="health-card" :class="health.scheduler?.ok ? 'health-card--ok' : 'health-card--bad'">
                    <header>
                        <strong>{{ t('imageTransfers.schedulerTitle') }}</strong>
                        <span>{{ health.scheduler?.ok ? t('imageTransfers.statusOk') : t('imageTransfers.statusDown') }}</span>
                    </header>
                    <p>{{ t('imageTransfers.schedulerCmd') }}: <code>image-transfers:process</code></p>
                    <p>{{ t('imageTransfers.lastRun') }}: {{ formatTime(health.scheduler?.last_run_at) }}</p>
                    <p v-if="health.scheduler?.hint" class="health-card__hint">{{ health.scheduler.hint }}</p>
                </div>
                <div class="health-card" :class="health.queue?.ok ? 'health-card--ok' : 'health-card--bad'">
                    <header>
                        <strong>{{ t('imageTransfers.queueTitle') }}</strong>
                        <span>{{ health.queue?.ok ? t('imageTransfers.statusOk') : t('imageTransfers.statusDown') }}</span>
                    </header>
                    <p>{{ t('imageTransfers.queueDriver') }}: <code>{{ health.queue_connection }}</code></p>
                    <p>{{ t('imageTransfers.pendingJobs') }}: {{ health.pending_queue_jobs ?? 0 }}</p>
                    <p>{{ t('imageTransfers.lastRun') }}: {{ formatTime(health.queue?.last_run_at || health.batch?.last_run_at) }}</p>
                    <p v-if="health.queue?.hint" class="health-card__hint">{{ health.queue.hint }}</p>
                </div>
            </div>
        </div>

        <div v-if="loading && ! jobs.length" class="transfers-empty">
            <ProgressSpinner style="width: 28px; height: 28px" />
            <span>{{ t('imageTransfers.loading') }}</span>
        </div>

        <p v-else-if="! jobs.length" class="transfers-empty">{{ t('imageTransfers.empty') }}</p>

        <div v-else class="transfers-list">
            <article
                v-for="job in jobs"
                :key="job.id"
                class="transfer-card"
                :class="[
                    `transfer-card--${job.status}`,
                    { 'transfer-card--stale': job.is_stale },
                ]"
            >
                <header class="transfer-card__head">
                    <div>
                        <h3>{{ jobLabel(job) }}</h3>
                        <p class="transfer-card__type">{{ typeLabel(job.type) }}</p>
                    </div>
                    <span class="transfer-card__status">{{ statusLabel(job.status) }}</span>
                </header>

                <div class="transfer-card__progress">
                    <div class="transfer-card__bar">
                        <span :style="{ width: `${job.progress_percent || 0}%` }" />
                    </div>
                    <div class="transfer-card__meta">
                        <span>{{ job.transferred_count }}/{{ job.total_images }} · {{ job.progress_percent }}%</span>
                        <span v-if="job.failed_count">{{ t('imageTransfers.failedCount', { count: job.failed_count }) }}</span>
                    </div>
                </div>

                <p v-if="job.is_stale" class="transfer-card__alert">
                    {{ t('imageTransfers.staleHint') }}
                </p>
                <p v-if="job.error_message" class="transfer-card__error">
                    {{ job.error_message }}
                </p>

                <div class="transfer-card__times">
                    <span>{{ t('imageTransfers.createdAt') }}: {{ formatTime(job.created_at) }}</span>
                    <span v-if="job.updated_at">{{ t('imageTransfers.updatedAt') }}: {{ formatTime(job.updated_at) }}</span>
                </div>

                <div class="transfer-card__actions">
                    <Button
                        :label="t('imageTransfers.details')"
                        icon="pi pi-list"
                        size="small"
                        text
                        @click="openDetails(job)"
                    />
                    <Button
                        v-if="canProcessNow(job)"
                        :label="t('imageTransfers.processNow')"
                        icon="pi pi-play"
                        size="small"
                        severity="help"
                        outlined
                        :loading="actionId === job.id && actionKind === 'process'"
                        @click="runAction(job, 'process')"
                    />
                    <Button
                        v-if="canRetry(job)"
                        :label="t('imageTransfers.retry')"
                        icon="pi pi-replay"
                        size="small"
                        severity="warning"
                        outlined
                        :loading="actionId === job.id && actionKind === 'retry'"
                        @click="runAction(job, 'retry')"
                    />
                    <Button
                        v-if="canCancel(job)"
                        :label="t('imageTransfers.cancel')"
                        icon="pi pi-times"
                        size="small"
                        severity="danger"
                        text
                        :loading="actionId === job.id && actionKind === 'cancel'"
                        @click="runAction(job, 'cancel')"
                    />
                </div>
            </article>
        </div>

        <div v-if="meta?.has_more" class="transfers-more">
            <Button
                :label="t('imageTransfers.showMore')"
                icon="pi pi-angle-down"
                severity="secondary"
                outlined
                :loading="loadingMore"
                @click="loadMore"
            />
        </div>

        <Dialog
            v-model:visible="detailsOpen"
            modal
            :header="t('imageTransfers.detailsTitle')"
            style="width: min(560px, 96vw)"
        >
            <div v-if="detailsJob" class="details-body">
                <p><strong>{{ jobLabel(detailsJob) }}</strong></p>
                <p class="details-status">{{ statusLabel(detailsJob.status) }} · {{ detailsJob.progress_percent }}%</p>
                <p v-if="detailsJob.error_message" class="transfer-card__error">{{ detailsJob.error_message }}</p>

                <h4>{{ t('imageTransfers.failedItems') }}</h4>
                <p v-if="! (detailsJob.failed_items || []).length" class="details-empty">
                    {{ t('imageTransfers.noFailedItems') }}
                </p>
                <ul v-else class="failed-list">
                    <li v-for="(item, index) in detailsJob.failed_items" :key="`${item.name}-${index}`">
                        <strong>{{ item.name }}</strong>
                        <span>{{ item.error || t('imageTransfers.unknownError') }}</span>
                    </li>
                </ul>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import {
    cancelImageTransfer,
    fetchImageTransferStatus,
    fetchImageTransfers,
    processImageTransferNow,
    retryImageTransfer,
} from '../../utils/imageTransfer';

const { t, locale } = useI18n();
const toast = useToast();

const jobs = ref([]);
const meta = ref(null);
const page = ref(1);
const loading = ref(false);
const loadingMore = ref(false);
const actionId = ref(null);
const actionKind = ref('');
const detailsOpen = ref(false);
const detailsJob = ref(null);
let pollTimer = null;

const health = computed(() => meta.value?.health ?? null);

const hasActive = computed(() => (meta.value?.active_count ?? 0) > 0
    || jobs.value.some((job) => ['queued', 'processing'].includes(job.status)));

function jobLabel(job) {
    if (job.container_number) {
        return job.container_number;
    }

    if (job.vehicle_vin) {
        return `${job.vehicle_vin}${job.stage ? ` · ${job.stage}` : ''}`;
    }

    return job.id;
}

function typeLabel(type) {
    return t(`imageTransfers.types.${type}`, type);
}

function statusLabel(status) {
    return t(`imageTransfers.status.${status}`, status);
}

function canRetry(job) {
    return ['failed', 'partial'].includes(job.status);
}

function canCancel(job) {
    return ['queued', 'processing'].includes(job.status);
}

function canProcessNow(job) {
    return ['queued', 'processing'].includes(job.status);
}

function formatTime(value) {
    if (! value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString(locale.value === 'ar' ? 'ar' : locale.value === 'ckb' ? 'ckb' : 'en', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

function replaceJob(updated) {
    const index = jobs.value.findIndex((job) => job.id === updated.id);

    if (index >= 0) {
        jobs.value[index] = { ...jobs.value[index], ...updated };
    }

    if (detailsJob.value?.id === updated.id) {
        detailsJob.value = { ...detailsJob.value, ...updated };
    }
}

async function load({ reset = true } = {}) {
    if (reset) {
        loading.value = true;
        page.value = 1;
    } else {
        loadingMore.value = true;
    }

    try {
        const nextPage = reset ? 1 : page.value + 1;
        const result = await fetchImageTransfers('/admin', {
            page: nextPage,
            perPage: 15,
        });

        jobs.value = reset
            ? (result.data ?? [])
            : [...jobs.value, ...(result.data ?? [])];
        meta.value = result.meta ?? null;
        page.value = result.meta?.page ?? nextPage;
        schedulePoll();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || t('imageTransfers.loadFailed'),
            life: 4000,
        });
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
}

async function loadMore() {
    if (loadingMore.value || ! meta.value?.has_more) {
        return;
    }

    await load({ reset: false });
}

async function openDetails(job) {
    detailsOpen.value = true;
    detailsJob.value = job;

    try {
        const full = await fetchImageTransferStatus(job.id, '/admin');

        if (full) {
            detailsJob.value = full;
            replaceJob(full);
        }
    } catch {
        // keep list payload
    }
}

async function runAction(job, kind) {
    actionId.value = job.id;
    actionKind.value = kind;

    try {
        let updated = null;

        if (kind === 'retry') {
            updated = await retryImageTransfer(job.id);
        } else if (kind === 'process') {
            updated = await processImageTransferNow(job.id);
        } else if (kind === 'cancel') {
            updated = await cancelImageTransfer(job.id);
        }

        if (updated) {
            replaceJob(updated);
        }

        toast.add({
            severity: 'success',
            summary: t('imageTransfers.actionOk'),
            life: 2500,
        });

        await load({ reset: true });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || t('imageTransfers.actionFailed'),
            life: 4500,
        });
    } finally {
        actionId.value = null;
        actionKind.value = '';
    }
}

function schedulePoll() {
    clearPoll();

    if (! hasActive.value) {
        return;
    }

    pollTimer = setTimeout(async () => {
        try {
            const result = await fetchImageTransfers('/admin', {
                page: 1,
                perPage: Math.max(jobs.value.length, 15),
            });

            const fresh = result.data ?? [];
            const byId = new Map(fresh.map((job) => [job.id, job]));

            jobs.value = jobs.value.map((job) => byId.get(job.id) ?? job);
            meta.value = result.meta ?? meta.value;
        } catch {
            // keep current list
        } finally {
            schedulePoll();
        }
    }, 5000);
}

function clearPoll() {
    if (pollTimer) {
        clearTimeout(pollTimer);
        pollTimer = null;
    }
}

onMounted(() => load({ reset: true }));
onBeforeUnmount(clearPoll);
</script>

<style scoped>
.transfers-page {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.transfers-toolbar {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    flex-wrap: wrap;
}

.transfers-lead {
    margin: 0;
    color: var(--vs-text-muted);
    font-size: 0.9rem;
}

.health-panel {
    border: 1px solid var(--admin-border);
    border-radius: 14px;
    padding: 0.85rem 1rem;
    background: var(--admin-surface);
    box-shadow: var(--admin-shadow);
}

.health-panel--ok {
    border-color: color-mix(in srgb, #22c55e 35%, var(--admin-border));
}

.health-panel--warn {
    border-color: color-mix(in srgb, #f59e0b 45%, var(--admin-border));
}

.health-panel__title {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-weight: 700;
    margin-bottom: 0.7rem;
}

.health-panel--ok .health-panel__title {
    color: #15803d;
}

.health-panel--warn .health-panel__title {
    color: #b45309;
}

.health-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
}

.health-card {
    border: 1px solid var(--admin-border);
    border-radius: 12px;
    padding: 0.7rem 0.8rem;
    background: color-mix(in srgb, var(--admin-surface) 92%, #f8fafc);
}

.health-card header {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.35rem;
}

.health-card p {
    margin: 0.2rem 0 0;
    font-size: 0.8rem;
    color: var(--vs-text-muted);
}

.health-card code {
    font-size: 0.78rem;
}

.health-card--ok header span {
    color: #15803d;
    font-size: 0.78rem;
    font-weight: 700;
}

.health-card--bad header span {
    color: #b45309;
    font-size: 0.78rem;
    font-weight: 700;
}

.health-card__hint {
    color: #b45309 !important;
}

@media (max-width: 720px) {
    .health-grid {
        grid-template-columns: 1fr;
    }
}

.transfers-summary {
    display: flex;
    gap: 0.45rem;
    margin-top: 0.55rem;
    flex-wrap: wrap;
}

.pill {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    background: color-mix(in srgb, var(--admin-accent) 14%, transparent);
    color: var(--admin-accent);
}

.pill--warn {
    background: color-mix(in srgb, #f59e0b 18%, transparent);
    color: #d97706;
}

.transfers-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    min-height: 8rem;
    color: var(--vs-text-muted);
}

.transfers-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.transfer-card {
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: 14px;
    padding: 0.95rem 1rem;
    box-shadow: var(--admin-shadow);
}

.transfer-card--failed,
.transfer-card--partial {
    border-color: color-mix(in srgb, #ef4444 35%, var(--admin-border));
}

.transfer-card--stale {
    border-color: color-mix(in srgb, #f59e0b 45%, var(--admin-border));
}

.transfer-card__head {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: flex-start;
}

.transfer-card__head h3 {
    margin: 0;
    font-size: 0.98rem;
}

.transfer-card__type {
    margin: 0.2rem 0 0;
    font-size: 0.78rem;
    color: var(--vs-text-muted);
}

.transfer-card__status {
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--vs-text-muted);
}

.transfer-card__progress {
    margin-top: 0.75rem;
}

.transfer-card__bar {
    height: 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--vs-text-muted) 16%, transparent);
    overflow: hidden;
}

.transfer-card__bar span {
    display: block;
    height: 100%;
    background: #22c55e;
}

.transfer-card--failed .transfer-card__bar span {
    background: #ef4444;
}

.transfer-card--partial .transfer-card__bar span {
    background: #f59e0b;
}

.transfer-card__meta,
.transfer-card__times {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 0.4rem;
    font-size: 0.8rem;
    color: var(--vs-text-muted);
}

.transfer-card__alert,
.transfer-card__error {
    margin: 0.55rem 0 0;
    font-size: 0.82rem;
}

.transfer-card__alert {
    color: #d97706;
}

.transfer-card__error {
    color: #dc2626;
}

.transfer-card__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-top: 0.7rem;
}

.transfers-more {
    display: flex;
    justify-content: center;
}

.details-body h4 {
    margin: 1rem 0 0.45rem;
    font-size: 0.9rem;
}

.details-status,
.details-empty {
    color: var(--vs-text-muted);
    font-size: 0.85rem;
}

.failed-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

.failed-list li {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    padding: 0.55rem 0.65rem;
    border-radius: 10px;
    background: color-mix(in srgb, #ef4444 8%, transparent);
    font-size: 0.84rem;
}

.failed-list span {
    color: var(--vs-text-muted);
    word-break: break-word;
}
</style>
