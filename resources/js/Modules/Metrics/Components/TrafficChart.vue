<template>
  <div class="h-64">
    <Bar :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
)

/**
 * Component props
 * @property {Object} data - Chart data
 * @property {string} metric - Selected metric type
 * @property {string} timeRange - Selected time range
 */
const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  metric: {
    type: String,
    default: 'requests'
  },
  timeRange: {
    type: String,
    default: '7d'
  }
})

/**
 * Get metric label
 */
const metricLabel = computed(() => {
  const labels = {
    requests: 'Requests',
    bandwidth: 'Bandwidth (GB)',
    uniqueVisitors: 'Unique Visitors'
  }
  return labels[props.metric] || 'Requests'
})

/**
 * Chart data configuration
 */
const chartData = computed(() => {
  const data = props.data || {}
  const labels = data.labels || []
  const metricData = data[props.metric] || []
  
  // Ensure labels and data arrays have the same length
  const normalizedData = labels.map((_, index) => metricData[index] ?? 0)
  
  return {
    labels: labels.length > 0 ? labels : [],
    datasets: [
      {
        label: metricLabel.value,
        data: normalizedData.length > 0 ? normalizedData : [],
        backgroundColor: 'rgba(147, 51, 234, 0.8)',
        borderColor: 'rgb(147, 51, 234)',
        borderWidth: 0,
        borderRadius: 4,
      }
    ]
  }
})

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
      displayColors: false
    }
  },
  scales: {
    x: {
      grid: {
        display: false,
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
        color: 'rgb(156, 163, 175)'
      }
    }
  }
}))
</script>
