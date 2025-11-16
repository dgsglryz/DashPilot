<script setup lang="ts">
/**
 * Breadcrumbs component provides navigation trail for better user orientation.
 */
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@heroicons/vue/24/outline';

interface BreadcrumbItem {
    label: string;
    href?: string;
}

interface Props {
    items: BreadcrumbItem[];
}

defineProps<Props>();
</script>

<template>
    <nav class="flex items-center gap-2 text-sm" aria-label="Breadcrumb">
        <ol class="flex items-center gap-2">
            <li
                v-for="(item, index) in items"
                :key="index"
                class="flex items-center gap-2"
            >
                <Link
                    v-if="item.href && index < items.length - 1"
                    :href="item.href"
                    class="text-gray-400 hover:text-white transition-colors"
                >
                    {{ item.label }}
                </Link>
                <span
                    v-else
                    class="text-white font-medium"
                >
                    {{ item.label }}
                </span>
                <ChevronRightIcon
                    v-if="index < items.length - 1"
                    class="h-4 w-4 text-gray-500"
                />
            </li>
        </ol>
    </nav>
</template>

