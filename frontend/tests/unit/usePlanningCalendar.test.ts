import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import {
  assertMonthCountsMatchIndex,
  buildCalendarLessonCounts,
  calendarPeriodOverlapsPlanningWindow,
  pickOpenSlotForCalendarDay,
  pruneYmdListToRange,
  unionLoadedDateRange,
} from '~/composables/planning/usePlanningCalendar'
import {
  buildLessonsByLocalDate,
  toLocalYmd,
} from '~/composables/planning/usePlanningLessonIndex'
import {
  addCalendarMonths,
  buildMonthCalendarDays,
  getMonthBounds,
  getQuarterBounds,
  getQuarterMonthAnchors,
} from '~/composables/planning/useDateHelpers'
import PlanningMonthView from '~/components/planning/PlanningMonthView.vue'
import PlanningQuarterView from '~/components/planning/PlanningQuarterView.vue'

const formatTime = (t: string) => t.substring(0, 5)

describe('buildCalendarLessonCounts', () => {
  it('ignore annulés et placeholders, agrège par jour local', () => {
    const lessons = [
      { id: 1, start_time: '2026-05-12T10:00:00', status: 'confirmed' },
      { id: 2, start_time: '2026-05-12T11:00:00', status: 'cancelled' },
      { id: 3, start_time: '2026-05-12T12:00:00', status: 'confirmed', is_recurring_placeholder: true },
      { id: 4, start_time: '2026-05-13T09:00:00', status: 'confirmed' },
      { id: 5, start_time: '2026-05-13T10:00:00', status: 'confirmed' },
    ]
    const byDate = buildLessonsByLocalDate(lessons)
    const counts = buildCalendarLessonCounts(byDate)
    expect(counts).toEqual({
      '2026-05-12': 1,
      '2026-05-13': 2,
    })
  })

  it('n’émet pas de clé pour un jour sans cours actif', () => {
    const byDate = buildLessonsByLocalDate([
      { start_time: '2026-05-12T10:00:00', status: 'cancelled' },
    ])
    expect(buildCalendarLessonCounts(byDate)).toEqual({})
  })
})

describe('pickOpenSlotForCalendarDay', () => {
  const slots = [
    { id: 1, day_of_week: 2, start_time: '09:00:00', end_time: '12:00:00', is_active: true }, // mardi matin
    { id: 2, day_of_week: 2, start_time: '14:00:00', end_time: '18:00:00', is_active: true }, // mardi après-midi
    { id: 3, day_of_week: 5, start_time: '10:00:00', end_time: '12:00:00', is_active: true }, // vendredi
    { id: 4, day_of_week: 2, start_time: '08:00:00', end_time: '09:00:00', is_active: false }, // mardi inactif
  ]

  it('conserve le créneau courant s’il matche le weekday', () => {
    const day = new Date(2026, 4, 12) // mardi
    const picked = pickOpenSlotForCalendarDay(day, slots, slots[1], [], formatTime)
    expect(picked?.id).toBe(2)
  })

  it('ignore un créneau courant inactif et reprend un actif', () => {
    const day = new Date(2026, 4, 12)
    const picked = pickOpenSlotForCalendarDay(day, slots, slots[3], [], formatTime)
    expect(picked?.id).toBe(1)
  })

  it('exclut les créneaux is_active false de la sélection', () => {
    const day = new Date(2026, 4, 12)
    const onlyInactiveMorning = [
      slots[3],
      { id: 5, day_of_week: 2, start_time: '14:00:00', end_time: '18:00:00', is_active: true },
    ]
    const picked = pickOpenSlotForCalendarDay(day, onlyInactiveMorning, null, [], formatTime)
    expect(picked?.id).toBe(5)
  })

  it('prend le seul créneau du jour s’il n’y en a qu’un', () => {
    const day = new Date(2026, 4, 15) // vendredi
    const picked = pickOpenSlotForCalendarDay(day, slots, null, [], formatTime)
    expect(picked?.id).toBe(3)
  })

  it('parmi plusieurs, choisit celui avec le plus de cours actifs', () => {
    // mardi 12 mai 2026
    const day = new Date(2026, 4, 12)
    const dayLessons = [
      { start_time: '2026-05-12T09:30:00', status: 'confirmed' },
      { start_time: '2026-05-12T15:00:00', status: 'confirmed' },
      { start_time: '2026-05-12T16:00:00', status: 'confirmed' },
      { start_time: '2026-05-12T10:00:00', status: 'cancelled' },
    ]
    const picked = pickOpenSlotForCalendarDay(day, slots, null, dayLessons, formatTime)
    expect(picked?.id).toBe(2) // après-midi : 2 cours
  })

  it('retourne null s’il n’y a aucun créneau ce weekday', () => {
    const day = new Date(2026, 4, 10) // dimanche
    expect(pickOpenSlotForCalendarDay(day, slots, null, [], formatTime)).toBeNull()
  })
})

