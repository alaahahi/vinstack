import api from './client';

/**
 * @param {Record<string, unknown>} params
 */
export function searchAuctions(params = {}) {
    return api.get('/auctions', { params });
}

/**
 * @param {Record<string, unknown>} params
 */
export function getAuctionCacheStatus(params = {}) {
    return api.get('/auctions/cache', { params });
}

/**
 * @param {Record<string, unknown>} params
 */
export function getAuctionFilters(params = {}) {
    return api.get('/auctions/filters', { params });
}

export function testAuctions() {
    return api.get('/auctions/test');
}

/**
 * @param {Record<string, unknown>} params
 */
export function getAuctionUsage(params = {}) {
    return api.get('/auctions/usage', { params });
}

export function listAuctionFavorites() {
    return api.get('/auctions/favorites');
}

export function listAuctionFavoriteIds() {
    return api.get('/auctions/favorites/ids');
}

/**
 * @param {Record<string, unknown>} vehicle
 */
export function addAuctionFavorite(vehicle) {
    return api.post('/auctions/favorites', vehicle);
}

/**
 * @param {string} identifier
 * @param {{ user_id?: number|string }} [params]
 */
export function removeAuctionFavorite(identifier, params = {}) {
    return api.delete(`/auctions/favorites/${encodeURIComponent(identifier)}`, { params });
}

/**
 * @param {string} identifier
 * @param {Record<string, unknown>} params
 */
export function getAuction(identifier, params = {}) {
    return api.get(`/auctions/${encodeURIComponent(identifier)}`, { params });
}

/**
 * @param {string} identifier
 * @param {Record<string, unknown>} params
 */
export function getAuctionHistory(identifier, params = {}) {
    return api.get(`/auctions/${encodeURIComponent(identifier)}/history`, { params });
}

/**
 * @param {string} identifier
 * @param {Record<string, unknown>} params
 */
export function getAuctionRelated(identifier, params = {}) {
    return api.get(`/auctions/${encodeURIComponent(identifier)}/related`, { params });
}

export function listAuctionSpotlight() {
    return api.get('/auctions/spotlight');
}

/**
 * @param {Record<string, unknown>} vehicle
 */
export function recordAuctionSpotlight(vehicle) {
    return api.post('/auctions/spotlight', vehicle);
}

/**
 * @param {{ enabled: boolean }} payload
 */
export function updateAuctionSpotlightSettings(payload) {
    return api.put('/auctions/spotlight/settings', payload);
}

export function clearAuctionSpotlight() {
    return api.delete('/auctions/spotlight');
}

/**
 * @param {string} identifier
 */
export function removeAuctionSpotlightItem(identifier) {
    return api.delete(`/auctions/spotlight/${encodeURIComponent(identifier)}`);
}
