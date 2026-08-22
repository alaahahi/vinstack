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
                        <span v-if="favoritesBadgeCount" class="view-tab__count">{{ favoritesBadgeCount }}</span>
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

        <section v-if="isAdmin && usage" class="auction-usage admin-surface">
            <div class="auction-usage__stats">
                <div>
                    <span>{{ t('auctions.activeApi') }}</span>
                    <strong>{{ usage.active_provider?.name || '—' }}</strong>
                </div>
                <div>
                    <span>{{ t('auctions.remainingQuota') }}</span>
                    <strong :class="{ 'text-warn': usage.remaining_estimate <= 20 }">
                        {{ usage.remaining_estimate }} / {{ usage.free_quota }}
                    </strong>
                </div>
                <div>
                    <span>{{ t('auctions.usage.billed') }}</span>
                    <strong>{{ usage.billed }} / {{ usage.free_quota }}</strong>
                </div>
                <div>
                    <span>{{ t('auctions.usage.cached') }}</span>
                    <strong>{{ usage.cached }}</strong>
                </div>
                <div>
                    <span>{{ t('auctions.usage.cacheTtl') }}</span>
                    <strong>{{ cacheHours }} {{ t('auctions.usage.hours') }}</strong>
                </div>
            </div>
            <p class="auction-usage__hint">{{ t('auctions.usage.hint') }}</p>
            <div v-if="usage.by_user?.length" class="auction-usage__users">
                <strong>{{ t('auctions.usage.byUser') }}</strong>
                <ul>
                    <li v-for="row in usage.by_user" :key="`${row.user_id}-${row.role}`">
                        <span class="auction-usage__user-name">{{ row.name }}</span>
                        <span class="pill" :class="row.role === 'admin' ? 'pill--owner' : 'pill--copart'">
                            {{ roleLabel(row.role) }}
                        </span>
                        <span>{{ row.billed }} {{ t('auctions.usage.billedShort') }}</span>
                        <span>{{ row.cached }} {{ t('auctions.usage.cachedShort') }}</span>
                    </li>
                </ul>
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
                            size="small"
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
                        class="w-full filter-select"
                        size="small"
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
                        class="w-full filter-select"
                        size="small"
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
                        class="w-full filter-select"
                        size="small"
                        :options="typeOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="t('auctions.all')"
                        :loading="filtersLoading"
                        filter
                        show-clear
                    />
                </div>

                <div class="field-row year-row">
                    <div class="field">
                        <label>{{ t('auctions.yearFrom') }}</label>
                        <Select
                            v-model="filters.year_from"
                            class="w-full filter-select year-select"
                            size="small"
                            :options="yearOptions"
                            :placeholder="String(DEFAULT_YEAR_FROM)"
                            filter
                            show-clear
                        />
                    </div>
                    <div class="field">
                        <label>{{ t('auctions.yearTo') }}</label>
                        <Select
                            v-model="filters.year_to"
                            class="w-full filter-select year-select"
                            size="small"
                            :options="yearOptions"
                            :placeholder="String(DEFAULT_YEAR_TO)"
                            filter
                            show-clear
                        />
                    </div>
                </div>

                <div class="field">
                    <label>{{ t('auctions.state') }}</label>
                    <Select
                        v-model="filters.state"
                        class="w-full filter-select"
                        size="small"
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
                <Button
                    v-if="showLoadCachedButton"
                    :label="t('auctions.loadCached')"
                    icon="pi pi-database"
                    outlined
                    class="auction-filters__search"
                    :loading="loadingCached"
                    @click="loadCachedSearch"
                />
            </aside>

            <section class="auction-results">
                <div class="auction-results__head admin-surface">
                    <div>
                        <h3>{{ viewMode === 'favorites' ? t('auctions.favoritesTab') : t('auctions.resultsTitle') }}</h3>
                        <p class="muted">
                            <template v-if="viewMode === 'favorites'">
                                {{ isAdmin ? t('auctions.favoritesHintAdmin') : t('auctions.favoritesHint') }}
                            </template>
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
                        :key="cardKey(row)"
                        class="vehicle-card admin-surface"
                        @click="openDetail(row)"
                    >
                        <div class="vehicle-card__media" @click.stop>
                            <template v-if="photosOf(row).length">
                                <img
                                    :src="currentPhoto(row)"
                                    alt=""
                                    loading="lazy"
                                    @click="openDetail(row)"
                                />
                                <template v-if="photosOf(row).length > 1">
                                    <button
                                        type="button"
                                        class="slide-btn slide-btn--prev"
                                        :aria-label="t('auctions.prevPhoto')"
                                        @click="shiftSlide(row, -1)"
                                    >
                                        <i class="pi pi-chevron-left" />
                                    </button>
                                    <button
                                        type="button"
                                        class="slide-btn slide-btn--next"
                                        :aria-label="t('auctions.nextPhoto')"
                                        @click="shiftSlide(row, 1)"
                                    >
                                        <i class="pi pi-chevron-right" />
                                    </button>
                                    <div class="slide-dots">
                                        <button
                                            v-for="(_, idx) in Math.min(photosOf(row).length, 8)"
                                            :key="idx"
                                            type="button"
                                            class="slide-dot"
                                            :class="{ 'slide-dot--active': slideIndex(row) === idx }"
                                            @click="goSlide(row, idx)"
                                        />
                                    </div>
                                    <span class="slide-count" dir="ltr">
                                        {{ slideIndex(row) + 1 }}/{{ photosOf(row).length }}
                                    </span>
                                </template>
                            </template>
                            <div v-else class="vehicle-card__no-photo" @click="openDetail(row)">
                                {{ t('auctions.noPhotos') }}
                            </div>
                            <span class="status-pill">{{ statusOf(row) }}</span>
                            <button
                                type="button"
                                class="fav-btn"
                                :class="{ 'fav-btn--on': isFavorite(row) }"
                                :title="isFavorite(row) ? t('auctions.removeFavorite') : t('auctions.addFavorite')"
                                :disabled="favoriteBusy === favoriteBusyKey(row)"
                                @click.stop="toggleFavorite(row)"
                            >
                                <i :class="isFavorite(row) ? 'pi pi-heart-fill' : 'pi pi-heart'" />
                            </button>
                        </div>

                        <div class="vehicle-card__body">
                            <div class="badge-row">
                                <span class="pill" :class="platformClass(row.platform)">{{ platformLabel(row.platform) }}</span>
                                <span
                                    v-if="viewMode === 'favorites' && ownerLabel(row)"
                                    class="pill pill--owner"
                                >
                                    {{ ownerLabel(row) }}
                                </span>
                                <span v-if="damageOf(row) !== '—'" class="pill">{{ damageOf(row) }}</span>
                                <span v-if="runConditionOf(row)" class="pill">{{ runConditionOf(row) }}</span>
                            </div>

                            <h4 class="vehicle-card__title">{{ titleOf(row) }}</h4>

                            <ul class="vehicle-card__specs">
                                <li><i class="pi pi-map-marker" /> {{ locationOf(row) }}</li>
                                <li dir="ltr"><i class="pi pi-gauge" /> {{ odometerOf(row) }}</li>
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
                            <div v-if="viewMode === 'search'" class="vehicle-card__footer" @click.stop>
                                <Button
                                    :label="t('auctions.refreshItem')"
                                    icon="pi pi-refresh"
                                    size="small"
                                    outlined
                                    :loading="refreshingKey === cardKey(row)"
                                    @click="refreshItem(row)"
                                />
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
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputIcon from 'primevue/inputicon';
import IconField from 'primevue/iconfield';
import ProgressSpinner from 'primevue/progressspinner';
import Select from 'primevue/select';
import {
    addAuctionFavorite,
    getAuction,
    getAuctionCacheStatus,
    getAuctionFilters,
    getAuctionUsage,
    listAuctionFavoriteIds,
    listAuctionFavorites,
    removeAuctionFavorite,
    searchAuctions,
} from '../../api/auctions';
import { filtersKey, useAuctionSearchStore } from '../../stores/auctionSearch';
import { useAuthStore } from '../../stores/auth';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const auctionSearchStore = useAuctionSearchStore();
const auth = useAuthStore();