describe('unionLoadedDateRange / pruneYmdListToRange', () => {
  it('étend la plage sans rétrécir', () => {
    const prev = {
      start: new Date(2026, 3, 1),
      end: new Date(2026, 3, 30, 23, 59, 59, 999),
    }
    const union = unionLoadedDateRange(
      prev,
      new Date(2026, 4, 1),
      new Date(2026, 4, 31, 23, 59, 59, 999),
      new Date(2026, 4, 19),
    )
    expect(toLocalYmd(union.start)).toBe('2026-04-01')
    expect(toLocalYmd(union.end)).toBe('2026-05-31')
  })

  it('recentre sur la fenêtre demandée si l’union dépasse le plafond', () => {
    const prev = {
      start: new Date(2026, 0, 1),
      end: new Date(2026, 3, 30, 23, 59, 59, 999),
    }
    const nextStart = new Date(2026, 5, 1)
    const nextEnd = new Date(2026, 5, 30, 23, 59, 59, 999)
    const union = unionLoadedDateRange(prev, nextStart, nextEnd, new Date(2026, 4, 19))
    expect(toLocalYmd(union.start)).toBe('2026-06-01')
    expect(toLocalYmd(union.end)).toBe('2026-06-30')
  })

  it('prune les congés hors plage', () => {
    const pruned = pruneYmdListToRange(
      ['2026-03-15', '2026-05-01', '2026-06-01'],
      {
        start: new Date(2026, 4, 1),
        end: new Date(2026, 4, 31, 23, 59, 59, 999),
      },
    )
    expect(pruned).toEqual(['2026-05-01'])
  })
})

describe('calendarPeriodOverlapsPlanningWindow', () => {
  const today = new Date(2026, 4, 19)

  it('autorise le mois courant', () => {
    expect(calendarPeriodOverlapsPlanningWindow(today, 'month', today)).toBe(true)
  })

  it('refuse un trimestre trop loin dans le futur', () => {
    const far = addCalendarMonths(today, 30)
    expect(calendarPeriodOverlapsPlanningWindow(far, 'quarter', today)).toBe(false)
  })

  it('refuse un mois trop loin dans le passé', () => {
    const far = addCalendarMonths(today, -12)
    expect(calendarPeriodOverlapsPlanningWindow(far, 'month', today)).toBe(false)
  })
})

