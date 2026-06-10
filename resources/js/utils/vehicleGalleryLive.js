import api from '../api/client';

/** Gallery API — سيارات Vinstack المستوردة فقط (ليس يدوي). */
export function vehicleUsesLiveGallery(vehicle) {
    return vehicle?.source === 'vinstack';
}

/**
 * @param {number|string} vehicleId
 * @param {'admin'|'dealer'} mode
 */
export async function fetchLiveVehicleGallery(vehicleId, mode = 'admin') {
    const prefix = mode === 'dealer' ? '/dealer/vehicles' : '/admin/vehicles';
    const { data } = await api.get(`${prefix}/${vehicleId}/gallery`);

    return data.data;
}

function clientPortalStageBlock(galleryPayload, stage, staleFallback) {
    const topLevel = galleryPayload?.[stage];

    if (topLevel && typeof topLevel === 'object') {
        return topLevel;
    }

    const nested = galleryPayload?.gallery?.[stage];

    if (nested && typeof nested === 'object') {
        return nested;
    }

    return staleFallback;
}

/**
 * Temporary debug helper — counts images in a live gallery API response.
 * @returns {{
 *   gallery_fresh: boolean,
 *   gallery_error: string|null,
 *   api_raw: { terminal: number, pickup: number, destination: number },
 *   images_by_stage: { terminal: number, pickup: number, destination: number },
 *   flat_images: number,
 *   top_level_urls: number,
 *   total_api_stages: number,
 *   total_merged: number,
 * }}
 */
export function summarizeGalleryApiResponse(payload) {
    const stages = ['terminal', 'pickup', 'destination'];
    const apiRaw = { terminal: 0, pickup: 0, destination: 0 };

    for (const stage of stages) {
        const block = payload?.[stage] ?? payload?.gallery?.[stage];

        if (Array.isArray(block?.urls)) {
            apiRaw[stage] = block.urls.length;
        } else if (Array.isArray(block)) {
            apiRaw[stage] = block.length;
        }
    }

    const byStage = payload?.images_by_stage ?? {};
    const merged = { terminal: 0, pickup: 0, destination: 0 };

    for (const stage of stages) {
        merged[stage] = Array.isArray(byStage[stage]) ? byStage[stage].length : 0;
    }

    const flatImages = Array.isArray(payload?.images) ? payload.images.length : 0;
    const topLevelUrls = Array.isArray(payload?.urls) ? payload.urls.length : 0;

    return {
        gallery_fresh: Boolean(payload?.gallery_fresh),
        gallery_error: payload?.gallery_error ?? null,
        gallery_token_expired: Boolean(payload?.gallery_token_expired),
        api_raw: apiRaw,
        images_by_stage: merged,
        flat_images: flatImages,
        top_level_urls: topLevelUrls,
        total_api_stages: apiRaw.terminal + apiRaw.pickup + apiRaw.destination,
        total_merged: merged.terminal + merged.pickup + merged.destination,
    };
}

export function mergeGalleryIntoVehicle(vehicle, galleryPayload) {
    if (! galleryPayload || ! vehicle) {
        return vehicle;
    }

    const galleryFresh = Boolean(galleryPayload.gallery_fresh);
    const staleRaw = vehicle.raw_data ?? {};
    const imagesByStage = galleryPayload.images_by_stage
        ?? (galleryFresh ? undefined : vehicle.images_by_stage);

    const stageFallback = (stage) => (galleryFresh ? undefined : staleRaw[stage]);

    return {
        ...vehicle,
        images: galleryPayload.images ?? (galleryFresh ? undefined : vehicle.images),
        images_by_stage: imagesByStage,
        uploaded_images: galleryPayload.uploaded_images ?? vehicle.uploaded_images,
        thumbnail_url: galleryPayload.thumbnail_url ?? vehicle.thumbnail_url,
        gallery_fresh: galleryFresh,
        gallery_error: galleryPayload.gallery_error,
        gallery_token_expired: galleryPayload.gallery_token_expired,
        gallery_stored: galleryPayload.gallery_stored,
        gallery_new_images_count: galleryPayload.gallery_new_images_count,
        raw_data: {
            ...staleRaw,
            thumbnail_url: galleryPayload.thumbnail_url ?? staleRaw.thumbnail_url,
            images: galleryPayload.images ?? (galleryFresh ? undefined : staleRaw.images),
            images_by_stage: imagesByStage,
            uploaded_images: galleryPayload.uploaded_images ?? staleRaw.uploaded_images,
            gallery: galleryPayload.gallery ?? (galleryFresh ? undefined : staleRaw.gallery),
            terminal: clientPortalStageBlock(galleryPayload, 'terminal', stageFallback('terminal')),
            pickup: clientPortalStageBlock(galleryPayload, 'pickup', stageFallback('pickup')),
            destination: clientPortalStageBlock(galleryPayload, 'destination', stageFallback('destination')),
        },
    };
}
