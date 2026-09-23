import { describe, it, expect } from 'vitest'
import {
  buildRecurringPlaceholder,
  buildTimeSlotAvailability,
  classifyOccurrence,
  computeRecurringPlaceholders,
  countOccupiedParallelLanes,
  findInactiveLessonFreeingOccurrence,
  isInactivePlanningLesson,
  lessonMaterializesRecurringOnDate,
  placeholderKey,
  type PlanningLessonLike,
  type RecurringSlotLike,
} from '~/composables/planning/usePlanningPlaceholders'

// 2026-07-01 est un mercredi (day_of_week 3) ; 2026-06-03 aussi, 4 semaines plus tôt.
const DATE = '2026-07-01'
const SLOT = { day_of_week: 3, start_time: '09:00:00', end_time: '12:00:00' }

function series(over: Partial<RecurringSlotLike> = {}): RecurringSlotLike {
  return {
    id: 42,
    status: 'active',
    student_id: 7,
    teacher_id: 3,
    day_of_week: 3,
    start_time: '10:00:00',
    end_time: '11:00:00',
    start_date: '2026-06-03',
    end_date: '2026-12-02',
    recurring_interval: 1,
    subscription_instance: { subscription: { template: { name: 'Formule 10', price: 250 } } },
    ...over,
  }
}

function lesson(over: Partial<PlanningLessonLike> = {}): PlanningLessonLike {
  return {
    id: 100,
    student_id: 7,
    teacher_id: 3,
    start_time: `${DATE}T10:00:00`,
    end_time: `${DATE}T11:00:00`,
    status: 'confirmed',
    course_type: { name: 'Dressage' },
    ...over,
  }
}

function compute(recurringSlots: RecurringSlotLike[], dayLessons: PlanningLessonLike[] = [], extra = {}) {
  return computeRecurringPlaceholders({
    recurringSlots,
    dateStr: DATE,
    slot: SLOT,
    dayFilteredLessons: dayLessons,
    dayLessons,
    ...extra,
  })
}

describe('isInactivePlanningLesson', () => {
  it('annulé ou soft-supprimé = inactif ; placeholder jamais inactif', () => {
    expect(isInactivePlanningLesson(lesson({ status: 'cancelled' }))).toBe(true)
    expect(isInactivePlanningLesson(lesson({ deleted_at: '2026-06-30T08:00:00' }))).toBe(true)
    expect(isInactivePlanningLesson(lesson({ deleted_at: '' }))).toBe(false)
    expect(isInactivePlanningLesson(lesson())).toBe(false)
    expect(isInactivePlanningLesson(lesson({ is_recurring_placeholder: true, status: 'cancelled' }))).toBe(false)
  })
})

describe('lessonMaterializesRecurringOnDate', () => {
  it('même élève + chevauchement partiel = matérialise', () => {
    expect(lessonMaterializesRecurringOnDate(lesson({ start_time: `${DATE}T10:30:00`, end_time: `${DATE}T11:30:00` }), series(), DATE)).toBe(true)
  })
  it('remplacement d\'enseignant : même élève, autre coach = matérialise', () => {
    expect(lessonMaterializesRecurringOnDate(lesson({ teacher_id: 99 }), series(), DATE)).toBe(true)
  })
  it('cours annulé ne matérialise pas ; autre élève non plus ; autre jour non plus', () => {
    expect(lessonMaterializesRecurringOnDate(lesson({ status: 'cancelled' }), series(), DATE)).toBe(false)
    expect(lessonMaterializesRecurringOnDate(lesson({ student_id: 8 }), series(), DATE)).toBe(false)
    expect(lessonMaterializesRecurringOnDate(lesson({ start_time: '2026-07-08T10:00:00', end_time: '2026-07-08T11:00:00' }), series(), DATE)).toBe(false)
  })
})

