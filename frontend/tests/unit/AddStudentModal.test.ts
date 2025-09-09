import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import AddStudentModal from '../../components/AddStudentModal.vue'

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

describe('AddStudentModal', () => {
  let wrapper: any

  beforeEach(() => {
    vi.clearAllMocks()
    
    // Mock des réponses API
    mock$fetch
      .mockResolvedValueOnce({
        club: {
          disciplines: [
            { id: 1, name: 'Dressage', description: 'Cours de dressage', activity_type_id: 1 },
            { id: 2, name: 'Obstacle', description: 'Cours d\'obstacle', activity_type_id: 1 }
          ]
        }
      })
      .mockResolvedValueOnce({
        message: 'Étudiant créé avec succès',
        student: { id: 1, name: 'Test Student' }
      })

    wrapper = mount(AddStudentModal, {
      global: {
        stubs: {
          'svg': true
        }
      }
    })
  })

  it('devrait afficher le titre du modal', () => {
    expect(wrapper.text()).toContain('Ajouter un élève')
  })

  it('devrait avoir tous les champs requis', () => {
    expect(wrapper.find('input[type="text"]').exists()).toBe(true) // Nom
    expect(wrapper.find('input[type="email"]').exists()).toBe(true) // Email
    expect(wrapper.find('input[type="tel"]').exists()).toBe(true) // Téléphone
    expect(wrapper.find('select').exists()).toBe(true) // Niveau
    expect(wrapper.find('textarea').exists()).toBe(true) // Objectifs/Infos médicales
  })

  it('devrait charger les spécialités du club au montage', async () => {
    await wrapper.vm.$nextTick()
    expect(mock$fetch).toHaveBeenCalledWith(
      'http://localhost:8081/api/club/profile-test',
      expect.any(Object)
    )
  })

  it('devrait permettre d\'ajouter des documents médicaux', async () => {
    const addDocButton = wrapper.find('button[type="button"]')
    await addDocButton.trigger('click')
    
    // Vérifier qu'un nouveau document est ajouté
    expect(wrapper.vm.medicalDocuments).toHaveLength(1)
  })

  it('devrait permettre de supprimer des documents médicaux', async () => {
    // Ajouter un document
    wrapper.vm.addDocument()
    await wrapper.vm.$nextTick()
    
    // Supprimer le document
    wrapper.vm.removeDocument(0)
    expect(wrapper.vm.medicalDocuments).toHaveLength(0)
  })

  it('devrait valider les champs requis', async () => {
    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    
    // Le formulaire ne devrait pas être soumis sans les champs requis
    expect(mock$fetch).not.toHaveBeenCalledWith(
      'http://localhost:8081/api/club/students-test',
      expect.any(Object)
    )
  })

  it('devrait créer un étudiant avec des données valides', async () => {
    // Remplir le formulaire
    await wrapper.find('input[type="text"]').setValue('Test Student')
    await wrapper.find('input[type="email"]').setValue('test@example.com')
    await wrapper.find('input[type="tel"]').setValue('0123456789')
    await wrapper.find('select').setValue('debutant')
    await wrapper.find('textarea').setValue('Objectifs de test')
    
    // Soumettre le formulaire
    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    
    // Vérifier que l'API est appelée
    expect(mock$fetch).toHaveBeenCalledWith(
      'http://localhost:8081/api/club/students-test',
      expect.objectContaining({
        method: 'POST',
        headers: expect.objectContaining({
          'Authorization': 'Bearer mock-token',
          'Content-Type': 'application/json'
        }),
        body: expect.objectContaining({
          name: 'Test Student',
          email: 'test@example.com',
          phone: '0123456789',
          level: 'debutant',
          goals: 'Objectifs de test'
        })
      })
    )
  })

  it('devrait émettre les événements de succès', async () => {
    // Remplir et soumettre le formulaire
    await wrapper.find('input[type="text"]').setValue('Test Student')
    await wrapper.find('input[type="email"]').setValue('test@example.com')
    
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
    await wrapper.find('input[type="text"]').setValue('Test Student')
    await wrapper.find('input[type="email"]').setValue('test@example.com')
    
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
    await wrapper.find('input[type="text"]').setValue('Test Student')
    await wrapper.find('input[type="email"]').setValue('test@example.com')
    
    // Soumettre le formulaire
    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    
    // Vérifier que le bouton de soumission est désactivé
    const submitButton = wrapper.find('button[type="submit"]')
    expect(submitButton.attributes('disabled')).toBeDefined()
  })
})
