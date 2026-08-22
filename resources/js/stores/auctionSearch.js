import { defineStore } from 'pinia';

const STORAGE_KEY = 'auction_search_last_v1';
const DEFAULT_TTL_MS = 60 * 60 * 1000; // match server search cache (1h)

function readStored() {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);

        if (! raw) return null;

        const parsed = JSON.parse(raw);

        if (! parsed || typeof parsed !== 'object') return null;

        const savedAt = Number(parsed.savedAt || 0);
        const ttl = Number(parsed.ttlMs || DEFAULT_TTL_MS);

        if (! savedAt || Date.now() - savedAt > ttl) {
            sessionStorage.removeItem(STORAGE_KEY);

            return null;
        }

        return parsed;
    } catch {
        return null;
    }
}

function writeStored(payload) {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
    } catch {
        // quota / private mode — ignore
    }
}

export const useAuctionSearchStore = defineStore('auctionSearch', {
    state: () => ({
        filters: null,
        rows: [],
        meta: null,
        resultLayout: 'grid',
        searched: false,
        lastCached: false,
        savedAt: null,
    }),
    getters: {
        hasCachedResults: (state) => state.searched && Array.isArray(state.rows) && state.rows.length > 0,
    },
    actions: {
        hydrate() {
            const stored = readStored();

            if (! stored) return false;

            this.filters = stored.filters ?? null;
            this.rows = Array.isArray(stored.rows) ? stored.rows : [];
            this.meta = stored.meta ?? null;
            this.resultLayout = stored.resultLayout === 'list' ? 'list' : 'grid';
            this.searched = Boolean(stored.searched);
            this.lastCached = true;
            this.savedAt = stored.savedAt ?? null;

            return this.hasCachedResults || this.searched;
        },
        saveSnapshot({ filters, rows, meta, resultLayout, searched, lastCached }) {
            this.filters = filters ? { ...filters } : null;
            this.rows = Array.isArray(rows) ? rows : [];
            this.meta = meta ?? null;
            this.resultLayout = resultLayout === 'list' ? 'list' : 'grid';
            this.searched = Boolean(searched);
            this.lastCached = Boolean(lastCached);
            this.savedAt = Date.now();

            writeStored({
                filters: this.filters,
                rows: this.rows,
                meta: this.meta,
                resultLayout: this.resultLayout,
                searched: this.searched,
                lastCached: this.lastCached,
                savedAt: this.savedAt,
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

            try {
                sessionStorage.removeItem(STORAGE_KEY);
            } catch {
                // ignore
            }
        },
    },
});
