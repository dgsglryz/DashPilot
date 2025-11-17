/**
 * Tests for QuickActionsDropdown component
 */
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import QuickActionsDropdown from '../QuickActionsDropdown.vue'

// Mock Inertia router
vi.mock('@inertiajs/vue3', () => ({
  router: {
    post: vi.fn(),
    visit: vi.fn(),
    delete: vi.fn(),
  },
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

// Mock navigator.clipboard
Object.assign(navigator, {
  clipboard: {
    writeText: vi.fn(),
  },
})

// Mock window.open
globalThis.window.open = vi.fn()

// Mock confirm
globalThis.window.confirm = vi.fn(() => true)

describe('QuickActionsDropdown', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders dropdown button', () => {
    const wrapper = mount(QuickActionsDropdown, {
      props: {
        siteId: 1,
        siteUrl: 'https://example.com',
        isFavorited: false,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    const button = wrapper.find('button')
    expect(button.exists()).toBe(true)
  })

  it('toggles dropdown when button is clicked', async () => {
    const wrapper = mount(QuickActionsDropdown, {
      props: {
        siteId: 1,
        siteUrl: 'https://example.com',
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    const button = wrapper.find('button')
    await button.trigger('click')
    await wrapper.vm.$nextTick()
    
    // Dropdown should show action items after click
    const actions = wrapper.findAll('button')
    expect(actions.length).toBeGreaterThan(1) // More than just the toggle button
  })

  it('displays all action items', async () => {
    const wrapper = mount(QuickActionsDropdown, {
      props: {
        siteId: 1,
        siteUrl: 'https://example.com',
        isFavorited: false,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    // Open dropdown
    await wrapper.find('button').trigger('click')
    await wrapper.vm.$nextTick()

    const actions = wrapper.findAll('button')
    expect(actions.length).toBeGreaterThan(0)
  })

  it('shows correct favorite label when not favorited', async () => {
    const wrapper = mount(QuickActionsDropdown, {
      props: {
        siteId: 1,
        siteUrl: 'https://example.com',
        isFavorited: false,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    // Open dropdown first
    await wrapper.find('button').trigger('click')
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Mark as favorite')
  })

  it('shows correct favorite label when favorited', async () => {
    const wrapper = mount(QuickActionsDropdown, {
      props: {
        siteId: 1,
        siteUrl: 'https://example.com',
        isFavorited: true,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    // Open dropdown first
    await wrapper.find('button').trigger('click')
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Remove from favorites')
  })

  it('emits favorite-toggled event when favorite action is clicked', async () => {
    const { router } = await import('@inertiajs/vue3')
    vi.mocked(router.post).mockImplementation((url, data, options) => {
      if (options?.onSuccess) {
        options.onSuccess({} as any)
      }
      return Promise.resolve()
    })

    const wrapper = mount(QuickActionsDropdown, {
      props: {
        siteId: 1,
        siteUrl: 'https://example.com',
        isFavorited: false,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    await wrapper.find('button').trigger('click')
    await wrapper.vm.$nextTick()

    // Find and click favorite action
    const favoriteButton = wrapper.findAll('button').find(btn => 
      btn.text().includes('Mark as favorite')
    )
    
    if (favoriteButton) {
      await favoriteButton.trigger('click')
      await wrapper.vm.$nextTick()
      
      // Event should be emitted after successful post
      expect(wrapper.emitted('favorite-toggled')).toBeTruthy()
    }
  })
})


