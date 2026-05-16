/** Mois civil strictement postérieur au mois en cours (côté navigateur). */
export function isPayrollPeriodInFuture(year: number, month: number, now: Date = new Date()): boolean {
  const currentYear = now.getFullYear()
  const currentMonth = now.getMonth() + 1
  return year > currentYear || (year === currentYear && month > currentMonth)
}

export function filterReportsNotInFuture<T extends { year: number; month: number }>(
  items: T[],
  now: Date = new Date(),
): T[] {
  return items.filter((r) => !isPayrollPeriodInFuture(r.year, r.month, now))
}

/** Dernier mois autorisé pour une année (0 si année future). */
export function maxAllowedPayrollMonthForYear(year: number, now: Date = new Date()): number {
  const currentYear = now.getFullYear()
  if (year > currentYear) return 0
  if (year < currentYear) return 12
  return now.getMonth() + 1
}

export type PayrollReportTeacherRow = {
  nom_enseignant?: string
  total_duree_cours_minutes?: number
  total_heures_cours?: number
}

/** Minutes de cours prestées (VH) pour le tri du détail rapport. */
export function payrollTeacherWorkedMinutes(row: PayrollReportTeacherRow): number {
  const minutes = Number(row.total_duree_cours_minutes ?? 0)
  if (minutes > 0) return minutes
  const hours = Number(row.total_heures_cours ?? 0)
  return Number.isFinite(hours) && hours > 0 ? Math.round(hours * 60) : 0
}

/** Entrées [teacherId, row] triées par heures prestées décroissantes, puis nom. */
export function sortPayrollReportTeachersEntries<T extends PayrollReportTeacherRow>(
  report: Record<string, T>,
): [string, T][] {
  return Object.entries(report).sort(([, a], [, b]) => {
    const diff = payrollTeacherWorkedMinutes(b) - payrollTeacherWorkedMinutes(a)
    if (diff !== 0) return diff
    return String(a.nom_enseignant ?? '').localeCompare(String(b.nom_enseignant ?? ''), 'fr')
  })
}
