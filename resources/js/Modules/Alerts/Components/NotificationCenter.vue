<template>
  <div class="relative" v-click-outside="closeDropdown">
    <!-- Bell Icon with Badge -->
    <button 
      @click="toggleDropdown"
      class="relative p-2 hover:bg-gray-700 rounded-lg transition-colors"
    >
      <BellIcon class="w-6 h-6 text-gray-400" />
      <span 
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div 
        v-if="isOpen"
        class="absolute right-0 mt-2 w-96 bg-gray-800 rounded-xl border border-gray-700 shadow-2xl z-50"
      >
        <!-- Header -->
        <div class="p-4 border-b border-gray-700">
          <div class="flex items-center justify-between">
            <h3 class="text-white font-semibold">Notifications</h3>
            <button 
              @click="markAllAsRead"
              class="text-xs text-blue-400 hover:text-blue-300 transition-colors"
            >
              Mark all as read
            </button>
          </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
          <div v-if="notifications.length === 0" class="p-8 text-center">
            <BellSlashIcon class="w-12 h-12 text-gray-600 mx-auto mb-2" />
            <p class="text-gray-400 text-sm">No notifications</p>
          </div>
          
          <button
            v-for="notification in notifications"
            :key="notification.id"
            @click="handleNotificationClick(notification)"
            class="w-full p-4 border-b border-gray-700/50 hover:bg-gray-700/30 transition-colors text-left"
            :class="{ 'bg-gray-700/20': !notification.isRead }"
          >
            <div class="flex items-start gap-3">
              <div 
                class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                :class="getNotificationIconBg(notification.severity)"
              >
                <component 
                  :is="getNotificationIcon(notification.severity)" 
                  class="w-4 h-4"
                  :class="getNotificationIconColor(notification.severity)"
                />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-medium mb-1">{{ notification.title }}</p>
                <p class="text-gray-400 text-xs line-clamp-2">{{ notification.message }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ formatRelativeTime(notification.createdAt) }}</p>
              </div>
              <span 
                v-if="!notification.isRead"
                class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-2"
              ></span>
            </div>
          </button>
        </div>

        <!-- Footer -->
        <div class="p-3 border-t border-gray-700">
          <Link 
            href="/alerts"
            class="block text-center text-sm text-blue-400 hover:text-blue-300 transition-colors"
          >
            View all alerts
          </Link>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { 
  BellIcon, 
  BellSlashIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline'

/**
 * Component props
 * @property {Array} notifications - Recent notifications
 */
const props = defineProps({
  notifications: {
    type: Array,
    default: () => []
  }
})

/**
 * Local state
 */
const isOpen = ref(false)

/**
 * Unread notification count
 */
const unreadCount = computed(() => {
  return props.notifications.filter(n => !n.isRead).length
})

/**
 * Toggle dropdown visibility
 */
const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

/**
 * Close dropdown
 */
const closeDropdown = () => {
  isOpen.value = false
}

/**
 * Mark all notifications as read
 */
const markAllAsRead = () => {
  router.post('/notifications/mark-all-read')
  closeDropdown()
}

/**
 * Handle notification click
 * @param {Object} notification - Notification object
 */
const handleNotificationClick = (notification) => {
  if (!notification.isRead) {
    router.post(`/notifications/${notification.id}/read`)
  }
  if (notification.url) {
    router.visit(notification.url)
  }
  closeDropdown()
}

/**
 * Get notification icon based on severity
 * @param {string} severity - Notification severity
 * @returns {Component} Icon component
 */
const getNotificationIcon = (severity) => {
  const icons = {
    critical: ExclamationCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon
  }
  return icons[severity] || InformationCircleIcon
}

/**
 * Get notification icon background color
 * @param {string} severity - Notification severity
 * @returns {string} Tailwind class
 */
const getNotificationIconBg = (severity) => {
  const colors = {
    critical: 'bg-red-500/20',
    warning: 'bg-yellow-500/20',
    info: 'bg-blue-500/20'
  }
  return colors[severity] || 'bg-gray-500/20'
}

/**
 * Get notification icon color
 * @param {string} severity - Notification severity
 * @returns {string} Tailwind class
 */
const getNotificationIconColor = (severity) => {
  const colors = {
    critical: 'text-red-400',
    warning: 'text-yellow-400',
    info: 'text-blue-400'
  }
  return colors[severity] || 'text-gray-400'
}

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

/**
 * Click outside directive
 */
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value()
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent)
  }
}
</script>
