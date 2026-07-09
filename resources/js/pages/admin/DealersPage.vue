<template>
    <div class="admin-page">
        <AdminPageHeader>
            <template #actions>
                <Button :label="t('dealers.newDealer')" icon="pi pi-plus" class="btn-add" @click="showForm = true" />
                <Button icon="pi pi-refresh" :label="t('actions.refresh')" outlined :loading="loading" @click="load()" />
            </template>
        </AdminPageHeader>

        <section class="admin-surface dealers-panel">
            <div v-if="loading && !dealers.length" class="dealers-loading">
                <ProgressSpinner style="width: 36px; height: 36px" />
            </div>

            <div v-else-if="!dealers.length" class="admin-empty">
                <i class="pi pi-building" />
                <p class="admin-empty-title">{{ t('dealers.emptyTitle') }}</p>
                <p class="admin-empty-hint">{{ t('dealers.emptyHint') }}</p>
                <Button :label="t('dealers.newDealer')" icon="pi pi-plus" class="btn-add" @click="showForm = true" />
            </div>

            <ul v-else class="dealer-list" role="list">
                <li v-for="dealer in dealers" :key="dealer.id" class="dealer-card">
                    <div class="dealer-card__icon" aria-hidden="true">
                        <i class="pi pi-building" />
                    </div>
                    <div class="dealer-card__body">
                        <h3 class="dealer-card__name">{{ dealer.company_name }}</h3>
                        <p class="dealer-card__user">
                            <i class="pi pi-user" />
                            {{ dealer.user?.name || '—' }}
                            <span class="dealer-card__sep">·</span>
                            {{ dealer.user?.email || '—' }}
                        </p>
                        <p class="dealer-card__presence">
                            <span
                                class="presence-badge"
                                :class="isDealerOnline(dealer) ? 'presence-badge--online' : 'presence-badge--offline'"
                            >
                                <i :class="isDealerOnline(dealer) ? 'pi pi-circle-fill' : 'pi pi-clock'" />
                                {{ presenceLabel(dealer) }}
                            </span>
                            <label class="locale-select-wrap">
                                <span class="locale-select-label">
                                    <i class="pi pi-language" />
                                    {{ t('dealers.notificationLocale') }}
                                </span>
                                <Select
                                    :model-value="notificationLocaleValue(dealer)"
                                    :options="notificationLocaleOptions"
                                    option-label="label"
                                    option-value="value"
                                    size="small"
                                    class="locale-select"
                                    :disabled="savingLocaleDealerId === dealer.id"
                                    :loading="savingLocaleDealerId === dealer.id"
                                    @update:model-value="(value) => updateNotificationLocale(dealer, value)"
                                />
                            </label>
                        </p>
                    </div>
                    <div v-if="dealer.phone" class="dealer-card__meta">
                        <i class="pi pi-phone" />
                        <span>{{ dealer.phone }}</span>
                    </div>
                    <Button
                        icon="pi pi-copy"
                        :label="t('dealers.copyLogin')"
                        severity="secondary"
                        outlined
                        size="small"
                        :loading="copyingDealerId === dealer.id"
                        @click="copyLoginInfo(dealer)"
                    />
                    <Button
                        v-if="dealer.two_factor_enabled"
                        icon="pi pi-key"
                        :label="t('dealers.viewRecoveryCodes')"
                        severity="secondary"
                        outlined
                        size="small"
                        :disabled="!dealer.has_recovery_codes_archive"
                        :loading="loadingRecoveryDealerId === dealer.id"
                        @click="viewRecoveryCodes(dealer)"
                    />
                    <Button
                        icon="pi pi-pencil"
                        :label="t('actions.edit')"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="openEdit(dealer)"
                    />
                    <Button
                        icon="pi pi-trash"
                        :label="t('dealers.delete')"
                        severity="danger"
                        outlined
                        size="small"
                        :disabled="dealer.vehicles_count > 0"
                        :title="
                            dealer.vehicles_count > 0
                                ? t('dealers.deleteBlocked')
                                : t('dealers.deleteTitle')
                        "
                        :loading="deletingDealerId === dealer.id"
                        @click="confirmDeleteDealer(dealer)"
                    />
                </li>
            </ul>
        </section>

        <Dialog v-model:visible="showForm" :header="t('dealers.createHeader')" modal style="width: min(480px, 100vw)">
            <div class="form-grid">
                <div class="field">
                    <label class="vs-form-label">{{ t('dealers.name') }}</label>
                    <InputText v-model="form.name" class="w-full" />
                </div>
                <div class="field">
                    <label class="vs-form-label">{{ t('dealers.email') }}</label>
                    <InputText v-model="form.email" type="email" class="w-full" />
                </div>
                <div class="field">
                    <label class="vs-form-label">{{ t('dealers.password') }}</label>
                    <Password v-model="form.password" toggle-mask input-class="w-full" class="w-full" />
                </div>
                <div class="field">
                    <label class="vs-form-label">{{ t('dealers.company') }}</label>
                    <InputText v-model="form.company_name" class="w-full" />
                </div>
                <div class="field">
                    <label class="vs-form-label">{{ t('dealers.phone') }}</label>
                    <InputText v-model="form.phone" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button :label="t('actions.cancel')" text @click="showForm = false" />
                <Button :label="t('actions.save')" class="btn-cta" :loading="saving" @click="save" />
            </template>
        </Dialog>

        <Dialog
            v-model:visible="showEdit"
            :header="t('dealers.editHeader')"
            modal
            style="width: min(480px, 100vw)"
        >
            <div class="form-grid">
                <div class="field">
                    <label class="vs-form-label">{{ t('dealers.name') }}</label>
                    <InputText v-model="editForm.name" class="w-full" />
                </div>
                <div class="field">
                    <label class="vs-form-label">{{ t('dealers.company') }}</label>
                    <InputText v-model="editForm.company_name" class="w-full" />
                </div>
                <div class="field">
                    <label class="vs-form-label">{{ t('dealers.phone') }}</label>
                    <InputText v-model="editForm.phone" class="w-full" />
                </div>
                <div class="field">
                    <label class="vs-form-label">{{ t('dealers.newPasswordOptional') }}</label>
                    <Password v-model="editForm.password" toggle-mask input-class="w-full" class="w-full" />
                    <small class="field-hint">{{ t('dealers.passwordHint') }}</small>
                </div>
            </div>
            <template #footer>
                <Button :label="t('actions.cancel')" text @click="showEdit = false" />
                <Button :label="t('dealers.saveChanges')" class="btn-cta" :loading="editing" @click="saveEdit" />
            </template>
        </Dialog>

        <RecoveryCodesDialog
            v-model:visible="recoveryDialogVisible"
            :codes="recoveryCodes"
            :subtitle="recoverySubtitle"
            read-only
            :header="t('dealers.recoveryHeader')"
        />
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, reactive, ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import ProgressSpinner from 'primevue/progressspinner';
import AdminPageHeader from '../../components/AdminPageHeader.vue';
import RecoveryCodesDialog from '../../components/RecoveryCodesDialog.vue';
import api from '../../api/client';
import { ADMIN_POLL_MS } from '../../constants/presence';
import { SUPPORTED_LOCALES } from '../../constants/locales';
import { formatDealerLoginCopy } from '../../utils/dealerLoginCopy';
import { formatLastSeenLabel, isDealerOnline } from '../../utils/lastSeen';