const DEFAULT_YEAR_FROM = 2025;
const DEFAULT_YEAR_TO = 2027;

const isAdmin = computed(() => auth.isAdmin);
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
    year_from: DEFAULT_YEAR_FROM,
    year_to: DEFAULT_YEAR_TO,
    q: '',
    state: null,
    lot_status: 'All',
    lot_sub_status: 'Open',
    per_page: 10,
});

const rows = ref([]);
const favoriteRows = ref([]);
const favoriteIds = ref([]);
const favoritesCount = ref(0);
const favoriteBusy = ref('');
const slideByKey = reactive({});
const meta = ref(null);
const loading = ref(false);
const loadingMore = ref(false);
const error = ref('');
const searched = ref(false);
const lastCached = ref(false);
const usage = ref(null);
const loadingCached = ref(false);
const refreshingKey = ref('');
const serverCacheAvailable = ref(false);
const activeSearchKey = ref('');
let cacheCheckTimer = null;

const cacheHours = computed(() => (
    Math.max(1, Math.round(Number(usage.value?.cache_ttl_seconds || 86400) / 3600))
));

const currentSearchKey = computed(() => filtersKey(filters));

const showLoadCachedButton = computed(() => {
    if (viewMode.value !== 'search') return false;

    if (currentSearchKey.value === activeSearchKey.value && rows.value.length) {
        return false;
    }

    return Boolean(auctionSearchStore.snapshotFor(filters) || serverCacheAvailable.value);
});

