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
