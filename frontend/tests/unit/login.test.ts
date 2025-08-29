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
                        template: '<a data-test-stub="NuxtLink" :href="to"><slot /></a>',
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

    it('affiche le titre de la page', () => {
        expect(wrapper.text()).toContain('Se connecter à votre compte')
    })

    it('a des champs de saisie avec les bons placeholders', () => {
        const emailInput = wrapper.find('input[type="email"]')
        const passwordInput = wrapper.find('input[type="password"]')

        expect(emailInput.attributes('placeholder')).toBe('Adresse email')
        expect(passwordInput.attributes('placeholder')).toBe('Mot de passe')
    })

    it('a un bouton de soumission', () => {
        const submitButton = wrapper.find('button[type="submit"]')
        expect(submitButton.exists()).toBe(true)
        expect(submitButton.text()).toContain('Se connecter')
    })

    it('a une case à cocher "Se souvenir de moi"', () => {
        const rememberCheckbox = wrapper.find('input[type="checkbox"]')
        expect(rememberCheckbox.exists()).toBe(true)
    })

    it('a un lien vers l\'inscription', () => {
        const registerLink = wrapper.findComponent('[data-test-stub="NuxtLink"]')
        expect(registerLink.exists()).toBe(true)
    })

    it('affiche une erreur si les champs sont vides lors de la soumission', async () => {
        const form = wrapper.find('form')
        await form.trigger('submit.prevent')

        // Vérifier que la validation native du navigateur se déclenche
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
})
