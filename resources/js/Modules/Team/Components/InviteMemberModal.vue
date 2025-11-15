<template>
  <Transition
    enter-active-class="transition-opacity duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition-opacity duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div 
      v-if="show"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="$emit('close')"
    >
      <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold text-white mb-4">Invite Team Member</h3>
        
        <form @submit.prevent="handleInvite" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Full Name</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
              placeholder="Ada Lovelace"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
            <input 
              v-model="form.email"
              type="email"
              required
              class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
              placeholder="colleague@example.com"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Role</label>
            <select 
              v-model="form.role"
              class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
            >
              <option value="member">Member</option>
              <option value="manager">Manager</option>
              <option value="admin">Admin</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">{{ getRoleDescription(form.role) }}</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Message (Optional)</label>
            <textarea 
              v-model="form.message"
              rows="3"
              class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
              placeholder="Add a personal message..."
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2">
            <button 
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors"
            >
              Cancel
            </button>
            <button 
              type="submit"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
            >
              Send Invite
            </button>
          </div>
        </form>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
// @ts-nocheck
import { reactive } from 'vue'

/**
 * Component props
 * @property {boolean} show - Modal visibility
 */
defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

/**
 * Component emits
 */
const emit = defineEmits(['close', 'invite'])

/**
 * Form state
 */
const form = reactive({
  name: '',
  email: '',
  role: 'member',
  message: ''
})

/**
 * Get role description
 * @param {string} role - Role name
 * @returns {string} Description
 */
const getRoleDescription = (role) => {
  const descriptions = {
    member: 'Can view and monitor sites',
    manager: 'Can manage sites and view reports',
    admin: 'Full access to all features and settings'
  }
  return descriptions[role] || ''
}

/**
 * Handle invite submission
 */
const handleInvite = () => {
  emit('invite', { ...form })
  
  // Reset form
  form.name = ''
  form.email = ''
  form.role = 'member'
  form.message = ''
}
</script>
