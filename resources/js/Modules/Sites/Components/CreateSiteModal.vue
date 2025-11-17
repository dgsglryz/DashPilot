<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
            role="dialog"
            aria-modal="true"
        >
            <div
                class="w-full max-w-2xl rounded-2xl border border-gray-700/60 bg-gray-900/90 p-6 shadow-2xl backdrop-blur"
            >
                <header class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-white">
                            Add a new site
                        </h2>
                        <p class="text-sm text-gray-400">
                            Track uptime, SEO, and performance instantly
                        </p>
                    </div>
                    <button
                        class="rounded-full p-2 text-gray-400 transition hover:bg-gray-800 hover:text-white"
                        @click="$emit('close')"
                        aria-label="Close create site modal"
                    >
                        ✕
                    </button>
                </header>

                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label
                                for="quick-site-client"
                                class="mb-1 block text-sm font-medium text-gray-300"
                                >Client *</label
                            >
                            <select
                                id="quick-site-client"
                                v-model="form.client_id"
                                required
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
                                :class="{ 'border-red-500': form.errors.client_id }"
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
                                for="quick-site-name"
                                class="mb-1 block text-sm font-medium text-gray-300"
                                >Site name *</label
                            >
                            <input
                                id="quick-site-name"
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Acme Storefront"
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <p
                                v-if="form.errors.name"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label
                                for="quick-site-url"
                                class="mb-1 block text-sm font-medium text-gray-300"
                                >URL *</label
                            >
                            <input
                                id="quick-site-url"
                                v-model="form.url"
                                type="url"
                                required
                                placeholder="https://example.com"
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
                                :class="{ 'border-red-500': form.errors.url }"
                            />
                            <p
                                v-if="form.errors.url"
                                class="mt-1 text-sm text-red-400"
                            >
                                {{ form.errors.url }}
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    for="quick-site-platform"
                                    class="mb-1 block text-sm font-medium text-gray-300"
                                    >Platform *</label
                                >
                                <select
                                    id="quick-site-platform"
                                    v-model="form.type"
                                    required
                                    class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
                                >
                                    <option value="wordpress">WordPress</option>
                                    <option value="shopify">Shopify</option>
                                    <option value="woocommerce">
                                        WooCommerce
                                    </option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                            <div>
                                <label
                                    for="quick-site-status"
                                    class="mb-1 block text-sm font-medium text-gray-300"
                                    >Status *</label
                                >
                                <select
                                    id="quick-site-status"
                                    v-model="form.status"
                                    required
                                    class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
                                >
                                    <option value="healthy">Healthy</option>
                                    <option value="warning">Warning</option>
                                    <option value="critical">Critical</option>
                                    <option value="offline">Offline</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            class="rounded-lg px-4 py-2 text-sm text-gray-300 transition hover:text-white"
                            @click="$emit('close')"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>Create site</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
// @ts-nocheck
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";

/**
 * Quick create modal for sites on the index page.
 */
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    clients: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "created"]);

const form = useForm({
    client_id: "",
    name: "",
    url: "",
    type: "wordpress",
    status: "healthy",
});

const resetForm = () => {
    form.clearErrors();
    form.reset();
};

const handleSubmit = () => {
    form.post(route("sites.store"), {
        preserveScroll: true,
        onSuccess: () => {
            emit("created");
            resetForm();
        },
    });
};

watch(
    () => props.show,
    (visible) => {
        if (!visible) {
            resetForm();
        }
    },
);
</script>


