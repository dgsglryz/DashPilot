<template>
  <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
    <h3 class="text-lg font-semibold text-white mb-4">{{ title }}</h3>
    <slot />
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
/**
 * Component props
 * @property {string} title - Card title
 */
defineProps({
  title: {
    type: String,
    required: true
  }
})
</script>
