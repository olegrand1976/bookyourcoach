/**
 * Composable pour la gestion des couleurs du planning
 * Utilisé pour les disciplines, les statuts, et les indicateurs visuels
 */

/**
 * Couleurs par type d'activité/discipline
 */
const ACTIVITY_COLORS: { [key: number]: { bg: string; border: string; text: string } } = {
  1: { // Équitation
    bg: 'bg-amber-100',
    border: 'border-amber-400',
    text: 'text-amber-900'
  },
  2: { // Natation
    bg: 'bg-blue-100',
    border: 'border-blue-400',
    text: 'text-blue-900'
  },
  3: { // Fitness
    bg: 'bg-green-100',
    border: 'border-green-400',
    text: 'text-green-900'
  },
  4: { // Sports collectifs
    bg: 'bg-orange-100',
    border: 'border-orange-400',
    text: 'text-orange-900'
  },
  5: { // Arts martiaux
    bg: 'bg-red-100',
    border: 'border-red-400',
    text: 'text-red-900'
  },
  6: { // Danse
    bg: 'bg-pink-100',
    border: 'border-pink-400',
    text: 'text-pink-900'
  },
  7: { // Tennis
    bg: 'bg-lime-100',
    border: 'border-lime-400',
    text: 'text-lime-900'
  },
  8: { // Golf
    bg: 'bg-emerald-100',
    border: 'border-emerald-400',
    text: 'text-emerald-900'
  },
  9: { // Yoga
    bg: 'bg-purple-100',
    border: 'border-purple-400',
    text: 'text-purple-900'
  },
  10: { // Escalade
    bg: 'bg-slate-100',
    border: 'border-slate-400',
    text: 'text-slate-900'
  }
}

/**
 * Couleur par défaut
 */
const DEFAULT_COLOR = {
  bg: 'bg-gray-100',
  border: 'border-gray-400',
  text: 'text-gray-900'
}

/**
 * Obtenir les classes de couleur pour une discipline
 */
export function getDisciplineColors(activityTypeId: number | null | undefined): { bg: string; border: string; text: string } {
  if (!activityTypeId) return DEFAULT_COLOR
  return ACTIVITY_COLORS[activityTypeId] || DEFAULT_COLOR
}

/**
 * Obtenir la classe de fond pour une discipline
 */
export function getDisciplineBgClass(activityTypeId: number | null | undefined): string {
  return getDisciplineColors(activityTypeId).bg
}

/**
 * Obtenir la classe de bordure pour une discipline
 */
export function getDisciplineBorderClass(activityTypeId: number | null | undefined): string {
  return getDisciplineColors(activityTypeId).border
}

/**
 * Obtenir la classe de texte pour une discipline
 */
export function getDisciplineTextClass(activityTypeId: number | null | undefined): string {
  return getDisciplineColors(activityTypeId).text
}

/**
 * Couleurs pour les statuts de cours
 */
const STATUS_COLORS: { [key: string]: { bg: string; text: string } } = {
  'pending': {
    bg: 'bg-yellow-100',
    text: 'text-yellow-800'
  },
  'confirmed': {
    bg: 'bg-green-100',
    text: 'text-green-800'
  },
  'completed': {
    bg: 'bg-blue-100',
    text: 'text-blue-800'
  },
  'cancelled': {
    bg: 'bg-red-100',
    text: 'text-red-800'
  },
  'rescheduled': {
    bg: 'bg-purple-100',
    text: 'text-purple-800'
  }
}

/**
 * Obtenir les classes de couleur pour un statut
 */
export function getStatusColors(status: string): { bg: string; text: string } {
  return STATUS_COLORS[status] || { bg: 'bg-gray-100', text: 'text-gray-800' }
}

/**
 * Obtenir la classe de fond pour un statut
 */
export function getStatusBgClass(status: string): string {
  return getStatusColors(status).bg
}

/**
 * Obtenir la classe de texte pour un statut
 */
export function getStatusTextClass(status: string): string {
  return getStatusColors(status).text
}

/**
 * Couleurs pour les indicateurs de priorité
 */
export function getPriorityColor(priority: 'low' | 'medium' | 'high' | 'critical'): string {
  const colors = {
    'low': 'bg-blue-500',
    'medium': 'bg-yellow-500',
    'high': 'bg-orange-500',
    'critical': 'bg-red-500'
  }
  return colors[priority] || 'bg-gray-500'
}

/**
 * Couleurs pour les taux de remplissage
 */
export function getOccupancyColor(rate: number): { bg: string; text: string } {
  if (rate >= 90) {
    return { bg: 'bg-red-100', text: 'text-red-800' }
  }
  if (rate >= 70) {
    return { bg: 'bg-orange-100', text: 'text-orange-800' }
  }
  if (rate >= 50) {
    return { bg: 'bg-yellow-100', text: 'text-yellow-800' }
  }
  return { bg: 'bg-green-100', text: 'text-green-800' }
}

/**
 * Obtenir une couleur de fond basée sur le taux de remplissage
 */
export function getOccupancyBgClass(used: number, total: number): string {
  if (total === 0) return 'bg-gray-100'
  const rate = (used / total) * 100
  return getOccupancyColor(rate).bg
}

/**
 * Obtenir une couleur de texte basée sur le taux de remplissage
 */
export function getOccupancyTextClass(used: number, total: number): string {
  if (total === 0) return 'text-gray-800'
  const rate = (used / total) * 100
  return getOccupancyColor(rate).text
}

/**
 * Générer une couleur aléatoire (pour les éléments sans catégorie)
 */
export function getRandomColor(seed: string | number): string {
  const colors = [
    'bg-red-100', 'bg-orange-100', 'bg-amber-100', 'bg-yellow-100',
    'bg-lime-100', 'bg-green-100', 'bg-emerald-100', 'bg-teal-100',
    'bg-cyan-100', 'bg-sky-100', 'bg-blue-100', 'bg-indigo-100',
    'bg-violet-100', 'bg-purple-100', 'bg-fuchsia-100', 'bg-pink-100'
  ]
  
  // Utiliser le seed pour avoir une couleur cohérente
  const hash = typeof seed === 'string' 
    ? seed.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0)
    : seed
  
  return colors[hash % colors.length]
}

/**
 * Obtenir l'opacité en fonction de la disponibilité
 */
export function getAvailabilityOpacity(isAvailable: boolean): string {
  return isAvailable ? 'opacity-100' : 'opacity-50'
}

/**
 * Obtenir les classes pour un badge
 */
export function getBadgeClasses(type: 'info' | 'success' | 'warning' | 'error' = 'info'): string {
  const classes = {
    'info': 'bg-blue-100 text-blue-800 border-blue-200',
    'success': 'bg-green-100 text-green-800 border-green-200',
    'warning': 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'error': 'bg-red-100 text-red-800 border-red-200'
  }
  return classes[type]
}


