<script setup lang="ts">
/**
 * MetricCard displays compact KPI rows inside the sidebar with an icon,
 * descriptive labels, and the metric value.
 */
import {
    ChartBarIcon,
    ClockIcon,
    CurrencyDollarIcon,
    PresentationChartLineIcon,
    ServerIcon,
} from '@heroicons/vue/24/outline';

type Variant = 'default' | 'success' | 'warning' | 'danger' | 'info';

const iconComponents = {
    'chart-bar': ChartBarIcon,
    'currency-dollar': CurrencyDollarIcon,
    'chart-line': PresentationChartLineIcon,
    clock: ClockIcon,
    server: ServerIcon,
} as const;

const props = withDefaults(
    defineProps<{
        icon: keyof typeof iconComponents;
        label: string;
        sublabel: string;
        value: string | number;
        prefix?: string;
        suffix?: string;
        variant?: Variant;
    }>(),
    {
        prefix: '',
        suffix: '',
        variant: 'default',
    },
);
</script>

<template>
    <div class="flex items-center gap-4 rounded-lg border border-gray-800 p-3">
        <div
            class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg"
            :class="{
                'bg-emerald-500/10': props.variant === 'success',
                'bg-yellow-500/10': props.variant === 'warning',
                'bg-red-500/10': props.variant === 'danger',
                'bg-blue-500/10': props.variant === 'info',
                'bg-gray-800': props.variant === 'default',
            }"
        >
            <component
                :is="iconComponents[props.icon]"
                class="h-6 w-6"
                :class="{
                    'text-emerald-400': props.variant === 'success',
                    'text-yellow-400': props.variant === 'warning',
                    'text-red-400': props.variant === 'danger',
                    'text-blue-400': props.variant === 'info',
                    'text-gray-400': props.variant === 'default',
                }"
            />
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-white">
                {{ label }}
            </p>
            <p class="text-xs text-gray-400">
                {{ sublabel }}
            </p>
        </div>
        <div class="text-right text-white">
            <span class="text-xs text-gray-400">{{ prefix }}</span>
            <span class="text-lg font-semibold">{{ value }}</span>
            <span class="text-xs text-gray-400">{{ suffix }}</span>
        </div>
    </div>
</template>

