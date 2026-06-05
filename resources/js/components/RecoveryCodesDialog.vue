<template>

    <Dialog

        :visible="visible"

        modal

        :closable="closable"

        :draggable="false"

        :header="header"

        style="width: min(440px, 100vw)"

        @update:visible="$emit('update:visible', $event)"

    >

        <Message

            :severity="readOnly ? 'info' : 'warn'"

            :icon="readOnly ? 'pi pi-info-circle' : 'pi pi-exclamation-triangle'"

            :closable="false"

            class="banner-msg"

        >

            {{ readOnly ? adminBannerText : dealerBannerText }}

        </Message>



        <p v-if="subtitle" class="subtitle">{{ subtitle }}</p>



        <ul v-if="codes.length" class="codes-list" dir="ltr">

            <li v-for="code in codes" :key="code">{{ code }}</li>

        </ul>



        <template #footer>

            <Button

                type="button"

                label="نسخ الكل"

                icon="pi pi-copy"

                severity="secondary"

                outlined

                :disabled="!codes.length"

                @click="copyAll"

            />

            <Button

                type="button"

                :label="readOnly ? 'إغلاق' : 'تم الحفظ'"

                :icon="readOnly ? 'pi pi-times' : 'pi pi-check'"

                class="btn-cta"

                @click="close"

            />

        </template>

    </Dialog>

</template>



<script setup>

import { useToast } from 'primevue/usetoast';

import Dialog from 'primevue/dialog';

import Button from 'primevue/button';

import Message from 'primevue/message';



const dealerBannerText = 'احفظها مرة واحدة — لن تُعرض مجدداً بعد إغلاق هذه النافذة.';

const adminBannerText =

    'نسخة محفوظة للمسؤول عند تفعيل التاجر أو إعادة إنشاء الرموز. لا تشاركها إلا عند الحاجة.';



const props = defineProps({

    visible: { type: Boolean, default: false },

    codes: { type: Array, default: () => [] },

    header: { type: String, default: 'رموز الاسترداد' },

    subtitle: { type: String, default: '' },

    closable: { type: Boolean, default: true },

    readOnly: { type: Boolean, default: false },

});



const emit = defineEmits(['update:visible', 'closed']);



const toast = useToast();



function close() {

    emit('update:visible', false);

    emit('closed');

}



async function copyAll() {

    if (! props.codes.length) {

        return;

    }



    const text = props.codes.join('\n');



    try {

        await navigator.clipboard.writeText(text);

        toast.add({

            severity: 'success',

            summary: 'تم النسخ',

            detail: 'تم نسخ رموز الاسترداد إلى الحافظة.',

            life: 2500,

        });

    } catch {

        toast.add({

            severity: 'warn',

            summary: 'تعذّر النسخ',

            detail: 'انسخ الرموز يدوياً من القائمة.',

            life: 3500,

        });

    }

}

</script>



<style scoped>

.banner-msg {

    margin: 0 0 0.85rem;

}



[data-theme='dark'] .banner-msg :deep(.p-message-text) {

    color: var(--vs-zinc-100);

}



[data-theme='dark'] .banner-msg :deep(.p-message-warn) {

    background: rgba(234, 179, 8, 0.12);

    border-color: rgba(234, 179, 8, 0.35);

    color: #fde68a;

}



[data-theme='dark'] .banner-msg :deep(.p-message-info) {

    background: rgba(59, 130, 246, 0.12);

    border-color: rgba(59, 130, 246, 0.35);

    color: #bfdbfe;

}



.subtitle {

    margin: 0 0 0.65rem;

    font-size: 0.85rem;

    color: var(--vs-text-muted);

    line-height: 1.45;

}



.codes-list {

    --recovery-codes-bg: #fafafa;

    --recovery-codes-fg: var(--vs-zinc-900);

    --recovery-codes-border: #ececef;

    margin: 0;

    padding: 0.85rem 1rem;

    background: var(--recovery-codes-bg);

    border: 1px solid var(--recovery-codes-border);

    border-radius: 8px;

    list-style: none;

    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;

    font-size: 0.82rem;

    line-height: 1.65;

    color: var(--recovery-codes-fg);

    max-height: 240px;

    overflow-y: auto;

}



.codes-list li {

    padding: 0.1rem 0;

}



[data-theme='light'] .codes-list,

:root:not([data-theme='dark']) .codes-list {

    --recovery-codes-bg: #fafafa;

    --recovery-codes-fg: var(--vs-zinc-900);

    --recovery-codes-border: #ececef;

}



[data-theme='dark'] .codes-list {

    --recovery-codes-bg: #27272a;

    --recovery-codes-fg: #e4e4e7;

    --recovery-codes-border: var(--vs-zinc-700);

}

</style>

