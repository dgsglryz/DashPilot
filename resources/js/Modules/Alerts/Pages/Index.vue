<template>
    <AppLayout>
        <div class="space-y-6">
            <Breadcrumbs
                :items="[
                    { label: 'Dashboard', href: route('dashboard') },
                    { label: 'Alerts' },
                ]"
            />
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-bold text-white">Alerts</h1>
                    <p class="text-gray-400 mt-1">
                        Monitor and manage system alerts and notifications
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="exportAlerts"
                        :disabled="isExporting"
                        data-testid="export-alerts-button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-gray-700 disabled:opacity-50"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        {{ isExporting ? "Exporting..." : "Export" }}
                    </button>
                    <button
                        @click="markAllAsRead"
                        data-testid="mark-all-read-button"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors"
                    >
                        Mark All as Read
                    </button>
                </div>
            </div>

            <!-- Alert Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                            <ExclamationCircleIcon
                                class="w-6 h-6 text-red-400"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-br from-yellow-500/10 to-yellow-600/5 border border-yellow-500/20 rounded-xl p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Warning</p>
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
                    class="bg-gradient-to-br from-blue-500/10 to-blue-600/5 border border-blue-500/20 rounded-xl p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Info</p>
                            <p class="text-2xl font-bold text-white mt-1">
                                {{ stats.info }}
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 bg-blue-500/20 rounded-full flex items-center justify-center"
                        >
                            <InformationCircleIcon
                                class="w-6 h-6 text-blue-400"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-br from-green-500/10 to-green-600/5 border border-green-500/20 rounded-xl p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Resolved</p>
                            <p class="text-2xl font-bold text-white mt-1">
                                {{ stats.resolved }}
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center"
                        >
                            <CheckCircleIcon class="w-6 h-6 text-green-400" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
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
                            placeholder="Search alerts..."
                            data-testid="alerts-search-input"
                            autocomplete="off"
                            class="w-full pl-10 pr-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                            @focus="onSearchFocus"
                            @blur="onSearchBlur"
                            @keyup.enter="handleSearchEnter"
                            @keydown.enter.prevent
                        />
                        <!-- Search Suggestions Dropdown -->
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
                                @mousedown.prevent="
                                    selectSuggestion(suggestion)
                                "
                            >
                                <div class="flex-1">
                                    <p class="font-medium text-white">
                                        {{ suggestion.title }}
                                    </p>
                                    <p
                                        class="text-xs text-gray-500 line-clamp-1"
                                    >
                                        {{ suggestion.message }}
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
                            v-model="filterSeverity"
                            class="px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                        >
                            <option value="all">All Severities</option>
                            <option value="critical">Critical</option>
                            <option value="warning">Warning</option>
                            <option value="info">Info</option>
                        </select>

                        <select
                            v-model="filterStatus"
                            class="px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                        >
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="resolved">Resolved</option>
                            <option value="acknowledged">Acknowledged</option>
                        </select>

                        <select
                            v-model="filterType"
                            class="px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                        >
                            <option value="all">All Types</option>
                            <option value="downtime">Downtime</option>
                            <option value="performance">Performance</option>
                            <option value="security">Security</option>
                            <option value="seo">SEO</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Alerts List -->
            <div class="space-y-3" data-testid="alerts-list">
                <AlertCard
                    v-for="alert in filteredAlerts"
                    :key="alert.id"
                    :alert="alert"
                    data-testid="alert-card"
                    @acknowledge="acknowledgeAlert"
                    @resolve="resolveAlert"
                    @view-site="viewSite"
                    @view-details="openAlertDetails"
                />

                <div
                    v-if="filteredAlerts.length === 0"
                    class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-12 text-center"
                >
                    <BellSlashIcon
                        class="w-16 h-16 text-gray-600 mx-auto mb-4"
                    />
                    <p class="text-gray-400 text-lg">No alerts found</p>
                    <p class="text-gray-500 text-sm mt-1">
                        All systems are running smoothly
                    </p>
                </div>
            </div>

            <!-- Pagination -->
            <Pagination
                v-if="alerts.links && alerts.links.length > 3"
                :links="alerts.links"
                :from="alerts.from"
                :to="alerts.to"
                :total="alerts.total"
            />

            <AlertDetailsModal
                :is-open="showDetailsModal"
                :alert="selectedAlert"
                @close="closeAlertDetails"
                @view-site="viewSite"
                @acknowledge="acknowledgeAlert"
                @resolve="resolveAlert"
            />
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useDebounceFn } from "@vueuse/core";
import AppLayout from "@/Shared/Layouts/AppLayout.vue";
import AlertCard from "@/Modules/Alerts/Components/AlertCard.vue";
import AlertDetailsModal from "@/Modules/Alerts/Components/AlertDetailsModal.vue";
import Breadcrumbs from "@/Shared/Components/Breadcrumbs.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import {
    ArrowDownTrayIcon,
    MagnifyingGlassIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    CheckCircleIcon,
    BellSlashIcon,
} from "@heroicons/vue/24/outline";

/**
 * Component props from Inertia
 * @property {Array} alerts - List of system alerts
 * @property {Object} stats - Alert statistics
 * @property {Object} filters - Current filter values
 */
