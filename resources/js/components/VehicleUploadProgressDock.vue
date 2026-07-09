<template>
    <Teleport to="body">
        <Transition name="upload-dock">
            <aside
                v-if="uploadStore.dockJobs.length"
                class="upload-dock"
                :class="{
                    'upload-dock--active': uploadStore.hasActive,
                    'upload-dock--minimized': !expanded,
                    'upload-dock--failed': showCloseButton,
                }"
                aria-live="polite"
                aria-label="تقدم رفع الصور"
            >
                <header class="upload-dock__header" :class="{ 'upload-dock__header--minimized': !expanded }">
                    <button
                        type="button"
                        class="upload-dock__title-wrap upload-dock__title-btn"
                        :disabled="expanded || showCloseButton"
                        @click="expanded = true"
                    >
                        <span class="upload-dock__icon" :class="{ 'upload-dock__icon--pulse': uploadStore.hasActive }">
                            <i :class="headerIcon" />
                        </span>
                        <div class="upload-dock__title-block">
                            <h2 class="upload-dock__title">
                                {{ headerTitle }}
                            </h2>
                            <p class="upload-dock__subtitle">
                                {{ summaryText }}
                            </p>
                        </div>
                    </button>

                    <div class="upload-dock__actions">
                        <span v-if="!expanded && primaryJob" class="upload-dock__compact-percent">
                            {{ primaryJob.progress }}%
                        </span>
                        <button
                            v-if="finishedCount && expanded && !showCloseButton"
                            type="button"
                            class="upload-dock__ghost-btn"
                            @click="uploadStore.dismissAllFinished()"
                        >
                            مسح المكتمل
                        </button>
                        <button
                            type="button"
                            class="upload-dock__icon-btn"
                            :class="{ 'upload-dock__icon-btn--danger': showCloseButton }"
                            :aria-expanded="expanded"
                            :aria-label="showCloseButton ? 'إغلاق' : (expanded ? 'تصغير' : 'توسيع')"
                            @click="onHeaderAction"
                        >
                            <i :class="headerActionIcon" />
                        </button>
                    </div>
                </header>

                <div v-show="expanded" class="upload-dock__body">
                    <div v-if="primaryJob" class="upload-dock__primary">
                        <div class="upload-dock__primary-top">
                            <div class="upload-dock__vehicle">
                                <span class="upload-dock__vehicle-name">{{ primaryJob.vehicleLabel }}</span>
                                <span class="upload-dock__stage-pill">{{ primaryJob.stageLabel }}</span>
                            </div>
                            <span class="upload-dock__percent">{{ primaryJob.progress }}%</span>
                        </div>

                        <div class="upload-dock__progress-track">
                            <div
                                class="upload-dock__progress-fill"
                                :class="progressClass(primaryJob)"
                                :style="{ width: `${primaryJob.progress}%` }"
                            />
                        </div>

                        <div class="upload-dock__meta">
                            <span class="upload-dock__message">{{ primaryJob.message }}</span>
                            <span v-if="etaText(primaryJob)" class="upload-dock__eta">{{ etaText(primaryJob) }}</span>
                        </div>

                        <p v-if="primaryJob.error" class="upload-dock__error">
                            <i class="pi pi-exclamation-circle" />
                            {{ primaryJob.error }}
                        </p>

                        <p v-else-if="primaryJob.currentFileName && isActive(primaryJob)" class="upload-dock__file">
                            <i class="pi pi-image" />
                            {{ primaryJob.currentFileName }}
                        </p>
                    </div>

                    <div v-if="uploadStore.dockJobs.length > 1" class="upload-dock__list">
                    <article
                        v-for="job in uploadStore.dockJobs"
                        :key="job.id"
                        class="upload-dock__item"
                        :class="`upload-dock__item--${job.status}`"
                    >
                        <div class="upload-dock__item-head">
                            <div>
                                <strong>{{ job.vehicleLabel }}</strong>
                                <span class="upload-dock__item-stage">{{ job.stageLabel }}</span>
                            </div>
                            <button
                                v-if="!isActive(job)"
                                type="button"
                                class="upload-dock__icon-btn upload-dock__icon-btn--small"
                                aria-label="إخفاء"
                                @click="uploadStore.dismissJob(job.id)"
                            >
                                <i class="pi pi-times" />
                            </button>
                        </div>

                        <div class="upload-dock__progress-track upload-dock__progress-track--thin">
                            <div
                                class="upload-dock__progress-fill"
                                :class="progressClass(job)"
                                :style="{ width: `${job.progress}%` }"
                            />
                        </div>

                        <p class="upload-dock__item-message">
                            <template v-if="job.type === 'images' && isActive(job)">
                                {{ job.completed }} / {{ job.total }} صورة
                            </template>
                            <template v-else>
                                {{ job.message }}
                            </template>
                        </p>

                        <p v-if="job.error" class="upload-dock__item-error">{{ job.error }}</p>
                    </article>
                    </div>
                </div>

                <div v-if="!expanded && primaryJob" class="upload-dock__mini-track">
                    <div
                        class="upload-dock__progress-fill"
                        :class="progressClass(primaryJob)"
                        :style="{ width: `${primaryJob.progress}%` }"
                    />
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useVehicleUploadStore } from '../stores/vehicleUpload';

