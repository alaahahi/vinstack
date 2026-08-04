<template>

    <footer class="dealer-footer">

        <div class="dealer-footer__row">

            <span class="dealer-footer__group">

                <span class="dealer-footer__prefix">{{ t('footer.poweredBy') }}</span>

                <a

                    class="dealer-footer__link"

                    href="https://intellij-app.com/"

                    target="_blank"

                    rel="noopener noreferrer"

                >https://intellij-app.com/</a>

            </span>



            <template v-if="hasSupportPhone">

                <span class="dealer-footer__separator" aria-hidden="true">|</span>

                <span class="dealer-footer__group dealer-footer__support">

                    <span class="dealer-footer__support-label">{{ t('footer.support') }}</span>

                    <i class="pi pi-headphones dealer-footer__support-icon" aria-hidden="true" />

                    <a

                        class="dealer-footer__link dealer-footer__phone"

                        :href="supportWhatsAppHref"

                        target="_blank"

                        rel="noopener"

                    >{{ supportPhone }}</a>

                </span>

            </template>

        </div>

    </footer>

</template>



<script setup>

import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

import { usePublicSettings } from '../composables/usePublicSettings';

const { t } = useI18n();


const { supportPhone, hasSupportPhone, supportWhatsAppHref, loadPublicSettings } = usePublicSettings();



onMounted(loadPublicSettings);

</script>



<style scoped>

.dealer-footer {

    margin-top: auto;

    padding: 1rem 1.25rem;

    text-align: center;

    font-size: 0.8rem;

    color: var(--dealer-footer-text);

    background: var(--dealer-footer-bg);

}



.dealer-footer__row {

    display: inline-flex;

    flex-wrap: wrap;

    align-items: center;

    justify-content: center;

    gap: 0.5rem 0.65rem;

    max-width: 100%;

}



.dealer-footer__group {

    display: inline-flex;

    align-items: center;

    flex-wrap: wrap;

    justify-content: center;

    gap: 0.35rem;

}



.dealer-footer__support {

    gap: 0.4rem;

}



.dealer-footer__separator {

    opacity: 0.45;

    user-select: none;

}



.dealer-footer__prefix {

    opacity: 0.85;

}



.dealer-footer__support-label {

    opacity: 0.9;

    white-space: nowrap;

}



.dealer-footer__support-icon {

    font-size: 0.95em;

    opacity: 0.85;

}



.dealer-footer__link {

    color: var(--dealer-footer-link);

    text-decoration: none;

    transition: color 0.15s ease, opacity 0.15s ease;

}



.dealer-footer__link:hover {

    color: var(--dealer-footer-link-hover);

    text-decoration: underline;

}



.dealer-footer__phone {

    direction: ltr;

    unicode-bidi: isolate;

}

@media (max-width: 640px) {

    .dealer-footer {

        font-size: 0.75rem;

        padding: 0.85rem 1rem;

    }

    .dealer-footer__row {

        flex-direction: column;

        gap: 0.6rem;

    }

    .dealer-footer__separator {

        display: none;

    }

    .dealer-footer__support-label {

        white-space: normal;

        text-align: center;

    }

}

</style>

