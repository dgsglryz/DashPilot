<script setup lang="ts">
/**
 * Client Reports Page - Displays all reports for a specific client
 * Shows report history with download links and metrics
 */
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Shared/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    ArrowDownTrayIcon,
    DocumentTextIcon,
    CalendarIcon,
    ChartBarIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';

type Client = {
    id: number;
    name: string;
    company: string;
};

type Report = {
    id: number;
    siteName: string;
    siteUrl: string | null;
    month: string;
    uptime: number;
    avgLoadTime: number;
    totalBackups: number;
    securityScans: number;
    incidentsCount: number;
    generatedAt: string | null;
    downloadUrl: string | null;
};

defineProps<{
    client: Client;
    reports: Report[];
}>();

/**
 * Format date from ISO string
 */
const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <AppLayout>
        <section class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('clients.show', $props.client.id)"
                        class="rounded-lg border border-gray-700/60 p-2 text-gray-400 transition-colors hover:border-white/60 hover:text-white"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold text-white">
                            Reports - {{ $props.client.name }}
                        </h1>
                        <p class="mt-1 flex items-center gap-2 text-sm text-gray-400">
                            <CalendarIcon class="h-4 w-4" />
                            {{ $props.client.company }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Reports List -->
            <div
                v-if="$props.reports.length > 0"
                class="rounded-2xl border border-gray-700/70 bg-gray-900/60 overflow-hidden"
            >
                <div class="p-6 border-b border-gray-700/70">
                    <h2 class="text-lg font-semibold text-white">
                        Report History ({{ $props.reports.length }})
                    </h2>
                </div>

                <div class="divide-y divide-gray-700/50">
                    <div
                        v-for="report in $props.reports"
                        :key="report.id"
                        class="p-6 hover:bg-gray-800/40 transition-colors"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <DocumentTextIcon class="h-5 w-5 text-blue-400" />
                                    <div>
                                        <h3 class="text-lg font-semibold text-white">
                                            {{ report.month }} Report
                                        </h3>
                                        <p class="text-sm text-gray-400">
                                            {{ report.siteName }}
                                            <span v-if="report.siteUrl" class="text-gray-500">
                                                • {{ report.siteUrl }}
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mt-4">
                                    <div class="bg-gray-800/60 rounded-lg p-3 border border-gray-700/50">
                                        <div class="flex items-center gap-2 mb-1">
                                            <ChartBarIcon class="h-4 w-4 text-green-400" />
                                            <p class="text-xs text-gray-400 uppercase">Uptime</p>
                                        </div>
                                        <p class="text-xl font-bold text-white">
                                            {{ report.uptime }}%
                                        </p>
                                    </div>

                                    <div class="bg-gray-800/60 rounded-lg p-3 border border-gray-700/50">
                                        <div class="flex items-center gap-2 mb-1">
                                            <ChartBarIcon class="h-4 w-4 text-blue-400" />
                                            <p class="text-xs text-gray-400 uppercase">Load Time</p>
                                        </div>
                                        <p class="text-xl font-bold text-white">
                                            {{ report.avgLoadTime }}s
                                        </p>
                                    </div>

                                    <div class="bg-gray-800/60 rounded-lg p-3 border border-gray-700/50">
                                        <div class="flex items-center gap-2 mb-1">
                                            <ShieldCheckIcon class="h-4 w-4 text-purple-400" />
                                            <p class="text-xs text-gray-400 uppercase">Backups</p>
                                        </div>
                                        <p class="text-xl font-bold text-white">
                                            {{ report.totalBackups }}
                                        </p>
                                    </div>

                                    <div class="bg-gray-800/60 rounded-lg p-3 border border-gray-700/50">
                                        <div class="flex items-center gap-2 mb-1">
                                            <ShieldCheckIcon class="h-4 w-4 text-yellow-400" />
                                            <p class="text-xs text-gray-400 uppercase">Scans</p>
                                        </div>
                                        <p class="text-xl font-bold text-white">
                                            {{ report.securityScans }}
                                        </p>
                                    </div>

                                    <div class="bg-gray-800/60 rounded-lg p-3 border border-gray-700/50">
                                        <div class="flex items-center gap-2 mb-1">
                                            <ShieldCheckIcon class="h-4 w-4 text-red-400" />
                                            <p class="text-xs text-gray-400 uppercase">Incidents</p>
                                        </div>
                                        <p class="text-xl font-bold text-white">
                                            {{ report.incidentsCount }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 text-xs text-gray-500">
                                    Generated: {{ formatDate(report.generatedAt) }}
                                </div>
                            </div>

                            <div class="flex-shrink-0">
                                <a
                                    v-if="report.downloadUrl"
                                    :href="report.downloadUrl"
                                    class="inline-flex items-center gap-2 rounded-lg border border-blue-500/30 bg-blue-500/10 px-4 py-2 text-sm font-semibold text-blue-400 transition-colors hover:bg-blue-500/20"
                                >
                                    <ArrowDownTrayIcon class="h-4 w-4" />
                                    Download PDF
                                </a>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-700/50 bg-gray-800/50 px-4 py-2 text-sm font-semibold text-gray-500"
                                >
                                    No PDF available
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="rounded-2xl border border-dashed border-gray-700/70 bg-gray-900/60 p-12 text-center"
            >
                <DocumentTextIcon class="h-16 w-16 text-gray-600 mx-auto mb-4" />
                <h3 class="text-lg font-semibold text-white mb-2">
                    No Reports Generated
                </h3>
                <p class="text-gray-400 mb-6">
                    No reports have been generated for this client yet.
                </p>
                <Link
                    :href="route('reports.index')"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700"
                >
                    Generate Report
                </Link>
            </div>
        </section>
    </AppLayout>
</template>

