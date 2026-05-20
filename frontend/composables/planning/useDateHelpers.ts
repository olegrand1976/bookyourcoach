/**
 * Composable pour les utilitaires de manipulation de dates
 * Utilisé dans le planning pour la navigation et le formatage
 */

/** Fenêtre de navigation du planning club (depuis aujourd'hui). */
export const CLUB_PLANNING_MONTHS_BACK = 6
export const CLUB_PLANNING_MONTHS_FORWARD = 18

export function getClubPlanningMinDate(from: Date = new Date()): Date {
  const d = new Date(from)
  d.setMonth(d.getMonth() - CLUB_PLANNING_MONTHS_BACK)
  d.setHours(0, 0, 0, 0)
  return d
}

export function getClubPlanningMaxDate(from: Date = new Date()): Date {
  const d = new Date(from)
  d.setMonth(d.getMonth() + CLUB_PLANNING_MONTHS_FORWARD)
  d.setHours(23, 59, 59, 999)
  return d
}

export function isDateWithinClubPlanningRange(date: Date, from: Date = new Date()): boolean {
  const d = new Date(date)
  d.setHours(12, 0, 0, 0)
  const min = getClubPlanningMinDate(from)
  const max = getClubPlanningMaxDate(from)
  min.setHours(0, 0, 0, 0)
  max.setHours(23, 59, 59, 999)
  return d >= min && d <= max
}

/**
 * Décale d'un mois calendaire en conservant le jour du créneau (ex. samedi)
 * le plus proche du même numéro de jour dans le mois.
 */
/**
 * Date par défaut pour un créneau : aujourd'hui si c'est le bon jour de semaine,
 * sinon la prochaine occurrence (sans sauter à la semaine suivante le jour même).
 */
export function getDefaultDateForSlotDay(dayOfWeek: number, from: Date = new Date()): Date {
  const today = new Date(from)
  today.setHours(0, 0, 0, 0)
  let daysToAdd = dayOfWeek - today.getDay()
  if (daysToAdd < 0) {
    daysToAdd += 7
  }
  const result = new Date(today)
  result.setDate(today.getDate() + daysToAdd)
  return result
}

export function addMonthsForSlotWeekday(date: Date, monthDelta: number, dayOfWeek: number): Date {
  const anchorDay = date.getDate()
  const probe = new Date(date.getFullYear(), date.getMonth() + monthDelta, 1)
  const monthIndex = probe.getMonth()
  const yearAdj = probe.getFullYear()

  const candidates: Date[] = []
  const first = new Date(yearAdj, monthIndex, 1)
  const offset = (dayOfWeek - first.getDay() + 7) % 7
  first.setDate(1 + offset)

  for (let d = new Date(first); d.getMonth() === monthIndex; d.setDate(d.getDate() + 7)) {
    candidates.push(new Date(d))
  }

  if (candidates.length === 0) {
    return new Date(date)
  }

  let best = candidates[0]
  let bestDist = Math.abs(best.getDate() - anchorDay)
  for (const c of candidates) {
    const dist = Math.abs(c.getDate() - anchorDay)
    if (dist < bestDist) {
      best = c
      bestDist = dist
    }
  }

  const result = new Date(best)
  result.setHours(0, 0, 0, 0)
  return result
}

/**
 * Formater une date au format YYYY-MM-DD
 */
export function formatDateToISO(date: Date | string): string {
  const d = typeof date === 'string' ? new Date(date) : date
  return d.toISOString().split('T')[0]
}

/**
 * Obtenir le début de la semaine (lundi)
 */
export function getWeekStart(date: Date): Date {
  const d = new Date(date)
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1) // Ajuster si dimanche
  return new Date(d.setDate(diff))
}

/**
 * Obtenir la fin de la semaine (dimanche)
 */
export function getWeekEnd(date: Date): Date {
  const start = getWeekStart(date)
  const end = new Date(start)
  end.setDate(start.getDate() + 6)
  return end
}

/**
 * Obtenir un tableau des 7 jours de la semaine
 */
export function getWeekDays(weekStart: Date): string[] {
  const days: string[] = []
  const start = new Date(weekStart)
  
  for (let i = 0; i < 7; i++) {
    const day = new Date(start)
    day.setDate(start.getDate() + i)
    days.push(formatDateToISO(day))
  }
  
  return days
}

