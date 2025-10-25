/**
 * Composable pour les calculs mathématiques du planning
 * Utilisé pour PGCD, pas de temps, disponibilités, etc.
 */

/**
 * Pas de temps par défaut (en minutes)
 */
export const BASE_SLOT_TIME_STEP = 30

/**
 * Calculer le Plus Grand Commun Diviseur (PGCD) de deux nombres
 */
export function gcd(a: number, b: number): number {
  return b === 0 ? a : gcd(b, a % b)
}

/**
 * Calculer le PGCD d'un tableau de nombres
 */
export function gcdArray(numbers: number[]): number {
  if (!numbers || numbers.length === 0) return BASE_SLOT_TIME_STEP
  if (numbers.length === 1) return numbers[0]
  
  let result = numbers[0]
  for (let i = 1; i < numbers.length; i++) {
    result = gcd(result, numbers[i])
  }
  
  return result
}

/**
 * Calculer le pas de créneau optimal basé sur les durées des types de cours disponibles
 * @param courseTypes - Les types de cours disponibles pour un créneau
 * @returns Le pas de créneau en minutes (minimum 5, maximum 60)
 */
export function calculateSlotTimeStep(courseTypes: any[]): number {
  if (!courseTypes || courseTypes.length === 0) {
    return BASE_SLOT_TIME_STEP
  }
  
  // Extraire les durées des types de cours
  const durations = courseTypes
    .map(ct => ct.duration_minutes)
    .filter(d => d && d > 0) // Filtrer les durées nulles ou invalides
  
  if (durations.length === 0) {
    return BASE_SLOT_TIME_STEP
  }
  
  // Calculer le PGCD de toutes les durées
  const step = gcdArray(durations)
  
  // Assurer que le pas est raisonnable (entre 5 et 60 minutes)
  const minStep = 5
  const maxStep = 60
  const finalStep = Math.max(minStep, Math.min(step, maxStep))
  
  console.log(`📏 SLOT_TIME_STEP calculé: ${finalStep}min (durées: ${durations.join(', ')}min)`)
  
  return finalStep
}

/**
 * Trouver la plus petite durée parmi les types de cours
 */
export function findMinDuration(courseTypes: any[]): number {
  if (!courseTypes || courseTypes.length === 0) return 60
  
  const durations = courseTypes
    .map(ct => ct.duration_minutes)
    .filter(d => d && d > 0)
  
  if (durations.length === 0) return 60
  
  return Math.min(...durations)
}

/**
 * Trouver la plus grande durée parmi les types de cours
 */
export function findMaxDuration(courseTypes: any[]): number {
  if (!courseTypes || courseTypes.length === 0) return 60
  
  const durations = courseTypes
    .map(ct => ct.duration_minutes)
    .filter(d => d && d > 0)
  
  if (durations.length === 0) return 60
  
  return Math.max(...durations)
}

/**
 * Calculer le nombre de créneaux possibles dans une plage horaire
 */
export function calculatePossibleSlots(
  startMinutes: number, 
  endMinutes: number, 
  duration: number,
  timeStep: number
): number {
  let count = 0
  
  for (let minutes = startMinutes; minutes < endMinutes; minutes += timeStep) {
    if (minutes + duration <= endMinutes) {
      count++
    }
  }
  
  return count
}

/**
 * Vérifier si deux plages horaires se chevauchent
 */
export function timesOverlap(
  start1: number, 
  end1: number, 
  start2: number, 
  end2: number
): boolean {
  return start1 < end2 && start2 < end1
}

/**
 * Vérifier si une heure est dans une plage
 */
export function isTimeInRange(
  time: number, 
  rangeStart: number, 
  rangeEnd: number
): boolean {
  return time >= rangeStart && time < rangeEnd
}

/**
 * Calculer le taux d'occupation (pourcentage)
 */
export function calculateOccupancyRate(used: number, total: number): number {
  if (total === 0) return 0
  return Math.round((used / total) * 100)
}

/**
 * Calculer le taux d'occupation global d'un créneau
 * Prend en compte toutes les heures possibles et leur remplissage
 */
