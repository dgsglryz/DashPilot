<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white">Revenue Overview</h1>
          <p class="text-gray-400 mt-1">Track revenue analytics across all Shopify and WooCommerce sites</p>
        </div>
      </div>

      <!-- Stats Overview -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-green-500/10 to-green-600/5 border border-green-500/20 rounded-xl p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm">Total Revenue</p>
              <p class="text-2xl font-bold text-white mt-1">${{ stats.totalRevenue.toLocaleString() }}</p>
            </div>
            <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center">
              <CurrencyDollarIcon class="w-6 h-6 text-green-400" />
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500/10 to-blue-600/5 border border-blue-500/20 rounded-xl p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm">Monthly Revenue</p>
              <p class="text-2xl font-bold text-white mt-1">${{ stats.monthlyRevenue.toLocaleString() }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-500/20 rounded-full flex items-center justify-center">
              <ChartBarIcon class="w-6 h-6 text-blue-400" />
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500/10 to-purple-600/5 border border-purple-500/20 rounded-xl p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm">Average per Site</p>
              <p class="text-2xl font-bold text-white mt-1">${{ stats.averageRevenue.toLocaleString() }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-500/20 rounded-full flex items-center justify-center">
              <BuildingStorefrontIcon class="w-6 h-6 text-purple-400" />
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500/10 to-yellow-600/5 border border-yellow-500/20 rounded-xl p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm">Growth</p>
              <p class="text-2xl font-bold text-white mt-1">+{{ stats.growth }}%</p>
            </div>
            <div class="w-12 h-12 bg-yellow-500/20 rounded-full flex items-center justify-center">
              <ArrowTrendingUpIcon class="w-6 h-6 text-yellow-400" />
            </div>
          </div>
        </div>
      </div>

      <!-- Monthly Trend Chart -->
      <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Monthly Revenue Trend</h3>
        <div class="h-64">
          <Line :data="chartData" :options="chartOptions" />
        </div>
      </div>

      <!-- Revenue by Site -->
      <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 overflow-hidden">
        <div class="p-6 border-b border-gray-700/50">
          <h3 class="text-lg font-semibold text-white">Revenue by Site</h3>
          <p class="text-sm text-gray-400 mt-1">{{ revenueBySite.length }} Shopify/WooCommerce sites</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-900/50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Site</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Revenue</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Orders</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Growth</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-700/50">
              <tr v-for="site in revenueBySite" :key="site.id" class="hover:bg-gray-700/20 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="relative">
                      <img :src="site.thumbnail" :alt="site.name" class="h-12 w-12 rounded-xl object-cover" />
                      <img :src="site.logo" :alt="`${site.name} logo`" class="absolute -bottom-1 -right-1 h-6 w-6 rounded-full border border-gray-900 bg-gray-900 object-cover" />
                    </div>
                    <div>
                      <p class="font-medium text-white">{{ site.name }}</p>
                      <p class="text-sm text-gray-400">{{ site.url }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="text-white font-semibold">${{ site.revenue.toLocaleString() }}</span>
                </td>
                <td class="px-6 py-4">
                  <span class="text-white">{{ site.orders }}</span>
                </td>
                <td class="px-6 py-4">
                  <span 
                    class="inline-flex items-center gap-1 text-sm font-medium"
                    :class="site.growth >= 0 ? 'text-green-400' : 'text-red-400'"
                  >
                    <ArrowTrendingUpIcon v-if="site.growth >= 0" class="w-4 h-4" />
                    <ArrowTrendingDownIcon v-else class="w-4 h-4" />
                    {{ Math.abs(site.growth) }}%
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end gap-2">
                    <Link :href="route('sites.show', site.id)" class="p-2 hover:bg-gray-700 rounded-lg transition-colors">
                      <EyeIcon class="w-4 h-4 text-gray-400" />
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
// @ts-nocheck
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Shared/Layouts/AppLayout.vue'
import { 
  CurrencyDollarIcon,
  ChartBarIcon,
  BuildingStorefrontIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  EyeIcon
} from '@heroicons/vue/24/outline'
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
import { Line } from 'vue-chartjs'
import type { ChartOptions } from 'chart.js'

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
 * Component props from Inertia
 */
const props = defineProps({
  stats: {
    type: Object,
    required: true
  },
  revenueBySite: {
    type: Array,
    required: true
  },
  monthlyTrend: {
    type: Array,
    required: true
  }
})

const chartData = computed(() => ({
  labels: props.monthlyTrend.map(item => item.month),
  datasets: [
    {
      label: 'Revenue',
      data: props.monthlyTrend.map(item => item.revenue),
      borderColor: 'rgb(34, 197, 94)',
      backgroundColor: 'rgba(34, 197, 94, 0.1)',
      fill: true,
      tension: 0.4,
    },
  ],
}))

const chartOptions: ChartOptions<'line'> = {
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
        callback: function(value) {
          return '$' + Number(value).toLocaleString()
        },
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
}
</script>

