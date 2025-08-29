import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
    plugins: [
        vue({
            template: {
                compilerOptions: {
                    isCustomElement: (tag) => ['Logo', 'LanguageSelector', 'EquestrianIcon'].includes(tag)
                }
            }
        })
    ],
    resolve: {
        alias: {
            '~': fileURLToPath(new URL('./', import.meta.url)),
            '@': fileURLToPath(new URL('./', import.meta.url))
        }
    },
    test: {
        environment: 'happy-dom',
        setupFiles: ['tests/setup.ts'],
        css: true,
        globals: true,
        coverage: {
            reporter: ['text', 'html'],
            reportsDirectory: './coverage'
        }
    }
})
