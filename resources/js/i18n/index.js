import { createI18n } from 'vue-i18n';
import {
    DEFAULT_LOCALE,
    FALLBACK_LOCALE,
    LOCALE_STORAGE_KEY,
    getLocaleDefinition,
    normalizeLocale,
} from '../constants/locales';
import en from './messages/en';
import ar from './messages/ar';
import ckb from './messages/ckb';

export const messages = {
    en,
    ar,
    ckb,
};

export function readStartingLocale() {
    const storedLocale = localStorage.getItem(LOCALE_STORAGE_KEY);

    if (storedLocale) {
        return normalizeLocale(storedLocale);
    }

    const documentLocale = document.documentElement.lang;

    if (documentLocale) {
        return normalizeLocale(documentLocale.split('-')[0]);
    }

    return DEFAULT_LOCALE;
}

export function applyLocaleToDocument(locale) {
    const definition = getLocaleDefinition(locale);

    document.documentElement.setAttribute('lang', definition.htmlLang);
    document.documentElement.setAttribute('dir', definition.dir);
    document.documentElement.dataset.locale = definition.code;
}

const initialLocale = readStartingLocale();

export const i18n = createI18n({
    legacy: false,
    locale: initialLocale,
    fallbackLocale: FALLBACK_LOCALE,
    messages,
});

applyLocaleToDocument(initialLocale);

export default i18n;
