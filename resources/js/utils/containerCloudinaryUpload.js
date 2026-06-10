import api from '../api/client';

/**
 * Upload extracted ZIP images to Laravel → Cloudinary.
 *
 * @param {object} params
 * @param {string} params.containerRef - container or booking number
 * @param {Array<{ name: string, url: string, vin: string|null, lot: string|null, file?: File|Blob }>} params.images
 * @param {string} [params.apiPrefix='/admin']
 * @param {boolean} [params.replace=true]
 * @param {(progress: { done: number, total: number, percent: number }) => void} [params.onProgress]
 * @returns {Promise<{ images: Array, byVin: Record<string, string[]>, unmatched: string[], meta?: object }>}
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

    for (let offset = 0; offset < total; offset += batchSize) {
        const slice = images.slice(offset, offset + batchSize);
        const form = new FormData();

        form.append('replace', replace && offset === 0 ? '1' : '0');

        const metadata = [];

        for (let i = 0; i < slice.length; i += 1) {
            const image = slice[i];
            const blob = await blobFromImageRecord(image);
            const filename = image.name || `image-${offset + i + 1}.jpg`;

            form.append(`images[${i}]`, blob, filename);
            metadata.push({
                name: filename,
                vin: image.vin ?? null,
                lot: image.lot ?? null,
            });
        }

        form.append('metadata', JSON.stringify(metadata));

        const { data } = await api.post(
            `${apiPrefix}/containers/${encodeURIComponent(containerRef)}/images/upload`,
            form,
            {
                headers: { 'Content-Type': 'multipart/form-data' },
            },
        );

        done += slice.length;
        lastPayload = data.data;

        onProgress?.({
            done,
            total,
            percent: Math.round((done / total) * 100),
        });
    }

    if (! lastPayload) {
        throw new Error('Upload did not return image payload.');
    }

    return lastPayload;
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
