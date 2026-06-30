import JSZip from 'jszip';
import api from '../api/client';
import { useAuthStore } from '../stores/auth';
import {
    GALLERY_STAGES,
    vehicleGalleryByStage,
    vehicleGalleryImages,
    vehicleLabel,
} from './vehicleImages';

function apiPrefix() {
    const auth = useAuthStore();

    return auth.isAdmin ? '/admin' : '/dealer';
}

function filenameFromUrl(url, index = 0) {
    const segment = url.split('/').pop()?.split('?')[0] || '';

    if (segment && segment.includes('.')) {
        return segment;
    }

    return `photo-${index + 1}.jpg`;
}

function safeZipBasename(vehicle) {
    const raw = vehicleLabel(vehicle) || vehicle?.vin || 'vehicle';

    return raw.replace(/[^\w\u0600-\u06FF\s-]+/g, '_').trim() || 'vehicle';
}

function stageZipBasename(vehicle, stageKey) {
    const stageLabel = GALLERY_STAGES.find((stage) => stage.key === stageKey)?.label ?? stageKey;

    return `${safeZipBasename(vehicle)}-${stageLabel}`.replace(/\s+/g, '-');
}

async function downloadUrlsAsZip(urls, vehicle, {
    zipFilename,
    folderName,
    onProgress,
    progressOffset = 0,
    progressTotal = urls.length,
} = {}) {
    const zip = new JSZip();
    const folder = zip.folder(folderName ?? safeZipBasename(vehicle)) ?? zip;
    let saved = 0;

    for (let i = 0; i < urls.length; i += 1) {
        const url = urls[i];
        onProgress?.(progressOffset + i + 1, progressTotal);

        try {
            const blob = await fetchVehicleImageBlob(url, vehicle?.id);
            folder.file(filenameFromUrl(url, i), blob);
            saved += 1;
        } catch {
            // skip failed image
        }

        if (i < urls.length - 1) {
            await new Promise((resolve) => setTimeout(resolve, 120));
        }
    }

    if (saved === 0) {
        throw new Error('fetch_failed');
    }

    const zipBlob = await zip.generateAsync({ type: 'blob' });
    triggerBlobDownload(zipBlob, zipFilename);

    return { ok: true, count: saved, total: urls.length };
}

function triggerBlobDownload(blob, filename) {
    const objectUrl = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = objectUrl;
    anchor.download = filename;
    anchor.rel = 'noopener';
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(objectUrl);
}

function openImageInNewTab(url) {
    window.open(url, '_blank', 'noopener,noreferrer');
}

async function fetchViaProxy(url, vehicleId) {
    const { data } = await api.get(`${apiPrefix()}/vehicles/${vehicleId}/images/download`, {
        params: { url },
        responseType: 'blob',
    });

    return data;
}

/**
 * @returns {Promise<Blob>}
 */
export async function fetchVehicleImageBlob(url, vehicleId) {
    if (! url) {
        throw new Error('missing_url');
    }

    try {
        const response = await fetch(url, { mode: 'cors', credentials: 'omit' });

        if (response.ok) {
            const blob = await response.blob();

            if (blob.size > 0) {
                return blob;
            }
        }
    } catch {
        // CORS or network — fall through to proxy / tab
    }

    if (vehicleId) {
        return fetchViaProxy(url, vehicleId);
    }

    throw new Error('cors_blocked');
}

export async function downloadVehicleImage(url, vehicle, index = 0) {
    const filename = filenameFromUrl(url, index);

    try {
        const blob = await fetchVehicleImageBlob(url, vehicle?.id);
        triggerBlobDownload(blob, filename);

        return { ok: true, method: 'blob' };
    } catch {
        openImageInNewTab(url);

        return { ok: true, method: 'tab' };
    }
}

export async function downloadStageVehicleImages(vehicle, stageKey, { onProgress } = {}) {
    const urls = vehicleGalleryByStage(vehicle)[stageKey] ?? [];

    if (! urls.length) {
        throw new Error('no_images');
    }

    return downloadUrlsAsZip(urls, vehicle, {
        zipFilename: `${stageZipBasename(vehicle, stageKey)}.zip`,
        folderName: stageZipBasename(vehicle, stageKey),
        onProgress,
        progressOffset: 0,
        progressTotal: urls.length,
    });
}

