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
 * @property {Object} data - Status code distribution data
 */
const props = defineProps({
  data: {
    type: Object,
    required: true
  }
})

/**
 * Chart data configuration
 */
const chartData = computed(() => ({
  labels: Object.keys(props.data || {}),
  datasets: [
    {
      label: 'Count',
      data: Object.values(props.data || {}),
      backgroundColor: (context) => {
        const code = Object.keys(props.data || {})[context.dataIndex]
        if (code.startsWith('2')) return 'rgba(34, 197, 94, 0.8)'
        if (code.startsWith('3')) return 'rgba(59, 130, 246, 0.8)'
        if (code.startsWith('4')) return 'rgba(234, 179, 8, 0.8)'
        if (code.startsWith('5')) return 'rgba(239, 68, 68, 0.8)'
        return 'rgba(156, 163, 175, 0.8)'
      },
      borderWidth: 0,
      borderRadius: 4
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
        color: 'rgb(156, 163, 175)'
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