describe('cohérence compteurs ↔ index sur un mois / trimestre', () => {
  const lessons = [
    { id: 1, start_time: '2026-05-05T10:00:00', status: 'confirmed' },
    { id: 2, start_time: '2026-05-05T11:00:00', status: 'cancelled' },
    { id: 3, start_time: '2026-05-12T09:00:00', status: 'confirmed' },
    { id: 4, start_time: '2026-05-12T10:00:00', status: 'confirmed', is_recurring_placeholder: true },
    { id: 5, start_time: '2026-04-20T10:00:00', status: 'confirmed' },
    { id: 6, start_time: '2026-06-02T10:00:00', status: 'confirmed' },
  ]

  it('les compteurs du mois de mai matchent l’index filtré', () => {
    const byDate = buildLessonsByLocalDate(lessons)
    const counts = buildCalendarLessonCounts(byDate)
    const days = buildMonthCalendarDays(new Date(2026, 4, 1), new Date(2026, 4, 19))
    const { ok, mismatches } = assertMonthCountsMatchIndex(days, counts, byDate)
    expect(ok, mismatches.join('; ')).toBe(true)
    expect(counts['2026-05-05']).toBe(1)
    expect(counts['2026-05-12']).toBe(1)
    expect(counts['2026-04-20']).toBe(1) // présent dans l’index global
    // mais hors mois courant de la grille : assertMonthCountsMatchIndex ne le vérifie pas comme cellule courant
    const mayCells = days.filter((d) => d.isCurrentMonth)
    expect(mayCells.some((d) => d.date === '2026-04-20')).toBe(false)
  })

  it('le trimestre T2 couvre avril–juin et les 3 ancres sont cohérentes', () => {
    const anchor = new Date(2026, 4, 15)
    const { start, end } = getQuarterBounds(anchor)
    expect(toLocalYmd(start)).toBe('2026-04-01')
    expect(toLocalYmd(end)).toBe('2026-06-30')
    const anchors = getQuarterMonthAnchors(anchor)
    expect(anchors.map((d) => d.getMonth())).toEqual([3, 4, 5])

    const byDate = buildLessonsByLocalDate(lessons)
    const counts = buildCalendarLessonCounts(byDate)
    for (const monthAnchor of anchors) {
      const days = buildMonthCalendarDays(monthAnchor, new Date(2026, 4, 19))
      const { ok, mismatches } = assertMonthCountsMatchIndex(days, counts, byDate)
      expect(ok, `${toLocalYmd(monthAnchor)}: ${mismatches.join('; ')}`).toBe(true)
    }
  })

  it('getMonthBounds aligne date_from/date_to locaux sur le mois affiché', () => {
    const { start, end } = getMonthBounds(new Date(2026, 4, 19))
    expect(toLocalYmd(start)).toBe('2026-05-01')
    expect(toLocalYmd(end)).toBe('2026-05-31')
  })
})

describe('PlanningMonthView affichage', () => {
  it('affiche le compteur et le congé sur la bonne case', () => {
    const wrapper = mount(PlanningMonthView, {
      props: {
        displayDate: new Date(2026, 4, 1),
        lessonCounts: { '2026-05-12': 3 },
        closureDates: ['2026-05-13'],
        selectedYmd: '2026-05-12',
        showNav: true,
      },
    })
    const buttons = wrapper.findAll('[data-testid="planning-month-view"] button')
    const day12 = buttons.find((b) => b.attributes('aria-label')?.startsWith('2026-05-12'))
    const day13 = buttons.find((b) => b.attributes('aria-label')?.startsWith('2026-05-13'))
    expect(day12?.text()).toContain('3')
    expect(day12?.text()).toContain('cours')
    expect(day13?.text()).toContain('Congé')
    expect(day12?.classes().join(' ')).toMatch(/ring-2/)
  })

  it('émet select-day uniquement pour un jour du mois courant', async () => {
    const wrapper = mount(PlanningMonthView, {
      props: {
        displayDate: new Date(2026, 4, 1),
        lessonCounts: {},
        showNav: false,
      },
    })
    const day12 = wrapper
      .findAll('button')
      .find((b) => b.attributes('aria-label')?.startsWith('2026-05-12'))
    await day12!.trigger('click')
    expect(wrapper.emitted('select-day')?.[0]).toEqual(['2026-05-12'])
  })

  it('désactive prev/next selon canPrev/canNext', () => {
    const wrapper = mount(PlanningMonthView, {
      props: {
        displayDate: new Date(2026, 4, 1),
        canPrev: false,
        canNext: true,
        showNav: true,
      },
    })
    const prev = wrapper.find('button[aria-label="Mois précédent"]')
    const next = wrapper.find('button[aria-label="Mois suivant"]')
    expect(prev.attributes('disabled')).toBeDefined()
    expect(next.attributes('disabled')).toBeUndefined()
  })
})

describe('PlanningQuarterView', () => {
  it('rend 3 mini-mois pour le trimestre', () => {
    const wrapper = mount(PlanningQuarterView, {
      props: {
        displayDate: new Date(2026, 4, 15),
        lessonCounts: { '2026-05-12': 2, '2026-04-08': 1 },
        closureDates: [],
      },
    })
    expect(wrapper.text()).toContain('T2 2026')
    const months = wrapper.findAll('[data-testid="planning-month-view"]')
    expect(months).toHaveLength(3)
    // compteur compact sans le mot « cours »
    const mayDay = months[1]
      .findAll('button')
      .find((b) => b.attributes('aria-label')?.startsWith('2026-05-12'))
    expect(mayDay?.text()).toContain('2')
    expect(mayDay?.text()).not.toContain('cours')
  })
})
