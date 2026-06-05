/**
 * Fixed LTR datetime for Arabic RTL UI.
 * Avoids Intl.DateTimeFormat mixing punctuation in RTL parents.
 * Uses browser local timezone via new Date(iso).
 */
export function formatDateTime(iso) {
    if (!iso) {
        return '—';
    }

    try {
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) {
            return String(iso);
        }

        const pad = (n) => String(n).padStart(2, '0');
        const day = pad(d.getDate());
        const month = pad(d.getMonth() + 1);
        const year = d.getFullYear();
        const hours = pad(d.getHours());
        const minutes = pad(d.getMinutes());

        return `${day}.${month}.${year}  ${hours}:${minutes}`;
    } catch {
        return String(iso);
    }
}
