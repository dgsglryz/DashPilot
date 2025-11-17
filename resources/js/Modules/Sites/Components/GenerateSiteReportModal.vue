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
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/65 p-4"
        >
            <div class="w-full max-w-xl rounded-2xl border border-gray-700/70 bg-gray-900/95 p-6 shadow-2xl">
                <header class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-white">
                            Generate report for {{ site?.name }}
                        </h2>
                        <p class="text-sm text-gray-400">
                            Export uptime, SEO, and performance metrics without leaving this page.
                        </p>
                    </div>
                    <button
                        class="rounded-full p-2 text-gray-400 transition hover:bg-gray-800 hover:text-white"
                        @click="$emit('close')"
                        aria-label="Close generate report modal"
                    >
                        ✕
                    </button>
                </header>

                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="site-report-start" class="mb-1 block text-sm font-medium text-gray-300"
                                >Start date</label
                            >
                            <input
                                id="site-report-start"
                                v-model="form.startDate"
                                type="date"
                                required
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label for="site-report-end" class="mb-1 block text-sm font-medium text-gray-300"
                                >End date</label
                            >
                            <input
                                id="site-report-end"
                                v-model="form.endDate"
                                type="date"
                                required
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="site-report-template" class="mb-1 block text-sm font-medium text-gray-300"
                                >Template</label
                            >
                            <select
                                id="site-report-template"
                                v-model="form.templateId"
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
                            >
                                <option value="1">Performance summary</option>
                                <option value="2">Security audit</option>
                                <option value="3">Uptime report</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-300">Format</label>
                            <div class="flex flex-wrap gap-3">
                                <label class="flex items-center gap-2 text-sm text-gray-300">
                                    <input
                                        v-model="form.format"
                                        type="radio"
                                        value="pdf"
                                        class="text-blue-600 focus:ring-blue-500"
                                    />
                                    PDF
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-300">
                                    <input
                                        v-model="form.format"
                                        type="radio"
                                        value="csv"
                                        class="text-blue-600 focus:ring-blue-500"
                                    />
                                    CSV
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-300">
                                    <input
                                        v-model="form.format"
                                        type="radio"
                                        value="xlsx"
                                        class="text-blue-600 focus:ring-blue-500"
                                    />
                                    Excel
                                </label>
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
                            :disabled="loading"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span v-if="loading">Generating...</span>
                            <span v-else>Generate report</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
// @ts-nocheck
import { reactive, watch } from "vue";

/**
 * Lightweight modal to generate a single-site report.
 */
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    site: {
        type: Object,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "generate"]);

const today = () => {
    const date = new Date();
    return date.toISOString().slice(0, 10);
};

const thirtyDaysAgo = () => {
    const date = new Date();
    date.setDate(date.getDate() - 30);
    return date.toISOString().slice(0, 10);
};

const form = reactive({
    templateId: 1,
    format: "pdf",
    startDate: thirtyDaysAgo(),
    endDate: today(),
});

const resetRange = () => {
    form.startDate = thirtyDaysAgo();
    form.endDate = today();
    form.templateId = 1;
    form.format = "pdf";
};

const handleSubmit = () => {
    emit("generate", { ...form });
};

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            resetRange();
        }
    },
);
</script>



