/**
 * Keep upload toasts/dock free of raw API dumps (manifest JSON, SQL bindings, etc.).
 *
 * @param {unknown} value
 * @param {string} [fallback='']
 * @returns {string}
 */
export function sanitizeUploadUserMessage(value, fallback = '') {
    const safeFallback = typeof fallback === 'string' && fallback.trim() !== ''
        ? fallback.trim()
        : '';

    if (value == null) {
        return safeFallback;
    }

    if (typeof value === 'object') {
        if (looksLikeManifestPayload(value)) {
            return safeFallback;
        }

        if (Array.isArray(value)) {
            const first = value.find((item) => typeof item === 'string' && item.trim() !== '');

            return first ? truncateUserMessage(first.trim()) : safeFallback;
        }

        if (typeof value.message === 'string' && value.message.trim() !== '') {
            return sanitizeUploadUserMessage(value.message, safeFallback);
        }

        return safeFallback;
    }

    const text = String(value).trim();

    if (text === '' || text === '[object Object]') {
        return safeFallback;
    }

    if (looksLikeTechnicalDump(text)) {
        return safeFallback;
    }

    return truncateUserMessage(text);
}

/**
 * @param {unknown} value
 * @returns {boolean}
 */
function looksLikeManifestPayload(value) {
    if (Array.isArray(value)) {
        if (value.length === 0) {
            return false;
        }

        return value.some(isManifestItem);
    }

    if (! value || typeof value !== 'object') {
        return false;
    }

    if (Array.isArray(value.manifest) && value.manifest.some(isManifestItem)) {
        return true;
    }

    return isManifestItem(value);
}

/**
 * @param {unknown} item
 * @returns {boolean}
 */
function isManifestItem(item) {
    if (! item || typeof item !== 'object' || Array.isArray(item)) {
        return false;
    }

    const hasPath = typeof item.path === 'string';
    const hasStatus = typeof item.status === 'string';
    const hasName = typeof item.name === 'string';

    return hasPath && hasStatus && hasName;
}

/**
 * @param {string} text
 * @returns {boolean}
 */
function looksLikeTechnicalDump(text) {
    const trimmed = text.trim();

    if (trimmed.startsWith('[') || trimmed.startsWith('{')) {
        try {
            const parsed = JSON.parse(trimmed);

            if (looksLikeManifestPayload(parsed)) {
                return true;
            }

            if (parsed && typeof parsed === 'object' && ! Array.isArray(parsed)) {
                if (looksLikeManifestPayload(parsed.manifest) || looksLikeManifestPayload(parsed.data)) {
                    return true;
                }
            }
        } catch {
            // Non-JSON junk that still looks like a dump — fall through.
        }

        if (
            /"status"\s*:\s*"(pending|done|failed)"/.test(trimmed)
            && /"path"\s*:/.test(trimmed)
        ) {
            return true;
        }
    }

    if (
        trimmed.includes('image-transfers/')
        && /"status"\s*:\s*"(pending|done|failed)"/.test(trimmed)
    ) {
        return true;
    }

    if (
        /SQLSTATE/i.test(trimmed)
        && (trimmed.includes('image-transfers/') || /"status"\s*:\s*"pending"/.test(trimmed))
    ) {
        return true;
    }

    if (trimmed.length > 280 && (trimmed.match(/\{"/g) || []).length >= 2) {
        return true;
    }

    return false;
}

/**
 * @param {string} text
 * @returns {string}
 */
function truncateUserMessage(text) {
    if (text.length <= 220) {
        return text;
    }

    return `${text.slice(0, 217)}…`;
}