const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();
const dealers = ref([]);
const loading = ref(false);
const showForm = ref(false);
const showEdit = ref(false);
const saving = ref(false);
const editing = ref(false);
const editingDealerId = ref(null);
const recoveryDialogVisible = ref(false);
const recoveryCodes = ref([]);
const recoverySubtitle = ref('');
const loadingRecoveryDealerId = ref(null);
const deletingDealerId = ref(null);
const copyingDealerId = ref(null);
const savingLocaleDealerId = ref(null);
const loginUrl = ref('');
let pollTimer = null;

const notificationLocaleOptions = computed(() => [
    { value: 'default', label: t('dealers.notificationLocaleDefault') },
    ...SUPPORTED_LOCALES.map((entry) => ({
        value: entry.code,
        label: entry.nativeName,
    })),
]);

const form = reactive({
    name: '',
    email: '',
    password: '',
    company_name: '',
    phone: '',
});

const editForm = reactive({
    name: '',
    company_name: '',
    phone: '',
    password: '',
});

function presenceLabel(dealer) {
    return isDealerOnline(dealer) ? t('dealers.online') : formatLastSeenLabel(dealer);
}

function notificationLocaleValue(dealer) {
    if (! dealer.notification_locale_customized) {
        return 'default';
    }

    return dealer.notification_locale || dealer.locale || 'ckb';
}

