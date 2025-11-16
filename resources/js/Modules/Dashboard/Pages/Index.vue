<script setup lang="ts">
/**
 * DashPilot Dashboard - Main Overview Page
 *
 * Displays comprehensive operations overview including:
 * - Site monitoring status (WordPress + Shopify)
 * - SEO performance scores
 * - Revenue analytics (Shopify)
 * - Recent activities and alerts
 * - Calendar view for scheduled checks
 * - Real-time performance metrics
 *
 * @component
 */
import { ref, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Shared/Layouts/AppLayout.vue";
import StatCard from "@/Shared/Components/StatCard.vue";
import MetricCard from "@/Shared/Components/MetricCard.vue";
import AlertCard from "@/Shared/Components/AlertCard.vue";
import CalendarWidget from "@/Modules/Dashboard/Components/CalendarWidget.vue";
import PerformanceChart from "@/Shared/Components/PerformanceChart.vue";
import { useIntervalFn } from "@vueuse/core";

type DashboardStats = {
    totalSites: number;
    activeSites: number;
    healthySites: number;
    criticalAlerts: number;
    avgUptime: number;
    totalRevenue: number;
    avgSeoScore: number;
    activitiesToday?: number;
};

type DashboardAlert = {
    id: number;
    title: string;
    severity: "critical" | "warning" | "info";
    time: string;
};

type ScheduledCheck = {
    date: string;
    title: string;
    subtitle?: string | null;
    tag?: string | null;
    status?: "info" | "success" | "warning" | "danger";
};

type FeaturedSite = {
    id: number;
    name: string;
    status: string;
    platform: string;
    region?: string | null;
    thumbnail: string;
    logo: string;
    uptime?: string | null;
};

type ActivityItem = {
    id: number;
    action: string;
    description: string;
    user: string;
    site: string | null;
    time: string;
    timestamp: string | null;
};

const props = withDefaults(
    defineProps<{
        stats: DashboardStats & { warningSites?: number };
        recentAlerts: DashboardAlert[];
        scheduledChecks: ScheduledCheck[];
        featuredSites: FeaturedSite[];
        activities?: ActivityItem[];
    }>(),
    {
        stats: () => ({
            totalSites: 0,
            activeSites: 0,
            healthySites: 0,
            criticalAlerts: 0,
            avgUptime: 0,
            totalRevenue: 0,
            avgSeoScore: 0,
            activitiesToday: 0,
            warningSites: 0,
        }),
        recentAlerts: () => [],
        scheduledChecks: () => [],
        featuredSites: () => [],
        activities: () => [],
    },
);

// Reactive state for real-time updates
const liveStats = ref({ ...props.stats });
const isLiveMode = ref(true);

/**
 * Simulates real-time data updates
 * In production, this would fetch from Laravel backend via Inertia
 */
const updateLiveData = () => {
    if (isLiveMode.value) {
        // Simulate minor fluctuations in metrics
        liveStats.value.avgUptime = Math.min(
            100,
            props.stats.avgUptime + Math.random() * 0.2,
        );
    }
};

// Auto-refresh every 30 seconds using VueUse
useIntervalFn(updateLiveData, 30000);

onMounted(() => {
    console.log("[DashPilot] Dashboard mounted with stats:", props.stats);
});
</script>

<template>
    <Head title="Dashboard - DashPilot" />

    <AppLayout>
        <section class="space-y-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-bold text-white">Overview</h1>
                    <p class="mt-1 text-sm text-gray-400">
                        Operations dashboard for all managed sites
                    </p>
                </div>
                <div
                    class="flex items-center gap-2 rounded-lg border border-gray-700 bg-gray-800 px-4 py-2"
                >
                    <div class="relative">
                        <div
                            class="h-2 w-2 rounded-full"
                            :class="
                                isLiveMode ? 'bg-emerald-500' : 'bg-gray-500'
                            "
                        ></div>
                        <div
                            v-if="isLiveMode"
                            class="absolute inset-0 h-2 w-2 rounded-full bg-emerald-500 animate-ping"
                        ></div>
                    </div>
                    <span class="text-sm font-medium text-gray-300">
                        {{ isLiveMode ? "Live updates" : "Paused" }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
                <div class="space-y-6 xl:col-span-8">
                    <!-- Top Stats Cards (5 large cards) -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
                        <!-- Site Monitoring Card -->
                        <StatCard
                            title="Site Monitoring"
                            subtitle="Track site performance"
                            :value="stats.activeSites"
                            :total="stats.totalSites"
                            label="sites monitored"
                            status="healthy"
                            :metrics="[
                                {
                                    label: 'Uptime',
                                    value: `${liveStats.avgUptime.toFixed(1)}%`,
                                },
                                {
                                    label: 'Alerts',
                                    value: `${stats.criticalAlerts} open`,
                                    variant:
                                        stats.criticalAlerts > 0
                                            ? 'warning'
                                            : 'success',
                                },
                            ]"
                            image-query="person working on laptop with multiple website dashboards"
                            :href="route('sites.index')"
                        />

                        <!-- SEO Performance Card -->
                        <StatCard
                            title="SEO Performance"
                            subtitle="Check SEO scores now"
                            :value="stats.avgSeoScore"
                            suffix="/100"
                            label="average score"
                            status="good"
                            :metrics="[
                                { label: 'Last updated', value: '10 AM' },
                                {
                                    label: 'Issues',
                                    value: '5 unresolved',
                                    variant: 'warning',
                                },
                            ]"
                            badge-text="View Report"
                            badge-variant="primary"
                            image-query="smiling professional woman in red sweater"
                            :href="route('metrics.index')"
                        />

                        <!-- Revenue Overview Card (Shopify) -->
                        <StatCard
                            title="Revenue Overview"
                            subtitle="Shopify earnings this month"
                            :value="stats.totalRevenue.toLocaleString()"
                            prefix="$"
                            label="total revenue"
                            status="growth"
                            :metrics="[
                                {
                                    label: 'Growth',
                                    value: '15% this week',
                                    variant: 'success',
                                },
                                {
                                    label: 'Status',
                                    value: 'Monitoring active',
                                    variant: 'info',
                                },
                            ]"
                            badge-text="View Stats"
                            image-query="financial charts and analytics on laptop screen"
                            :href="route('revenue.index')"
                        />

                        <!-- Recent Activities Card -->
                        <StatCard
                            title="Recent Activities"
                            subtitle="Updates on all sites"
                            :value="stats.activitiesToday ?? 12"
                            label="activities today"
                            status="active"
                            :metrics="[
                                { label: 'Last sync', value: '5 min ago' },
                                {
                                    label: 'Report',
                                    value: 'Weekly summary',
                                    variant: 'info',
                                },
                            ]"
                            image-query="multiple website dashboards and analytics screens"
                            :href="route('activity.index')"
                        />

                        <!-- System Health Card (5th card) -->
                        <StatCard
                            title="System Health"
                            subtitle="Warning sites monitor"
                            :value="stats.warningSites ?? 0"
                            label="sites need attention"
                            status="warning"
                            :metrics="[
                                {
                                    label: 'Healthy',
                                    value: `${stats.healthySites}/${stats.totalSites}`,
                                    variant: 'success',
                                },
                                {
                                    label: 'Status',
                                    value:
                                        stats.warningSites === 0
                                            ? 'All good'
                                            : 'Needs review',
                                    variant:
                                        stats.warningSites === 0
                                            ? 'success'
                                            : 'warning',
                                },
                            ]"
                            image-query="server room with network equipment and monitors"
                            :href="route('sites.index', { status: 'warning' })"
                        />
                    </div>

                    <!-- Calendar Widget -->
                    <CalendarWidget :scheduled-checks="scheduledChecks" />

                    <!-- Performance Chart -->
                    <div
                        class="rounded-xl border border-gray-700 bg-gray-800 p-6"
                    >
                        <div
                            class="mb-4 flex flex-wrap items-center justify-between gap-4"
                        >
                            <div>
                                <h3 class="text-lg font-semibold text-white">
                                    Site performance trends
                                </h3>
                                <p class="text-sm text-gray-400">
                                    Uptime vs response time (rolling 7 days)
                                </p>
                            </div>
                            <span class="text-xs text-gray-500"
                                >Refreshed 5 minutes ago</span
                            >
                        </div>
                        <PerformanceChart />
                    </div>
                </div>

                <!-- Right Column: Sidebar (4 cols) -->
                <div class="space-y-6 xl:col-span-4">
                    <!-- Current Operations -->
                    <div
                        class="rounded-xl border border-gray-700 bg-gray-800 p-6"
                    >
                        <h3 class="text-lg font-semibold text-white">
                            Current operations
                        </h3>
                        <p class="text-sm text-gray-400">Runbook snapshot</p>
                        <div
                            class="mt-4 overflow-hidden rounded-xl border border-gray-700 bg-gray-900"
                        >
                            <img
                                src="/placeholder.svg?height=300&width=400"
                                alt="Current operations preview"
                                class="h-48 w-full object-cover opacity-60"
                            />
                        </div>
                        <dl class="mt-4 space-y-2 text-sm text-gray-300">
                            <div class="flex justify-between">
                                <dt>Active sites</dt>
                                <dd class="font-semibold text-white">
                                    {{ stats.activeSites }}/{{
                                        stats.totalSites
                                    }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>Status</dt>
                                <dd class="font-semibold text-emerald-400">
                                    {{
                                        stats.healthySites >=
                                        stats.totalSites * 0.8
                                            ? "Healthy"
                                            : "Needs attention"
                                    }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>Critical alerts</dt>
                                <dd
                                    class="font-semibold"
                                    :class="
                                        stats.criticalAlerts > 0
                                            ? 'text-yellow-400'
                                            : 'text-gray-300'
                                    "
                                >
                                    {{ stats.criticalAlerts }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Performance Metrics -->
                    <div
                        class="rounded-xl border border-gray-700 bg-gray-800 p-6"
                    >
                        <h3 class="text-lg font-semibold text-white">
                            Performance metrics
                        </h3>
                        <div class="mt-4 space-y-4">
                            <MetricCard
                                icon="chart-bar"
                                label="Uptime"
                                sublabel="Rolling 7-day"
                                :value="liveStats.avgUptime.toFixed(0)"
                                suffix="%"
                                variant="success"
                            />

                            <MetricCard
                                icon="currency-dollar"
                                label="Revenue"
                                sublabel="Last month"
                                :value="(stats.totalRevenue / 1000).toFixed(2)"
                                prefix="$"
                                suffix="k"
                                variant="info"
                            />

                            <MetricCard
                                icon="chart-line"
                                label="SEO score"
                                sublabel="Portfolio average"
                                :value="stats.avgSeoScore"
                                variant="warning"
                            />
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div
                        class="rounded-xl border border-gray-700 bg-gray-800 p-6"
                    >
                        <h3 class="text-lg font-semibold text-white">
                            Notifications
                        </h3>

                        <AlertCard
                            class="mt-4"
                            title="New alert: High traffic detected!"
                            message="Auto-scaled Shopify worker pool to keep up with demand."
                            variant="success"
                        />

                        <div class="mt-4 space-y-3">
                            <article
                                v-for="alert in recentAlerts.slice(0, 4)"
                                :key="alert.id"
                                class="rounded-lg border border-gray-800 bg-gray-950 p-3"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-white"
                                        >
                                            {{ alert.title }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ alert.time }}
                                        </p>
                                    </div>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-semibold uppercase"
                                        :class="{
                                            'bg-red-500/10 text-red-400':
                                                alert.severity === 'critical',
                                            'bg-yellow-500/10 text-yellow-400':
                                                alert.severity === 'warning',
                                            'bg-blue-500/10 text-blue-400':
                                                alert.severity === 'info',
                                        }"
                                    >
                                        {{ alert.severity }}
                                    </span>
                                </div>
                            </article>

                            <p
                                v-if="recentAlerts.length === 0"
                                class="text-center text-sm text-gray-500"
                            >
                                No alerts in the queue. All systems are nominal.
                            </p>
                        </div>
                    </div>

                    <!-- Activity Feed -->
                    <div
                        class="rounded-xl border border-gray-700 bg-gray-800 p-6"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white">
                                Activity Feed
                            </h3>
                            <Link
                                :href="route('activity.index')"
                                class="text-sm font-semibold text-blue-400 transition hover:text-blue-300"
                            >
                                View all →
                            </Link>
                        </div>

                        <div class="space-y-3">
                            <article
                                v-for="activity in activities.slice(0, 6)"
                                :key="activity.id"
                                class="flex items-start gap-3 rounded-lg border border-gray-800 bg-gray-950 p-3 transition hover:border-gray-700"
                            >
                                <div
                                    class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-blue-400"
                                ></div>
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-sm font-semibold text-white"
                                    >
                                        {{ activity.action }}
                                    </p>
                                    <p class="text-sm text-gray-400">
                                        {{ activity.description }}
                                    </p>
                                    <div
                                        class="mt-1 flex items-center gap-2 text-xs text-gray-500"
                                    >
                                        <span>{{ activity.user }}</span>
                                        <span v-if="activity.site">•</span>
                                        <span
                                            v-if="activity.site"
                                            class="truncate"
                                        >
                                            {{ activity.site }}
                                        </span>
                                        <span>•</span>
                                        <span>{{ activity.time }}</span>
                                    </div>
                                </div>
                            </article>

                            <p
                                v-if="activities.length === 0"
                                class="rounded-lg border border-dashed border-gray-700/70 p-6 text-center text-sm text-gray-500"
                            >
                                No recent activity logged.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Featured Sites Carousel -->
            <section class="space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-white">Featured sites</h2>
                        <p class="text-sm text-gray-400">
                            Tap any card to jump directly into the site workspace.
                        </p>
                    </div>
                    <Link
                        :href="route('sites.index')"
                        class="text-sm font-semibold text-blue-400 transition hover:text-blue-300"
                    >
                        View all sites →
                    </Link>
                </div>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="site in featuredSites"
                        :key="site.id"
                        :href="route('sites.show', site.id)"
                        class="group relative overflow-hidden rounded-2xl border border-gray-700/70 bg-gray-900/40 shadow-xl transition hover:-translate-y-1 hover:border-blue-500/60"
                    >
                        <img
                            :src="site.thumbnail"
                            :alt="site.name"
                            class="h-40 w-full object-cover transition duration-500 group-hover:scale-105"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/70 to-transparent" />

                        <div class="relative flex flex-col gap-3 p-5">
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-12 rounded-xl bg-gray-900/70 p-1 shadow-lg">
                                    <img
                                        :src="site.logo"
                                        :alt="`${site.name} logo`"
                                        class="h-full w-full rounded-lg object-cover"
                                    />
                                </div>
                                <div>
                                    <p class="font-semibold text-white">
                                        {{ site.name }}
                                    </p>
                                    <p class="text-xs uppercase tracking-wide text-gray-400">
                                        {{ site.region ?? "Global" }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase">
                                <span
                                    class="rounded-full px-2 py-0.5"
                                    :class="{
                                        'bg-green-500/15 text-green-400': site.status === 'healthy',
                                        'bg-yellow-500/15 text-yellow-400': site.status === 'warning',
                                        'bg-red-500/15 text-red-400': site.status === 'critical',
                                    }"
                                >
                                    {{ site.status }}
                                </span>
                                <span class="rounded-full bg-blue-500/10 px-2 py-0.5 text-blue-300">
                                    {{ site.platform }}
                                </span>
                                <span v-if="site.uptime" class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-emerald-300">
                                    {{ site.uptime }}% uptime
                                </span>
                            </div>
                        </div>
                    </Link>

                    <div
                        v-if="featuredSites.length === 0"
                        class="rounded-2xl border border-gray-700/70 p-6 text-center text-gray-400"
                    >
                        No highlighted sites yet.
                    </div>
                </div>
            </section>
        </section>
    </AppLayout>
</template>
