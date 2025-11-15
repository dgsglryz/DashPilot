<script setup lang="ts">
/**
 * AlertCard shows dismissible notification badges for quick alerting.
 */
import { ref } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = withDefaults(
    defineProps<{
        title: string;
        message: string;
        variant?: 'success' | 'warning' | 'danger' | 'info';
    }>(),
    {
        variant: 'info',
    },
);

const isDismissed = ref(false);
const dismiss = (): void => {
    isDismissed.value = true;
};
</script>

<template>
    <div
        v-if="!isDismissed"
        class="rounded-lg border p-4"
        :class="{
            'border-emerald-500/40 bg-emerald-500/10': props.variant === 'success',
            'border-yellow-500/40 bg-yellow-500/10': props.variant === 'warning',
            'border-red-500/40 bg-red-500/10': props.variant === 'danger',
            'border-blue-500/40 bg-blue-500/10': props.variant === 'info',
        }"
    >
        <div class="flex items-start gap-3">
            <div
                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full"
                :class="{
                    'bg-emerald-500/20 text-emerald-300': props.variant === 'success',
                    'bg-yellow-500/20 text-yellow-300': props.variant === 'warning',
                    'bg-red-500/20 text-red-300': props.variant === 'danger',
                    'bg-blue-500/20 text-blue-300': props.variant === 'info',
                }"
            >
                <span class="text-lg">
                    {{
                        props.variant === 'success'
                            ? '✓'
                            : props.variant === 'warning'
                              ? '!'
                              : props.variant === 'danger'
                                ? '✕'
                                : 'ℹ'
                    }}
                </span>
            </div>

            <div class="flex-1">
                <p class="text-sm font-semibold text-white">{{ title }}</p>
                <p class="text-xs text-gray-300">{{ message }}</p>
            </div>

            <button
                class="rounded-md p-1 text-gray-400 transition hover:bg-gray-800 hover:text-white"
                @click="dismiss"
            >
                <XMarkIcon class="h-4 w-4" />
                <span class="sr-only">Dismiss</span>
            </button>
        </div>
    </div>
</template>

