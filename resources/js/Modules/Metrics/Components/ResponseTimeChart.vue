<template>
  <div class="h-64">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

/**
 * Component props
 * @property {Object} data - Chart data with labels and values
 * @property {string} timeRange - Selected time range
 */
const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  timeRange: {
    type: String,
    default: '7d'
  }
})

/**
 * Chart data configuration
 */
const chartData = computed(() => ({
  labels: props.data.labels || [],
  datasets: [
    {
      label: 'Response Time (ms)',
      data: props.data.values || [],
      borderColor: 'rgb(59, 130, 246)',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      tension: 0.4,
      fill: true,
      pointRadius: 0,
      pointHoverRadius: 6,
      pointHoverBackgroundColor: 'rgb(59, 130, 246)',
      pointHoverBorderColor: 'rgb(255, 255, 255)',
      pointHoverBorderWidth: 2,
    }
  ]
}))

/**
 * Chart options configuration
 */
const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      backgroundColor: 'rgba(17, 24, 39, 0.9)',
      titleColor: 'rgb(243, 244, 246)',
      bodyColor: 'rgb(209, 213, 219)',
      borderColor: 'rgb(75, 85, 99)',
      borderWidth: 1,
      padding: 12,
      displayColors: false,
      callbacks: {
        label: (context) => `${context.parsed.y}ms`
      }
    }
  },
  scales: {
    x: {
      grid: {
        color: 'rgba(75, 85, 99, 0.2)',
        drawBorder: false
      },
      ticks: {
        color: 'rgb(156, 163, 175)',
        maxRotation: 0
      }
    },
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(75, 85, 99, 0.2)',
        drawBorder: false
      },
      ticks: {
        color: 'rgb(156, 163, 175)',
        callback: (value) => `${value}ms`
      }
    }
  },
  interaction: {
    mode: 'nearest',
    axis: 'x',
    intersect: false
  }
}))
</script>
