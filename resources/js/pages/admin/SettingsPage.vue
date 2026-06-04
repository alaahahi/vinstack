<template>
    <div class="admin-page settings-page">
        <AdminPageHeader>
            <template #actions>
                <Button
                    label="مزامنة الآن"
                    icon="pi pi-sync"
                    severity="secondary"
                    outlined
                    :loading="syncing"
                    @click="syncNow"
                />
            </template>
        </AdminPageHeader>

        <div class="settings-grid">
            <div class="settings-group settings-group--vinstack settings-card--wide">
                <div class="settings-group__cards">
            <section class="admin-surface settings-card">
                <header class="settings-card__head">
                    <i class="pi pi-link" />
                    <div>
                        <h2 class="vs-card-title">اتصال API</h2>
                        <p class="vs-card-subtitle">عنوان Vinstack والتوكن للمزامنة</p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div class="field">
                        <label for="api-base" class="vs-form-label">Base URL</label>
                        <InputText
                            id="api-base"
                            v-model="form.api_base_url"
                            class="w-full"
                            placeholder="https://app.vinstack.com/api/v1/client"
                        />
                    </div>
                    <div class="field">
                        <label for="api-token" class="vs-form-label">API Token</label>
                        <Password
                            id="api-token"
                            v-model="form.api_token"
                            :placeholder="settings.has_token ? '•••••• (اتركه فارغاً للإبقاء)' : 'أدخل التوكن'"
                            toggle-mask
                            input-class="w-full"
                            class="w-full"
                        />
                    </div>
                </div>
            </section>

            <section class="admin-surface settings-card">
                <header class="settings-card__head">
                    <i class="pi pi-headphones" />
                    <div>
                        <h2 class="vs-card-title">الدعم الفني</h2>
                        <p class="vs-card-subtitle">يظهر في تذييل صفحات التجار وتسجيل الدخول</p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div class="field">
                        <label for="support-phone" class="vs-form-label">رقم دعم فني</label>
                        <InputText
                            id="support-phone"
                            v-model="form.support_phone"
                            class="w-full"
                            placeholder="+966 5xx xxx xxxx"
                            dir="ltr"
                        />
                    </div>
                </div>
            </section>

            <section class="admin-surface settings-card">
                <header class="settings-card__head">
                    <i class="pi pi-sync" />
                    <div>
                        <h2 class="vs-card-title">المزامنة</h2>
                        <p class="vs-card-subtitle">التحديث التلقائي من Vinstack</p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div class="field field--row">
                        <Checkbox v-model="form.sync_enabled" binary input-id="sync" />
                        <label for="sync" class="vs-form-label">تفعيل المزامنة التلقائية</label>
                    </div>
                    <div v-if="settings.last_sync_at" class="vs-sync-status">
                        <i class="pi pi-clock" />
                        <span>آخر مزامنة: <strong>{{ settings.last_sync_at }}</strong></span>
                    </div>
                    <div v-else class="vs-sync-status vs-sync-status--muted">
                        <i class="pi pi-info-circle" />
                        <span>لم تُنفَّذ مزامنة بعد</span>
                    </div>
                </div>
            </section>
                </div>

                <div class="settings-group__actions">
                    <Button
                        label="حفظ الإعدادات"
                        icon="pi pi-check"
                        class="btn-add"
                        :loading="saving"
                        @click="save"
                    />
                </div>
            </div>

            <section class="admin-surface settings-card settings-card--wide">
                <header class="settings-card__head">
                    <i class="pi pi-list" />
                    <div>
                        <h2 class="vs-card-title">خيارات نموذج السيارة</h2>
                        <p class="vs-card-subtitle">قوائم الشحن والمزادات للإدخال اليدوي</p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <VehicleOptionsEditor v-model="vehicleOptions" />
                </div>

                <div class="settings-card__footer">
                    <Button
                        label="حفظ الخيارات"
                        icon="pi pi-save"
                        class="btn-add"
                        :loading="savingOptions"
                        @click="saveVehicleOptions"
                    />
                </div>
            </section>

            <section class="admin-surface settings-card settings-card--wide settings-card--system">
                <header class="settings-card__head">
                    <i class="pi pi-database" />
                    <div>
                        <h2 class="vs-card-title">قاعدة البيانات</h2>
                        <p class="vs-card-subtitle">
                            حالة المايغريشن
                            <template v-if="migrationSummary">
                                — منفّذ: {{ migrationSummary.ran }} · معلّق: {{ migrationSummary.pending }}
                            </template>
                        </p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div v-if="migrationsLoading" class="system-loading">
                        <ProgressSpinner style="width: 32px; height: 32px" />
                        <span>جاري تحميل المايغريشن...</span>
                    </div>
                    <div v-else class="migrations-table-wrap">
                        <table class="migrations-table">
                            <thead>
                                <tr>
                                    <th>الملف</th>
                                    <th>الحالة</th>
                                    <th>الدفعة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in migrations" :key="row.name">
                                    <td class="migrations-table__name" dir="ltr">{{ row.name }}</td>
                                    <td>
                                        <span
                                            class="migration-status"
                                            :class="
                                                row.status === 'ran'
                                                    ? 'migration-status--ran'
                                                    : 'migration-status--pending'
                                            "
                                        >
                                            {{ row.status === 'ran' ? 'منفّذ' : 'معلّق' }}
                                        </span>
                                    </td>
                                    <td>{{ row.batch ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="system-actions">
                        <Button
                            label="تشغيل المايغريشن"
                            icon="pi pi-play"
                            class="btn-add"
                            :loading="migrating"
                            :disabled="migrationsLoading"
                            @click="confirmMigrate"
                        />
                        <Button
                            label="تحديث القائمة"
                            icon="pi pi-refresh"
                            outlined
                            :loading="migrationsLoading"
                            @click="loadMigrations"
                        />
                    </div>

                    <pre v-if="migrateOutput" class="system-console">{{ migrateOutput }}</pre>

                    <div class="backup-section">
                        <h3 class="backup-section__title">نسخ احتياطي</h3>
                        <p class="vs-card-subtitle backup-section__hint">
                            نسخ SQL لقاعدة البيانات
                            <span v-if="dbDriver" dir="ltr">({{ dbDriver }})</span>
                        </p>

                        <div v-if="backupsLoading" class="system-loading">
                            <ProgressSpinner style="width: 32px; height: 32px" />
                            <span>جاري تحميل النسخ الاحتياطية...</span>
                        </div>

                        <template v-else>
                            <div class="system-actions">
                                <Button
                                    label="إنشاء نسخة SQL"
                                    icon="pi pi-database"
                                    class="btn-add"
                                    :loading="creatingBackup"
                                    @click="createBackup"
                                />
                                <Button
                                    label="تحديث القائمة"
                                    icon="pi pi-refresh"
                                    outlined
                                    :loading="backupsLoading"
                                    @click="loadBackups"
                                />
                            </div>

                            <div v-if="backups.length" class="migrations-table-wrap">
                                <table class="migrations-table">
                                    <thead>
                                        <tr>
                                            <th>الملف</th>
                                            <th>الحجم</th>
                                            <th>التاريخ</th>
                                            <th>إجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="row in backups" :key="row.filename">
                                            <td class="migrations-table__name" dir="ltr">{{ row.filename }}</td>
                                            <td>{{ row.size_human }}</td>
                                            <td dir="ltr">{{ formatBackupDate(row.created_at) }}</td>
                                            <td>
                                                <div class="backup-row-actions">
                                                    <Button
                                                        icon="pi pi-download"
                                                        text
                                                        rounded
                                                        severity="secondary"
                                                        title="تنزيل"
                                                        :loading="downloadingFilename === row.filename"
                                                        @click="downloadBackup(row.filename)"
                                                    />
                                                    <Button
                                                        icon="pi pi-replay"
                                                        text
                                                        rounded
                                                        severity="danger"
                                                        title="استرجاع"
                                                        :loading="restoringFilename === row.filename"
                                                        @click="confirmRestoreFromList(row.filename)"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-else class="vs-card-subtitle backup-section__empty">
                                لا توجد نسخ احتياطية بعد. أنشئ نسخة SQL أولاً.
                            </p>

                            <div class="backup-upload">
                                <label class="vs-form-label" for="restore-sql-file">استرجاع من ملف .sql (اختياري)</label>
                                <div class="backup-upload__row">
                                    <input
                                        id="restore-sql-file"
                                        ref="restoreFileInput"
                                        type="file"
                                        accept=".sql,.txt"
                                        class="backup-upload__input"
                                        @change="onRestoreFileSelected"
                                    />
                                    <Button
                                        label="استرجاع الملف المرفوع"
                                        icon="pi pi-upload"
                                        severity="danger"
                                        outlined
                                        :disabled="!restoreFile"
                                        :loading="restoringUpload"
                                        @click="confirmRestoreFromUpload"
                                    />
                                </div>
                                <p v-if="restoreFile" class="backup-upload__name" dir="ltr">{{ restoreFile.name }}</p>
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            <section class="admin-surface settings-card settings-card--wide settings-card--system">
                <header class="settings-card__head">
                    <i class="pi pi-exclamation-triangle" />
                    <div>
                        <h2 class="vs-card-title">سجل الأخطاء</h2>
                        <p class="vs-card-subtitle">
                            آخر أسطر من storage/logs/laravel.log
                            <template v-if="logLines"> ({{ logLines }} سطر)</template>
                        </p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div class="system-actions">
                        <Button
                            label="تحديث السجل"
                            icon="pi pi-refresh"
                            outlined
                            :loading="logsLoading"
                            @click="loadLogs"
                        />
                        <Button
                            label="مسح السجل"
                            icon="pi pi-trash"
                            severity="danger"
                            outlined
                            :loading="clearingLogs"
                            :disabled="logsLoading"
                            @click="confirmClearLogs"
                        />
                    </div>

                    <div v-if="logsLoading" class="system-loading">
                        <ProgressSpinner style="width: 32px; height: 32px" />
                        <span>جاري تحميل السجل...</span>
                    </div>
                    <pre v-else class="system-log-viewer">{{ logContent || logMessage }}</pre>
                </div>
            </section>
        </div>

        <Dialog
            v-model:visible="clearLogsConfirmVisible"
            header="مسح سجل الأخطاء"
            modal
            :style="{ width: 'min(420px, 95vw)' }"
        >
            <p class="vs-card-subtitle">
                سيتم حذف محتوى ملف laravel.log بالكامل. لا يمكن التراجع عن هذا الإجراء. هل تريد المتابعة؟
            </p>
            <template #footer>
                <Button label="إلغاء" text @click="clearLogsConfirmVisible = false" />
                <Button
                    label="مسح السجل"
                    icon="pi pi-trash"
                    severity="danger"
                    :loading="clearingLogs"
                    @click="clearLogs"
                />
            </template>
        </Dialog>

        <Dialog
            v-model:visible="migrateConfirmVisible"
            header="تشغيل المايغريشن"
            modal
            :style="{ width: 'min(420px, 95vw)' }"
        >
            <p class="vs-card-subtitle">
                سيتم تنفيذ جميع المايغريشن المعلّقة على قاعدة البيانات. هل تريد المتابعة؟
            </p>
            <template #footer>
                <Button label="إلغاء" text @click="migrateConfirmVisible = false" />
                <Button
                    label="تشغيل الآن"
                    icon="pi pi-play"
                    class="btn-add"
                    :loading="migrating"
                    @click="runMigrate"
                />
            </template>
        </Dialog>

        <Dialog
            v-model:visible="restorableVisible"
            header="سيارات محذوفة في Vinstack"
            modal
            :style="{ width: 'min(520px, 95vw)' }"
        >
            <p class="vs-card-subtitle">
                وُجدت مركبات محذوفة محلياً ما زالت موجودة في Vinstack. استعِدها لتجنّب التكرار.
            </p>
            <ul class="restorable-list">
                <li v-for="item in restorableItems" :key="item.id" class="restorable-list__item">
                    <span dir="ltr" class="restorable-list__vin">{{ item.vin || '—' }}</span>
                    <Button
                        label="استعادة"
                        icon="pi pi-replay"
                        size="small"
                        outlined
                        :loading="restoringId === item.id"
                        @click="restoreFromSync(item.id)"
                    />
                </li>
            </ul>
            <template #footer>
                <Button label="إغلاق" text @click="restorableVisible = false" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import AdminPageHeader from '../../components/AdminPageHeader.vue';
import VehicleOptionsEditor from '../../components/VehicleOptionsEditor.vue';
import { restoreVehicle } from '../../api/vehicles';
import api from '../../api/client';

const toast = useToast();
const confirm = useConfirm();
const settings = ref({ has_token: false, last_sync_at: null });
const saving = ref(false);
const savingOptions = ref(false);
const syncing = ref(false);
const restorableVisible = ref(false);
const restorableItems = ref([]);
const restoringId = ref(null);
const migrations = ref([]);
const migrationSummary = ref(null);
const migrationsLoading = ref(false);
const migrating = ref(false);
const migrateOutput = ref('');
const migrateConfirmVisible = ref(false);
const logsLoading = ref(false);
const clearingLogs = ref(false);
const clearLogsConfirmVisible = ref(false);
const logContent = ref('');
const logMessage = ref('اضغط «تحديث السجل» لعرض آخر الأخطاء.');
const logLines = ref(0);
const backups = ref([]);
const dbDriver = ref('');
const backupsLoading = ref(false);
const creatingBackup = ref(false);
const downloadingFilename = ref('');
const restoringFilename = ref('');
const restoringUpload = ref(false);
const restoreFile = ref(null);
const restoreFileInput = ref(null);

const vehicleOptions = ref({
    shipping_destinations: [],
    loading_points: [],
    auctions: [],
    shipping_methods: [],
    delivery_types: [],
    title_types: [],
});

const form = reactive({
    api_base_url: '',
    api_token: '',
    sync_enabled: true,
    support_phone: '',
});

async function load() {
    const [settingsRes, optionsRes] = await Promise.all([
        api.get('/admin/vinstack/settings'),
        api.get('/admin/settings/vehicle-options'),
    ]);
    settings.value = settingsRes.data.data;
    form.api_base_url = settingsRes.data.data.api_base_url || '';
    form.sync_enabled = settingsRes.data.data.sync_enabled ?? true;
    form.support_phone = settingsRes.data.data.support_phone || '';
    form.api_token = '';
    vehicleOptions.value = optionsRes.data.data;
}

async function saveVehicleOptions() {
    savingOptions.value = true;

    try {
        await api.put('/admin/settings/vehicle-options', vehicleOptions.value);
        toast.add({ severity: 'success', summary: 'تم حفظ خيارات النموذج', life: 3000 });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل حفظ الخيارات',
            life: 4000,
        });
    } finally {
        savingOptions.value = false;
    }
}

