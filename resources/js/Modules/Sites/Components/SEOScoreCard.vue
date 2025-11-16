<template>
  <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-white">SEO Analysis</h3>
      <div class="flex items-center gap-2">
        <span class="text-3xl font-bold" :class="scoreColor">{{ site.seoScore }}</span>
        <span class="text-gray-400">/100</span>
        <button
          @click="showModal = true"
          class="ml-2 rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-700 hover:text-white"
          title="View score breakdown"
        >
          <InformationCircleIcon class="h-5 w-5" />
        </button>
      </div>
    </div>

    <!-- Score Breakdown -->
    <div class="space-y-4">
      <div v-for="metric in seoMetrics" :key="metric.name" class="space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-400">{{ metric.name }}</span>
          <span class="text-sm font-medium" :class="getScoreColor(metric.score)">{{ metric.score }}/100</span>
        </div>
        <div class="h-2 bg-gray-900 rounded-full overflow-hidden">
          <div 
            class="h-full rounded-full transition-all duration-500"
            :class="getScoreBgColor(metric.score)"
            :style="{ width: `${metric.score}%` }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Issues -->
    <div v-if="seoIssues.length > 0" class="mt-6 pt-6 border-t border-gray-700">
      <h4 class="text-sm font-semibold text-white mb-3">Issues to Fix</h4>
      <div class="space-y-2">
        <div v-for="issue in seoIssues" :key="issue.id" class="flex items-start gap-3 p-3 bg-gray-900/50 rounded-lg">
          <ExclamationTriangleIcon class="w-5 h-5 text-yellow-400 flex-shrink-0 mt-0.5" />
          <div class="flex-1">
            <p class="text-sm text-white">{{ issue.title }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ issue.description }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Health Score Modal -->
    <HealthScoreModal
      :is-open="showModal"
      :score="site.seoScore"
      :breakdown="scoreBreakdown"
      :site-name="site.name"
      @close="showModal = false"
    />
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { computed, ref } from 'vue'
import { ExclamationTriangleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'
import HealthScoreModal from '@/Shared/Components/HealthScoreModal.vue'

/**
 * Component props
 * @property {Object} site - Site object with SEO data
 */
const props = defineProps({
  site: {
    type: Object,
    required: true
  }
})

/**
 * SEO metrics breakdown
 */
const seoMetrics = computed(() => props.site.seoMetrics || [
  { name: 'Performance', score: 85 },
  { name: 'Accessibility', score: 92 },
  { name: 'Best Practices', score: 88 },
  { name: 'SEO', score: 95 }
])

/**
 * SEO issues to fix
 */
const seoIssues = computed(() => props.site.seoIssues || [])

/**
 * Modal state
 */
const showModal = ref(false)

/**
 * Score breakdown for modal
 */
const scoreBreakdown = computed(() => {
  const breakdown = []
  const score = props.site.seoScore
  
  if (score < 100) {
    if (score < 90) breakdown.push({ issue: 'Missing meta description', points: -10, description: 'Add a compelling meta description for better SEO' })
    if (score < 85) breakdown.push({ issue: 'No H1 or multiple H1s', points: -15, description: 'Ensure exactly one H1 tag exists' })
    if (score < 80) breakdown.push({ issue: 'No SSL certificate', points: -20, description: 'Site should use HTTPS' })
    if (score < 75) breakdown.push({ issue: 'Slow page load time', points: -10, description: 'Page takes longer than 3 seconds to load' })
    if (score < 70) breakdown.push({ issue: 'No viewport meta tag', points: -10, description: 'Add viewport meta for mobile responsiveness' })
    if (score < 60) breakdown.push({ issue: 'Images missing alt tags', points: -20, description: 'Multiple images lack alt attributes' })
  }
  
  return breakdown
})

/**
 * Overall score color
 */
const scoreColor = computed(() => {
  const score = props.site.seoScore
  if (score >= 90) return 'text-green-400'
  if (score >= 70) return 'text-yellow-400'
  return 'text-red-400'
})

/**
 * Get text color based on score
 * @param {number} score - Score value
 * @returns {string} Tailwind color class
 */
const getScoreColor = (score) => {
  if (score >= 90) return 'text-green-400'
  if (score >= 70) return 'text-yellow-400'
  return 'text-red-400'
}

/**
 * Get background color based on score
 * @param {number} score - Score value
 * @returns {string} Tailwind bg color class
 */
const getScoreBgColor = (score) => {
  if (score >= 90) return 'bg-green-500'
  if (score >= 70) return 'bg-yellow-500'
  return 'bg-red-500'
}
</script>
