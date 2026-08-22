<template>
    <div class="auction-detail admin-page">
        <div class="auction-detail__toolbar">
            <Button
                :label="t('auctions.back')"
                icon="pi pi-arrow-right"
                text
                @click="goBack"
            />
            <div class="auction-detail__actions">
                <Button
                    v-if="vehicle"
                    :label="isFavorite ? t('auctions.removeFavorite') : t('auctions.addFavorite')"
                    :icon="isFavorite ? 'pi pi-heart-fill' : 'pi pi-heart'"
                    :severity="isFavorite ? 'danger' : 'secondary'"
                    outlined
                    size="small"
                    :loading="favoriteBusy"
                    @click="toggleFavorite"
                />
                <Button
                    :label="t('actions.refresh')"
                    icon="pi pi-refresh"
                    outlined
                    size="small"
                    :loading="loading"
                    @click="load(true)"
                />
            </div>
        </div>

        <div v-if="loading && ! vehicle" class="auction-detail__state">
            <ProgressSpinner style="width: 32px; height: 32px" />
            <span>{{ t('auctions.loadingDetail') }}</span>
        </div>

        <p v-else-if="error" class="auction-detail__error">{{ error }}</p>

        <template v-else-if="vehicle">
            <header class="auction-detail__hero admin-surface">
                <div>
                    <Tag :value="platformLabel(vehicle.platform)" :severity="platformSeverity(vehicle.platform)" />
                    <h1>{{ vehicle.title || `${vehicle.year || ''} ${vehicle.make || ''} ${vehicle.model || ''}`.trim() }}</h1>
                    <p class="muted">
                        <span dir="ltr">VIN: {{ vehicle.vin || '—' }}</span>
                        ·
                        <span dir="ltr">Lot: {{ vehicle.lot_number || '—' }}</span>
                    </p>
                </div>
                <div class="auction-detail__prices">
                    <div>
                        <span>{{ t('auctions.col.currentBid') }}</span>
                        <strong>{{ money(vehicle.pricing?.current_bid_usd) }}</strong>
                    </div>
                    <div>
                        <span>{{ t('auctions.col.buyNow') }}</span>
                        <strong>{{ money(vehicle.pricing?.buy_now_usd) }}</strong>
                    </div>
                </div>
            </header>

            <section class="auction-detail__gallery admin-surface">
                <div v-if="photos.length" class="gallery">
                    <button
                        type="button"
                        class="gallery__main"
                        @click="activePhoto = activePhoto"
                    >
                        <img :src="photos[activePhoto]?.large || photos[activePhoto]?.full || photos[activePhoto]?.thumb" alt="" />
                    </button>
                    <div class="gallery__thumbs">
                        <button
                            v-for="(photo, idx) in photos"
                            :key="idx"
                            type="button"
                            class="gallery__thumb"
                            :class="{ 'gallery__thumb--active': idx === activePhoto }"
                            @click="activePhoto = idx"
                        >
                            <img :src="photo.thumb || photo.full" alt="" loading="lazy" />
                        </button>
                    </div>
                </div>
                <p v-else class="muted">{{ t('auctions.noPhotos') }}</p>
            </section>

            <section class="auction-detail__grid">
                <article class="admin-surface detail-card">
                    <h3>{{ t('auctions.section.auction') }}</h3>
                    <dl>
                        <div><dt>{{ t('auctions.col.auctionDate') }}</dt><dd>{{ auctionDate(vehicle) }}</dd></div>
                        <div><dt>{{ t('auctions.status') }}</dt><dd>{{ vehicle.auction?.state || vehicle.auction?.lot_status || '—' }}</dd></div>
                        <div><dt>{{ t('auctions.col.location') }}</dt><dd>{{ vehicle.location?.display || '—' }}</dd></div>
                        <div><dt>{{ t('auctions.titleDoc') }}</dt><dd>{{ vehicle.sale_document?.name || '—' }}</dd></div>
                    </dl>
                </article>

                <article class="admin-surface detail-card">
                    <h3>{{ t('auctions.section.condition') }}</h3>
                    <dl>
                        <div><dt>{{ t('auctions.col.damage') }}</dt><dd>{{ vehicle.condition?.primary_damage || '—' }}</dd></div>
                        <div><dt>{{ t('auctions.secondaryDamage') }}</dt><dd>{{ vehicle.condition?.secondary_damage || '—' }}</dd></div>
                        <div><dt>{{ t('auctions.odometer') }}</dt><dd>{{ odometer(vehicle) }}</dd></div>
                        <div><dt>{{ t('auctions.runCondition') }}</dt><dd>{{ vehicle.condition?.run_condition?.label || vehicle.condition?.run_condition?.value || '—' }}</dd></div>
                    </dl>
                </article>

                <article class="admin-surface detail-card">
                    <h3>{{ t('auctions.section.specs') }}</h3>
                    <dl>
                        <div><dt>{{ t('auctions.engine') }}</dt><dd>{{ vehicle.vehicle_specs?.engine?.raw || '—' }}</dd></div>
                        <div><dt>{{ t('auctions.transmission') }}</dt><dd>{{ vehicle.vehicle_specs?.transmission || '—' }}</dd></div>
                        <div><dt>{{ t('auctions.fuel') }}</dt><dd>{{ vehicle.vehicle_specs?.fuel_type || '—' }}</dd></div>
                        <div><dt>{{ t('auctions.drive') }}</dt><dd>{{ vehicle.vehicle_specs?.drive_type || '—' }}</dd></div>
                    </dl>
                </article>
            </section>

            <section class="admin-surface detail-card">
                <div class="history-head">
                    <h3>{{ t('auctions.related') }}</h3>
                    <Button
                        :label="relatedLoaded ? t('auctions.reloadRelated') : t('auctions.loadRelated')"
                        icon="pi pi-car"
                        size="small"
                        outlined
                        :loading="relatedLoading"
                        @click="loadRelated"
                    />
                </div>
                <p v-if="relatedError" class="auction-detail__error">{{ relatedError }}</p>
                <p v-else-if="! relatedLoaded" class="muted">{{ t('auctions.relatedPrompt') }}</p>
                <p v-else-if="! relatedRows.length" class="muted">{{ t('auctions.relatedEmpty') }}</p>
                <div v-else class="related-grid">
                    <AuctionRelatedCard
                        v-for="row in relatedRows"
                        :key="relatedKey(row)"
                        :vehicle="row"
                        @open="openRelated"
                    />
                </div>
            </section>

            <section class="admin-surface detail-card">
                <div class="history-head">
                    <h3>{{ t('auctions.history') }}</h3>
                    <Button
                        :label="t('auctions.loadHistory')"
                        icon="pi pi-history"
                        size="small"
                        outlined
                        :loading="historyLoading"
                        @click="loadHistory"
                    />
                </div>
                <p v-if="historyError" class="auction-detail__error">{{ historyError }}</p>
                <p v-else-if="! historyLoaded" class="muted">{{ t('auctions.historyPrompt') }}</p>
                <p v-else-if="! historyRows.length" class="muted">{{ t('auctions.historyEmpty') }}</p>
                <ul v-else class="history-list">
                    <li v-for="(item, idx) in historyRows" :key="idx">
                        <strong>{{ item.platform || item.source || '—' }}</strong>
                        <span dir="ltr">{{ item.lot_number || item.lot || '—' }}</span>
                        <span>{{ item.auction_at || item.sold_at || item.date || '—' }}</span>
                        <span class="money">{{ money(item.sale_price_usd ?? item.price_usd ?? item.price) }}</span>
                    </li>
                </ul>
            </section>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import Tag from 'primevue/tag';
