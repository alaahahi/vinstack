import api from '../api/client';
import { ZIP_UPLOAD_TIMEOUT_MS } from '../constants/uploadTimeouts';
import { sanitizeUploadUserMessage } from './sanitizeUploadUserMessage';

const ZIP_UPLOAD_FALLBACK = 'تعذّر رفع ملف ZIP إلى Vinstack';
const ZIP_SENT_OK_MESSAGE = 'تم إرسال الملف — معالجة الصور جارية في الخلفية.';

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
 * Bytes finished uploading; a later timeout/network drop often means the server
 * already accepted the ZIP and is processing — treat as soft success.
 *
 * @param {unknown} error
 * @param {number} uploadPercent
 * @returns {boolean}
 */
function isPostUploadTransportFailure(error, uploadPercent) {
    if (uploadPercent < 99) {
        return false;
    }

    if (error?.code === 'ECONNABORTED' || /timeout/i.test(String(error?.message || ''))) {
        return true;
    }

    const status = error?.response?.status;

    // No HTTP response (proxy cut / connection reset) after body was sent.
    if (! error?.response && error?.request) {
        return true;
    }

    // Gateway / origin timeouts after the upload body arrived.
    if ([408, 502, 503, 504].includes(Number(status))) {
        return true;
    }

    return false;
}

/**
 * @param {import('axios').AxiosResponse} response
 * @returns {{ async: true, transfer: object, message: string } | null}
 */
function asyncTransferFromResponse(response) {
    const payload = response?.data?.data;
    const transfer = payload?.transfer;
    const transferId = transfer?.id;

    if (payload?.async && transferId) {
        return {
            async: true,
            transfer,
            message: sanitizeUploadUserMessage(
                response.data?.message,
                ZIP_SENT_OK_MESSAGE,
            ),
        };
    }

    return null;
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

    let uploadPercent = 0;

    try {
        const response = await api.post(`/admin/vehicles/${vehicleId}/images/zip`, form, {
            timeout: ZIP_UPLOAD_TIMEOUT_MS,
            onUploadProgress: (event) => {
                if (onProgress && event.total) {
                    uploadPercent = Math.round((event.loaded * 100) / event.total);
                    onProgress(uploadPercent);
                } else if (onProgress && event.loaded) {
                    uploadPercent = 99;
                    onProgress(99);
                }
            },
            validateStatus: (status) => (
                status === 200
                || status === 201
                || status === 202
                || status === 422
            ),
        });

        if (onProgress) {
            uploadPercent = 100;
            onProgress(100);
        }

        const asyncResult = asyncTransferFromResponse(response);

        if (asyncResult) {
            return asyncResult;
        }

        if (response.status === 422) {
            // Partial sync success still carries uploaded count.
            const uploaded = Number(response.data?.data?.uploaded ?? 0);

            if (uploaded > 0) {
                return response.data;
            }

            const error = new Error(formatVinstackZipUploadError({ response }));
            error.response = response;
            throw error;
        }

        return response.data;
    } catch (error) {
        const asyncFromError = asyncTransferFromResponse(error?.response);

        if (asyncFromError) {
            return asyncFromError;
        }

        if (isPostUploadTransportFailure(error, uploadPercent)) {
            return {
                async: true,
                transfer: { id: null, total_images: 0 },
                message: ZIP_SENT_OK_MESSAGE,
                assumedAccepted: true,
            };
        }

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
        || file.type === 'application/octet-stream'
        || file.type === 'multipart/x-zip'
        || name.endsWith('.zip');
}
