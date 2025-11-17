import { expect, afterEach, vi, beforeEach } from 'vitest'
import * as matchers from '@testing-library/jest-dom/matchers'

expect.extend(matchers)

// Mock Inertia route function globally
;(globalThis as any).route = vi.fn((name: string, params?: any) => {
  if (params) {
    return `/${name}/${params}`
  }
  return `/${name}`
})

// Mock fetch globally
;(globalThis as any).fetch = vi.fn(() =>
  Promise.resolve({
    ok: true,
    json: () => Promise.resolve({ data: [] }),
  } as Response)
)

// Mock localStorage
beforeEach(() => {
  Storage.prototype.getItem = vi.fn(() => null)
  Storage.prototype.setItem = vi.fn()
  Storage.prototype.removeItem = vi.fn()
})

afterEach(() => {
  vi.clearAllMocks()
})

