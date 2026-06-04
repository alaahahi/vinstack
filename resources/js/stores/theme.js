import { defineStore } from 'pinia';

const STORAGE_KEY = 'theme';
const DEFAULT_THEME = 'dark';

export const LOGO_DAY = '/images/logo-day.jpg';
export const LOGO_NIGHT = '/images/logo-night.png';

function readStoredTheme() {
    const stored = localStorage.getItem(STORAGE_KEY);

    return stored === 'dark' || stored === 'light' ? stored : DEFAULT_THEME;
}

export function applyThemeToDocument(theme) {
    document.documentElement.setAttribute('data-theme', theme);
}

export const useThemeStore = defineStore('theme', {
    state: () => ({
        theme: readStoredTheme(),
    }),
    getters: {
        isDark: (state) => state.theme === 'dark',
        themeLogo: (state) => (state.theme === 'dark' ? LOGO_NIGHT : LOGO_DAY),
        toggleIcon: (state) => (state.theme === 'dark' ? 'pi pi-sun' : 'pi pi-moon'),
        toggleTooltip: (state) => (state.theme === 'dark' ? 'الوضع النهاري' : 'الوضع الليلي'),
    },
    actions: {
        init() {
            this.theme = readStoredTheme();
            applyThemeToDocument(this.theme);
        },
        setTheme(theme) {
            if (theme !== 'light' && theme !== 'dark') {
                return;
            }

            this.theme = theme;
            localStorage.setItem(STORAGE_KEY, theme);
            applyThemeToDocument(theme);
        },
        toggle() {
            this.setTheme(this.theme === 'dark' ? 'light' : 'dark');
        },
    },
});
