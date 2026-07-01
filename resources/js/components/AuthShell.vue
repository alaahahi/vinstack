<template>
    <div class="login-page">
        <aside class="login-brand-panel" aria-label="KAML KAMAL">
            <div class="login-brand-panel__glow" aria-hidden="true" />

            <div class="login-brand-panel__content">
                <div class="login-logo-frame login-logo-frame--animate">
                    <img :src="themeLogo" alt="KAML KAMAL" class="login-logo" />
                </div>

                <h1 class="login-company">KAML KAMAL</h1>
                <p class="login-tagline">{{ t('common.brandTagline') }}</p>
                <p class="login-promo">
                    {{ t('auth.shell.promo') }}
                </p>

                <div class="login-route" aria-hidden="true">
                    <div class="login-route__track">
                        <span
                            v-for="n in 5"
                            :key="n"
                            class="login-route__dot"
                            :style="{ '--dot-i': n }"
                        />
                        <span class="login-route__vehicle">
                            <i class="pi pi-truck" />
                        </span>
                    </div>
                    <p class="login-route__caption">{{ t('auth.shell.routeCaption') }}</p>
                </div>
            </div>
        </aside>

        <section class="login-form-panel">
            <header class="login-form-panel__header">
                <div class="login-mobile-brand">
                    <div class="login-logo-frame login-logo-frame--compact login-logo-frame--animate">
                        <img :src="themeLogo" alt="KAML KAMAL" class="login-logo" />
                    </div>
                    <div class="login-mobile-brand__text">
                        <span class="login-mobile-brand__name">KAML KAMAL</span>
                        <span class="login-mobile-brand__tag">{{ t('common.brandTagline') }}</span>
                    </div>
                </div>
                <LocaleSwitcher />
                <ThemeToggle class="login-theme-toggle" />
            </header>

            <div class="login-form-panel__body">
                <slot />
            </div>

            <DealerFooter class="login-footer" />
        </section>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { useTheme } from '../composables/useTheme';
import LocaleSwitcher from './LocaleSwitcher.vue';
import ThemeToggle from './ThemeToggle.vue';
import DealerFooter from './DealerFooter.vue';

const { t } = useI18n();
const { themeLogo } = useTheme();
</script>

<style scoped>
.login-page {
    min-height: 100dvh;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: var(--login-panel-bg);
    overflow-x: hidden;
}

.login-brand-panel {
    display: none;
}

.login-form-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: max(1rem, env(safe-area-inset-top, 0px)) max(1.25rem, env(safe-area-inset-right, 0px))
        max(1.5rem, env(safe-area-inset-bottom, 0px)) max(1rem, env(safe-area-inset-left, 0px));
    min-height: 100dvh;
    min-height: 100vh;
    box-sizing: border-box;
    position: relative;
    isolation: isolate;
}

.login-form-panel::before {
    content: '';
    position: absolute;
    inset: 0;
    z-index: -1;
    pointer-events: none;
    background: var(--login-mobile-form-gradient);
    opacity: 1;
}

.login-form-panel__header {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1.25rem;
    position: relative;
}

.login-mobile-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.85rem;
    min-width: 0;
    flex: 1;
    margin-inline: auto;
    padding-inline: 2.75rem;
}

.login-theme-toggle {
    position: absolute;
    inset-inline-end: 0;
    top: 0;
}

