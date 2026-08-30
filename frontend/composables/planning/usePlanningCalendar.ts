/**
 * Logique pure des vues calendrier mois / trimestre (planning club).
 * Extraite pour tests de cohérence des compteurs et de la sélection de créneau.
 */

import {
  getClubPlanningMaxDate,
  getClubPlanningMinDate,
  getMonthBounds,
  getQuarterBounds,
} from '~/composables/planning/useDateHelpers'
import {
  filterLessonsForOpenSlot,
  toLocalYmd,
  type OpenSlotTimeWindow,
} from '~/composables/planning/usePlanningLessonIndex'

/** Au-delà, l’union de plages se recentre sur la fenêtre demandée (évite la croissance infinie). */
export const CALENDAR_LOADED_RANGE_MAX_DAYS = 120

export type CalendarCountableLesson = {
  start_time?: string | null
  status?: string | null
  is_recurring_placeholder?: boolean
}

export type CalendarOpenSlot = OpenSlotTimeWindow & {
  id: number
  start_time: string
  end_time: string
  day_of_week: number
  is_active?: boolean
}

function isOpenSlotActive(slot: CalendarOpenSlot): boolean {
  return slot.is_active !== false
}

/** Compte les cours affichés par jour (hors annulés / placeholders récurrence). */
export function buildCalendarLessonCounts<T extends CalendarCountableLesson>(
  lessonsByLocalDate: Map<string, T[]>,
): Record<string, number> {
  const counts: Record<string, number> = {}
  for (const [ymd, list] of lessonsByLocalDate.entries()) {
    let n = 0
    for (const lesson of list) {
      if (lesson.is_recurring_placeholder) continue
      if (lesson.status === 'cancelled') continue
      n++
    }
    if (n > 0) counts[ymd] = n
  }
  return counts
}

/**
 * Choisit le créneau open-slot pour un jour du calendrier.
 * Conserve le créneau courant s’il matche le weekday et est actif ; sinon le seul match actif,
 * sinon celui (actif) avec le plus de cours actifs ce jour.
 */
export function pickOpenSlotForCalendarDay<T extends CalendarCountableLesson & { start_time: string }>(
  day: Date,
  openSlots: CalendarOpenSlot[],
  currentSlot: CalendarOpenSlot | null | undefined,
  dayLessons: T[],
  formatTime: (time: string) => string,
): CalendarOpenSlot | null {
  const dow = day.getDay()
  if (
    currentSlot
    && currentSlot.day_of_week === dow
    && isOpenSlotActive(currentSlot)
  ) {
    return currentSlot
  }
  const matching = openSlots
    .filter((s) => s.day_of_week === dow && isOpenSlotActive(s))
    .slice()
    .sort((a, b) => String(a.start_time).localeCompare(String(b.start_time)))
  if (matching.length === 0) return null
  if (matching.length === 1) return matching[0]

  let best = matching[0]
  let bestCount = -1
  for (const slot of matching) {
    const count = filterLessonsForOpenSlot(dayLessons, slot, formatTime).filter(
      (l) => !l.is_recurring_placeholder && l.status !== 'cancelled',
    ).length
    if (count > bestCount) {
      bestCount = count
      best = slot
    }
  }
  return best
}

/**
 * Union de deux plages de dates (chargement incrémental calendrier).
 * Bornée à la fenêtre métier −6/+18 mois ; si l’union dépasse
 * {@link CALENDAR_LOADED_RANGE_MAX_DAYS}, recentrage sur la fenêtre demandée.
 */
export function unionLoadedDateRange(
  prev: { start: Date | null; end: Date | null },
  nextStart: Date,
  nextEnd: Date,
  from: Date = new Date(),
): { start: Date; end: Date } {
  let start =
    prev.start && prev.start.getTime() < nextStart.getTime()
      ? new Date(prev.start)
      : new Date(nextStart)
  let end =
    prev.end && prev.end.getTime() > nextEnd.getTime()
      ? new Date(prev.end)
      : new Date(nextEnd)

  const min = getClubPlanningMinDate(from)
  const max = getClubPlanningMaxDate(from)
  if (start.getTime() < min.getTime()) start = new Date(min)
  if (end.getTime() > max.getTime()) end = new Date(max)

  const maxMs = CALENDAR_LOADED_RANGE_MAX_DAYS * 24 * 60 * 60 * 1000
  if (end.getTime() - start.getTime() > maxMs) {
    start = new Date(nextStart)
    end = new Date(nextEnd)
    if (start.getTime() < min.getTime()) start = new Date(min)
    if (end.getTime() > max.getTime()) end = new Date(max)
  }

  return { start, end }
}

