<template>
    <div class="dash">
        <div v-if="loading" class="dash-loading">
            <ProgressSpinner style="width: 32px; height: 32px" />
            <span>{{ t('dashboard.loading') }}</span>
        </div>

        <template v-else-if="data">
            <section class="dash-chart-card">
                <div class="dash-chart-card__head">
                    <div>
                        <h2 class="dash-chart-card__title">{{ t('dashboard.addedTitle') }}</h2>
                        <p class="dash-chart-card__sub">{{ t('dashboard.addedSub') }}</p>
                    </div>
                    <strong class="dash-chart-card__total">{{ data.vehicles_added.total }}</strong>
                </div>
                <div class="dash-bars" role="img" :aria-label="t('dashboard.addedTitle')">
                    <div
                        v-for="month in data.vehicles_added.months"
                        :key="month.key"
                        class="dash-bar"
                    >
                        <span class="dash-bar__value">{{ month.count }}</span>
                        <div class="dash-bar__track">
                            <div class="dash-bar__fill" :style="{ height: barHeight(month.count) }" />
                        </div>
                        <span class="dash-bar__label">{{ monthLabel(month.key) }}</span>
                    </div>
                </div>
            </section>

            <section class="dash-kpi-grid">
                <article class="dash-stat-card">
                    <header class="dash-stat-card__head">
                        <div>
                            <h3>{{ t('dashboard.photosTitle') }}</h3>
                            <p>{{ t('dashboard.photosSub') }}</p>
                        </div>
                        <strong>{{ data.totals.vehicles }}</strong>
                    </header>
                    <div class="dash-stat-bar" aria-hidden="true">
                        <span
                            v-if="data.photos.with_photos"
                            class="dash-stat-bar__seg dash-stat-bar__seg--ok"
                            :style="{ flex: data.photos.with_photos }"
                        />
                        <span
                            v-if="data.photos.without_photos"
                            class="dash-stat-bar__seg dash-stat-bar__seg--warn"
                            :style="{ flex: data.photos.without_photos }"
                        />
                    </div>
                    <ul class="dash-stat-list">
                        <li>
                            <span><i class="dash-dot dash-dot--ok" /> {{ t('dashboard.withPhotos') }}</span>
                            <strong>{{ data.photos.with_photos }}</strong>
                        </li>
                        <li>
                            <span><i class="dash-dot dash-dot--warn" /> {{ t('dashboard.withoutPhotos') }}</span>
                            <strong>{{ data.photos.without_photos }}</strong>
                        </li>
                        <li>
                            <span><i class="dash-dot dash-dot--purple" /> {{ t('dashboard.withUploaded') }}</span>
                            <strong>{{ data.photos.with_uploaded }}</strong>
                        </li>
                        <li>
                            <span><i class="dash-dot dash-dot--muted" /> {{ t('dashboard.withoutUploaded') }}</span>
                            <strong>{{ data.photos.without_uploaded }}</strong>
                        </li>
                    </ul>
                </article>

                <article class="dash-stat-card">
                    <header class="dash-stat-card__head">
                        <div>
                            <h3>{{ t('dashboard.notificationsTitle') }}</h3>
                            <p>{{ t('dashboard.notificationsSub') }}</p>
                        </div>
                        <strong>{{ data.notifications.unread_total }}</strong>
                    </header>
                    <div class="dash-stat-bar" aria-hidden="true">
                        <span
                            v-if="data.notifications.status_changes.unread"
                            class="dash-stat-bar__seg dash-stat-bar__seg--orange"
                            :style="{ flex: data.notifications.status_changes.unread }"
                        />
                        <span
                            v-if="data.notifications.chat_unread"
                            class="dash-stat-bar__seg dash-stat-bar__seg--blue"
                            :style="{ flex: data.notifications.chat_unread }"
                        />
                        <span
                            v-if="data.notifications.dealer_notes_unread"
                            class="dash-stat-bar__seg dash-stat-bar__seg--purple"
                            :style="{ flex: data.notifications.dealer_notes_unread }"
                        />
                        <span
                            v-if="data.notifications.status_changes.read"
                            class="dash-stat-bar__seg dash-stat-bar__seg--ok"
                            :style="{ flex: data.notifications.status_changes.read }"
                        />
                    </div>
                    <ul class="dash-stat-list">
                        <li>
                            <span><i class="dash-dot dash-dot--orange" /> {{ t('dashboard.statusUnread') }}</span>
                            <strong>{{ data.notifications.status_changes.unread }}</strong>
                        </li>
                        <li>
                            <span><i class="dash-dot dash-dot--ok" /> {{ t('dashboard.statusRead') }}</span>
                            <strong>{{ data.notifications.status_changes.read }}</strong>
                        </li>
                        <li>
                            <span><i class="dash-dot dash-dot--blue" /> {{ t('dashboard.chatUnread') }}</span>
                            <strong>{{ data.notifications.chat_unread }}</strong>
                        </li>
                        <li>
                            <span><i class="dash-dot dash-dot--purple" /> {{ t('dashboard.notesUnread') }}</span>
                            <strong>{{ data.notifications.dealer_notes_unread }}</strong>
                        </li>
                    </ul>
                </article>

                <article class="dash-stat-card">
                    <header class="dash-stat-card__head">
                        <div>
                            <h3>{{ t('dashboard.whatsappTitle') }}</h3>
                            <p>{{ t('dashboard.whatsappSub') }}</p>
                        </div>
                        <strong>{{ data.whatsapp.total }}</strong>
                    </header>
                    <div class="dash-stat-bar" aria-hidden="true">
                        <span
                            v-if="data.whatsapp.success"
                            class="dash-stat-bar__seg dash-stat-bar__seg--ok"
                            :style="{ flex: data.whatsapp.success }"
                        />
                        <span
                            v-if="data.whatsapp.failed"
                            class="dash-stat-bar__seg dash-stat-bar__seg--danger"
                            :style="{ flex: data.whatsapp.failed }"
                        />
                    </div>
                    <ul class="dash-stat-list">
                        <li>
                            <span><i class="dash-dot dash-dot--ok" /> {{ t('dashboard.waSuccess') }}</span>
                            <strong>{{ data.whatsapp.success }}</strong>
                        </li>
                        <li>
                            <span><i class="dash-dot dash-dot--danger" /> {{ t('dashboard.waFailed') }}</span>
                            <strong>{{ data.whatsapp.failed }}</strong>
                        </li>
                        <li v-for="(count, status) in data.whatsapp.by_status" :key="status">
                            <span><i class="dash-dot dash-dot--muted" /> {{ waStatusLabel(status) }}</span>
                            <strong>{{ count }}</strong>
                        </li>
                    </ul>
                </article>
            </section>

            <section class="dash-section">
                <header class="dash-section__head">
                    <i class="pi pi-map-marker dash-section__icon" aria-hidden="true" />
                    <div>
                        <h2>{{ t('dashboard.loadingPointsTitle') }}</h2>
                        <p>{{ t('dashboard.loadingPointsSub') }}</p>
                    </div>
                </header>

                <p v-if="! data.loading_points.length" class="dash-empty">
                    {{ t('dashboard.loadingPointsEmpty') }}
                </p>

                <div v-else class="dash-lp-grid">
                    <article v-for="point in data.loading_points" :key="point.name" class="dash-lp-card">
                        <header class="dash-lp-card__head">
                            <h3>{{ pointName(point.name) }}</h3>
                            <strong>{{ point.total }}</strong>
                        </header>
                        <div class="dash-stat-bar" aria-hidden="true">
                            <span
                                v-for="row in point.statuses.filter((item) => item.count > 0)"
                                :key="row.key"
                                class="dash-stat-bar__seg"
                                :class="`dash-stat-bar__seg--${row.key}`"
                                :style="{ flex: row.count }"
                            />
                        </div>
                        <ul class="dash-stat-list">
                            <li v-for="row in point.statuses" :key="row.key">
                                <span>
                                    <i class="dash-dot" :class="`dash-dot--${row.key}`" />
                                    {{ t(`dashboard.status.${row.key}`) }}
                                </span>
                                <strong>{{ row.count }}</strong>
                            </li>
                        </ul>
                    </article>
                </div>
            </section>

            <section class="dash-section">
            <header class="dash-section__head">
                <i class="pi pi-database dash-section__icon" aria-hidden="true" />
                <div>
                    <h2>{{ t('dashboard.dbInsightsTitle') }}</h2>
                    <p>{{ t('dashboard.dbInsightsSub') }}</p>
                </div>
            </header>

            <div v-if="dbLoading" class="dash-empty">
                <ProgressSpinner style="width: 24px; height: 24px" />
                <span>{{ t('dashboard.dbInsightsLoading') }}</span>
            </div>

            <p v-else-if="dbError" class="dash-empty">{{ t('dashboard.dbInsightsFailed') }}</p>

            <template v-else-if="db">
                <div class="dash-db-summary">
                    <div class="dash-db-stat">
                        <span>{{ t('dashboard.dbTotalSize') }}</span>
                        <strong>{{ formatBytes(db.database_size_bytes) }}</strong>
                    </div>
                    <div v-if="db.used_bytes != null" class="dash-db-stat">
                        <span>{{ t('dashboard.dbUsed') }}</span>
                        <strong>{{ formatBytes(db.used_bytes) }}</strong>
                    </div>
                    <div v-if="db.free_bytes != null" class="dash-db-stat">
                        <span>{{ t('dashboard.dbFree') }}</span>
                        <strong class="dash-db-stat--free">{{ formatBytes(db.free_bytes) }}</strong>
                    </div>
                </div>

                <div class="dash-db-body">
                    <div class="dash-db-chart" aria-hidden="true">
                        <div
                            class="dash-db-donut"
                            :style="{ background: dbChartGradient }"
                        />
                        <div class="dash-db-donut-hole" />
                    </div>

                    <ul class="dash-db-table-list">
                        <li v-for="(row, idx) in dbTopTables" :key="row.name">
                            <span class="dash-db-table-dot" :style="{ background: dbChartColors[idx % dbChartColors.length] }" />
                            <span class="dash-db-table-name">{{ row.name }}</span>
                            <span class="dash-db-table-rows">{{ t('dashboard.dbRows', { n: row.rows.toLocaleString() }) }}</span>
                            <strong class="dash-db-table-size">{{ formatBytes(row.size_bytes) }}</strong>
                            <span class="dash-db-table-pct">{{ row.percent_of_db != null ? row.percent_of_db.toFixed(1) + '%' : '' }}</span>
                        </li>
                    </ul>
                </div>
            </template>
            </section>
        </template>

        <p v-else class="dash-empty">{{ error || t('dashboard.loadFailed') }}</p>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import ProgressSpinner from 'primevue/progressspinner';