async function save() {
    saving.value = true;

    try {
        const payload = {
            api_base_url: form.api_base_url,
            sync_enabled: form.sync_enabled,
            support_phone: form.support_phone,
        };

        if (form.api_token) {
            payload.api_token = form.api_token;
        }

        await api.put('/admin/vinstack/settings', payload);
        toast.add({ severity: 'success', summary: 'تم الحفظ', life: 3000 });
        await load();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل الحفظ',
            life: 4000,
        });
    } finally {
        saving.value = false;
    }
}

async function syncNow() {
    syncing.value = true;

    try {
        const { data } = await api.post('/admin/vinstack/sync');
        toast.add({
            severity: 'success',
            summary: data.message,
            detail: `من Vinstack: ${data.total} · جديد: ${data.created} · محدّث: ${data.updated}`,
            life: 5000,
        });
        if (Array.isArray(data.restorable) && data.restorable.length > 0) {
            restorableItems.value = data.restorable;
            restorableVisible.value = true;
        }
        await load();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشلت المزامنة',
            life: 4000,
        });
    } finally {
        syncing.value = false;
    }
}

async function restoreFromSync(vehicleId) {
    restoringId.value = vehicleId;

    try {
        const result = await restoreVehicle(vehicleId);
        restorableItems.value = restorableItems.value.filter((item) => item.id !== vehicleId);
        if (restorableItems.value.length === 0) {
            restorableVisible.value = false;
        }
        toast.add({
            severity: 'success',
            summary: result.message || 'تمت الاستعادة',
            life: 3000,
        });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشلت الاستعادة',
            life: 4000,
        });
    } finally {
        restoringId.value = null;
    }
}

