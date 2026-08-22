<template>
    <div class="auction-search admin-page">
        <header class="auction-search__head">
            <div>
                <h2 class="auction-search__title">{{ t('auctions.title') }}</h2>
                <p class="auction-search__sub">{{ t('auctions.subtitle') }}</p>
            </div>
            <div class="auction-search__head-actions">
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
                <Button
                    :label="t('actions.refresh')"
                    icon="pi pi-refresh"
                    outlined
                    size="small"
                    :loading="loading"
                    @click="refreshCurrent"
                />
            </div>
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
            </div>
        </section>

        <p v-if="viewMode === 'search' && (lastCached || restoredFromCache)" class="auction-cache-badge">
            {{ t('auctions.fromCache') }}
        </p>
        <p v-if="error" class="auction-search__error">{{ error }}</p>

        <div class="auction-layout" :class="{ 'auction-layout--favorites': viewMode === 'favorites' }">
            <aside v-if="viewMode === 'search'" class="auction-filters admin-surface">
                <div class="auction-filters__head">
                    <strong><i class="pi pi-filter" /> {{ t('auctions.filters') }}</strong>
                    <button type="button" class="link-btn" @click="clearFilters">{{ t('actions.clearFilters') }}</button>
                </div>

                <div class="field">
                    <label>{{ t('auctions.keyword') }}</label>
                    <IconField>
                        <InputIcon class="pi pi-search" />
                        <InputText
                            v-model="filters.q"
                            class="w-full"
                            dir="ltr"
                            :placeholder="t('auctions.keywordPlaceholder')"
                            @keyup.enter="search(false)"
                        />
                    </IconField>
                </div>

                <div class="field">
                    <label>{{ t('auctions.lotStatus') }}</label>
                    <div class="chip-row">
                        <button
                            v-for="opt in lotStatusOptions"
                            :key="opt.value"
                            type="button"
                            class="chip"
                            :class="{ 'chip--active chip--navy': filters.lot_status === opt.value }"
                            @click="filters.lot_status = opt.value"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <div class="field">
                    <label>{{ t('auctions.lotSubStatus') }}</label>
                    <div class="chip-row">
                        <button
                            v-for="opt in lotSubStatusOptions"
                            :key="opt.value"
                            type="button"
                            class="chip"
                            :class="{ 'chip--active chip--teal': filters.lot_sub_status === opt.value }"
                            @click="filters.lot_sub_status = opt.value"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <div class="field">
                    <label>{{ t('auctions.auctionType') }}</label>
                    <div class="chip-row">
                        <button
                            v-for="opt in platformOptions"
                            :key="opt.value || 'all'"
                            type="button"
                            class="chip"
                            :class="{ 'chip--active chip--orange': filters.platform === (opt.value || '') }"
                            @click="filters.platform = opt.value"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <div class="field">
                    <label>{{ t('auctions.make') }}</label>
                    <Select
                        v-model="filters.make"
                        class="w-full"
                        :options="makeOptions"
                        :placeholder="t('auctions.all')"
                        :loading="filtersLoading"
                        filter
                        show-clear
                        @change="onMakeChange"
                    />
                </div>

                <div class="field">
                    <label>{{ t('auctions.model') }}</label>
                    <Select
                        v-model="filters.model"
                        class="w-full"
                        :options="modelOptions"
                        :placeholder="t('auctions.all')"
                        :disabled="! filters.make"
                        filter
                        show-clear
                    />
                </div>

                <div class="field">
                    <label>{{ t('auctions.vehicleType') }}</label>
                    <Select
                        v-model="filters.type"
                        class="w-full"
                        :options="typeOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="t('auctions.all')"
                        :loading="filtersLoading"
                        filter
                        show-clear
                    />
                </div>

                <div class="field-row">
                    <div class="field">
                        <label>{{ t('auctions.yearFrom') }}</label>
                        <InputNumber
                            v-model="filters.year_from"
                            class="w-full"
                            :min="yearBounds.min"
                            :max="yearBounds.max"
                            :use-grouping="false"
                            :placeholder="String(yearBounds.min)"
                        />
                    </div>
                    <div class="field">
                        <label>{{ t('auctions.yearTo') }}</label>
                        <InputNumber
                            v-model="filters.year_to"
                            class="w-full"
                            :min="yearBounds.min"
                            :max="yearBounds.max"
                            :use-grouping="false"
                            :placeholder="String(yearBounds.max)"
                        />
                    </div>
                </div>

                <div class="field">
                    <label>{{ t('auctions.state') }}</label>
                    <Select
                        v-model="filters.state"
                        class="w-full"
                        :options="stateOptions"
                        :placeholder="t('auctions.all')"
                        filter
                        show-clear
                    />
                </div>

                <Button
                    :label="t('auctions.search')"
                    icon="pi pi-search"
                    class="btn-add auction-filters__search"
                    :loading="loading"
                    @click="search(false)"
                />
            </aside>

            <section class="auction-results">
                <div class="auction-results__head admin-surface">
                    <div>
                        <h3>{{ viewMode === 'favorites' ? t('auctions.favoritesTab') : t('auctions.resultsTitle') }}</h3>
                        <p class="muted">
                            <template v-if="viewMode === 'favorites'">{{ t('auctions.favoritesHint') }}</template>
                            <template v-else>{{ t('auctions.resultsHint') }}</template>
                        </p>
                    </div>
                    <div class="auction-results__meta">
                        <span v-if="displayRows.length" class="items-badge">{{ displayRows.length }} {{ t('auctions.items') }}</span>
                        <div class="layout-toggle">
                            <button
                                type="button"
                                class="layout-btn"
                                :class="{ 'layout-btn--active': resultLayout === 'grid' }"
                                :title="t('auctions.gridView')"
                                @click="resultLayout = 'grid'"
                            >
                                <i class="pi pi-th-large" />
                            </button>
                            <button
                                type="button"
                                class="layout-btn"
                                :class="{ 'layout-btn--active': resultLayout === 'list' }"
                                :title="t('auctions.listView')"
                                @click="resultLayout = 'list'"
                            >
                                <i class="pi pi-list" />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="loading && ! displayRows.length" class="auction-search__loading admin-surface">
                    <ProgressSpinner style="width: 32px; height: 32px" />
                    <span>{{ viewMode === 'favorites' ? t('auctions.loadingFavorites') : t('auctions.loading') }}</span>
                </div>

                <p v-else-if="! loading && ! displayRows.length" class="auction-search__empty admin-surface">
                    <template v-if="viewMode === 'favorites'">{{ t('auctions.favoritesEmpty') }}</template>
                    <template v-else>{{ searched ? t('auctions.empty') : t('auctions.prompt') }}</template>
                </p>

                <div
                    v-else
                    class="vehicle-grid"
                    :class="{ 'vehicle-grid--list': resultLayout === 'list' }"
                >
                    <article
                        v-for="row in displayRows"
                        :key="rowKey(row)"
                        class="vehicle-card admin-surface"
                        @click="openDetail(row)"
                    >
                        <div class="vehicle-card__media">
                            <img
                                v-if="thumb(row)"
                                :src="thumb(row)"
                                alt=""
                                loading="lazy"
                            />
                            <div v-else class="vehicle-card__no-photo">{{ t('auctions.noPhotos') }}</div>
                            <span class="status-pill">{{ statusOf(row) }}</span>
                            <button
                                type="button"
                                class="fav-btn"
                                :class="{ 'fav-btn--on': isFavorite(row) }"
                                :title="isFavorite(row) ? t('auctions.removeFavorite') : t('auctions.addFavorite')"
                                :disabled="favoriteBusy === favoriteKey(row)"
                                @click.stop="toggleFavorite(row)"
                            >
                                <i :class="isFavorite(row) ? 'pi pi-heart-fill' : 'pi pi-heart'" />
                            </button>
                        </div>

                        <div class="vehicle-card__body">
                            <div class="badge-row">
                                <span class="pill" :class="platformClass(row.platform)">{{ platformLabel(row.platform) }}</span>
                                <span v-if="damageOf(row) !== '—'" class="pill">{{ damageOf(row) }}</span>
                                <span v-if="runConditionOf(row)" class="pill">{{ runConditionOf(row) }}</span>
                            </div>

                            <h4 class="vehicle-card__title">{{ titleOf(row) }}</h4>

                            <ul class="vehicle-card__specs">
                                <li><i class="pi pi-map-marker" /> {{ locationOf(row) }}</li>
                                <li><i class="pi pi-gauge" /> {{ odometerOf(row) }}</li>
                                <li><i class="pi pi-cog" /> {{ drivetrainOf(row) }}</li>
                            </ul>

                            <div class="vehicle-card__prices">
                                <div>
                                    <span>{{ t('auctions.col.currentBid') }}</span>
                                    <strong>{{ money(bidOf(row)) }}</strong>
                                </div>
                                <div>
                                    <span>{{ t('auctions.col.buyNow') }}</span>
                                    <strong>{{ money(buyNowOf(row)) }}</strong>
                                </div>
                                <div>
                                    <span>{{ t('auctions.col.auctionDate') }}</span>
                                    <strong>{{ auctionDate(row) }}</strong>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-if="viewMode === 'search' && meta?.next_cursor" class="auction-search__pager">
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
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import InputIcon from 'primevue/inputicon';
import IconField from 'primevue/iconfield';
import ProgressSpinner from 'primevue/progressspinner';
import Select from 'primevue/select';
import {
    addAuctionFavorite,
    getAuctionFilters,
    getAuctionUsage,
    listAuctionFavoriteIds,
    listAuctionFavorites,
    removeAuctionFavorite,
    searchAuctions,
} from '../../api/auctions';
import { useAuctionSearchStore } from '../../stores/auctionSearch';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const auctionSearchStore = useAuctionSearchStore();

