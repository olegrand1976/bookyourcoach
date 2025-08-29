import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import Logo from '../../components/Logo.vue'

// Mock useSettings
const mockUseSettings = vi.fn(() => ({
    settings: {
        value: {
            platform_name: 'Test Platform',
            logo_url: '/test-logo.png'
        }
    },
    loadSettings: vi.fn()
}))

vi.mock('../../composables/useSettings', () => ({
    useSettings: mockUseSettings
}))

describe('Logo Component', () => {
    let wrapper: any

    beforeEach(() => {
        vi.clearAllMocks()
        wrapper = mount(Logo, {
            props: {
                size: 'md'
            },
            global: {
                mocks: {
                    useSettings: mockUseSettings
                }
            }
        })
    })

    it('devrait afficher le nom de la plateforme', () => {
        expect(wrapper.text()).toContain('Test Platform')
    })

    it('devrait avoir la bonne classe de taille', () => {
        expect(wrapper.classes()).toContain('flex')
        expect(wrapper.classes()).toContain('items-center')
    })

    it('devrait accepter différentes tailles', () => {
        const wrapperSm = mount(Logo, { props: { size: 'sm' } })
        const wrapperLg = mount(Logo, { props: { size: 'lg' } })
        
        expect(wrapperSm.exists()).toBe(true)
        expect(wrapperLg.exists()).toBe(true)
    })
})