async function loadMigrations() {
    migrationsLoading.value = true;

    try {
        const { data } = await api.get('/admin/system/migrations');
        migrations.value = data.data ?? [];
        migrationSummary.value = data.summary ?? null;
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل تحميل المايغريشن',
            life: 4000,
        });
    } finally {
        migrationsLoading.value = false;
    }
}

function confirmMigrate() {
    migrateConfirmVisible.value = true;
}

async function runMigrate() {
    migrating.value = true;
    migrateOutput.value = '';

    try {
        const { data } = await api.post('/admin/system/migrate');
        migrateOutput.value = data.output || '';
        migrateConfirmVisible.value = false;

        if (data.success) {
            toast.add({ severity: 'success', summary: 'تم تنفيذ المايغريشن', life: 3000 });
            await loadMigrations();
        } else {
            toast.add({
                severity: 'error',
                summary: 'فشل المايغريشن',
                detail: 'راجع مخرجات Artisan أدناه',
                life: 5000,
            });
        }
    } catch (e) {
        migrateOutput.value = e.response?.data?.output || e.response?.data?.message || String(e);
        migrateConfirmVisible.value = false;
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل تشغيل المايغريشن',
            life: 4000,
        });
    } finally {
        migrating.value = false;
    }
}

async function loadLogs() {
    logsLoading.value = true;

    try {
        const { data } = await api.get('/admin/system/logs');
        const payload = data.data ?? {};
        logContent.value = payload.content || '';
        logMessage.value = payload.message || (logContent.value ? '' : 'السجل فارغ.');
        logLines.value = payload.lines ?? 0;
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل تحميل السجل',
            life: 4000,
        });
    } finally {
        logsLoading.value = false;
    }
}

