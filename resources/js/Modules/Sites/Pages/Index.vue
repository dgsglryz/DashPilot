<template>
    <AppLayout>
        <div class="space-y-6">
            <Breadcrumbs
                :items="[
                    { label: 'Dashboard', href: route('dashboard') },
                    { label: 'Sites' },
                ]"
            />
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-bold text-white">Sites</h1>
                    <p class="text-gray-400 mt-1">
                        Monitor and manage all your WordPress and Shopify sites
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="exportSites"
                        :disabled="isExporting"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-gray-700 disabled:opacity-50"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        {{ isExporting ? "Exporting..." : "Export" }}
                    </button>
                    <button
                        @click="showAddSiteModal = true"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center gap-2"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Add Site
                    </button>
                </div>
            </div>

            <!-- Filters & Search -->
            <div
                class="bg-gray-800/50 backdrop-blur-sm rounded-xl p-4 border border-gray-700/50"
            >
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1 relative">
                        <MagnifyingGlassIcon
                            class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                        />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search sites..."
                            class="w-full pl-10 pr-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                            @keyup.enter="refreshSite"
                            @focus="onSearchFocus"
                            @blur="onSearchBlur"
                        />
                        <div
                            v-if="
                                suggestionOpen && suggestionResults.length > 0
                            "
                            class="absolute left-0 right-0 top-full z-10 mt-2 rounded-xl border border-gray-700/80 bg-gray-900/95 shadow-2xl"
                        >
                            <button
                                v-for="suggestion in suggestionResults"
                                :key="suggestion.id"
                                class="flex w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm text-gray-200 hover:bg-gray-800"
                                @mousedown.prevent="goToSite(suggestion.id)"
                            >
                                <div>
                                    <p class="font-medium text-white">
                                        {{ suggestion.name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ suggestion.url }}
                                    </p>
                                </div>
                                <span class="text-xs text-gray-500"
                                    >Enter ↵</span
                                >
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <select
                            v-model="filterPlatform"
                            class="px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                        >
                            <option value="all">All Platforms</option>
                            <option value="wordpress">WordPress</option>
                            <option value="shopify">Shopify</option>
                            <option value="custom">Custom</option>
                        </select>

                        <select
                            v-model="filterStatus"
                            class="px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                        >
                            <option value="all">All Status</option>
                            <option value="healthy">Healthy</option>
                            <option value="warning">Warning</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    class="bg-gradient-to-br from-green-500/10 to-green-600/5 border border-green-500/20 rounded-xl p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Healthy Sites</p>
                            <p class="text-2xl font-bold text-white mt-1">
                                {{ stats.healthy }}
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center"
                        >
                            <CheckCircleIcon class="w-6 h-6 text-green-400" />
                        </div>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-br from-yellow-500/10 to-yellow-600/5 border border-yellow-500/20 rounded-xl p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Warnings</p>
                            <p class="text-2xl font-bold text-white mt-1">
                                {{ stats.warning }}
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 bg-yellow-500/20 rounded-full flex items-center justify-center"
                        >
                            <ExclamationTriangleIcon
                                class="w-6 h-6 text-yellow-400"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-br from-red-500/10 to-red-600/5 border border-red-500/20 rounded-xl p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Critical</p>
                            <p class="text-2xl font-bold text-white mt-1">
                                {{ stats.critical }}
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 bg-red-500/20 rounded-full flex items-center justify-center"
                        >
                            <XCircleIcon class="w-6 h-6 text-red-400" />
                        </div>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-br from-blue-500/10 to-blue-600/5 border border-blue-500/20 rounded-xl p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Total Sites</p>
                            <p class="text-2xl font-bold text-white mt-1">
                                {{ stats.total }}
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 bg-blue-500/20 rounded-full flex items-center justify-center"
                        >
                            <GlobeAltIcon class="w-6 h-6 text-blue-400" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sites Table -->
            <div
                class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 overflow-hidden"
            >
                <!-- Batch Actions Bar -->
                <div
                    v-if="selectedSites.length > 0"
                    class="flex items-center justify-between border-b border-gray-700 bg-gray-900/50 px-6 py-3"
                >
                    <span class="text-sm text-gray-300">
                        {{ selectedSites.length }} site{{
                            selectedSites.length > 1 ? "s" : ""
                        }}
                        selected
                    </span>
                    <div class="flex items-center gap-2">
                        <button
                            @click="runBulkHealthCheck"
                            class="rounded-lg border border-gray-700 bg-gray-800 px-3 py-1.5 text-sm text-white transition-colors hover:bg-gray-700"
                        >
                            Run Health Check
                        </button>
                        <button
                            @click="exportSelected"
                            class="rounded-lg border border-gray-700 bg-gray-800 px-3 py-1.5 text-sm text-white transition-colors hover:bg-gray-700"
                        >
                            Export Selected
                        </button>
                        <button
                            @click="clearSelection"
                            class="rounded-lg px-3 py-1.5 text-sm text-gray-400 transition-colors hover:text-white"
                        >
                            Clear
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-900/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="allSelected"
                                        @change="toggleSelectAll"
                                        class="rounded border-gray-600 bg-gray-800 text-blue-600 focus:ring-blue-500"
                                    />
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"
                                >
                                    Site
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"
                                >
                                    Platform
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"
                                >
                                    Uptime
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"
                                >
                                    Response Time
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider"
                                >
                                    Last Checked
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700/50">
                            <tr
                                v-for="site in filteredSites"
                                :key="site.id"
                                class="hover:bg-gray-700/20 transition-colors"
                                :class="{
                                    'bg-blue-500/10': selectedSites.includes(
                                        site.id,
                                    ),
                                }"
                            >
                                <td class="px-6 py-4" @click.stop>
                                    <input
                                        type="checkbox"
                                        :checked="
                                            selectedSites.includes(site.id)
                                        "
                                        @change="toggleSiteSelection(site.id)"
                                        class="rounded border-gray-600 bg-gray-800 text-blue-600 focus:ring-blue-500"
                                    />
                                </td>
                                <td
                                    class="px-6 py-4 cursor-pointer"
                                    @click="goToSite(site.id)"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <img
                                                :src="site.thumbnail"
                                                :alt="site.name"
                                                class="h-12 w-12 rounded-xl object-cover"
                                            />
                                            <img
                                                :src="site.logo"
                                                :alt="`${site.name} logo`"
                                                class="absolute -bottom-1 -right-1 h-6 w-6 rounded-full border border-gray-900 bg-gray-900 object-cover"
                                            />
                                        </div>
                                        <div class="flex-1">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <p
                                                    class="font-medium text-white"
                                                >
                                                    {{ site.name }}
                                                </p>
                                                <button
                                                    @click.stop="
                                                        toggleFavorite(site.id)
                                                    "
                                                    class="rounded p-0.5 transition-colors"
                                                    :class="
                                                        site.is_favorited
                                                            ? 'text-yellow-400 hover:text-yellow-300'
                                                            : 'text-gray-500 hover:text-yellow-400'
                                                    "
                                                >
                                                    <StarIcon
                                                        class="h-4 w-4"
                                                        :class="{
                                                            'fill-current':
                                                                site.is_favorited,
                                                        }"
                                                    />
                                                </button>
                                            </div>
                                            <p class="text-sm text-gray-400">
                                                {{ site.url }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                        :class="
                                            site.platform === 'wordpress'
                                                ? 'bg-blue-500/10 text-blue-400'
                                                : 'bg-green-500/10 text-green-400'
                                        "
                                    >
                                        <component
                                            :is="
                                                site.platform === 'wordpress'
                                                    ? 'svg'
                                                    : 'svg'
                                            "
                                            class="w-4 h-4"
                                            viewBox="0 0 24 24"
                                            fill="currentColor"
                                        >
                                            <path
                                                v-if="
                                                    site.platform ===
                                                    'wordpress'
                                                "
                                                d="M21.469 6.825c.84 1.537 1.318 3.3 1.318 5.175 0 3.979-2.156 7.456-5.363 9.325l3.295-9.527c.615-1.54.82-2.771.82-3.864 0-.405-.026-.78-.07-1.11m-7.981.105c.647-.03 1.232-.105 1.232-.105.582-.075.514-.93-.067-.899 0 0-1.755.135-2.88.135-1.064 0-2.85-.15-2.85-.15-.585-.03-.661.855-.075.885 0 0 .54.061 1.125.09l1.68 4.605-2.37 7.08L5.354 6.9c.649-.03 1.234-.1 1.234-.1.585-.075.516-.93-.065-.896 0 0-1.746.138-2.874.138-.2 0-.438-.008-.69-.015C5.46 3.15 8.515 1.5 11.985 1.5c2.666 0 5.089 1.01 6.934 2.67-.043-.003-.088-.011-.132-.011-1.064 0-1.818.93-1.818 1.93 0 .895.519 1.65 1.068 2.55.415.72.901 1.65.901 2.985 0 .93-.357 2.015-.82 3.517l-1.073 3.585-3.883-11.55zM12 22.5c-1.146 0-2.25-.186-3.288-.525l3.495-10.163 3.579 9.81c.024.06.051.12.081.18-1.18.43-2.448.67-3.867.67M1.5 12C1.5 5.649 6.649.5 13 .5S24.5 5.649 24.5 12 19.351 23.5 13 23.5 1.5 18.351 1.5 12"
                                            />
                                            <path
                                                v-else
                                                d="M13.5 2.5c-5.621 0-10.211 4.443-10.475 10h3.825c.214-3.545 2.985-6.316 6.529-6.529V2.146c-3.964.214-7.18 3.119-7.925 6.854H1.5c.685-4.905 4.955-8.5 10-8.5V0C5.589 0 1 4.589 1 10.25c0 5.661 4.589 10.25 10.25 10.25S21.5 15.911 21.5 10.25H13.5v2.5h5.689c-.631 2.647-2.932 4.621-5.689 4.621-3.249 0-5.875-2.626-5.875-5.875H13.5V2.5z"
                                            />
                                        </component>
                                        {{
                                            site.platform === "wordpress"
                                                ? "WordPress"
                                                : "Shopify"
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-green-500/10 text-green-400':
                                                site.status === 'healthy',
                                            'bg-yellow-500/10 text-yellow-400':
                                                site.status === 'warning',
                                            'bg-red-500/10 text-red-400':
                                                site.status === 'critical',
                                        }"
                                    >
                                        <span
                                            class="w-1.5 h-1.5 rounded-full"
                                            :class="{
                                                'bg-green-400':
                                                    site.status === 'healthy',
                                                'bg-yellow-400':
                                                    site.status === 'warning',
                                                'bg-red-400':
                                                    site.status === 'critical',
                                            }"
                                        ></span>
                                        {{
                                            site.status
                                                .charAt(0)
                                                .toUpperCase() +
                                            site.status.slice(1)
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-white font-medium"
                                        >{{ site.uptime }}%</span
                                    >
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-white"
                                        >{{ site.responseTime }}ms</span
                                    >
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-400 text-sm">{{
                                        formatRelativeTime(site.lastChecked)
                                    }}</span>
                                </td>
                                <td class="px-6 py-4" @click.stop>
                                    <QuickActionsDropdown
                                        :site-id="site.id"
                                        :site-url="site.url"
                                        :is-favorited="site.is_favorited"
                                        @favorite-toggled="refreshSite"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Shared/Layouts/AppLayout.vue";
import {
    MagnifyingGlassIcon,
    PlusIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    GlobeAltIcon,
    ArrowDownTrayIcon,
    StarIcon,
} from "@heroicons/vue/24/outline";
import QuickActionsDropdown from "@/Shared/Components/QuickActionsDropdown.vue";
import Breadcrumbs from "@/Shared/Components/Breadcrumbs.vue";
import { useToast } from "@/Shared/Composables/useToast";

/**
 * Component props from Inertia
 * @property {Array} sites - List of monitored sites
 * @property {Object} stats - Site statistics
 */
const props = defineProps({
    sites: {
        type: Array,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({
            query: "",
            platform: "all",
            status: "all",
        }),
    },
});

/**
 * Local reactive state
 */
const searchQuery = ref(props.filters?.query ?? "");
const filterPlatform = ref(props.filters?.platform ?? "all");
const filterStatus = ref(props.filters?.status ?? "all");
const showAddSiteModal = ref(false);
const suggestionOpen = ref(false);
const selectedSites = ref<number[]>([]);
const isExporting = ref(false);
const toast = useToast();

/**
 * Computed filtered sites based on search and filters
 * @returns {Array} Filtered site list
 */
const filteredSites = computed(() => {
    return props.sites.filter((site) => {
        const matchesSearch =
            site.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            site.url.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesPlatform =
            filterPlatform.value === "all" ||
            site.platform === filterPlatform.value;
        const matchesStatus =
            filterStatus.value === "all" || site.status === filterStatus.value;

        return matchesSearch && matchesPlatform && matchesStatus;
    });
});

/**
 * Format relative time from timestamp
 * @param {string} timestamp - ISO timestamp
 * @returns {string} Formatted relative time
 */
const formatRelativeTime = (timestamp) => {
    const now = new Date();
    const past = new Date(timestamp);
    const diffMs = now - past;
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return "Just now";
    if (diffMins < 60) return `${diffMins}m ago`;

    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;

    const diffDays = Math.floor(diffHours / 24);
    return `${diffDays}d ago`;
};

/**
 * All sites selected
 */
const allSelected = computed(() => {
    return (
        filteredSites.value.length > 0 &&
        selectedSites.value.length === filteredSites.value.length
    );
});

/**
 * Toggle select all sites
 */
const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedSites.value = [];
    } else {
        selectedSites.value = filteredSites.value.map(
            (s: { id: number }) => s.id,
        );
    }
};

