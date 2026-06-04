<template>
    <div class="admin-layout" :class="{ 'admin-layout--sidebar-open': sidebarOpen }">
        <div
            v-if="sidebarOpen"
            class="admin-overlay"
            aria-hidden="true"
            @click="sidebarOpen = false"
        />

        <aside class="admin-sidebar" aria-label="قائمة الإدارة">
            <div class="admin-sidebar__brand">
                <div class="brand-logo-frame">
                    <img
                        :src="themeLogo"
                        alt="KAML KAMAL"
                        class="brand-logo"
                    />
                </div>
                <div class="admin-sidebar__titles">
                    <span class="admin-sidebar__name">KAML KAMAL</span>
                    <span class="admin-sidebar__tagline">Fast. Safe. Reliable.</span>
                    <span class="admin-sidebar__role">لوحة الإدارة</span>
                </div>
            </div>

            <nav class="admin-nav">
                <div v-for="group in navGroups" :key="group.label" class="admin-nav__group">
                    <span class="admin-nav__label">{{ group.label }}</span>
                    <RouterLink
                        v-for="item in group.items"
                        :key="item.name"
                        :to="{ name: item.name }"
                        class="admin-nav__link"
                        @click="sidebarOpen = false"
                    >
                        <i :class="item.icon" />
                        <span>{{ item.label }}</span>
                    </RouterLink>
                    <div v-if="group.label === 'النظام'" class="admin-nav__theme">
                        <ThemeToggle />
                    </div>
                </div>
            </nav>

            <div class="admin-sidebar__footer">
                <RouterLink
                    :to="{ name: 'admin.profile' }"
                    class="admin-sidebar__profile-link"
                    @click="sidebarOpen = false"
                >
                    <i class="pi pi-user" />
                    <span>الملف الشخصي</span>
                </RouterLink>
                <span v-if="auth.user?.name" class="admin-sidebar__user">{{ auth.user.name }}</span>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <Button
                    icon="pi pi-bars"
                    severity="secondary"
                    text
                    rounded
                    class="admin-topbar__menu"
                    aria-label="فتح القائمة"
                    @click="sidebarOpen = true"
                />

                <div class="admin-topbar__title-wrap">
                    <h1 class="admin-topbar__title">{{ pageTitle }}</h1>
                    <p v-if="pageSubtitle" class="admin-topbar__subtitle">{{ pageSubtitle }}</p>
                </div>

                <div class="admin-topbar__actions">
                    <slot name="topbar-actions" />
                    <Button
                        icon="pi pi-user"
                        :label="auth.user?.name || 'الحساب'"
                        severity="secondary"
                        text
                        class="admin-topbar__profile"
                        aria-haspopup="true"
                        :aria-expanded="profileMenuOpen"
                        @click="toggleProfileMenu"
                    />
                    <Menu ref="profileMenu" :model="profileMenuItems" popup />
                </div>
            </header>

            <main class="admin-content">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import { useConfirm } from 'primevue/useconfirm';
import { useAuthStore } from '../stores/auth';
import { useTheme } from '../composables/useTheme';
import ThemeToggle from '../components/ThemeToggle.vue';

const auth = useAuthStore();
const { themeLogo } = useTheme();
const router = useRouter();
const route = useRoute();
const confirm = useConfirm();
const profileMenu = ref(null);
const profileMenuOpen = ref(false);
const sidebarOpen = ref(false);

const navGroups = [
    {
        label: 'العمليات',
        items: [
            { name: 'admin.vehicles', label: 'السيارات', icon: 'pi pi-car' },
            { name: 'admin.containers', label: 'الحاويات', icon: 'pi pi-box' },
        ],
    },
    {
        label: 'الأعمال',
        items: [
            { name: 'admin.dealers', label: 'التجار', icon: 'pi pi-building' },
        ],
    },
    {
        label: 'النظام',
        items: [
            { name: 'admin.settings', label: 'إعدادات Vinstack', icon: 'pi pi-cog' },
        ],
    },
];

const pageTitle = computed(() => route.meta.title || 'الإدارة');
const pageSubtitle = computed(() => route.meta.subtitle || '');

const profileMenuItems = computed(() => [
    {
        label: auth.user?.email || 'مدير النظام',
        items: [
            {
                label: 'الملف الشخصي',
                icon: 'pi pi-id-card',
                command: () => router.push({ name: 'admin.profile' }),
            },
            {
                label: 'تسجيل الخروج',
                icon: 'pi pi-sign-out',
                command: confirmLogout,
            },
        ],
    },
]);

function toggleProfileMenu(event) {
    profileMenu.value.toggle(event);
    profileMenuOpen.value = ! profileMenuOpen.value;
}

function confirmLogout() {
    confirm.require({
        message: 'هل تريد تسجيل الخروج من لوحة الإدارة؟',
        header: 'تسجيل الخروج',
        icon: 'pi pi-sign-out',
        rejectLabel: 'إلغاء',
        acceptLabel: 'خروج',
        accept: () => {
            auth.logout();
            router.push({ name: 'login' });
        },
    });
}

watch(
    () => route.name,
    () => {
        sidebarOpen.value = false;
    },
);
</script>

<style scoped>
.admin-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    min-height: 100dvh;
    min-height: 100vh;
    background: var(--admin-content-bg);
    overflow-x: clip;
}

.admin-overlay {
    display: none;
}

.admin-sidebar {
    background: var(--admin-sidebar-bg);
    color: var(--admin-sidebar-text);
    display: flex;
    flex-direction: column;
    border-inline-end: 1px solid var(--admin-sidebar-border);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    z-index: 30;
}