.login-mobile-brand__text {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.login-mobile-brand__name {
    font-weight: 700;
    font-size: 1rem;
    letter-spacing: 0.04em;
    color: var(--login-form-title);
}

.login-mobile-brand__tag {
    font-size: 0.72rem;
    color: var(--login-form-muted);
}

.login-theme-toggle :deep(.p-button) {
    color: var(--login-form-muted);
}

.login-form-panel__body {
    flex: 1;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    width: 100%;
    min-height: 0;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0.25rem 0.15rem calc(1.5rem + env(safe-area-inset-bottom, 0px));
}

@media (min-width: 900px) {
    .login-form-panel__body {
        align-items: center;
        overflow-y: visible;
        padding-bottom: 0;
    }
}

.login-footer {
    padding-top: 0.5rem;
}

@media (min-width: 900px) {
    .login-page {
        flex-direction: row;
    }

    .login-brand-panel {
        display: flex;
        flex: 1;
        position: relative;
        align-items: center;
        justify-content: center;
        padding: 2.5rem 2rem;
        background: var(--login-brand-gradient);
        color: var(--login-brand-text);
        overflow: hidden;
    }

    .login-brand-panel__glow {
        position: absolute;
        inset: -20% 10% auto -30%;
        width: 70%;
        height: 55%;
        background: radial-gradient(ellipse at center, var(--login-brand-glow) 0%, transparent 70%);
        pointer-events: none;
        animation: loginGlowPulse 8s ease-in-out infinite;
    }

    .login-brand-panel__content {
        position: relative;
        z-index: 1;
        max-width: 28rem;
        text-align: center;
    }

    .login-company {
        margin: 1.25rem 0 0.35rem;
        font-size: 1.65rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        line-height: 1.2;
    }

    .login-tagline {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 500;
        letter-spacing: 0.06em;
        color: var(--login-brand-muted);
    }

    .login-promo {
        margin: 1.1rem 0 0;
        font-size: 0.92rem;
        line-height: 1.55;
        color: var(--login-brand-muted);
    }

    .login-route {
        margin-top: 2.25rem;
    }

    .login-route__track {
        position: relative;
        height: 3px;
        margin: 0 0.5rem;
        background: var(--login-route-track);
        border-radius: 999px;
    }

    .login-route__dot {
        position: absolute;
        top: 50%;
        width: 10px;
        height: 10px;
        margin-top: -5px;
        margin-inline-start: -5px;
        border-radius: 50%;
        background: var(--login-route-dot);
        box-shadow: 0 0 0 3px var(--login-route-dot-ring);
        left: calc((var(--dot-i) - 1) * 25%);
        animation: loginDotPulse 2.4s ease-in-out infinite;
        animation-delay: calc((var(--dot-i) - 1) * 0.35s);
    }

    .login-route__vehicle {
        position: absolute;
        top: 50%;
        left: 0;
        transform: translate(-50%, -58%);
        color: var(--login-accent);
        font-size: 1.15rem;
        animation: loginRouteDrive 6s ease-in-out infinite;
    }

    .login-route__caption {
        margin: 1rem 0 0;
        font-size: 0.75rem;
        color: var(--login-brand-subtle);
        opacity: 0.85;
    }

    .login-form-panel {
        flex: 0 0 min(480px, 42vw);
        max-width: 520px;
        border-inline-start: 1px solid var(--login-panel-border);
    }

    .login-mobile-brand {
        display: none;
    }

    .login-form-panel__header {
        justify-content: flex-end;
        margin-bottom: 0;
    }

    .login-theme-toggle {
        position: static;
    }

    .login-form-panel__body {
        padding: 2rem 0;
    }
}

@media (max-width: 640px) {
    .login-form-panel {
        padding: max(0.75rem, env(safe-area-inset-top, 0px)) max(1rem, env(safe-area-inset-right, 0px))
            max(1.25rem, env(safe-area-inset-bottom, 0px)) max(1rem, env(safe-area-inset-left, 0px));
    }

    .login-form-panel__header {
        margin-bottom: 1rem;
        padding-bottom: 0.65rem;
        border-bottom: 1px solid var(--login-panel-border);
    }

    .login-mobile-brand {
        gap: 0.7rem;
    }

    .login-logo-frame--compact {
        padding: 0.4rem;
        border-radius: 0.65rem;
        box-shadow: var(--login-logo-shadow), 0 2px 12px rgba(0, 0, 0, 0.06);
    }

    .login-form-panel__body {
        align-items: flex-start;
        padding-top: 0.35rem;
    }

    .login-footer {
        padding-top: 0.75rem;
        font-size: 0.8rem;
    }
}

@media (min-width: 900px) {
    .login-form-panel::before {
        display: none;
    }
}
</style>
