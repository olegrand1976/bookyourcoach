/**
 * Indexation / filtrage des cours pour la grille planning club.
 * Extrait de planning.vue pour tests unitaires anti-régression.
 */

export function toLocalYmd(d: Date): string {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

export function toLocalHm(d: Date): string {
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}

export type PlanningIndexLesson = {
  start_time?: string | null
  is_recurring_placeholder?: boolean
  subscription_instances?: unknown[] | null
  student?: { subscription_instances?: unknown[] | null } | null
  students?: Array<{ subscription_instances?: unknown[] | null }> | null
}

/**
 * Index O(1) par jour local (YYYY-MM-DD).
 */
export function buildLessonsByLocalDate<T extends { start_time?: string | null }>(
  lessons: T[],
): Map<string, T[]> {
  const map = new Map<string, T[]>()
  for (const lesson of lessons) {
    if (!lesson?.start_time) continue
    const d = new Date(lesson.start_time)
    if (Number.isNaN(d.getTime())) continue
    const key = toLocalYmd(d)
    const bucket = map.get(key)
    if (bucket) {
      bucket.push(lesson)
    } else {
      map.set(key, [lesson])
    }
  }
  return map
}

export type OpenSlotTimeWindow = {
  day_of_week: number
  start_time: string
  end_time: string
}

/**
 * Filtre créneau (jour + plage horaire) sur une liste déjà bornée éventuellement par date.
 */
export function filterLessonsForOpenSlot<T extends { start_time: string }>(
  candidates: T[],
  slot: OpenSlotTimeWindow,
  formatTime: (time: string) => string,
): T[] {
  const slotStartTime = formatTime(slot.start_time)
  const slotEndTime = formatTime(slot.end_time)

  return candidates.filter((lesson) => {
    const lessonDate = new Date(lesson.start_time)
    if (Number.isNaN(lessonDate.getTime())) return false
    const lessonDay = lessonDate.getDay()
    const lessonTime = toLocalHm(lessonDate)
    return lessonDay === slot.day_of_week
      && lessonTime >= slotStartTime
      && lessonTime < slotEndTime
  })
}

/**
 * Badge 📋 : parité avec hasActiveSubscription (pivot lesson + abo actifs élève).
 */
export function lessonHasActiveSubscriptionBadge(lesson: PlanningIndexLesson | null | undefined): boolean {
  if (!lesson) return false
  if (lesson.is_recurring_placeholder) return true
  if (Array.isArray(lesson.subscription_instances) && lesson.subscription_instances.length > 0) {
    return true
  }
  if (lesson.student?.subscription_instances && lesson.student.subscription_instances.length > 0) {
    return true
  }
  if (Array.isArray(lesson.students)) {
    return lesson.students.some(
      (student) => Array.isArray(student.subscription_instances) && student.subscription_instances.length > 0,
    )
  }
  return false
}
