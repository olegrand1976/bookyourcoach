import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath } from 'node:url'
import { resolve } from 'node:path'

export default defineConfig({
    plugins: [vue()],
    test: {
        environment: 'happy-dom',
        globals: true,
        setupFiles: ['./tests/setup.ts'],
        include: [
            'tests/unit/**/*.test.ts',
            'tests/unit/**/*.spec.ts'
        ],
        exclude: [
            '**/node_modules/**',
            '**/dist/**',
            '**/e2e/**'
        ],
        css: true
    }
})
