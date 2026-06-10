const PLACEHOLDER_FRAGMENTS = ['no_photo.png', 'no_photo'];

export const GALLERY_STAGES = [
    { key: 'terminal', label: 'Terminal' },
    { key: 'pickup', label: 'Pickup' },
    { key: 'destination', label: 'Destination' },
];

const STAGE_KEYS = GALLERY_STAGES.map((s) => s.key);

const NAMED_STAGE_LISTS = {
    terminal: ['terminal_images', 'images_terminal', 'terminal_photos'],
    pickup: ['pickup_images', 'images_pickup', 'pickup_photos'],
    destination: ['destination_images', 'images_destination', 'destination_photos', 'delivery_images'],
};

function normalizeUrl(url) {
    if (typeof url !== 'string' || url.trim() === '') {
        return null;
    }

    if (PLACEHOLDER_FRAGMENTS.some((part) => url.includes(part))) {
        return null;
    }

    if (/(?:^|\/)thumbnail[-_]/i.test(url)) {
        return null;
    }

    const storageMatch = url.match(/^(?:https?:\/\/[^/]+)?(\/storage\/.*)$/i);

    if (storageMatch) {
        return storageMatch[1];
    }

    return url;
}

function pushUrl(urls, url, exclude = null) {
    const normalized = normalizeUrl(url);

    if (! normalized || normalized === exclude || urls.includes(normalized)) {
        return;
    }

    urls.push(normalized);
}

function containsUrl(stages, url) {
    return STAGE_KEYS.some((key) => stages[key].includes(url));
}

function normalizeStage(value) {
    if (typeof value !== 'string' || value.trim() === '') {
        return null;
    }

    const normalized = value.trim().toLowerCase();

    if (STAGE_KEYS.includes(normalized)) {
        return normalized;
    }

    if (normalized.includes('pickup')) {
        return 'pickup';
    }

    if (normalized.includes('destination') || normalized.includes('delivery')) {
        return 'destination';
    }

    if (normalized.includes('terminal')) {
        return 'terminal';
    }

    return null;
}

function classifyUrl(url) {
    const lower = url.toLowerCase();

    if (lower.includes('pickup')) {
        return 'pickup';
    }

    if (lower.includes('destination') || lower.includes('delivery') || lower.includes('dropoff')) {
        return 'destination';
    }

    if (lower.includes('/autos/') || lower.includes('terminal')) {
        return 'terminal';
    }

    return null;
}

function collectFromList(urls, list, exclude = null) {
    for (const image of list ?? []) {
        if (typeof image === 'string') {
            pushUrl(urls, image, exclude);
        } else if (image && typeof image === 'object') {
            const stage = normalizeStage(image.stage ?? image.type ?? image.location ?? image.phase);
            const url = image.url ?? image.src ?? image.path;

            if (typeof url === 'string' && url !== '') {
                if (stage) {
                    pushUrl(urls, url, exclude);
                }
            }
        }
    }
}

function emptyStages() {
    return {
        terminal: [],
        pickup: [],
        destination: [],
    };
}

function assignBatchedFlatImages(stages, urls) {
    if (! urls.length) {
        return;
    }

    const batches = new Map();

    for (const url of urls) {
        const match = url.match(/images-(\d{10,13})-/i);
        const key = match ? match[1] : 'other';

        if (! batches.has(key)) {
            batches.set(key, []);
        }

        batches.get(key).push(url);
    }

    const keys = [...batches.keys()].sort((a, b) => {
        if (a === 'other') {
            return 1;
        }

        if (b === 'other') {
            return -1;
        }

        return a.localeCompare(b);
    });

    keys.forEach((key, index) => {
        const stage = STAGE_KEYS[Math.min(index, STAGE_KEYS.length - 1)] ?? 'terminal';

        for (const url of batches.get(key)) {
            pushUrl(stages[stage], url);
        }
    });
}

/** صورة المعاينة فقط (thumbnail من Vinstack — دقة منخفضة) */
export function vehicleThumbnail(vehicle) {
    if (! vehicle) {
        return null;
    }

    return normalizeUrl(vehicle.raw_data?.thumbnail_url);
}

/**
 * صور المعرض مجمّعة حسب المرحلة (Terminal / Pickup / Destination).
 * @returns {{ terminal: string[], pickup: string[], destination: string[] }}
 */
function hasClientPortalGalleryBlocks(raw) {
    if (! raw || typeof raw !== 'object') {
        return false;
    }

    return GALLERY_STAGES.some(({ key }) => {
        const block = raw[key];

        return block && typeof block === 'object' && ! Array.isArray(block) && Array.isArray(block.urls);
    });
}

function stagesFromPrecomputed(precomputed, vehicle) {
    const stages = emptyStages();
    const exclude = vehicleThumbnail(vehicle);

    for (const { key } of GALLERY_STAGES) {
        if (Array.isArray(precomputed[key])) {
            collectFromList(stages[key], precomputed[key], exclude);
        }
    }

    return stages;
}

