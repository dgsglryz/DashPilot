<template>
  <div class="h-full flex flex-col">
    <div class="p-4 border-b border-gray-700">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-white uppercase">Snippet Library</h3>
        <button 
          @click="showAddSnippet = true"
          class="p-1.5 hover:bg-gray-700 rounded transition-colors"
        >
          <PlusIcon class="w-4 h-4 text-gray-400" />
        </button>
      </div>
      
      <input 
        v-model="searchQuery"
        type="text"
        placeholder="Search snippets..."
        class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white text-sm placeholder-gray-400 focus:outline-none focus:border-blue-500"
      />
    </div>

    <div class="flex-1 overflow-y-auto p-4 space-y-3">
      <div 
        v-for="snippet in filteredSnippets" 
        :key="snippet.id"
        class="bg-gray-900/50 rounded-lg p-3 hover:bg-gray-700/30 transition-colors"
      >
        <div class="flex items-start justify-between mb-2">
          <div>
            <h4 class="text-white font-medium text-sm">{{ snippet.title }}</h4>
            <p class="text-gray-400 text-xs mt-1">{{ snippet.description }}</p>
          </div>
          <button 
            @click="$emit('insert', snippet)"
            class="p-1.5 hover:bg-blue-600 rounded transition-colors flex-shrink-0"
            title="Insert snippet"
          >
            <ArrowDownTrayIcon class="w-4 h-4 text-gray-400 hover:text-white" />
          </button>
        </div>
        
        <div class="mt-2">
          <div class="flex items-center gap-2 mb-1">
            <span class="text-xs text-gray-500">Category:</span>
            <span 
              class="px-2 py-0.5 bg-blue-500/10 text-blue-400 text-xs rounded"
            >
              {{ snippet.category }}
            </span>
          </div>
        </div>
      </div>

      <div v-if="filteredSnippets.length === 0" class="text-center py-8">
        <p class="text-gray-500 text-sm">No snippets found</p>
      </div>
    </div>

    <!-- Add Snippet Modal -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="showAddSnippet"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="showAddSnippet = false"
      >
        <div class="bg-gray-800 rounded-xl p-6 w-full max-w-2xl">
          <h3 class="text-xl font-bold text-white mb-4">Add Custom Snippet</h3>
          
          <div class="space-y-4">
            <div>
              <label for="snippet-title" class="block text-sm font-medium text-gray-300 mb-2">Title</label>
              <input 
                id="snippet-title"
                v-model="newSnippet.title"
                type="text"
                class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
              />
            </div>
            
            <div>
              <label for="snippet-description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
              <input 
                id="snippet-description"
                v-model="newSnippet.description"
                type="text"
                class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
              />
            </div>
            
            <div>
              <label for="snippet-category" class="block text-sm font-medium text-gray-300 mb-2">Category</label>
              <select 
                id="snippet-category"
                v-model="newSnippet.category"
                class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
              >
                <option value="layout">Layout</option>
                <option value="components">Components</option>
                <option value="logic">Logic</option>
                <option value="filters">Filters</option>
              </select>
            </div>
            
            <div>
              <label for="snippet-code" class="block text-sm font-medium text-gray-300 mb-2">Code</label>
              <textarea 
                id="snippet-code"
                v-model="newSnippet.code"
                rows="8"
                class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white font-mono text-sm focus:outline-none focus:border-blue-500"
              ></textarea>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 mt-6">
            <button 
              @click="showAddSnippet = false"
              class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors"
            >
              Cancel
            </button>
            <button 
              @click="saveSnippet"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
            >
              Save Snippet
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref, computed } from 'vue'
import { 
  PlusIcon,
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'

/**
 * Component props
 * @property {Array} snippets - Snippet library
 */
const props = defineProps({
  snippets: {
    type: Array,
    default: () => []
  }
})

/**
 * Component emits
 */
const emit = defineEmits(['insert', 'save'])

/**
 * Local state
 */
const searchQuery = ref('')
const showAddSnippet = ref(false)
const newSnippet = ref({
  title: '',
  description: '',
  category: 'components',
  code: ''
})

/**
 * Filtered snippets based on search
 */
const filteredSnippets = computed(() => {
  if (!searchQuery.value) return props.snippets
  
  const query = searchQuery.value.toLowerCase()
  return props.snippets.filter(snippet => 
    snippet.title.toLowerCase().includes(query) ||
    snippet.description.toLowerCase().includes(query) ||
    snippet.category.toLowerCase().includes(query)
  )
})

/**
 * Save new snippet
 */
const saveSnippet = () => {
  emit('save', { ...newSnippet.value })
  
  // Reset form
  newSnippet.value = {
    title: '',
    description: '',
    category: 'components',
    code: ''
  }
  
  showAddSnippet.value = false
}
</script>
