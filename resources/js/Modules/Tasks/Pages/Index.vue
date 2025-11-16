<script setup lang="ts">
/**
 * Tasks Index Page - Kanban Board View
 * Displays tasks in a Kanban board layout with columns: Pending, In Progress, Completed, Cancelled
 */
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Shared/Layouts/AppLayout.vue';
import Pagination from '@/Shared/Components/Pagination.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    CheckCircleIcon,
    ClockIcon,
    XCircleIcon,
    PencilIcon,
    TrashIcon,
    EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline';

type Task = {
    id: number;
    title: string;
    description: string;
    status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    dueDate: string | null;
    completedAt: string | null;
    assignee: {
        id: number;
        name: string | null;
        email: string | null;
    };
    site: {
        id: number;
        name: string;
    } | null;
    client: {
        id: number;
        name: string;
    } | null;
};

type TasksByStatus = {
    pending: Task[];
    in_progress: Task[];
    completed: Task[];
    cancelled: Task[];
};

const props = defineProps<{
    tasks: TasksByStatus;
    tasksPaginated?: {
        data: any[];
        links: any[];
        from: number | null;
        to: number | null;
        total: number;
    };
    stats: {
        total: number;
        pending: number;
        in_progress: number;
        completed: number;
        urgent: number;
    };
    users: Array<{ id: number; name: string; email: string }>;
    sites: Array<{ id: number; name: string }>;
    clients: Array<{ id: number; name: string }>;
    filters?: {
        query?: string;
        status?: string;
        priority?: string;
        my_tasks?: boolean;
        urgent?: boolean;
    };
}>();

const searchQuery = ref(props.filters?.query || '');
const filterStatus = ref(props.filters?.status || 'all');
const filterPriority = ref(props.filters?.priority || 'all');
const filterMyTasks = ref(props.filters?.my_tasks || false);
const filterUrgent = ref(props.filters?.urgent || false);

/**
 * Format relative time from date string
 */
const formatRelativeTime = (dateString: string | null): string => {
    if (!dateString) return 'No due date';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = date.getTime() - now.getTime();
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffDays < 0) return 'Overdue';
    if (diffDays === 0) return 'Due today';
    if (diffDays === 1) return 'Due tomorrow';
    if (diffDays < 7) return `Due in ${diffDays} days`;
    return date.toLocaleDateString();
};

/**
 * Check if task is overdue
 */
const isOverdue = (dueDate: string | null): boolean => {
    if (!dueDate) return false;
    return new Date(dueDate) < new Date();
};

/**
 * Get priority badge classes
 */
const getPriorityBadgeClasses = (priority: string): string => {
    return {
        urgent: 'bg-red-500/10 text-red-400 border-red-500/20',
        high: 'bg-orange-500/10 text-orange-400 border-orange-500/20',
        medium: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        low: 'bg-gray-500/10 text-gray-400 border-gray-500/20',
    }[priority] || 'bg-gray-500/10 text-gray-400 border-gray-500/20';
};


/**
 * Update task status (move between columns)
 */
