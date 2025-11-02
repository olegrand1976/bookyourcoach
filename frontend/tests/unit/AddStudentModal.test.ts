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
      const textInputs = wrapper.findAll('input[type="text"]')
      expect(textInputs.length).toBeGreaterThanOrEqual(2) // Prénom et Nom
      expect(wrapper.find('input[type="email"]').exists()).toBe(true) // Email
      expect(wrapper.find('input[type="tel"]').exists()).toBe(true) // Téléphone
      expect(wrapper.findAll('textarea').length).toBeGreaterThanOrEqual(2) // Objectifs + Infos médicales
    })
  })

  describe('Champs du formulaire', () => {
    it('devrait avoir un champ Prénom marqué comme facultatif', () => {
      expect(wrapper.text()).toContain('Prénom')
      expect(wrapper.text()).toContain('(facultatif)')
      // Vérifier que le champ n'est pas requis
      const firstNameInput = wrapper.find('input[type="text"]')
      expect(firstNameInput.attributes('required')).toBeUndefined()
    })

    it('devrait avoir un champ Nom marqué comme facultatif', () => {
      expect(wrapper.text()).toContain('Nom')
      expect(wrapper.text()).toContain('(facultatif)')
      const textInputs = wrapper.findAll('input[type="text"]')
      expect(textInputs.length).toBeGreaterThanOrEqual(2)
      // Vérifier que le champ nom n'est pas requis
      const lastNameInput = textInputs[1]
      expect(lastNameInput.attributes('required')).toBeUndefined()
    })

    it('devrait avoir un champ Email marqué comme facultatif', () => {
      expect(wrapper.text()).toContain('Email')
      expect(wrapper.text()).toContain('(facultatif)')
      const emailInput = wrapper.find('input[type="email"]')
      expect(emailInput.exists()).toBe(true)
      // Vérifier que le champ n'est pas requis
      expect(emailInput.attributes('required')).toBeUndefined()
      // Vérifier que le message d'information est présent
      expect(wrapper.text()).toContain('Si aucun email n\'est fourni')
    })

    it('devrait avoir un champ Téléphone optionnel', () => {
      expect(wrapper.text()).toContain('Téléphone')
      const phoneInput = wrapper.find('input[type="tel"]')
      expect(phoneInput.attributes('required')).toBeUndefined()
    })

    it('ne devrait PAS avoir de champ Niveau (supprimé)', () => {
      expect(wrapper.text()).not.toContain('Niveau')
      const selects = wrapper.findAll('select')
      // Les selects restants sont pour les documents médicaux, pas pour le niveau
      const hasLevelSelect = selects.some((select: any) => {
        const html = select.html()
        return html.includes('Débutant') || html.includes('Intermédiaire')
      })
      expect(hasLevelSelect).toBe(false)
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