<script setup lang="ts">
/**
 * AppLayout provides the dark dashboard shell with sidebar navigation,
 * global search, and quick actions. Every operations page is rendered
 * inside this layout to keep the UI consistent with the v0.dev design.
 */
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ArrowRightOnRectangleIcon,
    BellIcon,
    ChartBarIcon,
    ChatBubbleLeftIcon,
    Cog6ToothIcon,
    DocumentChartBarIcon,
    EnvelopeIcon,
    HomeIcon,
    MagnifyingGlassIcon,
    UsersIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline';

type NavigationItem = {
    name: string;
    icon: typeof HomeIcon;
    href?: string;
    routeName?: string;
};

const navigation: NavigationItem[] = [
    {
        name: 'Overview',
        icon: HomeIcon,
        routeName: 'dashboard',
    },
    { name: 'Sites', icon: ChartBarIcon, routeName: 'sites.index' },
    { name: 'Clients', icon: UserGroupIcon, routeName: 'clients.index' },
    { name: 'Metrics', icon: ChartBarIcon, routeName: 'metrics.index' },
    { name: 'Alerts', icon: BellIcon, routeName: 'alerts.index' },
    { name: 'Team', icon: UsersIcon, routeName: 'team.index' },
    { name: 'Reports', icon: DocumentChartBarIcon, routeName: 'reports.index' },
];

const isMobileMenuOpen = ref(false);

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
                    :href="item.routeName ? route(item.routeName) : item.href ?? '#'"
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
                            placeholder="Search sites, alerts, reports..."
                            class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-4 text-sm text-white placeholder:text-gray-500 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <div class="ml-4 flex items-center gap-2 sm:gap-3">
                    <button
                        class="relative rounded-lg p-2 text-gray-400 transition hover:bg-gray-800 hover:text-white"
                    >
                        <BellIcon class="h-6 w-6" />
                        <span
                            class="absolute right-1 top-1 h-2 w-2 rounded-full bg-red-500"
                        ></span>
                        <span class="sr-only">Notifications</span>
                    </button>
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
                        <p class="text-xs text-gray-400">
                            Operations Hub
                        </p>
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
                                : item.href ?? '#'
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
    </div>
</template>

