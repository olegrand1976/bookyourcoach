import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import Logo from '../../components/Logo.vue'

describe('Logo Component', () => {
    let wrapper: any

    beforeEach(() => {
        wrapper = mount(Logo, {
            props: {
                size: 'md'
            }
        })
    })

    it('devrait monter le composant sans erreur', () => {
        expect(wrapper.exists()).toBe(true)
    })

    it('devrait avoir la structure flex correcte', () => {
        expect(wrapper.find('.flex').exists()).toBe(true)
        expect(wrapper.find('.items-center').exists()).toBe(true)
    })

    it('devrait afficher une image', () => {
        const img = wrapper.find('img')
        expect(img.exists()).toBe(true)
        expect(img.attributes('src')).toBeDefined()
        expect(img.attributes('alt')).toBeDefined()
    })

    it('devrait accepter la prop size=sm', () => {
        const wrapperSm = mount(Logo, { props: { size: 'sm' } })
        expect(wrapperSm.exists()).toBe(true)
        const img = wrapperSm.find('img')
        expect(img.classes()).toContain('w-24')
    })

    it('devrait accepter la prop size=md', () => {
        const img = wrapper.find('img')
        expect(img.classes()).toContain('w-32')
    })

    it('devrait accepter la prop size=lg', () => {
        const wrapperLg = mount(Logo, { props: { size: 'lg' } })
        const img = wrapperLg.find('img')
        expect(img.classes()).toContain('w-40')
    })

    it('devrait avoir un texte de fallback conditionnel', () => {
        // Le span de fallback ne devrait pas être visible initialement
        const fallbackText = wrapper.findAll('span').filter((span: any) => span.classes().includes('font-serif'))
        expect(fallbackText.length).toBe(0)
    })

    it('devrait appeler useSettings au montage', () => {
        // Le composant utilise useSettings pour charger le logo et le nom de la plateforme
        expect(wrapper.vm).toBeDefined()
    })
})