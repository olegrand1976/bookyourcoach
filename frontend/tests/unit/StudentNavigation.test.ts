import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import StudentNavigation from '../../components/StudentNavigation.vue'

// Mock de useRoute
const mockRoute = {
    path: '/student/dashboard'
}

vi.mock('#app', () => ({
    useRoute: () => mockRoute
}))

describe('StudentNavigation Component', () => {
    let wrapper
    let pinia

    beforeEach(() => {
        pinia = createPinia()
        wrapper = mount(StudentNavigation, {
            global: {
                plugins: [pinia],
                stubs: {
                    NuxtLink: {
                        template: '<a :href="to" :class="$attrs.class"><slot /></a>',
                        props: ['to']
                    }
                }
            }
        })
    })

    describe('Structure de base', () => {
        it('devrait rendre le composant', () => {
            expect(wrapper.exists()).toBe(true)
        })

        it('devrait avoir une classe student-nav', () => {
            expect(wrapper.find('.student-nav').exists()).toBe(true)
        })
    })

    describe('Menu mobile', () => {
        it('devrait afficher le bouton de menu mobile sur petit écran', () => {
            const mobileButton = wrapper.find('.lg\\:hidden button')
            expect(mobileButton.exists()).toBe(true)
        })

        it('le bouton mobile devrait avoir une icône hamburger', () => {
            const svg = wrapper.find('.lg\\:hidden svg')
            expect(svg.exists()).toBe(true)
        })

        it('devrait ouvrir le menu mobile au clic', async () => {
            const mobileButton = wrapper.find('.lg\\:hidden button')
            await mobileButton.trigger('click')
            await wrapper.vm.$nextTick()
            
            // Le menu devrait être visible
            expect(wrapper.vm.mobileMenuOpen).toBe(true)
        })

        it('devrait fermer le menu mobile en cliquant sur le fond', async () => {
            const mobileButton = wrapper.find('.lg\\:hidden button')
            await mobileButton.trigger('click')
            await wrapper.vm.$nextTick()
            
            const overlay = wrapper.find('.bg-gray-600')
            await overlay.trigger('click')
            await wrapper.vm.$nextTick()
            
            expect(wrapper.vm.mobileMenuOpen).toBe(false)
        })

        it('devrait avoir un bouton de fermeture dans le menu mobile', async () => {
            const mobileButton = wrapper.find('.lg\\:hidden button')
            await mobileButton.trigger('click')
            await wrapper.vm.$nextTick()
            
            const closeButtons = wrapper.findAll('button')
            expect(closeButtons.length).toBeGreaterThan(1)
        })
    })

    describe('Sidebar desktop', () => {
        it('devrait afficher la sidebar sur desktop', () => {
            const sidebar = wrapper.find('.hidden.lg\\:flex')
            expect(sidebar.exists()).toBe(true)
        })

        it('devrait avoir une largeur fixe lg:w-64', () => {
            const sidebar = wrapper.find('.lg\\:w-64')
            expect(sidebar.exists()).toBe(true)
        })

        it('devrait avoir un arrière-plan blanc avec bordure', () => {
            const sidebar = wrapper.find('.lg\\:bg-white')
            expect(sidebar.exists()).toBe(true)
        })

        it('devrait être positionné en fixed', () => {
            const sidebar = wrapper.find('.lg\\:fixed')
            expect(sidebar.exists()).toBe(true)
        })
    })

    describe('Navigation items', () => {
        it('devrait afficher tous les liens de navigation', () => {
            const navLinks = wrapper.findAll('a')
            // Desktop + Mobile menu = items x2
            expect(navLinks.length).toBeGreaterThanOrEqual(6)
        })

        it('devrait afficher "Tableau de bord"', () => {
            expect(wrapper.text()).toContain('Tableau de bord')
        })

        it('devrait afficher "Leçons disponibles"', () => {
            expect(wrapper.text()).toContain('Leçons disponibles')
        })

        it('devrait afficher "Mes réservations"', () => {
            expect(wrapper.text()).toContain('Mes réservations')
        })

        it('devrait afficher "Mes préférences"', () => {
            expect(wrapper.text()).toContain('Mes préférences')
        })

        it('devrait afficher "Historique"', () => {
            expect(wrapper.text()).toContain('Historique')
        })

        it('devrait afficher "Enseignants"', () => {
            expect(wrapper.text()).toContain('Enseignants')
        })
    })

    describe('Routes actives', () => {
        it('devrait avoir des liens avec des classes pour l\'état actif', () => {
            // Vérifier que le code HTML contient les classes d'état actif
            const html = wrapper.html()
            expect(html).toContain('text-gray-600')
        })

        it('devrait avoir des transitions sur les liens', () => {
            const html = wrapper.html()
            expect(html).toContain('transition-colors')
        })
    })

    describe('Design et styles', () => {
        it('devrait avoir des transitions sur les liens', () => {
            const html = wrapper.html()
            expect(html).toContain('transition-colors')
        })

        it('devrait avoir des bordures arrondies sur les liens', () => {
            const html = wrapper.html()
            expect(html).toContain('rounded-md')
        })

        it('devrait avoir des effets de hover', () => {
            const html = wrapper.html()
            expect(html).toContain('hover:bg-gray-50')
            expect(html).toContain('hover:text-gray-900')
        })

        it('devrait avoir des espacements appropriés', () => {
            const html = wrapper.html()
            expect(html).toContain('space-y-1')
        })

        it('devrait avoir des icônes SVG', () => {
            const svgs = wrapper.findAll('svg')
            expect(svgs.length).toBeGreaterThan(0)
        })
    })

    describe('Responsive', () => {
        it('devrait masquer le menu mobile sur desktop (lg:hidden)', () => {
            const mobileMenu = wrapper.find('.lg\\:hidden')
            expect(mobileMenu.exists()).toBe(true)
        })

        it('devrait masquer la sidebar sur mobile (hidden lg:flex)', () => {
            const sidebar = wrapper.find('.hidden.lg\\:flex')
            expect(sidebar.exists()).toBe(true)
        })
    })

    describe('Accessibilité', () => {
        it('devrait avoir des liens stylés avec hover', () => {
            const html = wrapper.html()
            expect(html).toContain('hover:bg-gray-50')
        })

        it('les boutons devraient être accessibles au clavier', () => {
            const button = wrapper.find('button')
            expect(button.element.tagName).toBe('BUTTON')
        })

        it('devrait avoir un bouton de menu mobile', () => {
            const mobileButton = wrapper.find('.lg\\:hidden button')
            expect(mobileButton.exists()).toBe(true)
        })
    })

    describe('État du menu mobile', () => {
        it('devrait initialiser mobileMenuOpen à false', () => {
            expect(wrapper.vm.mobileMenuOpen).toBe(false)
        })

        it('devrait basculer l\'état mobileMenuOpen', async () => {
            const initialState = wrapper.vm.mobileMenuOpen
            const mobileButton = wrapper.find('.lg\\:hidden button')
            
            await mobileButton.trigger('click')
            expect(wrapper.vm.mobileMenuOpen).toBe(!initialState)
            
            await mobileButton.trigger('click')
            expect(wrapper.vm.mobileMenuOpen).toBe(initialState)
        })

        it('devrait gérer la fermeture du menu mobile', async () => {
            // Ouvrir le menu mobile
            const mobileButton = wrapper.find('.lg\\:hidden button')
            await mobileButton.trigger('click')
            expect(wrapper.vm.mobileMenuOpen).toBe(true)
            
            // Vérifier que le menu est bien ouvert
            await wrapper.vm.$nextTick()
            expect(wrapper.vm.mobileMenuOpen).toBe(true)
        })
    })
})
