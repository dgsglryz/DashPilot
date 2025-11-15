<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white">Reports</h1>
          <p class="text-gray-400 mt-1">Generate and download comprehensive reports</p>
        </div>
        <button 
          @click="showGenerateModal = true"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center gap-2"
        >
          <DocumentPlusIcon class="w-5 h-5" />
          Generate Report
        </button>
      </div>

      <!-- Report Templates -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="template in reportTemplates" 
          :key="template.id"
          class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6 hover:border-blue-500/50 transition-colors cursor-pointer"
          @click="selectTemplate(template)"
        >
          <div class="flex items-start justify-between mb-4">
            <div 
              class="w-12 h-12 rounded-lg flex items-center justify-center"
              :class="template.iconBg"
            >
              <component :is="iconComponents[template.icon]" class="w-6 h-6" :class="template.iconColor" />
            </div>
            <span 
              class="px-2.5 py-1 rounded-full text-xs font-medium"
              :class="template.categoryColor"
            >
              {{ template.category }}
            </span>
          </div>
          
          <h3 class="text-lg font-semibold text-white mb-2">{{ template.name }}</h3>
          <p class="text-sm text-gray-400 mb-4">{{ template.description }}</p>
          
          <div class="flex items-center justify-between text-xs text-gray-500">
            <span>{{ template.frequency }}</span>
            <span>{{ template.format }}</span>
          </div>
        </div>
      </div>

      <!-- Recent Reports -->
      <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 overflow-hidden">
        <div class="p-6 border-b border-gray-700">
          <h3 class="text-lg font-semibold text-white">Recent Reports</h3>
        </div>
        
        <table class="w-full">
          <thead class="bg-gray-900/50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Report Name</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Type</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Period</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Generated</th>
              <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-700/50">
            <tr v-for="report in recentReports" :key="report.id" class="hover:bg-gray-700/20 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <DocumentTextIcon class="w-5 h-5 text-gray-400" />
                  <span class="text-white font-medium">{{ report.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="text-gray-300 text-sm">{{ report.type }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="text-gray-300 text-sm">{{ report.period }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="text-gray-400 text-sm">{{ formatDate(report.createdAt) }}</span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-2">
                  <a 
                    :href="report.downloadUrl"
                    class="p-2 hover:bg-gray-700 rounded-lg transition-colors"
                    title="Download"
                  >
                    <ArrowDownTrayIcon class="w-4 h-4 text-gray-400" />
                  </a>
                  <button 
                    @click="deleteReport(report.id)"
                    class="p-2 hover:bg-gray-700 rounded-lg transition-colors"
                    title="Delete"
                  >
                    <TrashIcon class="w-4 h-4 text-gray-400" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Generate Report Modal -->
    <GenerateReportModal 
      :show="showGenerateModal"
      :template="selectedTemplate"
      :sites="props.sites"
      @close="showGenerateModal = false"
      @generate="generateReport"
    />
  </AppLayout>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Shared/Layouts/AppLayout.vue'
import GenerateReportModal from '@/Modules/Reports/Components/GenerateReportModal.vue'
import { 
  DocumentPlusIcon,
  DocumentTextIcon,
  ArrowDownTrayIcon,
  TrashIcon,
  ChartBarIcon,
  ShieldCheckIcon,
  ClockIcon
} from '@heroicons/vue/24/outline'

/**
 * Component props from Inertia
 * @property {Array} reportTemplates - Available report templates
 * @property {Array} recentReports - Recently generated reports
 */
const props = defineProps({
  reportTemplates: {
    type: Array,
    required: true
  },
  recentReports: {
    type: Array,
    default: () => []
  },
  sites: {
    type: Array,
    default: () => []
  }
})

const iconComponents = {
  ChartBarIcon,
  ShieldCheckIcon,
  ClockIcon
}

/**
 * Local state
 */
const showGenerateModal = ref(false)
const selectedTemplate = ref(null)

/**
 * Select report template
 * @param {Object} template - Report template
 */
const selectTemplate = (template) => {
  selectedTemplate.value = template
  showGenerateModal.value = true
}

/**
 * Generate report
 * @param {Object} config - Report configuration
 */
const generateReport = (config) => {
  router.post('/reports/generate', config, {
    onSuccess: () => {
      showGenerateModal.value = false
      selectedTemplate.value = null
    }
  })
}

/**
 * Delete report
 * @param {number} reportId - Report ID
 */
const deleteReport = (reportId) => {
  if (confirm('Are you sure you want to delete this report?')) {
    router.delete(`/reports/${reportId}`)
  }
}

/**
 * Format date
 * @param {string} date - ISO date string
 * @returns {string} Formatted date
 */
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}
</script>