describe('computeRecurringPlaceholders — 8 conditions d\'exclusion', () => {
  it('cas nominal : émet un placeholder', () => {
    const out = compute([series()])
    expect(out).toHaveLength(1)
    expect(out[0].recurring_slot_id).toBe(42)
    expect(out[0].occurrence_date).toBe(DATE)
    expect(out[0].is_freed_occurrence).toBe(false)
  })
  it('1. série non active', () => {
    expect(compute([series({ status: 'cancelled' })])).toHaveLength(0)
  })
  it('2. date hors [start_date, end_date]', () => {
    expect(compute([series({ start_date: '2026-07-08' })])).toHaveLength(0)
    expect(compute([series({ end_date: '2026-06-24' })])).toHaveLength(0)
  })
  it('3. mauvais jour de semaine', () => {
    expect(compute([series({ day_of_week: 4 })])).toHaveLength(0)
  })
  it('4. fenêtre > 120 min (plage club, pas un cours)', () => {
    expect(compute([series({ end_time: '12:01:00' })])).toHaveLength(0)
  })
  it('5. intervalle : semaine impaire d\'une série bi-hebdo ne tire pas', () => {
    const biweekly = series({ recurring_interval: 2 })
    // 2026-06-24 = ancre + 3 semaines → weekIndex 3, impair
    expect(computeRecurringPlaceholders({ recurringSlots: [biweekly], dateStr: '2026-06-24', slot: SLOT, dayFilteredLessons: [], dayLessons: [] })).toHaveLength(0)
    // 2026-07-01 = ancre + 4 semaines → pair
    expect(compute([biweekly])).toHaveLength(1)
  })
  it('5bis. date sautée (skipped_dates)', () => {
    expect(compute([series({ skipped_dates: [DATE] })])).toHaveLength(0)
  })
  it('6. début hors fenêtre du créneau : borne basse incluse, borne haute exclue', () => {
    expect(compute([series({ start_time: '09:00:00', end_time: '10:00:00' })])).toHaveLength(1)
    expect(compute([series({ start_time: '12:00:00', end_time: '13:00:00' })])).toHaveLength(0)
    expect(compute([series({ start_time: '08:00:00', end_time: '09:00:00' })])).toHaveLength(0)
  })
  it('7. cours matérialisant présent', () => {
    expect(compute([series()], [lesson()])).toHaveLength(0)
  })
  it('8. occurrence libérée par annulation : exclue par défaut, émise avec includeFreed', () => {
    const cancelled = lesson({ status: 'cancelled', cancelled_by_role: 'student' })
    expect(compute([series()], [cancelled])).toHaveLength(0)
    const out = compute([series()], [cancelled], { includeFreed: true })
    expect(out).toHaveLength(1)
    expect(out[0].is_freed_occurrence).toBe(true)
    expect(out[0].freed_by_lesson_id).toBe(100)
  })
})

describe('findInactiveLessonFreeingOccurrence', () => {
  it('retourne le cours annulé du même élève qui chevauche ; ignore les autres', () => {
    const c = lesson({ id: 5, status: 'cancelled' })
    expect(findInactiveLessonFreeingOccurrence(series(), DATE, [lesson(), c])?.id).toBe(5)
    expect(findInactiveLessonFreeingOccurrence(series(), DATE, [lesson({ id: 6, status: 'cancelled', student_id: 8 })])).toBeNull()
    expect(findInactiveLessonFreeingOccurrence(series(), DATE, [lesson({ id: 7, status: 'cancelled', start_time: `${DATE}T14:00:00`, end_time: `${DATE}T15:00:00` })])).toBeNull()
  })
})

describe('buildRecurringPlaceholder / placeholderKey', () => {
  it('id négatif déterministe, statut recurring_series, libellé abonnement', () => {
    const p = buildRecurringPlaceholder(series(), DATE)
    expect(p.id).toBe(-(10_000_000 + 42))
    expect(p.status).toBe('recurring_series')
    expect(p.is_recurring_placeholder).toBe(true)
    expect(p.start_time).toBe(`${DATE}T10:00:00`)
    expect(p.course_type?.name).toBe('Abonnement · Formule 10')
    expect((p as any).price).toBe(250)
  })
  it('sans template : libellé générique et prix 0', () => {
    const p = buildRecurringPlaceholder(series({ subscription_instance: null }), DATE)
    expect(p.course_type?.name).toBe('Réservation récurrente (abonnement)')
    expect((p as any).price).toBe(0)
  })
  it('la clé distingue deux occurrences d\'une même série', () => {
    const a = buildRecurringPlaceholder(series(), DATE)
    const b = buildRecurringPlaceholder(series(), '2026-07-08')
    expect(a.id).toBe(b.id)
    expect(placeholderKey(a)).not.toBe(placeholderKey(b))
  })
})

