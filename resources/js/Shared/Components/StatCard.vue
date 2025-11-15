<script setup lang="ts">
/**
 * StatCard Component mirroring v0.dev design. Displays hero metric tiles
 * with background imagery and supporting metrics.
 */
type Metric = {
    label: string;
    value: string;
    variant?: 'success' | 'warning' | 'danger' | 'info';
};

const props = withDefaults(
    defineProps<{
        title: string;
        subtitle: string;
        value: string | number;
        prefix?: string;
        suffix?: string;
        total?: string | number | null;
        label: string;
        status?: 'healthy' | 'good' | 'growth' | 'active' | 'warning' | 'critical' | 'neutral';
        metrics?: Metric[];
        badgeText?: string | null;
        badgeVariant?: 'primary' | 'success' | 'info';
        imageQuery: string;
        href?: string | null;
    }>(),
    {
        prefix: '',
        suffix: '',
        total: null,
        status: 'neutral',
        metrics: () => [],
        badgeText: null,
        badgeVariant: 'primary',
        href: null,
    },
);
</script>

<template>
    <component
        :is="props.href ? 'a' : 'div'"
        :href="props.href ?? undefined"
        class="rounded-xl border border-gray-700 bg-gray-800 transition-colors hover:border-gray-600"
    >
        <div class="relative h-40 overflow-hidden bg-gray-900">
            <img
                :src="`/placeholder.svg?height=160&width=400&query=${props.imageQuery}`"
                :alt="title"
                class="h-full w-full object-cover opacity-50 transition-opacity group-hover:opacity-60"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-gray-800 via-gray-800/50 to-transparent"></div>
            <div class="absolute inset-x-0 bottom-0 p-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-white">
                            {{ title }}
                        </h3>
                        <p class="mt-0.5 text-sm text-gray-300">
                            {{ subtitle }}
                        </p>
                    </div>
                    <span
                        v-if="badgeText"
                        class="rounded-full px-3 py-1 text-xs font-medium"
                        :class="{
                            'bg-blue-500/20 text-blue-300': badgeVariant === 'primary',
                            'bg-emerald-500/20 text-emerald-300': badgeVariant === 'success',
                            'bg-purple-500/20 text-purple-300': badgeVariant === 'info',
                        }"
                    >
                        {{ badgeText }}
                    </span>
                </div>
            </div>
        </div>

        <div class="p-4">
            <div class="mb-3">
                <div class="flex items-baseline gap-1">
                    <span v-if="prefix" class="text-lg text-gray-400">{{ prefix }}</span>
                    <span class="text-2xl font-bold text-white">{{ value }}</span>
                    <span v-if="total" class="text-lg text-gray-400">/{{ total }}</span>
                    <span v-if="suffix" class="text-lg text-gray-400">{{ suffix }}</span>
                </div>
                <p class="mt-1 text-sm text-gray-400">
                    {{ label }}
                </p>
            </div>

            <div
                v-if="metrics.length > 0"
                class="flex items-center justify-between border-t border-gray-700 pt-3"
            >
                <div
                    v-for="(metric, index) in metrics"
                    :key="`${metric.label}-${index}`"
                    class="flex-1"
                    :class="{ 'text-right': index > 0 }"
                >
                    <p class="text-xs uppercase tracking-wide text-gray-500">
                        {{ metric.label }}
                    </p>
                    <p
                        class="mt-0.5 text-sm font-medium"
                        :class="{
                            'text-emerald-400': metric.variant === 'success',
                            'text-yellow-400': metric.variant === 'warning',
                            'text-red-400': metric.variant === 'danger',
                            'text-blue-400': metric.variant === 'info',
                            'text-gray-300': !metric.variant,
                        }"
                    >
                        {{ metric.value }}
                    </p>
                </div>
            </div>
        </div>
    </component>
</template>

