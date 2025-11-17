import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import MessagePopup from '../MessagePopup.vue'

// Mock fetch
;(globalThis as any).fetch = vi.fn(() =>
  Promise.resolve({
    ok: true,
    json: () => Promise.resolve({ messages: [] }),
  } as Response)
)

describe('MessagePopup', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders when open', () => {
    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient: null,
      },
    })

    expect(wrapper.find('.fixed').exists()).toBe(true)
  })

  it('does not render when closed', () => {
    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: false,
        recipient: null,
      },
    })

    expect(wrapper.find('.fixed').exists()).toBe(false)
  })

  it('displays recipient name when provided', () => {
    const recipient = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
    }

    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient,
      },
    })

    expect(wrapper.text()).toContain('Test User')
    expect(wrapper.text()).toContain('test@example.com')
  })

  it('displays "Messages" when no recipient', () => {
    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient: null,
      },
    })

    expect(wrapper.text()).toContain('Messages')
  })

  it('shows loading state', () => {
    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient: { id: 1, name: 'Test', email: 'test@example.com' },
      },
      data() {
        return {
          loading: true,
        }
      },
    })

    expect(wrapper.text()).toContain('Loading messages...')
  })

  it('shows empty state when no messages', () => {
    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient: { id: 1, name: 'Test', email: 'test@example.com' },
      },
      data() {
        return {
          loading: false,
          messages: [],
        }
      },
    })

    expect(wrapper.text()).toContain('No messages yet')
  })

  it('emits close event when close button clicked', async () => {
    const wrapper = mount(MessagePopup, {
      props: {
        isOpen: true,
        recipient: null,
      },
    })

    const closeButton = wrapper.find('button')
    await closeButton.trigger('click')

    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