const viewMode = ref('search');
const resultLayout = ref('grid');
const filtersMeta = ref(null);
const filtersLoading = ref(false);
const restoredFromCache = ref(false);

const filters = reactive({
    platform: '',
    make: null,
    model: null,
    type: null,
    year_from: null,
    year_to: null,
    q: '',
    state: null,
    lot_status: 'All',
    lot_sub_status: 'Open',
    per_page: 10,
});

const rows = ref([]);
const favoriteRows = ref([]);
const favoriteIds = ref([]);
const favoriteBusy = ref('');
const meta = ref(null);
const loading = ref(false);
const loadingMore = ref(false);
const error = ref('');
const searched = ref(false);
const lastCached = ref(false);
const usage = ref(null);

const platformOptions = computed(() => [
    { value: '', label: t('auctions.all') },
    { value: 'copart', label: 'Copart' },
    { value: 'iaai', label: 'IAAI' },
]);

const lotStatusOptions = computed(() => {
    const remote = filtersMeta.value?.lot?.status;

    if (Array.isArray(remote) && remote.length) {
        return remote.map((item) => ({
            value: item.value,
            label: item.label || item.value,
        }));
    }

    return [
        { value: 'All', label: t('auctions.all') },
        { value: 'Buy Now', label: t('auctions.buyNow') },
        { value: 'Timed', label: t('auctions.timed') },
    ];
});

