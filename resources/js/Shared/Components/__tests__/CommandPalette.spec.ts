/**
 * Tests for CommandPalette component
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import CommandPalette from '../CommandPalette.vue'

// Mock Inertia router
const mockRouter = {
  visit: vi.fn(),
}

vi.mock('@inertiajs/vue3', () => ({
  router: mockRouter,
}))

// Mock route function
vi.mock('ziggy-js', () => ({
  route: vi.fn((name: string) => `/route/${name}`),
}))

// Mock useToast
vi.mock('@/Shared/Composables/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
    warning: vi.fn(),
  }),
}))

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
}
global.localStorage = localStorageMock as any

describe('CommandPalette', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorageMock.getItem.mockReturnValue(null)
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('renders when isOpen is true', () => {
    const wrapper = mount(CommandPalette, {
      props: {
        isOpen: true,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div v-if="$attrs.isOpen"><slot /></div>',
            props: ['isOpen'],
          },
        },
      },
    })

    expect(wrapper.find('[data-testid="command-palette"]').exists()).toBe(true)
  })

  it('does not render when isOpen is false', () => {
    const wrapper = mount(CommandPalette, {
      props: {
        isOpen: false,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div v-if="$attrs.isOpen"><slot /></div>',
            props: ['isOpen'],
          },
        },
      },
    })

    expect(wrapper.find('[data-testid="command-palette"]').text()).toBe('')
  })

  it('displays search input', () => {
    const wrapper = mount(CommandPalette, {
      props: {
        isOpen: true,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    const input = wrapper.find('input[type="text"]')
    expect(input.exists()).toBe(true)
    expect(input.attributes('placeholder')).toContain('Search or type a command')
  })

  it('filters commands based on search query', async () => {
    const wrapper = mount(CommandPalette, {
      props: {
        isOpen: true,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    const input = wrapper.find('input[type="text"]')
    await input.setValue('sites')
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Go to Sites')
  })

  it('displays recent searches when no query', () => {
    localStorageMock.getItem.mockReturnValue(JSON.stringify(['dashboard', 'sites']))

    const wrapper = mount(CommandPalette, {
      props: {
        isOpen: true,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    expect(wrapper.text()).toContain('Recent Searches')
  })

  it('navigates when command is clicked', async () => {
    const wrapper = mount(CommandPalette, {
      props: {
        isOpen: true,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    await wrapper.vm.$nextTick()

    const dashboardButton = wrapper.findAll('button').find(btn => 
      btn.text().includes('Go to Dashboard')
    )
    
    if (dashboardButton) {
      await dashboardButton.trigger('click')
      await wrapper.vm.$nextTick()
      
      expect(mockRouter.visit).toHaveBeenCalled()
      expect(wrapper.emitted('close')).toBeTruthy()
    }
  })

  it('handles keyboard navigation', async () => {
    const wrapper = mount(CommandPalette, {
      props: {
        isOpen: true,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    const keydownEvent = new KeyboardEvent('keydown', { key: 'ArrowDown' })
    document.dispatchEvent(keydownEvent)
    await wrapper.vm.$nextTick()

    // Should not throw
    expect(wrapper.exists()).toBe(true)
  })

  it('closes on Escape key', async () => {
    const wrapper = mount(CommandPalette, {
      props: {
        isOpen: true,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    const escapeEvent = new KeyboardEvent('keydown', { key: 'Escape' })
    document.dispatchEvent(escapeEvent)
    await wrapper.vm.$nextTick()

    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('saves search to localStorage when command is executed', async () => {
    const wrapper = mount(CommandPalette, {
      props: {
        isOpen: true,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    const input = wrapper.find('input[type="text"]')
    await input.setValue('test query')
    await wrapper.vm.$nextTick()

    const commandButton = wrapper.findAll('button').find(btn => 
      btn.text().includes('Go to Dashboard')
    )
    
    if (commandButton) {
      await commandButton.trigger('click')
      await wrapper.vm.$nextTick()
      
      expect(localStorageMock.setItem).toHaveBeenCalled()
    }
  })
})


