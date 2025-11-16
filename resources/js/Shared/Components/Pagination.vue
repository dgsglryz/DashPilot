<template>
    <div v-if="links.length > 3" class="flex items-center justify-between border-t border-gray-700 px-4 py-3 sm:px-6">
        <div class="flex flex-1 justify-between sm:hidden">
            <Link
                v-if="links[0]?.url"
                :href="links[0].url ?? '#'"
                class="relative inline-flex items-center rounded-md border border-gray-700 bg-gray-800 px-4 py-2 text-sm font-medium text-gray-300 hover:bg-gray-700"
            >
                Previous
            </Link>
            <Link
                v-if="links[links.length - 1]?.url"
                :href="links[links.length - 1].url ?? '#'"
                class="relative ml-3 inline-flex items-center rounded-md border border-gray-700 bg-gray-800 px-4 py-2 text-sm font-medium text-gray-300 hover:bg-gray-700"
            >
                Next
            </Link>
        </div>
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-400">
                    Showing
                    <span class="font-medium text-white">{{ from }}</span>
                    to
                    <span class="font-medium text-white">{{ to }}</span>
                    of
                    <span class="font-medium text-white">{{ total }}</span>
                    results
                </p>
            </div>
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                    <Link
                        v-for="(link, index) in links"
                        :key="index"
                        :href="link.url ?? '#'"
                        :class="[
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold',
                            index === 0 ? 'rounded-l-md' : '',
                            index === links.length - 1 ? 'rounded-r-md' : '',
                            link.active
                                ? 'z-10 bg-blue-600 text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600'
                                : 'bg-gray-800 text-gray-300 ring-1 ring-inset ring-gray-700 hover:bg-gray-700 focus:z-20 focus:outline-offset-0',
                            !link.url ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
                        ]"
                    >
                        {{ stripHtml(link.label) }}
                    </Link>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    from?: number;
    to?: number;
    total?: number;
}>();

/**
 * Strip HTML tags from label for safe rendering
 * Laravel pagination returns HTML like "&laquo; Previous" which we render as plain text
 */
const stripHtml = (html: string): string => {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent || div.innerText || '';
};
</script>

