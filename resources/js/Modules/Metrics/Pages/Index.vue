<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white">Metrics & Analytics</h1>
          <p class="text-gray-400 mt-1">Performance insights across all monitored sites</p>
        </div>
        <div class="flex items-center gap-3">
          <select v-model="timeRange" class="px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500">
            <option value="24h">Last 24 Hours</option>
            <option value="7d">Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
            <option value="90d">Last 90 Days</option>
          </select>
          <button @click="refreshMetrics" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center gap-2">
            <ArrowPathIcon class="w-4 h-4" />
            Refresh
          </button>
        </div>
      </div>

      <!-- Overview Stats -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <MetricSummaryCard 
          title="Avg Uptime"
          :value="`${metrics.averageUptime}%`"
          :trend="metrics.uptimeTrend"
          icon="ChartBarIcon"
          color="green"
        />
        <MetricSummaryCard 
          title="Avg Response Time"
          :value="`${metrics.averageResponseTime}ms`"
          :trend="metrics.responseTrend"
          icon="BoltIcon"
          color="blue"
        />
        <MetricSummaryCard 
          title="Total Requests"
          :value="formatNumber(metrics.totalRequests)"
          :trend="metrics.requestsTrend"
          icon="GlobeAltIcon"
          color="purple"
        />
        <MetricSummaryCard 
          title="Error Rate"
          :value="`${metrics.errorRate}%`"
          :trend="metrics.errorTrend"
          icon="ExclamationTriangleIcon"
          color="red"
          :inverse="true"
        />
      </div>

      <!-- Charts Row 1 -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Uptime Chart -->
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-white">Uptime Overview</h3>
            <div class="flex items-center gap-2 text-sm">
              <span class="w-3 h-3 bg-green-500 rounded-full"></span>
              <span class="text-gray-400">Healthy</span>
            </div>
          </div>
          <UptimeChart :data="metrics.uptimeHistory" :time-range="timeRange" />
        </div>

        <!-- Response Time Chart -->
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-white">Response Time Trends</h3>
            <div class="flex items-center gap-2 text-sm">
              <span class="text-gray-400">Average</span>
              <span class="text-white font-semibold">{{ metrics.averageResponseTime }}ms</span>
            </div>
          </div>
          <ResponseTimeChart :data="metrics.responseTimeHistory" :time-range="timeRange" />
        </div>
      </div>

      <!-- Charts Row 2 -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Traffic Chart -->
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-white">Traffic Analytics</h3>
            <select v-model="trafficMetric" class="px-3 py-1.5 bg-gray-900 border border-gray-700 rounded-lg text-sm text-white focus:outline-none">
              <option value="requests">Requests</option>
              <option value="bandwidth">Bandwidth</option>
              <option value="uniqueVisitors">Unique Visitors</option>
            </select>
          </div>
          <TrafficChart :data="metrics.trafficHistory" :metric="trafficMetric" :time-range="timeRange" />
        </div>

        <!-- Platform Distribution -->
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
          <h3 class="text-lg font-semibold text-white mb-6">Platform Distribution</h3>
          <PlatformPieChart :data="metrics.platformDistribution" />
        </div>
      </div>

      <!-- Top Performing Sites -->
      <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
        <h3 class="text-lg font-semibold text-white mb-6">Top Performing Sites</h3>
        <div class="space-y-4">
          <div 
            v-for="site in metrics.topSites" 
            :key="site.id"
            class="flex items-center justify-between p-4 bg-gray-900/50 rounded-lg hover:bg-gray-700/30 transition-colors"
          >
            <div class="flex items-center gap-4">
              <img :src="site.favicon" :alt="site.name" class="w-10 h-10 rounded" />
              <div>
                <Link :href="`/sites/${site.id}`" class="text-white font-medium hover:text-blue-400 transition-colors">
                  {{ site.name }}
                </Link>
                <p class="text-sm text-gray-400">{{ site.url }}</p>
              </div>
            </div>
            <div class="flex items-center gap-8">
              <div class="text-center">
                <p class="text-sm text-gray-400">Uptime</p>
                <p class="text-lg font-semibold text-green-400">{{ site.uptime }}%</p>
              </div>
              <div class="text-center">
                <p class="text-sm text-gray-400">Response</p>
                <p class="text-lg font-semibold text-blue-400">{{ site.responseTime }}ms</p>
              </div>
              <div class="text-center">
                <p class="text-sm text-gray-400">SEO</p>
                <p class="text-lg font-semibold text-purple-400">{{ site.seoScore }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error Analysis -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
          <h3 class="text-lg font-semibold text-white mb-6">Error Types</h3>
          <div v-if="metrics.errorTypes && metrics.errorTypes.length > 0" class="space-y-4">
            <div v-for="error in metrics.errorTypes" :key="error.type" class="space-y-2">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-3 h-3 rounded-full" :class="getErrorColor(error.type)"></div>
                  <span class="text-gray-300 font-medium">{{ error.label }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-white font-semibold">{{ error.count }}</span>
                  <span class="text-gray-500 text-sm">({{ error.percentage }}%)</span>
                </div>
              </div>
              <div class="w-full h-3 bg-gray-900 rounded-full overflow-hidden">
                <div 
                  class="h-full rounded-full transition-all duration-500"
                  :class="getErrorColor(error.type)"
                  :style="{ width: `${error.percentage}%` }"
                ></div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            <p>No errors detected in the selected time range</p>
          </div>
        </div>

        <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
          <h3 class="text-lg font-semibold text-white mb-6">Status Code Distribution</h3>
          <StatusCodeChart :data="metrics.statusCodes" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
// @ts-nocheck
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Shared/Layouts/AppLayout.vue'
import MetricSummaryCard from '@/Modules/Metrics/Components/MetricSummaryCard.vue'
import UptimeChart from '@/Modules/Metrics/Components/UptimeChart.vue'
import ResponseTimeChart from '@/Modules/Metrics/Components/ResponseTimeChart.vue'
import TrafficChart from '@/Modules/Metrics/Components/TrafficChart.vue'
import PlatformPieChart from '@/Modules/Metrics/Components/PlatformPieChart.vue'
import StatusCodeChart from '@/Modules/Metrics/Components/StatusCodeChart.vue'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'

/**
 * Component props from Inertia
 * @property {Object} metrics - Aggregated metrics data
 */
const props = defineProps({
  metrics: {
    type: Object,
    required: true
  }
})

const metrics = computed(() => props.metrics)

/**
 * Local reactive state
 */
const timeRange = ref('7d')
const trafficMetric = ref('requests')

/**
 * Refresh metrics data
 */
const refreshMetrics = () => {
  router.reload({ only: ['metrics'] })
}

/**
 * Format large numbers
 * @param {number} num - Number to format
 * @returns {string} Formatted number
 */
const formatNumber = (num) => {
  if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`
  if (num >= 1000) return `${(num / 1000).toFixed(1)}K`
  return num.toString()
}

/**
 * Get error type color
 * @param {string} type - Error type
 * @returns {string} Tailwind class
 */
const getErrorColor = (type) => {
  const colors = {
    '5xx': 'bg-red-500',
    '4xx': 'bg-yellow-500',
    'timeout': 'bg-orange-500',
    'ssl': 'bg-purple-500',
  }
  return colors[type] || 'bg-gray-500'
}

</script>
