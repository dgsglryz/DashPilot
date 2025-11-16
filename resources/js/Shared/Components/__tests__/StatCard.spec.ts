import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import StatCard from '../StatCard.vue'

describe('StatCard', () => {
  it('renders title and value', () => {
    const wrapper = mount(StatCard, {
      props: {
        title: 'Test Title',
        value: 100,
        total: 200,
        label: 'items',
      },
    })

    expect(wrapper.text()).toContain('Test Title')
    expect(wrapper.text()).toContain('100')
    expect(wrapper.text()).toContain('200')
  })

  it('renders as link when href is provided', () => {
    const wrapper = mount(StatCard, {
      props: {
        title: 'Test',
        value: 10,
        href: '/test',
      },
    })

    expect(wrapper.find('a').exists()).toBe(true)
    expect(wrapper.find('a').attributes('href')).toBe('/test')
  })

  it('renders as div when href is not provided', () => {
    const wrapper = mount(StatCard, {
      props: {
        title: 'Test',
        value: 10,
      },
    })

    expect(wrapper.find('div').exists()).toBe(true)
    expect(wrapper.find('a').exists()).toBe(false)
  })
})

