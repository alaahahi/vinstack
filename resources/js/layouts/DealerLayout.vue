<template>
    <div class="layout">
        <header class="header">
            <div class="brand">
                <div class="brand-logo-frame">
                    <img :src="themeLogo" alt="KAML KAMAL" class="brand-logo" />
                </div>
                <div class="brand-text">
                    <h2>KAML KAMAL</h2>
                    <p class="brand-tagline">Fast. Safe. Reliable.</p>
                    <p v-if="auth.user?.dealer?.company_name" class="brand-sub">
                        {{ auth.user.dealer.company_name }}
                    </p>
                </div>
            </div>

            <nav class="nav" aria-label="تنقل التاجر">
                <RouterLink
                    v-for="item in navItems"
                    :key="item.name"
                    :to="{ name: item.name }"
                    class="nav-link"
                >
                    <i :class="item.icon" />
                    <span>{{ item.label }}</span>
                </RouterLink>
            </nav>

            <div class="header-actions">
                <ThemeToggle />
                <Button
                    icon="pi pi-user"
                    :label="profileLabel"
                    severity="secondary"
                    text
                    class="profile-btn"
                    @click="toggleProfileMenu"
                    aria-haspopup="true"
                    :aria-expanded="profileMenuOpen"
                />
                <Menu ref="profileMenu" :model="profileMenuItems" popup />
            </div>
        </header>

        <main class="content">
            <div v-if="pageTitle" class="page-title-bar">
                <h1 class="page-title">{{ pageTitle }}</h1>
            </div>
            <div class="content-body">
                <RouterView />
            </div>
            <DealerFooter />
        </main>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import { useConfirm } from 'primevue/useconfirm';
import { useAuthStore } from '../stores/auth';
import { useTheme } from '../composables/useTheme';
import ThemeToggle from '../components/ThemeToggle.vue';
import DealerFooter from '../components/DealerFooter.vue';
import api from '../api/client';
import { HEARTBEAT_MS } from '../constants/presence';

const auth = useAuthStore();
const { themeLogo } = useTheme();
let heartbeatTimer = null;
const router = useRouter();
const route = useRoute();
const confirm = useConfirm();
const profileMenu = ref(null);
const profileMenuOpen = ref(false);

const navItems = [
    { name: 'dealer.vehicles', label: 'سياراتي', icon: 'pi pi-car' },
    { name: 'dealer.containers', label: 'حاوياتي', icon: 'pi pi-box' },
    { name: 'dealer.profile', label: 'الملف', icon: 'pi pi-user' },
];

const pageTitle = computed(() => route.meta.title || '');

const profileLabel = computed(() => {
    if (route.name === 'dealer.profile') {
        return 'الملف';
    }

    return '';
});

const profileMenuItems = computed(() => [
    {
        label: auth.user?.dealer?.company_name || 'حسابي',
        items: [
            {
                label: 'الملف الشخصي',
                icon: 'pi pi-user',
                command: () => router.push({ name: 'dealer.profile' }),
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
        message: 'هل تريد تسجيل الخروج من البوابة؟',
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

async function sendHeartbeat() {
    if (!auth.token) {
        return;
    }

    try {
        await api.post('/dealer/heartbeat');
    } catch {
        // ignore transient network errors; next interval retries
    }
}

onMounted(() => {
    if (auth.token) {
        sendHeartbeat();
        heartbeatTimer = setInterval(sendHeartbeat, HEARTBEAT_MS);
    }
});

onUnmounted(() => {
    if (heartbeatTimer) {
        clearInterval(heartbeatTimer);
    }
});
</script>

<style scoped>
.layout {
    min-height: 100dvh;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    overflow-x: clip;
}

.header {
    grid-column: 1 / -1;
    background: var(--dealer-header-bg);
    color: var(--dealer-header-text);
    padding: 0.85rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    position: sticky;
    top: 0;
    z-index: 20;
}

.brand {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-width: 0;
}

.brand-text {
    min-width: 0;
}

.brand h2 {
    margin: 0;
    font-size: 1.05rem;
    line-height: 1.2;
}

.brand-tagline {
    margin: 0.1rem 0 0;
    font-size: 0.68rem;
    font-weight: 500;
    letter-spacing: 0.03em;
    color: var(--dealer-header-muted);
    line-height: 1.2;
}

.brand-sub {
    margin: 0.15rem 0 0;
    font-size: 0.75rem;
    color: var(--dealer-header-muted);
    max-width: 12rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.nav {
    display: flex;
    gap: 0.25rem;
    flex: 1;
    flex-wrap: wrap;
}

.nav-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    color: var(--dealer-header-text);
    text-decoration: none;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-size: 0.88rem;
    transition: background 0.12s ease, color 0.12s ease;
}

.nav-link:hover {
    background: var(--dealer-nav-hover);
    color: #fff;
}

.nav-link.router-link-active {
    background: var(--dealer-nav-active);
    color: #fff;
    font-weight: 600;
}

.nav-link i {
    font-size: 0.95rem;
    opacity: 0.9;
}

.header-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    margin-inline-start: auto;
}

.header-actions :deep(.theme-toggle) {
    display: inline-flex;
    justify-content: center;
    align-items: center;
}

.header-actions :deep(.theme-toggle .p-button) {
    color: var(--dealer-header-text);
}

.header-actions :deep(.theme-toggle .p-button:hover) {
    background: var(--dealer-nav-hover);
    color: #fff;
}

.profile-btn :deep(.p-button-label) {
    display: none;
}

@media (min-width: 640px) {
    .profile-btn :deep(.p-button-label) {
        display: inline;
    }
}

.content {
    flex: 1;
    padding: 1rem 1.25rem 1.5rem;
    background: var(--dealer-content-bg);
    min-width: 0;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.content-body {
    flex: 1;
    min-width: 0;
}

.page-title-bar {
    margin-bottom: 0.75rem;
}

.page-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--dealer-page-title);
}

@media (max-width: 640px) {
    .header {
        padding: 0.75rem 1rem;
        isolation: isolate;
    }

    .nav {
        order: 3;
        width: 100%;
        justify-content: stretch;
        position: relative;
        z-index: 1;
    }

    .nav-link {
        flex: 1;
        justify-content: center;
        touch-action: manipulation;
        -webkit-tap-highlight-color: rgba(255, 255, 255, 0.08);
    }

    .header-actions {
        margin-inline-start: 0;
    }

    .content {
        padding: 0.85rem 1rem 1.25rem;
    }

    .brand h2 {
        font-size: 0.95rem;
    }

    .brand-logo {
        width: 4rem;
    }

    .nav-link {
        min-height: 44px;
        padding: 0.55rem 0.45rem;
        font-size: 0.8rem;
    }

    .profile-btn :deep(.p-button) {
        min-width: 44px;
        min-height: 44px;
    }

    .header-actions :deep(.theme-toggle .p-button) {
        min-width: 44px;
        min-height: 44px;
    }
}
</style>