async function updateNotificationLocale(dealer, locale) {
    if (! dealer?.id || notificationLocaleValue(dealer) === locale) {
        return;
    }

    savingLocaleDealerId.value = dealer.id;

    try {
        const { data } = await api.patch(`/admin/dealers/${dealer.id}/notification-locale`, {
            locale,
        });
        const index = dealers.value.findIndex((entry) => entry.id === dealer.id);

        if (index !== -1) {
            dealers.value[index] = data.data;
        }

        toast.add({
            severity: 'success',
            summary: t('dealers.notificationLocaleUpdated'),
            life: 2500,
        });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || t('dealers.notificationLocaleUpdateFailed'),
            life: 4000,
        });
    } finally {
        savingLocaleDealerId.value = null;
    }
}

async function load({ silent = false } = {}) {
    if (!silent) {
        loading.value = true;
    }

    try {
        const { data } = await api.get('/admin/dealers');
        dealers.value = [...(data.data ?? [])].sort((left, right) => Number(left.id) - Number(right.id));
        loginUrl.value = data.meta?.login_url || loginUrl.value;
    } finally {
        if (!silent) {
            loading.value = false;
        }
    }
}

function formatArchivedAt(iso) {
    if (!iso) {
        return '';
    }

    try {
        return new Intl.DateTimeFormat('ar-IQ', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(iso));
    } catch {
        return iso;
    }
}

function resolveLoginUrl(dealer) {
    return loginUrl.value || dealer.login_url || `${window.location.origin}/login`;
}

async function copyLoginInfo(dealer, passwordOverride) {
    const email = dealer.user?.email?.trim();
    const phone = dealer.phone?.trim();

    if (!email && !phone) {
        toast.add({
            severity: 'warn',
            summary: t('dealers.noData'),
            detail: t('dealers.noLoginData'),
            life: 4000,
        });

        return;
    }

    copyingDealerId.value = dealer.id;

    const text = formatDealerLoginCopy(dealer, resolveLoginUrl(dealer), passwordOverride);

    try {
        await navigator.clipboard.writeText(text);
        toast.add({
            severity: 'success',
            summary: t('dealers.copied'),
            detail: t('dealers.copiedDetail'),
            life: 3000,
        });
    } catch {
        toast.add({
            severity: 'warn',
            summary: t('dealers.copyFailed'),
            detail: t('dealers.copyFailedDetail'),
            life: 4000,
        });
    } finally {
        copyingDealerId.value = null;
    }
}

async function viewRecoveryCodes(dealer) {
    if (!dealer.has_recovery_codes_archive) {
        toast.add({
            severity: 'warn',
            summary: t('dealers.noRecoveryCodes'),
            detail: t('dealers.noRecoveryCodesDetail'),
            life: 4500,
        });

        return;
    }

    loadingRecoveryDealerId.value = dealer.id;

    try {
        const { data } = await api.get(`/admin/dealers/${dealer.id}/recovery-codes`);
        recoveryCodes.value = data.recovery_codes ?? [];
        recoverySubtitle.value = data.archived_at
            ? t('dealers.lastSaved', { date: formatArchivedAt(data.archived_at) })
            : '';
        recoveryDialogVisible.value = recoveryCodes.value.length > 0;
    } catch (e) {
        toast.add({
            severity: 'warn',
            summary: t('dealers.noRecoveryCodes'),
            detail:
                e.response?.data?.message ||
                t('dealers.noRecoveryCodesDetail'),
            life: 4500,
        });
    } finally {
        loadingRecoveryDealerId.value = null;
    }
}

function openEdit(dealer) {
    editingDealerId.value = dealer.id;
    Object.assign(editForm, {
        name: dealer.user?.name || '',
        company_name: dealer.company_name || '',
        phone: dealer.phone || '',
        password: '',
    });
    showEdit.value = true;
}

async function save() {
    saving.value = true;

    const createdPassword = form.password;

    try {
        const { data } = await api.post('/admin/dealers', { ...form });
        showForm.value = false;
        toast.add({ severity: 'success', summary: t('dealers.created'), life: 3000 });
        Object.assign(form, {
            name: '',
            email: '',
            password: '',
            company_name: '',
            phone: '',
        });
        await load();

        const created = data.data;

        if (created) {
            if (data.meta?.login_url) {
                loginUrl.value = data.meta.login_url;
            }

            const passwordForCopy =
                data.login_credentials?.password?.trim() || createdPassword?.trim() || '';

            await copyLoginInfo(created, passwordForCopy);

            if (data.credentials_notification?.ok) {
                toast.add({
                    severity: 'success',
                    summary: t('dealers.copied'),
                    detail: data.credentials_notification.message,
                    life: 3500,
                });
            } else if (data.credentials_notification?.message) {
                toast.add({
                    severity: 'warn',
                    summary: t('common.error'),
                    detail: data.credentials_notification.message,
                    life: 4500,
                });
            }
        }
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || t('dealers.saveFailed'),
            life: 4000,
        });
    } finally {
        saving.value = false;
    }
}