/**
 * Download every gallery stage as its own ZIP (Terminal / Pickup / Destination).
 */
export async function downloadAllVehicleImagesByStage(vehicle, { onProgress } = {}) {
    const stages = vehicleGalleryByStage(vehicle);
    const stagesWithImages = GALLERY_STAGES.filter(
        (stage) => (stages[stage.key]?.length ?? 0) > 0,
    );

    if (! stagesWithImages.length) {
        throw new Error('no_images');
    }

    const totalUrls = stagesWithImages.reduce(
        (sum, stage) => sum + (stages[stage.key]?.length ?? 0),
        0,
    );

    let saved = 0;
    let processed = 0;
    const zipCount = stagesWithImages.length;

    for (let zipIndex = 0; zipIndex < stagesWithImages.length; zipIndex += 1) {
        const stage = stagesWithImages[zipIndex];
        const urls = stages[stage.key] ?? [];

        const result = await downloadUrlsAsZip(urls, vehicle, {
            zipFilename: `${stageZipBasename(vehicle, stage.key)}.zip`,
            folderName: stageZipBasename(vehicle, stage.key),
            onProgress,
            progressOffset: processed,
            progressTotal: totalUrls,
        });

        saved += result.count;
        processed += urls.length;

        if (zipIndex < stagesWithImages.length - 1) {
            await new Promise((resolve) => setTimeout(resolve, 350));
        }
    }

    return {
        ok: true,
        count: saved,
        total: totalUrls,
        zipCount,
    };
}

export async function downloadAllVehicleImages(vehicle, options = {}) {
    return downloadAllVehicleImagesByStage(vehicle, options);
}

function extensionFromMime(mime) {
    if (! mime) {
        return 'jpg';
    }

    if (mime.includes('png')) {
        return 'png';
    }

    if (mime.includes('webp')) {
        return 'webp';
    }

    if (mime.includes('gif')) {
        return 'gif';
    }

    return 'jpg';
}

/** Browsers often cap Web Share `files` at ~10. */
const MAX_SHARE_FILES = 10;

async function buildImageFilesFromUrls(urls, vehicleId, { onProgress } = {}) {
    const files = [];

    for (let i = 0; i < urls.length; i += 1) {
        const url = urls[i];
        onProgress?.(i + 1, urls.length);

        try {
            const blob = await fetchVehicleImageBlob(url, vehicleId);
            files.push(
                new File([blob], filenameFromUrl(url, i), {
                    type: blob.type || 'image/jpeg',
                }),
            );
        } catch {
            // skip failed image
        }

        if (i < urls.length - 1) {
            await new Promise((resolve) => setTimeout(resolve, 120));
        }
    }

    return files;
}

async function buildZipFileFromImageFiles(files, vehicle) {
    const zip = new JSZip();
    const folder = zip.folder(safeZipBasename(vehicle)) ?? zip;

    for (let i = 0; i < files.length; i += 1) {
        folder.file(files[i].name, files[i]);
    }

    const zipBlob = await zip.generateAsync({ type: 'blob' });

    return new File([zipBlob], `${safeZipBasename(vehicle)}-photos.zip`, {
        type: 'application/zip',
    });
}

async function tryShareFiles(files, title) {
    if (! files.length || ! navigator.share) {
        return false;
    }

    if (! navigator.canShare?.({ files })) {
        return false;
    }

    await navigator.share({ files, title, text: title });

    return true;
}

async function tryShareUrl(title, text, url) {
    if (! navigator.share) {
        return false;
    }

    const payloads = url
        ? [{ title, text, url }, { title, text }]
        : [{ title, text }];

    for (const payload of payloads) {
        try {
            if (navigator.canShare && ! navigator.canShare(payload)) {
                continue;
            }

            await navigator.share(payload);

            return true;
        } catch (error) {
            if (error?.name === 'AbortError') {
                throw error;
            }
        }
    }

    return false;
}

function triggerFileDownload(file) {
    triggerBlobDownload(file, file.name);
}