import api from '../../api/client';

const { t, locale } = useI18n();

const loading = ref(true);
const data = ref(null);
const error = ref('');

const dbLoading = ref(true);
const db = ref(null);
const dbError = ref(false);

const dbChartColors = [
    '#6366f1', '#22c55e', '#f59e0b', '#3b82f6', '#ec4899',
    '#14b8a6', '#fb923c', '#a855f7', '#ef4444', '#64748b',
];

const dbTopTables = computed(() => (db.value?.tables ?? []).slice(0, 10));

const dbChartGradient = computed(() => {
    const tables = dbTopTables.value;

    if (!tables.length) return '#334155';

    const total = db.value?.database_size_bytes ?? tables.reduce((s, r) => s + (r.size_bytes ?? 0), 0);
    let offset = 0;
    const stops = [];

    tables.forEach((row, idx) => {
        const pct = total > 0 ? ((row.size_bytes ?? 0) / total) * 100 : 0;
        const color = dbChartColors[idx % dbChartColors.length];
        stops.push(`${color} ${offset.toFixed(2)}%`);
        offset += pct;
        stops.push(`${color} ${offset.toFixed(2)}%`);
    });

    if (offset < 100) {
        stops.push(`#334155 ${offset.toFixed(2)}%`);
        stops.push(`#334155 100%`);
    }

    return `conic-gradient(${stops.join(', ')})`;
});

