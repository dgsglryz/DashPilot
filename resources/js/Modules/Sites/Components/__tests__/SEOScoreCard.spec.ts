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
      seoIssues: ['Missing meta description', 'No SSL'],
    }

    const wrapper = mount(SEOScoreCard, {
      props: {
        site: siteWithIssues,
      },
    })

    expect(wrapper.text()).toContain('Missing meta description')
  })
})

