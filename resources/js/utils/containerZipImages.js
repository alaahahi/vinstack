import JSZip from 'jszip';

const STORAGE_PREFIX = 'container-zip-images:';
const IMAGE_EXTENSIONS = new Set(['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp']);

/** @type {Map<string, { images: Array<{ name: string, url: string, vin: string|null, lot: string|null }>, byVin: Record<string, string[]>, unmatched: string[] }>} */
const memoryStore = new Map();

function storageKey(containerKey) {
    return `${STORAGE_PREFIX}${containerKey}`;
}

function normalizeContainerKey(value) {
    if (! value) {
        return '';
    }

    return String(value).trim().toUpperCase().replace(/\s+/g, '');
}

function basename(path) {
    const parts = String(path).split(/[/\\]/);

    return parts[parts.length - 1] ?? path;
}

function fileExtension(name) {
    const match = basename(name).match(/\.([a-z0-9]+)$/i);

    return match ? match[1].toLowerCase() : '';
}

function isImageEntry(name) {
    const ext = fileExtension(name);

    return IMAGE_EXTENSIONS.has(ext);
}

function normalizeVin(value) {
    if (! value) {
        return '';
    }

    return String(value).trim().toUpperCase();
}

function normalizeLot(value) {
    if (! value) {
        return '';
    }

    return String(value).trim().replace(/^#/, '');
}

/**
 * Match ZIP image filename to a vehicle.
 * Priority: full VIN → VIN suffix (last 6+) → lot number → sequential index.
 */
export function matchImageToVehicle(filename, vehicles, sequentialIndex = null) {
    const base = basename(filename).replace(/\.[^.]+$/, '');
    const upper = base.toUpperCase();

    for (const vehicle of vehicles) {
        const vin = normalizeVin(vehicle?.vin);

        if (vin && (upper.includes(vin) || vin.includes(upper))) {
            return { vin, lot: normalizeLot(vehicle?.lot ?? vehicle?.raw_data?.lot) };
        }
    }

    for (const vehicle of vehicles) {
        const vin = normalizeVin(vehicle?.vin);

        if (vin.length >= 6) {
            const suffix = vin.slice(-6);

            if (upper.includes(suffix)) {
                return { vin, lot: normalizeLot(vehicle?.lot ?? vehicle?.raw_data?.lot) };
            }
        }
    }

    for (const vehicle of vehicles) {
        const lot = normalizeLot(vehicle?.lot ?? vehicle?.raw_data?.lot);

        if (lot && (upper.includes(lot) || new RegExp(`\\b${lot}\\b`).test(base))) {
            return { vin: normalizeVin(vehicle?.vin), lot };
        }
    }

    if (sequentialIndex !== null && vehicles[sequentialIndex]) {
        const vehicle = vehicles[sequentialIndex];

        return {
            vin: normalizeVin(vehicle?.vin),
            lot: normalizeLot(vehicle?.lot ?? vehicle?.raw_data?.lot),
        };
    }

    return { vin: null, lot: null };
}

/**
 * @param {File} file
 * @param {Array<object>} vehicles
 * @returns {Promise<{ images: Array, byVin: Record<string, string[]>, unmatched: string[] }>}
 */
export async function extractZipImagesForContainer(file, vehicles = []) {
    const zip = await JSZip.loadAsync(file);
    const entries = [];

    zip.forEach((relativePath, entry) => {
        if (! entry.dir && isImageEntry(relativePath)) {
            entries.push({ path: relativePath, entry });
        }
    });

    entries.sort((a, b) => a.path.localeCompare(b.path, undefined, { numeric: true }));

    const images = [];
    const byVin = {};
    const unmatched = [];
    let seq = 0;

    for (const { path, entry } of entries) {
        const blob = await entry.async('blob');
        const url = URL.createObjectURL(blob);
        const name = basename(path);
        const match = matchImageToVehicle(name, vehicles, vehicles.length ? seq : null);
        seq += 1;

        const record = { name, url, vin: match.vin, lot: match.lot, file: blob };
        images.push(record);

        if (match.vin) {
            if (! byVin[match.vin]) {
                byVin[match.vin] = [];
            }

            byVin[match.vin].push(url);
        } else {
            unmatched.push(url);
        }
    }

    return { images, byVin, unmatched };
}

export function getContainerZipImages(containerKey) {
    const key = normalizeContainerKey(containerKey);

    return memoryStore.get(key) ?? null;
}

export function saveContainerZipImages(containerKey, payload) {
    const key = normalizeContainerKey(containerKey);

    if (! key) {
        return;
    }

    const existing = memoryStore.get(key);

    if (existing) {
        for (const image of existing.images ?? []) {
            if (image.url?.startsWith('blob:')) {
                URL.revokeObjectURL(image.url);
            }
        }
    }

    memoryStore.set(key, payload);

    try {
        sessionStorage.setItem(storageKey(key), JSON.stringify({
            count: payload.images?.length ?? 0,
            matched: Object.keys(payload.byVin ?? {}).length,
            unmatched: payload.unmatched?.length ?? 0,
            uploadedAt: new Date().toISOString(),
            storage: payload.meta?.storage ?? payload.storage ?? 'local',
        }));
    } catch {
        // sessionStorage may be unavailable or full
    }
}

/** Apply Cloudinary payload from API (no blob URLs). */
export function applyCloudinaryContainerPayload(containerKey, payload) {
    if (! payload) {
        return null;
    }

    saveContainerZipImages(containerKey, {
        images: payload.images ?? [],
        byVin: payload.byVin ?? {},
        unmatched: payload.unmatched ?? [],
        meta: payload.meta ?? { storage: 'cloudinary' },
        storage: 'cloudinary',
    });

    return getContainerZipImages(containerKey);
}

export function clearContainerZipImages(containerKey) {
    const key = normalizeContainerKey(containerKey);
    const existing = memoryStore.get(key);

    if (existing) {
        for (const image of existing.images ?? []) {
            if (image.url?.startsWith('blob:')) {
                URL.revokeObjectURL(image.url);
            }
        }
    }

    memoryStore.delete(key);

    try {
        sessionStorage.removeItem(storageKey(key));
    } catch {
        // ignore
    }
}

export function containerZipMeta(containerKey) {
    const key = normalizeContainerKey(containerKey);

    try {
        const raw = sessionStorage.getItem(storageKey(key));

        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

/**
 * Merge client-side ZIP images into a vehicle object for gallery display.
 */
export function mergeZipImagesIntoVehicle(vehicle, zipPayload) {
    if (! vehicle || ! zipPayload) {
        return vehicle;
    }

    const vin = normalizeVin(vehicle?.vin);
    const zipUrls = vin ? (zipPayload.byVin?.[vin] ?? []) : [];

    if (! zipUrls.length) {
        return vehicle;
    }

    const zipUploaded = zipUrls.map((url, index) => {
        const record = zipPayload.images?.find((image) => image.url === url);

        return {
            id: record?.id ?? `zip-${vin}-${index}`,
            stage: 'destination',
            url,
            original_name: record?.name ?? `zip-${index + 1}`,
            source: record?.source
                ?? (zipPayload.storage === 'cloudinary' || zipPayload.meta?.storage === 'cloudinary' ? 'cloudinary' : 'zip'),
        };
    });

    const existing = vehicle.uploaded_images ?? vehicle?.raw_data?.uploaded_images ?? [];
    const mergedUploaded = [...existing, ...zipUploaded];

    const rawData = {
        ...(vehicle.raw_data ?? {}),
        uploaded_images: mergedUploaded,
    };

    const destinationStage = [
        ...(vehicle.images_by_stage?.destination ?? rawData.images_by_stage?.destination ?? []),
        ...zipUrls,
    ];

    return {
        ...vehicle,
        uploaded_images: mergedUploaded,
        images_by_stage: {
            terminal: vehicle.images_by_stage?.terminal ?? rawData.images_by_stage?.terminal ?? [],
            pickup: vehicle.images_by_stage?.pickup ?? rawData.images_by_stage?.pickup ?? [],
            destination: destinationStage,
        },
        images: [...(vehicle.images ?? []), ...zipUrls],
        raw_data: rawData,
    };
}

export function containerGalleryUrls(zipPayload) {
    if (! zipPayload?.images?.length) {
        return [];
    }

    return zipPayload.images.map((image) => image.url);
}

/** Count ZIP images matched to a vehicle VIN (excludes server thumbnails). */
export function vehicleZipImageCount(vehicle, zipPayload) {
    if (! vehicle || ! zipPayload?.byVin) {
        return 0;
    }

    const vin = normalizeVin(vehicle?.vin);

    return vin ? (zipPayload.byVin[vin]?.length ?? 0) : 0;
}

export function containerRefKey(container) {
    return normalizeContainerKey(
        container?.container_number
        ?? container?.booking_number
        ?? '',
    );
}
