import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath } from 'url'

export default defineConfig({
  plugins: [vue()],
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./tests/setup.ts'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html', 'lcov'],
      exclude: [
        'node_modules/',
        'tests/',
        '**/*.spec.ts',
        '**/*.test.ts',
        '**/__tests__/**',
        'vite.config.*',
        'vitest.config.*',
        'tailwind.config.*',
        'postcss.config.*',
        'eslint.config.*',
        'playwright.config.*',
      ],
      include: ['resources/js/**/*.{ts,vue}'],
    },
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
    },
  },
})

