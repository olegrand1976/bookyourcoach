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
                        template: '<a :href="to"><slot /></a>',
                        props: ['to']
                    }
                }
            }
        })
    })

    it('affiche le titre principal de la Hero section', () => {
        expect(wrapper.text()).toContain('Natation & Fitness')
        expect(wrapper.text()).toContain('réservez vos cours en un clic')
    })

    it('affiche la description de la plateforme', () => {
        expect(wrapper.text()).toContain('La plateforme de référence pour réserver vos cours')
    })

    it('affiche les boutons d\'action principaux', () => {
        expect(wrapper.text()).toContain('Commencer gratuitement')
        expect(wrapper.text()).toContain('Connexion')
    })

    it('affiche les statistiques de la plateforme', () => {
        expect(wrapper.text()).toContain('2,500+')  // Élèves actifs
        expect(wrapper.text()).toContain('150+')    // Clubs partenaires
        expect(wrapper.text()).toContain('50,000+') // Cours réservés
        expect(wrapper.text()).toContain('Élèves actifs')
        expect(wrapper.text()).toContain('Clubs partenaires')
        expect(wrapper.text()).toContain('Cours réservés')
    })

    it('affiche la section "Comment ça marche"', () => {
        expect(wrapper.text()).toContain('Comment ça marche')
        expect(wrapper.text()).toContain('Réservez vos cours en 3 étapes simples')
    })

    it('affiche les 3 étapes de réservation', () => {
        expect(wrapper.text()).toContain('Trouvez votre club')
        expect(wrapper.text()).toContain('Réservez en ligne')
        expect(wrapper.text()).toContain('Profitez de votre cours')
    })

    it('affiche la section des spécialités', () => {
        expect(wrapper.text()).toContain('Nos spécialités')
        expect(wrapper.text()).toContain('Natation et fitness')
    })

    it('affiche les cartes Natation et Fitness', () => {
        expect(wrapper.text()).toContain('Natation')
        expect(wrapper.text()).toContain('Fitness')
        expect(wrapper.text()).toContain('Aquagym')
        expect(wrapper.text()).toContain('Musculation')
    })

    it('contient au moins 4 sections principales', () => {
        const sections = wrapper.findAll('section')
        expect(sections.length).toBeGreaterThanOrEqual(4)
    })

    it('a la structure de page correcte', () => {
        expect(wrapper.find('.min-h-screen').exists()).toBe(true)
    })

    it('affiche des icônes et émojis pour améliorer l\'UX', () => {
        // Vérifie la présence d'émojis dans le contenu
        const text = wrapper.text()
        expect(text).toMatch(/[🏊💪🏆🏃]/u) // Au moins un emoji sport
    })
})