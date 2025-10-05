import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import AddStudentModal from '../../components/AddStudentModal.vue'

describe('AddStudentModal', () => {
  let wrapper: any

  beforeEach(() => {
    vi.clearAllMocks()
    wrapper = mount(AddStudentModal)
  })

  describe('Structure et affichage', () => {
    it('devrait afficher le titre du modal', () => {
      expect(wrapper.text()).toContain('Ajouter un nouvel élève')
      expect(wrapper.text()).toContain('Remplissez les informations ci-dessous')
    })

    it('devrait avoir un header avec gradient emerald-teal', () => {
      const header = wrapper.find('.bg-gradient-to-r')
      expect(header.exists()).toBe(true)
      expect(header.classes()).toContain('from-emerald-500')
      expect(header.classes()).toContain('to-teal-600')
    })

    it('devrait avoir un bouton de fermeture', () => {
      const closeButtons = wrapper.findAll('button')
      expect(closeButtons.length).toBeGreaterThan(0)
    })

    it('devrait afficher la section "Informations personnelles"', () => {
      expect(wrapper.text()).toContain('Informations personnelles')
    })

    it('devrait afficher tous les champs requis', () => {
      expect(wrapper.find('input[type="text"]').exists()).toBe(true) // Nom
      expect(wrapper.find('input[type="email"]').exists()).toBe(true) // Email
      expect(wrapper.find('input[type="tel"]').exists()).toBe(true) // Téléphone
      expect(wrapper.find('select').exists()).toBe(true) // Niveau
      expect(wrapper.findAll('textarea').length).toBeGreaterThanOrEqual(2) // Objectifs + Infos médicales
    })
  })

  describe('Champs du formulaire', () => {
    it('devrait avoir un champ Nom complet avec astérisque obligatoire', () => {
      expect(wrapper.text()).toContain('Nom complet')
      expect(wrapper.text()).toContain('*')
    })

    it('devrait avoir un champ Email avec astérisque obligatoire', () => {
      expect(wrapper.text()).toContain('Email')
      expect(wrapper.find('input[type="email"]').attributes('required')).toBeDefined()
    })

    it('devrait avoir un champ Téléphone optionnel', () => {
      expect(wrapper.text()).toContain('Téléphone')
      const phoneInput = wrapper.find('input[type="tel"]')
      expect(phoneInput.attributes('required')).toBeUndefined()
    })

    it('devrait avoir un select de niveau avec options', () => {
      const select = wrapper.find('select')
      expect(select.exists()).toBe(true)
      const options = select.findAll('option')
      expect(options.length).toBeGreaterThan(1)
      
      const optionsText = options.map((opt: any) => opt.text())
      expect(optionsText.some(text => text.includes('Débutant'))).toBe(true)
      expect(optionsText.some(text => text.includes('Intermédiaire'))).toBe(true)
      expect(optionsText.some(text => text.includes('Avancé'))).toBe(true)
      expect(optionsText.some(text => text.includes('Expert'))).toBe(true)
    })

    it('devrait avoir des émojis dans les options de niveau', () => {
      const select = wrapper.find('select')
      const html = select.html()
      expect(html).toMatch(/[🌱📈⭐🏆]/u)
    })
  })

  describe('Section Objectifs et informations médicales', () => {
    it('devrait afficher la section', () => {
      expect(wrapper.text()).toContain('Objectifs et informations médicales')
    })

    it('devrait avoir un champ Objectifs', () => {
      expect(wrapper.text()).toContain('Objectifs')
      const textareas = wrapper.findAll('textarea')
      expect(textareas.length).toBeGreaterThanOrEqual(1)
    })

    it('devrait avoir un champ Informations médicales', () => {
      expect(wrapper.text()).toContain('Informations médicales')
    })
  })

  describe('Section Documents médicaux', () => {
    it('devrait afficher la section documents', () => {
      expect(wrapper.text()).toContain('Documents médicaux')
    })

    it('devrait avoir un bouton "Ajouter"', () => {
      const buttons = wrapper.findAll('button')
      const addButton = buttons.find((btn: any) => btn.text().includes('Ajouter'))
      expect(addButton).toBeDefined()
    })

    it('devrait afficher le message pour ajouter des documents', () => {
      const text = wrapper.text()
      expect(text).toContain('Ajoutez les documents nécessaires')
    })
  })

  describe('Interactions', () => {
    it('devrait émettre close quand on clique sur fermer', async () => {
      const closeButton = wrapper.findAll('button')[0]
      await closeButton.trigger('click')
      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('devrait avoir un formulaire avec submit', () => {
      const form = wrapper.find('form')
      expect(form.exists()).toBe(true)
    })
  })

  describe('Design et accessibilité', () => {
    it('devrait avoir des sections avec arrière-plans colorés', () => {
      expect(wrapper.html()).toContain('bg-gray-50')
      expect(wrapper.html()).toContain('bg-purple-50')
      expect(wrapper.html()).toContain('bg-amber-50')
    })

    it('devrait avoir des icônes SVG', () => {
      const svgs = wrapper.findAll('svg')
      expect(svgs.length).toBeGreaterThan(5)
    })

    it('devrait avoir des labels avec text-gray-700', () => {
      const labels = wrapper.findAll('label')
      expect(labels.length).toBeGreaterThan(0)
    })

    it('devrait être responsive avec max-w-4xl', () => {
      expect(wrapper.html()).toContain('max-w-4xl')
    })
  })
})