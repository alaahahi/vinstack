import api from '../api/client';

export async function uploadVehicleImages(vehicleId, stage, files) {
    const form = new FormData();
    form.append('stage', stage);

    for (const file of files) {
        form.append('images[]', file);
    }

    const { data } = await api.post(`/admin/vehicles/${vehicleId}/images`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });

    return data;
}

/**
 * @param {number|string} vehicleId
 * @param {string} stage
 * @param {File} file
 * @param {(percent: number) => void} [onProgress]
 */
export async function uploadSingleVehicleImage(vehicleId, stage, file, onProgress) {
    const form = new FormData();
    form.append('stage', stage);
    form.append('images[]', file);

    const { data } = await api.post(`/admin/vehicles/${vehicleId}/images`, form, {
        onUploadProgress: (event) => {
            if (onProgress && event.total) {
                onProgress(Math.round((event.loaded * 100) / event.total));
            }
        },
    });

    return data;
}

export async function deleteVehicleImage(vehicleId, imageId) {
    const { data } = await api.delete(`/admin/vehicles/${vehicleId}/images/${imageId}`);

    return data;
}

export async function reorderVehicleGallery(vehicleId, stage, urls) {
    const { data } = await api.put(`/admin/vehicles/${vehicleId}/gallery/order`, {
        stage,
        urls,
    });

    return data;
}

export function uploadedImagesByStage(uploadedImages = []) {
    const map = {
        terminal: [],
        pickup: [],
        destination: [],
    };

    for (const image of uploadedImages) {
        if (image?.stage && Array.isArray(map[image.stage])) {
            map[image.stage].push(image);
        }
    }

    return map;
}

function normalizeStorageUrl(url) {
    if (typeof url !== 'string' || url === '') {
        return '';
    }

    const match = url.match(/^(?:https?:\/\/[^/]+)?(\/storage\/.*)$/i);

    return match ? match[1] : url;
}

export function isLocalUploadedUrl(url, uploadedImages = []) {
    return isDeletableUploadedUrl(url, uploadedImages);
}

export function isDeletableUploadedUrl(url, uploadedImages = []) {
    if (! url || ! Array.isArray(uploadedImages)) {
        return false;
    }

    const normalized = normalizeStorageUrl(url);

    return uploadedImages.some((image) => {
        const imageUrl = image?.url;

        return imageUrl === url || normalizeStorageUrl(imageUrl) === normalized;
    });
}

export function localImageIdForUrl(url, uploadedImages = []) {
    const normalized = normalizeStorageUrl(url);
    const match = uploadedImages.find((image) => {
        const imageUrl = image?.url;

        return imageUrl === url || normalizeStorageUrl(imageUrl) === normalized;
    });

    return match?.id ?? null;
}