async function shareZipDownloadFallback(files, vehicle, urls) {
    const zipFile = await buildZipFileFromImageFiles(files, vehicle);
    triggerFileDownload(zipFile);

    return {
        ok: true,
        method: 'zip_download',
        count: files.length,
        total: urls.length,
    };
}

function isShareAborted(error) {
    return error?.name === 'AbortError';
}

/**
 * Share every gallery image (same URL list as bulk download).
 *
 * @param {string[]} urls
 * @param {string} title
 * @param {{ vehicleId?: number, vehicle?: object, onProgress?: (current: number, total: number) => void }} [options]
 */
export async function shareAllImages(urls, title, { vehicleId, vehicle, onProgress } = {}) {
    if (! urls?.length) {
        throw new Error('no_images');
    }

    const shareTitle = title || vehicleLabel(vehicle) || 'صور السيارة';
    const shareText = `${shareTitle} — ${urls.length} صورة`;
    const firstUrl = urls[0];
    const files = await buildImageFilesFromUrls(urls, vehicleId, { onProgress });

    if (files.length === 0) {
        throw new Error('fetch_failed');
    }

    const filesToTry =
        files.length <= MAX_SHARE_FILES ? files : files.slice(0, MAX_SHARE_FILES);
    const isPartial = files.length > MAX_SHARE_FILES;

    try {
        if (await tryShareFiles(filesToTry, shareTitle)) {
            return {
                ok: true,
                method: isPartial ? 'files_partial' : 'files',
                count: filesToTry.length,
                total: urls.length,
            };
        }
    } catch (error) {
        if (isShareAborted(error)) {
            return { ok: false, aborted: true };
        }
    }

    if (files.length > 1 || isPartial) {
        try {
            const zipFile = await buildZipFileFromImageFiles(files, vehicle);
            if (await tryShareFiles([zipFile], shareTitle)) {
                return {
                    ok: true,
                    method: 'zip',
                    count: files.length,
                    total: urls.length,
                };
            }
        } catch (error) {
            if (isShareAborted(error)) {
                return { ok: false, aborted: true };
            }
        }
    }

    try {
        if (await tryShareUrl(shareTitle, shareText, firstUrl)) {
            return {
                ok: true,
                method: 'url',
                count: files.length,
                total: urls.length,
            };
        }
    } catch (error) {
        if (isShareAborted(error)) {
            return { ok: false, aborted: true };
        }
    }

    try {
        return await shareZipDownloadFallback(files, vehicle, urls);
    } catch {
        throw new Error('zip_failed');
    }
}

export async function shareAllVehicleImages(vehicle, { onProgress } = {}) {
    const urls = vehicleGalleryImages(vehicle);

    if (! urls.length) {
        throw new Error('no_images');
    }

    const title = vehicleLabel(vehicle) || 'صور السيارة';

    return shareAllImages(urls, title, {
        vehicleId: vehicle?.id,
        vehicle,
        onProgress,
    });
}

export async function shareVehicleImage(url, vehicle, title) {
    if (! url) {
        throw new Error('missing_url');
    }

    const shareTitle = title || vehicleLabel(vehicle) || 'صورة السيارة';

    try {
        const blob = await fetchVehicleImageBlob(url, vehicle?.id);
        const file = new File(
            [blob],
            filenameFromUrl(url),
            { type: blob.type || 'image/jpeg' },
        );

        if (navigator.canShare?.({ files: [file] })) {
            await navigator.share({ files: [file], title: shareTitle });

            return { ok: true, method: 'files' };
        }
    } catch (error) {
        if (error?.name === 'AbortError') {
            return { ok: false, aborted: true };
        }
    }

    if (navigator.share) {
        try {
            await navigator.share({ title: shareTitle, url });

            return { ok: true, method: 'url' };
        } catch (error) {
            if (error?.name === 'AbortError') {
                return { ok: false, aborted: true };
            }
        }
    }

    try {
        await navigator.clipboard.writeText(url);

        return { ok: true, method: 'clipboard' };
    } catch {
        throw new Error('share_unsupported');
    }
}

export { extensionFromMime, filenameFromUrl };
