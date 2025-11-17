<script setup lang="ts">
/**
 * HealthScoreModal displays detailed breakdown of site health score calculation.
 */
import { XMarkIcon, InformationCircleIcon } from "@heroicons/vue/24/outline";

interface ScoreBreakdown {
    issue: string;
    points: number;
    description?: string;
}

interface Props {
    isOpen: boolean;
    score: number;
    breakdown?: ScoreBreakdown[];
    siteName?: string;
}

withDefaults(defineProps<Props>(), {
    breakdown: () => [],
    siteName: "Site",
});

const emit = defineEmits<{
    close: () => void;
}>();

// Wrapper function for emit to satisfy TypeScript
const handleClose = (): void => {
    (emit as (event: 'close') => void)('close');
};
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            @click.self="handleClose"
        >
            <div
                class="relative w-full max-w-2xl rounded-xl border border-gray-700 bg-gray-800 p-6 shadow-2xl"
            >
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <InformationCircleIcon class="h-6 w-6 text-blue-400" />
                        <div>
                            <h3 class="text-lg font-semibold text-white">
                                Health Score Breakdown
                            </h3>
                            <p class="text-sm text-gray-400">
                                {{ siteName }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="handleClose"
                        class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-700 hover:text-white"
                    >
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>

                <!-- Score Summary -->
                <div
                    class="mb-6 rounded-lg border border-gray-700 bg-gray-900 p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">Current Score</p>
                            <p class="text-3xl font-bold text-white">
                                {{ score }}/100
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-400">Base Score</p>
                            <p class="text-xl font-semibold text-gray-300">
                                100
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Breakdown List -->
                <div class="space-y-3">
                    <h4 class="text-sm font-semibold text-gray-300">
                        Score Calculation
                    </h4>
                    <div v-if="breakdown.length > 0" class="space-y-2">
                        <div
                            v-for="(item, index) in breakdown"
                            :key="index"
                            class="flex items-center justify-between rounded-lg border border-gray-700 bg-gray-900 p-3"
                        >
                            <div class="flex-1">
                                <p class="text-sm font-medium text-white">
                                    {{ item.issue }}
                                </p>
                                <p
                                    v-if="item.description"
                                    class="mt-1 text-xs text-gray-400"
                                >
                                    {{ item.description }}
                                </p>
                            </div>
                            <span
                                class="ml-4 font-semibold"
                                :class="
                                    item.points < 0
                                        ? 'text-red-400'
                                        : 'text-green-400'
                                "
                            >
                                {{ item.points > 0 ? "+" : ""
                                }}{{ item.points }}
                            </span>
                        </div>
                    </div>
                    <div
                        v-else
                        class="rounded-lg border border-gray-700 bg-gray-900 p-4 text-center text-sm text-gray-400"
                    >
                        No breakdown available. Score is calculated
                        automatically.
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-6 flex justify-end">
                    <button
                        @click="handleClose"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