function confirmClearLogs() {
    clearLogsConfirmVisible.value = true;
}

async function clearLogs() {
    clearingLogs.value = true;

    try {
        const { data } = await api.delete('/admin/system/logs');
        const payload = data.data ?? {};
        logContent.value = payload.content || '';
        logMessage.value = payload.message || 'السجل فارغ.';
        logLines.value = payload.lines ?? 0;
        clearLogsConfirmVisible.value = false;
        toast.add({
            severity: 'success',
            summary: data.message || 'تم مسح السجل',
            life: 3000,
        });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل مسح السجل',
            life: 4000,
        });
    } finally {
        clearingLogs.value = false;
    }
}

function formatBackupDate(iso) {
    if (!iso) {
        return '—';
    }

    try {
        return new Date(iso).toLocaleString('ar-SA', {
            dateStyle: 'short',
            timeStyle: 'short',
        });
    } catch {
        return iso;
    }
}

async function loadBackups() {
    backupsLoading.value = true;

    try {
        const { data } = await api.get('/admin/system/backups');
        backups.value = data.data ?? [];
        dbDriver.value = data.driver ?? '';
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل تحميل النسخ الاحتياطية',
            life: 4000,
        });
    } finally {
        backupsLoading.value = false;
    }
}

async function createBackup() {
    creatingBackup.value = true;

    try {
        const { data } = await api.post('/admin/system/backup');
        toast.add({
            severity: 'success',
            summary: data.message || 'تم إنشاء النسخة',
            life: 3000,
        });
        await loadBackups();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل إنشاء النسخة الاحتياطية',
            life: 5000,
        });
    } finally {
        creatingBackup.value = false;
    }
}

