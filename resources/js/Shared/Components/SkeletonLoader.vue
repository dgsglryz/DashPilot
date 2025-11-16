<script setup lang="ts">
/**
 * SkeletonLoader provides animated placeholder content while data is loading.
 * Uses TailwindCSS animate-pulse for smooth loading animations.
 */
interface Props {
    /**
     * Number of skeleton lines to display
     */
    lines?: number;
    /**
     * Show circular avatar skeleton
     */
    avatar?: boolean;
    /**
     * Show rectangular image skeleton
     */
    image?: boolean;
    /**
     * Custom height class (default: h-4)
     */
    height?: string;
    /**
     * Show card-style skeleton
     */
    card?: boolean;
}

withDefaults(defineProps<Props>(), {
    lines: 3,
    avatar: false,
    image: false,
    height: 'h-4',
    card: false,
});
</script>

<template>
    <div v-if="card" class="rounded-xl border border-gray-700 bg-gray-800 p-6">
        <div v-if="avatar" class="mb-4 flex items-center gap-4">
            <div class="h-12 w-12 animate-pulse rounded-full bg-gray-700"></div>
            <div class="flex-1 space-y-2">
                <div class="h-4 w-3/4 animate-pulse rounded bg-gray-700"></div>
                <div class="h-3 w-1/2 animate-pulse rounded bg-gray-700"></div>
            </div>
        </div>
        <div v-if="image" class="mb-4 h-48 w-full animate-pulse rounded-lg bg-gray-700"></div>
        <div class="space-y-3">
            <div
                v-for="i in lines"
                :key="i"
                :class="[
                    height,
                    'animate-pulse rounded bg-gray-700',
                    i === lines ? 'w-5/6' : 'w-full',
                ]"
            ></div>
        </div>
    </div>
    <div v-else class="space-y-3">
        <div v-if="avatar" class="mb-4 flex items-center gap-4">
            <div class="h-12 w-12 animate-pulse rounded-full bg-gray-700"></div>
            <div class="flex-1 space-y-2">
                <div class="h-4 w-3/4 animate-pulse rounded bg-gray-700"></div>
                <div class="h-3 w-1/2 animate-pulse rounded bg-gray-700"></div>
            </div>
        </div>
        <div v-if="image" class="mb-4 h-48 w-full animate-pulse rounded-lg bg-gray-700"></div>
        <div
            v-for="i in lines"
            :key="i"
            :class="[
                height,
                'animate-pulse rounded bg-gray-700',
                i === lines ? 'w-5/6' : 'w-full',
            ]"
        ></div>
    </div>
</template>

