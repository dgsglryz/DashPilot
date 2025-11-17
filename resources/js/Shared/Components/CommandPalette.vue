<script setup lang="ts">
/**
 * CommandPalette provides a modern command palette interface (Cmd+K / Ctrl+K)
 * for quick navigation and actions throughout the application.
 * 
 * Features:
 * - Global search with autocomplete
 * - Keyboard navigation (arrow keys, enter)
 * - Recent searches (localStorage)
 * - Quick actions (create, export, toggle dark mode)
 * - Navigation shortcuts
 */
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/Shared/Composables/useToast';
import {
    MagnifyingGlassIcon,
    HomeIcon,
    GlobeAltIcon,
    BellIcon,
    UserGroupIcon,
    DocumentTextIcon,
    ChartBarIcon,
    PlusIcon,
    ArrowDownTrayIcon,
    MoonIcon,
    CommandLineIcon,
} from '@heroicons/vue/24/outline';

interface Command {
    id: string;
    label: string;
    description?: string;
    icon: typeof HomeIcon;
    category: string;
    action: () => void | Promise<void>;
    keywords?: string[];
}

const props = defineProps<{
    isOpen: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const toast = useToast();
const searchQuery = ref('');
const selectedIndex = ref(0);
const recentSearches = ref<string[]>([]);

/**
 * Load recent searches from localStorage
 */
onMounted(() => {
    const stored = localStorage.getItem('dashpilot_recent_searches');
    if (stored) {
        recentSearches.value = JSON.parse(stored).slice(0, 5);
    }
});

/**
 * Available commands
 */
const commands = computed<Command[]>(() => {
    const baseCommands: Command[] = [
        // Navigation
        {
            id: 'dashboard',
            label: 'Go to Dashboard',
            description: 'Navigate to main dashboard',
            icon: HomeIcon,
            category: 'Navigation',
            action: () => router.visit(route('dashboard')),
            keywords: ['dashboard', 'home', 'overview', 'main'],
        },
        {
            id: 'sites',
            label: 'Go to Sites',
            description: 'View all monitored sites',
            icon: GlobeAltIcon,
            category: 'Navigation',
            action: () => router.visit(route('sites.index')),
            keywords: ['sites', 'websites', 'domains'],
        },
        {
            id: 'alerts',
            label: 'Go to Alerts',
            description: 'View all alerts and notifications',
            icon: BellIcon,
            category: 'Navigation',
            action: () => router.visit(route('alerts.index')),
            keywords: ['alerts', 'notifications', 'warnings'],
        },
        {
            id: 'clients',
            label: 'Go to Clients',
            description: 'Manage clients',
            icon: UserGroupIcon,
            category: 'Navigation',
            action: () => router.visit(route('clients.index')),
            keywords: ['clients', 'customers'],
        },
        {
            id: 'reports',
            label: 'Go to Reports',
            description: 'View performance reports',
            icon: DocumentTextIcon,
            category: 'Navigation',
            action: () => router.visit(route('reports.index')),
            keywords: ['reports', 'analytics'],
        },
        {
            id: 'metrics',
            label: 'Go to Metrics',
            description: 'View detailed metrics',
            icon: ChartBarIcon,
            category: 'Navigation',
            action: () => router.visit(route('metrics.index')),
            keywords: ['metrics', 'analytics', 'stats'],
        },
        // Actions
        {
            id: 'create-site',
            label: 'Create new site',
            description: 'Add a new site to monitor',
            icon: PlusIcon,
            category: 'Actions',
            action: () => router.visit(route('sites.create')),
            keywords: ['create', 'add', 'new', 'site'],
        },
        {
            id: 'create-client',
            label: 'Create new client',
            description: 'Add a new client',
            icon: PlusIcon,
            category: 'Actions',
            action: () => router.visit(route('clients.create')),
            keywords: ['create', 'add', 'new', 'client'],
        },
        {
            id: 'export-sites',
            label: 'Export sites',
            description: 'Download sites as CSV/Excel',
            icon: ArrowDownTrayIcon,
            category: 'Actions',
            action: () => {
                router.visit(route('sites.export'), {
                    method: 'get',
                    onSuccess: () => {
                        toast.success('Sites exported successfully');
                        emit('close');
                    },
                });
            },
            keywords: ['export', 'download', 'csv', 'excel'],
        },
        {
            id: 'toggle-dark',
            label: 'Toggle dark mode',
            description: 'Switch between light and dark theme',
            icon: MoonIcon,
            category: 'Actions',
            action: () => {
                // Toggle dark mode logic here
                toast.info('Dark mode toggle - coming soon');
                emit('close');
            },
            keywords: ['dark', 'theme', 'mode', 'toggle'],
        },
    ];

    // Filter commands based on search query
    if (!searchQuery.value) {
        return baseCommands;
    }

    const query = searchQuery.value.toLowerCase();
    return baseCommands.filter((cmd) => {
        const matchesLabel = cmd.label.toLowerCase().includes(query);
        const matchesDescription = cmd.description?.toLowerCase().includes(query);
        const matchesKeywords = cmd.keywords?.some((kw) => kw.includes(query));
        return matchesLabel || matchesDescription || matchesKeywords;
    });
});

/**
 * Grouped commands by category
 */
const groupedCommands = computed(() => {
    const groups: Record<string, Command[]> = {};
    for (const cmd of commands.value) {
        if (!groups[cmd.category]) {
            groups[cmd.category] = [];
        }
        groups[cmd.category].push(cmd);
    }
    return groups;
});

/**
 * Flattened commands for keyboard navigation
 */
const flatCommands = computed(() => {
    return Object.values(groupedCommands.value).flat();
});

/**
 * Handle keyboard navigation
 */
const handleKeydown = (e: KeyboardEvent) => {
    if (!props.isOpen) return;

    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            selectedIndex.value = Math.min(
                selectedIndex.value + 1,
                flatCommands.value.length - 1,
            );
            break;
        case 'ArrowUp':
            e.preventDefault();
            selectedIndex.value = Math.max(selectedIndex.value - 1, 0);
            break;
        case 'Enter':
            e.preventDefault();
            if (flatCommands.value[selectedIndex.value]) {
                executeCommand(flatCommands.value[selectedIndex.value]);
            }
            break;
        case 'Escape':
            e.preventDefault();
            emit('close');
            break;
    }
};