const props = defineProps({
    alerts: {
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
            search: "",
        }),
    },
});

/**
 * Local reactive state
 */
const searchQuery = ref(props.filters?.search ?? "");
const filterSeverity = ref("all");
const filterStatus = ref("all");
const filterType = ref("all");
const showDetailsModal = ref(false);
const selectedAlert = ref(null);
const suggestionOpen = ref(false);

/**
 * Refresh alerts with current filters
 */
const refreshAlerts = () => {
    router.get(
        route("alerts.index"),
        {
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

/**
 * Debounced search function
 */
const debouncedSearch = useDebounceFn(() => {
    refreshAlerts();
}, 500);

/**
 * Watch search query for suggestions (optional backend sync)
 * Instant filtering is handled by filteredAlerts computed property
 */
watch(searchQuery, () => {
    // Optional: Sync with backend (debounced) for pagination consistency
    // But frontend filtering works instantly
    debouncedSearch();
    // Show suggestions if there are results
    if (
        suggestionResults.value.length > 0 &&
        searchQuery.value.trim().length >= 1
    ) {
        suggestionOpen.value = true;
    } else {
        suggestionOpen.value = false;
    }
});

/**
 * Computed suggestion results for search autocomplete
 * Uses immediate search query (not debounced) for instant feedback
 * @returns {Array} Filtered suggestions (max 6)
 */
const suggestionResults = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();
    if (!query) {
        return [];
    }

    const alertsList = props.alerts.data || props.alerts;

    // Limit to first 50 alerts for performance
    return alertsList
        .slice(0, 50)
        .filter((alert) => {
            const title = (alert.title || "").toLowerCase();
            const message = (alert.message || "").toLowerCase();
            const siteName = (alert.siteName || "").toLowerCase();

            return (
                title.includes(query) ||
                message.includes(query) ||
                siteName.includes(query)
            );
        })
        .slice(0, 6);
});

/**
 * Computed filtered alerts (client-side filtering with instant search)
 * Search is handled client-side for instant feedback, just like Sites page
 * @returns {Array} Filtered alert list
 */
const filteredAlerts = computed(() => {
    // If alerts is paginated, use alerts.data, otherwise use alerts directly
    const alertsList = props.alerts.data || props.alerts;
    const query = searchQuery.value.toLowerCase().trim();

    return alertsList.filter((alert) => {
        // Search filter (instant, client-side)
        const matchesSearch =
            !query ||
            (alert.title || "").toLowerCase().includes(query) ||
            (alert.message || "").toLowerCase().includes(query) ||
            (alert.siteName || "").toLowerCase().includes(query);

        // Other filters
        const matchesSeverity =
            filterSeverity.value === "all" ||
            alert.severity === filterSeverity.value;
        const matchesStatus =
            filterStatus.value === "all" || alert.status === filterStatus.value;
        const matchesType =
            filterType.value === "all" || alert.type === filterType.value;

        return matchesSearch && matchesSeverity && matchesStatus && matchesType;
    });
});

/**
 * Mark all alerts as read
 */
const markAllAsRead = () => {
    router.post("/alerts/mark-all-read");
};

/**
 * Acknowledge an alert
 * @param {number} alertId - Alert ID
 */
const acknowledgeAlert = (alertId) => {
    router.post(`/alerts/${alertId}/acknowledge`);
};

/**
 * Resolve an alert
 * @param {number} alertId - Alert ID
 */
const resolveAlert = (alertId) => {
    router.post(`/alerts/${alertId}/resolve`);
};

/**
 * Navigate to site details
 * @param {number} siteId - Site ID
 */
const viewSite = (siteId) => {
    router.visit(`/sites/${siteId}`);
};

const openAlertDetails = (alert) => {
    selectedAlert.value = alert;
    showDetailsModal.value = true;
};

const closeAlertDetails = () => {
    showDetailsModal.value = false;
    selectedAlert.value = null;
};

/**
 * Export state
 */
const isExporting = ref(false);

/**
 * Export alerts
 */
const exportAlerts = () => {
    isExporting.value = true;
    globalThis.location.href = route("alerts.export");
    setTimeout(() => {
        isExporting.value = false;
    }, 2000);
};

/**
 * Handle search input focus
 */
const onSearchFocus = () => {
    suggestionOpen.value = true;
};

/**
 * Handle search input blur
 */
const onSearchBlur = () => {
    globalThis.setTimeout(() => {
        suggestionOpen.value = false;
    }, 120);
};

/**
 * Handle Enter key in search input
 */
const handleSearchEnter = () => {
    if (suggestionResults.value.length > 0) {
        // If there are suggestions, select first one
        selectSuggestion(suggestionResults.value[0]);
    } else if (searchQuery.value.trim()) {
        // If no suggestions but has query, refresh immediately
        refreshAlerts();
    }
};

/**
 * Select a suggestion
 */
const selectSuggestion = (suggestion) => {
    suggestionOpen.value = false;
    searchQuery.value = suggestion.title || suggestion.message;
    refreshAlerts();
};
</script>