/**
 * Toggle site selection
 */
const toggleSiteSelection = (siteId: number) => {
    const index = selectedSites.value.indexOf(siteId);
    if (index > -1) {
        selectedSites.value.splice(index, 1);
    } else {
        selectedSites.value.push(siteId);
    }
};

/**
 * Clear selection
 */
const clearSelection = () => {
    selectedSites.value = [];
};

/**
 * Run bulk health check
 */
const runBulkHealthCheck = async () => {
    if (selectedSites.value.length === 0) return;

    try {
        for (const siteId of selectedSites.value) {
            await router.post(route("sites.health-check", siteId));
        }
        toast.success(
            `Health check initiated for ${selectedSites.value.length} site(s)`,
        );
        clearSelection();
    } catch {
        toast.error("Failed to run health checks");
    }
};

/**
 * Export selected sites
 */
const exportSelected = () => {
    if (selectedSites.value.length === 0) return;

    const params = new URLSearchParams();
    selectedSites.value.forEach((id) => params.append("ids[]", id.toString()));
    window.location.href = route("sites.export") + "?" + params.toString();
    clearSelection();
};

/**
 * Export all sites
 */
const exportSites = () => {
    isExporting.value = true;
    window.location.href = route("sites.export");
    setTimeout(() => {
        isExporting.value = false;
    }, 2000);
};

