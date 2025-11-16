<template>
  <div ref="editorRef" class="h-full w-full code-editor-container"></div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref, onMounted, watch, onBeforeUnmount } from 'vue'
import { EditorView } from '@codemirror/view'
import { EditorState, Compartment } from '@codemirror/state'
import { oneDark } from '@codemirror/theme-one-dark'
import { html } from '@codemirror/lang-html'
import { javascript } from '@codemirror/lang-javascript'

/**
 * CodeMirrorEditor component provides syntax highlighting for Liquid template files.
 * Uses CodeMirror 6 with HTML/Liquid syntax highlighting and dark theme.
 * Liquid syntax is similar to HTML with {% %} and {{ }} tags.
 */
const props = defineProps<{
  modelValue: string
  language?: string
}>()

const emit = defineEmits(['update:modelValue', 'change'])

const editorRef = ref<HTMLDivElement | null>(null)
let view: EditorView | null = null
const languageConf = new Compartment()

/**
 * Get language extension based on file extension or language prop
 */
const getLanguageExtension = () => {
  const lang = props.language?.toLowerCase() || 'liquid'
  
  // Use HTML as base since Liquid is similar ({% %}, {{ }})
  if (lang === 'liquid' || lang.includes('liquid')) {
    return html()
  }
  
  if (lang === 'javascript' || lang === 'js') {
    return javascript()
  }
  
  // Default to HTML for Liquid templates
  return html()
}

/**
 * Initialize CodeMirror editor with syntax highlighting
 */
onMounted(() => {
  if (!editorRef.value) return

  const startState = EditorState.create({
    doc: props.modelValue,
    extensions: [
      oneDark,
      languageConf.of(getLanguageExtension()),
      EditorView.lineWrapping,
      EditorView.updateListener.of((update) => {
        if (update.docChanged) {
          const value = update.state.doc.toString()
          emit('update:modelValue', value)
          emit('change', value)
        }
      }),
      EditorView.theme({
        '&': {
          height: '100%',
          fontSize: '14px',
          fontFamily: 'Fira Code, Consolas, monospace',
        },
        '.cm-scroller': {
          overflow: 'auto',
          height: '100%',
        },
        '.cm-content': {
          padding: '1rem',
        },
      }),
    ],
  })

  view = new EditorView({
    state: startState,
    parent: editorRef.value,
  })
})

/**
 * Update editor content when prop changes externally
 */
watch(
  () => props.modelValue,
  (newValue) => {
    if (view && view.state.doc.toString() !== newValue) {
      view.dispatch({
        changes: {
          from: 0,
          to: view.state.doc.length,
          insert: newValue,
        },
      })
    }
  }
)

/**
 * Update language when prop changes
 */
watch(
  () => props.language,
  () => {
    if (view) {
      view.dispatch({
        effects: languageConf.reconfigure(getLanguageExtension()),
      })
    }
  }
)

/**
 * Cleanup editor instance
 */
onBeforeUnmount(() => {
  view?.destroy()
})
</script>

<style>
/* CodeMirror styles are handled by the library and theme via EditorView.theme */
.code-editor-container {
  height: 100%;
  width: 100%;
}
</style>
