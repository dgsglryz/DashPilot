<template>
  <Transition
    enter-active-class="transition-all duration-300 ease-out"
    enter-from-class="opacity-0 translate-y-4 scale-95"
    enter-to-class="opacity-100 translate-y-0 scale-100"
    leave-active-class="transition-all duration-200 ease-in"
    leave-from-class="opacity-100 translate-y-0 scale-100"
    leave-to-class="opacity-0 translate-y-4 scale-95"
  >
    <div
      v-if="isOpen"
      class="fixed bottom-6 right-[420px] w-[500px] max-h-[700px] bg-gray-800 rounded-xl shadow-2xl border border-gray-700/50 flex flex-col z-40"
    >
      <!-- Header -->
      <div class="flex items-center justify-between p-4 border-b border-gray-700/50 bg-gray-900/50">
        <div class="flex items-center gap-3">
          <div
            v-if="member"
            class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold"
          >
            {{ getInitials(member.name) }}
          </div>
          <div>
            <h3 class="text-white font-semibold">
              {{ member ? member.name : 'Tasks' }}'s Tasks
            </h3>
            <p v-if="member" class="text-xs text-gray-400">{{ member.email }}</p>
          </div>
        </div>
        <button
          @click="closePopup"
          class="p-2 hover:bg-gray-700 rounded-lg transition-colors"
        >
          <XMarkIcon class="w-5 h-5 text-gray-400" />
        </button>
      </div>

      <!-- Stats -->
      <div v-if="stats" class="px-4 pt-4 grid grid-cols-4 gap-2">
        <div class="bg-gray-900/50 rounded-lg p-2 text-center">
          <p class="text-xs text-gray-400">Total</p>
          <p class="text-lg font-bold text-white">{{ stats.total }}</p>
        </div>
        <div class="bg-gray-900/50 rounded-lg p-2 text-center">
          <p class="text-xs text-gray-400">Pending</p>
          <p class="text-lg font-bold text-yellow-400">{{ stats.pending }}</p>
        </div>
        <div class="bg-gray-900/50 rounded-lg p-2 text-center">
          <p class="text-xs text-gray-400">In Progress</p>
          <p class="text-lg font-bold text-blue-400">{{ stats.in_progress }}</p>
        </div>
        <div class="bg-gray-900/50 rounded-lg p-2 text-center">
          <p class="text-xs text-gray-400">Completed</p>
          <p class="text-lg font-bold text-green-400">{{ stats.completed }}</p>
        </div>
      </div>

      <!-- Tasks List -->
      <div
        ref="tasksContainer"
        class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-900/30"
      >
        <div v-if="loading" class="text-center text-gray-400 py-8">
          Loading tasks...
        </div>
        <div v-else-if="tasks.length === 0" class="text-center text-gray-400 py-8">
          No tasks assigned yet.
        </div>
        <div
          v-for="task in tasks"
          :key="task.id"
          class="bg-gray-800/50 rounded-lg p-4 border border-gray-700/50 hover:border-gray-600 transition-colors cursor-pointer"
          @click="viewTask(task)"
        >
          <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
              <h4 class="text-white font-medium truncate">{{ task.title }}</h4>
              <p class="text-sm text-gray-400 mt-1 line-clamp-2">{{ task.description }}</p>
              <div class="flex items-center gap-2 mt-2 flex-wrap">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                  :class="getStatusBadgeClass(task.status)"
                >
                  {{ task.status.replace('_', ' ') }}
                </span>
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                  :class="getPriorityBadgeClass(task.priority)"
                >
                  {{ task.priority }}
                </span>
                <span v-if="task.site" class="text-xs text-gray-500">
                  Site: {{ task.site.name }}
                </span>
                <span v-if="task.client" class="text-xs text-gray-500">
                  Client: {{ task.client.name }}
                </span>
              </div>
              <p v-if="task.dueDate" class="text-xs text-gray-500 mt-2">
                Due: {{ formatDate(task.dueDate) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div v-if="member" class="p-4 border-t border-gray-700/50 bg-gray-900/50">
        <button
          @click="goToTasksPage"
          class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium"
        >
          View All Tasks
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'

/**
 * Component props
 * @property {boolean} isOpen - Popup visibility
 * @property {Object|null} member - Team member object
 */
const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  member: {
    type: Object,
    default: null
  }
})

/**
 * Component emits
 */
const emit = defineEmits(['close'])

/**
 * Local state
 */
const toast = useToast()
const tasks = ref([])
const stats = ref(null)
const loading = ref(false)
const tasksContainer = ref(null)

/**
 * Get user initials
 * @param {string} name - Full name
 * @returns {string} Initials
 */
const getInitials = (name) => {
  if (!name) return ''
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

/**
 * Get status badge class
 * @param {string} status - Task status
 * @returns {string} Tailwind classes
 */
const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-yellow-500/10 text-yellow-400',
    in_progress: 'bg-blue-500/10 text-blue-400',
    completed: 'bg-green-500/10 text-green-400',
    cancelled: 'bg-gray-500/10 text-gray-400',
  }
  return classes[status] || classes.pending
}

/**
 * Get priority badge class
 * @param {string} priority - Task priority
 * @returns {string} Tailwind classes
 */
const getPriorityBadgeClass = (priority) => {
  const classes = {
    low: 'bg-gray-500/10 text-gray-400',
    medium: 'bg-blue-500/10 text-blue-400',
    high: 'bg-orange-500/10 text-orange-400',
    urgent: 'bg-red-500/10 text-red-400',
  }
  return classes[priority] || classes.medium
}

/**
 * Format date for display
 * @param {string} dateString - Date string
 * @returns {string} Formatted date
 */
const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

/**
 * Load tasks for the member
 */
const loadTasks = async () => {
  if (!props.member) return
  
  loading.value = true
  try {
    const response = await fetch(`/tasks/user/${props.member.id}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    })
    
    if (response.ok) {
      const data = await response.json()
      tasks.value = data.tasks || []
      stats.value = data.stats || null
    }
  } catch (error) {
    toast.error('Failed to load tasks. Please try again.')
  } finally {
    loading.value = false
  }
}

/**
 * View task details
 * @param {Object} task - Task object
 */
const viewTask = (task) => {
  router.visit(`/tasks/${task.id}`)
  closePopup()
}

/**
 * Go to tasks page with filter for this user
 */
const goToTasksPage = () => {
  if (props.member) {
    router.visit(`/tasks?assigned_to=${props.member.id}`)
  } else {
    router.visit('/tasks')
  }
  closePopup()
}

/**
 * Close popup
 */
const closePopup = () => {
  emit('close')
}

// Watch for member changes
watch(() => props.member, (newMember) => {
  if (newMember && props.isOpen) {
    loadTasks()
  }
}, { immediate: true })

// Watch for popup open/close
watch(() => props.isOpen, (isOpen) => {
  if (isOpen && props.member) {
    loadTasks()
  } else {
    tasks.value = []
    stats.value = null
  }
})

onMounted(() => {
  if (props.isOpen && props.member) {
    loadTasks()
  }
})
</script>

