import { defineStore } from 'pinia';

const STORAGE_KEY = 'auction_search_cache_v2';
const DEFAULT_TTL_MS = 24 * 60 * 60 * 1000;

function filtersKey(filters) {
    const normalized = {
        platform: filters?.platform || '',
        make: filters?.make || '',
        model: filters?.model || '',
        type: filters?.type || '',
        year_from: filters?.year_from ?? '',
        year_to: filters?.year_to ?? '',
        q: String(filters?.q || '').trim(),
        state: filters?.state || '',
        lot_status: filters?.lot_status || 'All',
        lot_sub_status: filters?.lot_sub_status || 'Open',
        per_page: filters?.per_page || 10,
    };

    return JSON.stringify(normalized);
}

function pruneByKey(byKey, ttlMs) {
    const now = Date.now();
    const next = {};

    Object.entries(byKey || {}).forEach(([key, item]) => {
        if (! item || typeof item !== 'object') return;

        const savedAt = Number(item.savedAt || 0);

        if (savedAt && now - savedAt <= ttlMs) {
            next[key] = item;
        }
    });

    return next;
}

function readStored() {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);

        if (! raw) return null;

        const parsed = JSON.parse(raw);

        if (! parsed || typeof parsed !== 'object') return null;

        const ttl = Number(parsed.ttlMs || DEFAULT_TTL_MS);
        const savedAt = Number(parsed.savedAt || 0);

        return {
            ...parsed,
            ttlMs: ttl,
            lastExpired: savedAt ? Date.now() - savedAt > ttl : true,
            byKey: pruneByKey(parsed.byKey, ttl),
        };
    } catch {
        return null;
    }
}

function writeStored(payload) {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
    } catch {
        // quota / private mode
    }
}

export { filtersKey };

export const useAuctionSearchStore = defineStore('auctionSearch', {
    state: () => ({
        filters: null,
        rows: [],
        meta: null,
        resultLayout: 'grid',
        searched: false,
        lastCached: false,
        savedAt: null,
        byKey: {},
    }),
    getters: {
        hasCachedResults: (state) => state.searched && Array.isArray(state.rows) && state.rows.length > 0,
    },
    actions: {
        snapshotFor(filters) {
            const key = filtersKey(filters);
            const item = this.byKey[key];

            if (! item) return null;

            const savedAt = Number(item.savedAt || 0);

            if (! savedAt || Date.now() - savedAt > DEFAULT_TTL_MS) {
                delete this.byKey[key];

                return null;
            }

            return item;
        },
        hydrate() {
            const stored = readStored();

            if (! stored) return false;

            this.byKey = stored.byKey || {};
            this.filters = stored.filters ?? null;
            this.rows = Array.isArray(stored.rows) ? stored.rows : [];
            this.meta = stored.meta ?? null;
            this.resultLayout = stored.resultLayout === 'list' ? 'list' : 'grid';
            this.searched = Boolean(stored.searched);
            this.lastCached = true;
            this.savedAt = stored.savedAt ?? null;

            if (stored.lastExpired) {
                this.rows = [];
                this.meta = null;
                this.searched = false;
            }

            return this.hasCachedResults || this.searched || Object.keys(this.byKey).length > 0;
        },
        saveSnapshot({ filters, rows, meta, resultLayout, searched, lastCached }) {
            this.filters = filters ? { ...filters } : null;
            this.rows = Array.isArray(rows) ? rows : [];
            this.meta = meta ?? null;
            this.resultLayout = resultLayout === 'list' ? 'list' : 'grid';
            this.searched = Boolean(searched);
            this.lastCached = Boolean(lastCached);
            this.savedAt = Date.now();

            if (this.filters) {
                this.byKey = {
                    ...pruneByKey(this.byKey, DEFAULT_TTL_MS),
                    [filtersKey(this.filters)]: {
                        filters: { ...this.filters },
                        rows: this.rows,
                        meta: this.meta,
                        savedAt: this.savedAt,
                    },
                };
            }

            writeStored({
                filters: this.filters,
                rows: this.rows,
                meta: this.meta,
                resultLayout: this.resultLayout,
                searched: this.searched,
                lastCached: this.lastCached,
                savedAt: this.savedAt,
                byKey: this.byKey,
                ttlMs: DEFAULT_TTL_MS,
            });
        },
        clear() {
            this.filters = null;
            this.rows = [];
            this.meta = null;
            this.searched = false;
            this.lastCached = false;
            this.savedAt = null;
            this.byKey = {};

            try {
                sessionStorage.removeItem(STORAGE_KEY);
            } catch {
                // ignore
            }
        },
    },
});
