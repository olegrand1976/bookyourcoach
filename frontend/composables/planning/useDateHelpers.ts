/**
 * Composable pour les utilitaires de manipulation de dates
 * Utilisé dans le planning pour la navigation et le formatage
 */

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


