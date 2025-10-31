/**
 * Composable réutilisable pour le formatage des données student
 * Centralise toutes les fonctions de formatage (dates, prix, statuts, etc.)
 */
export const useStudentFormatters = () => {
  /**
   * Formater une date complète
   */
  const formatDate = (dateString: string, options?: { includeTime?: boolean; includeYear?: boolean }) => {
    if (!dateString) return ''
    
    const date = new Date(dateString)
    const dateOptions: Intl.DateTimeFormatOptions = {
      weekday: 'long',
      day: 'numeric',
      month: 'long'
    }
    
    // Inclure l'année par défaut (ou si explicitement demandé)
    if (options?.includeYear !== false) {
      dateOptions.year = 'numeric'
    }
    
    if (options?.includeTime) {
      dateOptions.hour = '2-digit'
      dateOptions.minute = '2-digit'
    }
    
    return date.toLocaleDateString('fr-FR', dateOptions)
  }

  /**
   * Formater une heure
   */
  const formatTime = (dateString: string) => {
    if (!dateString) return ''
    
    const date = new Date(dateString)
    return date.toLocaleTimeString('fr-FR', {
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  /**
   * Formater une date et heure complètes
   */
  const formatDateTime = (dateString: string) => {
    if (!dateString) return ''
    
    const date = new Date(dateString)
    return date.toLocaleString('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  /**
   * Formater un prix
   */
  const formatPrice = (price: number | null | undefined) => {
    if (price === null || price === undefined) return 'Non spécifié'
    return `${price.toFixed(2)}€`
  }

  /**
   * Obtenir la classe CSS pour un statut
   */
  const getStatusClass = (status: string) => {
    const statusClasses: Record<string, string> = {
      'pending': 'bg-yellow-100 text-yellow-800',
      'confirmed': 'bg-blue-100 text-blue-800',
      'completed': 'bg-green-100 text-green-800',
      'cancelled': 'bg-red-100 text-red-800',
      'available': 'bg-emerald-100 text-emerald-800',
      'full': 'bg-gray-100 text-gray-800'
    }
    
    return statusClasses[status] || 'bg-gray-100 text-gray-800'
  }

  /**
   * Obtenir le texte pour un statut
   */
  const getStatusText = (status: string) => {
    const statusTexts: Record<string, string> = {
      'pending': 'En attente',
      'confirmed': 'Confirmée',
      'completed': 'Terminée',
      'cancelled': 'Annulée',
      'available': 'Disponible',
      'full': 'Complet'
    }
    
    return statusTexts[status] || status
  }

  /**
   * Formater une date relative (il y a X jours/heures)
   */
  const formatRelativeDate = (dateString: string) => {
    if (!dateString) return ''
    
    const date = new Date(dateString)
    const now = new Date()
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000)
    
    if (diffInSeconds < 60) {
      return 'Il y a quelques secondes'
    } else if (diffInSeconds < 3600) {
      const minutes = Math.floor(diffInSeconds / 60)
      return `Il y a ${minutes} minute${minutes > 1 ? 's' : ''}`
    } else if (diffInSeconds < 86400) {
      const hours = Math.floor(diffInSeconds / 3600)
      return `Il y a ${hours} heure${hours > 1 ? 's' : ''}`
    } else if (diffInSeconds < 604800) {
      const days = Math.floor(diffInSeconds / 86400)
      return `Il y a ${days} jour${days > 1 ? 's' : ''}`
    } else {
      return formatDate(dateString)
    }
  }

  return {
    formatDate,
    formatTime,
    formatDateTime,
    formatPrice,
    getStatusClass,
    getStatusText,
    formatRelativeDate
  }
}

