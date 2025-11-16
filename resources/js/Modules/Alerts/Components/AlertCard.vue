<template>
  <div 
    class="bg-gray-800/50 backdrop-blur-sm rounded-xl border transition-all hover:border-gray-600"
    data-testid="alert-card"
    :class="[
      borderColor,
      alert.isRead ? 'opacity-60' : ''
    ]"
  >
    <div class="p-5">
      <div class="flex items-start gap-4">
        <!-- Severity Icon -->
        <div 
          class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
          :class="iconBg"
        >
          <component :is="severityIcon" class="w-5 h-5" :class="iconColor" />
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-4 mb-2">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <h3 class="text-white font-semibold">{{ alert.title }}</h3>
                <span 
                  v-if="!alert.isRead"
                  class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"
                ></span>
              </div>
              <p class="text-gray-400 text-sm">{{ alert.message }}</p>
            </div>
            
            <!-- Status Badge -->
            <span 
              class="px-2.5 py-1 rounded-full text-xs font-medium flex-shrink-0"
              :class="statusColor"
            >
              {{ alert.status.charAt(0).toUpperCase() + alert.status.slice(1) }}
            </span>
          </div>

          <!-- Meta Information -->
          <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 mb-3">
            <div class="flex items-center gap-1.5">
              <GlobeAltIcon class="w-4 h-4" />
              <button @click="$emit('view-site', alert.siteId)" class="hover:text-blue-400 transition-colors">
                {{ alert.siteName }}
              </button>
            </div>
            <div class="flex items-center gap-1.5">
              <TagIcon class="w-4 h-4" />
              <span>{{ alert.type }}</span>
            </div>
            <div class="flex items-center gap-1.5">
              <ClockIcon class="w-4 h-4" />
              <span>{{ formatRelativeTime(alert.createdAt) }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2">
            <button 
              v-if="alert.status === 'active'"
              @click="$emit('acknowledge', alert.id)"
              data-testid="acknowledge-alert-button"
              class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors"
            >
              Acknowledge
            </button>
            <button 
              v-if="alert.status !== 'resolved'"
              @click="$emit('resolve', alert.id)"
              data-testid="resolve-alert-button"
              class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors"
            >
              Resolve
            </button>
            <button class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-white text-sm rounded-lg transition-colors">
              View Details
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { computed } from 'vue'
import { 
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  GlobeAltIcon,
  TagIcon,
  ClockIcon
} from '@heroicons/vue/24/outline'

/**
 * Component props
 * @property {Object} alert - Alert object
 */
const props = defineProps({
  alert: {
    type: Object,
    required: true
  }
})

/**
 * Component emits
 */
defineEmits(['acknowledge', 'resolve', 'view-site'])

/**
 * Get severity icon component
 */
const severityIcon = computed(() => {
  const icons = {
    critical: ExclamationCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon
  }
  return icons[props.alert.severity] || InformationCircleIcon
})

/**
 * Border color based on severity
 */
const borderColor = computed(() => {
  const colors = {
    critical: 'border-red-500/30',
    warning: 'border-yellow-500/30',
    info: 'border-blue-500/30'
  }
  return colors[props.alert.severity] || 'border-gray-700/50'
})

/**
 * Icon background color
 */
const iconBg = computed(() => {
  const colors = {
    critical: 'bg-red-500/20',
    warning: 'bg-yellow-500/20',
    info: 'bg-blue-500/20'
  }
  return colors[props.alert.severity] || 'bg-gray-500/20'
})

/**
 * Icon color
 */
const iconColor = computed(() => {
  const colors = {
    critical: 'text-red-400',
    warning: 'text-yellow-400',
    info: 'text-blue-400'
  }
  return colors[props.alert.severity] || 'text-gray-400'
})

/**
 * Status badge color
 */
const statusColor = computed(() => {
  const colors = {
    active: 'bg-red-500/10 text-red-400',
    acknowledged: 'bg-yellow-500/10 text-yellow-400',
    resolved: 'bg-green-500/10 text-green-400'
  }
  return colors[props.alert.status] || 'bg-gray-500/10 text-gray-400'
})

/**
 * Format relative time
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