const lotSubStatusOptions = computed(() => {
    const remote = filtersMeta.value?.lot?.sub_status;

    if (Array.isArray(remote) && remote.length) {
        return remote.map((item) => ({
            value: item.value,
            label: item.label || item.value,
        }));
    }

    return [
        { value: 'Open', label: t('auctions.open') },
        { value: 'Live', label: t('auctions.live') },
        { value: 'Ended', label: t('auctions.ended') },
    ];
});

const makeOptions = computed(() => {
    const makes = filtersMeta.value?.make_model?.makes;

    return Array.isArray(makes) ? makes : [];
});

const modelOptions = computed(() => {
    if (! filters.make) return [];

    const map = filtersMeta.value?.make_model?.models_by_make || {};

    return Array.isArray(map[filters.make]) ? map[filters.make] : [];
});

const typeOptions = computed(() => {
    const types = filtersMeta.value?.types;

    if (! Array.isArray(types)) return [];

    return [
        ...types.map((item) => ({
            value: item.name,
            label: item.name,
        })),
    ];
});

const stateOptions = computed(() => {
    const states = filtersMeta.value?.location_filters?.state?.options
        || filtersMeta.value?.location_filters?.state;

    return Array.isArray(states) ? states : [];
});

const yearBounds = computed(() => {
    const year = filtersMeta.value?.ranges?.year || {};

    return {
        min: Number(year.min || 1900),
        max: Number(year.max || 2027),
    };
});

const detailRouteName = computed(() => (
    route.path.startsWith('/dealer') ? 'dealer.auctionDetail' : 'admin.auctionDetail'
));

