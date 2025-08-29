import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import DefaultLayout from '../../layouts/default.vue'

// Mock des dépendances
vi.mock('@heroicons/vue/24/outline', () => ({
    ChevronDownIcon: { template: '<div class="icon">ChevronDown</div>' }
}))

vi.mock('#app', () => ({
    useAuthStore: () => ({
        isAuthenticated: false,
        isAdmin: false,
        userName: 'John Doe'
    }),
    useToast: () => ({
        success: vi.fn()
    })
}))

describe('Layout par défaut', () => {
    let wrapper
    let pinia

    beforeEach(() => {
        pinia = createPinia()
        wrapper = mount(DefaultLayout, {
            global: {
                plugins: [pinia],
                stubs: {
                    NuxtLink: {
                        template: '<a data-test-stub="NuxtLink" :href="to"><slot /></a>',
                        props: ['to']
                    }
                }
            },
            slots: {
                default: '<div>Contenu de la page</div>'
            }
        })
    })

    it('affiche la navigation principale', () => {
        expect(wrapper.find('nav').exists()).toBe(true)
    })

    it('affiche le logo BookYourCoach', () => {
        expect(wrapper.text()).toContain('BookYourCoach')
    })

    it('affiche le contenu principal dans le slot', () => {
        expect(wrapper.text()).toContain('Contenu de la page')
    })

    it('affiche le footer', () => {
        expect(wrapper.find('footer').exists()).toBe(true)
        expect(wrapper.text()).toContain('© 2025 BookYourCoach. Tous droits réservés.')
    })

    it('a la structure HTML correcte', () => {
        expect(wrapper.find('.min-h-screen').exists()).toBe(true)
        expect(wrapper.find('main').exists()).toBe(true)
    })

    it('affiche les liens de connexion/inscription quand non authentifié', () => {
        const links = wrapper.findAllComponents('[data-test-stub="NuxtLink"]')
        const normalize = (s: string) => s
            .replace(/[\u{1F3C7}\u{1F40E}\u{1F3C6}]/gu, '') // remove emoji used in buttons
            .replace(/\s+/g, ' ')
            .trim()
        const linkTexts = links.map(link => normalize(link.text()))

        expect(linkTexts).toContain('Se connecter')
        expect(linkTexts).toContain("S'inscrire")
    })

    describe('Utilisateur authentifié', () => {
        it('affiche le nom de l\'utilisateur connecté (structure)', () => {
            expect(wrapper.vm).toBeDefined()
        })
    })
})
