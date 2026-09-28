import api from '../api/client';
import { ZIP_UPLOAD_TIMEOUT_MS } from '../constants/uploadTimeouts';
import { sanitizeUploadUserMessage } from './sanitizeUploadUserMessage';

const ZIP_UPLOAD_FALLBACK = 'تعذّر رفع ملف ZIP إلى Vinstack';

/**
 * @param {unknown} error
 * @returns {string}
 */
export function formatVinstackZipUploadError(error) {
    if (error?.code === 'ECONNABORTED' || /timeout/i.test(error?.message || '')) {
        return 'انتهت مهلة الاتصال أثناء معالجة ZIP على الخادم. إن كان شريط الرفع وصل 100% فقد تكون الصور رُفعت — حدّث الصفحة. للملفات الكبيرة جرّب تقسيمها أو زِد مهلة Nginx/PHP.';
    }

    const data = error?.response?.data;

    if (! data) {
        return sanitizeUploadUserMessage(error?.message, ZIP_UPLOAD_FALLBACK);
    }

    if (data.errors && typeof data.errors === 'object') {
        const first = Object.values(data.errors).flat()[0];
        const fromErrors = sanitizeUploadUserMessage(first, '');

        if (fromErrors) {
            return fromErrors;
        }
    }

    let message = sanitizeUploadUserMessage(data.message, ZIP_UPLOAD_FALLBACK);
    const failed = Array.isArray(data.failed)
        ? data.failed
        : (Array.isArray(data.data?.failed) ? data.data.failed : []);

    if (failed.length && ! looksLikeManifestList(failed)) {
        const details = failed
            .slice(0, 3)
            .map((item) => {
                if (! item || typeof item !== 'object') {
                    return null;
                }

                const name = sanitizeUploadUserMessage(item.name, 'ملف');
                const err = sanitizeUploadUserMessage(item.error, '');

                return err ? `${name}: ${err}` : name;
            })
            .filter(Boolean)
            .join(' — ');

        if (details) {
            message = `${message} (${details}${failed.length > 3 ? ` +${failed.length - 3}` : ''})`;
        }
    }

    return sanitizeUploadUserMessage(message, ZIP_UPLOAD_FALLBACK);
}

/**
 * @param {unknown[]} items
 * @returns {boolean}
 */
function looksLikeManifestList(items) {
    return items.some((item) => (
        item
        && typeof item === 'object'
        && typeof item.path === 'string'
        && typeof item.status === 'string'
    ));
}

/**
 * @param {number|string} vehicleId
 * @param {'terminal'|'pickup'|'destination'} stage
 * @param {File} zipFile
 * @param {(percent: number) => void} [onProgress]
 */
export async function uploadVehicleZipImages(vehicleId, stage, zipFile, onProgress) {
    const form = new FormData();
    form.append('stage', stage);
    form.append('zip', zipFile, zipFile.name);

    try {
        const response = await api.post(`/admin/vehicles/${vehicleId}/images/zip`, form, {
            timeout: ZIP_UPLOAD_TIMEOUT_MS,
            onUploadProgress: (event) => {
                if (onProgress && event.total) {
                    onProgress(Math.round((event.loaded * 100) / event.total));
                } else if (onProgress && event.loaded) {
                    onProgress(99);
                }
            },
            validateStatus: (status) => status === 201 || status === 202 || status === 422,
        });

        if (onProgress) {
            onProgress(100);
        }

        if (response.status === 202 && response.data?.data?.async) {
            return {
                async: true,
                transfer: response.data.data.transfer,
                message: response.data.message,
            };
        }

        if (response.status === 422) {
            const error = new Error(formatVinstackZipUploadError({ response }));
            error.response = response;
            throw error;
        }

        return response.data;
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
