<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white">Recent Activities</h1>
          <p class="text-gray-400 mt-1">Monitor all activity logs across your managed sites</p>
        </div>
      </div>

      <!-- Stats Overview -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-500/10 to-blue-600/5 border border-blue-500/20 rounded-xl p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm">Total Activities</p>
              <p class="text-2xl font-bold text-white mt-1">{{ stats.total.toLocaleString() }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-500/20 rounded-full flex items-center justify-center">
              <ClockIcon class="w-6 h-6 text-blue-400" />
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-green-500/10 to-green-600/5 border border-green-500/20 rounded-xl p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm">Today</p>
              <p class="text-2xl font-bold text-white mt-1">{{ stats.today }}</p>
            </div>
            <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center">
              <CalendarIcon class="w-6 h-6 text-green-400" />
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500/10 to-purple-600/5 border border-purple-500/20 rounded-xl p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm">This Week</p>
              <p class="text-2xl font-bold text-white mt-1">{{ stats.thisWeek }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-500/20 rounded-full flex items-center justify-center">
              <ChartBarIcon class="w-6 h-6 text-purple-400" />
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500/10 to-yellow-600/5 border border-yellow-500/20 rounded-xl p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm">This Month</p>
              <p class="text-2xl font-bold text-white mt-1">{{ stats.thisMonth }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-500/20 rounded-full flex items-center justify-center">
              <CalendarDaysIcon class="w-6 h-6 text-yellow-400" />
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl p-4 border border-gray-700/50">
        <div class="flex flex-col lg:flex-row gap-4">
          <div class="flex-1 relative">
            <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Search activities..."
              class="w-full pl-10 pr-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
            />
          </div>
          
          <div class="flex gap-2">
            <select v-model="filterSite" class="px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500">
              <option value="">All Sites</option>
              <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
            </select>
            
            <select v-model="filterAction" class="px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500">
              <option value="">All Actions</option>
              <option value="Updated">Updated</option>
              <option value="Synced">Synced</option>
              <option value="Optimised">Optimised</option>
              <option value="Cleared">Cleared</option>
              <option value="Checked">Checked</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Activities List -->
      <div class="space-y-3">
        <div 
          v-for="activity in filteredActivities" 
          :key="activity.id"
          class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-5 hover:border-gray-600 transition-colors"
        >
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center flex-shrink-0">
              <BoltIcon class="w-5 h-5 text-blue-400" />
            </div>
            
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-4 mb-2">
                <div class="flex-1">
                  <h3 class="text-white font-semibold">{{ activity.action }}</h3>
                  <p class="text-gray-400 text-sm mt-1">{{ activity.description }}</p>
                </div>
                <span class="text-xs text-gray-500 flex-shrink-0">{{ formatRelativeTime(activity.timestamp) }}</span>
              </div>

              <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 mt-3">
                <div v-if="activity.site" class="flex items-center gap-1.5">
                  <GlobeAltIcon class="w-4 h-4" />
                  <Link :href="route('sites.show', activity.site.id)" class="hover:text-blue-400 transition-colors">
                    {{ activity.site.name }}
                  </Link>
                </div>
                <div v-if="activity.user" class="flex items-center gap-1.5">
                  <UserIcon class="w-4 h-4" />
                  <span>{{ activity.user.name }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="filteredActivities.length === 0" class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-12 text-center">
          <BoltSlashIcon class="w-16 h-16 text-gray-600 mx-auto mb-4" />
          <p class="text-gray-400 text-lg">No activities found</p>
          <p class="text-gray-500 text-sm mt-1">Try adjusting your filters</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="activities.links && activities.links.length > 3" class="flex justify-center">
        <div class="flex gap-2">
          <a
            v-for="link in activities.links"
            :key="link.label"
            :href="link.url || '#'"
            class="px-4 py-2 rounded-lg border transition-colors"
            :class="link.active 
              ? 'bg-blue-600 border-blue-600 text-white' 
              : 'bg-gray-800 border-gray-700 text-gray-300 hover:bg-gray-700'"
          >
            <span v-if="link.label.includes('Previous')">‹ Previous</span>
            <span v-else-if="link.label.includes('Next')">Next ›</span>
            <span v-else>{{ link.label }}</span>
          </a>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Shared/Layouts/AppLayout.vue'
import { 
  MagnifyingGlassIcon,
  ClockIcon,
  CalendarIcon,
  ChartBarIcon,
  CalendarDaysIcon,
  BoltIcon,
  BoltSlashIcon,
  GlobeAltIcon,
  UserIcon
} from '@heroicons/vue/24/outline'

/**
 * Component props from Inertia
 */
const props = defineProps({
  activities: {
    type: Object,
    required: true
  },
  stats: {
    type: Object,
    required: true
  },
  sites: {
    type: Array,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

/**
 * Local reactive state
 */
const searchQuery = ref('')
const filterSite = ref(props.filters?.site_id || '')
const filterAction = ref(props.filters?.action || '')

/**
 * Computed filtered activities
 */
const filteredActivities = computed(() => {
  const items = props.activities.data || []
  
  return items.filter(activity => {
    const matchesSearch = !searchQuery.value || 
      activity.action.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      activity.description.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesSite = !filterSite.value || activity.site?.id === parseInt(filterSite.value)
    const matchesAction = !filterAction.value || activity.action.includes(filterAction.value)
    
    return matchesSearch && matchesSite && matchesAction
  })
})

/**
 * Format relative time from timestamp
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
  if (diffDays < 7) return `${diffDays}d ago`
  
  return new Date(timestamp).toLocaleDateString()
}
</script>

