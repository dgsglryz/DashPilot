<script setup lang="ts">
/**
 * QuickActionsDropdown provides contextual actions for table rows.
 */
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    EllipsisVerticalIcon,
    ArrowPathIcon,
    ArrowTopRightOnSquareIcon,
    LinkIcon,
    DocumentTextIcon,
    StarIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/Shared/Composables/useToast';

interface Action {
    label: string;
    icon: typeof EllipsisVerticalIcon;
    action: () => void;
    variant?: 'default' | 'danger';
}

interface Props {
    siteId: number;
    siteUrl?: string;
    isFavorited?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    siteUrl: '',
    isFavorited: false,
});

const emit = defineEmits<{
    (e: 'favorite-toggled'): void;
}>();

const toast = useToast();
const isOpen = ref(false);

const actions = computed<Action[]>(() => [
    {
        label: 'Run health check now',
        icon: ArrowPathIcon,
        action: () => {
            router.post(route('sites.health-check', props.siteId), {}, {
                onSuccess: () => {
                    toast.success('Health check queued successfully');
                    isOpen.value = false;
                },
            });
        },
    },
    {
        label: 'View in new tab',
        icon: ArrowTopRightOnSquareIcon,
        action: () => {
            window.open(props.siteUrl, '_blank');
            isOpen.value = false;
        },
    },
    {
        label: 'Copy URL',
        icon: LinkIcon,
        action: () => {
            navigator.clipboard.writeText(props.siteUrl);
            toast.success('URL copied to clipboard');
            isOpen.value = false;
        },
    },
    {
        label: 'Generate report',
        icon: DocumentTextIcon,
        action: () => {
            router.visit(route('reports.index'), {
                data: { site_id: props.siteId },
            });
            isOpen.value = false;
        },
    },
    {
        label: props.isFavorited ? 'Remove from favorites' : 'Mark as favorite',
        icon: StarIcon,
        action: () => {
            router.post(route('sites.toggle-favorite', props.siteId), {}, {
                onSuccess: () => {
                    toast.success(
                        props.isFavorited
                            ? 'Removed from favorites'
                            : 'Added to favorites',
                    );
                    emit('favorite-toggled');
                    isOpen.value = false;
                },
            });
        },
    },
    {
        label: 'Delete site',
        icon: TrashIcon,
        variant: 'danger',
        action: () => {
            if (confirm('Are you sure you want to delete this site?')) {
                router.delete(route('sites.destroy', props.siteId), {
                    onSuccess: () => {
                        toast.success('Site deleted successfully');
                        isOpen.value = false;
                    },
                });
            }
        },
    },
]);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
    isOpen.value = false;
};
</script>

<template>
    <div class="relative" v-click-outside="closeDropdown">
        <button
            @click="toggleDropdown"
            class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-700 hover:text-white"
        >
            <EllipsisVerticalIcon class="h-5 w-5" />
        </button>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 z-50 mt-2 w-56 rounded-lg border border-gray-700 bg-gray-800 shadow-xl"
            >
                <div class="py-1">
                    <button
                        v-for="(action, index) in actions"
                        :key="index"
                        @click="action.action"
                        class="flex w-full items-center gap-3 px-4 py-2 text-sm transition-colors"
                        :class="
                            action.variant === 'danger'
                                ? 'text-red-400 hover:bg-red-500/10'
                                : 'text-gray-300 hover:bg-gray-700'
                        "
                    >
                        <component :is="action.icon" class="h-4 w-4" />
                        {{ action.label }}
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script lang="ts">
// Click outside directive
const vClickOutside = {
    mounted(el: HTMLElement, binding: { value: () => void }) {
        type ExtendedHTMLElement = HTMLElement & { clickOutsideEvent?: (e: Event) => void };
        const extendedEl = el as ExtendedHTMLElement;
        extendedEl.clickOutsideEvent = (event: Event) => {
            if (!(el === event.target || el.contains(event.target as Node))) {
                binding.value();
            }
        };
        document.addEventListener('click', extendedEl.clickOutsideEvent);
    },
    unmounted(el: HTMLElement) {
        type ExtendedHTMLElement = HTMLElement & { clickOutsideEvent?: (e: Event) => void };
        const extendedEl = el as ExtendedHTMLElement;
        const clickHandler = extendedEl.clickOutsideEvent;
        if (clickHandler) {
            document.removeEventListener('click', clickHandler);
        }
    },
};
</script>

