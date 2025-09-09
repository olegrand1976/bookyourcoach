import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import AddTeacherModal from '../../components/AddTeacherModal.vue'

// Mock des composables Nuxt
const mockUseToast = vi.fn(() => ({
  showToast: vi.fn()
}))

const mockUseRuntimeConfig = vi.fn(() => ({
  public: {
    apiBase: 'http://localhost:8081/api'
  }
}))

const mockUseCookie = vi.fn(() => ({
  value: 'mock-token'
}))

const mock$fetch = vi.fn()

vi.mock('#app', () => ({
  useToast: mockUseToast,
  useRuntimeConfig: mockUseRuntimeConfig,
  useCookie: mockUseCookie,
  $fetch: mock$fetch
}))

describe('AddTeacherModal', () => {
  let wrapper: any

  beforeEach(() => {
    vi.clearAllMocks()
    
    // Mock de la réponse API
    mock$fetch.mockResolvedValueOnce({
      message: 'Enseignant créé avec succès',
      teacher: { id: 1, name: 'Test Teacher' }
    })

    wrapper = mount(AddTeacherModal, {
      global: {
        stubs: {
          'svg': true
        }
      }
    })
  })

  it('devrait afficher le titre du modal', () => {
    expect(wrapper.text()).toContain('Ajouter un enseignant')
  })

  it('devrait avoir tous les champs requis', () => {
    expect(wrapper.find('input[type="text"]').exists()).toBe(true) // Nom
    expect(wrapper.find('input[type="email"]').exists()).toBe(true) // Email
    expect(wrapper.find('input[type="tel"]').exists()).toBe(true) // Téléphone
    expect(wrapper.find('input[type="number"]').exists()).toBe(true) // Expérience et tarif
    expect(wrapper.find('textarea').exists()).toBe(true) // Biographie
  })

  it('devrait avoir les valeurs par défaut correctes', () => {
    expect(wrapper.vm.form.specializations).toEqual(['dressage'])
    expect(wrapper.vm.form.experience_years).toBe(0)
    expect(wrapper.vm.form.hourly_rate).toBe(50)
  })

  it('devrait permettre la sélection multiple de spécialisations', () => {
    const specializationsSelect = wrapper.find('select[multiple]')
    expect(specializationsSelect.exists()).toBe(true)
    
    // Vérifier les options disponibles
    const options = specializationsSelect.findAll('option')
    expect(options.length).toBeGreaterThan(0)
    expect(options.some(opt => opt.text().includes('Dressage'))).toBe(true)
    expect(options.some(opt => opt.text().includes('Obstacle'))).toBe(true)
  })

  it('devrait valider les champs requis', async () => {
    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    
    // Le formulaire ne devrait pas être soumis sans les champs requis
    expect(mock$fetch).not.toHaveBeenCalledWith(
      'http://localhost:8081/api/club/teachers-test',
      expect.any(Object)
    )
  })

  it('devrait créer un enseignant avec des données valides', async () => {
    // Remplir le formulaire
    await wrapper.find('input[type="text"]').setValue('Test Teacher')
    await wrapper.find('input[type="email"]').setValue('teacher@example.com')
    await wrapper.find('input[type="tel"]').setValue('0123456789')
    await wrapper.find('input[type="number"]').setValue('5') // Expérience
    await wrapper.find('input[type="number"]').setValue('60') // Tarif horaire
    await wrapper.find('textarea').setValue('Biographie de test')
    
    // Soumettre le formulaire
    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    
    // Vérifier que l'API est appelée
    expect(mock$fetch).toHaveBeenCalledWith(
      'http://localhost:8081/api/club/teachers-test',
      expect.objectContaining({
        method: 'POST',
        headers: expect.objectContaining({
          'Authorization': 'Bearer mock-token',
          'Content-Type': 'application/json'
        }),
        body: expect.objectContaining({
          name: 'Test Teacher',
          email: 'teacher@example.com',
          phone: '0123456789',
          experience_years: 5,
          hourly_rate: 60,
          bio: 'Biographie de test'
        })
      })
    )
  })

  it('devrait émettre les événements de succès', async () => {
    // Remplir et soumettre le formulaire
    await wrapper.find('input[type="text"]').setValue('Test Teacher')
    await wrapper.find('input[type="email"]').setValue('teacher@example.com')
    
    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    
    // Vérifier que les événements sont émis
    expect(wrapper.emitted('success')).toBeTruthy()
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('devrait gérer les erreurs de création', async () => {
    // Mock d'une erreur API
    mock$fetch.mockRejectedValueOnce(new Error('API Error'))
    
    // Remplir et soumettre le formulaire
    await wrapper.find('input[type="text"]').setValue('Test Teacher')
    await wrapper.find('input[type="email"]').setValue('teacher@example.com')
    
    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    
    // Vérifier que les événements d'erreur ne sont pas émis
    expect(wrapper.emitted('success')).toBeFalsy()
    expect(wrapper.emitted('close')).toBeFalsy()
  })

  it('devrait fermer le modal quand on clique sur le bouton fermer', async () => {
    const closeButton = wrapper.find('button[aria-label="close"]') || 
                       wrapper.findAll('button').find(btn => btn.text().includes('Annuler'))
    
    if (closeButton) {
      await closeButton.trigger('click')
      expect(wrapper.emitted('close')).toBeTruthy()
    }
  })

  it('devrait afficher l\'état de chargement pendant la soumission', async () => {
    // Remplir le formulaire
    await wrapper.find('input[type="text"]').setValue('Test Teacher')
    await wrapper.find('input[type="email"]').setValue('teacher@example.com')
    
    // Soumettre le formulaire
    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    
    // Vérifier que le bouton de soumission est désactivé
    const submitButton = wrapper.find('button[type="submit"]')
    expect(submitButton.attributes('disabled')).toBeDefined()
  })

  it('devrait afficher le texte de chargement sur le bouton', async () => {
    // Remplir le formulaire
    await wrapper.find('input[type="text"]').setValue('Test Teacher')
    await wrapper.find('input[type="email"]').setValue('teacher@example.com')
    
    // Soumettre le formulaire
    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    
    // Vérifier que le texte du bouton change
    const submitButton = wrapper.find('button[type="submit"]')
    expect(submitButton.text()).toContain('Ajout...')
  })

  it('devrait valider les valeurs numériques', async () => {
    // Tester avec des valeurs négatives
    await wrapper.find('input[type="number"]').setValue('-1')
    
    // Le formulaire devrait accepter les valeurs négatives (validation côté serveur)
    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    
    // L'API devrait être appelée même avec des valeurs négatives
    expect(mock$fetch).toHaveBeenCalled()
  })
})
