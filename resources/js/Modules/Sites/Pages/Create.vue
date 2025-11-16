<script setup lang="ts">
/**
 * Site Create Page - Form to create a new site.
 */
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Shared/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    GlobeAltIcon,
    BuildingOfficeIcon,
    ServerIcon,
    ExclamationCircleIcon,
} from '@heroicons/vue/24/outline';

type Client = {
    id: number;
    name: string;
    company: string;
};

defineProps<{
    clients: Client[];
}>();

const form = useForm({
    client_id: null as number | null,
    name: '',
    url: '',
    type: 'wordpress' as 'wordpress' | 'shopify' | 'woocommerce' | 'custom',
    status: 'healthy' as 'healthy' | 'warning' | 'critical' | 'offline',
    industry: null as string | null,
    region: null as string | null,
    wp_api_url: null as string | null,
    wp_api_key: null as string | null,
    shopify_store_url: null as string | null,
    shopify_api_key: null as string | null,
    shopify_access_token: null as string | null,
});

/**
 * Submit the form to create a new site
 */
const submit = (): void => {
    form.post(route('sites.store'), {
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
                    :href="route('sites.index')"
                    class="rounded-lg border border-gray-700/60 p-2 text-gray-400 transition-colors hover:border-white/60 hover:text-white"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-3xl font-bold text-white">Create Site</h1>
                    <p class="mt-1 text-sm text-gray-400">
                        Add a new site to monitor and manage
                    </p>
                </div>
            </div>

            <!-- Form -->
            <form
                @submit.prevent="submit"
                class="rounded-2xl border border-gray-700/70 bg-gray-900/60 p-6"
            >
                <div class="space-y-6">
                    <!-- Client & Name -->
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label
                                for="client_id"
                                class="mb-2 block text-sm font-medium text-gray-300"
                            >
                                <BuildingOfficeIcon
                                    class="mb-1 inline h-4 w-4"
                                />
                                Client *
                            </label>
                            <select
                                id="client_id"
                                v-model="form.client_id"
                                required
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white placeholder:text-gray-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :class="{
                                    'border-red-500': form.errors.client_id,
                                }"
                            >
                                <option value="">Select a client</option>
                                <option
                                    v-for="client in clients"
                                    :key="client.id"
                                    :value="client.id"
                                >
                                    {{ client.name }} ({{ client.company }})
                                </option>
                            </select>
                            <p
                                v-if="form.errors.client_id"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.client_id }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="name"
                                class="mb-2 block text-sm font-medium text-gray-300"
                            >
                                Site Name *
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white placeholder:text-gray-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :class="{ 'border-red-500': form.errors.name }"
                                placeholder="Example Site"
                            />
                            <p
                                v-if="form.errors.name"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>
                    </div>

                    <!-- URL & Type -->
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label
                                for="url"
                                class="mb-2 block text-sm font-medium text-gray-300"
                            >
                                <GlobeAltIcon class="mb-1 inline h-4 w-4" />
                                URL *
                            </label>
                            <input
                                id="url"
                                v-model="form.url"
                                type="url"
                                required
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white placeholder:text-gray-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :class="{ 'border-red-500': form.errors.url }"
                                placeholder="https://example.com"
                            />
                            <p
                                v-if="form.errors.url"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.url }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="type"
                                class="mb-2 block text-sm font-medium text-gray-300"
                            >
                                <ServerIcon class="mb-1 inline h-4 w-4" />
                                Platform Type *
                            </label>
                            <select
                                id="type"
                                v-model="form.type"
                                required
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :class="{ 'border-red-500': form.errors.type }"
                            >
                                <option value="wordpress">WordPress</option>
                                <option value="shopify">Shopify</option>
                                <option value="woocommerce">WooCommerce</option>
                                <option value="custom">Custom</option>
                            </select>
                            <p
                                v-if="form.errors.type"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.type }}
                            </p>
                        </div>
                    </div>

                    <!-- Status & Industry -->
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label
                                for="status"
                                class="mb-2 block text-sm font-medium text-gray-300"
                            >
                                <ExclamationCircleIcon
                                    class="mb-1 inline h-4 w-4"
                                />
                                Status *
                            </label>
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="healthy">Healthy</option>
                                <option value="warning">Warning</option>
                                <option value="critical">Critical</option>
                                <option value="offline">Offline</option>
                            </select>
                        </div>

                        <div>
                            <label
                                for="industry"
                                class="mb-2 block text-sm font-medium text-gray-300"
                            >
                                Industry
                            </label>
                            <input
                                id="industry"
                                v-model="form.industry"
                                type="text"
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white placeholder:text-gray-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="E-commerce & Retail"
                            />
                        </div>
                    </div>

                    <!-- Region -->
                    <div>
                        <label
                            for="region"
                            class="mb-2 block text-sm font-medium text-gray-300"
                        >
                            Region
                        </label>
                        <input
                            id="region"
                            v-model="form.region"
                            type="text"
                            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white placeholder:text-gray-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="North America"
                        />
                    </div>

                    <!-- WordPress API (conditional) -->
                    <div
                        v-if="form.type === 'wordpress' || form.type === 'woocommerce'"
                        class="grid gap-6 md:grid-cols-2"
                    >
                        <div>
                            <label
                                for="wp_api_url"
                                class="mb-2 block text-sm font-medium text-gray-300"
                            >
                                WordPress API URL
                            </label>
                            <input
                                id="wp_api_url"
                                v-model="form.wp_api_url"
                                type="url"
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white placeholder:text-gray-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="https://example.com/wp-json/wp/v2"
                            />
                        </div>

                        <div>
                            <label
                                for="wp_api_key"
                                class="mb-2 block text-sm font-medium text-gray-300"
                            >
                                WordPress API Key
                            </label>
                            <input
                                id="wp_api_key"
                                v-model="form.wp_api_key"
                                type="text"
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white placeholder:text-gray-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="API key (optional)"
                            />
                        </div>
                    </div>

                    <!-- Shopify API (conditional) -->
                    <div
                        v-if="form.type === 'shopify'"
                        class="grid gap-6 md:grid-cols-3"
                    >
                        <div>
                            <label
                                for="shopify_store_url"
                                class="mb-2 block text-sm font-medium text-gray-300"
                            >
                                Shopify Store URL
                            </label>
                            <input
                                id="shopify_store_url"
                                v-model="form.shopify_store_url"
                                type="url"
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white placeholder:text-gray-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="https://store.myshopify.com"
                            />
                        </div>

                        <div>
                            <label
                                for="shopify_api_key"
                                class="mb-2 block text-sm font-medium text-gray-300"
                            >
                                Shopify API Key
                            </label>
                            <input
                                id="shopify_api_key"
                                v-model="form.shopify_api_key"
                                type="text"
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white placeholder:text-gray-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="API key"
                            />
                        </div>

                        <div>
                            <label
                                for="shopify_access_token"
                                class="mb-2 block text-sm font-medium text-gray-300"
                            >
                                Shopify Access Token
                            </label>
                            <input
                                id="shopify_access_token"
                                v-model="form.shopify_access_token"
                                type="text"
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white placeholder:text-gray-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Access token"
                            />
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end gap-4 pt-4">
                        <Link
                            :href="route('sites.index')"
                            class="rounded-lg border border-gray-700/60 px-4 py-2 text-gray-300 transition-colors hover:border-white/60 hover:text-white"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">Creating...</span>
                            <span v-else>Create Site</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

