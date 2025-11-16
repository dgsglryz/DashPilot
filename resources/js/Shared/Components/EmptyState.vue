<script setup lang="ts">
/**
 * EmptyState component displays beautiful empty states for various scenarios.
 * Used when there's no data to display (no sites, no alerts, no search results, etc.)
 */
import { computed } from 'vue';
import {
    GlobeAltIcon,
    CheckCircleIcon,
    MagnifyingGlassIcon,
    DocumentTextIcon,
    UserGroupIcon,
    ChartBarIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

interface Props {
    /**
     * Type of empty state (determines icon and default message)
     */
    type?: 'sites' | 'alerts' | 'search' | 'reports' | 'tasks' | 'clients' | 'metrics' | 'generic';
    /**
     * Custom title
     */
    title?: string;
    /**
     * Custom description
     */
    description?: string;
    /**
     * Action button text
     */
    actionLabel?: string;
    /**
     * Action button click handler
     */
    action?: () => void;
    /**
     * Custom icon component
     */
    icon?: typeof GlobeAltIcon;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'generic',
    title: undefined,
    description: undefined,
    actionLabel: undefined,
    action: undefined,
    icon: undefined,
});

/**
 * Default configurations for each empty state type
 */
const configs = {
    sites: {
        icon: GlobeAltIcon,
        title: 'No sites added yet',
        description: 'Get started by adding your first website to monitor.',
        actionLabel: 'Add Your First Site',
    },
    alerts: {
        icon: CheckCircleIcon,
        title: 'All systems healthy!',
        description: 'No active alerts. Everything is running smoothly.',
        actionLabel: undefined,
    },
    search: {
        icon: MagnifyingGlassIcon,
        title: 'No results found',
        description: 'Try adjusting your search terms or filters.',
        actionLabel: undefined,
    },
    reports: {
        icon: DocumentTextIcon,
        title: 'No reports generated',
        description: 'Generate your first report to track performance over time.',
        actionLabel: 'Generate Report',
    },
    tasks: {
        icon: CheckCircleIcon,
        title: 'No tasks yet',
        description: 'Create your first task to start managing your workflow.',
        actionLabel: 'Create Task',
    },
    clients: {
        icon: UserGroupIcon,
        title: 'No clients added',
        description: 'Add your first client to start organizing your sites.',
        actionLabel: 'Add Client',
    },
    metrics: {
        icon: ChartBarIcon,
        title: 'No metrics available',
        description: 'Metrics will appear here once you start monitoring sites.',
        actionLabel: undefined,
    },
    generic: {
        icon: ExclamationTriangleIcon,
        title: 'Nothing here yet',
        description: 'Content will appear here once available.',
        actionLabel: undefined,
    },
};

const config = computed(() => {
    const baseConfig = configs[props.type];
    return {
        icon: props.icon || baseConfig.icon,
        title: props.title || baseConfig.title,
        description: props.description || baseConfig.description,
        actionLabel: props.actionLabel || baseConfig.actionLabel,
    };
});
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-xl border border-gray-700/50 bg-gray-800/50 p-12 text-center">
        <component
            :is="config.icon"
            class="mx-auto mb-4 h-16 w-16 text-gray-600"
        />
        <h3 class="mb-2 text-lg font-semibold text-white">
            {{ config.title }}
        </h3>
        <p class="mb-6 max-w-md text-sm text-gray-400">
            {{ config.description }}
        </p>
        <button
            v-if="config.actionLabel && action"
            @click="action"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700"
        >
            {{ config.actionLabel }}
        </button>
        <slot />
    </div>
</template>

