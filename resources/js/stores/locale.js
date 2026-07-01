import { defineStore } from 'pinia';
import i18n, { applyLocaleToDocument, readStartingLocale } from '../i18n';
import { LOCALE_STORAGE_KEY, SUPPORTED_LOCALES, getLocaleDirection, normalizeLocale } from '../constants/locales';

export const useLocaleStore = defineStore('locale', {
    state: () => ({
        locale: readStartingLocale(),
    }),
    getters: {
        availableLocales: () => SUPPORTED_LOCALES,
        direction: (state) => getLocaleDirection(state.locale),
        isRtl: (state) => getLocaleDirection(state.locale) === 'rtl',
    },
    actions: {
        init() {
            this.setLocale(this.locale);
        },
        setLocale(locale) {
            const nextLocale = normalizeLocale(locale);

            this.locale = nextLocale;
            localStorage.setItem(LOCALE_STORAGE_KEY, nextLocale);
            i18n.global.locale.value = nextLocale;
            applyLocaleToDocument(nextLocale);
        },
    },
});