function triggerBlobDownload(blob, filename) {
    const objectUrl = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = objectUrl;
    anchor.download = filename;
    anchor.click();
    URL.revokeObjectURL(objectUrl);
}

async function downloadBackup(filename) {
    downloadingFilename.value = filename;

    try {
        const { data } = await api.get(`/admin/system/backups/${filename}/download`, {
            responseType: 'blob',
        });
        triggerBlobDownload(data, filename);
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل تنزيل النسخة',
            life: 4000,
        });
    } finally {
        downloadingFilename.value = '';
    }
}

function onRestoreFileSelected(event) {
    const file = event.target.files?.[0];
    restoreFile.value = file ?? null;
}

function confirmRestoreFromList(filename) {
    confirm.require({
        message:
            'تحذير: استرجاع النسخة سيستبدل بيانات قاعدة البيانات الحالية بالكامل. قد تفقد التغييرات غير المحفوظة. هل أنت متأكد؟',
        header: 'استرجاع قاعدة البيانات',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'إلغاء',
        acceptLabel: 'نعم، استرجاع',
        acceptClass: 'p-button-danger',
        accept: () => restoreFromList(filename),
    });
}

function confirmRestoreFromUpload() {
    if (!restoreFile.value) {
        return;
    }

    confirm.require({
        message:
            'تحذير: رفع واسترجاع ملف SQL سيستبدل بيانات قاعدة البيانات الحالية. تأكد من صحة الملف قبل المتابعة. هل تريد الاستمرار؟',
        header: 'استرجاع من ملف مرفوع',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'إلغاء',
        acceptLabel: 'نعم، استرجاع',
        acceptClass: 'p-button-danger',
        accept: () => restoreFromUpload(),
    });
}

