import api from '../api/client';

const MIME_BY_EXT = {
    jpg: 'image/jpeg',
    jpeg: 'image/jpeg',
    png: 'image/png',
    webp: 'image/webp',
    gif: 'image/gif',
    bmp: 'image/bmp',
};

/**
 * @param {unknown} error
 * @returns {string}
 */
export function formatCloudinaryUploadError(error) {
    const data = error?.response?.data;

    if (! data) {
        return error?.message || 'تعذّر رفع الصور إلى Cloudinary';
    }

    if (data.errors && typeof data.errors === 'object') {
        const first = Object.values(data.errors).flat()[0];

        if (first) {
            return String(first);
        }
    }

    let message = data.message || 'تعذّر رفع الصور إلى Cloudinary';
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
 * Quick Cloudinary configuration check (no upload).
 */
export async function fetchCloudinaryStatus(apiPrefix = '/admin') {
    const { data } = await api.get(`${apiPrefix}/containers/cloudinary-status`);

    return data.data ?? null;
}

/**
 * Full Cloudinary connection test including optional probe upload.
 */
export async function testCloudinaryConnection(apiPrefix = '/admin') {
    const { data } = await api.post(`${apiPrefix}/vinstack/settings/cloudinary-test`);

    return data.data ?? data;
}

/**
 * Upload extracted ZIP images to Laravel → Cloudinary.
 *
 * @param {object} params
 * @param {string} params.containerRef - container or booking number
 * @param {Array<{ name: string, url: string, vin: string|null, lot: string|null, file?: File|Blob }>} params.images
 * @param {string} [params.apiPrefix='/admin']
 * @param {boolean} [params.replace=true]
 * @param {(progress: { done: number, total: number, percent: number }) => void} [params.onProgress]
 * @returns {Promise<{ images: Array, byVin: Record<string, string[]>, unmatched: string[], meta?: object, uploaded?: number }>}
 */
export async function uploadContainerImagesToCloud({
    containerRef,
    images,
    apiPrefix = '/admin',
    replace = true,
    onProgress,
}) {
    if (! containerRef || ! images?.length) {
        throw new Error('Container reference and images are required.');
    }

    const total = images.length;
    const batchSize = 10;
    let done = 0;
    let lastPayload = null;
    let totalUploaded = 0;
    const allFailed = [];

    for (let offset = 0; offset < total; offset += batchSize) {
        const slice = images.slice(offset, offset + batchSize);
        const form = new FormData();

        form.append('replace', replace && offset === 0 ? '1' : '0');

        const metadata = [];

        for (let i = 0; i < slice.length; i += 1) {
            const image = slice[i];
            const blob = await blobFromImageRecord(image);
            const filename = image.name || `image-${offset + i + 1}.jpg`;
            const typedBlob = withImageMimeType(blob, filename);

            form.append(`images[${i}]`, typedBlob, filename);
            metadata.push({
                name: filename,
                vin: image.vin ?? null,
                lot: image.lot ?? null,
            });
        }

        form.append('metadata', JSON.stringify(metadata));

        let response;

        try {
            response = await api.post(
                `${apiPrefix}/containers/${encodeURIComponent(containerRef)}/images/upload`,
                form,
            );
        } catch (error) {
            error.message = formatCloudinaryUploadError(error);
            throw error;
        }

        const batchPayload = response.data?.data ?? null;
        const batchUploaded = Number(batchPayload?.uploaded ?? 0);
        const batchFailed = batchPayload?.failed ?? response.data?.failed ?? [];

        totalUploaded += batchUploaded;

        if (batchFailed.length) {
            allFailed.push(...batchFailed);
        }

        if (batchUploaded === 0 && slice.length > 0) {
            const error = new Error(formatCloudinaryUploadError({
                response: { data: response.data },
            }));
            error.response = response;
            throw error;
        }

        done += slice.length;
        lastPayload = batchPayload;

        onProgress?.({
            done,
            total,
            percent: Math.round((done / total) * 100),
        });
    }

    if (! lastPayload) {
        throw new Error('Upload did not return image payload.');
    }

    return {
        ...lastPayload,
        uploaded: totalUploaded,
        failed: allFailed,
    };
}

/**
 * Fetch persisted Cloudinary images for a container.
 */
export async function fetchContainerCloudImages(containerRef, apiPrefix = '/admin') {
    if (! containerRef) {
        return null;
    }

    const { data } = await api.get(
        `${apiPrefix}/containers/${encodeURIComponent(containerRef)}/images`,
    );

    return data.data ?? null;
}

async function blobFromImageRecord(image) {
    if (image.file instanceof Blob) {
        return image.file;
    }

    if (image.url?.startsWith('blob:')) {
        const response = await fetch(image.url);

        return response.blob();
    }

    throw new Error(`Missing file blob for ${image.name ?? 'image'}`);
}

function withImageMimeType(blob, filename) {
    const ext = String(filename).split('.').pop()?.toLowerCase() ?? '';
    const mime = MIME_BY_EXT[ext];

    if (! mime || blob.type === mime) {
        return blob;
    }

    return new Blob([blob], { type: mime });
}
