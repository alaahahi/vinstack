/**
 * Preload a single image URL into the browser cache.
 * @param {string} url
 * @returns {Promise<string>}
 */
export function preloadImageUrl(url) {
    return new Promise((resolve, reject) => {
        if (! url) {
            reject(new Error('empty url'));

            return;
        }

        const img = new Image();
        img.decoding = 'async';
        img.onload = () => resolve(url);
        img.onerror = () => reject(new Error(`Failed to load: ${url}`));
        img.src = url;
    });
}

/**
 * Preload many image URLs with bounded concurrency and progress callbacks.
 *
 * @param {string[]} urls
 * @param {object} [options]
 * @param {(progress: { done: number, total: number, percent: number, remaining: number }) => void} [options.onProgress]
 * @param {number} [options.concurrency=6]
 * @param {AbortSignal} [options.signal]
 * @returns {Promise<{ loaded: number, failed: number, total: number }>}
 */
export async function preloadImageUrls(urls, { onProgress, concurrency = 6, signal } = {}) {
    const queue = [...urls].filter(Boolean);
    const total = queue.length;

    if (! total) {
        onProgress?.({ done: 0, total: 0, percent: 100, remaining: 0 });

        return { loaded: 0, failed: 0, total: 0 };
    }

    let done = 0;
    let failed = 0;

    const notify = () => {
        onProgress?.({
            done,
            total,
            percent: Math.round((done / total) * 100),
            remaining: Math.max(0, total - done),
        });
    };

    notify();

    const workerCount = Math.min(Math.max(1, concurrency), total);

    const workers = Array.from({ length: workerCount }, async () => {
        while (queue.length) {
            if (signal?.aborted) {
                return;
            }

            const url = queue.shift();

            try {
                await preloadImageUrl(url);
            } catch {
                failed += 1;
            } finally {
                done += 1;
                notify();
            }
        }
    });

    await Promise.all(workers);

    return {
        loaded: done - failed,
        failed,
        total,
    };
}