/** Filtre les YYYY-MM-DD hors de la plage chargée. */
export function pruneYmdListToRange(
  dates: string[],
  range: { start: Date | null; end: Date | null },
): string[] {
  if (!range.start || !range.end) return dates
  const from = toLocalYmd(range.start)
  const to = toLocalYmd(range.end)
  return dates.filter((d) => d >= from && d <= to)
}

/** La période mois/trimestre chevauche la fenêtre métier −6 / +18 mois. */
export function calendarPeriodOverlapsPlanningWindow(
  anchor: Date,
  mode: 'month' | 'quarter',
  from: Date = new Date(),
): boolean {
  const bounds = mode === 'quarter' ? getQuarterBounds(anchor) : getMonthBounds(anchor)
  const min = getClubPlanningMinDate(from)
  const max = getClubPlanningMaxDate(from)
  return bounds.start.getTime() <= max.getTime() && bounds.end.getTime() >= min.getTime()
}

/**
 * Cohérence d’affichage : chaque jour du mois courant
 * doit correspondre au nombre de cours indexés (hors cancelled / placeholder).
 */
export function assertMonthCountsMatchIndex<T extends CalendarCountableLesson>(
  monthDays: Array<{ date: string; isCurrentMonth: boolean }>,
  lessonCounts: Record<string, number>,
  lessonsByLocalDate: Map<string, T[]>,
): { ok: boolean; mismatches: string[] } {
  const mismatches: string[] = []
  for (const day of monthDays) {
    if (!day.isCurrentMonth) continue
    const expected = (lessonsByLocalDate.get(day.date) ?? []).filter(
      (l) => !l.is_recurring_placeholder && l.status !== 'cancelled',
    ).length
    const shown = lessonCounts[day.date] ?? 0
    if (expected !== shown) {
      mismatches.push(`${day.date}: shown=${shown} expected=${expected}`)
    }
  }
  return { ok: mismatches.length === 0, mismatches }
}

/**
 * Toutes les dates (YYYY-MM-DD) du weekday dans [start, end] inclus.
 * dayOfWeek : 0 = dimanche … 6 = samedi (Date#getDay).
 */
export function listSlotOccurrenceDates(
  bounds: { start: Date; end: Date },
  dayOfWeek: number,
): string[] {
  const dates: string[] = []
  const cursor = new Date(bounds.start)
  cursor.setHours(12, 0, 0, 0)
  const end = new Date(bounds.end)
  end.setHours(23, 59, 59, 999)

  const offset = (dayOfWeek - cursor.getDay() + 7) % 7
  cursor.setDate(cursor.getDate() + offset)

  while (cursor.getTime() <= end.getTime()) {
    dates.push(toLocalYmd(cursor))
    cursor.setDate(cursor.getDate() + 7)
  }
  return dates
}

/**
 * Pour chaque date d’occurrence, les cours du créneau (filtre open-slot).
 * Placeholders récurrence inclus ; annulés exclus (comme densité / grille utile).
 */
export function groupLessonsByYmdForSlot<T extends CalendarCountableLesson & { start_time: string }>(
  occurrenceYmds: string[],
  lessonsByLocalDate: Map<string, T[]>,
  slot: OpenSlotTimeWindow,
  formatTime: (time: string) => string,
  options?: { includeCancelled?: boolean; includePlaceholders?: boolean },
): Record<string, T[]> {
  const includeCancelled = options?.includeCancelled === true
  const includePlaceholders = options?.includePlaceholders !== false
  const result: Record<string, T[]> = {}
  for (const ymd of occurrenceYmds) {
    const bucket = lessonsByLocalDate.get(ymd) ?? []
    const filtered = filterLessonsForOpenSlot(bucket, slot, formatTime).filter((l) => {
      if (!includeCancelled && l.status === 'cancelled') return false
      if (!includePlaceholders && l.is_recurring_placeholder) return false
      return true
    })
    filtered.sort((a, b) => String(a.start_time).localeCompare(String(b.start_time)))
    result[ymd] = filtered
  }
  return result
}

/** HH:mm local à partir d’un datetime ISO / MySQL. */
export function lessonLocalHm(datetime: string): string | null {
  const d = new Date(datetime)
  if (Number.isNaN(d.getTime())) return null
  const hh = String(d.getHours()).padStart(2, '0')
  const mm = String(d.getMinutes()).padStart(2, '0')
  return `${hh}:${mm}`
}

