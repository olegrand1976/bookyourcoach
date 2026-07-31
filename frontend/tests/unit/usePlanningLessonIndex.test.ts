import { describe, it, expect } from 'vitest'
import {
  buildLessonsByLocalDate,
  filterLessonsForOpenSlot,
  lessonHasActiveSubscriptionBadge,
  toLocalHm,
  toLocalYmd,
} from '~/composables/planning/usePlanningLessonIndex'

const formatTime = (t: string) => t.substring(0, 5)

describe('toLocalYmd / toLocalHm', () => {
  it('formate une date locale YYYY-MM-DD et HH:mm', () => {
    const d = new Date(2026, 6, 31, 9, 5, 0) // 31 juil. 2026 09:05
    expect(toLocalYmd(d)).toBe('2026-07-31')
    expect(toLocalHm(d)).toBe('09:05')
  })
})

describe('buildLessonsByLocalDate', () => {
  it('indexe par jour local et ignore les start_time invalides', () => {
    const lessons = [
      { id: 1, start_time: '2026-07-31T10:00:00' },
      { id: 2, start_time: '2026-07-31T11:00:00' },
      { id: 3, start_time: '2026-08-01T09:00:00' },
      { id: 4, start_time: null },
      { id: 5, start_time: 'not-a-date' },
    ]
    const map = buildLessonsByLocalDate(lessons)
    expect(map.get('2026-07-31')?.map((l) => l.id)).toEqual([1, 2])
    expect(map.get('2026-08-01')?.map((l) => l.id)).toEqual([3])
    expect(map.has('invalid')).toBe(false)
  })
})

describe('filterLessonsForOpenSlot', () => {
  const slot = { day_of_week: 5, start_time: '09:00:00', end_time: '12:00:00' } // vendredi

  it('ne garde que les cours du jour et dans la plage horaire', () => {
    // vendredi 31 juil. 2026
    const candidates = [
      { id: 1, start_time: '2026-07-31T09:00:00' },
      { id: 2, start_time: '2026-07-31T11:30:00' },
      { id: 3, start_time: '2026-07-31T12:00:00' }, // fin exclusive
      { id: 4, start_time: '2026-07-30T10:00:00' }, // jeudi
    ]
    const filtered = filterLessonsForOpenSlot(candidates, slot, formatTime)
    expect(filtered.map((l) => l.id)).toEqual([1, 2])
  })

  it('retourne vide si aucun candidat', () => {
    expect(filterLessonsForOpenSlot([], slot, formatTime)).toEqual([])
  })
})

describe('lessonHasActiveSubscriptionBadge', () => {
  it('vrai pour placeholder récurrent', () => {
    expect(lessonHasActiveSubscriptionBadge({ is_recurring_placeholder: true })).toBe(true)
  })

  it('vrai si subscription_instances sur le cours (payload slim)', () => {
    expect(lessonHasActiveSubscriptionBadge({
      subscription_instances: [{ id: 12 }],
    })).toBe(true)
  })

  it('vrai si abo actif sur student (payload historique)', () => {
    expect(lessonHasActiveSubscriptionBadge({
      student: { subscription_instances: [{ id: 1 }] },
    })).toBe(true)
  })

  it('faux sans lien abo', () => {
    expect(lessonHasActiveSubscriptionBadge({
      id: 1,
      subscription_instances: [],
      student: { subscription_instances: [] },
      students: [],
    } as any)).toBe(false)
  })
})
