import api from '../api/client';

/**
 * @param {number|string} vehicleId
 * @param {'admin'|'dealer'} mode
 */
export async function fetchLiveVehicleGallery(vehicleId, mode = 'admin') {
    const prefix = mode === 'dealer' ? '/dealer/vehicles' : '/admin/vehicles';
    const { data } = await api.get(`${prefix}/${vehicleId}/gallery`);

    return data.data;
}

export function mergeGalleryIntoVehicle(vehicle, galleryPayload) {
    if (! galleryPayload || ! vehicle) {
        return vehicle;
    }

    return {
        ...vehicle,
        images: galleryPayload.images ?? vehicle.images,
        images_by_stage: galleryPayload.images_by_stage ?? vehicle.images_by_stage,
        uploaded_images: galleryPayload.uploaded_images ?? vehicle.uploaded_images,
        thumbnail_url: galleryPayload.thumbnail_url ?? vehicle.thumbnail_url,
        gallery_fresh: galleryPayload.gallery_fresh,
        gallery_error: galleryPayload.gallery_error,
        gallery_token_expired: galleryPayload.gallery_token_expired,
        gallery_stored: galleryPayload.gallery_stored,
        gallery_new_images_count: galleryPayload.gallery_new_images_count,
        raw_data: {
            ...(vehicle.raw_data ?? {}),
            thumbnail_url: galleryPayload.thumbnail_url ?? vehicle.raw_data?.thumbnail_url,
            images: galleryPayload.images ?? vehicle.raw_data?.images,
            images_by_stage: galleryPayload.images_by_stage ?? vehicle.raw_data?.images_by_stage,
            uploaded_images: galleryPayload.uploaded_images ?? vehicle.raw_data?.uploaded_images,
        },
    };
}
