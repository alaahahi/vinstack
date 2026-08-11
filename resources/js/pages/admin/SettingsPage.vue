<template>
    <div class="admin-page settings-page">
        <AdminPageHeader>
            <template #actions>
                <Button
                    :label="t('settings.syncNow')"
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
                        <h2 class="vs-card-title">{{ t('settings.sections.apiConnection') }}</h2>
                        <p class="vs-card-subtitle">{{ t('settings.sections.apiConnectionSub') }}</p>
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

            <section class="admin-surface settings-card settings-card--gallery">
                <header class="settings-card__head">
                    <i class="pi pi-images" />
                    <div>
                        <h2 class="vs-card-title">{{ t('settings.sections.galleryApi') }}</h2>
                        <p class="vs-card-subtitle">{{ t('settings.sections.galleryApiSub') }}</p>
                    </div>
                    <Tag
                        v-if="settings.gallery_token_expired"
                        severity="danger"
                        :value="t('settings.expired')"
                        class="gallery-expired-tag"
                    />
                </header>

                <div class="settings-card__body">
                    <div class="gallery-status-row">
                        <span class="gallery-status-chip" :class="galleryUrlReady ? 'gallery-status-chip--ok' : 'gallery-status-chip--warn'">
                            {{ galleryUrlReady ? t('settings.urlOk') : t('settings.urlMissing') }}
                        </span>
                        <span class="gallery-status-chip" :class="galleryTokenReady ? 'gallery-status-chip--ok' : 'gallery-status-chip--warn'">
                            {{ galleryTokenReady ? t('settings.tokenOk') : t('settings.tokenMissing') }}
                        </span>
                    </div>

                    <div class="field">
                        <label for="gallery-api-base" class="vs-form-label">Gallery Base URL</label>
                        <InputText
                            id="gallery-api-base"
                            v-model="form.gallery_api_base_url"
                            class="w-full gallery-input"
                            dir="ltr"
                            placeholder="https://app.vinstack.com/api/client-portal"
                        />
                    </div>
                    <div class="field">
                        <label for="gallery-api-token" class="vs-form-label">Gallery API Token</label>
                        <Password
                            id="gallery-api-token"
                            v-model="form.gallery_api_token"
                            :placeholder="settings.has_gallery_token ? '•••••• (اتركه فارغاً للإبقاء)' : 'أدخل توكن المعرض'"
                            toggle-mask
                            input-class="w-full gallery-input"
                            class="w-full"
                        />
                    </div>

                    <p class="sync-cron-help sync-cron-help--muted gallery-path-hint">
                        <code dir="ltr">{{ galleryEndpointPreview }}</code>
                    </p>

                    <Button
                        :label="t('actions.test')"
                        icon="pi pi-bolt"
                        size="small"
                        outlined
                        severity="secondary"
                        class="gallery-test-btn"
                        :loading="testingGallery"
                        @click="testGalleryConnection"
                    />

                    <div v-if="settings.gallery_token_checked_at" class="vs-sync-status">
                        <i class="pi pi-clock" />
                        <span>
                            آخر فحص:
                            <strong>
                                <span class="sync-datetime" dir="ltr">{{ formatDateTime(settings.gallery_token_checked_at) }}</span>
                            </strong>
                        </span>
                    </div>
                </div>
            </section>

            <section class="admin-surface settings-card settings-card--gallery">
                <header class="settings-card__head">
                    <i class="pi pi-cloud-upload" />
                    <div>
                        <h2 class="vs-card-title">{{ t('settings.sections.cloudinary') }}</h2>
                        <p class="vs-card-subtitle">{{ t('settings.sections.cloudinarySub') }}</p>
                    </div>
                    <Tag
                        v-if="settings.cloudinary_configured"
                        severity="success"
                        :value="t('settings.ready')"
                        class="gallery-expired-tag"
                    />
                </header>

                <div class="settings-card__body">
                    <div class="gallery-status-row">
                        <span class="gallery-status-chip" :class="cloudinaryNameReady ? 'gallery-status-chip--ok' : 'gallery-status-chip--warn'">
                            {{ cloudinaryNameReady ? 'Cloud ✓' : 'Cloud ✗' }}
                        </span>
                        <span class="gallery-status-chip" :class="cloudinaryKeyReady ? 'gallery-status-chip--ok' : 'gallery-status-chip--warn'">
                            {{ cloudinaryKeyReady ? 'Key ✓' : 'Key ✗' }}
                        </span>
                        <span class="gallery-status-chip" :class="cloudinarySecretReady ? 'gallery-status-chip--ok' : 'gallery-status-chip--warn'">
                            {{ cloudinarySecretReady ? 'Secret/Preset ✓' : 'Secret/Preset ✗' }}
                        </span>
                    </div>

                    <div class="field">
                        <label for="cloudinary-cloud-name" class="vs-form-label">Cloud Name</label>
                        <InputText
                            id="cloudinary-cloud-name"
                            v-model="form.cloudinary_cloud_name"
                            class="w-full gallery-input"
                            dir="ltr"
                            placeholder="my-cloud"
                        />
                    </div>
                    <div class="field">
                        <label for="cloudinary-api-key" class="vs-form-label">API Key</label>
                        <InputText
                            id="cloudinary-api-key"
                            v-model="form.cloudinary_api_key"
                            class="w-full gallery-input"
                            dir="ltr"
                        />
                    </div>
                    <div class="field">
                        <label for="cloudinary-api-secret" class="vs-form-label">API Secret</label>
                        <Password
                            id="cloudinary-api-secret"
                            v-model="form.cloudinary_api_secret"
                            :placeholder="settings.has_cloudinary_api_secret ? '•••••• (اتركه فارغاً للإبقاء)' : 'أدخل API Secret'"
                            toggle-mask
                            input-class="w-full gallery-input"
                            class="w-full"
                        />
                    </div>
                    <div class="field">
                        <label for="cloudinary-upload-preset" class="vs-form-label">Upload Preset (اختياري — للرفع بدون Secret)</label>
                        <InputText
                            id="cloudinary-upload-preset"
                            v-model="form.cloudinary_upload_preset"
                            class="w-full gallery-input"
                            dir="ltr"
                            placeholder="unsigned-preset"
                        />
                    </div>
                    <div class="field">
                        <label for="cloudinary-folder" class="vs-form-label">Folder</label>
                        <InputText
                            id="cloudinary-folder"
                            v-model="form.cloudinary_folder"
                            class="w-full gallery-input"
                            dir="ltr"
                            placeholder="vinstack/containers"
                        />
                    </div>

                    <Button
                        label="اختبار Cloudinary"
                        icon="pi pi-bolt"
                        size="small"
                        outlined
                        severity="secondary"
                        class="gallery-test-btn"
                        :loading="testingCloudinary"
                        @click="testCloudinaryConnection"
                    />
                </div>
            </section>

            <section class="admin-surface settings-card settings-card--gallery">
                <header class="settings-card__head">
                    <i class="pi pi-cloud-upload" />
                    <div>
                        <h2 class="vs-card-title">{{ t('pages.admin.imageTransfers.title') }}</h2>
                        <p class="vs-card-subtitle">{{ t('imageTransfers.lead') }}</p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div class="field field--inline">
                        <label class="vs-form-label" for="image-transfer-async">النقل بالخلفية</label>
                        <ToggleSwitch
                            id="image-transfer-async"
                            v-model="form.image_transfer_async_enabled"
                        />
                    </div>
                    <div class="field">
                        <label for="image-transfer-batch" class="vs-form-label">حجم الدفعة (صور لكل دورة)</label>
                        <InputNumber
                            id="image-transfer-batch"
                            v-model="form.image_transfer_batch_size"
                            :min="1"
                            :max="50"
                            show-buttons
                            class="transfer-batch-input"
                        />
                    </div>

                    <RouterLink
                        :to="{ name: 'admin.imageTransfers' }"
                        class="transfer-page-link"
                    >
                        <i class="pi pi-external-link" />
                        {{ t('imageTransfers.openPage') }}
                    </RouterLink>
                </div>
            </section>

            <section class="admin-surface settings-card">
                <header class="settings-card__head">
                    <i class="pi pi-headphones" />
                    <div>
                        <h2 class="vs-card-title">{{ t('settings.sections.support') }}</h2>
                        <p class="vs-card-subtitle">{{ t('settings.sections.supportSub') }}</p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div class="field">
                        <label for="support-phone" class="vs-form-label">{{ t('settings.supportPhone') }}</label>
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
                        <h2 class="vs-card-title">{{ t('settings.sections.sync') }}</h2>
                        <p class="vs-card-subtitle">{{ t('settings.sections.syncSub') }}</p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div class="field field--row">
                        <Checkbox v-model="form.sync_enabled" binary input-id="sync" />
                        <label for="sync" class="vs-form-label">{{ t('settings.autoSync') }}</label>
                    </div>
                    <div v-if="settings.last_sync_at" class="vs-sync-status">
                        <i class="pi pi-clock" />
                        <span>
                            {{ t('settings.lastSync') }}
                            <strong>
                                <span class="sync-datetime" dir="ltr">{{ formatDateTime(settings.last_sync_at) }}</span>
                            </strong>
                        </span>
                    </div>
                    <div v-else class="vs-sync-status vs-sync-status--muted">
                        <i class="pi pi-info-circle" />
                        <span>{{ t('settings.noSyncYet') }}</span>
                    </div>
                    <div v-if="settings.last_auto_sync_at" class="vs-sync-status">
                        <i class="pi pi-calendar-clock" />
                        <span>
                            {{ t('settings.lastAutoSync') }}
                            <strong>
                                <span class="sync-datetime" dir="ltr">{{ formatDateTime(settings.last_auto_sync_at) }}</span>
                            </strong>
                        </span>
                    </div>
                    <div v-else class="vs-sync-status vs-sync-status--muted">
                        <i class="pi pi-info-circle" />
                        <span>{{ t('settings.noAutoSyncYet') }}</span>
                    </div>
                    <p class="sync-cron-help">
                        لتشغيل المزامنة التلقائية على السيرفر، فعِّل «تفعيل المزامنة التلقائية» واحفظ الإعدادات، ثم أضِف مهمة
                        cron في cPanel (أو ما يعادلها):
                    </p>
                    <ul class="sync-cron-help__list" dir="ltr">
                        <li>
                            <code>* * * * * php /path/to/artisan schedule:run</code>
                            — يُشغِّل جدولة Laravel كل دقيقة (المزامنة كل ساعة)
                        </li>
                        <li>
                            <code>0 * * * * php /path/to/artisan vinstack:sync</code>
                            — بديل مباشر دون <code>schedule:run</code>
                        </li>
                    </ul>
                    <p class="sync-cron-help sync-cron-help--muted">
                        على استضافة مشتركة بدون cron: زر «مزامنة الآن» يبقى يعمل يدوياً. بدون cron لن تُنفَّذ المزامنة
                        تلقائياً حتى مع تفعيل الخيار أعلاه.
                    </p>
                </div>
            </section>
                </div>

                <div class="settings-group__actions">
                    <Button
                        :label="t('settings.saveSettings')"
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
                        <h2 class="vs-card-title">{{ t('settings.sections.vehicleOptions') }}</h2>
                        <p class="vs-card-subtitle">{{ t('settings.sections.vehicleOptionsSub') }}</p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <VehicleOptionsEditor v-model="vehicleOptions" />
                </div>

                <div class="settings-card__footer">
                    <Button
                        :label="t('settings.saveOptions')"
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
                        <h2 class="vs-card-title">{{ t('settings.sections.database') }}</h2>
                        <p class="vs-card-subtitle">
                            {{ t('settings.dbSubtitle') }}
                            <template v-if="migrationSummary">
                                — {{ t('settings.dbRan', { count: migrationSummary.ran }) }} · {{ t('settings.dbPending', { count: migrationSummary.pending }) }}
                            </template>
                        </p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div v-if="migrationsLoading" class="system-loading">
                        <ProgressSpinner style="width: 32px; height: 32px" />
                        <span>{{ t('settings.loadingMigrations') }}</span>
                    </div>
                    <div v-else class="migrations-table-wrap">
                        <table class="migrations-table">
                            <thead>
                                <tr>
                                    <th>{{ t('settings.colFile') }}</th>
                                    <th>{{ t('settings.colStatus') }}</th>
                                    <th>{{ t('settings.colBatch') }}</th>
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
                                            {{ row.status === 'ran' ? t('settings.migrationRan') : t('settings.migrationPending') }}
                                        </span>
                                    </td>
                                    <td>{{ row.batch ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="system-actions">
                        <Button
                            :label="t('settings.runMigrations')"
                            icon="pi pi-play"
                            class="btn-add"
                            :loading="migrating"
                            :disabled="migrationsLoading"
                            @click="confirmMigrate"
                        />
                        <Button
                            :label="t('actions.refresh')"
                            icon="pi pi-refresh"
                            outlined
                            :loading="migrationsLoading"
                            @click="loadMigrations"
                        />
                    </div>

                    <pre v-if="migrateOutput" class="system-console">{{ migrateOutput }}</pre>

                    <div class="backup-section">
                        <h3 class="backup-section__title">{{ t('settings.backups') }}</h3>
                        <p class="vs-card-subtitle backup-section__hint">
                            {{ t('settings.backupsHint') }}
                            <span v-if="dbDriver" dir="ltr">({{ dbDriver }})</span>
                        </p>

                        <div v-if="backupsLoading" class="system-loading">
                            <ProgressSpinner style="width: 32px; height: 32px" />
                            <span>{{ t('settings.loadingBackups') }}</span>
                        </div>

                        <template v-else>
                            <div class="system-actions">
                                <Button
                                    :label="t('settings.createSqlBackup')"
                                    icon="pi pi-database"
                                    class="btn-add"
                                    :loading="creatingBackup"
                                    @click="createBackup"
                                />
                                <Button
                                    :label="t('actions.refresh')"
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
                                            <td>
                                                <span class="sync-datetime" dir="ltr">{{ formatDateTime(row.created_at) }}</span>
                                            </td>
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
                                                        icon="pi pi-trash"
                                                        text
                                                        rounded
                                                        severity="danger"
                                                        title="حذف"
                                                        :loading="deletingFilename === row.filename"
                                                        @click="confirmDeleteBackup(row.filename)"
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
                    <i class="pi pi-bolt" />
                    <div>
                        <h2 class="vs-card-title">{{ t('settings.sections.cache') }}</h2>
                        <p class="vs-card-subtitle">{{ t('settings.sections.cacheSub') }}</p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div class="system-actions">
                        <Button
                            :label="t('settings.clearCache')"
                            icon="pi pi-trash"
                            severity="warning"
                            outlined
                            :loading="clearingCache"
                            @click="confirmClearCache"
                        />
                    </div>
                </div>
            </section>

            <section class="admin-surface settings-card settings-card--wide settings-card--system">
                <header class="settings-card__head">
                    <i class="pi pi-exclamation-triangle" />
                    <div>
                        <h2 class="vs-card-title">{{ t('settings.sections.errorLog') }}</h2>
                        <p class="vs-card-subtitle">
                            {{ t('settings.sections.errorLogSub') }}
                            <template v-if="logLines"> {{ t('settings.logLines', { count: logLines }) }}</template>
                        </p>
                    </div>
                </header>

                <div class="settings-card__body">
                    <div class="system-actions">
                        <Button
                            :label="t('settings.refreshLog')"
                            icon="pi pi-refresh"
                            outlined
                            :loading="logsLoading"
                            @click="loadLogs"
                        />
                        <Button
                            :label="t('settings.clearLog')"
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
                        <span>{{ t('settings.loadingLog') }}</span>
                    </div>
                    <pre v-else class="system-log-viewer">{{ logContent || logMessage }}</pre>
                </div>
            </section>
        </div>

        <Dialog
            v-model:visible="clearCacheConfirmVisible"
            :header="t('settings.clearCacheHeader')"
            modal
            :style="{ width: 'min(420px, 95vw)' }"
        >
            <p class="vs-card-subtitle">
                {{ t('settings.clearCacheConfirm') }}
            </p>
            <template #footer>
                <Button :label="t('actions.cancel')" text @click="clearCacheConfirmVisible = false" />
                <Button
                    :label="t('settings.clearCache')"
                    icon="pi pi-trash"
                    severity="warning"
                    :loading="clearingCache"
                    @click="clearCache"
                />
            </template>
        </Dialog>

        <Dialog
            v-model:visible="clearLogsConfirmVisible"
            :header="t('settings.clearLogHeader')"
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
import { computed, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import InputNumber from 'primevue/inputnumber';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import AdminPageHeader from '../../components/AdminPageHeader.vue';
import VehicleOptionsEditor from '../../components/VehicleOptionsEditor.vue';
import { restoreVehicle } from '../../api/vehicles';
import api from '../../api/client';
import { formatDateTime } from '../../utils/formatDateTime';

const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();
const settings = ref({ has_token: false, last_sync_at: null, last_auto_sync_at: null });
const saving = ref(false);
const testingGallery = ref(false);
const testingCloudinary = ref(false);
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
const clearCacheConfirmVisible = ref(false);
const clearingCache = ref(false);
const logContent = ref('');
const logMessage = ref('');
const logLines = ref(0);
const backups = ref([]);
const dbDriver = ref('');
const backupsLoading = ref(false);
const creatingBackup = ref(false);
const downloadingFilename = ref('');
const restoringFilename = ref('');
const deletingFilename = ref('');
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

const DEFAULT_GALLERY_BASE = 'https://app.vinstack.com/api/client-portal';

const galleryEndpointPreview = computed(() => {
    const base = (form.gallery_api_base_url || settings.value.gallery_api_base_url || DEFAULT_GALLERY_BASE).replace(/\/$/, '');

    return `${base}/autos/{vehicle_id}/gallery`;
});

const galleryUrlReady = computed(() => Boolean(
    (form.gallery_api_base_url || settings.value.gallery_api_base_url || '').trim(),
));

const galleryTokenReady = computed(() => (
    Boolean(form.gallery_api_token?.trim())
    || settings.value.has_gallery_token
    || settings.value.has_token
));

const cloudinaryNameReady = computed(() => Boolean(
    (form.cloudinary_cloud_name || settings.value.cloudinary_cloud_name || '').trim(),
));

const cloudinaryKeyReady = computed(() => Boolean(
    (form.cloudinary_api_key || '').trim() || settings.value.has_cloudinary_api_key,
));

const cloudinarySecretReady = computed(() => Boolean(
    (form.cloudinary_api_secret || '').trim()
    || settings.value.has_cloudinary_api_secret
    || (form.cloudinary_upload_preset || settings.value.cloudinary_upload_preset || '').trim(),
));

const form = reactive({
    api_base_url: '',
    api_token: '',
    gallery_api_base_url: '',
    gallery_api_token: '',
    sync_enabled: true,
    support_phone: '',
    cloudinary_cloud_name: '',
    cloudinary_api_key: '',
    cloudinary_api_secret: '',
    cloudinary_upload_preset: '',
    cloudinary_folder: '',
    image_transfer_async_enabled: true,
    image_transfer_batch_size: 10,
});

async function load() {
    const [settingsRes, optionsRes] = await Promise.all([
        api.get('/admin/vinstack/settings'),
        api.get('/admin/settings/vehicle-options'),
    ]);
    settings.value = settingsRes.data.data;
    form.api_base_url = settingsRes.data.data.api_base_url || '';
    form.gallery_api_base_url = settingsRes.data.data.gallery_api_base_url || '';
    form.sync_enabled = settingsRes.data.data.sync_enabled ?? true;
    form.support_phone = settingsRes.data.data.support_phone || '';
    form.cloudinary_cloud_name = settingsRes.data.data.cloudinary_cloud_name || '';
    form.cloudinary_upload_preset = settingsRes.data.data.cloudinary_upload_preset || '';
    form.cloudinary_folder = settingsRes.data.data.cloudinary_folder || '';
    form.image_transfer_async_enabled = settingsRes.data.data.image_transfer_async_enabled ?? true;
    form.image_transfer_batch_size = Number(settingsRes.data.data.image_transfer_batch_size ?? 10);
    form.api_token = '';
    form.gallery_api_token = '';
    form.cloudinary_api_key = '';
    form.cloudinary_api_secret = '';
    vehicleOptions.value = optionsRes.data.data;
}

async function saveVehicleOptions() {
    savingOptions.value = true;

    try {
        await api.put('/admin/settings/vehicle-options', vehicleOptions.value);
        toast.add({ severity: 'success', summary: t('settings.optionsSaved'), life: 3000 });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || 'فشل حفظ الخيارات',
            life: 4000,
        });
    } finally {
        savingOptions.value = false;
    }
}

async function testGalleryConnection() {
    testingGallery.value = true;

    try {
        const { data } = await api.post('/admin/vinstack/settings/gallery-test');
        toast.add({
            severity: 'success',
            summary: 'إعدادات المعرض',
            detail: data.message || data.data?.message,
            life: 5000,
        });
    } catch (e) {
        toast.add({
            severity: 'warn',
            summary: 'إعدادات المعرض',
            detail: e.response?.data?.message || e.response?.data?.data?.message || 'تحقق من الرابط والتوكن ثم احفظ الإعدادات',
            life: 6000,
        });
    } finally {
        testingGallery.value = false;
    }
}

async function testCloudinaryConnection() {
    testingCloudinary.value = true;

    try {
        const { data } = await api.post('/admin/vinstack/settings/cloudinary-test');
        toast.add({
            severity: 'success',
            summary: 'Cloudinary',
            detail: data.message || data.data?.message,
            life: 5000,
        });
    } catch (e) {
        toast.add({
            severity: 'warn',
            summary: 'Cloudinary',
            detail: e.response?.data?.message || e.response?.data?.data?.message || 'تحقق من الإعدادات ثم احفظ',
            life: 6000,
        });
    } finally {
        testingCloudinary.value = false;
    }
}

async function save() {
    saving.value = true;

    try {
        const payload = {
            api_base_url: form.api_base_url,
            gallery_api_base_url: form.gallery_api_base_url,
            sync_enabled: form.sync_enabled,
            support_phone: form.support_phone,
            cloudinary_cloud_name: form.cloudinary_cloud_name,
            cloudinary_upload_preset: form.cloudinary_upload_preset,
            cloudinary_folder: form.cloudinary_folder,
            image_transfer_async_enabled: form.image_transfer_async_enabled,
            image_transfer_batch_size: form.image_transfer_batch_size,
        };

        if (form.api_token) {
            payload.api_token = form.api_token;
        }

        if (form.gallery_api_token) {
            payload.gallery_api_token = form.gallery_api_token;
        }

        if (form.cloudinary_api_key) {
            payload.cloudinary_api_key = form.cloudinary_api_key;
        }

        if (form.cloudinary_api_secret) {
            payload.cloudinary_api_secret = form.cloudinary_api_secret;
        }

        await api.put('/admin/vinstack/settings', payload);
        toast.add({ severity: 'success', summary: t('settings.saved'), life: 3000 });
        await load();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
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
            summary: t('common.error'),
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
            summary: t('common.error'),
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
            summary: t('common.error'),
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
            summary: t('common.error'),
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
            summary: t('common.error'),
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

function confirmClearCache() {
    clearCacheConfirmVisible.value = true;
}

async function clearCache() {
    clearingCache.value = true;

    try {
        const { data } = await api.post('/admin/system/cache/clear');
        clearCacheConfirmVisible.value = false;
        toast.add({
            severity: 'success',
            summary: data.message || t('settings.cacheCleared'),
            life: 3000,
        });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || t('settings.clearCacheFailed'),
            life: 4000,
        });
    } finally {
        clearingCache.value = false;
    }
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
            summary: t('common.error'),
            detail: e.response?.data?.message || 'فشل مسح السجل',
            life: 4000,
        });
    } finally {
        clearingLogs.value = false;
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
            summary: t('common.error'),
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
            summary: t('common.error'),
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
            summary: t('common.error'),
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

function confirmDeleteBackup(filename) {
    confirm.require({
        message: 'هل تريد حذف هذا النسخة الاحتياطية؟',
        header: 'حذف نسخة احتياطية',
        icon: 'pi pi-trash',
        rejectLabel: 'إلغاء',
        acceptLabel: 'حذف',
        acceptClass: 'p-button-danger',
        accept: () => deleteBackup(filename),
    });
}

async function deleteBackup(filename) {
    deletingFilename.value = filename;

    try {
        const { data } = await api.delete(`/admin/system/backups/${encodeURIComponent(filename)}`);
        toast.add({
            severity: 'success',
            summary: data.message || 'تم الحذف',
            life: 3000,
        });
        await loadBackups();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: e.response?.data?.message || 'فشل حذف النسخة الاحتياطية',
            life: 4000,
        });
    } finally {
        deletingFilename.value = '';
    }
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
            summary: t('common.error'),
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
            summary: t('common.error'),
            detail: e.response?.data?.message || 'فشل استرجاع الملف',
            life: 5000,
        });
    } finally {
        restoringUpload.value = false;
    }
}