/**
 * Toggle favorite status
 */
const toggleFavorite = async (siteId: number) => {
    try {
        await router.post(
            route("sites.toggle-favorite", siteId),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    // Update local state
                    const site = props.sites.find(
                        (s: { id: number }) => s.id === siteId,
                    );
                    if (site) {
                        site.is_favorited = !site.is_favorited;
                    }
                },
            },
        );
    } catch {
        toast.error("Failed to update favorite status");
    }
};

/**
 * Refresh single site data
 * @param {number} siteId - Site ID to refresh
 */
const refreshSite = () => {
    router.get(
        route("sites.index"),
        {
            query: searchQuery.value || undefined,
            platform:
                filterPlatform.value === "all"
                    ? undefined
                    : filterPlatform.value,
            status:
                filterStatus.value === "all" ? undefined : filterStatus.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const suggestionResults = computed(() => {
    if (!searchQuery.value) {
        return [];
    }

    const needle = searchQuery.value.toLowerCase();

    return props.sites
        .filter(
            (site) =>
                site.name.toLowerCase().includes(needle) ||
                site.url.toLowerCase().includes(needle),
        )
        .slice(0, 6);
});

const goToSite = (id) => {
    suggestionOpen.value = false;
    router.visit(route("sites.show", id));
};

const onSearchFocus = () => {
    suggestionOpen.value = true;
};

const onSearchBlur = () => {
    window.setTimeout(() => {
        suggestionOpen.value = false;
    }, 120);
};

watch([filterPlatform, filterStatus], () => {
    refreshSite();
});
</script>
