<script setup lang="ts">
/**
 * Client Show Page - Displays detailed client information including:
 * - Contact information
 * - All client sites
 * - Recent tasks
 * - Latest monthly report
 */
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Shared/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    EnvelopeIcon,
    PhoneIcon,
    BuildingOfficeIcon,
    UserIcon,
    PencilIcon,
    TrashIcon,
    GlobeAltIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';

type Client = {
    id: number;
    name: string;
    company: string;
    email: string;
    phone: string | null;
    status: 'active' | 'inactive';
    notes: string | null;
    assignedDeveloper: {
        id: number | null;
        name: string | null;
        email: string | null;
    };
};

type Site = {
    id: number;
    name: string;
    url: string;
    type: string;
    status: string;
    healthScore: number;
    uptime: number | null;
    thumbnail: string | null;
    logo: string | null;
};

type Task = {
    id: number;
    title: string;
    status: string;
    priority: string;
    dueDate: string | null;
    assignee: {
        id: number;
        name: string | null;
    };
};

type Report = {
    id: number;
    month: string;
    uptime: number;
    avgLoadTime: number;
    incidentsCount: number;
} | null;

const props = defineProps<{
    client: Client;
    sites: Site[];
    recentTasks: Task[];
    latestReport: Report;
    developers: Array<{
        id: number;
        name: string;
        email: string;
    }>;
}>();

/**
 * Format relative time from ISO string
 */
const formatRelativeTime = (dateString: string | null): string => {
    if (!dateString) return 'Never';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    return date.toLocaleDateString();
};

/**
 * Delete client after confirmation
 */
const deleteClient = (): void => {
    if (!confirm(`Are you sure you want to delete ${props.client.name}?`)) {
        return;
    }

    router.delete(route('clients.destroy', props.client.id), {
        onSuccess: () => {
            router.visit(route('clients.index'));
        },
    });
};

const statusBadge = computed(() => {
    return props.client.status === 'active'
        ? 'bg-green-500/10 text-green-400'
        : 'bg-gray-500/10 text-gray-400';
});
</script>

