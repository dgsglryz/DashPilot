<template>
  <div>
    <button
      v-if="file.type === 'folder'"
      @click="toggleFolder"
      class="w-full flex items-center gap-2 px-2 py-1.5 hover:bg-gray-700/50 rounded transition-colors text-left"
    >
      <ChevronRightIcon 
        class="w-4 h-4 text-gray-400 transition-transform"
        :class="{ 'rotate-90': isOpen }"
      />
      <FolderIcon class="w-4 h-4 text-blue-400" />
      <span class="text-sm text-gray-300">{{ file.name }}</span>
    </button>
    
    <button
      v-else
      @click="$emit('select', file)"
      class="w-full flex items-center gap-2 px-2 py-1.5 hover:bg-gray-700/50 rounded transition-colors text-left"
      :class="{ 'bg-gray-700/70': isSelected }"
    >
      <div class="w-4 h-4"></div>
      <DocumentTextIcon class="w-4 h-4 text-gray-400" />
      <span class="text-sm text-gray-300">{{ file.name }}</span>
    </button>

    <div v-if="file.type === 'folder' && isOpen" class="pl-4">
      <FileTreeNode
        v-for="child in file.children"
        :key="child.path"
        :file="child"
        :selected-path="selectedPath"
        @select="$emit('select', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref, computed } from 'vue'
import { 
  ChevronRightIcon,
  FolderIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline'

/**
 * Component props
 * @property {Object} file - File or folder object
 * @property {string} selectedPath - Currently selected file path
 */
const props = defineProps({
  file: {
    type: Object,
    required: true
  },
  selectedPath: {
    type: String,
    default: null
  }
})

/**
 * Component emits
 */
defineEmits(['select'])

/**
 * Local state
 */
const isOpen = ref(false)

/**
 * Check if current file is selected
 */
const isSelected = computed(() => {
  return props.file.path === props.selectedPath
})

/**
 * Toggle folder open/close
 */
const toggleFolder = () => {
  isOpen.value = !isOpen.value
}
</script>