.admin-sidebar__brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.75rem;
    padding: 1.25rem 1rem 1rem;
    border-bottom: 1px solid var(--admin-sidebar-border);
}

.admin-sidebar__brand .brand-logo-frame {
    width: 100%;
    max-width: 9.5rem;
}

.admin-sidebar__brand .brand-logo {
    width: 100%;
    max-width: 7rem;
}

.admin-sidebar__titles {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.admin-sidebar__name {
    font-weight: 700;
    font-size: 0.95rem;
    line-height: 1.2;
}

.admin-sidebar__tagline {
    font-size: 0.62rem;
    font-weight: 500;
    letter-spacing: 0.03em;
    color: var(--admin-sidebar-muted);
    margin-top: 0.1rem;
    line-height: 1.2;
}

.admin-sidebar__role {
    font-size: 0.72rem;
    color: var(--admin-sidebar-muted);
    margin-top: 0.15rem;
}

.admin-nav {
    flex: 1;
    padding: 0.75rem 0.65rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.admin-nav__label {
    display: block;
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--admin-sidebar-muted);
    padding: 0 0.5rem 0.35rem;
}

.admin-nav__link {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    color: var(--admin-sidebar-text);
    text-decoration: none;
    padding: 0.55rem 0.65rem;
    border-radius: var(--admin-radius-sm);
    font-size: 0.88rem;
    transition: background 0.12s ease, color 0.12s ease;
    margin-bottom: 0.15rem;
    touch-action: manipulation;
}

.admin-nav__link i {
    font-size: 1rem;
    opacity: 0.85;
    width: 1.25rem;
    text-align: center;
}

.admin-nav__link:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #fff;
}

.admin-nav__link.router-link-active {
    background: var(--admin-sidebar-active);
    color: var(--admin-sidebar-active-text);
    font-weight: 600;
}

.admin-sidebar__footer {
    padding: 0.85rem 1rem;
    border-top: 1px solid var(--admin-sidebar-border);
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.admin-sidebar__profile-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--admin-sidebar-text);
    text-decoration: none;
    font-size: 0.85rem;
    padding: 0.45rem 0.5rem;
    border-radius: var(--admin-radius-sm);
    transition: background 0.12s ease, color 0.12s ease;
}

.admin-sidebar__profile-link:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #fff;
}

.admin-sidebar__profile-link.router-link-active {
    background: var(--admin-sidebar-active);
    color: var(--admin-sidebar-active-text);
    font-weight: 600;
}

.admin-sidebar__user {
    font-size: 0.8rem;
    color: var(--admin-sidebar-muted);
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.admin-nav__theme {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    box-sizing: border-box;
    padding: 0.35rem 0.65rem;
    margin-top: 0.1rem;
    text-align: center;
}

.admin-nav__theme :deep(.theme-toggle) {
    display: flex;
    justify-content: center;
    width: 100%;
}

.admin-nav__theme :deep(.p-button) {
    color: var(--admin-sidebar-text);
}

.admin-nav__theme :deep(.p-button:hover) {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
}

.admin-main {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.admin-topbar {
    position: sticky;
    top: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem 1.25rem;
    background: var(--admin-surface);
    border-bottom: 1px solid var(--admin-border);
    box-shadow: 0 1px 0 rgba(0, 0, 0, 0.03);
}

.admin-topbar__menu {
    display: none;
}

.admin-topbar__title-wrap {
    flex: 1;
    min-width: 0;
}

.admin-topbar__title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--admin-topbar-title);
    line-height: 1.25;
}

.admin-topbar__subtitle {
    margin: 0.2rem 0 0;
    font-size: 0.8rem;
    color: var(--admin-topbar-subtitle);
}

.admin-topbar__actions {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
}

.admin-topbar__profile :deep(.p-button-label) {
    display: none;
}

.admin-content {
    flex: 1;
    padding: 1.15rem 1.25rem 1.75rem;
}

@media (min-width: 768px) {
    .admin-topbar__profile :deep(.p-button-label) {
        display: inline;
    }
}

@media (max-width: 900px) {
    .admin-layout {
        grid-template-columns: 1fr;
    }

    .admin-overlay {
        display: block;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 35;
    }

    .admin-sidebar {
        position: fixed;
        top: 0;
        bottom: 0;
        right: 0;
        left: auto;
        width: min(280px, 88vw);
        transform: translateX(100%);
        transition: transform 0.22s ease;
        box-shadow: -8px 0 24px rgba(0, 0, 0, 0.2);
        /* Above .admin-overlay (35) so drawer links receive touch/click */
        z-index: 40;
    }

    .admin-layout--sidebar-open .admin-sidebar {
        transform: translateX(0);
    }

    .admin-topbar__menu {
        display: inline-flex;
    }

    .admin-content {
        padding: 1rem 1rem 1.5rem;
    }
}

@media (max-width: 640px) {
    .admin-topbar {
        padding: 0.65rem 0.85rem;
        gap: 0.5rem;
    }

    .admin-topbar__title {
        font-size: 1rem;
    }

    .admin-topbar__subtitle {
        font-size: 0.75rem;
    }

    .admin-topbar__menu :deep(.p-button) {
        min-width: 44px;
        min-height: 44px;
    }

    .admin-topbar__profile :deep(.p-button) {
        min-width: 44px;
        min-height: 44px;
    }

    .admin-content {
        padding: 0.85rem 0.85rem 1.25rem;
    }

    .admin-nav__link {
        min-height: 44px;
    }
}
</style>
