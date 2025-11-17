/**
 * Tests for useDarkMode composable
 */
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { defineComponent, h } from 'vue'
import { useDarkMode } from '../useDarkMode'

// Mock window and document
const mockClassList = {
  add: vi.fn(),
  remove: vi.fn(),
}

const mockDocumentElement = {
  classList: mockClassList,
  style: {} as CSSStyleDeclaration,
}

const mockBody = {
  classList: mockClassList,
}

const mockDocument = {
  documentElement: mockDocumentElement,
  body: mockBody,
} as unknown as Document

const mockWindow = {
  ...globalThis.window,
  document: mockDocument,
} as any

Object.defineProperty(globalThis, 'window', {
  value: mockWindow,
  writable: true,
  configurable: true,
})

Object.defineProperty(globalThis, 'document', {
  value: mockDocument,
  writable: true,
  configurable: true,
})

describe('useDarkMode', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Reset initialization state
    ;(useDarkMode as any).initialized = false
  })

  it('initializes dark mode on mount', () => {
    const TestComponent = defineComponent({
      setup() {
        const { isDark } = useDarkMode()
        return { isDark }
      },
      render: () => h('div'),
    })

    mount(TestComponent)

    expect(mockDocumentElement.classList.add).toHaveBeenCalledWith('dark')
    expect(mockDocumentElement.style.colorScheme).toBe('dark')
  })

  it('toggles dark mode state', () => {
    const TestComponent = defineComponent({
      setup() {
        const { isDark, toggleDarkMode } = useDarkMode()
        return { isDark, toggleDarkMode }
      },
      render: () => h('div'),
    })

    const wrapper = mount(TestComponent)
    const { toggleDarkMode } = wrapper.vm

    toggleDarkMode()
    expect(mockDocumentElement.classList.add).toHaveBeenCalledWith('dark')
  })

  it('sets dark mode state', () => {
    const TestComponent = defineComponent({
      setup() {
        const { setDarkMode } = useDarkMode()
        return { setDarkMode }
      },
      render: () => h('div'),
    })

    const wrapper = mount(TestComponent)
    const { setDarkMode } = wrapper.vm

    setDarkMode(true)
    expect(mockDocumentElement.classList.add).toHaveBeenCalledWith('dark')
  })

  it('handles SSR environment (no window)', () => {
    const originalWindow = globalThis.window
    delete (globalThis as any).window

    const TestComponent = defineComponent({
      setup() {
        const { isDark } = useDarkMode()
        return { isDark }
      },
      render: () => h('div'),
    })

    // Should not throw
    expect(() => mount(TestComponent)).not.toThrow()

    globalThis.window = originalWindow
  })
})