export function formatKanbanPlageLabel(startHm: string, endHm: string | null): string {
  if (endHm && endHm !== startHm) return `${startHm} – ${endHm}`
  return startHm
}

export type KanbanPlageBand<T> = {
  plageKey: string
  label: string
  stripeIndex: number
  rowKeys: string[]
  slots: Array<T | null>
}

export type KanbanPlageAlignOptions<T extends { start_time: string; end_time?: string; id?: string | number }> = {
  getStudentRowKey: (lesson: T) => string
  getStudentSortKey: (lesson: T) => string
}

/**
 * Bandes = plages HH:mm (début) ; lignes = union des élèves sur la période.
 * Même élève = même index de ligne dans toutes les colonnes (trou = null).
 */
export function buildKanbanPlageAlignedRows<
  T extends { start_time: string; end_time?: string; id?: string | number },
>(
  columns: Array<{ ymd: string; lessons: T[] }>,
  options: KanbanPlageAlignOptions<T>,
): { plages: Array<{ key: string; label: string }>; byYmd: Record<string, KanbanPlageBand<T>[]> } {
  const { getStudentRowKey, getStudentSortKey } = options

  /** ymd → plageKey → rowKey → lesson */
  const cell = new Map<string, Map<string, Map<string, T>>>()
  const plageEndHm = new Map<string, string>()
  const rowMeta = new Map<string, Map<string, { sortKey: string }>>()

  for (const col of columns) {
    const byPlage = new Map<string, Map<string, T>>()
    for (const lesson of col.lessons) {
      const plageKey = lessonLocalHm(lesson.start_time)
      if (!plageKey) continue
      const rowKey = getStudentRowKey(lesson)
      const endHm = lesson.end_time ? lessonLocalHm(lesson.end_time) : null
      if (endHm && !plageEndHm.has(plageKey)) plageEndHm.set(plageKey, endHm)

      let rowMap = byPlage.get(plageKey)
      if (!rowMap) {
        rowMap = new Map()
        byPlage.set(plageKey, rowMap)
      }
      if (!rowMap.has(rowKey)) {
        rowMap.set(rowKey, lesson)
      } else {
        // Une cellule = un élève ; en cas de doublon même jour/plage, préférer le cours réel au placeholder.
        const existing = rowMap.get(rowKey)!
        const existingPh = Boolean(
          (existing as { is_recurring_placeholder?: boolean }).is_recurring_placeholder,
        )
        const nextPh = Boolean(
          (lesson as { is_recurring_placeholder?: boolean }).is_recurring_placeholder,
        )
        if (existingPh && !nextPh) {
          rowMap.set(rowKey, lesson)
        }
      }

      let meta = rowMeta.get(plageKey)
      if (!meta) {
        meta = new Map()
        rowMeta.set(plageKey, meta)
      }
      if (!meta.has(rowKey)) {
        meta.set(rowKey, { sortKey: getStudentSortKey(lesson) })
      }
    }
    cell.set(col.ymd, byPlage)
  }

  const sortedPlageKeys = Array.from(
    new Set([...cell.values()].flatMap((m) => [...m.keys()])),
  ).sort((a, b) => a.localeCompare(b))

  const orderedRowsByPlage = new Map<string, string[]>()
  for (const plageKey of sortedPlageKeys) {
    const meta = rowMeta.get(plageKey) ?? new Map()
    const keys = Array.from(meta.keys()).sort((a, b) => {
      const byName = (meta.get(a)?.sortKey ?? '').localeCompare(meta.get(b)?.sortKey ?? '', 'fr', {
        sensitivity: 'base',
      })
      if (byName !== 0) return byName
      return a.localeCompare(b)
    })
    orderedRowsByPlage.set(plageKey, keys)
  }

  const plages = sortedPlageKeys.map((key) => ({
    key,
    label: formatKanbanPlageLabel(key, plageEndHm.get(key) ?? null),
  }))

  const byYmd: Record<string, KanbanPlageBand<T>[]> = {}
  for (const col of columns) {
    const byPlage = cell.get(col.ymd) ?? new Map()
    byYmd[col.ymd] = sortedPlageKeys.map((plageKey, stripeIndex) => {
      const rowKeys = orderedRowsByPlage.get(plageKey) ?? []
      const rowMap = byPlage.get(plageKey) ?? new Map()
      return {
        plageKey,
        label: formatKanbanPlageLabel(plageKey, plageEndHm.get(plageKey) ?? null),
        stripeIndex,
        rowKeys,
        slots: rowKeys.map((rk) => rowMap.get(rk) ?? null),
      }
    })
  }

  return { plages, byYmd }
}