const displayRows = computed(() => (
    viewMode.value === 'favorites' ? favoriteRows.value : rows.value
));

watch(() => filters.make, (next, prev) => {
    if (next !== prev) {
        filters.model = null;
    }
});

watch(resultLayout, () => {
    if (searched.value || rows.value.length) {
        persistSearchSnapshot();
    }
});

function onMakeChange() {
    filters.model = null;
}

function buildParams(cursor = null) {
    const params = {
        lot_status: filters.lot_status || 'All',
        lot_sub_status: filters.lot_sub_status || 'Open',
        per_page: filters.per_page || 10,
    };

    if (filters.platform) params.platform = filters.platform;
    if (filters.make) params.make = filters.make;
    if (filters.model) params.model = filters.model;
    if (filters.type) params.type = filters.type;
    if (filters.year_from) params.year_from = filters.year_from;
    if (filters.year_to) params.year_to = filters.year_to;
    if (filters.q?.trim()) params.q = filters.q.trim();
    if (filters.state) params.state = filters.state;
    if (cursor) params.cursor = cursor;

    return params;
}

async function switchView(mode) {
    viewMode.value = mode;
    error.value = '';

    if (mode === 'favorites') {
        await loadFavorites();
    }
}

async function refreshCurrent() {
    if (viewMode.value === 'favorites') {
        await loadFavorites();
    } else {
        await search(false);
    }
}

async function loadFilterMeta({ applyDefaults = true } = {}) {
    filtersLoading.value = true;

    try {
        const { data } = await getAuctionFilters();
        filtersMeta.value = data.data ?? null;

        if (! applyDefaults) {
            return;
        }

        const defaults = filtersMeta.value?.lot?.defaults;
        if (defaults?.lot_status) filters.lot_status = defaults.lot_status;
        if (defaults?.lot_sub_status) filters.lot_sub_status = defaults.lot_sub_status;

        const year = filtersMeta.value?.ranges?.year;
        if (year?.from_default != null && filters.year_from == null) {
            filters.year_from = Number(year.from_default);
        }
        if (year?.to_default != null && filters.year_to == null) {
            filters.year_to = Number(year.to_default);
        }
    } catch {
        // selects still work with fallbacks
    } finally {
        filtersLoading.value = false;
    }
}

function persistSearchSnapshot() {
    auctionSearchStore.saveSnapshot({
        filters: { ...filters },
        rows: rows.value,
        meta: meta.value,
        resultLayout: resultLayout.value,
        searched: searched.value,
        lastCached: lastCached.value || restoredFromCache.value,
    });
}

function restoreSearchSnapshot() {
    if (! auctionSearchStore.hydrate()) {
        return false;
    }

    const saved = auctionSearchStore.filters;

    if (saved && typeof saved === 'object') {
        Object.assign(filters, {
            platform: saved.platform ?? '',
            make: saved.make ?? null,
            model: saved.model ?? null,
            type: saved.type ?? null,
            year_from: saved.year_from ?? null,
            year_to: saved.year_to ?? null,
            q: saved.q ?? '',
            state: saved.state ?? null,
            lot_status: saved.lot_status || 'All',
            lot_sub_status: saved.lot_sub_status || 'Open',
            per_page: saved.per_page || 10,
        });
    }

    rows.value = Array.isArray(auctionSearchStore.rows) ? [...auctionSearchStore.rows] : [];
    meta.value = auctionSearchStore.meta ?? null;
    resultLayout.value = auctionSearchStore.resultLayout === 'list' ? 'list' : 'grid';
    searched.value = Boolean(auctionSearchStore.searched);
    lastCached.value = true;
    restoredFromCache.value = true;
    viewMode.value = 'search';

    return true;
}

async function search(loadMore = false) {
    viewMode.value = 'search';
    error.value = '';
    searched.value = true;
    restoredFromCache.value = false;

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
        persistSearchSnapshot();
        await Promise.all([loadUsage(), loadFavoriteIds()]);
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

async function loadFavorites() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await listAuctionFavorites();
        favoriteRows.value = Array.isArray(data.data) ? data.data : [];
        favoriteIds.value = favoriteRows.value.map((row) => row.identifier).filter(Boolean);
    } catch (e) {
        error.value = e.response?.data?.message || t('auctions.loadFailed');
        favoriteRows.value = [];
    } finally {
        loading.value = false;
    }
}

