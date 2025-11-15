<script setup lang="ts">
import {
    BellAlertIcon,
    Cog8ToothIcon,
    EnvelopeIcon,
    HomeIcon,
    ChartBarIcon,
    UsersIcon,
    ShieldCheckIcon,
    BellIcon,
} from '@heroicons/vue/24/outline';
import { Head } from '@inertiajs/vue3';

type DashboardCard = {
    title: string;
    subtitle: string;
    description: string;
    image: string;
    tag?: string;
    stats: Array<{ label: string; value: string }>;
};

type CalendarDay = {
    day: number;
    title?: string | null;
    subtitle?: string | null;
    tag?: string | null;
};

type CalendarBlock = {
    range: string;
    days: CalendarDay[];
};

type OperationPanel = {
    sitePerformance: {
        updatedAgo: string;
        activeSites: string;
        status: string;
    };
    metrics: Array<{ label: string; value: string; subtitle: string }>;
    notifications: Array<{ title: string; description: string }>;
};

type ActivityItem = {
    action: string;
    description: string;
    time: string | null;
};

defineProps<{
    userProfile: {
        name: string;
        email: string;
        avatar: string;
    };
    cards: DashboardCard[];
    calendar: CalendarBlock;
    operations: OperationPanel;
    activityFeed: ActivityItem[];
}>();
</script>

