<template>
    <div class="h-64">
        <Line
            v-if="hasData"
            :key="chartKey"
            :data="chartData"
            :options="chartOptions"
        />
        <div
            v-else
            class="flex items-center justify-center h-full text-gray-400"
        >
            No data available
        </div>
    </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { computed, ref, watch, onMounted, nextTick } from "vue";
import { Line } from "vue-chartjs";
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from "chart.js";

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
);

/**
 * Component props
 * @property {Object} data - Chart data with labels and values
 * @property {string} timeRange - Selected time range
 */
const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
    timeRange: {
        type: String,
        default: "7d",
    },
});

/**
 * Chart data configuration
 */
const chartData = computed(() => {
    const data = props.data || {};
    const labels = data.labels || [];
    const values = data.values || [];

    // Debug log
    console.log("UptimeChart - chartData computed:", {
        hasData: !!data,
        labelsCount: labels.length,
        valuesCount: values.length,
        labels: labels.slice(0, 3),
        values: values.slice(0, 3),
    });

    // Ensure labels and values arrays have the same length
    const normalizedValues = labels.map((_, index) => values[index] ?? 0);

    const result = {
        labels: labels.length > 0 ? labels : [],
        datasets: [
            {
                label: "Uptime %",
                data: normalizedValues.length > 0 ? normalizedValues : [],
                borderColor: "rgb(34, 197, 94)",
                backgroundColor: "rgba(34, 197, 94, 0.1)",
                tension: 0.4,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: "rgb(34, 197, 94)",
                pointHoverBorderColor: "rgb(255, 255, 255)",
                pointHoverBorderWidth: 2,
            },
        ],
    };

    console.log("UptimeChart - chartData result:", result);
    return result;
});

/**
 * Check if we have data to display
 */
const hasData = computed(() => {
    const data = props.data || {};
    const labels = data.labels || [];
    const values = data.values || [];
    return labels.length > 0 && values.length > 0;
});

// Force chart re-render when data changes
const chartKey = ref(0);
watch(
    () => props.data,
    async (newData) => {
        console.log("UptimeChart - data changed:", newData);
        await nextTick();
        chartKey.value++;
    },
    { deep: true, immediate: true },
);

onMounted(async () => {
    console.log("UptimeChart - mounted with data:", props.data);
    await nextTick();
    chartKey.value++;
});

/**
 * Chart options configuration
 */
const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            mode: "index",
            intersect: false,
            backgroundColor: "rgba(17, 24, 39, 0.9)",
            titleColor: "rgb(243, 244, 246)",
            bodyColor: "rgb(209, 213, 219)",
            borderColor: "rgb(75, 85, 99)",
            borderWidth: 1,
            padding: 12,
            displayColors: false,
            callbacks: {
                label: (context) => `${context.parsed.y.toFixed(2)}%`,
            },
        },
    },
    scales: {
        x: {
            grid: {
                color: "rgba(75, 85, 99, 0.2)",
                drawBorder: false,
            },
            ticks: {
                color: "rgb(156, 163, 175)",
                maxRotation: 0,
            },
        },
        y: {
            beginAtZero: false,
            min: 80,
            max: 100,
            grid: {
                color: "rgba(75, 85, 99, 0.2)",
                drawBorder: false,
            },
            ticks: {
                color: "rgb(156, 163, 175)",
                callback: (value) => `${value}%`,
            },
        },
    },
    interaction: {
        mode: "nearest",
        axis: "x",
        intersect: false,
    },
}));
</script>
