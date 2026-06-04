import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

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
                redirect: { name: 'admin.vehicles' },
            },
            {
                path: 'vehicles',
                name: 'admin.vehicles',
                component: () => import('../pages/admin/VehiclesPage.vue'),
                meta: {
                    title: 'السيارات',
                    subtitle: 'إدارة المخزون والإسناد للتجار',
                },
            },
            {
                path: 'dealers',
                name: 'admin.dealers',
                component: () => import('../pages/admin/DealersPage.vue'),
                meta: {
                    title: 'التجار',
                    subtitle: 'إنشاء حسابات تجار وعرض الشركات المسجّلة',
                },
            },
            {
                path: 'containers',
                name: 'admin.containers',
                component: () => import('../pages/admin/ContainersPage.vue'),
                meta: {
                    title: 'الحاويات',
                    subtitle: 'بيانات Vinstack — CNTR / BKG / SEAL والمسار',
                },
            },
            {
                path: 'invoices',
                name: 'admin.invoices',
                component: () => import('../pages/admin/InvoicesPage.vue'),
                meta: {
                    title: 'الفواتير',
                    subtitle: 'عرض فقط — البيانات من Vinstack API',
                },
            },
            {
                path: 'settings',
                name: 'admin.settings',
                component: () => import('../pages/admin/SettingsPage.vue'),
                meta: { title: 'إعدادات Vinstack', subtitle: 'اتصال API والمزامنة' },
            },
            {
                path: 'profile',
                name: 'admin.profile',
                component: () => import('../pages/admin/AdminProfilePage.vue'),
                meta: { title: 'الملف الشخصي', subtitle: 'تحديث بيانات حساب المدير' },
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
                meta: { title: 'سياراتي' },
            },
            {
                path: 'containers',
                name: 'dealer.containers',
                component: () => import('../pages/dealer/ContainersPage.vue'),
                meta: { title: 'حاوياتي' },
            },
            {
                path: 'profile',
                name: 'dealer.profile',
                component: () => import('../pages/dealer/DealerProfilePage.vue'),
                meta: { title: 'الملف الشخصي' },
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

    if (to.meta.guest && auth.isAuthenticated) {
        return auth.isAdmin
            ? { name: 'admin.vehicles' }
            : { name: 'dealer.vehicles' };
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
