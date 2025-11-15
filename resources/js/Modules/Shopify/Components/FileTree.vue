<template>
  <div class="space-y-1">
    <FileTreeNode 
      v-for="file in files" 
      :key="file.path"
      :file="file"
      :selected-path="selectedFile?.path"
      @select="$emit('select', file)"
    />
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
/**
 * Component props
 * @property {Array} files - File tree structure
 * @property {Object} selectedFile - Currently selected file
 */
defineProps({
  files: {
    type: Array,
    required: true
  },
  selectedFile: {
    type: Object,
    default: null
  }
})

/**
 * Component emits
 */
defineEmits(['select'])
</script>
