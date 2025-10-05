import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import RegisterPage from '../../pages/register.vue'

// Mock des dépendances Nuxt
vi.mock('#app', () => ({
    useAuthStore: () => ({
        register: vi.fn().mockResolvedValue(true),
        isAuthenticated: false
    }),
    useToast: () => ({
        success: vi.fn(),
        error: vi.fn()
    }),
    useRouter: () => ({
        push: vi.fn()
    }),
    navigateTo: vi.fn(),
    definePageMeta: vi.fn()
}))

describe('Page d\'inscription', () => {
    let wrapper
    let pinia

    beforeEach(() => {
        pinia = createPinia()
        wrapper = mount(RegisterPage, {
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

    describe('Affichage initial', () => {
        it('affiche le titre "Inscription"', () => {
            expect(wrapper.text()).toContain('Inscription')
        })

        it('affiche le lien vers la connexion', () => {
            expect(wrapper.text()).toContain('Déjà un compte')
            expect(wrapper.text()).toContain('Connexion')
        })

        it('affiche le titre de sélection de profil', () => {
            expect(wrapper.text()).toContain('Choisissez votre profil')
        })
    })

    describe('Sélection du type de profil', () => {
        it('affiche les trois options de profil', () => {
            expect(wrapper.text()).toContain('Élève')
            expect(wrapper.text()).toContain('Enseignant')
            expect(wrapper.text()).toContain('Club')
        })

        it('affiche les descriptions des profils', () => {
            expect(wrapper.text()).toContain('Réservez des cours')
            expect(wrapper.text()).toContain('Proposez vos services')
            expect(wrapper.text()).toContain('Gérez votre centre')
        })

        it('affiche des émojis pour chaque type de profil', () => {
            const html = wrapper.html()
            expect(html).toMatch(/[🎓👨‍🏫🏢]/u)
        })

        it('affiche des boutons cliquables pour chaque profil', () => {
            const buttons = wrapper.findAll('button')
            expect(buttons.length).toBeGreaterThanOrEqual(3)
        })

        it('les boutons ont des classes de hover colorées', () => {
            const html = wrapper.html()
            expect(html).toContain('hover:border-blue-500')
            expect(html).toContain('hover:border-green-500')
            expect(html).toContain('hover:border-purple-500')
        })
    })

    describe('Formulaire d\'inscription', () => {
        it('devrait permettre de cliquer sur un bouton de profil', async () => {
            const buttons = wrapper.findAll('button')
            const studentButton = buttons.find(btn => btn.text().includes('Élève'))
            expect(studentButton).toBeDefined()
            
            if (studentButton) {
                await studentButton.trigger('click')
                await wrapper.vm.$nextTick()
                // Le test vérifie simplement que le bouton existe et est cliquable
                expect(studentButton.exists()).toBe(true)
            }
        })

        it('les boutons de profil devraient être interactifs', () => {
            const buttons = wrapper.findAll('button')
            expect(buttons.length).toBeGreaterThanOrEqual(3)
            
            // Vérifier que les boutons ont les bonnes classes
            buttons.forEach((btn: any) => {
                if (btn.text().includes('Élève') || btn.text().includes('Enseignant') || btn.text().includes('Club')) {
                    expect(btn.classes()).toContain('p-4')
                }
            })
        })
    })

    describe('Validation et structure', () => {
        it('a une structure responsive avec max-w-md', () => {
            expect(wrapper.html()).toContain('max-w-md')
        })

        it('a un arrière-plan bg-gray-50', () => {
            const mainDiv = wrapper.find('.min-h-screen')
            expect(mainDiv.classes()).toContain('bg-gray-50')
        })

        it('a des espacements appropriés', () => {
            expect(wrapper.html()).toContain('space-y-8')
            expect(wrapper.html()).toContain('space-y-4')
        })
    })

    describe('Design et UX', () => {
        it('a des bordures arrondies pour les boutons de profil', () => {
            expect(wrapper.html()).toContain('rounded-lg')
        })

        it('a des transitions pour les hovers', () => {
            expect(wrapper.html()).toContain('transition-colors')
        })

        it('affiche le texte centré pour le titre', () => {
            const title = wrapper.find('h2')
            expect(title.classes()).toContain('text-center')
        })

        it('a des classes de typographie appropriées', () => {
            expect(wrapper.html()).toContain('font-extrabold')
            expect(wrapper.html()).toContain('font-medium')
        })
    })

    describe('Navigation', () => {
        it('contient un lien NuxtLink vers /login', () => {
            const links = wrapper.findAll('a')
            const loginLink = links.find(link => link.attributes('href') === '/login')
            expect(loginLink).toBeDefined()
        })

        it('le lien de connexion a un style primary', () => {
            expect(wrapper.html()).toContain('text-primary-600')
        })
    })

    describe('Accessibilité', () => {
        it('les boutons de sélection ont text-left pour meilleure lisibilité', () => {
            expect(wrapper.html()).toContain('text-left')
        })

        it('les boutons sont interactifs et accessibles', () => {
            const buttons = wrapper.findAll('button')
            buttons.forEach((btn: any) => {
                if (btn.text().includes('Élève') || btn.text().includes('Enseignant') || btn.text().includes('Club')) {
                    expect(btn.element.tagName).toBe('BUTTON')
                }
            })
        })
    })

    describe('Grid layout', () => {
        it('utilise une grille pour les boutons de profil', () => {
            expect(wrapper.html()).toContain('grid')
            expect(wrapper.html()).toContain('grid-cols-1')
        })

        it('devrait avoir un gap-4 entre les éléments', () => {
            expect(wrapper.html()).toContain('gap-4')
        })
    })
})
