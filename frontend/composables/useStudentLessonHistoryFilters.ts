/**
 * Filtres période (trimestre) et regroupement mensuel pour l'historique cours élève (club).
 */

export type LessonStatusFilter = 'all' | 'pending' | 'confirmed' | 'completed' | 'cancelled'
export type LessonPeriodMode = 'upcoming_quarter' | 'quarter_all' | 'with_previous_quarter'

export interface CalendarQuarterBounds {
  start: Date
  end: Date
  quarter: number
  year: number
}

export interface LessonHistoryPeriod {
  from: Date
  to: Date
  label: string
}

export function startOfLocalDay(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0, 0)
}

export function endOfLocalDay(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate(), 23, 59, 59, 999)
}

/** Fin de la veille (pour bornes « passé »). */
export function endOfPreviousLocalDay(referenceDate: Date = new Date()): Date {
  const today = startOfLocalDay(referenceDate)

  return new Date(today.getTime() - 1)
}

/** Trimestre calendaire (T1 = jan–mar, …). */
export function getCalendarQuarterBounds(referenceDate: Date = new Date()): CalendarQuarterBounds {
  const year = referenceDate.getFullYear()
  const month = referenceDate.getMonth()
  const quarterIndex = Math.floor(month / 3)
  const start = new Date(year, quarterIndex * 3, 1, 0, 0, 0, 0)
  const end = new Date(year, quarterIndex * 3 + 3, 0, 23, 59, 59, 999)

  return { start, end, quarter: quarterIndex + 1, year }
}

export function getPreviousCalendarQuarterBounds(referenceDate: Date = new Date()): CalendarQuarterBounds {
  const { start } = getCalendarQuarterBounds(referenceDate)
  const previousReference = new Date(start.getTime() - 1)

  return getCalendarQuarterBounds(previousReference)
}

/** Période des cours à venir selon le mode sélectionné. */
export function resolveUpcomingLessonPeriod(
  mode: LessonPeriodMode,
  referenceDate: Date = new Date(),
): LessonHistoryPeriod {
  const current = getCalendarQuarterBounds(referenceDate)
  const today = startOfLocalDay(referenceDate)
  const from = today.getTime() > current.start.getTime() ? today : current.start

  if (mode === 'with_previous_quarter') {
    return {
      from,
      to: current.end,
      label: `À venir — T${current.quarter} ${current.year}`,
    }
  }

  if (mode === 'quarter_all') {
    return {
      from,
      to: current.end,
      label: `À venir — trimestre T${current.quarter} ${current.year}`,
    }
  }

  return {
    from,
    to: current.end,
    label: `À venir — T${current.quarter} ${current.year}`,
  }
}

/** Période des cours passés (null si aucune plage passée dans le périmètre). */
export function resolvePastLessonPeriod(
  mode: LessonPeriodMode,
  referenceDate: Date = new Date(),
): LessonHistoryPeriod | null {
  const current = getCalendarQuarterBounds(referenceDate)
  const today = startOfLocalDay(referenceDate)
  const pastEnd = endOfPreviousLocalDay(referenceDate)

  if (pastEnd.getTime() < current.start.getTime()) {
    return null
  }

  if (mode === 'with_previous_quarter') {
    const previous = getPreviousCalendarQuarterBounds(referenceDate)

    return {
      from: previous.start,
      to: pastEnd,
      label: `Passés — T${previous.quarter} ${previous.year} à aujourd'hui`,
    }
  }

  const from = current.start
  if (pastEnd.getTime() < from.getTime()) {
    return null
  }

  return {
    from,
    to: pastEnd,
    label: `Passés — T${current.quarter} ${current.year}`,
  }
}

/** @deprecated Utiliser resolveUpcomingLessonPeriod */
export function resolveLessonHistoryPeriod(
  mode: LessonPeriodMode | 'current_quarter',
  referenceDate: Date = new Date(),
): LessonHistoryPeriod {
  const normalized: LessonPeriodMode =
    mode === 'current_quarter' ? 'upcoming_quarter' : (mode as LessonPeriodMode)

  return resolveUpcomingLessonPeriod(normalized, referenceDate)
}

