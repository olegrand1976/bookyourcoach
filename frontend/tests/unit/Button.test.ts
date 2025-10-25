import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Button from '../../components/Button.vue'

describe('Button Component', () => {
  describe('Rendu de base', () => {
    it('devrait rendre le composant avec le contenu par défaut', () => {
      const wrapper = mount(Button, {
        slots: {
          default: 'Cliquez ici'
        }
      })
      
      expect(wrapper.text()).toContain('Cliquez ici')
    })

    it('devrait avoir le type "button" par défaut', () => {
      const wrapper = mount(Button)
      expect(wrapper.find('button').attributes('type')).toBe('button')
    })

    it('devrait accepter un type personnalisé', () => {
      const wrapper = mount(Button, {
        props: { type: 'submit' }
      })
      expect(wrapper.find('button').attributes('type')).toBe('submit')
    })
  })

  describe('Variantes de style', () => {
    it('devrait appliquer la variante "primary" par défaut', () => {
      const wrapper = mount(Button)
      const button = wrapper.find('button')
      expect(button.classes()).toContain('bg-blue-600')
      expect(button.classes()).toContain('text-white')
    })

    it('devrait appliquer la variante "secondary"', () => {
      const wrapper = mount(Button, {
        props: { variant: 'secondary' }
      })
      const button = wrapper.find('button')
      expect(button.classes()).toContain('bg-gray-600')
    })

    it('devrait appliquer la variante "danger"', () => {
      const wrapper = mount(Button, {
        props: { variant: 'danger' }
      })
      const button = wrapper.find('button')
      expect(button.classes()).toContain('bg-red-600')
    })

    it('devrait appliquer la variante "success"', () => {
      const wrapper = mount(Button, {
        props: { variant: 'success' }
      })
      const button = wrapper.find('button')
      expect(button.classes()).toContain('bg-green-600')
    })

    it('devrait appliquer la variante "warning"', () => {
      const wrapper = mount(Button, {
        props: { variant: 'warning' }
      })
      const button = wrapper.find('button')
      expect(button.classes()).toContain('bg-yellow-600')
    })

    it('devrait appliquer la variante "outline"', () => {
      const wrapper = mount(Button, {
        props: { variant: 'outline' }
      })
      const button = wrapper.find('button')
      expect(button.classes()).toContain('border-2')
      expect(button.classes()).toContain('border-gray-300')
    })
  })

  describe('Tailles', () => {
    it('devrait appliquer la taille "md" par défaut', () => {
      const wrapper = mount(Button)
      const button = wrapper.find('button')
      expect(button.classes()).toContain('px-6')
      expect(button.classes()).toContain('py-2')
    })

    it('devrait appliquer la taille "sm"', () => {
      const wrapper = mount(Button, {
        props: { size: 'sm' }
      })
      const button = wrapper.find('button')
      expect(button.classes()).toContain('px-3')
      expect(button.classes()).toContain('text-sm')
    })

    it('devrait appliquer la taille "lg"', () => {
      const wrapper = mount(Button, {
        props: { size: 'lg' }
      })
      const button = wrapper.find('button')
      expect(button.classes()).toContain('px-8')
      expect(button.classes()).toContain('py-3')
    })
  })

  describe('États', () => {
    it('devrait être désactivé quand la prop disabled est true', () => {
      const wrapper = mount(Button, {
        props: { disabled: true }
      })
      expect(wrapper.find('button').attributes('disabled')).toBeDefined()
    })

    it('ne devrait pas émettre de click quand désactivé', async () => {
      const wrapper = mount(Button, {
        props: { disabled: true }
      })
      await wrapper.find('button').trigger('click')
      expect(wrapper.emitted('click')).toBeUndefined()
    })

    it('devrait afficher un spinner en état loading', () => {
      const wrapper = mount(Button, {
        props: { loading: true }
      })
      expect(wrapper.find('svg.animate-spin').exists()).toBe(true)
    })

    it('devrait afficher le texte de chargement personnalisé', () => {
      const wrapper = mount(Button, {
        props: { 
          loading: true,
          loadingText: 'Envoi en cours...'
        }
      })
      expect(wrapper.text()).toContain('Envoi en cours...')
    })

    it('ne devrait pas émettre de click en état loading', async () => {
      const wrapper = mount(Button, {
        props: { loading: true }
      })
      await wrapper.find('button').trigger('click')
      expect(wrapper.emitted('click')).toBeUndefined()
    })
  })

  describe('Largeur', () => {
    it('devrait occuper toute la largeur avec fullWidth', () => {
      const wrapper = mount(Button, {
        props: { fullWidth: true }
      })
      expect(wrapper.find('button').classes()).toContain('w-full')
    })

    it('ne devrait pas occuper toute la largeur par défaut', () => {
      const wrapper = mount(Button)
      expect(wrapper.find('button').classes()).not.toContain('w-full')
    })
  })

  describe('Événements', () => {
    it('devrait émettre un événement click', async () => {
      const wrapper = mount(Button)
      await wrapper.find('button').trigger('click')
      expect(wrapper.emitted('click')).toBeTruthy()
      expect(wrapper.emitted('click')?.length).toBe(1)
    })

    it('devrait passer l\'événement au parent', async () => {
      const wrapper = mount(Button)
      await wrapper.find('button').trigger('click')
      const emittedEvents = wrapper.emitted('click')
      expect(emittedEvents).toBeTruthy()
      expect(emittedEvents?.[0]).toBeDefined()
    })
  })

  describe('Slots', () => {
    it('devrait accepter un slot par défaut', () => {
      const wrapper = mount(Button, {
        slots: {
          default: 'Contenu du bouton'
        }
      })
      expect(wrapper.text()).toContain('Contenu du bouton')
    })

    it('devrait accepter un slot icon', () => {
      const wrapper = mount(Button, {
        slots: {
          icon: '<svg class="test-icon"></svg>',
          default: 'Avec icône'
        }
      })
      expect(wrapper.find('.test-icon').exists()).toBe(true)
      expect(wrapper.text()).toContain('Avec icône')
    })

    it('devrait ajouter une marge à gauche du texte si icône présente', () => {
      const wrapper = mount(Button, {
        slots: {
          icon: '<div class="icon"></div>',
          default: 'Texte'
        }
      })
      const span = wrapper.find('span')
      expect(span.classes()).toContain('ml-2')
    })
  })

  describe('Classes CSS', () => {
    it('devrait avoir les classes de base', () => {
      const wrapper = mount(Button)
      const button = wrapper.find('button')
      expect(button.classes()).toContain('inline-flex')
      expect(button.classes()).toContain('items-center')
      expect(button.classes()).toContain('justify-center')
      expect(button.classes()).toContain('font-medium')
      expect(button.classes()).toContain('rounded-lg')
      expect(button.classes()).toContain('transition-colors')
    })

    it('devrait avoir les classes de focus', () => {
      const wrapper = mount(Button)
      const button = wrapper.find('button')
      expect(button.classes()).toContain('focus:outline-none')
      expect(button.classes()).toContain('focus:ring-2')
      expect(button.classes()).toContain('focus:ring-offset-2')
    })
  })

  describe('Accessibilité', () => {
    it('devrait être un élément button', () => {
      const wrapper = mount(Button)
      const button = wrapper.find('button')
      expect(button.element.tagName).toBe('BUTTON')
    })

    it('ne devrait pas être focusable quand désactivé', () => {
      const wrapper = mount(Button, {
        props: { disabled: true }
      })
      expect(wrapper.find('button').attributes('disabled')).toBeDefined()
    })

    it('devrait permettre la navigation au clavier', () => {
      const wrapper = mount(Button)
      const button = wrapper.find('button')
      // Un bouton non désactivé est focusable
      expect(button.attributes('disabled')).toBeUndefined()
    })
  })
})
