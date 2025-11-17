import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath } from 'url'

export default defineConfig({
  plugins: [vue()],
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./tests/setup.ts'],
    // Only run unit tests (spec.ts files), exclude e2e tests
    include: ['**/__tests__/**/*.spec.ts', 'resources/js/**/*.spec.ts'],
    exclude: ['node_modules', 'tests/e2e/**', '**/*.e2e.spec.ts'],
    // Coverage disabled - only backend coverage is tracked
    // Frontend tests run but coverage is not calculated
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
    },
  },
})