const uploadStore = useVehicleUploadStore();
const expanded = ref(true);

const primaryJob = computed(() => uploadStore.dockJobs[0] ?? null);

const showCloseButton = computed(() => {
    if (! primaryJob.value) {
        return false;
    }

    if (primaryJob.value.status === 'failed') {
        return true;
    }

    return ! uploadStore.hasActive && uploadStore.dockJobs.some((job) => job.status === 'failed');
});

const headerTitle = computed(() => {
    if (primaryJob.value?.status === 'failed') {
        return 'فشل الرفع';
    }

    if (uploadStore.hasActive) {
        return 'جاري رفع الصور';
    }

    return 'اكتمل الرفع';
});

const headerIcon = computed(() => {
    if (primaryJob.value?.status === 'failed') {
        return 'pi pi-exclamation-triangle';
    }

    if (uploadStore.hasActive) {
        return 'pi pi-cloud-upload';
    }

    return 'pi pi-check-circle';
});

const headerActionIcon = computed(() => {
    if (showCloseButton.value) {
        return 'pi pi-times';
    }

    return expanded.value ? 'pi pi-chevron-down' : 'pi pi-chevron-up';
});

const finishedCount = computed(() => uploadStore.dockJobs.filter(
    (job) => ['completed', 'failed'].includes(job.status),
).length);

const summaryText = computed(() => {
    const active = uploadStore.activeJobs.length;

    if (active > 0) {
        return active === 1 ? 'عملية رفع واحدة قيد التنفيذ' : `${active} عمليات رفع قيد التنفيذ`;
    }

    const done = uploadStore.dockJobs.filter((job) => job.status === 'completed').length;
    const failed = uploadStore.dockJobs.filter((job) => job.status === 'failed').length;

    if (failed > 0) {
        return `اكتمل ${done}، فشل ${failed}`;
    }

    return `${done} عملية مكتملة`;
});

watch(
    () => uploadStore.activeJobs.length,
    (count, previous) => {
        if (count > previous) {
            expanded.value = true;
        }
    },
);

function onHeaderAction() {
    if (showCloseButton.value) {
        if (primaryJob.value?.status === 'failed') {
            uploadStore.dismissJob(primaryJob.value.id);
        } else {
            uploadStore.dismissAllFinished();
        }

        expanded.value = true;

        return;
    }

    expanded.value = ! expanded.value;
}

function isActive(job) {
    return ['queued', 'uploading', 'processing', 'refreshing'].includes(job.status);
}

function progressClass(job) {
    if (job.status === 'failed') {
        return 'upload-dock__progress-fill--failed';
    }

    if (job.status === 'completed') {
        return 'upload-dock__progress-fill--done';
    }

    if (job.phase === 'refresh') {
        return 'upload-dock__progress-fill--refresh';
    }

    return 'upload-dock__progress-fill--active';
}

