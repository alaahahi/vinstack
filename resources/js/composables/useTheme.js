import { storeToRefs } from 'pinia';
import { useThemeStore } from '../stores/theme';

export function useTheme() {
    const themeStore = useThemeStore();
    const { theme, isDark, themeLogo, toggleIcon } = storeToRefs(themeStore);

    return {
        theme,
        isDark,
        themeLogo,
        toggleIcon,
        setTheme: themeStore.setTheme.bind(themeStore),
        toggle: themeStore.toggle.bind(themeStore),
    };
}