/**
 * Execute a command
 */
const executeCommand = (command: Command) => {
    // Save to recent searches
    if (searchQuery.value) {
        const recent = recentSearches.value.filter((s) => s !== searchQuery.value);
        recent.unshift(searchQuery.value);
        recentSearches.value = recent.slice(0, 5);
        localStorage.setItem('dashpilot_recent_searches', JSON.stringify(recentSearches.value));
    }

    command.action();
    emit('close');
};

/**
 * Reset selection when search changes
 */
watch(searchQuery, () => {
    selectedIndex.value = 0;
});

/**
 * Reset selection when modal opens
 */
watch(() => props.isOpen, (isOpen) => {
    if (isOpen) {
        searchQuery.value = '';
        selectedIndex.value = 0;
    }
});

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div data-testid="command-palette">
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
            class="fixed inset-0 z-50 flex items-start justify-center pt-[20vh]"
            @click.self="() => emit('close')"
        >
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="() => emit('close')"></div>

            <!-- Command Palette -->
            <div
                class="relative z-10 w-full max-w-2xl rounded-xl border border-gray-700 bg-gray-800 shadow-2xl"
            >
                <!-- Search Input -->
                <div class="flex items-center gap-3 border-b border-gray-700 p-4">
                    <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search or type a command..."
                        class="flex-1 bg-transparent text-white placeholder:text-gray-500 focus:outline-none"
                        autofocus
                    />
                    <kbd
                        class="rounded border border-gray-600 bg-gray-700 px-2 py-1 text-xs text-gray-400"
                    >
                        ESC
                    </kbd>
                </div>

                <!-- Commands List -->
                <div class="max-h-96 overflow-y-auto">
                    <!-- Recent Searches -->
                    <div
                        v-if="!searchQuery && recentSearches.length > 0"
                        class="border-b border-gray-700 p-2"
                    >
                        <div class="px-3 py-2 text-xs font-semibold uppercase text-gray-500">
                            Recent Searches
                        </div>
                        <button
                            v-for="search in recentSearches"
                            :key="search"
                            @click="searchQuery = search"
                            class="w-full rounded-lg px-3 py-2 text-left text-sm text-gray-300 hover:bg-gray-700"
                        >
                            <div class="flex items-center gap-2">
                                <MagnifyingGlassIcon class="h-4 w-4 text-gray-500" />
                                {{ search }}
                            </div>
                        </button>
                    </div>

                    <!-- Commands by Category -->
                    <div v-if="Object.keys(groupedCommands).length > 0" class="p-2">
                        <template
                            v-for="(categoryCommands, category) in groupedCommands"
                            :key="category"
                        >
                            <div class="px-3 py-2 text-xs font-semibold uppercase text-gray-500">
                                {{ category }}
                            </div>
                            <button
                                v-for="command in categoryCommands"
                                :key="command.id"
                                @click="executeCommand(command)"
                                :class="[
                                    'w-full rounded-lg px-3 py-2 text-left transition-colors',
                                    flatCommands.indexOf(command) === selectedIndex
                                        ? 'bg-blue-600 text-white'
                                        : 'text-gray-300 hover:bg-gray-700',
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <component
                                        :is="command.icon"
                                        class="h-5 w-5 flex-shrink-0"
                                    />
                                    <div class="flex-1">
                                        <div class="font-medium">{{ command.label }}</div>
                                        <div
                                            v-if="command.description"
                                            class="text-xs opacity-75"
                                        >
                                            {{ command.description }}
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>

                    <!-- No Results -->
                    <div
                        v-else
                        class="p-12 text-center"
                    >
                        <CommandLineIcon class="mx-auto mb-4 h-12 w-12 text-gray-600" />
                        <p class="text-gray-400">No commands found</p>
                        <p class="mt-1 text-sm text-gray-500">
                            Try searching for "sites", "alerts", or "create"
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between border-t border-gray-700 px-4 py-3 text-xs text-gray-500">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1">
                            <kbd class="rounded border border-gray-600 bg-gray-700 px-1.5 py-0.5">
                                ↑↓
                            </kbd>
                            <span>Navigate</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <kbd class="rounded border border-gray-600 bg-gray-700 px-1.5 py-0.5">
                                Enter
                            </kbd>
                            <span>Select</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <kbd class="rounded border border-gray-600 bg-gray-700 px-1.5 py-0.5">
                            ESC
                        </kbd>
                        <span>Close</span>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
    </div>
</template>

