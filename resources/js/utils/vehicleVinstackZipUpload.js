import api from '../api/client';

/**
 * @param {unknown} error
 * @returns {string}
 */
export function formatVinstackZipUploadError(error) {
    const data = error?.response?.data;

    if (! data) {
        return error?.message || 'تعذّر رفع ملف ZIP إلى Vinstack';
    }

    if (data.errors && typeof data.errors === 'object') {
        const first = Object.values(data.errors).flat()[0];

        if (first) {
            return String(first);
        }
    }

    let message = data.message || 'تعذّر رفع ملف ZIP إلى Vinstack';
    const failed = data.failed ?? data.data?.failed ?? [];

    if (failed.length) {
        const details = failed
            .slice(0, 3)
            .map((item) => `${item.name}: ${item.error}`)
            .join(' — ');

        message = `${message} (${details}${failed.length > 3 ? ` +${failed.length - 3}` : ''})`;
    }

    return message;
}

/**
 * @param {number|string} vehicleId
 * @param {'terminal'|'pickup'|'destination'} stage
 * @param {File} zipFile
 */
export async function uploadVehicleZipImages(vehicleId, stage, zipFile) {
    const form = new FormData();
    form.append('stage', stage);
    form.append('zip', zipFile, zipFile.name);

    try {
        const { data } = await api.post(`/admin/vehicles/${vehicleId}/images/zip`, form);

        return data;
    } catch (error) {
        error.message = formatVinstackZipUploadError(error);
        throw error;
    }
}

export function isZipFile(file) {
    if (! file) {
        return false;
    }

    const name = String(file.name || '').toLowerCase();

    return file.type === 'application/zip'
        || file.type === 'application/x-zip-compressed'
        || name.endsWith('.zip');
}
