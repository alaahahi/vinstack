import api from '../api/client';

const POLL_MS = 4000;
const backgroundWatchers = new Map();

/**
 * @param {string} transferId
 * @param {string} [apiPrefix='/admin']
 */
export async function fetchImageTransferStatus(transferId, apiPrefix = '/admin') {
    if (! transferId) {
        return null;
    }

    const { data } = await api.get(
        `${apiPrefix}/image-transfers/${encodeURIComponent(transferId)}`,
    );

    return data.data ?? null;
}

export async function fetchImageTransfers(apiPrefix = '/admin') {
    const { data } = await api.get(`${apiPrefix}/image-transfers`);

    return data.data ?? [];
}

/**
 * Silent background polling — no UI dock.
 */
export function watchBackgroundTransfer({
    transferId,
    apiPrefix = '/admin',
    onComplete,
    onFailed,
}) {
    if (! transferId) {
        return () => {};
    }

    stopBackgroundTransferWatch(transferId);

    let stopped = false;

    const tick = async () => {
        if (stopped) {
            return;
        }

        try {
            const transfer = await fetchImageTransferStatus(transferId, apiPrefix);

            if (! transfer) {
                backgroundWatchers.set(transferId, setTimeout(tick, POLL_MS * 2));

                return;
            }

            if (['completed', 'partial'].includes(transfer.status)) {
                stopBackgroundTransferWatch(transferId);
                onComplete?.(transfer);

                return;
            }

            if (transfer.status === 'failed') {
                stopBackgroundTransferWatch(transferId);
                onFailed?.(transfer);

                return;
            }

            backgroundWatchers.set(transferId, setTimeout(tick, POLL_MS));
        } catch {
            backgroundWatchers.set(transferId, setTimeout(tick, POLL_MS * 2));
        }
    };

    backgroundWatchers.set(transferId, setTimeout(tick, POLL_MS));

    return () => {
        stopped = true;
        stopBackgroundTransferWatch(transferId);
    };
}

export function stopBackgroundTransferWatch(transferId) {
    const timer = backgroundWatchers.get(transferId);

    if (timer) {
        clearTimeout(timer);
        backgroundWatchers.delete(transferId);
    }
}
