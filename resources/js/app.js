import { createApp } from 'vue';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';

import App from './App.vue';
import i18n from './i18n';
import router from './router';
import { useLocaleStore } from './stores/locale';
import { useThemeStore } from './stores/theme';
import { useAuthStore } from './stores/auth';

const pinia = createPinia();
const app = createApp(App);

app.use(pinia);
app.use(i18n);
app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: '[data-theme="dark"]',
        },
    },
});

const auth = useAuthStore();
const localeStore = useLocaleStore();
localeStore.initFromUser(auth.user?.locale);
useThemeStore(pinia).init();
app.use(ToastService);
app.use(ConfirmationService);

app.mount('#app');
