/**
 * Composable pour le formatage des données du planning
 * Utilisé pour formater les heures, durées, prix, etc.
 */

/**
 * Formater une heure au format HH:MM
 */
export function formatTime(hour: number, minute: number = 0): string {
  return `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`
}

/**
 * Parser une heure "HH:MM" ou "HH:MM:SS" en {hour, minute}
 */
export function parseTime(timeString: string): { hour: number; minute: number } {
  const [hourStr, minuteStr] = timeString.split(':')
  return {
    hour: parseInt(hourStr) || 0,
    minute: parseInt(minuteStr) || 0
  }
}

/**
 * Convertir des minutes en format "Xh YYmin"
 */
export function formatDuration(minutes: number): string {
  if (minutes < 60) {
    return `${minutes}min`
  }
  
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  
  if (mins === 0) {
    return `${hours}h`
  }
  
  return `${hours}h ${mins}min`
}

/**
 * Formater un prix en euros
 */
export function formatPrice(price: number | string): string {
  const p = typeof price === 'string' ? parseFloat(price) : price
  return `${p.toFixed(2)}€`
}

/**
 * Formater un taux de remplissage en pourcentage
 */
export function formatOccupancyRate(used: number, total: number): string {
  if (total === 0) return '0%'
  const rate = (used / total) * 100
  return `${Math.round(rate)}%`
}

/**
 * Obtenir une classe CSS pour le taux de remplissage
 */
export function getOccupancyClass(used: number, total: number): string {
  if (total === 0) return 'text-gray-500'
  
  const rate = (used / total) * 100
  
  if (rate >= 90) return 'text-red-600 font-bold'
  if (rate >= 70) return 'text-orange-600 font-semibold'
  if (rate >= 50) return 'text-yellow-600'
  return 'text-green-600'
}

/**
 * Formater une plage horaire
 */
export function formatTimeRange(startTime: string, endTime: string): string {
  const start = startTime.substring(0, 5) // Garder seulement HH:MM
  const end = endTime.substring(0, 5)
  return `${start} - ${end}`
}

/**
 * Convertir des heures en minutes depuis minuit
 */
export function timeToMinutes(hour: number, minute: number): number {
  return hour * 60 + minute
}

/**
 * Convertir des minutes depuis minuit en {hour, minute}
 */
export function minutesToTime(minutes: number): { hour: number; minute: number } {
  return {
    hour: Math.floor(minutes / 60),
    minute: minutes % 60
  }
}

/**
 * Extraire l'heure d'une chaîne de date-heure
 * Gère les formats : "2025-10-15T09:30:00", "2025-10-15 09:30:00", "09:30:00"
 */
export function extractTime(dateTimeString: string): string {
  if (!dateTimeString) return '00:00'
  
  // Format ISO : "2025-10-15T09:30:00"
  if (dateTimeString.includes('T')) {
    return dateTimeString.split('T')[1].substring(0, 5)
  }
  
  // Format SQL : "2025-10-15 09:30:00"
  if (dateTimeString.includes(' ')) {
    return dateTimeString.split(' ')[1].substring(0, 5)
  }
  
  // Format heure seule : "09:30:00"
  return dateTimeString.substring(0, 5)
}

/**
 * Extraire la date d'une chaîne de date-heure
 * Gère les formats : "2025-10-15T09:30:00", "2025-10-15 09:30:00"
 */
export function extractDate(dateTimeString: string): string {
  if (!dateTimeString) return ''
  
  // Format ISO : "2025-10-15T09:30:00"
  if (dateTimeString.includes('T')) {
    return dateTimeString.split('T')[0]
  }
  
  // Format SQL : "2025-10-15 09:30:00"
  if (dateTimeString.includes(' ')) {
    return dateTimeString.split(' ')[0]
  }
  
  // Déjà une date : "2025-10-15"
  return dateTimeString
}

/**
 * Formater un nombre de participants
 */
export function formatParticipants(count: number, isIndividual: boolean): string {
  if (isIndividual) {
    return 'Individuel'
  }
  return `Groupe (${count} max)`
}

/**
 * Obtenir un label court pour le type de cours
 */
export function getCourseTypeLabel(courseType: any): string {
  const duration = formatDuration(courseType.duration_minutes || 60)
  const type = courseType.is_individual ? 'Ind.' : 'Groupe'
  const price = courseType.price ? formatPrice(courseType.price) : ''
  
  return `${courseType.name} - ${duration} - ${type}${price ? ` - ${price}` : ''}`
}

/**
 * Formater un numéro de téléphone
 */
export function formatPhoneNumber(phone: string): string {
  if (!phone) return ''
  
  // Supprimer tous les caractères non numériques
  const cleaned = phone.replace(/\D/g, '')
  
  // Formater en XX XX XX XX XX pour les numéros français
  if (cleaned.length === 10) {
    return cleaned.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5')
  }
  
  // Formater en +XX X XX XX XX XX pour les numéros internationaux
  if (cleaned.length > 10) {
    const country = cleaned.substring(0, cleaned.length - 10)
    const local = cleaned.substring(cleaned.length - 10)
    return `+${country} ${local.replace(/(\d{1})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5')}`
  }
  
  return phone
}

/**
 * Tronquer un texte avec ellipse
 */
export function truncate(text: string, maxLength: number): string {
  if (!text || text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

/**
 * Capitaliser la première lettre
 */
export function capitalize(text: string): string {
  if (!text) return ''
  return text.charAt(0).toUpperCase() + text.slice(1)
}

/**
 * Formater un statut de cours avec émoji
 */
export function formatLessonStatus(status: string): string {
  const statusMap: { [key: string]: string } = {
    'pending': '⏳ En attente',
    'confirmed': '✅ Confirmé',
    'completed': '✓ Terminé',
    'cancelled': '❌ Annulé',
    'rescheduled': '🔄 Reprogrammé'
  }
  return statusMap[status] || status
}