const updateTaskStatus = (taskId: number, newStatus: string): void => {
    router.post(
        route('tasks.status', taskId),
        { status: newStatus },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

/**
 * Delete task
 */
const deleteTask = (task: Task): void => {
    if (!confirm(`Are you sure you want to delete "${task.title}"?`)) {
        return;
    }

    router.delete(route('tasks.destroy', task.id), {
        preserveScroll: true,
    });
};

/**
 * Apply filters and reload page
 */
const applyFilters = (): void => {
    router.get(
        route('tasks.index'),
        {
            query: searchQuery.value || undefined,
            status: filterStatus.value !== 'all' ? filterStatus.value : undefined,
            priority: filterPriority.value !== 'all' ? filterPriority.value : undefined,
            my_tasks: filterMyTasks.value || undefined,
            urgent: filterUrgent.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const columns = [
    { key: 'pending', label: 'Pending', icon: ClockIcon, color: 'yellow' },
    { key: 'in_progress', label: 'In Progress', icon: CheckCircleIcon, color: 'blue' },
    { key: 'completed', label: 'Completed', icon: CheckCircleIcon, color: 'green' },
    { key: 'cancelled', label: 'Cancelled', icon: XCircleIcon, color: 'gray' },
];
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Tasks</h1>
                    <p class="mt-1 text-sm text-gray-400">
                        Manage your team tasks with Kanban board
                    </p>
                </div>
                <Link
                    :href="route('tasks.create')"
                    class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700"
                >
                    <PlusIcon class="h-5 w-5" />
                    Create Task
                </Link>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div
                    class="rounded-xl border border-gray-700/70 bg-gradient-to-br from-blue-500/10 to-blue-600/5 p-4"
                >
                    <p class="text-sm text-gray-400">Total Tasks</p>
                    <p class="mt-1 text-2xl font-bold text-white">
                        {{ stats.total }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-gray-700/70 bg-gradient-to-br from-yellow-500/10 to-yellow-600/5 p-4"
                >
                    <p class="text-sm text-gray-400">Pending</p>
                    <p class="mt-1 text-2xl font-bold text-white">
                        {{ stats.pending }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-gray-700/70 bg-gradient-to-br from-blue-500/10 to-blue-600/5 p-4"
                >
                    <p class="text-sm text-gray-400">In Progress</p>
                    <p class="mt-1 text-2xl font-bold text-white">
                        {{ stats.in_progress }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-gray-700/70 bg-gradient-to-br from-green-500/10 to-green-600/5 p-4"
                >
                    <p class="text-sm text-gray-400">Completed</p>
                    <p class="mt-1 text-2xl font-bold text-white">
                        {{ stats.completed }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-gray-700/70 bg-gradient-to-br from-red-500/10 to-red-600/5 p-4"
                >
                    <p class="text-sm text-gray-400">Urgent</p>
                    <p class="mt-1 text-2xl font-bold text-white">
                        {{ stats.urgent }}
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div
                class="rounded-xl border border-gray-700/50 bg-gray-800/50 p-4 backdrop-blur-sm"
            >
                <div class="flex flex-col gap-4 lg:flex-row">
                    <div class="relative flex-1">
                        <MagnifyingGlassIcon
                            class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                        />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search tasks..."
                            class="w-full rounded-lg border border-gray-700 bg-gray-900 py-2 pl-10 pr-4 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <div class="flex gap-2">
                        <select
                            v-model="filterStatus"
                            class="rounded-lg border border-gray-700 bg-gray-900 px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                            @change="applyFilters"
                        >
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>

                        <select
                            v-model="filterPriority"
                            class="rounded-lg border border-gray-700 bg-gray-900 px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                            @change="applyFilters"
                        >
                            <option value="all">All Priorities</option>
                            <option value="urgent">Urgent</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>

                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-700 bg-gray-900 px-4 py-2 text-white"
                        >
                            <input
                                v-model="filterMyTasks"
                                type="checkbox"
                                class="rounded border-gray-600 text-blue-600 focus:ring-blue-500"
                                @change="applyFilters"
                            />
                            <span class="text-sm">My Tasks</span>
                        </label>

                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-700 bg-gray-900 px-4 py-2 text-white"
                        >
                            <input
                                v-model="filterUrgent"
                                type="checkbox"
                                class="rounded border-gray-600 text-blue-600 focus:ring-blue-500"
                                @change="applyFilters"
                            />
                            <span class="text-sm">Urgent</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Kanban Board -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                <div
                    v-for="column in columns"
                    :key="column.key"
                    class="flex flex-col rounded-xl border border-gray-700/70 bg-gray-900/60"
                >
                    <!-- Column Header -->
                    <div
                        class="flex items-center justify-between border-b border-gray-700/70 p-4"
                    >
                        <div class="flex items-center gap-2">
                            <component
                                :is="column.icon"
                                class="h-5 w-5"
                                :class="{
                                    'text-yellow-400': column.color === 'yellow',
                                    'text-blue-400': column.color === 'blue',
                                    'text-green-400': column.color === 'green',
                                    'text-gray-400': column.color === 'gray',
                                }"
                            />
                            <h3 class="font-semibold text-white">
                                {{ column.label }}
                            </h3>
                            <span
                                class="rounded-full bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-300"
                            >
                                {{ tasks[column.key as keyof TasksByStatus]?.length || 0 }}
                            </span>
                        </div>
                    </div>

                    <!-- Column Tasks -->
                    <div class="flex-1 space-y-3 overflow-y-auto p-4">
                        <div
                            v-for="task in tasks[column.key as keyof TasksByStatus]"
                            :key="task.id"
                            class="group relative rounded-xl border border-gray-700/60 bg-gray-800/60 p-4 transition-all hover:border-blue-500/60 hover:shadow-lg"
                        >
                            <!-- Task Header -->
                            <div class="mb-3">
                                <div class="flex items-start justify-between gap-2">
                                    <Link
                                        :href="route('tasks.edit', task.id)"
                                        class="flex-1 font-semibold text-white transition-colors hover:text-blue-400"
                                    >
                                        {{ task.title }}
                                    </Link>
                                    <div class="relative">
                                        <button
                                            class="rounded-lg p-1 text-gray-400 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-gray-700 hover:text-white"
                                        >
                                            <EllipsisVerticalIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                                <p
                                    class="mt-1 line-clamp-2 text-sm text-gray-400"
                                >
                                    {{ task.description }}
                                </p>
                            </div>

                            <!-- Task Meta -->
                            <div class="space-y-2">
                                <!-- Priority Badge -->
                                <div class="flex items-center gap-2">
                                    <span
                                        class="rounded-full border px-2 py-0.5 text-xs font-semibold uppercase"
                                        :class="getPriorityBadgeClasses(task.priority)"
                                    >
                                        {{ task.priority }}
                                    </span>
                                </div>

                                <!-- Due Date -->
                                <div
                                    v-if="task.dueDate"
                                    class="flex items-center gap-1.5 text-xs"
                                    :class="
                                        isOverdue(task.dueDate)
                                            ? 'text-red-400'
                                            : 'text-gray-400'
                                    "
                                >
                                    <ClockIcon class="h-3.5 w-3.5" />
                                    <span>{{ formatRelativeTime(task.dueDate) }}</span>
                                </div>

                                <!-- Assignee -->
                                <div
                                    v-if="task.assignee.name"
                                    class="flex items-center gap-2 text-xs text-gray-400"
                                >
                                    <div
                                        class="flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-xs font-semibold text-white"
                                    >
                                        {{
                                            task.assignee.name
                                                .split(' ')
                                                .map((n) => n[0])
                                                .join('')
                                                .toUpperCase()
                                                .slice(0, 2)
                                        }}
                                    </div>
                                    <span>{{ task.assignee.name }}</span>
                                </div>

                                <!-- Site/Client -->
                                <div
                                    v-if="task.site || task.client"
                                    class="flex items-center gap-1.5 text-xs text-gray-500"
                                >
                                    <span v-if="task.site">{{ task.site.name }}</span>
                                    <span v-if="task.site && task.client">•</span>
                                    <span v-if="task.client">{{ task.client.name }}</span>
                                </div>
                            </div>

                            <!-- Task Actions -->
                            <div
                                class="absolute right-2 top-2 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                            >
                                <Link
                                    :href="route('tasks.edit', task.id)"
                                    class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-700 hover:text-white"
                                >
                                    <PencilIcon class="h-4 w-4" />
                                </Link>
                                <button
                                    @click="deleteTask(task)"
                                    class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-red-500/10 hover:text-red-400"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Status Change Dropdown (Quick Actions) -->
                            <div
                                class="mt-3 flex flex-wrap gap-2 border-t border-gray-700/60 pt-3"
                            >
                                <button
                                    v-for="otherColumn in columns.filter(
                                        (c) => c.key !== column.key,
                                    )"
                                    :key="otherColumn.key"
                                    @click="updateTaskStatus(task.id, otherColumn.key)"
                                    class="rounded-lg border border-gray-700/60 bg-gray-900/60 px-2 py-1 text-xs text-gray-400 transition-colors hover:border-blue-500/60 hover:text-blue-400"
                                >
                                    Move to {{ otherColumn.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-if="
                                !tasks[column.key as keyof TasksByStatus] ||
                                tasks[column.key as keyof TasksByStatus].length === 0
                            "
                            class="rounded-xl border border-dashed border-gray-700/70 p-8 text-center text-gray-500"
                        >
                            <p class="text-sm">No tasks in this column</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pagination -->
            <div v-if="props.tasksPaginated && props.tasksPaginated.links.length > 3" class="mt-6">
                <Pagination
                    :links="props.tasksPaginated.links"
                    :from="props.tasksPaginated.from ?? undefined"
                    :to="props.tasksPaginated.to ?? undefined"
                    :total="props.tasksPaginated.total"
                />
            </div>
        </div>
    </AppLayout>
</template>

