<template>
    <AppLayout>
        <section class="space-y-8">
            <Breadcrumbs
                :items="[
                    { label: 'Dashboard', href: route('dashboard') },
                    { label: 'Sites', href: route('sites.index') },
                    { label: site.name },
                ]"
            />
            <!-- Hero -->
            <div
                class="relative overflow-hidden rounded-3xl border border-gray-700/70 bg-gray-900/60"
            >
                <img
                    :src="site.thumbnail"
                    :alt="site.name"
                    class="absolute inset-0 h-full w-full object-cover opacity-40"
                />
                <div
                    class="relative flex flex-col gap-6 bg-gradient-to-r from-gray-950/90 via-gray-950/70 to-transparent p-6 md:flex-row md:items-center md:justify-between"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="h-16 w-16 rounded-2xl bg-gray-900/70 p-2 shadow-2xl"
                        >
                            <img
                                :src="site.logo"
                                :alt="`${site.name} logo`"
                                class="h-full w-full rounded-xl object-cover"
                            />
                        </div>
                        <div>
                            <p
                                class="text-xs uppercase tracking-widest text-gray-400"
                            >
                                {{ platformLabel }}
                            </p>
                            <h1 class="text-3xl font-bold text-white">
                                {{ site.name }}
                            </h1>
                            <div
                                class="flex flex-wrap items-center gap-3 text-sm text-gray-300"
                            >
                                <span>{{ site.industry }}</span>
                                <span class="text-gray-600">•</span>
                                <span>{{ site.region || "Global" }}</span>
                                <span class="text-gray-600">•</span>
                                <a
                                    :href="site.url"
                                    target="_blank"
                                    class="text-blue-400 hover:text-blue-300"
                                    >{{ site.url }}</a
                                >
                            </div>
                        </div>
                    </div>
                    <div
                        class="flex flex-col gap-3 text-sm font-semibold text-white sm:flex-row sm:items-center"
                    >
                        <span
                            class="inline-flex items-center gap-2 rounded-full px-4 py-1.5"
                            :class="statusBadge"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-current"
                            ></span>
                            {{ site.status }}
                        </span>
                        <Link
                            :href="route('sites.health-check', site.id)"
                            method="post"
                            as="button"
                            data-testid="run-health-check"
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-blue-600 px-4 py-1.5 text-white transition hover:bg-blue-700"
                        >
                            <PlayIcon class="h-4 w-4" />
                            Run Health Check
                        </Link>
                        <Link
                            :href="route('sites.index')"
                            class="inline-flex items-center justify-center gap-2 rounded-full border border-gray-600/60 px-4 py-1.5 text-gray-200 transition hover:border-white/60 hover:text-white"
                        >
                            <ArrowLeftIcon class="h-4 w-4" />
                            Back to sites
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-2xl border border-gray-700/70 bg-gray-900/60 p-5 shadow-lg"
                >
                    <p class="text-sm uppercase tracking-wide text-gray-400">
                        {{ card.label }}
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-white">
                        {{ card.value }}
                    </p>
                    <p
                        class="mt-2 text-sm"
                        :class="
                            card.tone === 'success'
                                ? 'text-emerald-400'
                                : card.tone === 'warning'
                                  ? 'text-yellow-400'
                                  : 'text-blue-400'
                        "
                    >
                        {{ card.delta }}
                    </p>
                </article>
            </div>

            <!-- Performance + Client -->
            <div class="grid gap-6 lg:grid-cols-3">
                <div
                    class="rounded-3xl border border-gray-700/70 bg-gray-900/60 p-6 lg:col-span-2"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm uppercase tracking-wide text-gray-400"
                            >
                                Performance history
                            </p>
                            <p class="text-xl font-semibold text-white">
                                Uptime & response trends
                            </p>
                        </div>
                        <span class="text-xs text-gray-500"
                            >Last check
                            {{ formatRelativeTime(site.lastChecked) }}</span
                        >
                    </div>
                    <PerformanceChart :series="chart" />
                </div>

                <div
                    class="rounded-3xl border border-gray-700/70 bg-gray-900/60 p-6"
                >
                    <h3 class="text-lg font-semibold text-white">
                        Client & access
                    </h3>
                    <dl class="mt-4 space-y-3 text-sm text-gray-300">
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-400">Client</dt>
                            <dd class="font-semibold text-white">
                                {{ site.client?.name }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-400">Email</dt>
                            <dd class="text-blue-300">
                                {{ site.client?.email ?? "N/A" }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-400">Region</dt>
                            <dd>{{ site.region ?? "Global" }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-400">Last checked</dt>
                            <dd>{{ formatRelativeTime(site.lastChecked) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Tasks & Activity -->
            <div class="grid gap-6 lg:grid-cols-2">
                <div
                    class="rounded-3xl border border-gray-700/70 bg-gray-900/60 p-6"
                >
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white">
                            Active tasks
                        </h3>
                        <span class="text-sm text-gray-500"
                            >{{ tasks.length }} items</span
                        >
                    </div>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="task in tasks"
                            :key="task.id"
                            class="rounded-2xl border border-gray-700/60 bg-gray-800/60 p-4"
                        >
                            <div
                                class="flex items-center justify-between text-sm text-gray-400"
                            >
                                <span class="uppercase tracking-widest">{{
                                    task.priority
                                }}</span>
                                <span>{{ task.dueDate ?? "No due date" }}</span>
                            </div>
                            <p class="mt-2 font-semibold text-white">
                                {{ task.title }}
                            </p>
                            <p
                                class="text-xs uppercase tracking-wide text-gray-500"
                            >
                                {{ task.status }}
                            </p>
                        </div>
                        <p
                            v-if="tasks.length === 0"
                            class="rounded-2xl border border-dashed border-gray-700/70 p-6 text-center text-gray-500"
                        >
                            No open tasks for this site.
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-3xl border border-gray-700/70 bg-gray-900/60 p-6"
                >
                    <h3 class="text-lg font-semibold text-white">
                        Recent activity
                    </h3>
                    <ul class="mt-4 space-y-4">
                        <li
                            v-for="item in activity"
                            :key="item.timestamp"
                            class="flex items-start gap-3 rounded-2xl bg-gray-800/50 p-4"
                        >
                            <div
                                class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-blue-400"
                            />
                            <div>
                                <p class="text-sm font-semibold text-white">
                                    {{ item.action }}
                                </p>
                                <p class="text-sm text-gray-400">
                                    {{ item.description }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ formatRelativeTime(item.timestamp) }}
                                </p>
                            </div>
                        </li>
                        <p
                            v-if="activity.length === 0"
                            class="rounded-2xl border border-dashed border-gray-700/70 p-6 text-center text-gray-500"
                        >
                            No recent activity logged.
                        </p>
                    </ul>
                </div>
            </div>

            <!-- SEO Section with Charts -->
            <div class="space-y-6">
                <SEOScoreCard :site="site" />
                
                <!-- SEO Charts -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Uptime Overview Chart -->
                    <div class="rounded-3xl border border-gray-700/70 bg-gray-900/60 p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Uptime Overview</h3>
                        <UptimeChart 
                            v-if="chart.labels && chart.labels.length > 0"
                            :data="{
                                labels: chart.labels || [],
                                values: chart.datasets?.[0]?.data || []
                            }"
                            time-range="7d"
                        />
                        <div v-else class="flex items-center justify-center h-64 text-gray-500">
                            <p class="text-sm">No data available</p>
                        </div>
                    </div>
                    
                    <!-- Response Time Trends Chart -->
                    <div class="rounded-3xl border border-gray-700/70 bg-gray-900/60 p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Response Time Trends</h3>
                        <ResponseTimeChart 
                            v-if="chart.labels && chart.labels.length > 0"
                            :data="{
                                labels: chart.labels || [],
                                values: chart.datasets?.[1]?.data || []
                            }"
                            time-range="7d"
                        />
                        <div v-else class="flex items-center justify-center h-64 text-gray-500">
                            <p class="text-sm">No data available</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Alerts -->
            <div class="rounded-3xl border border-gray-700/70 bg-gray-900/60 p-6">
                <h3 class="text-lg font-semibold text-white">
                    Latest alerts
                </h3>
                <div class="mt-4 space-y-3">
                        <div
                            v-for="alert in alerts"
                            :key="alert.id"
                            class="rounded-2xl border border-gray-700/60 bg-gray-800/60 p-4"
                        >
                            <div class="flex items-center justify-between">
                                <p class="font-semibold text-white">
                                    {{ alert.title }}
                                </p>
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-semibold uppercase"
                                    :class="
                                        alert.severity === 'critical'
                                            ? 'bg-red-500/10 text-red-400'
                                            : alert.severity === 'warning'
                                              ? 'bg-yellow-500/10 text-yellow-400'
                                              : 'bg-blue-500/10 text-blue-400'
                                    "
                                >
                                    {{ alert.severity }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-400">
                                {{ alert.message }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ formatRelativeTime(alert.timestamp) }}
                            </p>
                        </div>

                        <p
                            v-if="alerts.length === 0"
                            class="rounded-2xl border border-dashed border-gray-700/70 p-6 text-center text-gray-500"
                        >
                            No alerts in the last 48 hours.
                        </p>
                </div>
            </div>

            <!-- Reports -->
            <div
                class="rounded-3xl border border-gray-700/70 bg-gray-900/60 p-6"
            >
                <h3 class="text-lg font-semibold text-white">
                    Monthly reports
                </h3>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <article
                        v-for="report in reports"
                        :key="report.month"
                        class="rounded-2xl border border-gray-700/60 bg-gray-800/60 p-4"
                    >
                        <p class="text-sm text-gray-400">{{ report.month }}</p>
                        <p class="mt-2 text-2xl font-semibold text-white">
                            {{ report.uptime }}%
                        </p>
                        <p class="text-sm text-gray-400">Uptime</p>
                        <div class="mt-3 text-sm text-gray-300">
                            <p>Avg response: {{ report.response }}s</p>
                            <p>Incidents: {{ report.incidents }}</p>
                        </div>
                    </article>
                    <p
                        v-if="reports.length === 0"
                        class="rounded-2xl border border-dashed border-gray-700/70 p-6 text-center text-gray-500"
                    >
                        No reports generated yet.
                    </p>
                </div>
            </div>
        </section>
    </AppLayout>
</template>

<script setup lang="ts">
// @ts-nocheck
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Shared/Layouts/AppLayout.vue";
import PerformanceChart from "@/Shared/Components/PerformanceChart.vue";
import SEOScoreCard from "@/Modules/Sites/Components/SEOScoreCard.vue";
import UptimeChart from "@/Modules/Metrics/Components/UptimeChart.vue";
import ResponseTimeChart from "@/Modules/Metrics/Components/ResponseTimeChart.vue";
import Breadcrumbs from "@/Shared/Components/Breadcrumbs.vue";
import { ArrowLeftIcon, PlayIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    site: {
        type: Object,
        required: true,
    },
    alerts: {
        type: Array,
        default: () => [],
    },
    checks: {
        type: Array,
        default: () => [],
    },
    tasks: {
        type: Array,
        default: () => [],
    },
    activity: {
        type: Array,
        default: () => [],
    },
    reports: {
        type: Array,
        default: () => [],
    },
    chart: {
        type: Object,
        default: () => ({ labels: [], datasets: [] }),
    },
});

const summaryCards = computed(() => {
    const responseMs = Math.round((props.site.response ?? 0) * 1000);
    return [
        {
            label: "Uptime",
            value: `${props.site.uptime?.toFixed(2)}%`,
            delta: "+0.3% vs last week",
            tone: "success",
        },
        {
            label: "Response time",
            value: `${responseMs}ms`,
            delta: "-120ms after caching",
            tone: "info",
        },
        {
            label: "SEO score",
            value: `${props.site.seoScore}/100`,
            delta:
                props.site.seoScore >= 85
                    ? "Excellent visibility"
                    : "Needs meta updates",
            tone: props.site.seoScore >= 85 ? "success" : "warning",
        },
        {
            label: "Last check",
            value: formatRelativeTime(props.site.lastChecked),
            delta: "Automated every 5 minutes",
            tone: "info",
        },
    ];
});

const platformLabel = computed(() => {
    if (props.site.platform === "wordpress") return "WordPress";
    if (props.site.platform === "shopify") return "Shopify";
    return props.site.platform ?? "Custom Build";
});

const statusBadge = computed(() => {
    return {
        "bg-emerald-500/10 text-emerald-300": props.site.status === "healthy",
        "bg-yellow-500/10 text-yellow-300": props.site.status === "warning",
        "bg-red-500/10 text-red-300": props.site.status === "critical",
    };
});

const formatRelativeTime = (timestamp) => {
    if (!timestamp) return "Unknown";
    const now = new Date();
    const past = new Date(timestamp);
    const diffMs = now.getTime() - past.getTime();
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return "Just now";
    if (diffMins < 60) return `${diffMins}m ago`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    const diffDays = Math.floor(diffHours / 24);
    return `${diffDays}d ago`;
};

const { site, alerts, tasks, activity, reports, chart } = props;
</script>
