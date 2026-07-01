<template>
    <section class="dealer-dashboard">
        <div class="welcome-card">
            <div>
                <p class="welcome-label">{{ t('dealerDashboard.welcome') }}</p>
                <h2 class="welcome-title">{{ companyName }}</h2>
                <p v-if="auth.user?.name" class="welcome-sub">{{ auth.user.name }}</p>
            </div>
            <i class="pi pi-building welcome-icon" aria-hidden="true" />
        </div>

        <div class="stats-grid">
            <RouterLink :to="{ name: 'dealer.vehicles' }" class="stat-card">
                <span class="stat-value">{{ stats.vehicles_count ?? '—' }}</span>
                <span class="stat-label">{{ t('dealerDashboard.myVehicles') }}</span>
                <i class="pi pi-car stat-icon" />
            </RouterLink>
            <RouterLink :to="{ name: 'dealer.containers' }" class="stat-card stat-card--containers">
                <span class="stat-value">{{ stats.containers_count ?? '—' }}</span>
                <span class="stat-label">{{ t('dealerDashboard.activeContainers') }}</span>
                <i class="pi pi-box stat-icon" />
            </RouterLink>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../api/client';

const { t } = useI18n();
const auth = useAuthStore();
const stats = ref({ vehicles_count: null, containers_count: null });

const companyName = computed(
    () => auth.user?.dealer?.company_name || t('dealerDashboard.portalFallback'),
);

onMounted(async () => {
    try {
        const { data } = await api.get('/dealer/stats');
        stats.value = data;
    } catch {
        stats.value = { vehicles_count: 0, containers_count: 0 };
    }
});
</script>

<style scoped>
.dealer-dashboard {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.welcome-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #18181b 0%, #27272a 100%);
    color: #fff;
    border-radius: 12px;
}

.welcome-label {
    margin: 0 0 0.2rem;
    font-size: 0.78rem;
    color: #a1a1aa;
}

.welcome-title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
}

.welcome-sub {
    margin: 0.35rem 0 0;
    font-size: 0.82rem;
    color: #d4d4d8;
}

.welcome-icon {
    font-size: 2rem;
    opacity: 0.35;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
}

.stat-card {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 1rem 1.1rem;
    background: #fff;
    border: 1px solid #ececef;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.stat-card:hover {
    border-color: #d4d4d8;
    box-shadow: 0 2px 8px rgb(0 0 0 / 6%);
}

.stat-value {
    font-size: 1.6rem;
    font-weight: 700;
    color: #18181b;
    font-variant-numeric: tabular-nums;
}

.stat-label {
    font-size: 0.82rem;
    color: #71717a;
}

.stat-icon {
    position: absolute;
    top: 1rem;
    inset-inline-end: 1rem;
    font-size: 1.25rem;
    color: #a1a1aa;
    opacity: 0.5;
}

.stat-card--containers .stat-value {
    color: #0f766e;
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
