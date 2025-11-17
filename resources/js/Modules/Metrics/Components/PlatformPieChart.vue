<template>
  <div class="h-64">
    <Doughnut :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(
  ArcElement,
  Tooltip,
  Legend
)

/**
 * Component props
 * @property {Object} data - Platform distribution data
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
const chartData = computed(() => {
  const data = props.data || {}
  const wordpress = data.wordpress || 0
  const shopify = data.shopify || 0
  
  // If both are 0, show a single value to prevent empty chart
  const total = wordpress + shopify
  const wordpressValue = total > 0 ? wordpress : 1
  const shopifyValue = total > 0 ? shopify : 0
  
  return {
    labels: ['WordPress', 'Shopify'],
    datasets: [
      {
        data: [wordpressValue, shopifyValue],
        backgroundColor: [
          'rgba(59, 130, 246, 0.8)',
          'rgba(34, 197, 94, 0.8)'
        ],
        borderColor: [
          'rgb(59, 130, 246)',
          'rgb(34, 197, 94)'
        ],
        borderWidth: 2
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
      position: 'bottom',
      labels: {
        color: 'rgb(156, 163, 175)',
        padding: 15,
        font: {
          size: 12
        }
      }
    },
    tooltip: {
      backgroundColor: 'rgba(17, 24, 39, 0.9)',
      titleColor: 'rgb(243, 244, 246)',
      bodyColor: 'rgb(209, 213, 219)',
      borderColor: 'rgb(75, 85, 99)',
      borderWidth: 1,
      padding: 12
    }
  },
  cutout: '60%'
}))
</script>
