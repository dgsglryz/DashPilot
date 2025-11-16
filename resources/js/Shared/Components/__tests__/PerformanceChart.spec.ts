import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import PerformanceChart from '../PerformanceChart.vue'

describe('PerformanceChart', () => {
  const mockSeries = {
    labels: ['Jan', 'Feb', 'Mar'],
    datasets: [
      {
        label: 'Uptime %',
        data: [99.5, 99.8, 99.2],
        borderColor: 'rgb(16, 185, 129)',
        backgroundColor: 'rgba(16, 185, 129, 0.15)',
      },
      {
        label: 'Response (ms)',
        data: [120, 115, 130],
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.15)',
      },
    ],
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
          datasets: [],
        },
      },
    })

    expect(wrapper.exists()).toBe(true)
  })
})

