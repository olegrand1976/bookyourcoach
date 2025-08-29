import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useSettings } from '../../composables/useSettings'

// Mock Nuxt composables
vi.mock('#app', () => ({
    useNuxtApp: () => ({
        $api: {
            get: vi.fn(),
            put: vi.fn()
        }
    })
}))

describe('useSettings Composable', () => {
    let settings: any

    beforeEach(() => {
        // Reset mocks
        vi.clearAllMocks()
        settings = useSettings()
    })

    it('devrait avoir des valeurs par défaut correctes', () => {
        expect(settings.settings.value.platform_name).toBe('BookYourCoach')
        expect(settings.settings.value.contact_email).toBe('contact@bookyourcoach.fr')
        expect(settings.settings.value.timezone).toBe('Europe/Brussels')
    })

    it('devrait avoir une fonction loadSettings', () => {
        expect(typeof settings.loadSettings).toBe('function')
    })

    it('devrait avoir une fonction saveSettings', () => {
        expect(typeof settings.saveSettings).toBe('function')
    })

    it('devrait retourner un objet settings réactif', () => {
        expect(settings.settings).toBeDefined()
        expect(settings.settings.value).toBeDefined()
        expect(typeof settings.settings.value.platform_name).toBe('string')
    })
})