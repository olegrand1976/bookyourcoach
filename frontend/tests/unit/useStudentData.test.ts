import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useNuxtApp } from '#app'

// Mock Nuxt composables - récupérer le mock depuis #app
const { $api: mockApi } = useNuxtApp()

// Imports après les mocks
import { useStudentData } from '../../composables/useStudentData'
import { useToast } from '../../composables/useToast'

// Mock useToast - doit être fait avant l'import
const mockToast = {
  success: vi.fn(),
  error: vi.fn(),
  warning: vi.fn(),
  info: vi.fn()
}

vi.mock('../../composables/useToast', () => ({
  useToast: () => mockToast
}))

describe('useStudentData Composable', () => {
  // Mock console.error pour éviter les logs dans stderr pendant les tests
  const originalConsoleError = console.error
  
  beforeEach(() => {
    vi.clearAllMocks()
    // Réinitialiser les mocks
    mockApi.get.mockReset()
    mockApi.post.mockReset()
    mockApi.put.mockReset()
    mockApi.delete.mockReset()
    mockToast.success.mockReset()
    mockToast.error.mockReset()
    mockToast.warning.mockReset()
    mockToast.info.mockReset()
    // Supprimer les logs console.error pendant les tests
    console.error = vi.fn()
  })
  
  afterEach(() => {
    // Restaurer console.error après chaque test
    console.error = originalConsoleError
  })

  describe('loadStats', () => {
    it('devrait charger les statistiques avec succès', async () => {
      // Arrange
      const mockStats = {
        success: true,
        data: {
          availableLessons: 10,
          activeBookings: 3,
          completedLessons: 7,
          favoriteTeachers: 2
        }
      }
      mockApi.get.mockResolvedValue({ data: mockStats })

      const { loadStats, loading } = useStudentData()

      // Act
      const result = await loadStats()

      // Assert
      expect(mockApi.get).toHaveBeenCalledWith('/student/dashboard/stats')
      expect(result).toEqual(mockStats.data)
      expect(loading.value).toBe(false)
    })

    it('devrait gérer les erreurs correctement', async () => {
      // Arrange
      const error = new Error('Erreur de chargement')
      mockApi.get.mockRejectedValue(error)

      const { loadStats, error: errorRef } = useStudentData()

      // Act & Assert
      await expect(loadStats()).rejects.toThrow('Erreur de chargement')
      expect(errorRef.value).toBe('Erreur de chargement')
    })

    it('devrait gérer les réponses avec success:false', async () => {
      // Arrange
      mockApi.get.mockResolvedValue({
        data: {
          success: false,
          message: 'Erreur serveur'
        }
      })

      const { loadStats } = useStudentData()

      // Act & Assert
      await expect(loadStats()).rejects.toThrow('Erreur serveur')
    })
  })

  describe('loadLessonHistory', () => {
    it('devrait charger l\'historique des cours', async () => {
      // Arrange
      const mockHistory = {
        success: true,
        data: [
          { id: 1, status: 'completed', start_time: '2025-10-01 10:00:00' },
          { id: 2, status: 'completed', start_time: '2025-10-02 10:00:00' }
        ]
      }
      mockApi.get.mockResolvedValue({ data: mockHistory })

      const { loadLessonHistory } = useStudentData()

      // Act
      const result = await loadLessonHistory()

      // Assert
      expect(mockApi.get).toHaveBeenCalledWith('/student/lesson-history')
      expect(result).toEqual(mockHistory.data)
    })

    it('devrait limiter le nombre de résultats avec le paramètre limit', async () => {
      // Arrange
      const mockHistory = {
        success: true,
        data: [
          { id: 1 }, { id: 2 }, { id: 3 }, { id: 4 }, { id: 5 }
        ]
      }
      mockApi.get.mockResolvedValue({ data: mockHistory })

      const { loadLessonHistory } = useStudentData()

      // Act
      const result = await loadLessonHistory(3)

      // Assert
      expect(result).toHaveLength(3)
    })
  })

  describe('loadBookings', () => {
    it('devrait charger les réservations', async () => {
      // Arrange
      const mockBookings = {
        success: true,
        data: [
          { id: 1, status: 'confirmed', lesson: { id: 10 } },
          { id: 2, status: 'pending', lesson: { id: 11 } }
        ]
      }
      mockApi.get.mockResolvedValue({ data: mockBookings })

      const { loadBookings } = useStudentData()

      // Act
      const result = await loadBookings()

      // Assert
      expect(mockApi.get).toHaveBeenCalledWith('/student/bookings')
      expect(result).toEqual(mockBookings.data)
    })

    it('devrait retourner un tableau vide en cas d\'erreur', async () => {
      // Arrange
      mockApi.get.mockRejectedValue(new Error('Erreur'))

      const { loadBookings, error } = useStudentData()

      // Act & Assert
      await expect(loadBookings()).rejects.toThrow()
      expect(error.value).toBeTruthy()
    })
  })

  describe('loadAvailableLessons', () => {
    it('devrait charger les cours disponibles', async () => {
      // Arrange
      const mockLessons = {
        success: true,
        data: [
          { id: 1, status: 'available', start_time: '2025-11-01 10:00:00' },
          { id: 2, status: 'available', start_time: '2025-11-02 10:00:00' }
        ]
      }
      mockApi.get.mockResolvedValue({ data: mockLessons })

      const { loadAvailableLessons } = useStudentData()

      // Act
      const result = await loadAvailableLessons()

      // Assert
      expect(mockApi.get).toHaveBeenCalledWith('/student/available-lessons', { params: undefined })
      expect(result).toEqual(mockLessons.data)
    })

    it('devrait passer les filtres en paramètres', async () => {
      // Arrange
      const filters = { discipline: 1, date: '2025-11-01' }
      const mockLessons = {
        success: true,
        data: [{ id: 1, status: 'available' }]
      }
      mockApi.get.mockResolvedValue({ data: mockLessons })

      const { loadAvailableLessons } = useStudentData()

      // Act
      await loadAvailableLessons(filters)

      // Assert
      expect(mockApi.get).toHaveBeenCalledWith('/student/available-lessons', { params: filters })
    })
  })

  describe('bookLesson', () => {
    it('devrait réserver un cours avec succès', async () => {
      // Arrange
      const mockResponse = {
        success: true,
        data: { id: 1, status: 'confirmed', lesson_id: 10 },
        message: 'Cours réservé avec succès!'
      }
      mockApi.post.mockResolvedValue({ data: mockResponse })
      
      const { bookLesson } = useStudentData()

      // Act
      const result = await bookLesson(10, 'Note de test')

      // Assert
      expect(mockApi.post).toHaveBeenCalledWith('/student/bookings', {
        lesson_id: 10,
        notes: 'Note de test'
      })
      expect(result).toEqual(mockResponse.data)
      expect(mockToast.success).toHaveBeenCalledWith('Cours réservé avec succès!')
    })

    it('devrait gérer les erreurs de réservation', async () => {
      // Arrange
      const error = new Error('Erreur de réservation')
      mockApi.post.mockRejectedValue(error)
      
      const { bookLesson, error: errorRef } = useStudentData()

      // Act & Assert
      await expect(bookLesson(10)).rejects.toThrow()
      expect(errorRef.value).toBeTruthy()
      expect(mockToast.error).toHaveBeenCalled()
    })
  })

  describe('cancelBooking', () => {
    it('devrait annuler une réservation avec succès', async () => {
      // Arrange
      const mockResponse = {
        success: true,
        message: 'Réservation annulée avec succès'
      }
      mockApi.put.mockResolvedValue({ data: mockResponse })
      
      const { cancelBooking } = useStudentData()

      // Act
      const result = await cancelBooking(1)

      // Assert
      expect(mockApi.put).toHaveBeenCalledWith('/student/bookings/1/cancel')
      expect(result).toEqual(mockResponse.data)
      expect(mockToast.success).toHaveBeenCalledWith('Réservation annulée avec succès!')
    })

    it('devrait gérer les erreurs d\'annulation', async () => {
      // Arrange
      const error = new Error('Erreur d\'annulation')
      mockApi.put.mockRejectedValue(error)
      
      const { cancelBooking, error: errorRef } = useStudentData()

      // Act & Assert
      await expect(cancelBooking(1)).rejects.toThrow()
      expect(errorRef.value).toBeTruthy()
      expect(mockToast.error).toHaveBeenCalled()
    })
  })

  describe('États de chargement', () => {
    it('devrait mettre loading à true pendant le chargement', async () => {
      // Arrange
      let resolvePromise: (value: any) => void
      const promise = new Promise((resolve) => {
        resolvePromise = resolve
      })
      mockApi.get.mockReturnValue(promise)

      const { loadStats, loading } = useStudentData()

      // Act
      const loadPromise = loadStats()

      // Assert - loading devrait être true
      expect(loading.value).toBe(true)

      // Résoudre la promesse
      resolvePromise!({ data: { success: true, data: {} } })
      await loadPromise

      // Assert - loading devrait être false
      expect(loading.value).toBe(false)
    })
  })
})

