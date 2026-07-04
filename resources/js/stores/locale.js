import { defineStore } from 'pinia';
import i18n, { applyLocaleToDocument, readStartingLocale } from '../i18n';
import { LOCALE_STORAGE_KEY, SUPPORTED_LOCALES, getLocaleDirection, normalizeLocale } from '../constants/locales';
import api from '../api/client';

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
        initFromUser(userLocale) {
            if (userLocale) {
                this.setLocale(userLocale, { syncServer: false });

                return;
            }

            this.applyLocale(this.locale);
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
