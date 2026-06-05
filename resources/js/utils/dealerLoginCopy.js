/**
 * @param {string} baseUrl
 * @returns {string}
 */
export function dealerLoginPageUrl(baseUrl) {
    const root = (baseUrl || '').replace(/\/+$/, '');

    if (!root) {
        return '/login';
    }

    return `${root}/login`;
}

/**
 * @param {{ login_identifier?: string, phone?: string, user?: { email?: string } }} dealer
 * @returns {string}
 */
export function dealerLoginUsername(dealer) {
    const id = dealer?.login_identifier?.trim();

    if (id) {
        return id;
    }

    const phone = dealer?.phone?.trim();

    if (phone) {
        return phone;
    }

    return dealer?.user?.email?.trim() || '';
}

/**
 * @param {{ user?: { email?: string } }} dealer
 * @returns {string}
 */
export function dealerLoginEmailLine(dealer) {
    return dealer?.user?.email?.trim() || '—';
}

/**
 * @param {{ phone?: string }} dealer
 * @returns {string}
 */
export function dealerLoginPhoneLine(dealer) {
    return dealer?.phone?.trim() || '—';
}

/**
 * @param {{ phone?: string }} dealer
 * @param {string} [passwordOverride] Plain password (e.g. right after create).
 * @returns {string}
 */
export function dealerLoginPasswordLine(dealer, passwordOverride) {
    if (passwordOverride?.trim()) {
        return passwordOverride.trim();
    }

    if (dealer?.phone?.trim()) {
        return '— (الدخول برقم الهاتف — بدون كلمة مرور)';
    }

    return '— (عُيّنت عند الإنشاء — غير قابلة للاسترجاع)';
}

/**
 * @param {{ login_identifier?: string, phone?: string, user?: { email?: string } }} dealer
 * @param {string} loginUrl Full login page URL.
 * @param {string} [passwordOverride]
 * @returns {string}
 */
export function formatDealerLoginCopy(dealer, loginUrl, passwordOverride) {
    const email = dealerLoginEmailLine(dealer);
    const password = dealerLoginPasswordLine(dealer, passwordOverride);
    const phone = dealerLoginPhoneLine(dealer);
    const url = loginUrl || dealerLoginPageUrl('');

    return `البريد الإلكتروني: ${email}\nكلمة المرور: ${password}\nالهاتف: ${phone}\n${url}`;
}