function etaText(job) {
    const seconds = uploadStore.estimateEta(job);

    if (! seconds || seconds < 2) {
        return '';
    }

    if (seconds < 60) {
        return `متبقي ~${seconds} ث`;
    }

    const minutes = Math.ceil(seconds / 60);

    return `متبقي ~${minutes} د`;
}
</script>

<style scoped>
.upload-dock {
    position: fixed;
    inset-inline: 1rem;
    bottom: 1rem;
    z-index: 1200;
    width: min(520px, calc(100vw - 2rem));
    margin-inline: auto;
    padding: 1rem 1.1rem 1.05rem;
    border-radius: 18px;
    border: 1px solid color-mix(in srgb, var(--admin-accent, #3b82f6) 22%, var(--vs-border));
    background:
        linear-gradient(
            145deg,
            color-mix(in srgb, var(--vs-surface-elevated, #fff) 92%, var(--admin-accent, #3b82f6) 8%),
            color-mix(in srgb, var(--vs-surface, #f8fafc) 96%, transparent)
        );
    box-shadow:
        0 18px 50px rgb(15 23 42 / 18%),
        0 0 0 1px rgb(255 255 255 / 35%) inset;
    backdrop-filter: blur(14px);
}

.upload-dock--active {
    border-color: color-mix(in srgb, var(--admin-accent, #3b82f6) 38%, var(--vs-border));
}

.upload-dock--minimized {
    padding: 0.75rem 0.9rem 0.8rem;
}

.upload-dock--failed {
    border-color: color-mix(in srgb, #dc2626 32%, var(--vs-border));
}

.upload-dock__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0;
}

.upload-dock__header:not(.upload-dock__header--minimized) {
    margin-bottom: 0.85rem;
}

.upload-dock__title-wrap {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    min-width: 0;
    flex: 1;
}

.upload-dock__title-btn {
    border: 0;
    background: transparent;
    padding: 0;
    text-align: start;
    cursor: pointer;
}

.upload-dock__title-btn:disabled {
    cursor: default;
}

.upload-dock__title-block {
    min-width: 0;
}

.upload-dock__icon {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: color-mix(in srgb, var(--admin-accent, #3b82f6) 14%, transparent);
    color: var(--admin-accent, #3b82f6);
    flex-shrink: 0;
}

.upload-dock__icon--pulse {
    animation: upload-pulse 1.8s ease-in-out infinite;
}

.upload-dock__title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text-primary, var(--vs-text));
}

.upload-dock__subtitle {
    margin: 0.15rem 0 0;
    font-size: 0.78rem;
    color: var(--text-muted, var(--vs-text-muted));
}

.upload-dock__actions {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
}

.upload-dock__ghost-btn {
    border: 0;
    background: transparent;
    color: var(--vs-text-secondary);
    font-size: 0.72rem;
    font-weight: 600;
    cursor: pointer;
    padding: 0.25rem 0.45rem;
    border-radius: 8px;
}

.upload-dock__ghost-btn:hover {
    background: color-mix(in srgb, var(--vs-border) 55%, transparent);
}

.upload-dock__icon-btn {
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    border: 1px solid var(--vs-border);
    border-radius: 10px;
    background: var(--vs-surface);
    color: var(--vs-text-secondary);
    cursor: pointer;
}

.upload-dock__icon-btn--danger {
    border-color: color-mix(in srgb, #dc2626 35%, var(--vs-border));
    color: #dc2626;
    background: color-mix(in srgb, #dc2626 8%, var(--vs-surface));
}

.upload-dock__compact-percent {
    font-size: 0.82rem;
    font-weight: 800;
    color: var(--admin-accent, #3b82f6);
    font-variant-numeric: tabular-nums;
}

.upload-dock__mini-track {
    position: relative;
    height: 5px;
    margin-top: 0.55rem;
    border-radius: 999px;
    overflow: hidden;
    background: color-mix(in srgb, var(--vs-border) 70%, transparent);
}

.upload-dock__error {
    display: flex;
    align-items: flex-start;
    gap: 0.35rem;
    margin: 0.5rem 0 0;
    font-size: 0.74rem;
    color: #dc2626;
    line-height: 1.4;
}

.upload-dock__primary-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.55rem;
}

.upload-dock__vehicle {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    min-width: 0;
    flex-wrap: wrap;
}

.upload-dock__vehicle-name {
    font-size: 0.86rem;
    font-weight: 700;
    color: var(--text-primary, var(--vs-text));
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 240px;
}

.upload-dock__stage-pill {
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--admin-accent, #3b82f6) 12%, transparent);
    color: var(--admin-accent, #3b82f6);
    font-size: 0.68rem;
    font-weight: 700;
}

.upload-dock__percent {
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--admin-accent, #3b82f6);
    font-variant-numeric: tabular-nums;
}

.upload-dock__progress-track {
    position: relative;
    height: 10px;
    border-radius: 999px;
    overflow: hidden;
    background: color-mix(in srgb, var(--vs-border) 70%, transparent);
}

.upload-dock__progress-track--thin {
    height: 6px;
    margin-top: 0.45rem;
}

.upload-dock__progress-fill {
    height: 100%;
    border-radius: inherit;
    transition: width 0.25s ease;
}

.upload-dock__progress-fill--active {
    background: linear-gradient(90deg, #60a5fa, var(--admin-accent, #3b82f6));
    background-size: 200% 100%;
    animation: upload-stripes 1.2s linear infinite;
}

.upload-dock__progress-fill--refresh {
    background: linear-gradient(90deg, #a78bfa, #6366f1);
    animation: upload-indeterminate 1.4s ease-in-out infinite;
}

.upload-dock__progress-fill--done {
    background: linear-gradient(90deg, #34d399, #059669);
}

.upload-dock__progress-fill--failed {
    background: linear-gradient(90deg, #f87171, #dc2626);
}

.upload-dock__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-top: 0.45rem;
}

.upload-dock__message {
    font-size: 0.78rem;
    color: var(--vs-text-secondary);
}

.upload-dock__eta {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--admin-accent, #3b82f6);
    white-space: nowrap;
}

.upload-dock__file {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin: 0.45rem 0 0;
    font-size: 0.72rem;
    color: var(--text-muted, var(--vs-text-muted));
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.upload-dock__list {
    margin-top: 0.85rem;
    padding-top: 0.85rem;
    border-top: 1px dashed color-mix(in srgb, var(--vs-border) 80%, transparent);
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    max-height: 220px;
    overflow-y: auto;
}

.upload-dock__item {
    padding: 0.55rem 0.65rem;
    border-radius: 12px;
    background: color-mix(in srgb, var(--vs-surface-elevated, #fff) 88%, transparent);
    border: 1px solid color-mix(in srgb, var(--vs-border) 75%, transparent);
}

.upload-dock__item-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
}

.upload-dock__item-head strong {
    display: block;
    font-size: 0.8rem;
}

.upload-dock__item-stage {
    display: inline-block;
    margin-top: 0.15rem;
    font-size: 0.68rem;
    color: var(--vs-text-muted);
}

.upload-dock__item-message {
    margin: 0.35rem 0 0;
    font-size: 0.72rem;
    color: var(--vs-text-secondary);
}

.upload-dock__item-error {
    margin: 0.25rem 0 0;
    font-size: 0.7rem;
    color: #dc2626;
}

.upload-dock-enter-active,
.upload-dock-leave-active {
    transition: transform 0.28s ease, opacity 0.28s ease;
}

.upload-dock-enter-from,
.upload-dock-leave-to {
    opacity: 0;
    transform: translateY(18px);
}

@keyframes upload-stripes {
    0% { background-position: 100% 0; }
    100% { background-position: -100% 0; }
}

@keyframes upload-indeterminate {
    0%, 100% { opacity: 0.65; }
    50% { opacity: 1; }
}

@keyframes upload-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@media (max-width: 640px) {
    .upload-dock {
        inset-inline: 0.65rem;
        bottom: 0.65rem;
        width: auto;
        padding: 0.9rem;
    }

    .upload-dock__vehicle-name {
        max-width: 160px;
    }
}
</style>