import AuctionRelatedCard from '../../components/auctions/AuctionRelatedCard.vue';
import {
    addAuctionFavorite,
    getAuction,
    getAuctionHistory,
    getAuctionRelated,
    listAuctionFavoriteIds,
    recordAuctionSpotlight,
    removeAuctionFavorite,
} from '../../api/auctions';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const vehicle = ref(null);
const loading = ref(true);
const error = ref('');
const activePhoto = ref(0);
const favoriteIds = ref([]);
const favoriteBusy = ref(false);

const historyRows = ref([]);
const historyLoading = ref(false);
const historyLoaded = ref(false);
const historyError = ref('');

const relatedRows = ref([]);
const relatedLoading = ref(false);
const relatedLoaded = ref(false);
const relatedError = ref('');

const photos = computed(() => {
    const items = vehicle.value?.media?.items;

    if (Array.isArray(items) && items.length) {
        return items.filter((item) => item.type === 'image' || item.thumb || item.full);
    }

    const thumbs = vehicle.value?.media?.thumbs;

    if (Array.isArray(thumbs)) {
        return thumbs.map((url) => ({ thumb: url, full: url, large: url }));
    }

    return [];
});

const listRouteName = computed(() => (
    route.path.startsWith('/dealer') ? 'dealer.auctions' : 'admin.auctions'
));