async function loadFavoriteIds() {
    try {
        const { data } = await listAuctionFavoriteIds();
        favoriteIds.value = Array.isArray(data.data) ? data.data : [];
    } catch {
        // optional
    }
}

async function loadUsage() {
    try {
        const { data } = await getAuctionUsage();
        usage.value = data.data?.local ?? null;
    } catch {
        // optional
    }
}

function clearFilters() {
    filters.platform = '';
    filters.make = null;
    filters.model = null;
    filters.type = null;
    filters.q = '';
    filters.state = null;
    filters.lot_status = filtersMeta.value?.lot?.defaults?.lot_status || 'All';
    filters.lot_sub_status = filtersMeta.value?.lot?.defaults?.lot_sub_status || 'Open';

    const year = filtersMeta.value?.ranges?.year;
    filters.year_from = year?.from_default != null ? Number(year.from_default) : null;
    filters.year_to = year?.to_default != null ? Number(year.to_default) : null;

    rows.value = [];
    meta.value = null;
    searched.value = false;
    error.value = '';
    lastCached.value = false;
    restoredFromCache.value = false;
    auctionSearchStore.clear();
}

function favoriteKey(row) {
    return row.identifier || row.slug_vin || row.vin || row.lot_number || '';
}

function isFavorite(row) {
    const key = favoriteKey(row);

    return key !== '' && favoriteIds.value.includes(key);
}

async function toggleFavorite(row) {
    const key = favoriteKey(row);

    if (! key) return;

    favoriteBusy.value = key;

    try {
        if (isFavorite(row)) {
            await removeAuctionFavorite(key);
            favoriteIds.value = favoriteIds.value.filter((id) => id !== key);
            favoriteRows.value = favoriteRows.value.filter((item) => item.identifier !== key);
        } else {
            const payload = {
                identifier: key,
                slug_vin: row.slug_vin || key,
                vin: row.vin,
                lot_number: row.lot_number,
                platform: row.platform,
                title: row.title || titleOf(row),
                year: row.year,
                make: row.make,
                model: row.model,
                pricing: row.pricing,
                location: row.location,
                condition: row.condition,
                media: row.media,
                auction: row.auction,
                ad: row.ad,
                thumb_url: thumb(row),
                current_bid_usd: bidOf(row),
                buy_now_usd: buyNowOf(row),
                location_display: locationOf(row) === '—' ? null : locationOf(row),
                primary_damage: damageOf(row) === '—' ? null : damageOf(row),
                auction_at: auctionDate(row) === '—' ? null : auctionDate(row),
            };
            const { data } = await addAuctionFavorite(payload);
            if (! favoriteIds.value.includes(key)) {
                favoriteIds.value = [...favoriteIds.value, key];
            }
            if (data.data) {
                favoriteRows.value = [data.data, ...favoriteRows.value.filter((item) => item.identifier !== key)];
            }
        }
    } catch (e) {
        error.value = e.response?.data?.message || t('auctions.favoriteFailed');
    } finally {
        favoriteBusy.value = '';
    }
}

function rowKey(row) {
    return favoriteKey(row) || `${row.platform}-${row.lot_number}-${row.vin}`;
}

function thumb(row) {
    return row.thumb_url
        || row.media?.thumbs?.[0]
        || row.media?.items?.find((item) => item.type === 'image')?.thumb
        || null;
}

function titleOf(row) {
    if (row.title) return String(row.title).toUpperCase();

    return `${row.year || ''} ${row.make || ''} ${row.model || ''}`.trim().toUpperCase() || '—';
}

function bidOf(row) {
    return row.pricing?.current_bid_usd ?? row.current_bid_usd ?? null;
}

function buyNowOf(row) {
    return row.pricing?.buy_now_usd ?? row.buy_now_usd ?? null;
}

function locationOf(row) {
    return row.location?.display || row.location_display || '—';
}

function damageOf(row) {
    return row.condition?.primary_damage || row.primary_damage || '—';
}

function runConditionOf(row) {
    return row.condition?.run_condition || row.condition?.starts_drives || null;
}