export function calculateSlotOccupancyRate(
  lessonsCount: number,
  maxCapacity: number,
  possibleTimeSlots: number
): number {
  if (maxCapacity === 0 || possibleTimeSlots === 0) return 0
  
  const totalCapacity = maxCapacity * possibleTimeSlots
  return Math.round((lessonsCount / totalCapacity) * 100)
}

/**
 * Arrondir au multiple le plus proche
 */
export function roundToNearest(value: number, multiple: number): number {
  return Math.round(value / multiple) * multiple
}

/**
 * Arrondir vers le bas au multiple le plus proche
 */
export function floorToNearest(value: number, multiple: number): number {
  return Math.floor(value / multiple) * multiple
}

/**
 * Arrondir vers le haut au multiple le plus proche
 */
export function ceilToNearest(value: number, multiple: number): number {
  return Math.ceil(value / multiple) * multiple
}

/**
 * Calculer la durée entre deux heures (en minutes)
 */
export function calculateDuration(
  startHour: number, 
  startMinute: number, 
  endHour: number, 
  endMinute: number
): number {
  const startMinutes = startHour * 60 + startMinute
  const endMinutes = endHour * 60 + endMinute
  return endMinutes - startMinutes
}

/**
 * Vérifier si un cours peut tenir dans un créneau
 */
export function canFitInSlot(
  lessonStartMinutes: number,
  lessonDuration: number,
  slotEndMinutes: number
): boolean {
  return (lessonStartMinutes + lessonDuration) <= slotEndMinutes
}

/**
 * Calculer le nombre de minutes depuis minuit
 */
export function getMinutesFromMidnight(hour: number, minute: number): number {
  return hour * 60 + minute
}

/**
 * Obtenir l'heure et les minutes depuis les minutes totales
 */
export function getTimeFromMinutes(minutes: number): { hour: number; minute: number } {
  return {
    hour: Math.floor(minutes / 60),
    minute: minutes % 60
  }
}

/**
 * Calculer le prochain créneau disponible dans une plage
 */
export function findNextAvailableSlot(
  occupiedSlots: Array<{ start: number; end: number }>,
  slotStart: number,
  slotEnd: number,
  duration: number,
  timeStep: number
): number | null {
  // Trier les créneaux occupés par heure de début
  const sorted = [...occupiedSlots].sort((a, b) => a.start - b.start)
  
  // Chercher un créneau libre
  for (let time = slotStart; time < slotEnd; time += timeStep) {
    const proposedEnd = time + duration
    
    // Vérifier si le cours peut tenir
    if (proposedEnd > slotEnd) continue
    
    // Vérifier s'il n'y a pas de conflit
    const hasConflict = sorted.some(occupied => 
      timesOverlap(time, proposedEnd, occupied.start, occupied.end)
    )
    
    if (!hasConflict) {
      return time
    }
  }
  
  return null
}

/**
 * Calculer le revenu potentiel d'un créneau
 */
export function calculatePotentialRevenue(
  maxCapacity: number,
  possibleTimeSlots: number,
  averagePrice: number
): number {
  return maxCapacity * possibleTimeSlots * averagePrice
}

/**
 * Calculer le revenu actuel d'un créneau
 */
export function calculateActualRevenue(lessons: Array<{ price: number }>): number {
  return lessons.reduce((sum, lesson) => sum + (lesson.price || 0), 0)
}

/**
 * Calculer le taux de conversion (revenu actuel / revenu potentiel)
 */
export function calculateRevenueRate(actualRevenue: number, potentialRevenue: number): number {
  if (potentialRevenue === 0) return 0
  return Math.round((actualRevenue / potentialRevenue) * 100)
}

/**
 * Générer un tableau de créneaux possibles
 */
export function generateTimeSlots(
  startMinutes: number,
  endMinutes: number,
  step: number
): Array<{ minutes: number; hour: number; minute: number; timeStr: string }> {
  const slots = []
  
  for (let minutes = startMinutes; minutes < endMinutes; minutes += step) {
    const hour = Math.floor(minutes / 60)
    const minute = minutes % 60
    const timeStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`
    
    slots.push({ minutes, hour, minute, timeStr })
  }
  
  return slots
}


