<template>
    <div class="auction-search admin-page">
        <header class="auction-search__head">
            <div>
                <h2 class="auction-search__title">{{ t('auctions.title') }}</h2>
                <p class="auction-search__sub">{{ t('auctions.subtitle') }}</p>
            </div>
            <Button
                :label="t('actions.refresh')"
                icon="pi pi-refresh"
                outlined
                size="small"
                :loading="loading"
                @click="search(false)"
            />
        </header>

        <section v-if="usage" class="auction-usage admin-surface">
            <div class="auction-usage__stats">
                <div>
                    <span>{{ t('auctions.usage.billed') }}</span>
                    <strong>{{ usage.billed }} / {{ usage.free_quota }}</strong>
                </div>
                <div>
                    <span>{{ t('auctions.usage.remaining') }}</span>
                    <strong :class="{ 'text-warn': usage.remaining_estimate <= 20 }">{{ usage.remaining_estimate }}</strong>
                </div>
                <div>
                    <span>{{ t('auctions.usage.cached') }}</span>
                    <strong>{{ usage.cached }}</strong>
                </div>
                <div>
                    <span>{{ t('auctions.usage.cacheTtl') }}</span>
                    <strong>{{ Math.round((usage.cache_ttl_seconds || 3600) / 60) }} {{ t('auctions.usage.minutes') }}</strong>
                </div>
            </div>
            <div v-if="usage.by_user?.length" class="auction-usage__users">
                <h4>{{ t('auctions.usage.byUser') }}</h4>
                <ul>
                    <li v-for="row in usage.by_user.slice(0, 8)" :key="`${row.user_id}-${row.name}`">
                        <span>{{ row.name }} <small>({{ row.role || '—' }})</small></span>
                        <strong>{{ row.billed }} {{ t('auctions.usage.billedShort') }} · {{ row.cached }} {{ t('auctions.usage.cachedShort') }}</strong>
                    </li>
                </ul>
            </div>
            <p class="auction-usage__hint">{{ t('auctions.usage.hint') }}</p>
        </section>

        <p v-if="lastCached" class="auction-cache-badge">{{ t('auctions.fromCache') }}</p>

        <div class="view-tabs" role="tablist">
            <button
                type="button"
                class="view-tab"
                :class="{ 'view-tab--active': viewMode === 'search' }"
                @click="switchView('search')"
            >
                <i class="pi pi-search" />
                {{ t('auctions.searchTab') }}
            </button>
            <button
                type="button"
                class="view-tab"
                :class="{ 'view-tab--active': viewMode === 'favorites' }"
                @click="switchView('favorites')"
            >
                <i class="pi pi-heart-fill" />
                {{ t('auctions.favoritesTab') }}
                <span v-if="favoriteIds.length" class="view-tab__count">{{ favoriteIds.length }}</span>
            </button>
        </div>

        <section v-if="viewMode === 'search'" class="auction-search__filters admin-surface">
            <div class="platform-tabs" role="tablist">
                <button
                    v-for="opt in platformOptions"
                    :key="opt.value || 'all'"
                    type="button"
                    class="platform-tab"
                    :class="{ 'platform-tab--active': filters.platform === (opt.value || '') }"
                    @click="filters.platform = opt.value"
                >
                    {{ opt.label }}
                </button>
            </div>

            <div class="filter-grid">
                <div class="field">
                    <label>{{ t('auctions.make') }}</label>
                    <InputText v-model="filters.make" class="w-full" :placeholder="t('auctions.make')" />
                </div>
                <div class="field">
                    <label>{{ t('auctions.model') }}</label>
                    <InputText v-model="filters.model" class="w-full" :placeholder="t('auctions.model')" />
                </div>
                <div class="field">
                    <label>{{ t('auctions.yearFrom') }}</label>
                    <InputNumber v-model="filters.year_from" class="w-full" :min="1980" :max="2100" :use-grouping="false" />
                </div>
                <div class="field">
                    <label>{{ t('auctions.yearTo') }}</label>
                    <InputNumber v-model="filters.year_to" class="w-full" :min="1980" :max="2100" :use-grouping="false" />
                </div>
                <div class="field">
                    <label>{{ t('auctions.vin') }}</label>
                    <InputText v-model="filters.vin" class="w-full" dir="ltr" placeholder="VIN" />
                </div>
                <div class="field">
                    <label>{{ t('auctions.lotNumber') }}</label>
                    <InputText v-model="filters.lot_number" class="w-full" dir="ltr" />
                </div>
                <div class="field">
                    <label>{{ t('auctions.state') }}</label>
                    <InputText v-model="filters.state" class="w-full" dir="ltr" placeholder="FL / TX / CA" />
                </div>
            </div>

            <div class="filter-actions">
                <Button
                    :label="t('auctions.search')"
                    icon="pi pi-search"
                    class="btn-add"
                    :loading="loading"
                    @click="search(false)"
                />
                <Button
                    :label="t('actions.clearFilters')"
                    icon="pi pi-filter-slash"
                    outlined
                    severity="secondary"
                    @click="clearFilters"
                />
            </div>
        </section>

        <p v-if="error" class="auction-search__error">{{ error }}</p>

        <section class="auction-search__results admin-surface">
            <div v-if="loading && !rows.length" class="auction-search__loading">
                <ProgressSpinner style="width: 32px; height: 32px" />
                <span>{{ t('auctions.loading') }}</span>
            </div>

            <p v-else-if="! loading && ! rows.length" class="auction-search__empty">
                {{ searched ? t('auctions.empty') : t('auctions.prompt') }}
            </p>

            <div v-else class="table-wrap">
                <table class="auction-table">
                    <thead>
                        <tr>
                            <th>{{ t('auctions.col.image') }}</th>
                            <th>{{ t('auctions.col.source') }}</th>
                            <th>{{ t('auctions.col.lot') }}</th>
                            <th>{{ t('auctions.col.vin') }}</th>
                            <th>{{ t('auctions.col.year') }}</th>
                            <th>{{ t('auctions.col.make') }}</th>
                            <th>{{ t('auctions.col.model') }}</th>
                            <th>{{ t('auctions.col.currentBid') }}</th>
                            <th>{{ t('auctions.col.buyNow') }}</th>
                            <th>{{ t('auctions.col.auctionDate') }}</th>
                            <th>{{ t('auctions.col.location') }}</th>
                            <th>{{ t('auctions.col.damage') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in rows"
                            :key="rowKey(row)"
                            class="auction-table__row"
                            @click="openDetail(row)"
                        >
                            <td>
                                <img
                                    v-if="thumb(row)"
                                    :src="thumb(row)"
                                    alt=""
                                    class="auction-thumb"
                                    loading="lazy"
                                />
                                <span v-else class="auction-thumb auction-thumb--empty">—</span>
                            </td>
                            <td>
                                <Tag :value="platformLabel(row.platform)" :severity="platformSeverity(row.platform)" />
                            </td>
                            <td dir="ltr">{{ row.lot_number || '—' }}</td>
                            <td dir="ltr" class="mono">{{ row.vin || '—' }}</td>
                            <td>{{ row.year || '—' }}</td>
                            <td>{{ row.make || '—' }}</td>
                            <td>{{ row.model || '—' }}</td>
                            <td class="money">{{ money(row.pricing?.current_bid_usd) }}</td>
                            <td class="money">{{ money(row.pricing?.buy_now_usd) }}</td>
                            <td>{{ auctionDate(row) }}</td>
                            <td>{{ row.location?.display || '—' }}</td>
                            <td>{{ row.condition?.primary_damage || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="meta?.next_cursor" class="auction-search__pager">
                <Button
                    :label="t('auctions.loadMore')"
                    icon="pi pi-angle-down"
                    outlined
                    :loading="loadingMore"
                    @click="search(true)"
                />
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import ProgressSpinner from 'primevue/progressspinner';
import Tag from 'primevue/tag';
import { getAuctionUsage, searchAuctions } from '../../api/auctions';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const filters = reactive({
    platform: '',
    make: '',
    model: '',
    year_from: null,
    year_to: null,
    vin: '',
    lot_number: '',
    state: '',
    lot_status: 'All',
    per_page: 10,
});

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const loadingMore = ref(false);
const error = ref('');
const searched = ref(false);
const lastCached = ref(false);
const usage = ref(null);

const platformOptions = computed(() => [
    { value: '', label: t('auctions.platformAll') },
    { value: 'copart', label: 'Copart' },
    { value: 'iaai', label: 'IAAI' },
]);

const detailRouteName = computed(() => (
    route.path.startsWith('/dealer') ? 'dealer.auctionDetail' : 'admin.auctionDetail'
));

function buildParams(cursor = null) {
    const params = {
        lot_status: filters.lot_status || 'All',
        per_page: filters.per_page || 10,
    };

    if (filters.platform) params.platform = filters.platform;
    if (filters.make?.trim()) params.make = filters.make.trim();
    if (filters.model?.trim()) params.model = filters.model.trim();
    if (filters.year_from) params.year_from = filters.year_from;
    if (filters.year_to) params.year_to = filters.year_to;
    if (filters.vin?.trim()) params.vin = filters.vin.trim();
    if (filters.lot_number?.trim()) params.lot_number = filters.lot_number.trim();
    if (filters.state?.trim()) params.state = filters.state.trim();
    if (cursor) params.cursor = cursor;

    return params;
}

async function search(loadMore = false) {
    error.value = '';
    searched.value = true;

    if (loadMore) {
        loadingMore.value = true;
    } else {
        loading.value = true;
        rows.value = [];
        meta.value = null;
    }

    try {
        const cursor = loadMore ? meta.value?.next_cursor : null;
        const { data } = await searchAuctions(buildParams(cursor));
        const list = Array.isArray(data.data) ? data.data : [];
        rows.value = loadMore ? [...rows.value, ...list] : list;
        meta.value = data.meta ?? null;
        lastCached.value = Boolean(data.cached);
        await loadUsage();
    } catch (e) {
        error.value = e.response?.data?.message || t('auctions.loadFailed');
        if (! loadMore) {
            rows.value = [];
            meta.value = null;
        }
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
}

async function loadUsage() {
    try {
        const { data } = await getAuctionUsage();
        usage.value = data.data?.local ?? null;
    } catch {
        // usage is optional; ignore failures
    }
}

function clearFilters() {
    filters.platform = '';
    filters.make = '';
    filters.model = '';
    filters.year_from = null;
    filters.year_to = null;
    filters.vin = '';
    filters.lot_number = '';
    filters.state = '';
    rows.value = [];
    meta.value = null;
    searched.value = false;
    error.value = '';
    lastCached.value = false;
}

function rowKey(row) {
    return row.slug_vin || `${row.platform}-${row.lot_number}-${row.vin}`;
}

function thumb(row) {
    return row.media?.thumbs?.[0]
        || row.media?.items?.find((item) => item.type === 'image')?.thumb
        || null;
}

function money(value) {
    if (value == null || value === '') return '—';

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(Number(value));
}

function auctionDate(row) {
    return row.auction?.formatted
        || row.auction?.auction_at
        || row.ad
        || '—';
}

function platformLabel(platform) {
    if (! platform) return '—';

    return String(platform).toUpperCase();
}

function platformSeverity(platform) {
    return platform === 'iaai' ? 'info' : 'warn';
}

function openDetail(row) {
    const id = row.slug_vin || row.vin || row.lot_number;

    if (! id) return;

    router.push({ name: detailRouteName.value, params: { identifier: id } });
}

onMounted(loadUsage);
</script>

<style scoped>
.auction-search {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.auction-search__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.auction-search__title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
}

.auction-search__sub {
    margin: 0.25rem 0 0;
    color: var(--vs-text-muted);
    font-size: 0.85rem;
}

.auction-usage {
    padding: 0.9rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.auction-usage__stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 0.65rem;
}

.auction-usage__stats > div {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.auction-usage__stats span {
    font-size: 0.72rem;
    color: var(--vs-text-muted);
}

.auction-usage__stats strong {
    font-size: 1rem;
}

.text-warn { color: #d97706; }

.auction-usage__users h4 {
    margin: 0 0 0.35rem;
    font-size: 0.85rem;
}

.auction-usage__users ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.auction-usage__users li {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    font-size: 0.8rem;
}

.auction-usage__hint,
.auction-cache-badge {
    margin: 0;
    font-size: 0.78rem;
    color: var(--vs-text-muted);
}

.auction-cache-badge {
    color: #15803d;
    font-weight: 600;
}

.auction-search__filters {
    padding: 1rem 1.1rem;
}

.platform-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    margin-bottom: 1rem;
}

.platform-tab {
    border: 1px solid var(--admin-border, var(--vs-border));
    background: transparent;
    color: var(--vs-text-muted);
    border-radius: 999px;
    padding: 0.4rem 0.9rem;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
}

.platform-tab--active {
    background: color-mix(in srgb, var(--admin-accent, #4a3558) 14%, transparent);
    border-color: var(--admin-accent, #4a3558);
    color: var(--admin-accent, #4a3558);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 0.75rem;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.field label {
    font-size: 0.78rem;
    color: var(--vs-text-muted);
    font-weight: 600;
}

.w-full { width: 100%; }

.filter-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 1rem;
}

.auction-search__results {
    padding: 0.75rem;
    overflow: hidden;
}

.auction-search__loading,
.auction-search__empty,
.auction-search__error {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    min-height: 8rem;
    color: var(--vs-text-muted);
}

.auction-search__error {
    color: #b91c1c;
    min-height: auto;
    justify-content: flex-start;
    padding: 0.5rem 0;
}

.table-wrap {
    overflow-x: auto;
}

.auction-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82rem;
}

.auction-table th,
.auction-table td {
    padding: 0.55rem 0.45rem;
    text-align: start;
    border-bottom: 1px solid var(--admin-border, var(--vs-border));
    white-space: nowrap;
}

.auction-table th {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: var(--vs-text-muted);
}

.auction-table__row {
    cursor: pointer;
}

.auction-table__row:hover {
    background: color-mix(in srgb, var(--admin-accent, #4a3558) 6%, transparent);
}

.auction-thumb {
    width: 64px;
    height: 48px;
    object-fit: cover;
    border-radius: 6px;
    display: block;
    background: #111827;
}

.auction-thumb--empty {
    display: grid;
    place-items: center;
    color: var(--vs-text-muted);
    font-size: 0.75rem;
}

.mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.75rem;
}

.money {
    font-variant-numeric: tabular-nums;
    font-weight: 700;
}

.auction-search__pager {
    display: flex;
    justify-content: center;
    padding: 0.85rem 0 0.25rem;
}
</style>