const favoritesBadgeCount = computed(() => (
    isAdmin.value ? favoritesCount.value : favoriteIds.value.length
));

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
        min: Number(year.min || 1980),
        max: Number(year.max || DEFAULT_YEAR_TO),
    };
});

const yearOptions = computed(() => {
    const years = [];

    for (let y = yearBounds.value.max; y >= yearBounds.value.min; y -= 1) {
        years.push(y);
    }

    return years;
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

watch(filters, () => {
    serverCacheAvailable.value = Boolean(auctionSearchStore.snapshotFor(filters));
    clearTimeout(cacheCheckTimer);
    cacheCheckTimer = setTimeout(checkServerCache, 400);
}, { deep: true });

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
        await search(false, { forceRefresh: true });
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

        if (filters.year_from == null) filters.year_from = DEFAULT_YEAR_FROM;
        if (filters.year_to == null) filters.year_to = DEFAULT_YEAR_TO;
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
            year_from: saved.year_from ?? DEFAULT_YEAR_FROM,
            year_to: saved.year_to ?? DEFAULT_YEAR_TO,
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
    activeSearchKey.value = filtersKey(filters);

    return true;
}

async function search(loadMore = false, { forceRefresh = false, cacheOnly = false } = {}) {
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
        const params = {
            ...buildParams(cursor),
        };

        if (forceRefresh) params.force_refresh = true;
        if (cacheOnly) params.cache_only = true;

        const { data } = await searchAuctions(params);
        const list = Array.isArray(data.data) ? data.data : [];
        rows.value = loadMore ? [...rows.value, ...list] : list;
        meta.value = data.meta ?? null;
        lastCached.value = Boolean(data.cached);
        activeSearchKey.value = currentSearchKey.value;
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

async function checkServerCache() {
    try {
        const { data } = await getAuctionCacheStatus(buildParams());
        serverCacheAvailable.value = Boolean(data.data?.available);
    } catch {
        // keep local snapshot flag
    }
}

async function loadCachedSearch() {
    const local = auctionSearchStore.snapshotFor({ ...filters });

    if (local?.rows?.length) {
        rows.value = [...local.rows];
        meta.value = local.meta ?? null;
        searched.value = true;
        lastCached.value = true;
        restoredFromCache.value = true;
        error.value = '';
        viewMode.value = 'search';
        activeSearchKey.value = currentSearchKey.value;
        persistSearchSnapshot();

        return;
    }

    loadingCached.value = true;

    try {
        await search(false, { cacheOnly: true });
        restoredFromCache.value = true;
    } finally {
        loadingCached.value = false;
    }
}

async function refreshItem(row) {
    const id = detailIdentifier(row);

    if (! id) return;

    const key = cardKey(row);
    refreshingKey.value = key;
    error.value = '';

    try {
        const { data } = await getAuction(id, { force_refresh: true });
        const updated = data.data;

        if (! updated) return;

        rows.value = rows.value.map((item) => (
            cardKey(item) === key ? { ...item, ...updated } : item
        ));
        persistSearchSnapshot();
        await loadUsage();
    } catch (e) {
        error.value = e.response?.data?.message || t('auctions.loadFailed');
    } finally {
        refreshingKey.value = '';
    }
}

function roleLabel(role) {
    if (role === 'admin') return t('auctions.usage.roleAdmin');
    if (role === 'dealer') return t('auctions.usage.roleDealer');

    return role || '—';
}

async function loadFavorites() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await listAuctionFavorites();
        favoriteRows.value = Array.isArray(data.data) ? data.data : [];
        favoritesCount.value = Number(data.meta?.count ?? favoriteRows.value.length);

        if (isAdmin.value) {
            await loadFavoriteIds();
        } else {
            favoriteIds.value = favoriteRows.value.map((row) => row.identifier).filter(Boolean);
        }
    } catch (e) {
        error.value = e.response?.data?.message || t('auctions.loadFailed');
        favoriteRows.value = [];
    } finally {
        loading.value = false;
    }
}

