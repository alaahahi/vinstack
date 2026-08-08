import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useLocaleStore } from '../stores/locale';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/LoginPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/two-factor/setup',
        name: 'two-factor.setup',
        component: () => import('../pages/TwoFactorSetupPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/two-factor/challenge',
        name: 'two-factor.challenge',
        component: () => import('../pages/TwoFactorChallengePage.vue'),
        meta: { guest: true },
    },
    {
        path: '/admin',
        component: () => import('../layouts/AdminLayout.vue'),
        meta: { requiresAuth: true, role: 'admin' },
        children: [
            {
                path: '',
                redirect: { name: 'admin.dashboard' },
            },
            {
                path: 'dashboard',
                name: 'admin.dashboard',
                component: () => import('../pages/admin/DashboardPage.vue'),
                meta: {
                    titleKey: 'pages.admin.dashboard.title',
                    subtitleKey: 'pages.admin.dashboard.subtitle',
                },
            },
            {
                path: 'vehicles',
                name: 'admin.vehicles',
                component: () => import('../pages/admin/VehiclesPage.vue'),
                meta: {
                    titleKey: 'pages.admin.vehicles.title',
                    subtitleKey: 'pages.admin.vehicles.subtitle',
                },
            },
            {
                path: 'dealers',
                name: 'admin.dealers',
                component: () => import('../pages/admin/DealersPage.vue'),
                meta: {
                    titleKey: 'pages.admin.dealers.title',
                    subtitleKey: 'pages.admin.dealers.subtitle',
                },
            },
            {
                path: 'containers',
                name: 'admin.containers',
                component: () => import('../pages/admin/ContainersPage.vue'),
                meta: {
                    titleKey: 'pages.admin.containers.title',
                    subtitleKey: 'pages.admin.containers.subtitle',
                },
            },
            {
                path: 'invoices',
                name: 'admin.invoices',
                redirect: { name: 'admin.vehicles' },
            },
            {
                path: 'settings',
                name: 'admin.settings',
                component: () => import('../pages/admin/SettingsPage.vue'),
                meta: {
                    titleKey: 'pages.admin.settings.title',
                    subtitleKey: 'pages.admin.settings.subtitle',
                },
            },
            {
                path: 'notifications',
                name: 'admin.notifications',
                component: () => import('../pages/admin/NotificationsPage.vue'),
                meta: {
                    titleKey: 'pages.admin.notifications.title',
                    subtitleKey: 'pages.admin.notifications.subtitle',
                },
            },
            {
                path: 'profile',
                name: 'admin.profile',
                component: () => import('../pages/admin/AdminProfilePage.vue'),
                meta: {
                    titleKey: 'pages.admin.profile.title',
                    subtitleKey: 'pages.admin.profile.subtitle',
                },
            },
        ],
    },
    {
        path: '/dealer',
        component: () => import('../layouts/DealerLayout.vue'),
        meta: { requiresAuth: true, role: 'dealer' },
        children: [
            {
                path: '',
                redirect: { name: 'dealer.vehicles' },
            },
            {
                path: 'vehicles',
                name: 'dealer.vehicles',
                component: () => import('../pages/dealer/VehiclesPage.vue'),
                meta: { titleKey: 'pages.dealer.vehicles.title' },
            },
            {
                path: 'containers/:containerRef',
                name: 'dealer.container',
                component: () => import('../pages/dealer/ContainersPage.vue'),
                meta: { titleKey: 'pages.dealer.containers.title' },
            },
            {
                path: 'containers',
                name: 'dealer.containers',
                component: () => import('../pages/dealer/ContainersPage.vue'),
                meta: { titleKey: 'pages.dealer.containers.title' },
            },
            {
                path: 'profile',
                name: 'dealer.profile',
                component: () => import('../pages/dealer/DealerProfilePage.vue'),
                meta: { titleKey: 'pages.dealer.profile.title' },
            },
            {
                path: 'invoices',
                redirect: { name: 'dealer.vehicles' },
            },
        ],
    },
    {
        path: '/',
        redirect: () => {
            const auth = useAuthStore();

            if (! auth.isAuthenticated) {
                return { name: 'login' };
            }

            return auth.isAdmin
                ? { name: 'admin.vehicles' }
                : { name: 'dealer.vehicles' };
        },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (auth.token && ! auth.user) {
        try {
            await auth.fetchMe();
        } catch {
            auth.logout();
        }
    }

    if (auth.user) {
        const customized = Boolean(auth.user.locale_customized);
        const locale = customized
            ? auth.user.locale
            : (auth.isDealer ? 'ckb' : auth.user.locale);

        if (locale) {
            useLocaleStore().setLocale(locale, { syncServer: false });
        }
    }

    if (to.meta.guest && auth.isAuthenticated) {
        const autoLogin = (to.query.email || to.query.username) && to.query.password;

        if (!autoLogin) {
            return auth.isAdmin
                ? { name: 'admin.vehicles' }
                : { name: 'dealer.vehicles' };
        }
    }

    if (to.meta.requiresAuth && ! auth.isAuthenticated) {
        return { name: 'login' };
    }

    if (to.meta.role && auth.user?.role !== to.meta.role) {
        return auth.isAdmin
            ? { name: 'admin.vehicles' }
            : { name: 'dealer.vehicles' };
    }

    return true;
});

export default router;
