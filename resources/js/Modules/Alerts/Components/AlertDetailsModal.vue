<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isOpen && alert"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4"
        >
            <div class="w-full max-w-2xl rounded-2xl border border-gray-700/70 bg-gray-900/95 p-6 shadow-2xl">
                <header class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm uppercase tracking-wide text-gray-400">
                            {{ alert.type }} · {{ formatRelativeTime(alert.createdAt) }}
                        </p>
                        <h2 class="text-2xl font-semibold text-white">
                            {{ alert.title }}
                        </h2>
                        <p class="text-sm text-gray-400">
                            {{ alert.siteName }}
                        </p>
                    </div>
                    <button
                        class="rounded-full p-2 text-gray-400 transition hover:bg-gray-800 hover:text-white"
                        @click="$emit('close')"
                        aria-label="Close alert details"
                    >
                        ✕
                    </button>
                </header>

                <div class="mb-5 grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-gray-700/60 bg-gray-800/80 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-400">Severity</p>
                        <span
                            class="mt-2 inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold capitalize"
                            :class="severityClass"
                        >
                            <span class="mr-2 h-2 w-2 rounded-full bg-current"></span>
                            {{ alert.severity }}
                        </span>
                    </div>
                    <div class="rounded-xl border border-gray-700/60 bg-gray-800/80 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-400">Status</p>
                        <span
                            class="mt-2 inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold capitalize"
                            :class="statusClass"
                        >
                            {{ alert.status }}
                        </span>
                    </div>
                </div>

                <section class="space-y-4">
                    <div class="rounded-2xl border border-gray-700/60 bg-gray-800/80 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-400">
                            Message
                        </p>
                        <p class="mt-2 text-sm text-gray-200">
                            {{ alert.message }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-gray-700/60 bg-gray-800/80 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-400">
                            Metadata
                        </p>
                        <dl class="mt-3 grid gap-3 md:grid-cols-2">
                            <div>
                                <dt class="text-xs text-gray-500">Site</dt>
                                <dd class="text-sm font-medium text-white">
                                    {{ alert.siteName }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Created</dt>
                                <dd class="text-sm font-medium text-white">
                                    {{ formatAbsoluteDate(alert.createdAt) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <footer class="mt-6 flex flex-wrap items-center justify-end gap-3">
                    <button
                        class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-300 transition hover:border-white/40 hover:text-white"
                        @click="$emit('view-site', alert.siteId)"
                    >
                        View Site
                    </button>
                    <button
                        v-if="alert.status === 'active'"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                        @click="$emit('acknowledge', alert.id)"
                    >
                        Acknowledge
                    </button>
                    <button
                        v-if="alert.status !== 'resolved'"
                        class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-700"
                        @click="$emit('resolve', alert.id)"
                    >
                        Resolve
                    </button>
                </footer>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
// @ts-nocheck
import { computed } from "vue";

/**
 * Props
 */
const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    alert: {
        type: Object,
        default: null,
    },
});

defineEmits(["close", "acknowledge", "resolve", "view-site"]);

const severityClass = computed(() => {
    if (!props.alert) return "bg-gray-500/20 text-gray-200";
    return {
        critical: "bg-red-500/10 text-red-300",
        warning: "bg-yellow-500/10 text-yellow-300",
        info: "bg-blue-500/10 text-blue-300",
    }[props.alert.severity] || "bg-gray-500/10 text-gray-300";
});

const statusClass = computed(() => {
    if (!props.alert) return "bg-gray-500/10 text-gray-300";
    return {
        active: "bg-red-500/10 text-red-300",
        acknowledged: "bg-yellow-500/10 text-yellow-300",
        resolved: "bg-green-500/10 text-green-300",
    }[props.alert.status] || "bg-gray-500/10 text-gray-300";
});

const formatRelativeTime = (timestamp?: string) => {
    if (!timestamp) return "Unknown";
    const now = new Date();
    const past = new Date(timestamp);
    const diffMs = now.getTime() - past.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    if (diffMins < 1) return "Just now";
    if (diffMins < 60) return `${diffMins}m ago`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    const diffDays = Math.floor(diffHours / 24);
    return `${diffDays}d ago`;
};

const formatAbsoluteDate = (timestamp?: string) => {
    if (!timestamp) return "Unknown";
    return new Date(timestamp).toLocaleString();
};
</script>

