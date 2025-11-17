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
import { useToast } from "vue-toastification";
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
type SearchResult = {
    type: string;
    id?: number;
    label: string;
    subtitle?: string;
    route: string;
    params?: Record<string, string | number>;
    icon?: string;
    badge?: string;
    preview?: string;
};

const toast = useToast();
const searchResults = ref<SearchResult[]>([]);
const isSearching = ref(false);
const searchContainer = ref<HTMLElement | null>(null);

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
                href: globalThis.location.pathname,
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
            params: { q: query, scope: "pages" },
        });
        searchResults.value = response.data.results || [];
    } catch (error) {
        toast.error("Search failed. Please try again.");
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
const searchSuggestions = computed(() => searchResults.value);
const cardSuggestions = computed(() =>
    searchSuggestions.value.filter((item) => item.type === "card"),
);
const pageSuggestions = computed(() =>
    searchSuggestions.value.filter((item) => item.type === "page"),
);
const hasSuggestions = computed(
    () => cardSuggestions.value.length > 0 || pageSuggestions.value.length > 0,
);

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
        router.visit(route("dashboard"), {
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
    params?: Record<string, string | number>;
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

const handleSearchFocus = (): void => {
    if (hasSuggestions.value) {
        showSuggestions.value = true;
    }
};

const closeSuggestions = (): void => {
    showSuggestions.value = false;
};

const handleClickOutside = (event: MouseEvent): void => {
    if (!searchContainer.value) {
        return;
    }

    if (!searchContainer.value.contains(event.target as Node)) {
        showSuggestions.value = false;
    }
};

/**
 * Get icon component by name
 */
const getIconComponent = (iconName: string): typeof GlobeAltIcon => {
    const iconMap: Record<string, typeof GlobeAltIcon> = {
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
    }
};

onMounted(() => {
    document.addEventListener("keydown", handleKeyboardShortcuts);
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("keydown", handleKeyboardShortcuts);
    document.removeEventListener("click", handleClickOutside);
});

watch(searchSuggestions, () => {
    if (hasSuggestions.value && searchQuery.value.trim().length >= 2) {
        showSuggestions.value = true;
        return;
    }

    if (!hasSuggestions.value) {
        showSuggestions.value = false;
    }
});
</script>

<template>
    <div class="flex min-h-screen bg-gray-950">
        <aside
            class="hidden w-64 flex-shrink-0 border-r border-gray-800 bg-gray-900 lg:flex lg:flex-col"
            aria-label="Main navigation sidebar"
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

            <nav class="flex-1 space-y-1 px-4 py-6 overflow-y-auto" aria-label="Primary navigation">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="
                        item.routeName
                            ? route(item.routeName)
                            : (item.href ?? '#')
                    "
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                    :aria-label="`Navigate to ${item.name}`"
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
                    aria-label="Toggle mobile menu"
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
                    <div class="relative" ref="searchContainer">
                        <MagnifyingGlassIcon
                            class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                        />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search sites, alerts, reports..."
                            @keydown.enter="handleSearch"
                            @focus="handleSearchFocus"
                            @blur="handleSearchBlur"
                            @keydown.escape.prevent="closeSuggestions"
                            class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-20 text-sm text-white placeholder:text-gray-500 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <kbd
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded border border-gray-600 bg-gray-700 px-2 py-0.5 text-xs text-gray-400"
                        >
                            ⌘K
                        </kbd>

                        <!-- Search Suggestions Dropdown -->
                        <div
                            v-if="showSuggestions && (hasSuggestions || isSearching)"
                            class="absolute top-full left-0 right-0 z-50 mt-2 max-h-[28rem] overflow-y-auto rounded-xl border border-gray-700 bg-gray-900/95 shadow-2xl backdrop-blur-md"
                        >
                            <div
                                v-if="isSearching"
                                class="px-4 py-3 text-center text-gray-400 text-sm"
                            >
                                Searching...
                            </div>
                            <template v-else>
                                <div
                                    v-if="cardSuggestions.length > 0"
                                    class="border-b border-gray-800 p-4"
                                >
                                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Key cards
                                    </p>
                                    <div class="grid gap-3 md:grid-cols-3">
                                        <button
                                            v-for="card in cardSuggestions"
                                            :key="`card-${card.label}`"
                                            class="group relative overflow-hidden rounded-xl border border-gray-700/80 bg-gray-900/60 text-left transition hover:border-blue-500/60"
                                            @mousedown.prevent.stop="selectSuggestion(card)"
                                        >
                                            <div class="relative h-24">
                                                <img
                                                    v-if="card.preview"
                                                    :src="card.preview"
                                                    :alt="card.label"
                                                    class="h-full w-full object-cover opacity-60 transition duration-300 group-hover:scale-105"
                                                />
                                                <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/60 to-transparent"></div>
                                                <div class="absolute inset-0 flex flex-col justify-end p-3">
                                                    <div class="flex items-center gap-2 text-xs font-semibold uppercase text-gray-400">
                                                        <component
                                                            :is="getIconComponent(card.icon || 'MagnifyingGlassIcon')"
                                                            class="h-4 w-4 text-blue-300"
                                                        />
                                                        Quick access
                                                    </div>
                                                    <p class="text-sm font-semibold text-white">
                                                        {{ card.label }}
                                                    </p>
                                                    <p class="text-xs text-gray-300">
                                                        {{ card.subtitle }}
                                                    </p>
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <div
                                    v-if="pageSuggestions.length > 0"
                                    class="divide-y divide-gray-800"
                                >
                                    <p class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Pages
                                    </p>
                                    <div
                                        v-for="(suggestion, index) in pageSuggestions"
                                        :key="`page-${suggestion.id || index}`"
                                        @mousedown.prevent.stop="selectSuggestion(suggestion)"
                                        class="flex cursor-pointer items-center gap-3 px-4 py-3 transition hover:bg-gray-800"
                                    >
                                        <component
                                            :is="
                                                getIconComponent(
                                                    suggestion.icon ||
                                                        'MagnifyingGlassIcon',
                                                )
                                            "
                                            class="h-5 w-5 flex-shrink-0 text-gray-400"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="truncate font-medium text-white">
                                                    {{ suggestion.label }}
                                                </span>
                                                <span
                                                    v-if="suggestion.badge"
                                                    class="rounded bg-blue-500/20 px-2 py-0.5 text-xs text-blue-300"
                                                >
                                                    {{ suggestion.badge }}
                                                </span>
                                            </div>
                                            <p
                                                v-if="suggestion.subtitle"
                                                class="mt-0.5 truncate text-xs text-gray-400"
                                            >
                                                {{ suggestion.subtitle }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="!cardSuggestions.length && !pageSuggestions.length && searchQuery.length >= 2"
                                    class="px-4 py-3 text-center text-sm text-gray-400"
                                >
                                    No results found
                                </div>
                            </template>
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
                            aria-label="Toggle notifications menu"
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

                    <!-- Settings Dropdown - Using Alpine.js for lightweight interaction -->
                    <div
                        x-data="{ open: false }"
                        x-on:click.away="open = false"
                        class="relative"
                    >
                        <button
                            x-on:click="open = !open"
                            class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-800 hover:text-white"
                            :title="$page.props.auth?.user?.name || 'Settings'"
                            aria-label="Toggle user menu"
                        >
                            <Cog6ToothIcon class="h-6 w-6" />
                            <span class="sr-only">Settings</span>
                        </button>

                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 z-50 mt-2 w-48 rounded-lg border border-gray-700 bg-gray-800 shadow-xl"
                            style="display: none"
                        >
                            <div class="py-1">
                                <Link
                                    :href="route('settings.index')"
                                    class="block px-4 py-2 text-sm text-gray-300 transition hover:bg-gray-700 hover:text-white"
                                    x-on:click="open = false"
                                >
                                    Settings
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    class="block w-full px-4 py-2 text-left text-sm text-gray-300 transition hover:bg-gray-700 hover:text-white"
                                    x-on:click="open = false"
                                >
                                    Log out
                                </Link>
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
                aria-label="Mobile navigation menu"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-lg font-bold text-white">DashPilot</p>
                        <p class="text-xs text-gray-400">Operations Hub</p>
                    </div>
                    <button
                        class="rounded-lg border border-gray-800 p-2 text-gray-400 transition hover:bg-gray-800 hover:text-white"
                        @click="closeMobileMenu"
                        aria-label="Close mobile menu"
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

                <nav class="mt-6 space-y-2" aria-label="Mobile navigation links">
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
                        :aria-label="`Navigate to ${item.name}`"
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
