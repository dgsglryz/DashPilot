<script setup lang="ts">
/**
 * Task Create Page - Form to create a new task.
 */
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Shared/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    DocumentTextIcon,
    UserIcon,
    CalendarIcon,
    FlagIcon,
    GlobeAltIcon,
    BuildingOfficeIcon,
} from '@heroicons/vue/24/outline';

type User = {
    id: number;
    name: string;
    email: string;
};

type Site = {
    id: number;
    name: string;
};

type Client = {
    id: number;
    name: string;
};

const props = defineProps<{
    users: User[];
    sites: Site[];
    clients: Client[];
}>();

const form = useForm({
    title: '',
    description: '',
    site_id: null as number | null,
    client_id: null as number | null,
    assigned_to: props.users[0]?.id || 0,
    priority: 'medium' as 'low' | 'medium' | 'high' | 'urgent',
    status: 'pending' as 'pending' | 'in_progress' | 'completed' | 'cancelled',
    due_date: null as string | null,
});

/**
 * Submit the form to create a new task
 */
const submit = (): void => {
    form.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by Inertia redirect
        },
        onError: () => {
            // Error handled by Inertia
        },
    });
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('tasks.index')"
                    class="rounded-lg border border-gray-700/60 p-2 text-gray-400 transition-colors hover:border-white/60 hover:text-white"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-3xl font-bold text-white">Create Task</h1>
                    <p class="mt-1 text-sm text-gray-400">
                        Add a new task to the Kanban board
                    </p>
                </div>
            </div>

            <!-- Form -->
            <form
                @submit.prevent="submit"
                class="rounded-2xl border border-gray-700/70 bg-gray-900/60 p-6"
            >
                <div class="space-y-6">
                    <!-- Title & Description -->
                    <div>
                        <label
                            for="title"
                            class="mb-2 block text-sm font-medium text-white"
                        >
                            Task Title <span class="text-red-400">*</span>
                        </label>
                        <input
                            id="title"
                            v-model="form.title"
                            type="text"
                            required
                            class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 px-4 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            :class="{
                                'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                    form.errors.title,
                            }"
                            placeholder="Enter task title..."
                        />
                        <p
                            v-if="form.errors.title"
                            class="mt-1 text-sm text-red-400"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="description"
                            class="mb-2 block text-sm font-medium text-white"
                        >
                            Description <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <DocumentTextIcon
                                class="absolute left-3 top-3 h-5 w-5 text-gray-400"
                            />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="5"
                                required
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-4 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                :class="{
                                    'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                        form.errors.description,
                                }"
                                placeholder="Describe the task in detail..."
                            ></textarea>
                        </div>
                        <p
                            v-if="form.errors.description"
                            class="mt-1 text-sm text-red-400"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <!-- Site & Client (Optional) -->
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label
                                for="site_id"
                                class="mb-2 block text-sm font-medium text-white"
                            >
                                Site (Optional)
                            </label>
                            <div class="relative">
                                <GlobeAltIcon
                                    class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                                />
                                <select
                                    id="site_id"
                                    v-model="form.site_id"
                                    class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-4 text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    :class="{
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                            form.errors.site_id,
                                    }"
                                >
                                    <option :value="null">No site</option>
                                    <option
                                        v-for="site in sites"
                                        :key="site.id"
                                        :value="site.id"
                                    >
                                        {{ site.name }}
                                    </option>
                                </select>
                            </div>
                            <p
                                v-if="form.errors.site_id"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.site_id }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="client_id"
                                class="mb-2 block text-sm font-medium text-white"
                            >
                                Client (Optional)
                            </label>
                            <div class="relative">
                                <BuildingOfficeIcon
                                    class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                                />
                                <select
                                    id="client_id"
                                    v-model="form.client_id"
                                    class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-4 text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    :class="{
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                            form.errors.client_id,
                                    }"
                                >
                                    <option :value="null">No client</option>
                                    <option
                                        v-for="client in clients"
                                        :key="client.id"
                                        :value="client.id"
                                    >
                                        {{ client.name }}
                                    </option>
                                </select>
                            </div>
                            <p
                                v-if="form.errors.client_id"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.client_id }}
                            </p>
                        </div>
                    </div>

                    <!-- Assigned To & Priority -->
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label
                                for="assigned_to"
                                class="mb-2 block text-sm font-medium text-white"
                            >
                                Assigned To <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <UserIcon
                                    class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                                />
                                <select
                                    id="assigned_to"
                                    v-model="form.assigned_to"
                                    required
                                    class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-4 text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    :class="{
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                            form.errors.assigned_to,
                                    }"
                                >
                                    <option
                                        v-for="user in users"
                                        :key="user.id"
                                        :value="user.id"
                                    >
                                        {{ user.name }} ({{ user.email }})
                                    </option>
                                </select>
                            </div>
                            <p
                                v-if="form.errors.assigned_to"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.assigned_to }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="priority"
                                class="mb-2 block text-sm font-medium text-white"
                            >
                                Priority <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <FlagIcon
                                    class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                                />
                                <select
                                    id="priority"
                                    v-model="form.priority"
                                    required
                                    class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-4 text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    :class="{
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                            form.errors.priority,
                                    }"
                                >
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <p
                                v-if="form.errors.priority"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.priority }}
                            </p>
                        </div>
                    </div>

                    <!-- Status & Due Date -->
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label
                                for="status"
                                class="mb-2 block text-sm font-medium text-white"
                            >
                                Status <span class="text-red-400">*</span>
                            </label>
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 px-4 text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                :class="{
                                    'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                        form.errors.status,
                                }"
                            >
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <p
                                v-if="form.errors.status"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="due_date"
                                class="mb-2 block text-sm font-medium text-white"
                            >
                                Due Date
                            </label>
                            <div class="relative">
                                <CalendarIcon
                                    class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                                />
                                <input
                                    id="due_date"
                                    v-model="form.due_date"
                                    type="date"
                                    class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-4 text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    :class="{
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                            form.errors.due_date,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.due_date"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.due_date }}
                            </p>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div
                        class="flex items-center justify-end gap-4 border-t border-gray-700/60 pt-6"
                    >
                        <Link
                            :href="route('tasks.index')"
                            class="rounded-lg border border-gray-700/60 bg-gray-800/60 px-6 py-2 text-sm font-semibold text-white transition-colors hover:bg-gray-700/60"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-blue-600 px-6 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">Creating...</span>
                            <span v-else>Create Task</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