function confirmDeleteDealer(dealer) {
    if (dealer.vehicles_count > 0) {
        return;
    }

    confirm.require({
        message: t('dealers.deleteConfirm', { name: dealer.company_name }),
        header: t('dealers.deleteHeader'),
        icon: 'pi pi-trash',
        rejectLabel: t('actions.cancel'),
        acceptLabel: t('dealers.delete'),
        acceptClass: 'p-button-danger',
        accept: () => deleteDealer(dealer),
    });
}

async function deleteDealer(dealer) {
    deletingDealerId.value = dealer.id;

    try {
        const { data } = await api.delete(`/admin/dealers/${dealer.id}`);
        toast.add({
            severity: 'success',
            summary: t('dealers.deleted'),
            detail: data.message || t('dealers.deleted'),
            life: 3000,
        });
        await load({ silent: true });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || t('dealers.deleteFailed'),
            life: 4000,
        });
    } finally {
        deletingDealerId.value = null;
    }
}

async function saveEdit() {
    if (!editingDealerId.value) {
        return;
    }

    editing.value = true;

    const payload = {
        name: editForm.name,
        company_name: editForm.company_name,
        phone: editForm.phone,
    };

    if (editForm.password?.trim()) {
        payload.password = editForm.password;
    }

    try {
        const { data } = await api.put(`/admin/dealers/${editingDealerId.value}`, payload);
        showEdit.value = false;
        toast.add({
            severity: 'success',
            summary: t('dealers.updated'),
            detail: data.message || t('dealers.updated'),
            life: 3000,
        });
        await load({ silent: true });
    } catch (e) {
        const errors = e.response?.data?.errors;
        const firstError = errors ? Object.values(errors).flat()[0] : null;

        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: firstError || e.response?.data?.message || t('dealers.updateFailed'),
            life: 4000,
        });
    } finally {
        editing.value = false;
    }
}

onMounted(() => {
    load();
    pollTimer = setInterval(() => load({ silent: true }), ADMIN_POLL_MS);
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
});
</script>

<style scoped>
.dealers-panel {
    min-height: 12rem;
}

.dealers-loading {
    display: flex;
    justify-content: center;
    padding: 3rem;
}

.dealer-list {
    list-style: none;
    margin: 0;
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.dealer-card {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.85rem 1rem;
    padding: 0.9rem 1rem;
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface);
    transition: border-color 0.12s ease, box-shadow 0.12s ease;
}

.dealer-card:hover {
    border-color: var(--vs-border);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.dealer-card__icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 10px;
    background: var(--admin-sidebar-active);
    color: var(--admin-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.dealer-card__body {
    flex: 1;
    min-width: 10rem;
}

.dealer-card__name {
    margin: 0 0 0.2rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--vs-text);
}

.dealer-card__user {
    margin: 0;
    font-size: 0.85rem;
    color: var(--vs-text-muted);
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
}

.dealer-card__user i {
    font-size: 0.8rem;
}

.dealer-card__presence {
    margin: 0.35rem 0 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
}

.presence-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.78rem;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    border: 1px solid transparent;
}

.presence-badge i {
    font-size: 0.55rem;
}

.presence-badge--online {
    color: var(--status-new-fg);
    background: var(--status-new-bg);
    border-color: transparent;
}

.presence-badge--offline {
    color: var(--vs-zinc-600);
    background: var(--admin-surface);
    border-color: var(--admin-border);
}

.locale-select-wrap {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.locale-select-label {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--vs-text-secondary);
}

.locale-select {
    min-width: 7.5rem;
    max-width: 8.75rem;
}

.locale-select :deep(.p-select) {
    min-height: 1.65rem;
    font-size: 0.68rem;
}

.locale-select :deep(.p-select-label) {
    padding: 0.2rem 0.45rem;
    font-size: 0.68rem;
    line-height: 1.2;
}

.locale-select :deep(.p-select-dropdown) {
    width: 1.5rem;
}

.locale-select :deep(.p-select-dropdown .p-icon) {
    width: 0.7rem;
    height: 0.7rem;
}

.dealer-card__sep {
    opacity: 0.5;
}

.dealer-card__meta {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.85rem;
    color: var(--vs-zinc-600);
    padding: 0.35rem 0.65rem;
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: 999px;
}

.form-grid {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.field {
    margin-bottom: 0.85rem;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.w-full {
    width: 100%;
}
</style>
