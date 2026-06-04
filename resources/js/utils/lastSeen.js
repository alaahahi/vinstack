import { ONLINE_THRESHOLD_MINUTES } from '../constants/presence';

function parseInstant(value) {
    if (!value) {
        return null;
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
}

export function isDealerOnline(dealer) {
    if (dealer?.is_online) {
        return true;
    }

    const seen = parseInstant(dealer?.last_seen_at);

    if (!seen) {
        return false;
    }

    const thresholdMs = ONLINE_THRESHOLD_MINUTES * 60 * 1000;

    return Date.now() - seen.getTime() <= thresholdMs;
}

export function formatLastSeenDateTime(value) {
    const date = parseInstant(value);

    if (!date) {
        return '';
    }

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

function arabicMinutesWord(n) {
    if (n === 1) {
        return 'دقيقة';
    }

    if (n === 2) {
        return 'دقيقتين';
    }

    if (n >= 3 && n <= 10) {
        return 'دقائق';
    }

    return 'دقيقة';
}

export function formatLastSeenLabel(dealer) {
    if (isDealerOnline(dealer)) {
        return 'متصل';
    }

    const seen = parseInstant(dealer?.last_seen_at);

    if (!seen) {
        return 'لم يظهر بعد';
    }

    const diffMs = Date.now() - seen.getTime();
    const diffMinutes = Math.floor(diffMs / 60_000);

    if (diffMinutes < 60) {
        const n = Math.max(1, diffMinutes);

        return `منذ ${n} ${arabicMinutesWord(n)}`;
    }

    return `آخر ظهور: ${formatLastSeenDateTime(dealer.last_seen_at)}`;
}
