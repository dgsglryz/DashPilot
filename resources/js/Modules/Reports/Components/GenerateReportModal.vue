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
      <div class="bg-gray-800 rounded-xl p-6 w-full max-w-2xl">
        <h3 class="text-xl font-bold text-white mb-4">Generate {{ template?.name }}</h3>
        
        <form @submit.prevent="handleGenerate" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="report-start-date" class="block text-sm font-medium text-gray-300 mb-2">Start Date</label>
              <input 
                id="report-start-date"
                v-model="form.startDate"
                type="date"
                required
                class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
              />
            </div>
            
            <div>
              <label for="report-end-date" class="block text-sm font-medium text-gray-300 mb-2">End Date</label>
              <input 
                id="report-end-date"
                v-model="form.endDate"
                type="date"
                required
                class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
              />
            </div>
          </div>
          
          <div>
            <label for="report-sites" class="block text-sm font-medium text-gray-300 mb-2">Sites to Include</label>
            <select 
              id="report-sites"
              v-model="form.siteIds"
              multiple
              class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
              size="5"
            >
              <option value="all">All Sites</option>
              <option v-for="site in sites" :key="site.id" :value="site.id.toString()">
                {{ site.name }}
              </option>
            </select>
          </div>
          
          <div>
            <span class="block text-sm font-medium text-gray-300 mb-2">Format</span>
            <div class="flex gap-3">
              <label for="format-pdf" class="flex items-center gap-2 cursor-pointer">
                <input 
                  id="format-pdf"
                  v-model="form.format"
                  type="radio"
                  value="pdf"
                  class="text-blue-600 focus:ring-blue-500"
                />
                <span class="text-white">PDF</span>
              </label>
              <label for="format-csv" class="flex items-center gap-2 cursor-pointer">
                <input 
                  id="format-csv"
                  v-model="form.format"
                  type="radio"
                  value="csv"
                  class="text-blue-600 focus:ring-blue-500"
                />
                <span class="text-white">CSV</span>
              </label>
              <label for="format-xlsx" class="flex items-center gap-2 cursor-pointer">
                <input 
                  id="format-xlsx"
                  v-model="form.format"
                  type="radio"
                  value="xlsx"
                  class="text-blue-600 focus:ring-blue-500"
                />
                <span class="text-white">Excel</span>
              </label>
            </div>
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
              Generate Report
            </button>
          </div>
        </form>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
// @ts-nocheck
import { reactive, watch } from 'vue'

/**
 * Component props
 * @property {boolean} show - Modal visibility
 * @property {Object} template - Selected report template
 */
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  template: {
    type: Object,
    default: null
  },
  sites: {
    type: Array,
    default: () => []
  }
})

/**
 * Component emits
 */
const emit = defineEmits(['close', 'generate'])

/**
 * Form state
 */
const form = reactive({
  templateId: null,
  startDate: '',
  endDate: '',
  siteIds: ['all'],
  format: 'pdf'
})

/**
 * Watch template changes
 */
watch(() => props.template, (template) => {
  if (template) {
    form.templateId = template.id
  }
})

/**
 * Handle generate submission
 */
const handleGenerate = () => {
  emit('generate', { ...form })
}
</script>