function formatBytes(bytes) {
    if (bytes == null) return '–';

    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';

    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';

    if (bytes >= 1024) return (bytes / 1024).toFixed(0) + ' KB';

    return bytes + ' B';
}

async function loadDbInsights() {
    dbLoading.value = true;
    dbError.value = false;

    try {
        const { data: payload } = await api.get('/admin/system/database-insights');
        db.value = payload.data;
    } catch {
        dbError.value = true;
    } finally {
        dbLoading.value = false;
    }
}

const maxBar = computed(() => {
    const months = data.value?.vehicles_added?.months ?? [];

    return Math.max(1, ...months.map((month) => month.count));
});

function barHeight(count) {
    return `${Math.max(6, Math.round((count / maxBar.value) * 100))}%`;
}

function monthLabel(key) {
    const [year, month] = String(key).split('-').map(Number);
    const date = new Date(year, (month || 1) - 1, 1);

    return date.toLocaleDateString(locale.value === 'ar' ? 'ar' : locale.value === 'ckb' ? 'ckb' : 'en', {
        month: 'short',
        year: 'numeric',
    });
}

function pointName(name) {
    return name === 'unspecified' ? t('dashboard.unspecifiedPoint') : name;
}

function waStatusLabel(status) {
    const key = `dashboard.waStatus.${status}`;

    return t(key) === key ? status : t(key);
}

