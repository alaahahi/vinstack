export const LOCALE_STORAGE_KEY = 'vinstack-locale';
export const DEFAULT_LOCALE = 'ar';
export const FALLBACK_LOCALE = 'en';

export const SUPPORTED_LOCALES = [
    {
        code: 'en',
        dir: 'ltr',
        htmlLang: 'en',
        nativeName: 'English',
    },
    {
        code: 'ar',
        dir: 'rtl',
        htmlLang: 'ar',
        nativeName: 'العربية',
    },
    {
        code: 'ckb',
        dir: 'rtl',
        htmlLang: 'ckb',
        nativeName: 'کوردی',
    },
];

export function normalizeLocale(locale) {
    return SUPPORTED_LOCALES.some(({ code }) => code === locale) ? locale : DEFAULT_LOCALE;
}

export function getLocaleDefinition(locale) {
    return SUPPORTED_LOCALES.find(({ code }) => code === locale) || SUPPORTED_LOCALES[0];
}

export function getLocaleDirection(locale) {
    return getLocaleDefinition(locale).dir;
}

export function isRtlLocale(locale) {
    return getLocaleDirection(locale) === 'rtl';
}
