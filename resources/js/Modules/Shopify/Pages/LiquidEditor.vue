<template>
  <AppLayout>
    <div class="h-[calc(100vh-4rem)] flex flex-col">
      <!-- Header -->
      <div class="flex items-center justify-between p-4 border-b border-gray-700">
        <div class="flex items-center gap-4">
          <h1 class="text-2xl font-bold text-white">Liquid Editor</h1>
          <select 
            v-model="selectedSiteId" 
            @change="loadSiteTheme"
            class="px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
          >
            <option value="">Select Shopify Site</option>
            <option v-for="site in shopifySites" :key="site.id" :value="site.id">
              {{ site.name }}
            </option>
          </select>
        </div>
        
        <div class="flex items-center gap-2">
          <button 
            @click="showSnippets = !showSnippets"
            class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors flex items-center gap-2"
          >
            <BookmarkIcon class="w-4 h-4" />
            Snippets
          </button>
          <button 
            @click="formatCode"
            class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors"
          >
            Format
          </button>
          <button 
            @click="saveTemplate"
            :disabled="!hasChanges || saving"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-700 disabled:cursor-not-allowed text-white rounded-lg transition-colors flex items-center gap-2"
          >
            <ArrowUpTrayIcon class="w-4 h-4" />
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>

      <div class="flex-1 flex overflow-hidden">
        <!-- Sidebar - File Tree -->
        <div class="w-64 bg-gray-800/50 border-r border-gray-700 overflow-y-auto">
          <div class="p-4">
            <h3 class="text-sm font-semibold text-gray-400 uppercase mb-3">Theme Files</h3>
          <FileTree 
            :files="themeFiles" 
            :selected-file="selectedFile"
            @select="selectFile"
          />
          </div>
        </div>

        <!-- Main Editor -->
        <div class="flex-1 flex flex-col">
          <div v-if="selectedFile" class="flex-1 flex flex-col">
            <!-- File tabs -->
            <div class="flex items-center gap-1 px-4 py-2 bg-gray-800/30 border-b border-gray-700 overflow-x-auto">
              <div 
                v-for="tab in openTabs" 
                :key="tab.path"
                class="flex items-center gap-2 px-3 py-1.5 rounded-t-lg transition-colors cursor-pointer"
                :class="selectedFile?.path === tab.path ? 'bg-gray-900 text-white' : 'bg-gray-800/50 text-gray-400 hover:text-white'"
                @click="selectFile(tab)"
              >
                <DocumentTextIcon class="w-4 h-4" />
                <span class="text-sm">{{ tab.name }}</span>
                <button 
                  @click.stop="closeTab(tab.path)"
                  class="hover:text-red-400 transition-colors"
                >
                  <XMarkIcon class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Code Editor -->
            <div class="flex-1 relative">
              <CodeMirrorEditor 
                v-model="editorContent"
                :language="getFileLanguage(selectedFile.name)"
                @change="handleEditorChange"
              />
            </div>

            <!-- Status Bar -->
            <div class="flex items-center justify-between px-4 py-2 bg-gray-800/30 border-t border-gray-700 text-xs text-gray-400">
              <div class="flex items-center gap-4">
                <span>{{ selectedFile.path }}</span>
                <span>{{ editorContent.split('\n').length }} lines</span>
              </div>
              <div class="flex items-center gap-4">
                <span>{{ getFileLanguage(selectedFile.name).toUpperCase() }}</span>
                <span v-if="hasChanges" class="text-yellow-400">● Modified</span>
              </div>
            </div>
          </div>

          <div v-else class="flex-1 flex items-center justify-center text-gray-500">
            <div class="text-center">
              <DocumentTextIcon class="w-16 h-16 mx-auto mb-4 opacity-50" />
              <p class="text-lg">No file selected</p>
              <p class="text-sm">Select a file from the tree to start editing</p>
            </div>
          </div>
        </div>

        <!-- Snippets Sidebar -->
        <Transition
          enter-active-class="transition-transform duration-200"
          enter-from-class="translate-x-full"
          enter-to-class="translate-x-0"
          leave-active-class="transition-transform duration-200"
          leave-from-class="translate-x-0"
          leave-to-class="translate-x-full"
        >
          <div v-if="showSnippets" class="w-96 bg-gray-800/50 border-l border-gray-700 overflow-y-auto">
            <SnippetsPanel 
              :snippets="snippetLibrary"
              @insert="insertSnippet"
              @save="saveCustomSnippet"
            />
          </div>
        </Transition>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref, computed, watch } from 'vue'