async function load() {
    loading.value = true;
    error.value = '';

    try {
        const { data: payload } = await api.get('/admin/dashboard');
        data.value = payload.data;
    } catch (e) {
        error.value = e.response?.data?.message || t('dashboard.loadFailed');
        data.value = null;
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    load();
    loadDbInsights();
});
</script>

<style scoped>
.dash {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.dash-loading,
.dash-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    min-height: 8rem;
    color: var(--vs-text-muted);
}

.dash-chart-card,
.dash-stat-card,
.dash-lp-card {
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: 16px;
    padding: 1rem 1.15rem 1.1rem;
    box-shadow: var(--admin-shadow);
}

.dash-chart-card__head,
.dash-stat-card__head,
.dash-lp-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.85rem;
}

.dash-chart-card__title,
.dash-stat-card h3,
.dash-lp-card h3,
.dash-section__head h2 {
    margin: 0;
    font-size: 0.98rem;
    font-weight: 700;
}

.dash-chart-card__sub,
.dash-stat-card p,
.dash-section__head p {
    margin: 0.2rem 0 0;
    font-size: 0.8rem;
    color: var(--vs-text-muted);
}

.dash-chart-card__total,
.dash-stat-card__head strong,
.dash-lp-card__head strong {
    font-size: 1.7rem;
    line-height: 1;
    color: #22c55e;
}

.dash-bars {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0.65rem;
    align-items: end;
    min-height: 180px;
}

.dash-bar {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    min-width: 0;
}

.dash-bar__track {
    width: 100%;
    max-width: 42px;
    height: 140px;
    display: flex;
    align-items: flex-end;
    border-radius: 10px 10px 4px 4px;
    background: color-mix(in srgb, #22c55e 12%, transparent);
}

.dash-bar__fill {
    width: 100%;
    border-radius: inherit;
    background: #22c55e;
}

.dash-bar__value {
    font-size: 0.78rem;
    font-weight: 700;
    color: #22c55e;
}

.dash-bar__label {
    font-size: 0.72rem;
    color: var(--vs-text-muted);
    text-align: center;
}

.dash-kpi-grid,
.dash-lp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 0.85rem;
}

.dash-stat-bar {
    display: flex;
    height: 8px;
    border-radius: 999px;
    overflow: hidden;
    background: color-mix(in srgb, var(--vs-text-muted) 16%, transparent);
    margin-bottom: 0.75rem;
}

