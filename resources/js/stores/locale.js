import { defineStore } from 'pinia';
import i18n, { applyLocaleToDocument } from '../i18n';
import { LOCALE_STORAGE_KEY, SUPPORTED_LOCALES, getLocaleDirection, normalizeLocale } from '../constants/locales';
import api from '../api/client';

const DEALER_DEFAULT_LOCALE = 'ckb';

export const useLocaleStore = defineStore('locale', {
    state: () => ({
        locale: DEALER_DEFAULT_LOCALE,
    }),
    getters: {
        availableLocales: () => SUPPORTED_LOCALES,
        direction: (state) => getLocaleDirection(state.locale),
        isRtl: (state) => getLocaleDirection(state.locale) === 'rtl',
    },
    actions: {
        initFromUser(userLocale, { isDealer = false, localeCustomized = false } = {}) {
            if (isDealer && ! localeCustomized) {
                this.setLocale(DEALER_DEFAULT_LOCALE, { syncServer: false });

                return;
            }

            if (userLocale) {
                this.setLocale(userLocale, { syncServer: false });

                return;
            }

            const stored = localStorage.getItem(LOCALE_STORAGE_KEY);

            if (stored) {
                this.applyLocale(stored);

                return;
            }

            this.setLocale(isDealer ? DEALER_DEFAULT_LOCALE : 'ar', { syncServer: false });
        },
        setLocale(locale, { syncServer = true } = {}) {
            const nextLocale = normalizeLocale(locale);

            this.applyLocale(nextLocale);

            if (! syncServer) {
                return;
            }

            const token = localStorage.getItem('token');
            const user = JSON.parse(localStorage.getItem('user') || 'null');

            if (token && user?.role === 'dealer') {
                api.put('/dealer/locale', { locale: nextLocale }).catch(() => {});
            }
        },
        applyLocale(locale) {
            const nextLocale = normalizeLocale(locale);

            this.locale = nextLocale;
            localStorage.setItem(LOCALE_STORAGE_KEY, nextLocale);
            i18n.global.locale.value = nextLocale;
            applyLocaleToDocument(nextLocale);
        },
    },
});
