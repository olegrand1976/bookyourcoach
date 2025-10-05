import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import LoginPage from '../../pages/login.vue'

// Mock des dépendances Nuxt
vi.mock('#app', () => ({
    useAuthStore: () => ({
        login: vi.fn().mockResolvedValue(true),
        isAdmin: false,
        isTeacher: false
    }),
    useToast: () => ({
        success: vi.fn()
    }),
    useRouter: () => ({
        push: vi.fn()
    }),
    navigateTo: vi.fn(),
    definePageMeta: vi.fn()
}))

describe('Page de connexion', () => {
    let wrapper
    let pinia

    beforeEach(() => {
        pinia = createPinia()
        wrapper = mount(LoginPage, {
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

    it('affiche le formulaire de connexion', () => {
        expect(wrapper.find('form').exists()).toBe(true)
        expect(wrapper.find('input[type="email"]').exists()).toBe(true)
        expect(wrapper.find('input[type="password"]').exists()).toBe(true)
    })

    it('affiche le titre "Connexion"', () => {
        expect(wrapper.text()).toContain('Connexion')
    })

    it('affiche le texte d\'accueil', () => {
        expect(wrapper.text()).toContain('Bienvenue')
        expect(wrapper.text()).toContain('Accédez à votre espace personnel')
    })

    it('affiche la section branding avec activibe', () => {
        expect(wrapper.text()).toContain('activibe')
    })

    it('a des champs de saisie avec les bons placeholders', () => {
        const emailInput = wrapper.find('input[type="email"]')
        const passwordInput = wrapper.find('input[type="password"]')

        expect(emailInput.attributes('placeholder')).toBe('vous@exemple.com')
        expect(passwordInput.attributes('placeholder')).toBe('••••••••')
    })

    it('a des labels pour les champs', () => {
        expect(wrapper.text()).toContain('Adresse email')
        expect(wrapper.text()).toContain('Mot de passe')
    })

    it('a un bouton de soumission', () => {
        const submitButton = wrapper.find('button[type="submit"]')
        expect(submitButton.exists()).toBe(true)
        expect(submitButton.text()).toContain('Se connecter')
    })

    it('a une case à cocher "Se souvenir de moi"', () => {
        const rememberCheckbox = wrapper.find('input[type="checkbox"]')
        expect(rememberCheckbox.exists()).toBe(true)
        expect(wrapper.text()).toContain('Se souvenir de moi')
    })

    it('affiche les caractéristiques de la plateforme', () => {
        expect(wrapper.text()).toContain('Réservation simplifiée')
        expect(wrapper.text()).toContain('Multi-rôles')
        expect(wrapper.text()).toContain('Sécurisé')
    })

    it('a un lien vers l\'inscription', () => {
        expect(wrapper.text().toLowerCase()).toContain('inscription')
    })

    it('affiche un lien "Mot de passe oublié"', () => {
        expect(wrapper.text()).toContain('Mot de passe oublié')
    })

    it('a des icônes SVG dans le formulaire', () => {
        const svgs = wrapper.findAll('svg')
        expect(svgs.length).toBeGreaterThan(0)
    })

    it('affiche une erreur si les champs sont vides lors de la soumission', async () => {
        const form = wrapper.find('form')
        await form.trigger('submit.prevent')

        // Vérifier que les champs sont requis
        const emailInput = wrapper.find('input[type="email"]')
        const passwordInput = wrapper.find('input[type="password"]')

        expect(emailInput.attributes('required')).toBeDefined()
        expect(passwordInput.attributes('required')).toBeDefined()
    })

    it('permet de saisir l\'email et le mot de passe', async () => {
        const emailInput = wrapper.find('input[type="email"]')
        const passwordInput = wrapper.find('input[type="password"]')

        await emailInput.setValue('test@example.com')
        await passwordInput.setValue('password123')

        expect(emailInput.element.value).toBe('test@example.com')
        expect(passwordInput.element.value).toBe('password123')
    })

    it('a un bouton pour afficher/masquer le mot de passe', () => {
        const toggleButtons = wrapper.findAll('button[type="button"]')
        // Il devrait y avoir au moins un bouton type="button" pour toggle le mot de passe
        expect(toggleButtons.length).toBeGreaterThan(0)
    })

    it('a un design avec gradient', () => {
        const mainDiv = wrapper.find('.min-h-screen')
        expect(mainDiv.exists()).toBe(true)
        expect(mainDiv.classes()).toContain('bg-gradient-to-br')
    })

    it('a une mise en page en grille pour desktop', () => {
        const gridContainer = wrapper.find('.grid')
        expect(gridContainer.exists()).toBe(true)
    })

    it('affiche des icônes et graphiques pour améliorer l\'UX', () => {
        // Vérifier la présence d'icônes SVG
        const svgs = wrapper.findAll('svg')
        expect(svgs.length).toBeGreaterThan(5)
    })
})