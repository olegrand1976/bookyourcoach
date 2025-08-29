import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import IndexPage from '../../pages/index.vue'

// Mocks pour les composants Nuxt
vi.mock('@heroicons/vue/24/outline', () => ({
    UserGroupIcon: { template: '<div class="icon">UserGroup</div>' },
    CalendarIcon: { template: '<div class="icon">Calendar</div>' },
    CreditCardIcon: { template: '<div class="icon">CreditCard</div>' }
}))

vi.mock('#app', () => ({
    useHead: vi.fn(),
    useAuthStore: () => ({
        isAuthenticated: false
    }),
    navigateTo: vi.fn(),
    watchEffect: vi.fn((fn) => fn())
}))

describe('Page d\'accueil', () => {
    let wrapper
    let pinia

    beforeEach(() => {
        pinia = createPinia()
        wrapper = mount(IndexPage, {
            global: {
                plugins: [pinia],
                stubs: {
                    NuxtLink: {
                        template: '<a data-test-stub="NuxtLink" :href="to"><slot /></a>',
                        props: ['to']
                    }
                }
            }
        })
    })

    it('affiche le titre principal', () => {
        expect(wrapper.text()).toContain('Trouvez votre instructeur équestre idéal')
    })

    it('affiche la section des fonctionnalités', () => {
        expect(wrapper.text()).toContain('Pourquoi choisir BookYourCoach ?')
        expect(wrapper.text()).toContain('Instructeurs Certifiés')
        expect(wrapper.text()).toContain('Réservation Flexible')
        expect(wrapper.text()).toContain('Sécurité Garantie')
    })

    it('affiche les statistiques de la plateforme', () => {
        expect(wrapper.text()).toContain('150+')  // Coaches
        expect(wrapper.text()).toContain('2500+')  // Students
        expect(wrapper.text()).toContain('8500+')  // Lessons
        expect(wrapper.text()).toContain('45+')   // Locations
    })

    it('contient les boutons d\'action appropriés', () => {
        const links = wrapper.findAllComponents('[data-test-stub="NuxtLink"]')
        const normalize = (s: string) => s.replace(/[\u{1F3C7}\u{1F40E}\u{1F3C6}]/gu, '').replace(/\s+/g, ' ').trim()
        const linkTexts = links.map(link => normalize(link.text()))

        expect(linkTexts).toContain("Commencer l'aventure")
        expect(linkTexts).toContain('Découvrir les coaches')
        expect(linkTexts).toContain("S'inscrire Gratuitement")
    })

    it('a la structure de section correcte', () => {
        const sections = wrapper.findAll('section')
        expect(sections.length).toBeGreaterThanOrEqual(4) // Hero, Features, Stats, CTA
    })
})
