<template>
    <div class="options-editor">
        <div v-for="section in sections" :key="section.key" class="options-section">
            <label class="vs-form-label">{{ section.label }}</label>
            <div class="options-tags">
                <span v-for="(item, index) in model[section.key]" :key="`${section.key}-${index}`" class="option-tag">
                    {{ item }}
                    <button
                        type="button"
                        class="option-tag__remove"
                        :aria-label="`حذف ${item}`"
                        @click="remove(section.key, index)"
                    >
                        <i class="pi pi-times" />
                    </button>
                </span>
            </div>
            <div class="options-add">
                <InputText
                    v-model="drafts[section.key]"
                    class="options-add__input"
                    :placeholder="section.placeholder"
                    @keyup.enter="add(section.key)"
                />
                <Button
                    icon="pi pi-plus"
                    label="إضافة"
                    size="small"
                    outlined
                    class="btn-add"
                    @click="add(section.key)"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

const model = defineModel({
    type: Object,
    required: true,
});

const sections = [
    { key: 'loading_points', label: 'نقاط التحميل', placeholder: 'مثال: New York' },
    { key: 'shipping_destinations', label: 'وجهات الشحن', placeholder: 'مثال: Dubai' },
    { key: 'auctions', label: 'المزادات', placeholder: 'مثال: Copart' },
    { key: 'shipping_methods', label: 'طرق الشحن', placeholder: 'مثال: RoRo' },
    { key: 'delivery_types', label: 'أنواع التسليم', placeholder: 'مثال: Port' },
    { key: 'title_types', label: 'أنواع السند', placeholder: 'مثال: Clean' },
];

const drafts = reactive(
    Object.fromEntries(sections.map((s) => [s.key, ''])),
);

function add(key) {
    const value = drafts[key]?.trim();

    if (!value) {
        return;
    }

    if (!Array.isArray(model.value[key])) {
        model.value[key] = [];
    }

    if (!model.value[key].includes(value)) {
        model.value[key] = [...model.value[key], value];
    }

    drafts[key] = '';
}

function remove(key, index) {
    model.value[key] = model.value[key].filter((_, i) => i !== index);
}
</script>

<style scoped>
.options-editor {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.options-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin: 0.35rem 0 0.5rem;
    min-height: 1.5rem;
}

.option-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.2rem 0.5rem;
    border-radius: 999px;
    background: var(--admin-sidebar-active);
    color: var(--vs-text);
    font-size: 0.8rem;
}

.option-tag__remove {
    display: inline-flex;
    padding: 0;
    border: none;
    background: none;
    cursor: pointer;
    color: var(--vs-text-muted);
    line-height: 1;
}

.option-add {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.options-add__input {
    flex: 1;
}
</style>