async function restoreFromList(filename) {
    restoringFilename.value = filename;

    try {
        const { data } = await api.post('/admin/system/restore', {
            confirm: true,
            filename,
        });
        toast.add({
            severity: 'success',
            summary: data.message || 'تم الاسترجاع',
            life: 4000,
        });
        await Promise.all([loadMigrations(), loadBackups()]);
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل استرجاع النسخة',
            life: 5000,
        });
    } finally {
        restoringFilename.value = '';
    }
}

async function restoreFromUpload() {
    if (!restoreFile.value) {
        return;
    }

    restoringUpload.value = true;
    const form = new FormData();
    form.append('confirm', '1');
    form.append('file', restoreFile.value);

    try {
        const { data } = await api.post('/admin/system/restore', form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        toast.add({
            severity: 'success',
            summary: data.message || 'تم الاسترجاع',
            life: 4000,
        });
        restoreFile.value = null;

        if (restoreFileInput.value) {
            restoreFileInput.value.value = '';
        }

        await Promise.all([loadMigrations(), loadBackups()]);
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: e.response?.data?.message || 'فشل استرجاع الملف',
            life: 5000,
        });
    } finally {
        restoringUpload.value = false;
    }
}

onMounted(async () => {
    await load();
    await Promise.all([loadMigrations(), loadLogs(), loadBackups()]);
});
</script>

