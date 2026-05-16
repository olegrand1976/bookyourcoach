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