export function lessonIsUpcoming(
  lesson: { start_time: string },
  referenceDate: Date = new Date(),
): boolean {
  return new Date(lesson.start_time).getTime() >= startOfLocalDay(referenceDate).getTime()
}

export function lessonNeedsCertificateConfirmation(lesson: {
  status?: string
  cancellation_reason?: string
  cancellation_certificate_status?: string
  cancellation_certificate_path?: string
}): boolean {
  return (
    lesson.status === 'cancelled' &&
    lesson.cancellation_reason === 'medical' &&
    (lesson.cancellation_certificate_status === 'pending' || !!lesson.cancellation_certificate_path)
  )
}

export function lessonMatchesHistoryFilters(
  lesson: { start_time: string; status: string; cancellation_reason?: string; cancellation_certificate_status?: string },
  statusFilter: LessonStatusFilter,
  from: Date,
  to: Date,
  options: { alwaysShowPendingMedicalCerts?: boolean } = {},
): boolean {
  if (
    options.alwaysShowPendingMedicalCerts &&
    lesson.status === 'cancelled' &&
    lesson.cancellation_reason === 'medical' &&
    lesson.cancellation_certificate_status === 'pending'
  ) {
    return true
  }

  if (statusFilter !== 'all' && lesson.status !== statusFilter) {
    return false
  }

  const lessonTime = new Date(lesson.start_time).getTime()

  return lessonTime >= from.getTime() && lessonTime <= to.getTime()
}

export function compareLessonsForHistoryDisplay(
  a: { start_time: string; status?: string; cancellation_reason?: string; cancellation_certificate_status?: string; cancellation_certificate_path?: string },
  b: { start_time: string; status?: string; cancellation_reason?: string; cancellation_certificate_status?: string; cancellation_certificate_path?: string },
): number {
  const aCert = lessonNeedsCertificateConfirmation(a)
  const bCert = lessonNeedsCertificateConfirmation(b)
  if (aCert && !bCert) return -1
  if (!aCert && bCert) return 1

  return new Date(a.start_time).getTime() - new Date(b.start_time).getTime()
}

/** Cours passés : plus récents en premier. */
export function comparePastLessonsForHistoryDisplay(
  a: { start_time: string; status?: string; cancellation_reason?: string; cancellation_certificate_status?: string; cancellation_certificate_path?: string },
  b: { start_time: string; status?: string; cancellation_reason?: string; cancellation_certificate_status?: string; cancellation_certificate_path?: string },
): number {
  const aCert = lessonNeedsCertificateConfirmation(a)
  const bCert = lessonNeedsCertificateConfirmation(b)
  if (aCert && !bCert) return -1
  if (!aCert && bCert) return 1

  return new Date(b.start_time).getTime() - new Date(a.start_time).getTime()
}

export function groupLessonsByMonth<T extends { start_time: string }>(
  lessons: T[],
  compareFn: (a: T, b: T) => number = (a, b) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime(),
  options: { sortMonthsDescending?: boolean } = {},
): { key: string; label: string; lessons: T[] }[] {
  const byMonth = new Map<string, T[]>()

  for (const lesson of lessons) {
    const d = new Date(lesson.start_time)
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
    const bucket = byMonth.get(key)
    if (bucket) {
      bucket.push(lesson)
    } else {
      byMonth.set(key, [lesson])
    }
  }

  const entries = [...byMonth.entries()].sort(([keyA], [keyB]) =>
    options.sortMonthsDescending ? keyB.localeCompare(keyA) : keyA.localeCompare(keyB),
  )

  return entries.map(([key, monthLessons]) => {
    const [year, month] = key.split('-').map(Number)
    const label = new Date(year, month - 1, 1).toLocaleDateString('fr-FR', {
      month: 'long',
      year: 'numeric',
    })

    return {
      key,
      label,
      lessons: [...monthLessons].sort(compareFn),
    }
  })
}