<template>
    <Head title="Dashboard" />

    <div class="min-h-screen bg-[#111111] text-gray-100">
        <div class="flex">
            <aside class="hidden min-h-screen w-64 flex-col bg-[#d1d1d1] p-6 text-gray-900 md:flex">
                <div class="flex items-center gap-3 rounded-2xl bg-[#bebebe] p-4">
                    <img
                        :src="userProfile.avatar"
                        alt="avatar"
                        class="h-12 w-12 rounded-full object-cover"
                    />
                    <div>
                        <p class="text-lg font-semibold">
                            {{ userProfile.name }}
                        </p>
                        <p class="text-sm text-gray-700">
                            {{ userProfile.email }}
                        </p>
                    </div>
                </div>

                <nav class="mt-10 space-y-2">
                    <button
                        v-for="item in [
                            { label: 'Overview', icon: HomeIcon },
                            { label: 'Metrics', icon: ChartBarIcon },
                            { label: 'Alerts', icon: BellAlertIcon },
                            { label: 'Team', icon: UsersIcon },
                            { label: 'Reports', icon: ShieldCheckIcon },
                        ]"
                        :key="item.label"
                        class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-semibold text-gray-800 transition hover:bg-[#c6c6c6]"
                        :class="item.label === 'Overview' ? 'bg-[#c6c6c6]' : 'bg-transparent'"
                    >
                        <component
                            :is="item.icon"
                            class="h-5 w-5"
                        />
                        {{ item.label }}
                    </button>
                </nav>

                <div class="mt-auto space-y-3">
                    <button
                        class="flex w-full items-center gap-3 rounded-2xl bg-[#bebebe] px-4 py-3 text-left font-semibold text-gray-800"
                    >
                        <Cog8ToothIcon class="h-5 w-5" />
                        Settings
                    </button>
                    <button
                        class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left font-semibold text-gray-800 text-opacity-80 transition hover:bg-[#bebebe]"
                    >
                        Logout
                    </button>
                </div>
            </aside>

            <main class="flex-1 bg-[#1a1a1a] p-6 md:p-10">
                <div class="flex flex-col gap-6 lg:flex-row">
                    <section class="flex-1">
                        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm uppercase tracking-wide text-gray-400">
                                    Welcome back
                                </p>
                                <h1 class="text-3xl font-semibold">
                                    Overview
                                </h1>
                            </div>
                            <div class="flex w-full items-center gap-3 sm:w-auto">
                                <div class="flex flex-1 items-center rounded-full bg-[#2c2c2c] px-4 py-2 text-sm text-gray-300 sm:flex-none sm:w-72">
                                    <input
                                        type="text"
                                        placeholder="Search"
                                        class="w-full bg-transparent text-sm text-gray-100 placeholder:text-gray-500 focus:outline-none"
                                    />
                                </div>
                                <button
                                    class="rounded-full bg-[#2c2c2c] p-3"
                                >
                                    <BellIcon class="h-5 w-5 text-gray-200" />
                                </button>
                                <button
                                    class="rounded-full bg-[#2c2c2c] p-3"
                                >
                                    <EnvelopeIcon class="h-5 w-5 text-gray-200" />
                                </button>
                            </div>
                        </header>

                        <div class="mt-8 grid gap-6 lg:grid-cols-2">
                            <article
                                v-for="card in cards"
                                :key="card.title"
                                class="rounded-3xl bg-[#2b2b2b] p-5 text-gray-100 shadow-2xl"
                            >
                                <div class="flex gap-4">
                                    <img
                                        :src="card.image"
                                        :alt="card.title"
                                        class="h-28 w-28 rounded-2xl object-cover"
                                    />
                                    <div class="flex-1">
                                        <p class="text-xs uppercase tracking-wide text-gray-400">
                                            {{ card.subtitle }}
                                        </p>
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-xl font-semibold">
                                                {{ card.title }}
                                            </h3>
                                            <span
                                                v-if="card.tag"
                                                class="rounded-full bg-[#f6b4b8] px-3 py-0.5 text-xs font-semibold text-gray-900"
                                            >
                                                {{ card.tag }}
                                            </span>
                                        </div>
                                        <p class="mt-2 text-sm text-gray-300">
                                            {{ card.description }}
                                        </p>

                                        <dl class="mt-4 flex flex-wrap gap-4 text-sm text-gray-200">
                                            <div
                                                v-for="stat in card.stats"
                                                :key="stat.label"
                                            >
                                                <dt class="text-xs uppercase tracking-wide text-gray-400">
                                                    {{ stat.label }}
                                                </dt>
                                                <dd class="font-semibold">
                                                    {{ stat.value }}
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <section class="mt-8 rounded-3xl bg-[#0f0f0f] p-6 text-gray-100 shadow-2xl">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-xl font-semibold">
                Dashboard
            </h2>
                                    <p class="text-sm text-gray-400">
                                        Calendar view of key events
                                    </p>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-gray-400">
                                    <span>
                                        {{ calendar.range }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-3 rounded-3xl bg-[#1e1e1e] p-4 text-gray-900 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-7">
                                <div
                                    v-for="day in calendar.days"
                                    :key="day.day"
                                    class="flex min-h-[130px] flex-col justify-between rounded-2xl bg-[#d8d8d8] p-4"
                                >
                                    <span class="text-sm font-semibold text-gray-700">
                                        {{ day.day }}
                                    </span>
                                    <div v-if="day.title">
                                        <p class="text-sm font-semibold text-gray-900">
                                            {{ day.title }}
                                        </p>
                                        <p class="text-xs text-gray-600">
                                            {{ day.subtitle }}
                                        </p>
                                        <p
                                            v-if="day.tag"
                                            class="mt-2 text-xs font-semibold uppercase text-gray-500"
                                        >
                                            {{ day.tag }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="mt-8 rounded-3xl bg-[#1f1f1f] p-6 shadow-2xl">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-semibold">
                                    Recent Activity
                                </h2>
                                <button class="text-sm text-gray-400 hover:text-white">
                                    View all
                                </button>
                            </div>
                            <ul class="mt-4 space-y-4">
                                <li
                                    v-for="item in activityFeed"
                                    :key="item.action"
                                    class="rounded-2xl bg-[#2b2b2b] p-4"
                                >
                                    <p class="text-sm font-semibold text-gray-100">
                                        {{ item.action }}
                                    </p>
                                    <p class="text-sm text-gray-400">
                                        {{ item.description }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ item.time }}
                                    </p>
                                </li>
                            </ul>
                        </section>
                    </section>

                    <aside class="w-full max-w-sm rounded-3xl bg-[#e0e0e0] p-6 text-gray-900 shadow-2xl">
                        <div class="rounded-3xl bg-white p-4 shadow">
                            <h3 class="text-lg font-semibold">
                                Current Operations
                            </h3>
                            <div class="mt-4 rounded-2xl bg-gray-100 p-4">
                                <p class="text-xs text-gray-500">
                                    {{ operations.sitePerformance.updatedAgo }}
                                </p>
                                <p class="text-sm font-semibold">
                                    Active sites: {{ operations.sitePerformance.activeSites }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    Status: {{ operations.sitePerformance.status }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 space-y-4 rounded-3xl bg-white p-4 shadow">
                            <h4 class="text-lg font-semibold">
                                Performance Metrics
                            </h4>
                            <div
                                v-for="metric in operations.metrics"
                                :key="metric.label"
                                class="flex items-center justify-between rounded-2xl bg-gray-100 px-4 py-3"
                            >
                                <div>
                                    <p class="text-sm font-semibold">
                                        {{ metric.label }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ metric.subtitle }}
                                    </p>
                                </div>
                                <p class="text-lg font-semibold text-gray-800">
                                    {{ metric.value }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 rounded-3xl bg-white p-4 shadow">
                            <h4 class="text-lg font-semibold">
                                Notifications
                            </h4>
                            <div
                                v-for="note in operations.notifications"
                                :key="note.title"
                                class="mt-3 rounded-2xl bg-[#f7d9dd] p-4 text-sm text-gray-800"
                            >
                                <p class="font-semibold">
                                    {{ note.title }}
                                </p>
                                <p>
                                    {{ note.description }}
                                </p>
                            </div>
                    </div>
                    </aside>
                </div>
            </main>
        </div>
    </div>
</template>
