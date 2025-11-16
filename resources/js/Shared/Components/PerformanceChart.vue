<script setup lang="ts">
/**
 * PerformanceChart renders the combined uptime/response-time line chart
 * using Chart.js via vue-chartjs.
 */
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LineElement,
    LinearScale,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import type { ChartData, ChartOptions } from 'chart.js';

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

const props = defineProps<{
    series?: ChartData<'line'>;
}>();

const defaultSeries: ChartData<'line'> = {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [
        {
            label: 'Uptime %',
            data: [99.2, 99.5, 99.8, 98.5, 99.9, 99.7, 99.5],
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.15)',
            fill: true,
            tension: 0.4,
        },
        {
            label: 'Response Time (ms)',
            data: [120, 135, 110, 145, 105, 115, 125],
            borderColor: 'rgb(168, 85, 247)',
            backgroundColor: 'rgba(168, 85, 247, 0.15)',
            fill: true,
            tension: 0.4,
        },
    ],
};

const chartData = computed<ChartData<'line'>>(() => props.series ?? defaultSeries);

const chartOptions = computed<ChartOptions<'line'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'bottom',
            labels: {
                color: 'rgb(156, 163, 175)',
                padding: 16,
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
    scales: {
        y: {
            beginAtZero: false,
            grid: {
                color: 'rgba(75, 85, 99, 0.3)',
            },
            ticks: {
                color: 'rgb(156, 163, 175)',
            },
        },
        x: {
            grid: {
                color: 'rgba(75, 85, 99, 0.3)',
            },
            ticks: {
                color: 'rgb(156, 163, 175)',
            },
        },
    },
}));
</script>

<template>
    <div class="h-64">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>