import AppLayout from '@/Shared/Layouts/AppLayout.vue'
import FileTree from '@/Modules/Shopify/Components/FileTree.vue'
import CodeMirrorEditor from '@/Modules/Shopify/Components/CodeMirrorEditor.vue'
import SnippetsPanel from '@/Modules/Shopify/Components/SnippetsPanel.vue'
import { 
  DocumentTextIcon,
  BookmarkIcon,
  ArrowUpTrayIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

/**
 * Component props from Inertia
 * @property {Array} shopifySites - List of Shopify sites
 * @property {Array} snippetLibrary - Predefined snippet library
 */
const props = defineProps({
  shopifySites: {
    type: Array,
    required: true
  },
  snippetLibrary: {
    type: Array,
    default: () => []
  }
})

/**
 * Local reactive state
 */
const selectedSiteId = ref('')
const themeFiles = ref([])
const selectedFile = ref(null)
const openTabs = ref([])
const editorContent = ref('')
const originalContent = ref('')
const showSnippets = ref(false)
const saving = ref(false)

/**
 * Check if editor has unsaved changes
 */
const hasChanges = computed(() => {
  return editorContent.value !== originalContent.value
})

/**
 * Load theme files for selected site
 */
const loadSiteTheme = async () => {
  if (!selectedSiteId.value) return

  const response = await window.axios.get(`/shopify/editor/${selectedSiteId.value}/files`)
  themeFiles.value = response.data.files
}

/**
 * Select and open a file
 * @param {Object} file - File object
 */
const selectFile = (file) => {
  if (!file) return
  
  // Save current file if has changes
  if (hasChanges.value && selectedFile.value) {
    const confirmDiscard = confirm('You have unsaved changes. Discard them?')
    if (!confirmDiscard) return
  }
  
  selectedFile.value = file
  
  // Add to open tabs if not already open
  if (!openTabs.value.find(t => t.path === file.path)) {
    openTabs.value.push(file)
  }
  
  // Load file content
  window.axios
    .get(`/shopify/editor/${selectedSiteId.value}/file`, { params: { path: file.path } })
    .then(({ data }) => {
      editorContent.value = data.content || ''
      originalContent.value = data.content || ''
    })
}

/**
 * Close a tab
 * @param {string} path - File path
 */
const closeTab = (path) => {
  const index = openTabs.value.findIndex(t => t.path === path)
  if (index === -1) return
  
  openTabs.value.splice(index, 1)
  
  // If closing selected file, select another tab
  if (selectedFile.value?.path === path) {
    if (openTabs.value.length > 0) {
      selectFile(openTabs.value[openTabs.value.length - 1])
    } else {
      selectedFile.value = null
      editorContent.value = ''
      originalContent.value = ''
    }
  }
}

/**
 * Handle editor content change
 */
const handleEditorChange = () => {
  // Content is already bound via v-model
}

/**
 * Get file language for syntax highlighting
 * @param {string} filename - File name
 * @returns {string} Language identifier
 */
const getFileLanguage = (filename) => {
  if (filename.endsWith('.liquid')) return 'liquid'
  if (filename.endsWith('.json')) return 'json'
  if (filename.endsWith('.js')) return 'javascript'
  if (filename.endsWith('.css') || filename.endsWith('.scss')) return 'css'
  return 'text'
}

/**
 * Format code
 */
const formatCode = () => {
  // Basic formatting - can be enhanced with prettier
  editorContent.value = editorContent.value.trim()
}

/**
 * Save current template
 */
const saveTemplate = () => {
  if (!selectedFile.value || !hasChanges.value) return
  
  saving.value = true

  window.axios
    .post(`/shopify/editor/${selectedSiteId.value}/save`, {
      path: selectedFile.value.path,
      content: editorContent.value
    })
    .then(() => {
      originalContent.value = editorContent.value
    })
    .finally(() => {
      saving.value = false
    })
}

/**
 * Insert snippet at cursor position
 * @param {Object} snippet - Snippet object
 */
const insertSnippet = (snippet) => {
  editorContent.value += '\n' + snippet.code + '\n'
}

/**
 * Save custom snippet
 * @param {Object} snippet - New snippet object
 */
const saveCustomSnippet = (snippet) => {
  window.axios.post('/shopify/snippets', snippet).then(() => {
    showSnippets.value = false
  })
}

/**
 * Warn about unsaved changes on navigation
 */
watch(
  () => props.shopifySites,
  (sites) => {
    if (!selectedSiteId.value && sites.length) {
      selectedSiteId.value = sites[0].id
      loadSiteTheme()
    }
  },
  { immediate: true }
)

watch(() => selectedSiteId.value, () => {
  if (hasChanges.value) {
    const confirmDiscard = confirm('You have unsaved changes. Discard them?')
    if (!confirmDiscard) {
      // Reset selection
      selectedSiteId.value = selectedSiteId.value
    }
  }
})
</script>