<template>
    <AppLayout>
        <section class="space-y-8">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('clients.index')"
                        class="rounded-lg border border-gray-700/60 p-2 text-gray-400 transition-colors hover:border-white/60 hover:text-white"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold text-white">
                            {{ client.name }}
                        </h1>
                        <p class="mt-1 flex items-center gap-2 text-sm text-gray-400">
                            <BuildingOfficeIcon class="h-4 w-4" />
                            {{ client.company }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold"
                        :class="statusBadge"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-current"
                        ></span>
                        {{
                            client.status.charAt(0).toUpperCase() +
                            client.status.slice(1)
                        }}
                    </span>
                    <Link
                        :href="route('clients.edit', client.id)"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-700/60 bg-gray-800/60 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-gray-700/60"
                    >
                        <PencilIcon class="h-4 w-4" />
                        Edit
                    </Link>
                    <button
                        @click="deleteClient"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-500/20 bg-red-500/10 px-4 py-2 text-sm font-semibold text-red-400 transition-colors hover:bg-red-500/20"
                    >
                        <TrashIcon class="h-4 w-4" />
                        Delete
                    </button>
                </div>
            </div>

            <!-- Contact Info Card -->
            <div
                class="rounded-2xl border border-gray-700/70 bg-gray-900/60 p-6"
            >
                <h3 class="mb-4 text-lg font-semibold text-white">
                    Contact Information
                </h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="flex items-start gap-3">
                        <EnvelopeIcon class="mt-1 h-5 w-5 text-gray-400" />
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">
                                Email
                            </p>
                            <a
                                :href="`mailto:${client.email}`"
                                class="mt-1 text-white hover:text-blue-400"
                            >
                                {{ client.email }}
                            </a>
                        </div>
                    </div>
                    <div
                        v-if="client.phone"
                        class="flex items-start gap-3"
                    >
                        <PhoneIcon class="mt-1 h-5 w-5 text-gray-400" />
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">
                                Phone
                            </p>
                            <a
                                :href="`tel:${client.phone}`"
                                class="mt-1 text-white hover:text-blue-400"
                            >
                                {{ client.phone }}
                            </a>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <UserIcon class="mt-1 h-5 w-5 text-gray-400" />
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">
                                Assigned Developer
                            </p>
                            <p
                                v-if="client.assignedDeveloper.name"
                                class="mt-1 text-white"
                            >
                                {{ client.assignedDeveloper.name }}
                            </p>
                            <p
                                v-else
                                class="mt-1 text-gray-500"
                            >
                                Unassigned
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    v-if="client.notes"
                    class="mt-6 rounded-xl border border-gray-700/60 bg-gray-800/60 p-4"
                >
                    <p class="mb-2 text-xs uppercase tracking-wide text-gray-500">
                        Notes
                    </p>
                    <p class="text-sm text-gray-300">{{ client.notes }}</p>
                </div>
            </div>

            <!-- Sites List -->
            <div
                class="rounded-2xl border border-gray-700/70 bg-gray-900/60 p-6"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white">
                        Client Sites ({{ sites.length }})
                    </h3>
                    <Link
                        :href="route('sites.index', { client: client.id })"
                        class="text-sm font-semibold text-blue-400 transition-colors hover:text-blue-300"
                    >
                        View all sites →
                    </Link>
                </div>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="site in sites"
                        :key="site.id"
                        :href="route('sites.show', site.id)"
                        class="group relative overflow-hidden rounded-xl border border-gray-700/70 bg-gray-800/40 transition hover:-translate-y-1 hover:border-blue-500/60"
                    >
                        <img
                            v-if="site.thumbnail"
                            :src="site.thumbnail"
                            :alt="site.name"
                            class="h-32 w-full object-cover opacity-60 transition duration-500 group-hover:scale-105"
                        />
                        <div
                            v-else
                            class="flex h-32 w-full items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900"
                        >
                            <GlobeAltIcon class="h-12 w-12 text-gray-600" />
                        </div>
                        <div class="relative p-4">
                            <p class="font-semibold text-white">
                                {{ site.name }}
                            </p>
                            <p class="mt-1 text-xs text-gray-400">
                                {{ site.url }}
                            </p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-semibold uppercase"
                                    :class="{
                                        'bg-green-500/15 text-green-400':
                                            site.status === 'healthy',
                                        'bg-yellow-500/15 text-yellow-400':
                                            site.status === 'warning',
                                        'bg-red-500/15 text-red-400':
                                            site.status === 'critical',
                                    }"
                                >
                                    {{ site.status }}
                                </span>
                                <span
                                    class="rounded-full bg-blue-500/10 px-2 py-0.5 text-xs text-blue-300"
                                >
                                    {{ site.type }}
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-xs text-gray-400">
                                <span>Score: {{ site.healthScore }}/100</span>
                                <span
                                    v-if="site.uptime"
                                >
                                    {{ site.uptime }}% uptime
                                </span>
                            </div>
                        </div>
                    </Link>
                    <div
                        v-if="sites.length === 0"
                        class="col-span-full rounded-xl border border-dashed border-gray-700/70 p-12 text-center text-gray-500"
                    >
                        No sites associated with this client.
                    </div>
                </div>
            </div>

            <!-- Recent Tasks & Latest Report -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent Tasks -->
                <div
                    class="rounded-2xl border border-gray-700/70 bg-gray-900/60 p-6"
                >
                    <h3 class="mb-4 text-lg font-semibold text-white">
                        Recent Tasks
                    </h3>
                    <div class="space-y-3">
                        <div
                            v-for="task in recentTasks"
                            :key="task.id"
                            class="rounded-xl border border-gray-700/60 bg-gray-800/60 p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <p class="font-semibold text-white">
                                        {{ task.title }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs uppercase tracking-wide text-gray-500"
                                    >
                                        {{ task.status }} • {{ task.priority }}
                                    </p>
                                    <p
                                        v-if="task.dueDate"
                                        class="mt-2 text-xs text-gray-400"
                                    >
                                        Due: {{ formatRelativeTime(task.dueDate) }}
                                    </p>
                                    <p
                                        v-if="task.assignee.name"
                                        class="mt-2 text-xs text-gray-400"
                                    >
                                        Assigned to: {{ task.assignee.name }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="recentTasks.length === 0"
                            class="rounded-xl border border-dashed border-gray-700/70 p-8 text-center text-gray-500"
                        >
                            No tasks for this client.
                        </div>
                    </div>
                </div>

                <!-- Latest Report -->
                <div
                    class="rounded-2xl border border-gray-700/70 bg-gray-900/60 p-6"
                >
                    <h3 class="mb-4 text-lg font-semibold text-white">
                        Latest Monthly Report
                    </h3>
                    <div
                        v-if="latestReport"
                        class="space-y-4 rounded-xl border border-gray-700/60 bg-gray-800/60 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-sm uppercase tracking-wide text-gray-400">
                                {{ latestReport.month }}
                            </p>
                            <CheckCircleIcon class="h-5 w-5 text-green-400" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-2xl font-bold text-white">
                                    {{ latestReport.uptime }}%
                                </p>
                                <p class="text-xs text-gray-400">Uptime</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-white">
                                    {{ latestReport.avgLoadTime }}s
                                </p>
                                <p class="text-xs text-gray-400">
                                    Avg Load Time
                                </p>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-gray-700/60">
                            <p class="text-sm text-gray-400">
                                Incidents:
                                <span class="font-semibold text-white">
                                    {{ latestReport.incidentsCount }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div
                        v-else
                        class="rounded-xl border border-dashed border-gray-700/70 p-8 text-center text-gray-500"
                    >
                        No reports generated yet.
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>

