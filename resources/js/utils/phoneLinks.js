function normalizePhoneInput(phone) {
    if (!phone || typeof phone !== 'string') {
        return '';
    }

    return phone.trim().replace(/\s+/g, '').replace(/[-()]/g, '');
}

/**
 * Digits-only number for wa.me (no +). Iraqi local 0XXXXXXXXXX → 964XXXXXXXXX.
 */
export function whatsappDialDigits(phone) {
    const cleaned = normalizePhoneInput(phone);

    if (!cleaned) {
        return '';
    }

    let digits = cleaned.replace(/\D/g, '');

    if (!digits) {
        return '';
    }

    if (digits.startsWith('0') && digits.length === 11) {
        digits = `964${digits.slice(1)}`;
    }

    return digits;
}

export function whatsappUrl(phone) {
    const digits = whatsappDialDigits(phone);

    return digits ? `https://wa.me/${digits}` : '';
}
