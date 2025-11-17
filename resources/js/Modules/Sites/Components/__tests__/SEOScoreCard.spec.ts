import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import SEOScoreCard from '../SEOScoreCard.vue'

describe('SEOScoreCard', () => {
  const mockSite = {
    id: 1,
    name: 'Test Site',
    seoScore: 85,
    seoMetrics: {
      metaTags: true,
      h1: true,
      ssl: true,
    },
    seoIssues: [],
  }

  it('renders SEO score', () => {
    const wrapper = mount(SEOScoreCard, {
      props: {
        site: mockSite,
      },
    })

    expect(wrapper.text()).toContain('85')
  })

  it('displays SEO issues when present', () => {
    const siteWithIssues = {
      ...mockSite,
      seoIssues: [
        { id: 1, title: 'Missing meta description', description: 'Add meta description' },
        { id: 2, title: 'No SSL', description: 'Site should use HTTPS' },
      ],
    }

    const wrapper = mount(SEOScoreCard, {
      props: {
        site: siteWithIssues,
      },
      global: {
        stubs: {
          HealthScoreModal: true,
        },
      },
    })

    expect(wrapper.text()).toContain('Missing meta description')
  })
})