/**
 * Vérifier si deux dates sont le même jour
 */
export function isSameDay(date1: Date | string, date2: Date | string): boolean {
  return formatDateToISO(date1) === formatDateToISO(date2)
}

/**
 * Vérifier si une date est aujourd'hui
 */
export function isToday(date: Date | string): boolean {
  return isSameDay(date, new Date())
}

/**
 * Ajouter/soustraire des jours à une date
 */
export function addDays(date: Date, days: number): Date {
  const result = new Date(date)
  result.setDate(result.getDate() + days)
  return result
}

/**
 * Ajouter/soustraire des semaines à une date
 */
export function addWeeks(date: Date, weeks: number): Date {
  return addDays(date, weeks * 7)
}

/**
 * Obtenir le nom du jour de la semaine en français
 */
export function getDayName(date: Date | string, format: 'long' | 'short' = 'long'): string {
  const d = typeof date === 'string' ? new Date(date) : date
  const options: Intl.DateTimeFormatOptions = { weekday: format }
  return d.toLocaleDateString('fr-FR', options)
}

/**
 * Obtenir le nom du mois en français
 */
export function getMonthName(date: Date | string, format: 'long' | 'short' = 'long'): string {
  const d = typeof date === 'string' ? new Date(date) : date
  const options: Intl.DateTimeFormatOptions = { month: format }
  return d.toLocaleDateString('fr-FR', options)
}

/**
 * Formater une date en français (ex: "Lundi 15 octobre 2025")
 */
export function formatDateLong(date: Date | string): string {
  const d = typeof date === 'string' ? new Date(date) : date
  const dayName = getDayName(d, 'long')
  const day = d.getDate()
  const monthName = getMonthName(d, 'long')
  const year = d.getFullYear()
  
  return `${dayName.charAt(0).toUpperCase() + dayName.slice(1)} ${day} ${monthName} ${year}`
}

/**
 * Formater une date courte (ex: "15 oct.")
 */
export function formatDateShort(date: Date | string): string {
  const d = typeof date === 'string' ? new Date(date) : date
  const day = d.getDate()
  const monthName = getMonthName(d, 'short')
  
  return `${day} ${monthName}.`
}

/**
 * Formater une plage de dates (ex: "15 - 21 octobre 2025")
 */
export function formatDateRange(startDate: Date | string, endDate: Date | string): string {
  const start = typeof startDate === 'string' ? new Date(startDate) : startDate
  const end = typeof endDate === 'string' ? new Date(endDate) : endDate
  
  const startDay = start.getDate()
  const endDay = end.getDate()
  const monthName = getMonthName(start, 'long')
  const year = start.getFullYear()
  
  // Si même mois
  if (start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear()) {
    return `${startDay} - ${endDay} ${monthName} ${year}`
  }
  
  // Si mois différents
  const endMonthName = getMonthName(end, 'long')
  if (start.getFullYear() === end.getFullYear()) {
    return `${startDay} ${monthName} - ${endDay} ${endMonthName} ${year}`
  }
  
  // Si années différentes
  const endYear = end.getFullYear()
  return `${startDay} ${monthName} ${year} - ${endDay} ${endMonthName} ${endYear}`
}

/**
 * Convertir un jour de la semaine en numéro (0 = dimanche, 1 = lundi, ..., 6 = samedi)
 */
export function getDayOfWeek(date: Date | string): number {
  const d = typeof date === 'string' ? new Date(date) : date
  return d.getDay()
}

/**
 * Convertir un numéro de jour en nom de jour (pour les créneaux)
 * 0 = dimanche, 1 = lundi, etc.
 */
export function dayNumberToName(dayNumber: number): string {
  const days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi']
  return days[dayNumber] || ''
}

/**
 * Convertir un nom de jour en numéro
 */
export function dayNameToNumber(dayName: string): number {
  const days: { [key: string]: number } = {
    'sunday': 0,
    'monday': 1,
    'tuesday': 2,
    'wednesday': 3,
    'thursday': 4,
    'friday': 5,
    'saturday': 6,
    'dimanche': 0,
    'lundi': 1,
    'mardi': 2,
    'mercredi': 3,
    'jeudi': 4,
    'vendredi': 5,
    'samedi': 6
  }
  return days[dayName.toLowerCase()] ?? -1
}


