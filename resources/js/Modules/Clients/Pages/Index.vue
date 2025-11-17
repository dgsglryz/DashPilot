<script setup lang="ts">
/**
 * Clients Index Page - Lists all clients with filtering and search capabilities.
 * Displays client information, assigned developers, sites count, and status.
 */
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Shared/Layouts/AppLayout.vue';
import {
    MagnifyingGlassIcon,
    PlusIcon,
    UserGroupIcon,
    BuildingOfficeIcon,
    EnvelopeIcon,
    PhoneIcon,
    PencilIcon,
    TrashIcon,
    EyeIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';

type Client = {
    id: number;
    name: string;
    company: string;
    email: string;
    phone: string | null;
    status: 'active' | 'inactive';
    sitesCount: number;
    assignedDeveloper: {
        id: number | null;
        name: string | null;
        email: string | null;
    };
};

type Developer = {
    id: number;
    name: string;
    email: string;
};

const props = withDefaults(
    defineProps<{
        clients: Client[];
        developers: Developer[];
        filters?: {
            query?: string;
            status?: string;
        };
    }>(),
    {
        filters: () => ({
            query: '',
            status: 'all',
        }),
    },
);

const searchQuery = ref(props.filters?.query || '');
const filterStatus = ref(props.filters?.status || 'all');

/**
 * Filter clients based on search query and status filter
 */
const filteredClients = computed(() => {
    let result = props.clients;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (client) =>
                client.name.toLowerCase().includes(query) ||
                client.company.toLowerCase().includes(query) ||
                client.email.toLowerCase().includes(query),
        );
    }

    if (filterStatus.value !== 'all') {
        result = result.filter((client) => client.status === filterStatus.value);
    }

    return result;
});

/**
 * Apply filters and reload the page
 */
const applyFilters = (): void => {
    router.get(
        route('clients.index'),
        {
            query: searchQuery.value || undefined,
            status: filterStatus.value === 'all' ? undefined : filterStatus.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

/**
 * Delete a client after confirmation
 */
const deleteClient = (client: Client): void => {
    if (!confirm(`Are you sure you want to delete ${client.name}?`)) {
        return;
    }

    router.delete(route('clients.destroy', client.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by Inertia flash messages
        },
    });
};

const stats = computed(() => {
    return {
        total: props.clients.length,
        active: props.clients.filter((c) => c.status === 'active').length,
        inactive: props.clients.filter((c) => c.status === 'inactive').length,
    };
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Clients</h1>
                    <p class="mt-1 text-sm text-gray-400">
                        Manage your agency clients and their portfolios
                    </p>
                </div>
                <Link
                    :href="route('clients.create')"
                    data-testid="add-client-button"
                    class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700"
                >
                    <PlusIcon class="h-5 w-5" />
                    Add Client
                </Link>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div
                    class="rounded-xl border border-gray-700/70 bg-gradient-to-br from-blue-500/10 to-blue-600/5 p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">Total Clients</p>
                            <p class="mt-1 text-2xl font-bold text-white">
                                {{ stats.total }}
                            </p>
                        </div>
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-500/20"
                        >
                            <UserGroupIcon class="h-6 w-6 text-blue-400" />
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-gray-700/70 bg-gradient-to-br from-green-500/10 to-green-600/5 p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">Active</p>
                            <p class="mt-1 text-2xl font-bold text-white">
                                {{ stats.active }}
                            </p>
                        </div>
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-green-500/20"
                        >
                            <CheckCircleIcon class="h-6 w-6 text-green-400" />
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-gray-700/70 bg-gradient-to-br from-gray-500/10 to-gray-600/5 p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">Inactive</p>
                            <p class="mt-1 text-2xl font-bold text-white">
                                {{ stats.inactive }}
                            </p>
                        </div>
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-500/20"
                        >
                            <XCircleIcon class="h-6 w-6 text-gray-400" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters & Search -->
            <div
                class="rounded-xl border border-gray-700/50 bg-gray-800/50 p-4 backdrop-blur-sm"
            >
                <div class="flex flex-col gap-4 lg:flex-row">
                    <div class="relative flex-1">
                        <MagnifyingGlassIcon
                            class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                        />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search clients by name, company, or email..."
                            data-testid="clients-search-input"
                            class="w-full rounded-lg border border-gray-700 bg-gray-900 py-2 pl-10 pr-4 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <div class="flex gap-2">
                        <select
                            v-model="filterStatus"
                            class="rounded-lg border border-gray-700 bg-gray-900 px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                            @change="applyFilters"
                        >
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Clients Table -->
            <div
                class="overflow-hidden rounded-xl border border-gray-700/50 bg-gray-800/50 backdrop-blur-sm"
                data-testid="clients-table"
            >
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-900/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400"
                                >
                                    Client
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400"
                                >
                                    Contact
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400"
                                >
                                    Sites
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400"
                                >
                                    Developer
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-400"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700/50">
                            <tr
                                v-for="client in filteredClients"
                                :key="client.id"
                                data-testid="client-row"
                                @click="router.visit(route('clients.show', client.id))"
                                class="cursor-pointer transition-colors hover:bg-gray-700/20"
                            >
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-white">
                                            {{ client.name }}
                                        </p>
                                        <p class="mt-1 flex items-center gap-1.5 text-sm text-gray-400">
                                            <BuildingOfficeIcon class="h-4 w-4" />
                                            {{ client.company }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1 text-sm">
                                        <p
                                            class="flex items-center gap-1.5 text-gray-300"
                                        >
                                            <EnvelopeIcon class="h-4 w-4 text-gray-500" />
                                            {{ client.email }}
                                        </p>
                                        <p
                                            v-if="client.phone"
                                            class="flex items-center gap-1.5 text-gray-400"
                                        >
                                            <PhoneIcon class="h-4 w-4 text-gray-500" />
                                            {{ client.phone }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full bg-blue-500/10 px-2.5 py-1 text-xs font-medium text-blue-400"
                                    >
                                        {{ client.sitesCount }}
                                        {{
                                            client.sitesCount === 1
                                                ? 'site'
                                                : 'sites'
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p
                                        v-if="client.assignedDeveloper.name"
                                        class="text-sm text-white"
                                    >
                                        {{ client.assignedDeveloper.name }}
                                    </p>
                                    <p
                                        v-else
                                        class="text-sm text-gray-500"
                                    >
                                        Unassigned
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="
                                            client.status === 'active'
                                                ? 'bg-green-500/10 text-green-400'
                                                : 'bg-gray-500/10 text-gray-400'
                                        "
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                client.status === 'active'
                                                    ? 'bg-green-400'
                                                    : 'bg-gray-400'
                                            "
                                        ></span>
                                        {{
                                            client.status.charAt(0).toUpperCase() +
                                            client.status.slice(1)
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <Link
                                            :href="
                                                route('clients.show', client.id)
                                            "
                                            @click.stop
                                            class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-700 hover:text-white"
                                        >
                                            <EyeIcon class="h-4 w-4" />
                                        </Link>
                                        <Link
                                            :href="
                                                route('clients.edit', client.id)
                                            "
                                            @click.stop
                                            class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-700 hover:text-white"
                                        >
                                            <PencilIcon class="h-4 w-4" />
                                        </Link>
                                        <button
                                            @click.stop="deleteClient(client)"
                                            class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-500/10 hover:text-red-400"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="filteredClients.length === 0"
                    class="p-12 text-center"
                >
                    <p class="text-gray-400">
                        {{
                            searchQuery || filterStatus !== 'all'
                                ? 'No clients found matching your filters.'
                                : 'No clients yet. Create your first client to get started.'
                        }}
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

