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
  total_duree_attente_minutes?: number
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

export function payrollTeacherWaitingMinutes(row: PayrollReportTeacherRow): number {
  const n = Number(row.total_duree_attente_minutes ?? 0)
  return Number.isFinite(n) && n > 0 ? Math.floor(n) : 0
}

/** Temps total presté = VH cours + attente payée. */
export function payrollTeacherTotalPrestedMinutes(row: PayrollReportTeacherRow): number {
  return payrollTeacherWorkedMinutes(row) + payrollTeacherWaitingMinutes(row)
}

/** Part attente / total presté (0–100), ou null si aucun temps presté. */
export function payrollTeacherWaitingSharePercent(row: PayrollReportTeacherRow): number | null {
  const total = payrollTeacherTotalPrestedMinutes(row)
  const waiting = payrollTeacherWaitingMinutes(row)
  if (total <= 0) return waiting > 0 ? 100 : null
  if (waiting <= 0) return 0
  return Math.round((waiting / total) * 1000) / 10
}

export function formatPayrollWaitingSharePercent(row: PayrollReportTeacherRow): string {
  const pct = payrollTeacherWaitingSharePercent(row)
  if (pct === null) return '—'
  return formatPercentValue(pct)
}

function formatPercentValue(pct: number): string {
  return `${pct.toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 1 })} %`
}

export function waitingSharePercentFromMinutes(lessonMinutes: number, waitingMinutes: number): number | null {
  const lesson = Math.max(0, Math.floor(lessonMinutes) || 0)
  const waiting = Math.max(0, Math.floor(waitingMinutes) || 0)
  const total = lesson + waiting
  if (total <= 0) return waiting > 0 ? 100 : null
  if (waiting <= 0) return 0
  return Math.round((waiting / total) * 1000) / 10
}

export function formatDayWaitingSharePercent(lessonMinutes: number, waitingMinutes: number): string {
  const pct = waitingSharePercentFromMinutes(lessonMinutes, waitingMinutes)
  if (pct === null) return '—'
  return formatPercentValue(pct)
}