const favoriteKey = computed(() => {
    const row = vehicle.value;

    if (! row) return String(route.params.identifier || '');

    return row.vin || row.lot_number || row.slug_vin || String(route.params.identifier || '');
});

const isFavorite = computed(() => {
    const key = favoriteKey.value;

    return key !== '' && favoriteIds.value.includes(key);
});

async function loadFavoriteIds() {
    try {
        const { data } = await listAuctionFavoriteIds();
        favoriteIds.value = Array.isArray(data.data) ? data.data : [];
    } catch {
        // optional
    }
}

async function load(forceRefresh = false) {
    loading.value = true;
    error.value = '';
    relatedRows.value = [];
    relatedLoaded.value = false;
    relatedError.value = '';
    historyRows.value = [];
    historyLoaded.value = false;
    historyError.value = '';

    try {
        const [{ data }] = await Promise.all([
            getAuction(String(route.params.identifier), forceRefresh ? { force_refresh: true } : {}),
            loadFavoriteIds(),
        ]);
        vehicle.value = data.data ?? null;
        activePhoto.value = 0;
        if (vehicle.value) {
            recordSpotlightView(vehicle.value);
        }
    } catch (e) {
        error.value = e.response?.data?.message || t('auctions.loadFailed');
        vehicle.value = null;
    } finally {
        loading.value = false;
    }
}

async function recordSpotlightView(row) {
    try {
        const thumbs = [];
        if (Array.isArray(row.media?.thumbs)) {
            thumbs.push(...row.media.thumbs);
        }
        if (Array.isArray(row.media?.items)) {
            for (const item of row.media.items) {
                const url = item?.thumb || item?.large || item?.full;
                if (url) thumbs.push(url);
            }
        }

        await recordAuctionSpotlight({
            identifier: row.vin || row.lot_number || row.slug_vin,
            slug_vin: row.slug_vin,
            vin: row.vin,
            lot_number: row.lot_number,
            platform: row.platform,
            title: row.title,
            year: row.year,
            make: row.make,
            model: row.model,
            pricing: row.pricing,
            location: row.location,
            condition: row.condition,
            media: row.media,
            auction: row.auction,
            ad: row.ad,
            thumb_urls: thumbs.slice(0, 8),
            current_bid_usd: row.pricing?.current_bid_usd,
            location_display: row.location?.display,
            primary_damage: row.condition?.primary_damage,
        });
    } catch {
        // spotlight is optional — never block detail view
    }
}

async function toggleFavorite() {
    const row = vehicle.value;
    const key = favoriteKey.value;

    if (! row || ! key) return;

    favoriteBusy.value = true;

    try {
        if (isFavorite.value) {
            await removeAuctionFavorite(key);
            favoriteIds.value = favoriteIds.value.filter((id) => id !== key);
        } else {
            await addAuctionFavorite({
                identifier: key,
                slug_vin: row.slug_vin || key,
                vin: row.vin,
                lot_number: row.lot_number,
                platform: row.platform,
                title: row.title,
                year: row.year,
                make: row.make,
                model: row.model,
                pricing: row.pricing,
                location: row.location,
                condition: row.condition,
                media: row.media,
                auction: row.auction,
                ad: row.ad,
                thumb_url: photos.value[0]?.thumb || photos.value[0]?.full || null,
                current_bid_usd: row.pricing?.current_bid_usd,
                buy_now_usd: row.pricing?.buy_now_usd,
                location_display: row.location?.display,
                primary_damage: row.condition?.primary_damage,
                auction_at: auctionDate(row) === '—' ? null : auctionDate(row),
            });
            if (! favoriteIds.value.includes(key)) {
                favoriteIds.value = [...favoriteIds.value, key];
            }
        }
    } catch (e) {
        error.value = e.response?.data?.message || t('auctions.favoriteFailed');
    } finally {
        favoriteBusy.value = false;
    }
}

async function loadHistory() {
    historyLoading.value = true;
    historyError.value = '';

    try {
        const { data } = await getAuctionHistory(String(route.params.identifier), { per_page: 20 });
        historyRows.value = Array.isArray(data.data) ? data.data : [];
        historyLoaded.value = true;
    } catch (e) {
        historyError.value = e.response?.data?.message || t('auctions.loadFailed');
    } finally {
        historyLoading.value = false;
    }
}

