<template>
    <section v-if="enabled && items.length" class="spotlight admin-surface">
        <div class="spotlight__head">
            <div>
                <h3>{{ t('auctions.spotlightTitle') }}</h3>
                <p class="muted">{{ t('auctions.spotlightHint') }}</p>
            </div>
            <div v-if="isAdmin" class="spotlight__admin">
                <Button
                    :label="t('auctions.spotlightDisable')"
                    icon="pi pi-eye-slash"
                    size="small"
                    text
                    severity="secondary"
                    :loading="busy"
                    @click="disable"
                />
                <Button
                    :label="t('auctions.spotlightClear')"
                    icon="pi pi-trash"
                    size="small"
                    text
                    severity="danger"
                    :loading="busy"
                    @click="clearAll"
                />
            </div>
        </div>

        <div class="spotlight__track" @mouseenter="paused = true" @mouseleave="paused = false">
            <AuctionRelatedCard
                v-for="row in items"
                :key="row.identifier || row.id"
                class="spotlight__card"
                :vehicle="row"
                :autoplay-ms="2600"
                @open="$emit('open', row)"
            />
        </div>
    </section>

    <section v-else-if="isAdmin && loaded && !enabled" class="spotlight spotlight--off admin-surface">
        <div class="spotlight__head">
            <div>
                <h3>{{ t('auctions.spotlightTitle') }}</h3>
                <p class="muted">{{ t('auctions.spotlightDisabledHint') }}</p>
            </div>
            <Button
                :label="t('auctions.spotlightEnable')"
                icon="pi pi-eye"
                size="small"
                outlined
                :loading="busy"
                @click="enable"
            />
        </div>
    </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Button from 'primevue/button';
import AuctionRelatedCard from './AuctionRelatedCard.vue';
import {
    clearAuctionSpotlight,
    listAuctionSpotlight,
    updateAuctionSpotlightSettings,
} from '../../api/auctions';
import { useAuthStore } from '../../stores/auth';

defineEmits(['open']);

const { t } = useI18n();
const auth = useAuthStore();
const isAdmin = computed(() => auth.isAdmin);

const items = ref([]);
const enabled = ref(true);
const loaded = ref(false);
const busy = ref(false);
const paused = ref(false);
let scrollTimer = null;

async function load() {
    try {
        const { data } = await listAuctionSpotlight();
        items.value = Array.isArray(data.data) ? data.data : [];
        enabled.value = data.meta?.enabled !== false;
    } catch {
        items.value = [];
    } finally {
        loaded.value = true;
    }
}

async function enable() {
    busy.value = true;
    try {
        await updateAuctionSpotlightSettings({ enabled: true });
        enabled.value = true;
        await load();
    } finally {
        busy.value = false;
    }
}

async function disable() {
    busy.value = true;
    try {
        await updateAuctionSpotlightSettings({ enabled: false });
        enabled.value = false;
        items.value = [];
    } finally {
        busy.value = false;
    }
}

async function clearAll() {
    busy.value = true;
    try {
        await clearAuctionSpotlight();
        items.value = [];
    } finally {
        busy.value = false;
    }
}

function startAutoScroll() {
    stopAutoScroll();
    scrollTimer = setInterval(() => {
        if (paused.value) return;
        const track = document.querySelector('.spotlight__track');
        if (! track || track.scrollWidth <= track.clientWidth) return;

        const next = track.scrollLeft + 240;
        if (next + track.clientWidth >= track.scrollWidth - 8) {
            track.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
            track.scrollTo({ left: next, behavior: 'smooth' });
        }
    }, 4200);
}

function stopAutoScroll() {
    if (scrollTimer) {
        clearInterval(scrollTimer);
        scrollTimer = null;
    }
}

onMounted(async () => {
    await load();
    startAutoScroll();
});

onBeforeUnmount(stopAutoScroll);

defineExpose({ reload: load });
</script>

<style scoped>
.spotlight {
    padding: 0.85rem 1rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.spotlight--off {
    opacity: 0.92;
}

.spotlight__head {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: flex-start;
    flex-wrap: wrap;
}

.spotlight__head h3 {
    margin: 0;
    font-size: 0.95rem;
}

.muted {
    margin: 0.2rem 0 0;
    color: var(--vs-text-muted);
    font-size: 0.78rem;
}

.spotlight__admin {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.spotlight__track {
    display: flex;
    gap: 0.75rem;
    overflow-x: auto;
    scroll-behavior: smooth;
    padding-bottom: 0.25rem;
}

.spotlight__card {
    flex: 0 0 min(260px, 78vw);
    max-width: 280px;
}
</style>
