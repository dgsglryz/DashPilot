<template>
  <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-5">
    <div class="flex items-start justify-between mb-3">
      <div>
        <p class="text-gray-400 text-sm">{{ title }}</p>
        <p class="text-2xl font-bold text-white mt-1">{{ value }}</p>
      </div>
      <div 
        class="w-12 h-12 rounded-full flex items-center justify-center"
        :class="iconBgColor"
      >
        <component :is="iconComponent" class="w-6 h-6" :class="iconColor" />
      </div>
    </div>
    
    <div v-if="trend" class="flex items-center gap-1.5">
      <component 
        :is="trendIcon" 
        class="w-4 h-4"
        :class="trendColor"
      />
      <span class="text-sm font-medium" :class="trendColor">
        {{ Math.abs(trend) }}%
      </span>
      <span class="text-gray-500 text-xs">vs last period</span>
    </div>
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { computed } from 'vue'
import { 
  ChartBarIcon,
  BoltIcon,
  GlobeAltIcon,
  ExclamationTriangleIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon
} from '@heroicons/vue/24/outline'

/**
 * Component props
 * @property {string} title - Metric title
 * @property {string|number} value - Metric value
 * @property {number} trend - Trend percentage (positive or negative)
 * @property {string} icon - Icon name
 * @property {string} color - Color theme
 * @property {boolean} inverse - If true, negative trend is good
 */
const props = defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  trend: {
    type: Number,
    default: null
  },
  icon: {
    type: String,
    default: 'ChartBarIcon'
  },
  color: {
    type: String,
    default: 'blue'
  },
  inverse: {
    type: Boolean,
    default: false
  }
})

/**
 * Get icon component
 */
const iconComponent = computed(() => {
  const icons = {
    ChartBarIcon,
    BoltIcon,
    GlobeAltIcon,
    ExclamationTriangleIcon
  }
  return icons[props.icon] || ChartBarIcon
})

/**
 * Icon background color
 */
const iconBgColor = computed(() => {
  const colors = {
    green: 'bg-green-500/20',
    blue: 'bg-blue-500/20',
    purple: 'bg-purple-500/20',
    red: 'bg-red-500/20'
  }
  return colors[props.color] || 'bg-gray-500/20'
})

/**
 * Icon color
 */
const iconColor = computed(() => {
  const colors = {
    green: 'text-green-400',
    blue: 'text-blue-400',
    purple: 'text-purple-400',
    red: 'text-red-400'
  }
  return colors[props.color] || 'text-gray-400'
})

/**
 * Trend icon
 */
const trendIcon = computed(() => {
  if (!props.trend) return null
  return props.trend > 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon
})

/**
 * Trend color based on direction and inverse flag
 */
const trendColor = computed(() => {
  if (!props.trend) return 'text-gray-400'
  
  const isPositiveTrend = props.trend > 0
  const isGoodTrend = props.inverse ? !isPositiveTrend : isPositiveTrend
  
  return isGoodTrend ? 'text-green-400' : 'text-red-400'
})
</script>
