import api from '../api/client';
import { UPLOAD_TIMEOUT_MS, ZIP_UPLOAD_TIMEOUT_MS } from '../constants/uploadTimeouts';

const MIME_BY_EXT = {
    jpg: 'image/jpeg',
    jpeg: 'image/jpeg',
    png: 'image/png',
    webp: 'image/webp',
    gif: 'image/gif',
    bmp: 'image/bmp',
};

const ALLOWED_EXTENSIONS = new Set(Object.keys(MIME_BY_EXT));

/**
 * @param {unknown} error
 * @returns {string}
 */
export function formatCloudinaryUploadError(error) {
    if (error?.code === 'ECONNABORTED' || /timeout/i.test(error?.message || '')) {
        return 'انتهت مهلة الاتصال أثناء رفع ملف ZIP. حدّث الصفحة للتحقق من النتيجة أو أعد المحاولة.';
    }

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
 * Upload a ZIP archive once — server extracts images and pushes to Cloudinary.
 *
 * @param {object} params
 * @param {string} params.containerRef
 * @param {File} params.zipFile
 * @param {string} [params.apiPrefix='/admin']
 * @param {boolean} [params.replace=true]
 * @param {(progress: { percent: number, phase: 'upload'|'processing' }) => void} [params.onProgress]
 */
export async function uploadContainerZipToCloud({
    containerRef,
    zipFile,
    apiPrefix = '/admin',
    replace = true,
    onProgress,
}) {
    if (! containerRef || ! zipFile) {
        throw new Error('Container reference and ZIP file are required.');
    }

    const form = new FormData();
    form.append('zip', zipFile, zipFile.name);
    form.append('replace', replace ? '1' : '0');

    onProgress?.({ percent: 0, phase: 'upload' });

    let response;

    try {
        response = await api.post(
            `${apiPrefix}/containers/${encodeURIComponent(containerRef)}/images/zip`,
            form,
            {
                timeout: ZIP_UPLOAD_TIMEOUT_MS,
                onUploadProgress: (event) => {
                    if (! event.total) {
                        return;
                    }

                    onProgress?.({
                        percent: Math.round((event.loaded / event.total) * 100),
                        phase: 'upload',
                    });
                },
            },
        );
    } catch (error) {
        error.message = formatCloudinaryUploadError(error);
        throw error;
    }

    onProgress?.({ percent: 100, phase: 'processing' });

    const payload = response.data?.data ?? null;

    if (! payload) {
        throw new Error('تعذّر معالجة ملف ZIP على الخادم.');
    }

    const uploaded = Number(payload.uploaded ?? 0);

    if (uploaded === 0) {
        const error = new Error(formatCloudinaryUploadError({
            response: { data: response.data },
        }));
        error.response = response;
        throw error;
    }

    return {
        ...payload,
        uploaded,
        failed: payload.failed ?? response.data?.failed ?? [],
    };
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
            const filename = ensureImageFilename(image.name, offset + i);
            const file = toUploadFile(blob, filename);

            validateUploadFile(file, filename);

            form.append(`images[${metadata.length}]`, file, filename);
            metadata.push({
                name: filename,
                vin: image.vin ?? null,
                lot: image.lot ?? null,
            });
        }

        if (metadata.length === 0) {
            continue;
        }

        form.append('metadata', JSON.stringify(metadata));

        let response;

        try {
            response = await api.post(
                `${apiPrefix}/containers/${encodeURIComponent(containerRef)}/images/upload`,
                form,
                { timeout: UPLOAD_TIMEOUT_MS },
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

        if (batchUploaded === 0 && metadata.length > 0) {
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
        throw new Error('لا توجد ملفات صور صالحة للرفع في هذه الدفعة.');
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

export async function deleteContainerCloudImage(containerRef, imageId, apiPrefix = '/admin') {
    if (! containerRef || ! imageId) {
        throw new Error('Container reference and image id are required.');
    }

    const { data } = await api.delete(
        `${apiPrefix}/containers/${encodeURIComponent(containerRef)}/images/${imageId}`,
    );

    return data;
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

function ensureImageFilename(name, index) {
    const base = String(name || '').trim();

    if (base && /\.(jpe?g|png|webp|gif|bmp)$/i.test(base)) {
        return base;
    }

    if (base) {
        return `${base}.jpg`;
    }

    return `image-${index + 1}.jpg`;
}

function mimeForFilename(filename) {
    const ext = String(filename).split('.').pop()?.toLowerCase() ?? '';

    return MIME_BY_EXT[ext] ?? 'image/jpeg';
}

function toUploadFile(blob, filename) {
    const mime = mimeForFilename(filename);

    if (blob instanceof File && blob.name === filename && blob.type === mime) {
        return blob;
    }

    return new File([blob], filename, { type: mime || blob.type || 'image/jpeg' });
}

function fileExtension(filename) {
    return String(filename).split('.').pop()?.toLowerCase() ?? '';
}

function validateUploadFile(file, filename) {
    if (! file || file.size === 0) {
        throw new Error(`الملف فارغ أو غير صالح: ${filename}`);
    }

    const ext = fileExtension(filename);

    if (! ALLOWED_EXTENSIONS.has(ext)) {
        throw new Error(`امتداد الصورة غير مدعوم: ${filename}`);
    }

    const expectedMime = MIME_BY_EXT[ext];

    if (expectedMime && file.type && file.type !== expectedMime && file.type !== 'application/octet-stream') {
        throw new Error(`نوع الملف غير مدعوم: ${filename}`);
    }
}