onMounted(async () => {
    logMessage.value = t('settings.logPrompt');
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
    grid-template-columns: 1fr;
    gap: 1rem;
    padding: 1rem;
}

@media (min-width: 900px) {
    .settings-group__cards {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
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

.settings-card--gallery {
    border-color: color-mix(in srgb, var(--admin-accent, #3b82f6) 28%, var(--vs-border));
}

.gallery-expired-tag {
    margin-inline-start: auto;
    flex-shrink: 0;
}

.gallery-status-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-bottom: 0.65rem;
}

.gallery-status-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.15rem 0.45rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 600;
}

.gallery-status-chip--ok {
    color: #166534;
    background: rgba(22, 163, 74, 0.14);
}

.gallery-status-chip--warn {
    color: #b45309;
    background: rgba(245, 158, 11, 0.16);
}

.gallery-path-hint {
    margin: 0 0 0.65rem;
}

.gallery-path-hint code {
    display: block;
    font-size: 0.68rem;
    word-break: break-all;
    color: var(--admin-accent);
}

.gallery-test-btn {
    width: 100%;
}

.gallery-input :deep(input) {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.8125rem;
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

.sync-datetime {
    unicode-bidi: isolate;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.sync-cron-help {
    margin: 0.85rem 0 0;
    font-size: 0.8125rem;
    line-height: 1.55;
    color: var(--vs-text-muted);
}

.sync-cron-help--muted {
    margin-top: 0.5rem;
}

.sync-cron-help__list {
    margin: 0.5rem 0 0;
    padding-inline-start: 1.15rem;
    font-size: 0.8125rem;
    line-height: 1.6;
    color: var(--vs-text-muted);
}

.sync-cron-help__list code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.75rem;
    background: var(--admin-sidebar-active, rgba(0, 0, 0, 0.04));
    padding: 0.1rem 0.35rem;
    border-radius: 4px;
}

.field--inline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.transfer-batch-input :deep(.p-inputnumber-input) {
    width: 5rem;
}

.transfer-page-link {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    margin-top: 0.5rem;
    padding: 0.55rem 0.75rem;
    border: 1px solid var(--vs-border);
    border-radius: 8px;
    background: var(--vs-surface-elevated, #f8fafc);
    color: var(--vs-primary, #4a3558);
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.transfer-page-link:hover {
    border-color: color-mix(in srgb, var(--vs-primary, #4a3558) 35%, var(--vs-border));
    background: color-mix(in srgb, var(--vs-primary, #4a3558) 8%, #fff);
}

@media (max-width: 640px) {
    .settings-group__actions {
        flex-direction: column;
        align-items: stretch;
    }

    .settings-group__actions :deep(.p-button),
    .settings-card__footer :deep(.p-button),
    .system-actions :deep(.p-button) {
        width: 100%;
        min-height: 44px;
        justify-content: center;
    }

    .settings-card__head {
        padding: 1rem 0.85rem 0;
    }

    .settings-card__body {
        padding: 0.75rem 0.85rem 1rem;
    }

    .restorable-list__item {
        flex-direction: column;
        align-items: stretch;
    }

    .backup-row-actions {
        justify-content: flex-end;
    }
}
</style>
