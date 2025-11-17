/**
 * Tests for HealthScoreModal component
 */
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import HealthScoreModal from '../HealthScoreModal.vue'

describe('HealthScoreModal', () => {
  const mockBreakdown = [
    {
      issue: 'Missing meta description',
      points: -10,
      description: 'Meta description is required for SEO',
    },
    {
      issue: 'No H1 tag',
      points: -15,
      description: 'Exactly one H1 tag is required',
    },
    {
      issue: 'SSL enabled',
      points: 0,
      description: 'Site uses HTTPS',
    },
  ]

  it('renders when isOpen is true', () => {
    const wrapper = mount(HealthScoreModal, {
      props: {
        isOpen: true,
        score: 75,
        breakdown: mockBreakdown,
        siteName: 'Test Site',
      },
    })

    expect(wrapper.find('[class*="fixed"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Health Score Breakdown')
    expect(wrapper.text()).toContain('Test Site')
  })

  it('does not render when isOpen is false', () => {
    const wrapper = mount(HealthScoreModal, {
      props: {
        isOpen: false,
        score: 75,
        breakdown: mockBreakdown,
      },
    })

    expect(wrapper.find('[class*="fixed"]').exists()).toBe(false)
  })

  it('displays score correctly', () => {
    const wrapper = mount(HealthScoreModal, {
      props: {
        isOpen: true,
        score: 85,
        breakdown: mockBreakdown,
      },
    })

    expect(wrapper.text()).toContain('85/100')
    expect(wrapper.text()).toContain('100')
  })

  it('displays breakdown items', () => {
    const wrapper = mount(HealthScoreModal, {
      props: {
        isOpen: true,
        score: 75,
        breakdown: mockBreakdown,
      },
    })

    expect(wrapper.text()).toContain('Missing meta description')
    expect(wrapper.text()).toContain('No H1 tag')
    expect(wrapper.text()).toContain('-10')
    expect(wrapper.text()).toContain('-15')
  })

  it('emits close event when close button is clicked', async () => {
    const wrapper = mount(HealthScoreModal, {
      props: {
        isOpen: true,
        score: 75,
        breakdown: mockBreakdown,
      },
    })

    const closeButton = wrapper.findAll('button').find(btn => 
      btn.text().includes('Close') || btn.find('svg').exists()
    )
    
    if (closeButton) {
      await closeButton.trigger('click')
      expect(wrapper.emitted('close')).toBeTruthy()
    }
  })

  it('handles empty breakdown array', () => {
    const wrapper = mount(HealthScoreModal, {
      props: {
        isOpen: true,
        score: 100,
        breakdown: [],
      },
    })

    expect(wrapper.text()).toContain('No breakdown available')
  })

  it('uses default siteName when not provided', () => {
    const wrapper = mount(HealthScoreModal, {
      props: {
        isOpen: true,
        score: 75,
        breakdown: mockBreakdown,
      },
    })

    expect(wrapper.text()).toContain('Site')
  })
})


