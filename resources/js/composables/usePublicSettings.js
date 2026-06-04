import { computed, ref } from 'vue';
import api from '../api/client';
import { whatsappUrl } from '../utils/phoneLinks';

const supportPhone = ref('');
let loaded = false;
let loadPromise = null;

export function usePublicSettings() {
    const hasSupportPhone = computed(() => Boolean(supportPhone.value?.trim()));

    const supportWhatsAppHref = computed(() => whatsappUrl(supportPhone.value));

    async function loadPublicSettings() {
        if (loaded) {
            return;
        }

        if (!loadPromise) {
            loadPromise = api
                .get('/settings/public')
                .then(({ data }) => {
                    supportPhone.value = data.data?.support_phone?.trim() || '';
                })
                .catch(() => {
                    supportPhone.value = '';
                })
                .finally(() => {
                    loaded = true;
                });
        }

        await loadPromise;
    }

    return {
        supportPhone,
        hasSupportPhone,
        supportWhatsAppHref,
        loadPublicSettings,
    };
}