async function loadRelated() {
    relatedLoading.value = true;
    relatedError.value = '';

    try {
        const id = vehicle.value?.vin
            || vehicle.value?.lot_number
            || String(route.params.identifier);

        const { data } = await getAuctionRelated(id, { per_page: 10 });
        const payload = data.data ?? {};
        relatedRows.value = Array.isArray(payload.items)
            ? payload.items
            : [
                ...(Array.isArray(payload.upcoming) ? payload.upcoming.map((row) => ({ ...row, _related_group: 'upcoming' })) : []),
                ...(Array.isArray(payload.past) ? payload.past.map((row) => ({ ...row, _related_group: 'past' })) : []),
            ];
        relatedLoaded.value = true;
    } catch (e) {
        relatedError.value = e.response?.data?.message || t('auctions.loadFailed');
    } finally {
        relatedLoading.value = false;
    }
}

function relatedKey(row) {
    return row.vin || row.slug_vin || row.lot_number || `${row.platform}-${row.title}`;
}

function openRelated(row) {
    const id = row?.vin || row?.lot_number || row?.slug_vin;

    if (! id) return;

    const name = route.path.startsWith('/dealer') ? 'dealer.auctionDetail' : 'admin.auctionDetail';
    router.push({ name, params: { identifier: id } });
}

function goBack() {
    router.push({ name: listRouteName.value });
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
    return row.auction?.formatted || row.auction?.auction_at || row.ad || '—';
}

function odometer(row) {
    if (row.odometer?.mi != null) return `${Number(row.odometer.mi).toLocaleString()} mi`;
    if (typeof row.odometer === 'number') return `${row.odometer.toLocaleString()} mi`;

    return '—';
}

function platformLabel(platform) {
    return platform ? String(platform).toUpperCase() : '—';
}

function platformSeverity(platform) {
    return platform === 'iaai' ? 'info' : 'warn';
}

watch(() => route.params.identifier, () => {
    load();
});

onMounted(load);
</script>

<style scoped>
.auction-detail {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.auction-detail__toolbar {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
}

.auction-detail__actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.auction-detail__state,
.auction-detail__error {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    min-height: 8rem;
    color: var(--vs-text-muted);
}

.auction-detail__error {
    color: #b91c1c;
    min-height: auto;
    justify-content: flex-start;
}

.auction-detail__hero {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.15rem;
    flex-wrap: wrap;
}

.auction-detail__hero h1 {
    margin: 0.45rem 0 0.2rem;
    font-size: 1.25rem;
}

.muted { color: var(--vs-text-muted); font-size: 0.85rem; }

.auction-detail__prices {
    display: flex;
    gap: 1rem;
}

.auction-detail__prices > div {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    min-width: 110px;
}

.auction-detail__prices span {
    font-size: 0.75rem;
    color: var(--vs-text-muted);
}

.auction-detail__prices strong {
    font-size: 1.25rem;
    color: #16a34a;
}

.auction-detail__gallery {
    padding: 1rem;
}

.gallery__main {
    display: block;
    width: 100%;
    border: 0;
    padding: 0;
    background: #0f172a;
    border-radius: 12px;
    overflow: hidden;
    cursor: default;
}

.gallery__main img {
    width: 100%;
    max-height: 420px;
    object-fit: contain;
    display: block;
}

.gallery__thumbs {
    display: flex;
    gap: 0.45rem;
    overflow-x: auto;
    margin-top: 0.75rem;
}

.gallery__thumb {
    border: 2px solid transparent;
    padding: 0;
    border-radius: 8px;
    overflow: hidden;
    background: #111;
    cursor: pointer;
}

.gallery__thumb--active {
    border-color: var(--admin-accent, #4a3558);
}

.gallery__thumb img {
    width: 72px;
    height: 54px;
    object-fit: cover;
    display: block;
}

.auction-detail__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 0.85rem;
}

.detail-card {
    padding: 1rem 1.1rem;
}

.detail-card h3 {
    margin: 0 0 0.75rem;
    font-size: 0.95rem;
}

.detail-card dl {
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

.detail-card dl > div {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    font-size: 0.86rem;
}

.detail-card dt {
    color: var(--vs-text-muted);
}

.detail-card dd {
    margin: 0;
    font-weight: 600;
    text-align: end;
}

.history-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 0.75rem;
}

.history-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.history-list li {
    display: grid;
    grid-template-columns: 1fr 1fr 1.2fr auto;
    gap: 0.5rem;
    font-size: 0.84rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--admin-border, var(--vs-border));
}

.money {
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

@media (max-width: 640px) {
    .history-list li {
        grid-template-columns: 1fr 1fr;
    }
}
</style>