<style scoped>
.settings-group--vinstack {
    display: flex;
    flex-direction: column;
    gap: 0;
    padding: 0;
    border: 1px solid var(--vs-border);
    border-radius: 12px;
    background: var(--admin-surface, #fff);
    overflow: hidden;
}

.settings-group__cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr));
    gap: 1rem;
    padding: 1rem;
}

.settings-group__cards .settings-card {
    border: 1px solid var(--vs-border);
    border-radius: 10px;
    background: var(--vs-surface-elevated, rgba(0, 0, 0, 0.02));
}

.settings-group__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0.85rem 1.15rem;
    border-top: 1px solid var(--vs-border);
    background: var(--vs-surface-elevated, rgba(0, 0, 0, 0.02));
}

.settings-card__footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0 1.15rem 1.15rem;
    border-top: 1px solid var(--vs-border);
    margin-top: 0.25rem;
    padding-top: 1rem;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr));
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.settings-card__head {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    padding: 1.1rem 1.15rem 0;
}

.settings-card__head > i {
    width: 2.25rem;
    height: 2.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: var(--admin-sidebar-active);
    color: var(--admin-accent);
    font-size: 1rem;
    flex-shrink: 0;
}

.settings-card__body {
    padding: 0.85rem 1.15rem 1.15rem;
}

.field {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.field:last-child {
    margin-bottom: 0;
}

.field--row {
    flex-direction: row;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 0.75rem;
}

.settings-card--wide {
    grid-column: 1 / -1;
}

.w-full {
    width: 100%;
}

.restorable-list {
    list-style: none;
    margin: 0.75rem 0 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.restorable-list__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.55rem 0.65rem;
    border: 1px solid var(--vs-border);
    border-radius: 8px;
}

.restorable-list__vin {
    font-family: ui-monospace, monospace;
    font-weight: 600;
}

.system-loading {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    color: var(--vs-text-muted);
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
}

.system-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 0.75rem 0;
}

.migrations-table-wrap {
    overflow-x: auto;
    border: 1px solid var(--vs-border);
    border-radius: 8px;
}

.migrations-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}

.migrations-table th,
.migrations-table td {
    padding: 0.5rem 0.65rem;
    text-align: start;
    border-bottom: 1px solid var(--vs-border);
}

.migrations-table th {
    background: var(--admin-sidebar-active, rgba(0, 0, 0, 0.04));
    font-weight: 600;
}

.migrations-table__name {
    font-family: ui-monospace, monospace;
    font-size: 0.75rem;
    word-break: break-all;
}

.migration-status {
    display: inline-flex;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.migration-status--ran {
    color: #166534;
    background: rgba(22, 163, 74, 0.12);
}

.migration-status--pending {
    color: #b45309;
    background: rgba(245, 158, 11, 0.15);
}

.system-console,
.system-log-viewer {
    margin: 0.5rem 0 0;
    padding: 0.75rem 0.85rem;
    max-height: min(320px, 40vh);
    overflow: auto;
    border-radius: 8px;
    border: 1px solid var(--vs-border);
    background: #0f172a;
    color: #e2e8f0;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.75rem;
    line-height: 1.45;
    white-space: pre-wrap;
    word-break: break-word;
    direction: ltr;
    text-align: left;
}

.system-log-viewer {
    min-height: 8rem;
}

.backup-section {
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--vs-border);
}

.backup-section__title {
    margin: 0 0 0.25rem;
    font-size: 1rem;
    font-weight: 700;
}

.backup-section__hint {
    margin: 0 0 0.75rem;
}

.backup-section__empty {
    margin: 0.5rem 0 0;
}

.backup-row-actions {
    display: flex;
    gap: 0.15rem;
}

.backup-upload {
    margin-top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.backup-upload__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
}

.backup-upload__input {
    max-width: min(100%, 280px);
    font-size: 0.8125rem;
}

.backup-upload__name {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--vs-text-muted);
}
</style>
