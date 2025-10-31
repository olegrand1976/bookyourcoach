import { ref } from 'vue'
import { useToast } from '~/composables/useToast'

/**
 * Composable réutilisable pour la gestion des données student
 * Centralise les appels API et la gestion des états de chargement/erreur
 */
export const useStudentData = () => {
  const { $api } = useNuxtApp()
  const toast = useToast()
  
  const loading = ref(false)
  const error = ref<string | null>(null)

  /**
   * Charger les statistiques du dashboard
   */
  const loadStats = async () => {
    try {
      loading.value = true
      error.value = null
      
      const response = await $api.get('/student/dashboard/stats')
      
      if (response.data.success) {
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Erreur lors du chargement des statistiques')
      }
    } catch (err: any) {
      error.value = err.message || 'Erreur lors du chargement des statistiques'
      console.error('Error loading stats:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Charger l'historique des cours
   */
  const loadLessonHistory = async (limit?: number) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await $api.get('/student/lesson-history')
      
      if (response.data.success) {
        const history = response.data.data || []
        return limit ? history.slice(0, limit) : history
      } else {
        throw new Error(response.data.message || 'Erreur lors du chargement de l\'historique')
      }
    } catch (err: any) {
      error.value = err.message || 'Erreur lors du chargement de l\'historique'
      console.error('Error loading lesson history:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Charger les réservations
   */
  const loadBookings = async () => {
    try {
      loading.value = true
      error.value = null
      
      const response = await $api.get('/student/bookings')
      
      if (response.data.success) {
        return response.data.data || []
      } else {
        throw new Error(response.data.message || 'Erreur lors du chargement des réservations')
      }
    } catch (err: any) {
      error.value = err.message || 'Erreur lors du chargement des réservations'
      console.error('Error loading bookings:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Charger les cours disponibles
   */
  const loadAvailableLessons = async (filters?: any) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await $api.get('/student/available-lessons', { params: filters })
      
      if (response.data.success) {
        return response.data.data || []
      } else {
        throw new Error(response.data.message || 'Erreur lors du chargement des cours')
      }
    } catch (err: any) {
      error.value = err.message || 'Erreur lors du chargement des cours'
      console.error('Error loading available lessons:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Réserver un cours
   */
  const bookLesson = async (lessonId: number, notes?: string) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await $api.post('/student/bookings', {
        lesson_id: lessonId,
        notes
      })
      
      if (response.data.success) {
        toast.success('Cours réservé avec succès!')
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Erreur lors de la réservation')
      }
    } catch (err: any) {
      error.value = err.message || 'Erreur lors de la réservation'
      toast.error(error.value)
      console.error('Error booking lesson:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Annuler une réservation
   */
  const cancelBooking = async (bookingId: number) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await $api.put(`/student/bookings/${bookingId}/cancel`)
      
      if (response.data.success) {
        toast.success('Réservation annulée avec succès!')
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Erreur lors de l\'annulation')
      }
    } catch (err: any) {
      error.value = err.message || 'Erreur lors de l\'annulation'
      toast.error(error.value)
      console.error('Error cancelling booking:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    loadStats,
    loadLessonHistory,
    loadBookings,
    loadAvailableLessons,
    bookLesson,
    cancelBooking
  }
}

