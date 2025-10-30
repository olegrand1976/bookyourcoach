import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import AddTeacherModal from '../../components/AddTeacherModal.vue'

describe('AddTeacherModal', () => {
  let wrapper: any

  beforeEach(() => {
    vi.clearAllMocks()
    wrapper = mount(AddTeacherModal)
  })

  describe('Structure et affichage', () => {
    it('devrait afficher le titre du modal', () => {
      expect(wrapper.text()).toContain('Ajouter un nouvel enseignant')
      expect(wrapper.text()).toContain('Remplissez les informations ci-dessous')
    })

    it('devrait avoir un header avec gradient blue-indigo', () => {
      const header = wrapper.find('.bg-gradient-to-r')
      expect(header.exists()).toBe(true)
      expect(header.classes()).toContain('from-blue-500')
      expect(header.classes()).toContain('to-indigo-600')
    })

    it('devrait avoir un bouton de fermeture', () => {
      const closeButtons = wrapper.findAll('button')
      expect(closeButtons.length).toBeGreaterThan(0)
    })

    it('devrait afficher la section "Informations personnelles"', () => {
      expect(wrapper.text()).toContain('Informations personnelles')
    })

    it('devrait afficher tous les champs principaux', () => {
      expect(wrapper.find('input[type="text"]').exists()).toBe(true) // Nom
      expect(wrapper.find('input[type="email"]').exists()).toBe(true) // Email
      expect(wrapper.find('input[type="tel"]').exists()).toBe(true) // Téléphone
      expect(wrapper.find('input[type="number"]').exists()).toBe(true) // Expérience et tarif
      expect(wrapper.find('textarea').exists()).toBe(true) // Bio
    })
  })

  describe('Champs du formulaire', () => {
    it('devrait avoir un champ Nom complet obligatoire', () => {
      expect(wrapper.text()).toContain('Nom complet')
      expect(wrapper.text()).toContain('*')
      const nameInput = wrapper.find('input[type="text"]')
      expect(nameInput.attributes('required')).toBeDefined()
    })

    it('devrait avoir un champ Email obligatoire', () => {
      expect(wrapper.text()).toContain('Email')
      const emailInput = wrapper.find('input[type="email"]')
      expect(emailInput.attributes('required')).toBeDefined()
    })

    it('devrait avoir un champ Téléphone optionnel', () => {
      expect(wrapper.text()).toContain('Téléphone')
      const phoneInput = wrapper.find('input[type="tel"]')
      expect(phoneInput.attributes('required')).toBeUndefined()
    })

    it('devrait avoir un champ "Années d\'expérience"', () => {
      expect(wrapper.text()).toContain('Années d\'expérience')
      const expInput = wrapper.find('input[type="number"]')
      expect(expInput.exists()).toBe(true)
      expect(expInput.attributes('min')).toBe('0')
    })

    it('devrait avoir un select "Type de contrat"', () => {
      expect(wrapper.text()).toContain('Type de contrat')
      const selects = wrapper.findAll('select')
      expect(selects.length).toBeGreaterThan(0)
      
      const contractSelect = selects[0]
      const options = contractSelect.findAll('option')
      const optionsText = options.map((opt: any) => opt.text())
      
      expect(optionsText).toContain('Indépendant')
      expect(optionsText).toContain('Salarié')
      expect(optionsText).toContain('Bénévole')
      expect(optionsText).toContain('Étudiant')
      expect(optionsText).toContain('Article 17')
    })
  })

  describe('Section Tarifs et présentation', () => {
    it('devrait afficher la section Tarifs', () => {
      expect(wrapper.text()).toContain('Tarifs')
    })

    it('devrait avoir un champ tarif horaire avec le symbole €', () => {
      expect(wrapper.text()).toContain('Tarif horaire')
      expect(wrapper.text()).toContain('€')
    })

    it('devrait avoir un champ Bio/Présentation', () => {
      expect(wrapper.text()).toContain('Bio')
      const textarea = wrapper.find('textarea')
      expect(textarea.exists()).toBe(true)
    })
  })

  describe('Boutons d\'action', () => {
    it('devrait avoir un bouton Annuler', () => {
      const buttons = wrapper.findAll('button')
      const cancelButton = buttons.find((btn: any) => btn.text().includes('Annuler'))
      expect(cancelButton).toBeDefined()
    })

    it('devrait avoir un bouton de soumission', () => {
      const submitButton = wrapper.find('button[type="submit"]')
      expect(submitButton.exists()).toBe(true)
    })

    it('le bouton de soumission devrait contenir "Ajouter"', () => {
      const buttons = wrapper.findAll('button')
      const hasAddButton = buttons.some((btn: any) => btn.text().includes('Ajouter l\'enseignant'))
      expect(hasAddButton).toBe(true)
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
      expect(wrapper.html()).toContain('bg-emerald-50')
    })

    it('devrait avoir des icônes SVG', () => {
      const svgs = wrapper.findAll('svg')
      expect(svgs.length).toBeGreaterThan(3)
    })

    it('devrait être responsive avec max-w-3xl', () => {
      expect(wrapper.html()).toContain('max-w-3xl')
    })

    it('devrait avoir des bordures arrondies rounded-2xl', () => {
      expect(wrapper.html()).toContain('rounded-2xl')
    })

    it('devrait avoir des transitions pour les hovers', () => {
      expect(wrapper.html()).toContain('transition')
    })
  })

  describe('Validation et placeholders', () => {
    it('devrait avoir des placeholders informatifs', () => {
      const nameInput = wrapper.find('input[type="text"]')
      expect(nameInput.attributes('placeholder')).toContain('Marie')
      
      const emailInput = wrapper.find('input[type="email"]')
      expect(emailInput.attributes('placeholder')).toContain('@email.com')
    })

    it('devrait avoir des champs avec focus:ring-2', () => {
      expect(wrapper.html()).toContain('focus:ring-2')
      expect(wrapper.html()).toContain('focus:ring-blue-500')
    })
  })
})