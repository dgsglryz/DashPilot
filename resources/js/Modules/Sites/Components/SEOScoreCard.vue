<template>
  <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-white">SEO Analysis</h3>
      <div class="flex items-center gap-2">
        <span class="text-3xl font-bold" :class="scoreColor">{{ site.seoScore }}</span>
        <span class="text-gray-400">/100</span>
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
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { computed } from 'vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

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
