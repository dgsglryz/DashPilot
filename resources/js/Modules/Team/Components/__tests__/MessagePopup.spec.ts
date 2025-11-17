/**
 * Tests for MessagePopup component
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import MessagePopup from '../MessagePopup.vue'

// Mock fetch
global.fetch = vi.fn()

// Mock window methods
window.scrollTo = vi.fn()

describe('MessagePopup', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    ;(fetch as any).mockResolvedValue({
      ok: true,
      json: async () => ({ messages: [] }),
    })
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('renders when isOpen is true', () => {
    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient: null,
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

    expect(wrapper.find('[class*="fixed"]').exists()).toBe(true)
  })

  it('does not render when isOpen is false', () => {
    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: false,
        recipient: null,
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

    expect(wrapper.find('[class*="fixed"]').exists()).toBe(false)
  })

  it('displays recipient name when provided', () => {
    const recipient = {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
    }

    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    expect(wrapper.text()).toContain('John Doe')
    expect(wrapper.text()).toContain('john@example.com')
  })

  it('displays "Messages" when no recipient', () => {
    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient: null,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    expect(wrapper.text()).toContain('Messages')
  })

  it('loads messages when recipient is provided', async () => {
    const recipient = {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
    }

    const mockMessages = [
      {
        id: 1,
        content: 'Hello',
        is_sender: true,
        created_at: new Date().toISOString(),
      },
      {
        id: 2,
        content: 'Hi there',
        is_sender: false,
        created_at: new Date().toISOString(),
      },
    ]

    ;(fetch as any).mockResolvedValue({
      ok: true,
      json: async () => ({ messages: mockMessages }),
    })

    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient,
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
    await new Promise(resolve => setTimeout(resolve, 100))

    expect(fetch).toHaveBeenCalledWith(
      expect.stringContaining(`/messages/conversation/${recipient.id}`),
      expect.any(Object)
    )
  })

  it('displays loading state', async () => {
    const recipient = {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
    }

    ;(fetch as any).mockImplementation(() => new Promise(() => {}))

    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient,
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

    expect(wrapper.text()).toContain('Loading messages')
  })

  it('displays empty state when no messages', async () => {
    const recipient = {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
    }

    ;(fetch as any).mockResolvedValue({
      ok: true,
      json: async () => ({ messages: [] }),
    })

    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient,
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
    await new Promise(resolve => setTimeout(resolve, 100))

    expect(wrapper.text()).toContain('No messages yet')
  })

  it('sends message when form is submitted', async () => {
    const recipient = {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
    }

    const mockResponse = {
      id: 3,
      content: 'New message',
      is_sender: true,
      created_at: new Date().toISOString(),
    }

    ;(fetch as any)
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ messages: [] }),
      })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ message: mockResponse }),
      })

    // Mock CSRF token
    document.querySelector = vi.fn(() => ({
      getAttribute: () => 'csrf-token',
    })) as any

    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient,
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
    await new Promise(resolve => setTimeout(resolve, 100))

    const textarea = wrapper.find('textarea')
    await textarea.setValue('New message')

    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 100))

    expect(fetch).toHaveBeenCalledWith(
      '/messages/send',
      expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('New message'),
      })
    )
  })

  it('emits close event when close button is clicked', async () => {
    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient: null,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    const closeButton = wrapper.findAll('button').find(btn => 
      btn.find('svg').exists()
    )
    
    if (closeButton) {
      await closeButton.trigger('click')
      expect(wrapper.emitted('close')).toBeTruthy()
    }
  })

  it('formats initials correctly', () => {
    const recipient = {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
    }

    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient,
      },
      global: {
        stubs: {
          Transition: {
            template: '<div><slot /></div>',
          },
        },
      },
    })

    expect(wrapper.text()).toContain('JD')
  })
})


