const NAMED_KEYS = ['name', 'label', 'title', 'value', 'text', 'port', 'city'];

const GALLERY_BLOCK_KEYS = new Set(['urls', 'keys', 'images', 'photos', 'gallery']);

const URL_LIKE_PATTERN = /(?:^https?:\/\/|\/autos\/|\.(?:jpe?g|png|gif|webp|bmp|svg)(?:\?|$|[#&]))/i;

function isUnsafeDisplayString(value) {
    if (typeof value !== 'string') {
        return true;
    }

    const trimmed = value.trim();

    if (! trimmed) {
        return true;
    }

    if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
        return true;
    }

    return URL_LIKE_PATTERN.test(trimmed);
}

function sanitizeDisplayString(value) {
    if (value === null || value === undefined) {
        return null;
    }

    const trimmed = String(value).trim();

    if (isUnsafeDisplayString(trimmed)) {
        return null;
    }

    return trimmed;
}

function isGalleryBlock(value) {
    return Boolean(
        value
        && typeof value === 'object'
        && ! Array.isArray(value)
        && Array.isArray(value.urls),
    );
}

/**
 * Coerce Vinstack/Nujoom raw_data values (string, number, object, or array) to a display string.
 * Mirrors App\Services\ContainerService::scalarString(), but rejects URLs, JSON, and gallery blocks.
 */
export function scalarString(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (typeof value === 'boolean') {
        return value ? '1' : '0';
    }

    if (typeof value === 'number') {
        const trimmed = String(value).trim();

        return trimmed !== '' ? trimmed : null;
    }

    if (typeof value === 'string') {
        return sanitizeDisplayString(value);
    }

    if (typeof value !== 'object') {
        return null;
    }

    if (Array.isArray(value) && value.length === 0) {
        return null;
    }

    if (! Array.isArray(value) && Object.keys(value).length === 0) {
        return null;
    }

    if (isGalleryBlock(value)) {
        for (const key of NAMED_KEYS) {
            if (Object.prototype.hasOwnProperty.call(value, key)) {
                const nested = scalarString(value[key]);

                if (nested !== null) {
                    return nested;
                }
            }
        }

        return null;
    }

    for (const key of NAMED_KEYS) {
        if (Object.prototype.hasOwnProperty.call(value, key)) {
            const nested = scalarString(value[key]);

            if (nested !== null) {
                return nested;
            }
        }
    }

    const parts = [];

    for (const [key, item] of Object.entries(value)) {
        if (GALLERY_BLOCK_KEYS.has(key)) {
            continue;
        }

        if (item !== null && item !== undefined && (typeof item === 'string' || typeof item === 'number' || typeof item === 'boolean')) {
            const part = sanitizeDisplayString(String(item));

            if (part !== null) {
                parts.push(part);
            }
        } else if (item && typeof item === 'object') {
            const nested = scalarString(item);

            if (nested !== null) {
                parts.push(nested);
            }
        }
    }

    if (parts.length > 0) {
        return parts.join(', ');
    }

    return null;
}
