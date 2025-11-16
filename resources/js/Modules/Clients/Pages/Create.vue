<script setup lang="ts">
/**
 * Client Create Page - Form to create a new client.
 */
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Shared/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    BuildingOfficeIcon,
    EnvelopeIcon,
    PhoneIcon,
    UserIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline';

type Developer = {
    id: number;
    name: string;
    email: string;
};

defineProps<{
    developers: Developer[];
}>();

const form = useForm({
    name: '',
    company: '',
    email: '',
    phone: null as string | null,
    status: 'active' as 'active' | 'inactive',
    assigned_developer_id: null as number | null,
    notes: null as string | null,
});

/**
 * Submit the form to create a new client
 */
const submit = (): void => {
    form.post(route('clients.store'), {
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
                    :href="route('clients.index')"
                    class="rounded-lg border border-gray-700/60 p-2 text-gray-400 transition-colors hover:border-white/60 hover:text-white"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-3xl font-bold text-white">Create Client</h1>
                    <p class="mt-1 text-sm text-gray-400">
                        Add a new client to your agency portfolio
                    </p>
                </div>
            </div>

            <!-- Form -->
            <form
                @submit.prevent="submit"
                class="rounded-2xl border border-gray-700/70 bg-gray-900/60 p-6"
            >
                <div class="space-y-6">
                    <!-- Name & Company -->
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label
                                for="name"
                                class="mb-2 block text-sm font-medium text-white"
                            >
                                Client Name <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <UserIcon
                                    class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                                />
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-4 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    :class="{
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                            form.errors.name,
                                    }"
                                    placeholder="John Doe"
                                />
                            </div>
                            <p
                                v-if="form.errors.name"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="company"
                                class="mb-2 block text-sm font-medium text-white"
                            >
                                Company <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <BuildingOfficeIcon
                                    class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                                />
                                <input
                                    id="company"
                                    v-model="form.company"
                                    type="text"
                                    required
                                    class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-4 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    :class="{
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                            form.errors.company,
                                    }"
                                    placeholder="Acme Inc."
                                />
                            </div>
                            <p
                                v-if="form.errors.company"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.company }}
                            </p>
                        </div>
                    </div>

                    <!-- Email & Phone -->
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label
                                for="email"
                                class="mb-2 block text-sm font-medium text-white"
                            >
                                Email <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <EnvelopeIcon
                                    class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                                />
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-4 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    :class="{
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                            form.errors.email,
                                    }"
                                    placeholder="john@acme.com"
                                />
                            </div>
                            <p
                                v-if="form.errors.email"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="phone"
                                class="mb-2 block text-sm font-medium text-white"
                            >
                                Phone
                            </label>
                            <div class="relative">
                                <PhoneIcon
                                    class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                                />
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-4 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    :class="{
                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                            form.errors.phone,
                                    }"
                                    placeholder="+1 (555) 123-4567"
                                />
                            </div>
                            <p
                                v-if="form.errors.phone"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.phone }}
                            </p>
                        </div>
                    </div>

                    <!-- Status & Assigned Developer -->
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
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
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
                                for="assigned_developer_id"
                                class="mb-2 block text-sm font-medium text-white"
                            >
                                Assigned Developer
                            </label>
                            <select
                                id="assigned_developer_id"
                                v-model="form.assigned_developer_id"
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 px-4 text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                :class="{
                                    'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                        form.errors.assigned_developer_id,
                                }"
                            >
                                <option :value="null">Unassigned</option>
                                <option
                                    v-for="developer in developers"
                                    :key="developer.id"
                                    :value="developer.id"
                                >
                                    {{ developer.name }} ({{ developer.email }})
                                </option>
                            </select>
                            <p
                                v-if="form.errors.assigned_developer_id"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.assigned_developer_id }}
                            </p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label
                            for="notes"
                            class="mb-2 block text-sm font-medium text-white"
                        >
                            Notes
                        </label>
                        <div class="relative">
                            <DocumentTextIcon
                                class="absolute left-3 top-3 h-5 w-5 text-gray-400"
                            />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2 pl-10 pr-4 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                :class="{
                                    'border-red-500 focus:border-red-500 focus:ring-red-500/20':
                                        form.errors.notes,
                                }"
                                placeholder="Additional notes about the client..."
                            ></textarea>
                        </div>
                        <p
                            v-if="form.errors.notes"
                            class="mt-1 text-sm text-red-400"
                        >
                            {{ form.errors.notes }}
                        </p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end gap-4 border-t border-gray-700/60 pt-6">
                        <Link
                            :href="route('clients.index')"
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
                            <span v-else>Create Client</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

