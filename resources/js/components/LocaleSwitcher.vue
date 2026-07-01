<template>
    <label class="locale-switcher">
        <span class="locale-switcher__label">{{ t('locale.label') }}</span>
        <select
            :value="locale"
            class="locale-switcher__select"
            :aria-label="t('locale.label')"
            @change="setLocale($event.target.value)"
        >
            <option
                v-for="item in availableLocales"
                :key="item.code"
                :value="item.code"
            >
                {{ item.nativeName }}
            </option>
        </select>
    </label>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { useLocale } from '../composables/useLocale';

const { t } = useI18n();
const { locale, availableLocales, setLocale } = useLocale();
</script>

<style scoped>
.locale-switcher {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.locale-switcher__label {
    font-size: 0.8rem;
    white-space: nowrap;
}

.locale-switcher__select {
    min-height: 2.4rem;
    padding: 0.45rem 0.7rem;
    border: 1px solid var(--p-content-border-color, rgba(148, 163, 184, 0.4));
    border-radius: 999px;
    background: var(--p-content-background, rgba(255, 255, 255, 0.08));
    color: inherit;
    font: inherit;
}
</style>