async function refreshFavoritesCount() {
    if (! isAdmin.value) {
        favoritesCount.value = favoriteIds.value.length;

        return;
    }

    try {
        const { data } = await listAuctionFavorites();
        favoritesCount.value = Number(data.meta?.count ?? (Array.isArray(data.data) ? data.data.length : 0));

        if (viewMode.value === 'favorites') {
            favoriteRows.value = Array.isArray(data.data) ? data.data : [];
        }
    } catch {
        // optional
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
    if (! isAdmin.value) {
        usage.value = null;

        return;
    }

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
    filters.year_from = DEFAULT_YEAR_FROM;
    filters.year_to = DEFAULT_YEAR_TO;

    rows.value = [];
    meta.value = null;
    searched.value = false;
    error.value = '';
    lastCached.value = false;
    restoredFromCache.value = false;
    auctionSearchStore.clear();
}

function favoriteKey(row) {
    return row.vin || row.lot_number || row.identifier || row.slug_vin || '';
}

function favoriteBusyKey(row) {
    return `${row.user_id || 'me'}:${favoriteKey(row)}`;
}

function cardKey(row) {
    return row.id ? `fav-${row.id}` : rowKey(row);
}

function ownerLabel(row) {
    const owner = row.owner;

    if (! owner) return '';

    if (owner.company_name) {
        return owner.company_name;
    }

    if (owner.name) {
        return owner.name;
    }

    if (owner.role === 'admin') {
        return t('auctions.ownerAdmin');
    }

    return t('auctions.ownerDealer');
}

function isFavorite(row) {
    if (viewMode.value === 'favorites') {
        return true;
    }

    const key = favoriteKey(row);

    return key !== '' && favoriteIds.value.includes(key);
}

async function toggleFavorite(row) {
    const key = favoriteKey(row);

    if (! key) return;

    const busyKey = favoriteBusyKey(row);
    favoriteBusy.value = busyKey;

    try {
        if (viewMode.value === 'favorites') {
            const params = {};

            if (isAdmin.value && row.user_id) {
                params.user_id = row.user_id;
            }

            await removeAuctionFavorite(key, params);
            favoriteRows.value = favoriteRows.value.filter((item) => {
                if (row.id) return item.id !== row.id;

                return !(item.identifier === key && Number(item.user_id) === Number(row.user_id || auth.user?.id));
            });
            favoritesCount.value = Math.max(0, favoritesCount.value - 1);

            if (! row.user_id || Number(row.user_id) === Number(auth.user?.id)) {
                favoriteIds.value = favoriteIds.value.filter((id) => id !== key);
            }

            return;
        }

        if (isFavorite(row)) {
            await removeAuctionFavorite(key);
            favoriteIds.value = favoriteIds.value.filter((id) => id !== key);
            favoriteRows.value = favoriteRows.value.filter((item) => {
                if (Number(item.user_id || auth.user?.id) !== Number(auth.user?.id)) {
                    return true;
                }

                return item.identifier !== key;
            });
            if (! isAdmin.value) {
                favoritesCount.value = favoriteIds.value.length;
            } else {
                favoritesCount.value = Math.max(0, favoritesCount.value - 1);
            }
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
                favoriteRows.value = [data.data, ...favoriteRows.value.filter((item) => item.id !== data.data.id)];
            }
            if (! isAdmin.value) {
                favoritesCount.value = favoriteIds.value.length;
            } else {
                favoritesCount.value += 1;
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

function textOf(value) {
    if (value == null || value === '') return '';

    if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    if (typeof value === 'object') {
        return textOf(value.label ?? value.value ?? value.raw ?? value.name ?? value.display ?? '');
    }

    return '';
}

function photosOf(row) {
    const items = row?.media?.items;

    if (Array.isArray(items) && items.length) {
        return items
            .filter((item) => item && (item.type === 'image' || item.thumb || item.large || item.full))
            .map((item) => item.large || item.full || item.thumb)
            .filter(Boolean);
    }

    const thumbs = row?.media?.thumbs;

    if (Array.isArray(thumbs) && thumbs.length) {
        return thumbs.filter((url) => typeof url === 'string' && url);
    }

    if (row?.thumb_url) {
        return [row.thumb_url];
    }

    return [];
}

function slideIndex(row) {
    return Number(slideByKey[cardKey(row)] || 0);
}

function currentPhoto(row) {
    const photos = photosOf(row);
    if (! photos.length) return null;

    return photos[Math.min(slideIndex(row), photos.length - 1)];
}

function goSlide(row, idx) {
    const photos = photosOf(row);
    if (! photos.length) return;

    slideByKey[cardKey(row)] = ((idx % photos.length) + photos.length) % photos.length;
}

function shiftSlide(row, delta) {
    goSlide(row, slideIndex(row) + delta);
}

function thumb(row) {
    return currentPhoto(row) || row.thumb_url || null;
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
    return textOf(row.condition?.primary_damage) || textOf(row.primary_damage) || '—';
}

function runConditionOf(row) {
    return textOf(row.condition?.run_condition)
        || textOf(row.condition?.starts_drives)
        || '';
}

function odometerOf(row) {
    if (row.odometer?.mi != null) {
        const mi = Number(row.odometer.mi).toLocaleString('en-US');
        const km = row.odometer.km != null
            ? Number(row.odometer.km).toLocaleString('en-US')
            : Math.round(Number(row.odometer.mi) * 1.60934).toLocaleString('en-US');

        return `${mi} mi / ${km} km`;
    }

    return '—';
}

function drivetrainOf(row) {
    const fuel = textOf(row.vehicle_specs?.fuel_type || row.fuel_type);
    const transmission = textOf(row.vehicle_specs?.transmission || row.transmission);
    const engine = textOf(row.vehicle_specs?.engine || row.engine);

    return [fuel, transmission, engine].filter(Boolean).join(' · ') || '—';
}

function statusOf(row) {
    return textOf(row.auction?.lot_status)
        || textOf(row.auction?.state)
        || textOf(row.lot_status)
        || textOf(filters.lot_sub_status)
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
    return textOf(row.auction?.formatted)
        || textOf(row.auction?.auction_at)
        || textOf(row.auction_at)
        || textOf(row.ad)
        || '—';
}

function platformLabel(platform) {
    if (! platform) return '—';

    return String(platform).toUpperCase();
}

function platformClass(platform) {
    return platform === 'iaai' ? 'pill--iaai' : 'pill--copart';
}

function detailIdentifier(row) {
    return row.vin || row.lot_number || row.slug_vin || row.identifier || '';
}

function openDetail(row) {
    const id = detailIdentifier(row);

    if (! id) return;

    router.push({ name: detailRouteName.value, params: { identifier: id } });
}

onMounted(async () => {
    const restored = restoreSearchSnapshot();

    await Promise.all([
        loadFilterMeta({ applyDefaults: ! restored }),
        loadUsage(),
        loadFavoriteIds(),
        refreshFavoritesCount(),
        checkServerCache(),
    ]);
});

onUnmounted(() => {
    clearTimeout(cacheCheckTimer);
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

.auction-usage__hint {
    margin: 0.65rem 0 0;
    font-size: 0.78rem;
    color: var(--vs-text-muted);
}

.auction-usage__users {
    margin-top: 0.75rem;
}

.auction-usage__users ul {
    list-style: none;
    margin: 0.4rem 0 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.auction-usage__users li {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem 0.75rem;
    font-size: 0.8rem;
}

.auction-usage__user-name {
    font-weight: 600;
}

.vehicle-card__footer {
    margin-top: 0.65rem;
    display: flex;
    justify-content: flex-end;
}

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
    align-items: end;
}

.year-row .field {
    min-width: 0;
}

.year-select {
    direction: ltr;
}

.auction-filters :deep(.p-select),
.auction-filters :deep(.p-inputtext),
.auction-filters :deep(.p-iconfield .p-inputtext) {
    width: 100%;
    min-height: 2.15rem;
    height: 2.15rem;
    border-radius: 999px;
}

.auction-filters :deep(.p-select.p-select-sm),
.auction-filters :deep(.p-inputtext.p-inputtext-sm) {
    min-height: 2.15rem;
    height: 2.15rem;
    border-radius: 999px;
    padding-block: 0;
}

.auction-filters :deep(.p-select .p-select-label) {
    padding-block: 0.35rem;
    padding-inline: 0.85rem;
    font-size: 0.82rem;
    line-height: 1.35;
    display: flex;
    align-items: center;
}

.auction-filters :deep(.p-select .p-select-dropdown) {
    width: 2rem;
}

.auction-filters :deep(.p-iconfield .p-inputtext) {
    padding-block: 0.35rem;
    padding-inline-start: 2.2rem;
    padding-inline-end: 0.85rem;
    font-size: 0.82rem;
}

.auction-filters :deep(.p-iconfield .p-inputicon) {
    inset-inline-start: 0.75rem;
    font-size: 0.85rem;
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
    cursor: pointer;
}

.slide-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 1.85rem;
    height: 1.85rem;
    border: none;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.72);
    color: #f8fafc;
    display: grid;
    place-items: center;
    cursor: pointer;
    z-index: 2;
}

.slide-btn--prev { left: 0.45rem; right: auto; }
.slide-btn--next { right: 0.45rem; left: auto; }

.slide-dots {
    position: absolute;
    left: 50%;
    bottom: 0.45rem;
    transform: translateX(-50%);
    display: flex;
    gap: 0.28rem;
    z-index: 2;
}

.slide-dot {
    width: 0.42rem;
    height: 0.42rem;
    border-radius: 999px;
    border: none;
    padding: 0;
    background: rgba(248, 250, 252, 0.45);
    cursor: pointer;
}

.slide-dot--active {
    background: #fff;
    width: 0.85rem;
}

.slide-count {
    position: absolute;
    bottom: 0.4rem;
    inset-inline-end: 0.5rem;
    background: rgba(15, 23, 42, 0.75);
    color: #e2e8f0;
    font-size: 0.68rem;
    font-weight: 700;
    border-radius: 999px;
    padding: 0.15rem 0.4rem;
    z-index: 2;
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

.pill--owner {
    background: color-mix(in srgb, #7c3aed 16%, transparent);
    color: #6d28d9;
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

.vehicle-card__footer {
    display: flex;
    justify-content: flex-end;
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