function odometerOf(row) {
    if (row.odometer?.mi != null) {
        const mi = Number(row.odometer.mi).toLocaleString();
        const km = row.odometer.km != null
            ? Number(row.odometer.km).toLocaleString()
            : Math.round(Number(row.odometer.mi) * 1.60934).toLocaleString();

        return `${mi} mi / ${km} km`;
    }

    return '—';
}

function drivetrainOf(row) {
    const fuel = row.vehicle_specs?.fuel_type || row.fuel_type;
    const transmission = row.vehicle_specs?.transmission || row.transmission;
    const engine = row.vehicle_specs?.engine || row.engine;

    return [fuel, transmission, engine].filter(Boolean).join(' · ') || '—';
}

function statusOf(row) {
    return row.auction?.lot_status
        || row.auction?.state
        || row.lot_status
        || filters.lot_sub_status
        || 'OPEN';
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
        || row.auction_at
        || row.ad
        || '—';
}

function platformLabel(platform) {
    if (! platform) return '—';

    return String(platform).toUpperCase();
}

function platformClass(platform) {
    return platform === 'iaai' ? 'pill--iaai' : 'pill--copart';
}

function openDetail(row) {
    const id = favoriteKey(row);

    if (! id) return;

    router.push({ name: detailRouteName.value, params: { identifier: id } });
}

onMounted(async () => {
    const restored = restoreSearchSnapshot();

    await Promise.all([
        loadFilterMeta({ applyDefaults: ! restored }),
        loadUsage(),
        loadFavoriteIds(),
    ]);
});
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
    flex-wrap: wrap;
}

.auction-search__head-actions {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
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

.view-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.view-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    border: 1px solid var(--admin-border, var(--vs-border));
    background: transparent;
    color: var(--vs-text-muted);
    border-radius: 10px;
    padding: 0.4rem 0.8rem;
    font-weight: 600;
    font-size: 0.82rem;
    cursor: pointer;
}

