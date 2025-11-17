import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { router } from '@inertiajs/vue3'
import Index from '../Index.vue'

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
  router: {
    visit: vi.fn(),
    get: vi.fn(),
    post: vi.fn(),
  },
  route: (globalThis as any).route || vi.fn(),
  Link: {
    name: 'Link',
    template: '<a><slot /></a>',
    props: ['href'],
  },
}))

// Mock components
vi.mock('@/Shared/Layouts/AppLayout.vue', () => ({
  default: {
    name: 'AppLayout',
    template: '<div><slot /></div>',
  },
}))

vi.mock('@/Shared/Components/QuickActionsDropdown.vue', () => ({
  default: {
    name: 'QuickActionsDropdown',
    template: '<div></div>',
    props: ['siteId', 'siteUrl', 'isFavorited'],
    emits: ['favorite-toggled'],
  },
}))

vi.mock('@/Shared/Components/Breadcrumbs.vue', () => ({
  default: {
    name: 'Breadcrumbs',
    template: '<div></div>',
    props: ['items'],
  },
}))

vi.mock('@/Shared/Components/Pagination.vue', () => ({
  default: {
    name: 'Pagination',
    template: '<div></div>',
    props: ['links', 'from', 'to', 'total'],
  },
}))

describe('Sites Index', () => {
  const defaultProps = {
    sites: {
      data: [
        {
          id: 1,
          name: 'Test Site',
          url: 'https://test.com',
          platform: 'wordpress',
          status: 'healthy',
          uptime: 99.5,
          responseTime: 200,
          lastChecked: new Date().toISOString(),
          is_favorited: false,
        },
      ],
    },
    stats: {
      healthy: 10,
      warning: 2,
      critical: 1,
      total: 13,
    },
    filters: {
      query: '',
      platform: 'all',
      status: 'all',
    },
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders page title', () => {
    const wrapper = mount(Index, {
      props: defaultProps,
      global: {
        stubs: {
          AppLayout: true,
          Link: true,
          QuickActionsDropdown: true,
          Breadcrumbs: true,
          Pagination: true,
        },
      },
    })

    expect(wrapper.text()).toContain('Sites')
  })

  it('displays site statistics', () => {
    const wrapper = mount(Index, {
      props: defaultProps,
      global: {
        stubs: {
          AppLayout: true,
          Link: true,
          QuickActionsDropdown: true,
          Breadcrumbs: true,
          Pagination: true,
        },
      },
    })

    expect(wrapper.text()).toContain('10')
    expect(wrapper.text()).toContain('2')
    expect(wrapper.text()).toContain('1')
    expect(wrapper.text()).toContain('13')
  })

  it('renders sites table', () => {
    const wrapper = mount(Index, {
      props: defaultProps,
      global: {
        stubs: {
          AppLayout: true,
          Link: true,
          QuickActionsDropdown: true,
          Breadcrumbs: true,
          Pagination: true,
        },
      },
    })

    expect(wrapper.find('[data-testid="sites-table"]').exists()).toBe(true)
  })

  it('displays site name in table', () => {
    const wrapper = mount(Index, {
      props: defaultProps,
      global: {
        stubs: {
          AppLayout: true,
          Link: true,
          QuickActionsDropdown: true,
          Breadcrumbs: true,
          Pagination: true,
        },
      },
    })

    expect(wrapper.text()).toContain('Test Site')
    expect(wrapper.text()).toContain('https://test.com')
  })

  it('renders search input', () => {
    const wrapper = mount(Index, {
      props: defaultProps,
      global: {
        stubs: {
          AppLayout: true,
          Link: true,
          QuickActionsDropdown: true,
          Breadcrumbs: true,
          Pagination: true,
        },
      },
    })

    expect(wrapper.find('[data-testid="search-input"]').exists()).toBe(true)
  })

  it('renders add site button', () => {
    const wrapper = mount(Index, {
      props: defaultProps,
      global: {
        stubs: {
          AppLayout: true,
          Link: true,
          QuickActionsDropdown: true,
          Breadcrumbs: true,
          Pagination: true,
        },
      },
    })

    expect(wrapper.find('[data-testid="add-site-button"]').exists()).toBe(true)
  })
})