.dash-stat-bar__seg {
    min-width: 0;
}

.dash-stat-bar__seg--ok,
.dash-stat-bar__seg--loaded { background: #22c55e; }
.dash-stat-bar__seg--warn,
.dash-stat-bar__seg--new_purchase { background: #f59e0b; }
.dash-stat-bar__seg--orange,
.dash-stat-bar__seg--sent { background: #fb923c; }
.dash-stat-bar__seg--blue,
.dash-stat-bar__seg--at_terminal { background: #3b82f6; }
.dash-stat-bar__seg--purple { background: #a855f7; }
.dash-stat-bar__seg--danger,
.dash-stat-bar__seg--left_terminal { background: #ef4444; }
.dash-stat-bar__seg--muted,
.dash-stat-bar__seg--other { background: #94a3b8; }

.dash-stat-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.dash-stat-list li {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    font-size: 0.84rem;
}

.dash-stat-list span {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    color: var(--vs-text-muted);
}

.dash-dot {
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 999px;
    background: #94a3b8;
    flex-shrink: 0;
}

.dash-dot--ok,
.dash-dot--loaded { background: #22c55e; }
.dash-dot--warn,
.dash-dot--new_purchase { background: #f59e0b; }
.dash-dot--orange,
.dash-dot--sent { background: #fb923c; }
.dash-dot--blue,
.dash-dot--at_terminal { background: #3b82f6; }
.dash-dot--purple { background: #a855f7; }
.dash-dot--danger,
.dash-dot--left_terminal { background: #ef4444; }
.dash-dot--muted,
.dash-dot--other { background: #94a3b8; }

.dash-section__head {
    display: flex;
    align-items: flex-start;
    gap: 0.7rem;
    margin-bottom: 0.85rem;
}

.dash-section__icon {
    color: #a855f7;
    font-size: 1.1rem;
    margin-top: 0.15rem;
}

.dash-db-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    margin-bottom: 1rem;
}

.dash-db-stat {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    padding: 0.6rem 0.85rem;
    border: 1px solid var(--admin-border);
    border-radius: 10px;
    background: var(--admin-surface);
    flex: 1 1 120px;
}

.dash-db-stat span {
    font-size: 0.75rem;
    color: var(--vs-text-muted);
}

.dash-db-stat strong {
    font-size: 0.95rem;
    font-weight: 700;
}

.dash-db-stat--free {
    color: #22c55e;
}

.dash-db-body {
    display: grid;
    grid-template-columns: 160px 1fr;
    gap: 1.1rem;
    align-items: start;
}

.dash-db-chart {
    position: relative;
    width: 140px;
    height: 140px;
    border-radius: 50%;
    flex-shrink: 0;
}

.dash-db-donut {
    width: 140px;
    height: 140px;
    border-radius: 50%;
}

.dash-db-donut-hole {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 60%;
    height: 60%;
    border-radius: 50%;
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
}

.dash-db-table-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.dash-db-table-list li {
    display: grid;
    grid-template-columns: 0.6rem 1fr auto auto auto;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
}

.dash-db-table-dot {
    width: 0.6rem;
    height: 0.6rem;
    border-radius: 999px;
    flex-shrink: 0;
}

.dash-db-table-name {
    font-family: ui-monospace, monospace;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.dash-db-table-rows,
.dash-db-table-pct {
    color: var(--vs-text-muted);
    white-space: nowrap;
}

.dash-db-table-size {
    white-space: nowrap;
    font-weight: 700;
}

@media (max-width: 640px) {
    .dash-db-body {
        grid-template-columns: 1fr;
    }

    .dash-db-chart {
        margin: 0 auto;
    }
}

@media (max-width: 640px) {
    .dash-bars {
        min-height: 150px;
        gap: 0.35rem;
    }

    .dash-bar__track {
        height: 110px;
        max-width: 28px;
    }

    .dash-chart-card__total,
    .dash-stat-card__head strong,
    .dash-lp-card__head strong {
        font-size: 1.35rem;
    }
}
</style>
