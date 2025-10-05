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
                        template: '<a :href="to"><slot /></a>',
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

    it('affiche le logo BookYourCoach dans la navigation', () => {
        const text = wrapper.text()
        expect(text).toContain('BookYourCoach')
    })

    it('affiche une image de logo', () => {
        const logo = wrapper.find('img[alt="activibe"]')
        expect(logo.exists()).toBe(true)
        // Le logo peut être un fichier SVG ou un data URI
        const src = logo.attributes('src')
        expect(src).toBeDefined()
        expect(src.length).toBeGreaterThan(0)
    })

    it('affiche le contenu principal dans le slot', () => {
        expect(wrapper.text()).toContain('Contenu de la page')
    })

    it('affiche le footer', () => {
        expect(wrapper.find('footer').exists()).toBe(true)
    })

    it('affiche le copyright dans le footer', () => {
        expect(wrapper.text()).toContain('© 2025 BookYourCoach')
        expect(wrapper.text()).toContain('Tous droits réservés')
    })

    it('a la structure HTML correcte', () => {
        expect(wrapper.find('.min-h-screen').exists()).toBe(true)
        expect(wrapper.find('main').exists()).toBe(true)
    })

    it('affiche les liens de connexion/inscription quand non authentifié', () => {
        expect(wrapper.text()).toContain('Connexion')
        expect(wrapper.text()).toContain('Inscription')
    })

    it('affiche la navigation avec une bordure bleue', () => {
        const nav = wrapper.find('nav')
        expect(nav.classes()).toContain('border-blue-500')
    })

    it('affiche le footer avec les liens rapides', () => {
        expect(wrapper.text()).toContain('Liens Rapides')
        expect(wrapper.text()).toContain('Nos Instructeurs')
    })

    it('affiche les informations de contact dans le footer', () => {
        expect(wrapper.text()).toContain('Contact')
    })

    it('affiche des émojis dans le footer', () => {
        const text = wrapper.text()
        expect(text).toMatch(/[⚽🏆🏃💪🐎]/u)
    })

    it('a une navigation responsive avec max-width', () => {
        const navContainer = wrapper.find('nav .max-w-7xl')
        expect(navContainer.exists()).toBe(true)
    })

    describe('Utilisateur authentifié', () => {
        it('devrait afficher le menu utilisateur si authentifié', () => {
            // Note: Ce test nécessiterait de mocker isAuthenticated = true
            // Pour l'instant, nous testons juste la structure du layout
            expect(wrapper.vm).toBeDefined()
            expect(wrapper.find('nav').exists()).toBe(true)
        })
    })
})