describe('countOccupiedParallelLanes / buildTimeSlotAvailability', () => {
  const l1 = lesson({ id: 1, student_id: 1 })
  const l2 = lesson({ id: 2, student_id: 2, course_type: { name: 'Obstacle' } })
  const seriesPh = buildRecurringPlaceholder(series(), DATE)
  const freedPh = buildRecurringPlaceholder(series({ id: 43, student_id: 9 }), DATE, lesson({ id: 50, status: 'cancelled', student_id: 9 }))

  it('2 cours + 1 série sur 4 voies → [lesson, lesson, series, free], 1 libre', () => {
    const a = buildTimeSlotAvailability([l1, l2, seriesPh], 4)
    expect(a.occupied).toBe(3)
    expect(a.free).toBe(1)
    expect(a.lanes.map((x) => x.kind)).toEqual(['lesson', 'lesson', 'series', 'free'])
    expect(a.lanes[0].label).toBe('Dressage')
    expect(a.hasRecurringPlaceholder).toBe(true)
    expect(a.hasCancelledPlaceholder).toBe(false)
  })
  it('les annulés ne comptent pas', () => {
    expect(countOccupiedParallelLanes([l1, lesson({ id: 3, status: 'cancelled' })])).toBe(1)
  })
  it('place libérée : voie libre signalée « freed », comptée comme libre', () => {
    const a = buildTimeSlotAvailability([l1], 3, [freedPh])
    expect(a.occupied).toBe(1)
    expect(a.free).toBe(2)
    expect(a.hasCancelledPlaceholder).toBe(true)
    expect(a.lanes.map((x) => x.kind)).toEqual(['lesson', 'freed', 'free'])
  })
  it('surbooking : 5 occupés sur 4 voies → 5 voies, 0 libre', () => {
    const many = [1, 2, 3, 4, 5].map((i) => lesson({ id: i, student_id: i }))
    const a = buildTimeSlotAvailability(many, 4)
    expect(a.occupied).toBe(5)
    expect(a.free).toBe(0)
    expect(a.lanes).toHaveLength(5)
  })
})

describe('classifyOccurrence', () => {
  const ph = { recurring_slot_id: 42, occurrence_date: DATE }

  it('aucun cours source → trou de génération', () => {
    const out = classifyOccurrence(ph, null, false)
    expect(out.map((a) => a.kind)).toEqual(['generation_gap'])
    expect(out[0].recurringSlotId).toBe(42)
  })
  it('annulé par l\'élève → annulation élève, avec comptage abonnement', () => {
    const out = classifyOccurrence(ph, lesson({ id: 9, status: 'cancelled', cancelled_by_role: 'student', cancellation_count_in_subscription: true, cancelled_by_user: { name: 'Emma R.' } }), false)
    expect(out).toHaveLength(1)
    expect(out[0].kind).toBe('student_cancellation')
    expect(out[0].countsInSubscription).toBe(true)
    expect(out[0].sourceLessonId).toBe(9)
    expect(out[0].detail).toContain('Emma R.')
  })
  it('annulé par le club ou soft-supprimé → annulation club', () => {
    expect(classifyOccurrence(ph, lesson({ status: 'cancelled', cancelled_by_role: 'club' }), false)[0].kind).toBe('club_cancellation')
    expect(classifyOccurrence(ph, lesson({ status: 'cancelled', cancelled_by_role: 'teacher' }), false)[0].detail).toContain('enseignant')
    expect(classifyOccurrence(ph, lesson({ deleted_at: '2026-06-30T08:00:00' }), false)[0].detail).toContain('Supprimé')
  })
  it('jour de fermeture prime sur tout, même une annulation élève', () => {
    const out = classifyOccurrence(ph, lesson({ status: 'cancelled', cancelled_by_role: 'student' }), true)
    expect(out.map((a) => a.kind)).toEqual(['club_cancellation'])
  })
  it('summarizeAnomalies compte par famille dans l\'ordre, anomaliesRequireAttention cible trous et places récupérables', async () => {
    const { summarizeAnomalies, anomaliesRequireAttention } = await import('~/composables/planning/usePlanningPlaceholders')
    const list = [
      ...classifyOccurrence(ph, null, false, ['schedule_drift']),
      ...classifyOccurrence(ph, lesson({ status: 'cancelled', cancelled_by_role: 'club' }), false),
      ...classifyOccurrence(ph, null, false),
    ]
    expect(summarizeAnomalies(list)).toEqual([
      { kind: 'generation_gap', count: 2 },
      { kind: 'club_cancellation', count: 1 },
      { kind: 'inconsistency', count: 1 },
    ])
    expect(anomaliesRequireAttention(list)).toBe(true)
    expect(anomaliesRequireAttention(classifyOccurrence(ph, lesson({ status: 'cancelled', cancelled_by_role: 'club' }), false))).toBe(false)
    expect(anomaliesRequireAttention(classifyOccurrence(ph, lesson({ status: 'cancelled', cancelled_by_role: 'student' }), false))).toBe(true)
  })

  it('les incohérences se cumulent ; gap_in_series n\'est pas doublé', () => {
    const out = classifyOccurrence(ph, null, false, ['gap_in_series', 'schedule_drift', 'teacher_mismatch', 'unknown_code'])
    expect(out.map((a) => a.kind)).toEqual(['generation_gap', 'inconsistency', 'inconsistency'])
    expect(out.slice(1).map((a) => a.detail)).toEqual(['schedule_drift', 'teacher_mismatch'])
  })
})
