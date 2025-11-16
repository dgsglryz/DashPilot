import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import PerformanceChart from '../PerformanceChart.vue'

describe('PerformanceChart', () => {
  const mockSeries = {
    labels: ['Jan', 'Feb', 'Mar'],
    uptime: [99.5, 99.8, 99.2],
    response: [120, 115, 130],
  }

  it('renders chart with provided data', () => {
    const wrapper = mount(PerformanceChart, {
      props: {
        series: mockSeries,
      },
    })

    expect(wrapper.exists()).toBe(true)
  })

  it('handles empty data gracefully', () => {
    const wrapper = mount(PerformanceChart, {
      props: {
        series: {
          labels: [],
          uptime: [],
          response: [],
        },
      },
    })

    expect(wrapper.exists()).toBe(true)
  })
})