.view-tab--active {
    background: color-mix(in srgb, var(--admin-accent, #4a3558) 14%, transparent);
    border-color: var(--admin-accent, #4a3558);
    color: var(--admin-accent, #4a3558);
}

.view-tab__count {
    min-width: 1.2rem;
    height: 1.2rem;
    border-radius: 999px;
    background: color-mix(in srgb, #be123c 18%, transparent);
    color: #be123c;
    font-size: 0.7rem;
    display: inline-grid;
    place-items: center;
    padding: 0 0.25rem;
}

.auction-usage {
    padding: 0.75rem 1rem;
}

.auction-usage__stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
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

.text-warn { color: #d97706; }

.auction-cache-badge {
    margin: 0;
    font-size: 0.78rem;
    color: #15803d;
    font-weight: 600;
}

.auction-search__error {
    margin: 0;
    color: #b91c1c;
    font-size: 0.9rem;
}

.auction-layout {
    display: grid;
    grid-template-columns: minmax(260px, 300px) minmax(0, 1fr);
    gap: 1rem;
    align-items: start;
}

.auction-layout--favorites {
    grid-template-columns: 1fr;
}

.auction-filters {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    position: sticky;
    top: 0.75rem;
}

.auction-filters__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.auction-filters__head strong {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.9rem;
}

.link-btn {
    border: none;
    background: transparent;
    color: var(--admin-accent, #4a3558);
    font-weight: 600;
    font-size: 0.8rem;
    cursor: pointer;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.field label {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--vs-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.55rem;
}

.w-full { width: 100%; }

.chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.chip {
    border: 1px solid var(--admin-border, var(--vs-border));
    background: transparent;
    color: var(--vs-text-muted);
    border-radius: 999px;
    padding: 0.35rem 0.7rem;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
}

.chip--active.chip--navy {
    background: #1e293b;
    border-color: #1e293b;
    color: #fff;
}

.chip--active.chip--teal {
    background: #0d9488;
    border-color: #0d9488;
    color: #fff;
}

.chip--active.chip--orange {
    background: #ea580c;
    border-color: #ea580c;
    color: #fff;
}

.auction-filters__search {
    width: 100%;
    margin-top: 0.25rem;
}

.auction-results {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    min-width: 0;
}

.auction-results__head {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    flex-wrap: wrap;
    align-items: center;
}

.auction-results__head h3 {
    margin: 0;
    font-size: 1rem;
}

.muted {
    margin: 0.2rem 0 0;
    color: var(--vs-text-muted);
    font-size: 0.8rem;
}

.auction-results__meta {
    display: flex;
    align-items: center;
    gap: 0.55rem;
}

.items-badge {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    border: 1px solid var(--admin-border, var(--vs-border));
    border-radius: 999px;
    padding: 0.3rem 0.65rem;
    color: var(--vs-text-muted);
}

.layout-toggle {
    display: inline-flex;
    border: 1px solid var(--admin-border, var(--vs-border));
    border-radius: 10px;
    overflow: hidden;
}

.layout-btn {
    border: none;
    background: transparent;
    color: var(--vs-text-muted);
    padding: 0.4rem 0.55rem;
    cursor: pointer;
}

.layout-btn--active {
    background: color-mix(in srgb, var(--admin-accent, #4a3558) 14%, transparent);
    color: var(--admin-accent, #4a3558);
}

.auction-search__loading,
.auction-search__empty {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    min-height: 12rem;
    color: var(--vs-text-muted);
    padding: 1rem;
}

.vehicle-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 0.9rem;
}

.vehicle-grid--list {
    grid-template-columns: 1fr;
}

.vehicle-card {
    overflow: hidden;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.vehicle-grid--list .vehicle-card {
    display: grid;
    grid-template-columns: minmax(220px, 320px) minmax(0, 1fr);
}

.vehicle-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18);
}

.vehicle-card__media {
    position: relative;
    aspect-ratio: 16 / 10;
    background: #0f172a;
    overflow: hidden;
}

.vehicle-grid--list .vehicle-card__media {
    aspect-ratio: auto;
    min-height: 180px;
    height: 100%;
}

.vehicle-card__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.vehicle-card__no-photo {
    height: 100%;
    display: grid;
    place-items: center;
    color: #94a3b8;
    font-size: 0.85rem;
    padding: 1rem;
    text-align: center;
}

.status-pill {
    position: absolute;
    top: 0.65rem;
    inset-inline-start: 0.65rem;
    background: rgba(15, 23, 42, 0.82);
    color: #e2e8f0;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    border-radius: 999px;
    padding: 0.28rem 0.55rem;
}

.fav-btn {
    position: absolute;
    top: 0.55rem;
    inset-inline-end: 0.55rem;
    width: 2rem;
    height: 2rem;
    border: none;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.75);
    color: #e2e8f0;
    cursor: pointer;
    display: grid;
    place-items: center;
}

.fav-btn--on {
    color: #fb7185;
}

.fav-btn:disabled {
    opacity: 0.55;
    cursor: wait;
}

.vehicle-card__body {
    padding: 0.85rem 0.9rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.badge-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.pill {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 0.22rem 0.55rem;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    background: color-mix(in srgb, var(--admin-accent, #4a3558) 12%, transparent);
    color: var(--vs-text);
}

.pill--copart {
    background: color-mix(in srgb, #ea580c 18%, transparent);
    color: #c2410c;
}

.pill--iaai {
    background: color-mix(in srgb, #0284c7 18%, transparent);
    color: #0369a1;
}

.vehicle-card__title {
    margin: 0;
    font-size: 1rem;
    line-height: 1.25;
    font-weight: 800;
}

.vehicle-card__specs {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    color: var(--vs-text-muted);
    font-size: 0.82rem;
}

.vehicle-card__specs li {
    display: flex;
    align-items: flex-start;
    gap: 0.45rem;
}

.vehicle-card__specs i {
    margin-top: 0.15rem;
    font-size: 0.85rem;
}

.vehicle-card__prices {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.4rem;
    margin-top: 0.15rem;
}

.vehicle-card__prices > div {
    border: 1px solid var(--admin-border, var(--vs-border));
    border-radius: 10px;
    padding: 0.45rem 0.4rem;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    min-width: 0;
}

.vehicle-card__prices span {
    font-size: 0.62rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--vs-text-muted);
    font-weight: 700;
}

.vehicle-card__prices strong {
    font-size: 0.82rem;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.auction-search__pager {
    display: flex;
    justify-content: center;
    padding: 0.35rem 0 0.5rem;
}

@media (max-width: 960px) {
    .auction-layout {
        grid-template-columns: 1fr;
    }

    .auction-filters {
        position: static;
    }

    .vehicle-grid--list .vehicle-card {
        grid-template-columns: 1fr;
    }
}
</style>
