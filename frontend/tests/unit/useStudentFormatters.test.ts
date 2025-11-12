import { describe, it, expect } from 'vitest'
import { useStudentFormatters } from '../../composables/useStudentFormatters'

describe('useStudentFormatters Composable', () => {
  const {
    formatDate,
    formatTime,
    formatDateTime,
    formatPrice,
    getStatusClass,
    getStatusText,
    formatRelativeDate
  } = useStudentFormatters()

  describe('formatDate', () => {
    it('devrait formater une date correctement', () => {
      // Arrange
      const dateString = '2025-10-15T14:30:00'

      // Act
      const result = formatDate(dateString)

      // Assert
      expect(result).toContain('2025')
      expect(result).toContain('octobre')
    })

    it('devrait retourner une chaîne vide pour une date invalide', () => {
      // Act
      const result = formatDate('')

      // Assert
      expect(result).toBe('')
    })

    it('devrait inclure l\'heure si includeTime est true', () => {
      // Arrange
      const dateString = '2025-10-15T14:30:00'

      // Act
      const result = formatDate(dateString, { includeTime: true })

      // Assert
      expect(result).toMatch(/\d{2}:\d{2}/) // Contient une heure formatée
    })
  })

  describe('formatTime', () => {
    it('devrait formater une heure correctement', () => {
      // Arrange
      const dateString = '2025-10-15T14:30:00'

      // Act
      const result = formatTime(dateString)

      // Assert
      expect(result).toMatch(/^\d{2}:\d{2}$/) // Format HH:mm
    })

    it('devrait retourner une chaîne vide pour une date invalide', () => {
      // Act
      const result = formatTime('')

      // Assert
      expect(result).toBe('')
    })

    it('devrait formater correctement 09:00', () => {
      // Arrange
      const dateString = '2025-10-15T09:00:00'

      // Act
      const result = formatTime(dateString)

      // Assert
      expect(result).toBe('09:00')
    })
  })

  describe('formatDateTime', () => {
    it('devrait formater une date et heure complètes', () => {
      // Arrange
      const dateString = '2025-10-15T14:30:00'

      // Act
      const result = formatDateTime(dateString)

      // Assert
      expect(result).toContain('2025')
      expect(result).toMatch(/\d{2}:\d{2}/) // Contient l'heure
    })

    it('devrait retourner une chaîne vide pour une date invalide', () => {
      // Act
      const result = formatDateTime('')

      // Assert
      expect(result).toBe('')
    })
  })

  describe('formatPrice', () => {
    it('devrait formater un prix correctement', () => {
      // Act
      const result = formatPrice(45.50)

      // Assert
      expect(result).toBe('45.50€')
    })

    it('devrait formater un prix entier', () => {
      // Act
      const result = formatPrice(50)

      // Assert
      expect(result).toBe('50.00€')
    })

    it('devrait retourner "Non spécifié" pour null', () => {
      // Act
      const result = formatPrice(null)

      // Assert
      expect(result).toBe('Non spécifié')
    })

    it('devrait retourner "Non spécifié" pour undefined', () => {
      // Act
      const result = formatPrice(undefined)

      // Assert
      expect(result).toBe('Non spécifié')
    })

    it('devrait formater correctement 0', () => {
      // Act
      const result = formatPrice(0)

      // Assert
      expect(result).toBe('0.00€')
    })
  })

  describe('getStatusClass', () => {
    it('devrait retourner la classe pour "pending"', () => {
      // Act
      const result = getStatusClass('pending')

      // Assert
      expect(result).toBe('bg-yellow-100 text-yellow-800')
    })

    it('devrait retourner la classe pour "confirmed"', () => {
      // Act
      const result = getStatusClass('confirmed')

      // Assert
      expect(result).toBe('bg-blue-100 text-blue-800')
    })

    it('devrait retourner la classe pour "completed"', () => {
      // Act
      const result = getStatusClass('completed')

      // Assert
      expect(result).toBe('bg-green-100 text-green-800')
    })

    it('devrait retourner la classe pour "cancelled"', () => {
      // Act
      const result = getStatusClass('cancelled')

      // Assert
      expect(result).toBe('bg-red-100 text-red-800')
    })

    it('devrait retourner la classe par défaut pour un statut inconnu', () => {
      // Act
      const result = getStatusClass('unknown')

      // Assert
      expect(result).toBe('bg-gray-100 text-gray-800')
    })

    it('devrait retourner la classe pour "available"', () => {
      // Act
      const result = getStatusClass('available')

      // Assert
      expect(result).toBe('bg-emerald-100 text-emerald-800')
    })
  })

  describe('getStatusText', () => {
    it('devrait retourner le texte pour "pending"', () => {
      // Act
      const result = getStatusText('pending')

      // Assert
      expect(result).toBe('En attente')
    })

    it('devrait retourner le texte pour "confirmed"', () => {
      // Act
      const result = getStatusText('confirmed')

      // Assert
      expect(result).toBe('Confirmée')
    })

    it('devrait retourner le texte pour "completed"', () => {
      // Act
      const result = getStatusText('completed')

      // Assert
      expect(result).toBe('Terminée')
    })

    it('devrait retourner le texte pour "cancelled"', () => {
      // Act
      const result = getStatusText('cancelled')

      // Assert
      expect(result).toBe('Annulée')
    })

    it('devrait retourner le texte pour "available"', () => {
      // Act
      const result = getStatusText('available')

      // Assert
      expect(result).toBe('Disponible')
    })

    it('devrait retourner le statut original pour un statut inconnu', () => {
      // Act
      const result = getStatusText('unknown')

      // Assert
      expect(result).toBe('unknown')
    })
  })

  describe('formatRelativeDate', () => {
    it('devrait formater "il y a quelques secondes" pour une date très récente', () => {
      // Arrange
      const dateString = new Date(Date.now() - 5000).toISOString() // Il y a 5 secondes

      // Act
      const result = formatRelativeDate(dateString)

      // Assert
      expect(result).toContain('quelques secondes')
    })

    it('devrait formater "il y a X minutes"', () => {
      // Arrange
      const dateString = new Date(Date.now() - 5 * 60 * 1000).toISOString() // Il y a 5 minutes

      // Act
      const result = formatRelativeDate(dateString)

      // Assert
      expect(result).toContain('5 minute')
    })

    it('devrait formater "il y a X heures"', () => {
      // Arrange
      const dateString = new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString() // Il y a 2 heures

      // Act
      const result = formatRelativeDate(dateString)

      // Assert
      expect(result).toContain('2 heure')
    })

    it('devrait formater "il y a X jours"', () => {
      // Arrange
      const dateString = new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString() // Il y a 3 jours

      // Act
      const result = formatRelativeDate(dateString)

      // Assert
      expect(result).toContain('3 jour')
    })

    it('devrait utiliser formatDate pour les dates anciennes', () => {
      // Arrange
      const dateString = new Date(Date.now() - 10 * 24 * 60 * 60 * 1000).toISOString() // Il y a 10 jours

      // Act
      const result = formatRelativeDate(dateString)

      // Assert
      // Pour les dates anciennes, on devrait utiliser formatDate qui retourne une date complète
      expect(result).not.toContain('il y a')
    })

    it('devrait retourner une chaîne vide pour une date invalide', () => {
      // Act
      const result = formatRelativeDate('')

      // Assert
      expect(result).toBe('')
    })
  })
})

