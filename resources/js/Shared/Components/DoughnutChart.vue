<script setup lang="ts">
/**
 * DoughnutChart component renders a doughnut chart using Chart.js
 * for displaying categorical data (e.g., sites by status).
 */
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
} from 'chart.js';
import type { ChartData, ChartOptions } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

interface Props {
    data: Record<string, number>;
    labels?: Record<string, string>;
    colors?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    labels: () => ({
        healthy: 'Healthy',
        warning: 'Warning',
        critical: 'Critical',
        offline: 'Offline',
    }),
    colors: () => ({
        healthy: 'rgb(34, 197, 94)',
        warning: 'rgb(234, 179, 8)',
        critical: 'rgb(239, 68, 68)',
        offline: 'rgb(107, 114, 128)',
    }),
});

const chartData = computed<ChartData<'doughnut'>>(() => {
    const keys = Object.keys(props.data);
    return {
        labels: keys.map((key) => props.labels?.[key] || key),
        datasets: [
            {
                data: keys.map((key) => props.data[key]),
                backgroundColor: keys.map((key) => props.colors?.[key] || 'rgb(107, 114, 128)'),
                borderColor: 'rgb(17, 24, 39)',
                borderWidth: 2,
            },
        ],
    };
});

const chartOptions = computed<ChartOptions<'doughnut'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                color: 'rgb(156, 163, 175)',
                padding: 16,
                usePointStyle: true,
            },
        },
        tooltip: {
            backgroundColor: 'rgb(31, 41, 55)',
            titleColor: '#fff',
            bodyColor: 'rgb(209, 213, 219)',
            borderColor: 'rgb(75, 85, 99)',
            borderWidth: 1,
            padding: 12,
        },
    },
}));
</script>

<template>
    <div class="h-64">
        <Doughnut :data="chartData" :options="chartOptions" />
    </div>
</template>

