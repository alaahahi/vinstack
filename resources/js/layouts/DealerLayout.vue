<template>
    <div class="layout">
        <header class="header">
            <div class="header-top">
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

                <div ref="headerMenuWrap" class="header-menu-wrap">
                    <button
                        type="button"
                        class="header-menu-trigger"
                        aria-haspopup="menu"
                        :aria-expanded="headerMenuOpen"
                        @click.stop="toggleHeaderMenu"
                    >
                        <i class="pi pi-cog" aria-hidden="true" />
                        <span class="header-menu-trigger__label">الخيارات</span>
                        <i class="pi pi-ellipsis-v" aria-hidden="true" />
                    </button>

                    <div
                        v-if="headerMenuOpen"
                        class="header-menu-panel"
                        role="menu"
                        aria-label="خيارات البوابة"
                        @click.stop
                    >
                        <div class="header-menu-account">
                            <strong class="header-menu-account__name">{{ dealerCompanyName }}</strong>
                            <span class="header-menu-account__caption">{{ t('navigation.account') }}</span>
                        </div>

                        <RouterLink
                            :to="{ name: 'dealer.profile' }"
                            class="header-menu-link"
                            role="menuitem"
                            @click="closeHeaderMenu"
                        >
                            <i class="pi pi-user" aria-hidden="true" />
                            <span>{{ t('navigation.profile') }}</span>
                        </RouterLink>

                        <div class="header-menu-divider" />

                        <div class="header-menu-control">
                            <span class="header-menu-control__label">{{ t('locale.label') }}</span>
                            <LocaleSwitcher />
                        </div>

                        <div class="header-menu-control">
                            <span class="header-menu-control__label">المظهر</span>
                            <ThemeToggle />
                        </div>

                        <div class="header-menu-divider" />

                        <button
                            type="button"
                            class="header-menu-link header-menu-link--danger"
                            role="menuitem"
                            @click="confirmLogout"
                        >
                            <i class="pi pi-sign-out" aria-hidden="true" />
                            <span>{{ t('actions.logout') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <nav class="nav" :aria-label="t('navigation.dealerNav')">
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
        </header>

        <main class="content">
            <div v-if="pageTitle" class="page-title-bar">
                <h1 class="page-title">{{ pageTitle }}</h1>
            </div>
            <div class="content-body">
                <RouterView />
            </div>
        </main>

        <DealerFooter />
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import { useAuthStore } from '../stores/auth';
import { useTheme } from '../composables/useTheme';
import LocaleSwitcher from '../components/LocaleSwitcher.vue';
import ThemeToggle from '../components/ThemeToggle.vue';
import DealerFooter from '../components/DealerFooter.vue';
import api from '../api/client';
import { HEARTBEAT_MS } from '../constants/presence';

const { t } = useI18n();
const auth = useAuthStore();
const { themeLogo } = useTheme();
let heartbeatTimer = null;
const router = useRouter();
const route = useRoute();
const confirm = useConfirm();
const headerMenuWrap = ref(null);
const headerMenuOpen = ref(false);

const navItems = computed(() => [
    { name: 'dealer.vehicles', label: t('navigation.vehicles'), icon: 'pi pi-car' },
    { name: 'dealer.auctions', label: t('navigation.auctions'), icon: 'pi pi-shopping-bag' },
    { name: 'dealer.containers', label: t('navigation.containers'), icon: 'pi pi-box' },
]);

const pageTitle = computed(() => (
    route.meta.titleKey ? t(route.meta.titleKey) : (route.meta.title || '')
));

const dealerCompanyName = computed(() => auth.user?.dealer?.company_name || t('navigation.myAccount'));

function toggleHeaderMenu() {
    headerMenuOpen.value = ! headerMenuOpen.value;
}

function closeHeaderMenu() {
    headerMenuOpen.value = false;
}

function onDocumentClick(event) {
    if (! headerMenuOpen.value) {
        return;
    }

    if (headerMenuWrap.value && ! headerMenuWrap.value.contains(event.target)) {
        closeHeaderMenu();
    }
}

function onDocumentKeydown(event) {
    if (event.key === 'Escape') {
        closeHeaderMenu();
    }
}

function confirmLogout() {
    closeHeaderMenu();
    confirm.require({
        message: t('dealer.logoutPrompt'),
        header: t('actions.logout'),
        icon: 'pi pi-sign-out',
        rejectLabel: t('actions.cancel'),
        acceptLabel: t('actions.logout'),
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
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onDocumentKeydown);

    if (auth.token) {
        sendHeartbeat();
        heartbeatTimer = setInterval(sendHeartbeat, HEARTBEAT_MS);
    }
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onDocumentKeydown);

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
    padding: 0.8rem 1.25rem 0.7rem;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 0.7rem;
    position: sticky;
    top: 0;
    z-index: 20;
}

.header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    min-width: 0;
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

.header-menu-wrap {
    position: relative;
    flex-shrink: 0;
}

.header-menu-trigger {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 0.7rem;
    border: 1px solid rgb(255 255 255 / 14%);
    border-radius: 999px;
    background: rgb(255 255 255 / 0.06);
    color: var(--dealer-header-text);
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.header-menu-trigger:hover {
    background: var(--dealer-nav-hover);
    border-color: rgb(255 255 255 / 18%);
}

.header-menu-trigger__label {
    font-size: 0.8rem;
    font-weight: 600;
}

.header-menu-panel {
    position: absolute;
    top: calc(100% + 0.55rem);
    inset-inline-end: 0;
    width: min(22rem, calc(100vw - 2rem));
    padding: 0.65rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    border-radius: 16px;
    background: color-mix(in srgb, var(--dealer-header-bg) 92%, #0b1220 8%);
    box-shadow: 0 18px 40px rgb(15 23 42 / 0.28);
    backdrop-filter: blur(18px);
}

.header-menu-account {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    padding: 0.15rem 0.15rem 0.5rem;
}

.header-menu-account__name {
    font-size: 0.88rem;
}

.header-menu-account__caption {
    font-size: 0.72rem;
    color: var(--dealer-header-muted);
}

.header-menu-divider {
    height: 1px;
    margin: 0.45rem 0;
    background: rgb(255 255 255 / 0.08);
}

.header-menu-link {
    width: 100%;
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.65rem 0.7rem;
    border: 0;
    border-radius: 12px;
    background: transparent;
    color: var(--dealer-header-text);
    text-decoration: none;
    font: inherit;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
}

.header-menu-link:hover {
    background: var(--dealer-nav-hover);
    color: #fff;
}

.header-menu-link--danger {
    color: #fecaca;
}

.header-menu-control {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.45rem 0.2rem;
}

.header-menu-control__label {
    font-size: 0.76rem;
    color: var(--dealer-header-muted);
    white-space: nowrap;
}

.header-menu-panel :deep(.locale-switcher) {
    gap: 0.35rem;
}

.header-menu-panel :deep(.locale-switcher__label) {
    display: none;
}

.header-menu-panel :deep(.locale-switcher__select) {
    min-height: 2rem;
    padding: 0.35rem 0.6rem;
    font-size: 0.78rem;
}

.nav {
    display: flex;
    gap: 0.45rem;
    width: 100%;
    flex-wrap: nowrap;
}

.nav-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    color: var(--dealer-header-text);
    text-decoration: none;
    justify-content: center;
    flex: 1 1 0;
    padding: 0.55rem 0.8rem;
    border: 1px solid rgb(255 255 255 / 0.08);
    border-radius: 999px;
    font-size: 0.84rem;
    font-weight: 600;
    background: rgb(255 255 255 / 0.04);
    transition: background 0.12s ease, color 0.12s ease, border-color 0.12s ease;
}

.nav-link:hover {
    background: var(--dealer-nav-hover);
    color: #fff;
}

.nav-link.router-link-active {
    background: var(--dealer-nav-active);
    color: #fff;
    border-color: rgb(255 255 255 / 0.14);
}

.nav-link i {
    font-size: 0.9rem;
    opacity: 0.9;
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
        padding: 0.75rem 1rem 0.65rem;
        isolation: isolate;
    }

    .header-top {
        align-items: flex-start;
    }

    .nav {
        gap: 0.4rem;
    }

    .nav-link {
        justify-content: center;
        touch-action: manipulation;
        -webkit-tap-highlight-color: rgba(255, 255, 255, 0.08);
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

    .header-menu-trigger {
        min-height: 44px;
        padding-inline: 0.65rem;
    }

    .header-menu-panel {
        width: min(20rem, calc(100vw - 2rem));
    }

    .header-menu-control {
        align-items: flex-start;
        flex-direction: column;
        gap: 0.45rem;
    }

    .nav-link {
        min-height: 44px;
        padding: 0.52rem 0.45rem;
        font-size: 0.8rem;
    }
}
</style>
