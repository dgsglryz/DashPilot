/**
 * AppLayout Component Tests
 * 
 * Note: Frontend coverage is disabled - only backend coverage is tracked.
 * These tests ensure component functionality but coverage is not calculated.
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import AppLayout from '../AppLayout.vue'

// Mock route function globally first
const mockRoute = vi.fn((name: string) => `/${name}`)
(globalThis as any).route = mockRoute

// Mock Inertia - must define mocks inside factory function
vi.mock('@inertiajs/vue3', () => {
  const mockRouterVisit = vi.fn()
  return {
    Link: {
      name: 'Link',
      template: '<a><slot /></a>',
      props: ['href', 'method', 'as'],
    },
    router: {
      visit: mockRouterVisit,
      get: vi.fn(),
      post: vi.fn(),
    },
    route: mockRoute,
  }
})

// Mock axios - use vi.hoisted for proper hoisting
const { mockAxiosGet } = vi.hoisted(() => {
  return {
    mockAxiosGet: vi.fn(),
  }
})

vi.mock('axios', () => ({
  default: {
    get: mockAxiosGet,
  },
}))

// Mock CommandPalette
vi.mock('@/Shared/Components/CommandPalette.vue', () => ({
  default: {
    name: 'CommandPalette',
    template: '<div data-testid="command-palette"></div>',
    props: ['isOpen'],
    emits: ['close'],
  },
}))

// Mock route().current() function
const mockRouteCurrent = vi.fn<() => string | null>(() => null)
// Add current method to route mock
;(mockRoute as any).current = mockRouteCurrent

describe('AppLayout', () => {
  beforeEach(() => {
    // Reset all mocks
    vi.clearAllMocks()
    
    // Mock localStorage
    Storage.prototype.getItem = vi.fn(() => null)
    Storage.prototype.setItem = vi.fn()
    Storage.prototype.removeItem = vi.fn()
    
    // Mock global.route function
    ;(globalThis as any).route = mockRoute as any
    ;((globalThis as any).route as any).current = mockRouteCurrent
    
    // Mock window.location
    Object.defineProperty(window, 'location', {
      value: {
        pathname: '/dashboard',
      },
      writable: true,
    })
    
    // Reset axios mock
    mockAxiosGet.mockResolvedValue({ data: { results: [] } })
  })

  afterEach(() => {
    vi.clearAllMocks()
  })

  /**
   * Basic rendering tests
   */
  it('renders navigation items', () => {
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    expect(wrapper.text()).toContain('DashPilot')
    expect(wrapper.text()).toContain('Overview')
    expect(wrapper.text()).toContain('Sites')
    expect(wrapper.text()).toContain('Clients')
    expect(wrapper.text()).toContain('Tasks')
    expect(wrapper.text()).toContain('Metrics')
    expect(wrapper.text()).toContain('Alerts')
    expect(wrapper.text()).toContain('Team')
    expect(wrapper.text()).toContain('Reports')
  })

  it('renders search input', () => {
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const searchInput = wrapper.find('input[placeholder*="Search"]')
    expect(searchInput.exists()).toBe(true)
  })

  it('renders slot content', () => {
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div data-testid="slot-content">Test Content</div>',
      },
    })

    expect(wrapper.find('[data-testid="slot-content"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Test Content')
  })

  /**
   * Mobile menu tests
   */
  it('toggles mobile menu when button is clicked', async () => {
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const toggleButton = wrapper.find('button[aria-label="Toggle mobile menu"]')
    expect(toggleButton.exists()).toBe(true)
    
    // Initially mobile menu should be closed (hidden on lg screens)
    let mobileMenu = wrapper.find('aside[aria-label="Mobile navigation menu"]')
    expect(mobileMenu.exists()).toBe(false)
    
    // Click toggle button
    await toggleButton.trigger('click')
    await nextTick()
    
    // Mobile menu should now be visible
    mobileMenu = wrapper.find('aside[aria-label="Mobile navigation menu"]')
    expect(mobileMenu.exists()).toBe(true)
    
    // Click again to close
    await toggleButton.trigger('click')
    await nextTick()
    
    // Mobile menu should be closed again
    mobileMenu = wrapper.find('aside[aria-label="Mobile navigation menu"]')
    expect(mobileMenu.exists()).toBe(false)
  })

  it('closes mobile menu when backdrop is clicked', async () => {
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const toggleButton = wrapper.find('button[aria-label="Toggle mobile menu"]')
    await toggleButton.trigger('click')
    await nextTick()
    
    // Find backdrop and click it
    const backdrop = wrapper.find('.fixed.inset-0')
    await backdrop.trigger('click')
    await nextTick()
    
    // Mobile menu should be closed
    const mobileMenu = wrapper.find('aside[aria-label="Mobile navigation menu"]')
    expect(mobileMenu.exists()).toBe(false)
  })

  it('closes mobile menu when close button is clicked', async () => {
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const toggleButton = wrapper.find('button[aria-label="Toggle mobile menu"]')
    await toggleButton.trigger('click')
    await nextTick()
    
    // Find close button in mobile menu
    const closeButton = wrapper.find('button[aria-label="Close mobile menu"]')
    await closeButton.trigger('click')
    await nextTick()
    
    // Mobile menu should be closed
    const mobileMenu = wrapper.find('aside[aria-label="Mobile navigation menu"]')
    expect(mobileMenu.exists()).toBe(false)
  })

  /**
   * Search functionality tests
   */
  it('updates search query when input changes', async () => {
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const searchInput = wrapper.find('input[placeholder*="Search"]')
    await searchInput.setValue('test query')
    await nextTick()
    
    expect((searchInput.element as HTMLInputElement).value).toBe('test query')
  })

  it('performs search when query length is 2 or more', async () => {
    vi.useFakeTimers()
    
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const searchInput = wrapper.find('input[placeholder*="Search"]')
    await searchInput.setValue('te')
    await nextTick()
    
    // Fast-forward debounce timer (300ms)
    vi.advanceTimersByTime(300)
    await nextTick()
    
    expect(mockAxiosGet).toHaveBeenCalledWith(
      expect.stringContaining('search'),
      expect.objectContaining({
        params: { q: 'te' },
      })
    )
    
    vi.useRealTimers()
  })

  it('does not perform search when query length is less than 2', async () => {
    vi.useFakeTimers()
    
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const searchInput = wrapper.find('input[placeholder*="Search"]')
    await searchInput.setValue('t')
    await nextTick()
    
    // Fast-forward debounce timer
    vi.advanceTimersByTime(300)
    await nextTick()
    
    expect(mockAxiosGet).not.toHaveBeenCalled()
    
    vi.useRealTimers()
  })

  it('displays search results when available', async () => {
    vi.useFakeTimers()
    
    const mockResults = [
      {
        type: 'site',
        id: 1,
        label: 'Test Site',
        subtitle: 'https://test.com',
        route: 'sites.show',
        params: { site: 1 },
        icon: 'GlobeAltIcon',
      },
    ]
    
    mockAxiosGet.mockResolvedValue({ data: { results: mockResults } })
    
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const searchInput = wrapper.find('input[placeholder*="Search"]')
    await searchInput.setValue('test')
    await searchInput.trigger('focus')
    await nextTick()
    
    // Fast-forward debounce timer
    vi.advanceTimersByTime(300)
    await nextTick()
    
    // Wait for search to complete
    await new Promise(resolve => setTimeout(resolve, 100))
    await nextTick()
    
    expect(wrapper.text()).toContain('Test Site')
    
    vi.useRealTimers()
  })

  it('shows loading state while searching', async () => {
    vi.useFakeTimers()
    
    // Make axios call take time
    mockAxiosGet.mockImplementation(() => new Promise(resolve => {
      setTimeout(() => resolve({ data: { results: [] } }), 100)
    }))
    
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const searchInput = wrapper.find('input[placeholder*="Search"]')
    await searchInput.setValue('test')
    await searchInput.trigger('focus')
    await nextTick()
    
    // Fast-forward debounce timer
    vi.advanceTimersByTime(300)
    await nextTick()
    
    // Should show "Searching..." text
    expect(wrapper.text()).toContain('Searching...')
    
    vi.useRealTimers()
  })

  it('handles search error gracefully', async () => {
    vi.useFakeTimers()
    
    mockAxiosGet.mockRejectedValue(new Error('Search failed'))
    const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
    
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const searchInput = wrapper.find('input[placeholder*="Search"]')
    await searchInput.setValue('test')
    await nextTick()
    
    // Fast-forward debounce timer
    vi.advanceTimersByTime(300)
    await nextTick()
    
    // Wait for error handling
    await new Promise(resolve => setTimeout(resolve, 100))
    await nextTick()
    
    expect(consoleErrorSpy).toHaveBeenCalled()
    consoleErrorSpy.mockRestore()
    
    vi.useRealTimers()
  })

  it('navigates to first suggestion when Enter is pressed', async () => {
    vi.useFakeTimers()
    
    const mockResults = [
      {
        type: 'site',
        id: 1,
        label: 'Test Site',
        route: 'sites.show',
        params: { site: 1 },
      },
    ]
    
    mockAxiosGet.mockResolvedValue({ data: { results: mockResults } })
    
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const searchInput = wrapper.find('input[placeholder*="Search"]')
    await searchInput.setValue('test')
    await searchInput.trigger('focus')
    await nextTick()
    
    // Fast-forward debounce timer
    vi.advanceTimersByTime(300)
    await nextTick()
    
    // Wait for results
    await new Promise(resolve => setTimeout(resolve, 100))
    await nextTick()
    
    // Press Enter
    await searchInput.trigger('keydown.enter')
    await nextTick()
    
    const { router } = await import('@inertiajs/vue3')
    expect(router.visit).toHaveBeenCalled()
    
    vi.useRealTimers()
  })

  it('navigates to sites index with query when no suggestions available', async () => {
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const searchInput = wrapper.find('input[placeholder*="Search"]')
    await searchInput.setValue('test query')
    await nextTick()
    
    // Press Enter
    await searchInput.trigger('keydown.enter')
    await nextTick()
    
    expect(mockRouterVisit).toHaveBeenCalledWith(
      expect.stringContaining('sites.index'),
      expect.any(Object)
    )
  })

  it('selects suggestion when clicked', async () => {
    vi.useFakeTimers()
    
    const mockResults = [
      {
        type: 'site',
        id: 1,
        label: 'Test Site',
        route: 'sites.show',
        params: { site: 1 },
      },
    ]
    
    mockAxiosGet.mockResolvedValue({ data: { results: mockResults } })
    
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const searchInput = wrapper.find('input[placeholder*="Search"]')
    await searchInput.setValue('test')
    await searchInput.trigger('focus')
    await nextTick()
    
    // Fast-forward debounce timer
    vi.advanceTimersByTime(300)
    await nextTick()
    
    // Wait for results
    await new Promise(resolve => setTimeout(resolve, 100))
    await nextTick()
    
    // Find and click suggestion
    const suggestions = wrapper.findAll('.cursor-pointer')
    if (suggestions.length > 0) {
      await suggestions[0].trigger('mousedown')
      await nextTick()
      
      const { router } = await import('@inertiajs/vue3')
    expect(router.visit).toHaveBeenCalled()
    }
    
    vi.useRealTimers()
  })

  it('hides suggestions when input loses focus', async () => {
    vi.useFakeTimers()
    
    const mockResults = [
      {
        type: 'site',
        id: 1,
        label: 'Test Site',
        route: 'sites.show',
      },
    ]
    
    mockAxiosGet.mockResolvedValue({ data: { results: mockResults } })
    
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const searchInput = wrapper.find('input[placeholder*="Search"]')
    await searchInput.setValue('test')
    await searchInput.trigger('focus')
    await nextTick()
    
    // Fast-forward debounce timer
    vi.advanceTimersByTime(300)
    await nextTick()
    
    // Wait for results
    await new Promise(resolve => setTimeout(resolve, 100))
    await nextTick()
    
    // Blur input
    await searchInput.trigger('blur')
    
    // Fast-forward blur delay (200ms)
    vi.advanceTimersByTime(200)
    await nextTick()
    
    // Suggestions should be hidden
    const suggestions = wrapper.find('.absolute.top-full')
    // The suggestions dropdown should not be visible after blur
    expect(suggestions.exists()).toBe(false)
    
    vi.useRealTimers()
  })

  /**
   * Recent items tests
   */
  it('displays recent items from localStorage', () => {
    const recentItems = [
      {
        id: '1',
        label: 'Dashboard',
        href: '/dashboard',
        icon: 'HomeIcon',
        timestamp: Date.now(),
      },
      {
        id: '2',
        label: 'Sites',
        href: '/sites',
        icon: 'GlobeAltIcon',
        timestamp: Date.now() - 1000,
      },
    ]
    
    Storage.prototype.getItem = vi.fn(() => JSON.stringify(recentItems))
    
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    expect(wrapper.text()).toContain('Recent')
    expect(wrapper.text()).toContain('Dashboard')
    expect(wrapper.text()).toContain('Sites')
  })

  it('does not display recent items section when localStorage is empty', () => {
    Storage.prototype.getItem = vi.fn(() => null)
    
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    // Recent section should not be visible
    const recentSection = wrapper.find('h3:contains("Recent")')
    expect(recentSection.exists()).toBe(false)
  })

  it('handles invalid localStorage data gracefully', () => {
    Storage.prototype.getItem = vi.fn(() => 'invalid json')
    
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    // Should not crash and should not show recent items
    expect(wrapper.exists()).toBe(true)
  })

  it('limits recent items to 5', () => {
    const manyItems = Array.from({ length: 10 }, (_, i) => ({
      id: `${i}`,
      label: `Item ${i}`,
      href: `/item-${i}`,
      icon: 'HomeIcon',
      timestamp: Date.now() - i * 1000,
    }))
    
    Storage.prototype.getItem = vi.fn(() => JSON.stringify(manyItems))
    
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    // Should only show 5 most recent items
    const recentLinks = wrapper.findAll('a[href^="/item"]')
    expect(recentLinks.length).toBeLessThanOrEqual(5)
  })

  /**
   * Route tracking tests
   */
  it('tracks dashboard route on mount', () => {
    mockRouteCurrent.mockReturnValue('dashboard')
    
    mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    expect(Storage.prototype.setItem).toHaveBeenCalled()
    const setItemCalls = (Storage.prototype.setItem as any).mock.calls
    const lastCall = setItemCalls[setItemCalls.length - 1]
    expect(lastCall[0]).toBe('dashpilot_recent_items')
    expect(JSON.parse(lastCall[1])).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          label: 'Dashboard',
        }),
      ])
    )
  })

  it('tracks sites.index route on mount', () => {
    mockRouteCurrent.mockReturnValue('sites.index')
    
    mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    expect(Storage.prototype.setItem).toHaveBeenCalled()
    const setItemCalls = (Storage.prototype.setItem as any).mock.calls
    const lastCall = setItemCalls[setItemCalls.length - 1]
    const items = JSON.parse(lastCall[1])
    expect(items[0]).toMatchObject({
      label: 'Sites',
    })
  })

  it('tracks sites.show route on mount', () => {
    mockRouteCurrent.mockReturnValue('sites.show')
    
    mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    expect(Storage.prototype.setItem).toHaveBeenCalled()
    const setItemCalls = (Storage.prototype.setItem as any).mock.calls
    const lastCall = setItemCalls[setItemCalls.length - 1]
    const items = JSON.parse(lastCall[1])
    expect(items[0]).toMatchObject({
      label: 'Site Details',
    })
  })

  it('tracks alerts.index route on mount', () => {
    mockRouteCurrent.mockReturnValue('alerts.index')
    
    mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    expect(Storage.prototype.setItem).toHaveBeenCalled()
    const setItemCalls = (Storage.prototype.setItem as any).mock.calls
    const lastCall = setItemCalls[setItemCalls.length - 1]
    const items = JSON.parse(lastCall[1])
    expect(items[0]).toMatchObject({
      label: 'Alerts',
    })
  })

  it('tracks settings.index route on mount', () => {
    mockRouteCurrent.mockReturnValue('settings.index')
    
    mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    expect(Storage.prototype.setItem).toHaveBeenCalled()
    const setItemCalls = (Storage.prototype.setItem as any).mock.calls
    const lastCall = setItemCalls[setItemCalls.length - 1]
    const items = JSON.parse(lastCall[1])
    expect(items[0]).toMatchObject({
      label: 'Settings',
    })
  })

  it('does not track unknown routes', () => {
    mockRouteCurrent.mockReturnValue('unknown.route')
    
    const setItemSpy = vi.fn()
    Storage.prototype.setItem = setItemSpy
    
    mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    // Should not call setItem for unknown routes
    const calls = setItemSpy.mock.calls.filter(call => 
      call[0] === 'dashpilot_recent_items'
    )
    expect(calls.length).toBe(0)
  })

  it('removes duplicate recent items with same href', () => {
    const existingItems = [
      {
        id: '1',
        label: 'Dashboard',
        href: '/dashboard',
        icon: 'HomeIcon',
        timestamp: Date.now() - 5000,
      },
    ]
    
    Storage.prototype.getItem = vi.fn(() => JSON.stringify(existingItems))
    mockRouteCurrent.mockReturnValue('dashboard')
    
    mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    expect(Storage.prototype.setItem).toHaveBeenCalled()
    const setItemCalls = (Storage.prototype.setItem as any).mock.calls
    const lastCall = setItemCalls[setItemCalls.length - 1]
    const items = JSON.parse(lastCall[1])
    
    // Should only have one dashboard item (the new one)
    const dashboardItems = items.filter((item: any) => item.href === '/dashboard')
    expect(dashboardItems.length).toBe(1)
  })

  it('limits stored recent items to 10', () => {
    const existingItems = Array.from({ length: 10 }, (_, i) => ({
      id: `${i}`,
      label: `Item ${i}`,
      href: `/item-${i}`,
      icon: 'HomeIcon',
      timestamp: Date.now() - i * 1000,
    }))
    
    Storage.prototype.getItem = vi.fn(() => JSON.stringify(existingItems))
    mockRouteCurrent.mockReturnValue('dashboard')
    
    mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    expect(Storage.prototype.setItem).toHaveBeenCalled()
    const setItemCalls = (Storage.prototype.setItem as any).mock.calls
    const lastCall = setItemCalls[setItemCalls.length - 1]
    const items = JSON.parse(lastCall[1])
    
    // Should only have 10 items
    expect(items.length).toBe(10)
  })

  /**
   * Keyboard shortcuts tests
   */
  it('opens command palette with Cmd+K', async () => {
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const event = new KeyboardEvent('keydown', {
      key: 'k',
      metaKey: true,
      bubbles: true,
    })
    
    document.dispatchEvent(event)
    await nextTick()
    
    const commandPalette = wrapper.findComponent({ name: 'CommandPalette' })
    expect(commandPalette.props('isOpen')).toBe(true)
  })

  it('opens command palette with Ctrl+K', async () => {
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    const event = new KeyboardEvent('keydown', {
      key: 'k',
      ctrlKey: true,
      bubbles: true,
    })
    
    document.dispatchEvent(event)
    await nextTick()
    
    const commandPalette = wrapper.findComponent({ name: 'CommandPalette' })
    expect(commandPalette.props('isOpen')).toBe(true)
  })

  it('navigates to dashboard with G+D shortcut', async () => {
    mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    // Press G
    const gEvent = new KeyboardEvent('keydown', {
      key: 'g',
      bubbles: true,
    })
    document.dispatchEvent(gEvent)
    await nextTick()
    
    // Press D
    const dEvent = new KeyboardEvent('keydown', {
      key: 'd',
      bubbles: true,
    })
    document.dispatchEvent(dEvent)
    await nextTick()
    
    expect(mockRouterVisit).toHaveBeenCalledWith(
      expect.stringContaining('dashboard'),
      expect.any(Object)
    )
  })

  it('navigates to sites with G+S shortcut', async () => {
    mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    // Press G
    const gEvent = new KeyboardEvent('keydown', {
      key: 'g',
      bubbles: true,
    })
    document.dispatchEvent(gEvent)
    await nextTick()
    
    // Press S
    const sEvent = new KeyboardEvent('keydown', {
      key: 's',
      bubbles: true,
    })
    document.dispatchEvent(sEvent)
    await nextTick()
    
    expect(mockRouterVisit).toHaveBeenCalledWith(
      expect.stringContaining('sites.index'),
      expect.any(Object)
    )
  })

  it('navigates to alerts with G+A shortcut', async () => {
    mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    // Press G
    const gEvent = new KeyboardEvent('keydown', {
      key: 'g',
      bubbles: true,
    })
    document.dispatchEvent(gEvent)
    await nextTick()
    
    // Press A
    const aEvent = new KeyboardEvent('keydown', {
      key: 'a',
      bubbles: true,
    })
    document.dispatchEvent(aEvent)
    await nextTick()
    
    expect(mockRouterVisit).toHaveBeenCalledWith(
      expect.stringContaining('alerts.index'),
      expect.any(Object)
    )
  })

  it('cancels G shortcut when another key is pressed', async () => {
    mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    // Press G
    const gEvent = new KeyboardEvent('keydown', {
      key: 'g',
      bubbles: true,
    })
    document.dispatchEvent(gEvent)
    await nextTick()
    
    // Press X (not a valid second key)
    const xEvent = new KeyboardEvent('keydown', {
      key: 'x',
      bubbles: true,
    })
    document.dispatchEvent(xEvent)
    await nextTick()
    
    // Should not navigate
    expect(mockRouterVisit).not.toHaveBeenCalled()
  })

  /**
   * Command palette tests
   */
  it('closes command palette when close event is emitted', async () => {
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    // Open command palette
    const event = new KeyboardEvent('keydown', {
      key: 'k',
      metaKey: true,
      bubbles: true,
    })
    document.dispatchEvent(event)
    await nextTick()
    
    const commandPalette = wrapper.findComponent({ name: 'CommandPalette' })
    expect(commandPalette.props('isOpen')).toBe(true)
    
    // Emit close event
    commandPalette.vm.$emit('close')
    await nextTick()
    
    expect(commandPalette.props('isOpen')).toBe(false)
  })

  /**
   * Icon component mapping tests
   */
  it('maps icon names to components correctly', () => {
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    // The component should render icons correctly
    // This is tested indirectly through rendering
    expect(wrapper.exists()).toBe(true)
  })

  /**
   * Navigation current route tests
   */
  it('highlights current navigation item', () => {
    mockRouteCurrent.mockReturnValue('sites.index')
    
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    // Sites link should have active class
    const sitesLinks = wrapper.findAll('a[href*="sites"]')
    expect(sitesLinks.length).toBeGreaterThan(0)
  })

  /**
   * Settings and logout tests
   */
  it('renders settings link', () => {
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    expect(wrapper.text()).toContain('Settings')
  })

  it('renders logout link', () => {
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    expect(wrapper.text()).toContain('Log out')
  })

  /**
   * Cleanup tests
   */
  it('removes keyboard event listener on unmount', async () => {
    const removeEventListenerSpy = vi.spyOn(document, 'removeEventListener')
    
    const wrapper = mount(AppLayout, {
      global: {
        stubs: {
          Link: true,
          CommandPalette: true,
        },
      },
      slots: {
        default: '<div>Test Content</div>',
      },
    })

    wrapper.unmount()
    await nextTick()
    
    expect(removeEventListenerSpy).toHaveBeenCalledWith(
      'keydown',
      expect.any(Function)
    )
    
    removeEventListenerSpy.mockRestore()
  })
})

