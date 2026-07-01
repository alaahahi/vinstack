import { storeToRefs } from 'pinia';
import { useLocaleStore } from '../stores/locale';

export function useLocale() {
    const localeStore = useLocaleStore();
    const { locale, availableLocales, direction, isRtl } = storeToRefs(localeStore);

    return {
        locale,
        availableLocales,
        direction,
        isRtl,
        setLocale: localeStore.setLocale.bind(localeStore),
    };
}
