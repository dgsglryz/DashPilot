<script setup lang="ts">
/**
 * BarChart component renders a bar chart using Chart.js
 * for displaying time-series or categorical data.
 */
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    CategoryScale,
    Chart as ChartJS,
    LinearScale,
    BarElement,
    Tooltip,
    Legend,
} from 'chart.js';
import type { ChartData, ChartOptions } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend);

interface DataPoint {
    date: string;
    count: number;
}

interface Props {
    data: DataPoint[];
    label?: string;
    color?: string;
}

const props = withDefaults(defineProps<Props>(), {
    label: 'Count',
    color: 'rgb(59, 130, 246)',
});

const chartData = computed<ChartData<'bar'>>(() => ({
    labels: props.data.map((d) => d.date),
    datasets: [
        {
            label: props.label,
            data: props.data.map((d) => d.count),
            backgroundColor: props.color,
            borderColor: props.color,
            borderWidth: 1,
        },
    ],
}));

const chartOptions = computed<ChartOptions<'bar'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
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
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(75, 85, 99, 0.3)',
            },
            ticks: {
                color: 'rgb(156, 163, 175)',
            },
        },
        x: {
            grid: {
                display: false,
            },
            ticks: {
                color: 'rgb(156, 163, 175)',
                maxRotation: 45,
                minRotation: 45,
            },
        },
    },
}));
</script>

<template>
    <div class="h-64">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>

