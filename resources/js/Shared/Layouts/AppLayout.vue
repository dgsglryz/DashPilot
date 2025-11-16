<script setup lang="ts">
/**
 * AppLayout provides the dark dashboard shell with sidebar navigation,
 * global search, and quick actions. Every operations page is rendered
 * inside this layout to keep the UI consistent with the v0.dev design.
 */
import { ref, onMounted, onUnmounted } from "vue";
import { Link, router } from "@inertiajs/vue3";
import {
    ArrowRightOnRectangleIcon,
    BellIcon,
    ChartBarIcon,
    ChatBubbleLeftIcon,
    Cog6ToothIcon,
    DocumentChartBarIcon,
    DocumentTextIcon,
    EnvelopeIcon,
    HomeIcon,
    MagnifyingGlassIcon,
    MoonIcon,
    SunIcon,
    UsersIcon,
    UserGroupIcon,
} from "@heroicons/vue/24/outline";
import { useDarkMode } from "@/Shared/Composables/useDarkMode";
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
const { isDark, toggleDarkMode } = useDarkMode();

const toggleMobileMenu = (): void => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const closeMobileMenu = (): void => {
    isMobileMenuOpen.value = false;
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

    // Cmd+/ or Ctrl+/ - Show shortcuts modal (coming soon)
    if ((e.metaKey || e.ctrlKey) && e.key === "/") {
        e.preventDefault();
        // TODO: Show shortcuts modal
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

            <nav class="flex-1 space-y-1 px-4 py-6">
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

            <div class="space-y-1 border-t border-gray-800 px-4 py-4">
                <button
                    @click="toggleDarkMode"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-400 transition-colors hover:bg-gray-800 hover:text-white"
                >
                    <SunIcon v-if="isDark" class="h-5 w-5" />
                    <MoonIcon v-else class="h-5 w-5" />
                    <span>{{ isDark ? "Light Mode" : "Dark Mode" }}</span>
                </button>
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
                            type="text"
                            placeholder="Search sites, alerts, reports... (Cmd+K)"
                            @focus="isCommandPaletteOpen = true"
                            class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-20 text-sm text-white placeholder:text-gray-500 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <kbd
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded border border-gray-600 bg-gray-700 px-2 py-0.5 text-xs text-gray-400"
                        >
                            ⌘K
                        </kbd>
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
                    <button
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-800 hover:text-white"
                    >
                        <EnvelopeIcon class="h-6 w-6" />
                        <span class="sr-only">Email</span>
                    </button>
                    <button
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-800 hover:text-white"
                    >
                        <ChatBubbleLeftIcon class="h-6 w-6" />
                        <span class="sr-only">Messages</span>
                    </button>
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
