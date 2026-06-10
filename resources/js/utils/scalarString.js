const NAMED_KEYS = ['name', 'label', 'title', 'value', 'text', 'port', 'city'];

/**
 * Coerce Vinstack/Nujoom raw_data values (string, number, object, or array) to a display string.
 * Mirrors App\Services\ContainerService::scalarString().
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
        const trimmed = value.trim();

        return trimmed !== '' ? trimmed : null;
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

    for (const key of NAMED_KEYS) {
        if (Object.prototype.hasOwnProperty.call(value, key)) {
            const nested = scalarString(value[key]);

            if (nested !== null) {
                return nested;
            }
        }
    }

    const parts = [];

    for (const item of Object.values(value)) {
        if (item !== null && item !== undefined && (typeof item === 'string' || typeof item === 'number' || typeof item === 'boolean')) {
            const part = String(item).trim();

            if (part !== '') {
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

    const encoded = JSON.stringify(value);

    return encoded !== '[]' && encoded !== '{}' ? encoded : null;
}
