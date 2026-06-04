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

export async function deleteVehicleImage(vehicleId, imageId) {
    const { data } = await api.delete(`/admin/vehicles/${vehicleId}/images/${imageId}`);

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

export function isLocalUploadedUrl(url, uploadedImages = []) {
    if (! url || ! Array.isArray(uploadedImages)) {
        return false;
    }

    return uploadedImages.some((image) => image?.url === url);
}

export function localImageIdForUrl(url, uploadedImages = []) {
    const match = uploadedImages.find((image) => image?.url === url);

    return match?.id ?? null;
}
