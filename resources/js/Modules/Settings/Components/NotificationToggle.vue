<template>
  <div class="flex items-start justify-between p-4 bg-gray-900/50 rounded-lg">
    <div class="flex-1">
      <p class="text-white font-medium">{{ title }}</p>
      <p class="text-sm text-gray-400 mt-1">{{ description }}</p>
    </div>
    <button 
      @click="toggle"
      class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none flex-shrink-0"
      :class="isEnabled ? 'bg-blue-600' : 'bg-gray-700'"
    >
      <span 
        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
        :class="isEnabled ? 'translate-x-6' : 'translate-x-1'"
      ></span>
    </button>
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { computed } from 'vue'

/**
 * Component props
 * @property {boolean} modelValue - Toggle state (v-model)
 * @property {string} title - Toggle title
 * @property {string} description - Toggle description
 */
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  },
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  }
})

/**
 * Component emits
 */
const emit = defineEmits(['update:modelValue'])

/**
 * Computed enabled state
 */
const isEnabled = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

/**
 * Toggle state
 */
const toggle = () => {
  isEnabled.value = !isEnabled.value
}
</script>
