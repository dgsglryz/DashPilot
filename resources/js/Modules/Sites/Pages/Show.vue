<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link href="/sites" class="p-2 hover:bg-gray-700 rounded-lg transition-colors">
          <ArrowLeftIcon class="w-5 h-5 text-gray-400" />
        </Link>
        <div class="flex-1">
          <div class="flex items-center gap-3">
            <img :src="site.favicon" :alt="site.name" class="w-10 h-10 rounded" />
            <div>
              <h1 class="text-2xl font-bold text-white">{{ site.name }}</h1>
              <a :href="site.url" target="_blank" class="text-sm text-gray-400 hover:text-blue-400 transition-colors">
                {{ site.url }}
              </a>
            </div>
          </div>
        </div>
        <span 
          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium"
          :class="{
            'bg-green-500/10 text-green-400': site.status === 'healthy',
            'bg-yellow-500/10 text-yellow-400': site.status === 'warning',
            'bg-red-500/10 text-red-400': site.status === 'critical'
          }"
        >
          <span 
            class="w-2 h-2 rounded-full animate-pulse" 
            :class="{
              'bg-green-400': site.status === 'healthy',
              'bg-yellow-400': site.status === 'warning',
              'bg-red-400': site.status === 'critical'
            }"
          ></span>
          {{ site.status.charAt(0).toUpperCase() + site.status.slice(1) }}
        </span>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard 
          title="Uptime" 
          :value="`${site.uptime}%`"
          :trend="site.uptimeTrend"
          icon="ChartBarIcon"
          color="green"
        />
        <StatCard 
          title="Response Time" 
          :value="`${site.responseTime}ms`"
          :trend="site.responseTimeTrend"
          icon="BoltIcon"
          color="blue"
        />
        <StatCard 
          title="SEO Score" 
          :value="site.seoScore"
          :trend="site.seoTrend"
          icon="MagnifyingGlassIcon"
          color="purple"
        />
        <StatCard 
          title="Last Checked" 
          :value="formatRelativeTime(site.lastChecked)"
          icon="ClockIcon"
          color="gray"
        />
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-700">
        <nav class="flex gap-6">
          <button 
            v-for="tab in tabs" 
            :key="tab.id"
            @click="activeTab = tab.id"
            class="py-3 px-1 border-b-2 font-medium text-sm transition-colors"
            :class="activeTab === tab.id 
              ? 'border-blue-500 text-blue-400' 
              : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-300'"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div v-if="activeTab === 'overview'" class="space-y-6">
        <!-- Performance Chart -->
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
          <h3 class="text-lg font-semibold text-white mb-4">Performance History</h3>
          <PerformanceChart :data="site.performanceHistory" />
        </div>

        <!-- Site Info -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Site Information</h3>
            <dl class="space-y-3">
              <div class="flex justify-between">
                <dt class="text-gray-400">Platform</dt>
                <dd class="text-white font-medium">{{ site.platform === 'wordpress' ? 'WordPress' : 'Shopify' }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-400">Version</dt>
                <dd class="text-white font-medium">{{ site.version }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-400">PHP Version</dt>
                <dd class="text-white font-medium">{{ site.phpVersion }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-400">SSL Certificate</dt>
                <dd class="text-white font-medium flex items-center gap-2">
                  <CheckCircleIcon class="w-4 h-4 text-green-400" />
                  Valid
                </dd>
              </div>
            </dl>
          </div>

          <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Monitoring Settings</h3>
            <dl class="space-y-3">
              <div class="flex justify-between">
                <dt class="text-gray-400">Check Interval</dt>
                <dd class="text-white font-medium">5 minutes</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-400">Notifications</dt>
                <dd class="text-white font-medium">Enabled</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-400">Alert Threshold</dt>
                <dd class="text-white font-medium">95% uptime</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-400">Added</dt>
                <dd class="text-white font-medium">{{ new Date(site.createdAt).toLocaleDateString() }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- WordPress/Shopify Specific Tab -->
      <div v-if="activeTab === 'details'" class="space-y-6">
        <div v-if="site.platform === 'wordpress'" class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
          <h3 class="text-lg font-semibold text-white mb-4">WordPress Details</h3>
          <div class="space-y-4">
            <div>
              <h4 class="text-sm font-medium text-gray-400 mb-2">Active Plugins ({{ site.plugins?.length }})</h4>
              <div class="space-y-2">
                <div v-for="plugin in site.plugins" :key="plugin.name" class="flex items-center justify-between p-3 bg-gray-900/50 rounded-lg">
                  <span class="text-white">{{ plugin.name }}</span>
                  <span class="text-sm text-gray-400">v{{ plugin.version }}</span>
                </div>
              </div>
            </div>
            <div>
              <h4 class="text-sm font-medium text-gray-400 mb-2">Active Theme</h4>
              <div class="flex items-center justify-between p-3 bg-gray-900/50 rounded-lg">
                <span class="text-white">{{ site.theme?.name }}</span>
                <span class="text-sm text-gray-400">v{{ site.theme?.version }}</span>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
          <h3 class="text-lg font-semibold text-white mb-4">Shopify Details</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-gray-900/50 rounded-lg">
              <p class="text-gray-400 text-sm">Total Products</p>
              <p class="text-2xl font-bold text-white mt-1">{{ site.shopifyStats?.totalProducts }}</p>
            </div>
            <div class="p-4 bg-gray-900/50 rounded-lg">
              <p class="text-gray-400 text-sm">Orders (30d)</p>
              <p class="text-2xl font-bold text-white mt-1">{{ site.shopifyStats?.orders30d }}</p>
            </div>
            <div class="p-4 bg-gray-900/50 rounded-lg">
              <p class="text-gray-400 text-sm">Revenue (30d)</p>
              <p class="text-2xl font-bold text-white mt-1">${{ site.shopifyStats?.revenue30d?.toLocaleString() }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- SEO Tab -->
      <div v-if="activeTab === 'seo'" class="space-y-6">
        <SEOScoreCard :site="site" />
      </div>

      <!-- Alerts Tab -->
      <div v-if="activeTab === 'alerts'" class="space-y-4">
        <AlertCard 
          v-for="alert in site.alerts" 
          :key="alert.id"
          :alert="alert"
        />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Shared/Layouts/AppLayout.vue'
import StatCard from '@/Shared/Components/StatCard.vue'
import PerformanceChart from '@/Shared/Components/PerformanceChart.vue'
import SEOScoreCard from '@/Modules/Sites/Components/SEOScoreCard.vue'
import AlertCard from '@/Modules/Alerts/Components/AlertCard.vue'
import { ArrowLeftIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'

/**
 * Component props from Inertia
 * @property {Object} site - Detailed site information
 */
const props = defineProps({
  site: {
    type: Object,
    required: true
  }
})

/**
 * Tab navigation
 */
const activeTab = ref('overview')
const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'details', label: props.site.platform === 'wordpress' ? 'WordPress' : 'Shopify' },
  { id: 'seo', label: 'SEO Analysis' },
  { id: 'alerts', label: 'Alerts History' }
]

/**
 * Format relative time from timestamp
 * @param {string} timestamp - ISO timestamp
 * @returns {string} Formatted relative time
 */
const formatRelativeTime = (timestamp) => {
  const now = new Date()
  const past = new Date(timestamp)
  const diffMs = now - past
  const diffMins = Math.floor(diffMs / 60000)
  
  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  
  const diffHours = Math.floor(diffMins / 60)
  if (diffHours < 24) return `${diffHours}h ago`
  
  const diffDays = Math.floor(diffHours / 24)
  return `${diffDays}d ago`
}
</script>
