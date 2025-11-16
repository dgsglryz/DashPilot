<script setup lang="ts">
/**
 * AppLayout provides the dark dashboard shell with sidebar navigation,
 * global search, and quick actions. Every operations page is rendered
 * inside this layout to keep the UI consistent with the v0.dev design.
 */
import { ref, onMounted, onUnmounted, computed, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { useDebounceFn } from "@vueuse/core";
import axios from "axios";
import {
    ArrowRightOnRectangleIcon,
    BellIcon,
    ChartBarIcon,
    Cog6ToothIcon,
    DocumentChartBarIcon,
    DocumentTextIcon,
    HomeIcon,
    MagnifyingGlassIcon,
    UsersIcon,
    UserGroupIcon,
    GlobeAltIcon,
    ClockIcon,
    CurrencyDollarIcon,
} from "@heroicons/vue/24/outline";
import CommandPalette from "@/Shared/Components/CommandPalette.vue";

type NavigationItem = {
    name: string;
    icon: typeof HomeIcon;
    href?: string;
    routeName?: string;
};

const navigation: NavigationItem[] = [
    {
        name: "Overview",
        icon: HomeIcon,
        routeName: "dashboard",
    },
    { name: "Sites", icon: ChartBarIcon, routeName: "sites.index" },
    { name: "Clients", icon: UserGroupIcon, routeName: "clients.index" },
    { name: "Tasks", icon: DocumentTextIcon, routeName: "tasks.index" },
    { name: "Metrics", icon: ChartBarIcon, routeName: "metrics.index" },
    { name: "Alerts", icon: BellIcon, routeName: "alerts.index" },
    { name: "Team", icon: UsersIcon, routeName: "team.index" },
    { name: "Reports", icon: DocumentChartBarIcon, routeName: "reports.index" },
];

const isMobileMenuOpen = ref(false);
const isCommandPaletteOpen = ref(false);
const searchQuery = ref("");
const searchResults = ref<
    Array<{
        type: string;
        id?: number;
        label: string;
        subtitle?: string;
        route: string;
        params?: Record<string, any>;
        icon?: string;
        badge?: string;
    }>
>([]);
const isSearching = ref(false);

/**
 * Recent viewed items from localStorage
 */
interface RecentItem {
    id: string;
    label: string;
    href: string;
    icon: typeof GlobeAltIcon;
    timestamp: number;
}

const recentItems = computed<RecentItem[]>(() => {
    try {
        const stored = localStorage.getItem("dashpilot_recent_items");
        if (!stored) return [];
        const items = JSON.parse(stored) as RecentItem[];
        // Sort by timestamp, most recent first, limit to 5
        return items.sort((a, b) => b.timestamp - a.timestamp).slice(0, 5);
    } catch {
        return [];
    }
});

/**
 * Track page views for recent items
 */
onMounted(() => {
    const currentRoute = route().current();
    if (currentRoute) {
        const routeName = currentRoute;
        let label = "";
        let icon = ClockIcon;

        // Map route names to labels and icons
        if (routeName === "dashboard") {
            label = "Dashboard";
            icon = HomeIcon;
        } else if (routeName === "sites.index") {
            label = "Sites";
            icon = GlobeAltIcon;
        } else if (routeName === "sites.show") {
            label = "Site Details";
            icon = GlobeAltIcon;
        } else if (routeName === "alerts.index") {
            label = "Alerts";
            icon = BellIcon;
        } else if (routeName === "settings.index") {
            label = "Settings";
            icon = Cog6ToothIcon;
        }

        if (label) {
            const item: RecentItem = {
                id: `${routeName}_${Date.now()}`,
                label,
                href: window.location.pathname,
                icon,
                timestamp: Date.now(),
            };

            try {
                const stored = localStorage.getItem("dashpilot_recent_items");
                const items: RecentItem[] = stored ? JSON.parse(stored) : [];
                // Remove duplicates for same href
                const filtered = items.filter((i) => i.href !== item.href);
                filtered.unshift(item);
                // Keep only last 10
                const limited = filtered.slice(0, 10);
                localStorage.setItem(
                    "dashpilot_recent_items",
                    JSON.stringify(limited),
                );
            } catch {
                // Ignore localStorage errors
            }
        }
    }
});

const toggleMobileMenu = (): void => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const closeMobileMenu = (): void => {
    isMobileMenuOpen.value = false;
};

/**
 * Perform backend search
 */
const performSearch = useDebounceFn(async (query: string) => {
    if (!query.trim() || query.length < 2) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    try {
        const response = await axios.get(route("search"), {
            params: { q: query },
        });
        searchResults.value = response.data.results || [];
    } catch (error) {
        console.error("Search error:", error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
}, 300);

/**
 * Watch search query and perform search
 */
watch(searchQuery, (newQuery) => {
    if (newQuery.trim().length >= 2) {
        performSearch(newQuery);
    } else {
        searchResults.value = [];
    }
});

/**
 * Search suggestions from backend results
 */
const searchSuggestions = computed(() => {
    return searchResults.value;
});

const showSuggestions = ref(false);

/**
 * Handle search input enter key
 */
const handleSearch = (): void => {
    if (!searchQuery.value.trim()) return;

    // Check if we have results
    const suggestion = searchSuggestions.value[0];
    if (suggestion) {
        if (suggestion.params && Object.keys(suggestion.params).length > 0) {
            router.visit(route(suggestion.route, suggestion.params), {
                preserveState: false,
                preserveScroll: false,
            });
        } else {
            router.visit(route(suggestion.route), {
                preserveState: false,
                preserveScroll: false,
            });
        }
    } else {
        // Default to sites page with search query
        router.visit(route("sites.index", { query: searchQuery.value }), {
            preserveState: false,
            preserveScroll: false,
        });
    }
    showSuggestions.value = false;
    searchQuery.value = "";
};

/**
 * Select a suggestion
 */
const selectSuggestion = (suggestion: {
    route: string;
    params?: Record<string, any>;
}): void => {
    if (suggestion.params && Object.keys(suggestion.params).length > 0) {
        router.visit(route(suggestion.route, suggestion.params), {
            preserveState: false,
            preserveScroll: false,
        });
    } else {
        router.visit(route(suggestion.route), {
            preserveState: false,
            preserveScroll: false,
        });
    }
    showSuggestions.value = false;
    searchQuery.value = "";
};

/**
 * Handle search input blur with delay
 */
const handleSearchBlur = (): void => {
    setTimeout(() => {
        showSuggestions.value = false;
    }, 200);
};

/**
 * Get icon component by name
 */
const getIconComponent = (iconName: string) => {
    const iconMap: Record<string, any> = {
        GlobeAltIcon: GlobeAltIcon,
        BellIcon: BellIcon,
        DocumentTextIcon: DocumentTextIcon,
        DocumentChartBarIcon: DocumentChartBarIcon,
        UserGroupIcon: UserGroupIcon,
        HomeIcon: HomeIcon,
        ClockIcon: ClockIcon,
        CurrencyDollarIcon: CurrencyDollarIcon,
        UsersIcon: UsersIcon,
        Cog6ToothIcon: Cog6ToothIcon,
        ChartBarIcon: ChartBarIcon,
        MagnifyingGlassIcon: MagnifyingGlassIcon,
    };
    return iconMap[iconName] || MagnifyingGlassIcon;
};

const isCurrent = (item: NavigationItem): boolean => {
    if (item.routeName === undefined) {
        return false;
    }

    return route().current(item.routeName);
};

/**
 * Keyboard shortcuts handler
 */
const handleKeyboardShortcuts = (e: KeyboardEvent): void => {
    // Cmd+K or Ctrl+K - Open command palette
    if ((e.metaKey || e.ctrlKey) && e.key === "k") {
        e.preventDefault();
        isCommandPaletteOpen.value = true;
        return;
    }

    // Cmd+/ or Ctrl+/ - Show shortcuts modal (future enhancement)
    if ((e.metaKey || e.ctrlKey) && e.key === "/") {
        e.preventDefault();
        // Shortcuts modal feature to be implemented in future release
        return;
    }

    // G + D - Go to Dashboard
    if (e.key === "g" && !e.metaKey && !e.ctrlKey) {
        const handler = (e2: KeyboardEvent) => {
            if (e2.key === "d" && !e2.metaKey && !e2.ctrlKey) {
                e2.preventDefault();
                router.visit(route("dashboard"));
                document.removeEventListener("keydown", handler);
            } else if (e2.key !== "g") {
                document.removeEventListener("keydown", handler);
            }
        };
        document.addEventListener("keydown", handler);
        return;
    }

    // G + S - Go to Sites
    if (e.key === "g" && !e.metaKey && !e.ctrlKey) {
        const handler = (e2: KeyboardEvent) => {
            if (e2.key === "s" && !e2.metaKey && !e2.ctrlKey) {
                e2.preventDefault();
                router.visit(route("sites.index"));
                document.removeEventListener("keydown", handler);
            } else if (e2.key !== "g") {
                document.removeEventListener("keydown", handler);
            }
        };
        document.addEventListener("keydown", handler);
        return;
    }

    // G + A - Go to Alerts
    if (e.key === "g" && !e.metaKey && !e.ctrlKey) {
        const handler = (e2: KeyboardEvent) => {
            if (e2.key === "a" && !e2.metaKey && !e2.ctrlKey) {
                e2.preventDefault();
                router.visit(route("alerts.index"));
                document.removeEventListener("keydown", handler);
            } else if (e2.key !== "g") {
                document.removeEventListener("keydown", handler);
            }
        };
        document.addEventListener("keydown", handler);
        return;
    }
};

onMounted(() => {
    document.addEventListener("keydown", handleKeyboardShortcuts);
});

onUnmounted(() => {
    document.removeEventListener("keydown", handleKeyboardShortcuts);
});
</script>

<template>
    <div class="flex min-h-screen bg-gray-950">
        <aside
            class="hidden w-64 flex-shrink-0 border-r border-gray-800 bg-gray-900 lg:flex lg:flex-col"
        >
            <div class="flex h-16 items-center border-b border-gray-800 px-6">
                <Link href="/dashboard" class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 text-sm font-bold text-white"
                    >
                        DP
                    </div>
                    <div>
                        <p class="text-lg font-bold text-white leading-tight">
                            DashPilot
                        </p>
                        <p class="text-xs text-gray-400">Operations Hub</p>
                    </div>
                </Link>
            </div>

            <nav class="flex-1 space-y-1 px-4 py-6 overflow-y-auto">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="
                        item.routeName
                            ? route(item.routeName)
                            : (item.href ?? '#')
                    "
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                    :class="
                        isCurrent(item)
                            ? 'bg-gray-800 text-white'
                            : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                    "
                >
                    <component :is="item.icon" class="h-5 w-5" />
                    <span>{{ item.name }}</span>
                </Link>
            </nav>

            <!-- Recent Viewed Items -->
            <div
                v-if="recentItems.length > 0"
                class="border-t border-gray-800 px-4 py-4"
            >
                <h3
                    class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500"
                >
                    Recent
                </h3>
                <div class="space-y-1">
                    <Link
                        v-for="item in recentItems"
                        :key="item.id"
                        :href="item.href"
                        class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs text-gray-400 transition-colors hover:bg-gray-800 hover:text-white"
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                        <span class="truncate">{{ item.label }}</span>
                    </Link>
                </div>
            </div>

            <div class="space-y-1 border-t border-gray-800 px-4 py-4">
                <Link
                    :href="route('settings.index')"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-400 transition-colors hover:bg-gray-800 hover:text-white"
                >
                    <Cog6ToothIcon class="h-5 w-5" />
                    <span>Settings</span>
                </Link>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-400 transition-colors hover:bg-gray-800 hover:text-white"
                >
                    <ArrowRightOnRectangleIcon class="h-5 w-5" />
                    <span>Log out</span>
                </Link>
            </div>
        </aside>

        <div class="flex min-h-screen flex-1 flex-col">
            <header
                class="flex h-16 items-center justify-between border-b border-gray-800 bg-gray-900 px-4 sm:px-6"
            >
                <button
                    class="rounded-lg border border-gray-800 p-2 text-gray-400 transition hover:bg-gray-800 hover:text-white lg:hidden"
                    @click="toggleMobileMenu"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="h-6 w-6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3.75 7.5h16.5M3.75 12h16.5m-16.5 4.5h16.5"
                        />
                    </svg>
                    <span class="sr-only">Toggle navigation</span>
                </button>

                <div class="hidden flex-1 lg:block">
                    <div class="relative">
                        <MagnifyingGlassIcon
                            class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                        />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search sites, alerts, reports..."
                            @keydown.enter="handleSearch"
                            @focus="showSuggestions = true"
                            @blur="handleSearchBlur"
                            class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-20 text-sm text-white placeholder:text-gray-500 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <kbd
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded border border-gray-600 bg-gray-700 px-2 py-0.5 text-xs text-gray-400"
                        >
                            ⌘K
                        </kbd>

                        <!-- Search Suggestions Dropdown -->
                        <div
                            v-if="
                                showSuggestions &&
                                (searchSuggestions.length > 0 || isSearching)
                            "
                            class="absolute top-full left-0 right-0 mt-2 rounded-lg border border-gray-700 bg-gray-800 shadow-xl z-50 max-h-96 overflow-y-auto"
                        >
                            <div
                                v-if="isSearching"
                                class="px-4 py-3 text-center text-gray-400 text-sm"
                            >
                                Searching...
                            </div>
                            <div
                                v-else
                                v-for="(suggestion, index) in searchSuggestions"
                                :key="`${suggestion.type}-${suggestion.id || index}`"
                                @mousedown="selectSuggestion(suggestion)"
                                class="px-4 py-3 hover:bg-gray-700 cursor-pointer transition-colors flex items-center gap-3 border-b border-gray-700/50 last:border-b-0"
                            >
                                <component
                                    :is="
                                        getIconComponent(
                                            suggestion.icon ||
                                                'MagnifyingGlassIcon',
                                        )
                                    "
                                    class="h-5 w-5 text-gray-400 flex-shrink-0"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-white font-medium truncate"
                                            >{{ suggestion.label }}</span
                                        >
                                        <span
                                            v-if="suggestion.badge"
                                            class="px-2 py-0.5 text-xs rounded bg-blue-500/20 text-blue-400"
                                        >
                                            {{ suggestion.badge }}
                                        </span>
                                    </div>
                                    <p
                                        v-if="suggestion.subtitle"
                                        class="text-xs text-gray-400 truncate mt-0.5"
                                    >
                                        {{ suggestion.subtitle }}
                                    </p>
                                </div>
                            </div>
                            <div
                                v-if="
                                    !isSearching &&
                                    searchSuggestions.length === 0 &&
                                    searchQuery.length >= 2
                                "
                                class="px-4 py-3 text-center text-gray-400 text-sm"
                            >
                                No results found
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ml-4 flex items-center gap-2 sm:gap-3">
                    <!-- Notification Bell Dropdown - Using Alpine.js for lightweight interaction -->
                    <!-- eslint-disable vue/valid-v-on -->
                    <div
                        x-data="{ open: false }"
                        x-on:click.away="open = false"
                        class="relative"
                    >
                        <button
                            x-on:click="open = !open"
                            class="relative rounded-lg p-2 text-gray-400 transition hover:bg-gray-800 hover:text-white"
                        >
                            <BellIcon class="h-6 w-6" />
                            <span
                                class="absolute right-1 top-1 h-2 w-2 rounded-full bg-red-500"
                            ></span>
                            <span class="sr-only">Notifications</span>
                        </button>

                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 z-50 mt-2 w-80 rounded-lg border border-gray-700 bg-gray-800 shadow-xl"
                            style="display: none"
                        >
                            <div class="p-4">
                                <h3
                                    class="mb-3 text-sm font-semibold text-white"
                                >
                                    Notifications
                                </h3>
                                <div class="space-y-2">
                                    <p class="text-sm text-gray-400">
                                        No new notifications
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto bg-gray-950 p-4 sm:p-6">
                <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-6">
                    <slot />
                </div>
            </main>
        </div>

        <div
            v-if="isMobileMenuOpen"
            class="fixed inset-0 z-50 bg-black/60 lg:hidden"
            @click="closeMobileMenu"
        >
            <aside
                class="absolute inset-y-0 left-0 w-64 bg-gray-900 p-6 shadow-xl"
                @click.stop
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-lg font-bold text-white">DashPilot</p>
                        <p class="text-xs text-gray-400">Operations Hub</p>
                    </div>
                    <button
                        class="rounded-lg border border-gray-800 p-2 text-gray-400 transition hover:bg-gray-800 hover:text-white"
                        @click="closeMobileMenu"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-5 w-5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                        <span class="sr-only">Close menu</span>
                    </button>
                </div>

                <nav class="mt-6 space-y-2">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="
                            item.routeName
                                ? route(item.routeName)
                                : (item.href ?? '#')
                        "
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800 hover:text-white"
                        @click="closeMobileMenu"
                    >
                        <component :is="item.icon" class="h-5 w-5" />
                        <span>{{ item.name }}</span>
                    </Link>
                </nav>
            </aside>
        </div>

        <!-- Command Palette -->
        <CommandPalette
            :is-open="isCommandPaletteOpen"
            @close="isCommandPaletteOpen = false"
        />
    </div>
</template>