export function vehicleGalleryByStage(vehicle) {
    if (! vehicle) {
        return emptyStages();
    }

    const raw = vehicle.raw_data ?? {};
    const liveGallery = hasClientPortalGalleryBlocks(raw) || (raw.gallery && typeof raw.gallery === 'object');

    // Live gallery endpoint returns merged images_by_stage — trust it over re-parsing raw_data blocks.
    if (vehicle.gallery_fresh && vehicle.images_by_stage && typeof vehicle.images_by_stage === 'object') {
        return stagesFromPrecomputed(vehicle.images_by_stage, vehicle);
    }

    const precomputed = vehicle.images_by_stage ?? vehicle.raw_data?.images_by_stage;

    if (precomputed && typeof precomputed === 'object' && ! liveGallery) {
        return stagesFromPrecomputed(precomputed, vehicle);
    }

    const thumbnail = vehicleThumbnail(vehicle);
    const stages = emptyStages();
    const skipFlatClassification = hasClientPortalGalleryBlocks(raw);

    for (const { key } of GALLERY_STAGES) {
        for (const field of NAMED_STAGE_LISTS[key]) {
            if (Array.isArray(raw[field])) {
                collectFromList(stages[key], raw[field], thumbnail);
            }
        }

        const stageBlock = raw[key];

        if (stageBlock && typeof stageBlock === 'object' && ! Array.isArray(stageBlock)) {
            collectFromList(stages[key], stageBlock.urls ?? stageBlock, thumbnail);
        }
    }

    const nested = raw.photos ?? raw.gallery;

    if (nested && typeof nested === 'object' && ! Array.isArray(nested)) {
        for (const { key } of GALLERY_STAGES) {
            const block = nested[key];

            if (Array.isArray(block)) {
                collectFromList(stages[key], block, thumbnail);
            } else if (block && typeof block === 'object') {
                collectFromList(stages[key], block.urls ?? block, thumbnail);
            }
        }
    }

    const objectStageUrls = { terminal: [], pickup: [], destination: [], unclassified: [] };

    for (const list of [vehicle.images, raw.images]) {
        for (const image of list ?? []) {
            if (! image || typeof image !== 'object') {
                continue;
            }

            const stage = normalizeStage(image.stage ?? image.type ?? image.location ?? image.phase);
            const url = image.url ?? image.src ?? image.path;

            if (typeof url !== 'string' || url === '') {
                continue;
            }

            const normalized = normalizeUrl(url);

            if (! normalized || normalized === thumbnail || containsUrl(stages, normalized)) {
                continue;
            }

            if (stage) {
                pushUrl(stages[stage], normalized);
            } else {
                objectStageUrls.unclassified.push(normalized);
            }
        }
    }

    const unclassified = [...objectStageUrls.unclassified];

    for (const list of [vehicle.images, raw.images, raw.urls]) {
        for (const image of list ?? []) {
            if (typeof image !== 'string') {
                continue;
            }

            const normalized = normalizeUrl(image);

            if (! normalized || normalized === thumbnail || containsUrl(stages, normalized)) {
                continue;
            }

            if (skipFlatClassification) {
                continue;
            }

            const stage = classifyUrl(normalized);

            if (stage) {
                pushUrl(stages[stage], normalized);
            } else {
                unclassified.push(normalized);
            }
        }
    }

    const remaining = unclassified.filter((url) => ! containsUrl(stages, url));

    assignBatchedFlatImages(stages, remaining);

    return stages;
}

/** كل صور HD للمعرض (بدون thumbnail) */
export function vehicleGalleryImages(vehicle) {
    const stages = vehicleGalleryByStage(vehicle);
    const urls = [];

    for (const { key } of GALLERY_STAGES) {
        for (const url of stages[key]) {
            pushUrl(urls, url);
        }
    }

    return urls;
}

export function vehicleGalleryStageCount(vehicle, stageKey) {
    return vehicleGalleryByStage(vehicle)[stageKey]?.length ?? 0;
}

/** @deprecated استخدم vehicleGalleryImages */
export function vehicleImages(vehicle) {
    return vehicleGalleryImages(vehicle);
}

export function vehicleGalleryCount(vehicle) {
    return vehicleGalleryImages(vehicle).length;
}

export function hasVehiclePreview(vehicle) {
    return Boolean(vehicleThumbnail(vehicle));
}

export function hasVehicleGallery(vehicle) {
    return vehicleGalleryCount(vehicle) > 0;
}

export function vehicleLabel(vehicle) {
    return [vehicle?.year, vehicle?.make, vehicle?.model, vehicle?.vin]
        .filter(Boolean)
        .join(' ');
}

export function vehicleUploadedImages(vehicle) {
    return vehicle?.uploaded_images ?? vehicle?.raw_data?.uploaded_images ?? [];
}
