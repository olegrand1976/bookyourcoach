import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import {
  assertMonthCountsMatchIndex,
  buildCalendarLessonCounts,
  buildKanbanHourAlignedRows,
  calendarPeriodOverlapsPlanningWindow,
  groupLessonsByYmdForSlot,
  listSlotOccurrenceDates,
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
import PlanningSlotKanbanView from '~/components/planning/PlanningSlotKanbanView.vue'

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

describe('listSlotOccurrenceDates', () => {
  it('liste les samedis de mai 2026', () => {
    const bounds = getMonthBounds(new Date(2026, 4, 1))
    const saturdays = listSlotOccurrenceDates(bounds, 6)
    expect(saturdays).toEqual([
      '2026-05-02',
      '2026-05-09',
      '2026-05-16',
      '2026-05-23',
      '2026-05-30',
    ])
  })

  it('liste les samedis du T2 2026 (avr–juin)', () => {
    const bounds = getQuarterBounds(new Date(2026, 4, 15))
    const saturdays = listSlotOccurrenceDates(bounds, 6)
    expect(saturdays[0]).toBe('2026-04-04')
    expect(saturdays.at(-1)).toBe('2026-06-27')
    expect(saturdays).toHaveLength(13)
    for (const ymd of saturdays) {
      expect(new Date(`${ymd}T12:00:00`).getDay()).toBe(6)
    }
  })
})

describe('groupLessonsByYmdForSlot', () => {
  const slot = { day_of_week: 6, start_time: '09:00:00', end_time: '12:00:00' }
  const formatTime = (t: string) => t.substring(0, 5)

  it('filtre le créneau, exclut annulés, garde placeholders', () => {
    const lessons = [
      { id: 1, start_time: '2026-05-09T09:30:00', status: 'confirmed' },
      { id: 2, start_time: '2026-05-09T10:00:00', status: 'cancelled' },
      { id: 3, start_time: '2026-05-09T11:00:00', status: 'confirmed', is_recurring_placeholder: true },
      { id: 4, start_time: '2026-05-09T14:00:00', status: 'confirmed' }, // hors plage
      { id: 5, start_time: '2026-05-08T10:00:00', status: 'confirmed' }, // vendredi
    ]
    const byDate = buildLessonsByLocalDate(lessons)
    const grouped = groupLessonsByYmdForSlot(
      ['2026-05-09', '2026-05-16'],
      byDate,
      slot,
      formatTime,
      { includeCancelled: false, includePlaceholders: true },
    )
    expect(grouped['2026-05-09'].map((l) => l.id)).toEqual([1, 3])
    expect(grouped['2026-05-16']).toEqual([])
  })
})

describe('buildKanbanHourAlignedRows', () => {
  const studentKey = (l: { student?: { user?: { name?: string } } }) =>
    l.student?.user?.name ?? ''

  it('aligne les bandes : padding null si une colonne a plus de cours à la même heure', () => {
    const columns = [
      {
        ymd: '2026-05-09',
        lessons: [
          { id: 1, start_time: '2026-05-09T16:00:00', student: { user: { name: 'A' } } },
          { id: 2, start_time: '2026-05-09T16:15:00', student: { user: { name: 'B' } } },
          { id: 3, start_time: '2026-05-09T16:30:00', student: { user: { name: 'C' } } },
          { id: 4, start_time: '2026-05-09T16:40:00', student: { user: { name: 'D' } } },
          { id: 5, start_time: '2026-05-09T16:50:00', student: { user: { name: 'E' } } },
        ],
      },
      {
        ymd: '2026-05-16',
        lessons: [
          { id: 10, start_time: '2026-05-16T16:00:00', student: { user: { name: 'Z' } } },
          { id: 11, start_time: '2026-05-16T16:20:00', student: { user: { name: 'Y' } } },
        ],
      },
    ]
    const { hours, byYmd } = buildKanbanHourAlignedRows(columns, studentKey)
    expect(hours).toEqual([{ key: 16, label: '16h' }])
    expect(byYmd['2026-05-09'][0].slots).toHaveLength(5)
    expect(byYmd['2026-05-09'][0].slots.every((s) => s !== null)).toBe(true)
    expect(byYmd['2026-05-16'][0].slots).toHaveLength(5)
    expect(byYmd['2026-05-16'][0].slots.filter((s) => s === null)).toHaveLength(3)
  })

  it('trie par élève dans une bande et unionne plusieurs heures', () => {
    const columns = [
      {
        ymd: '2026-05-09',
        lessons: [
          { id: 1, start_time: '2026-05-09T16:00:00', student: { user: { name: 'Zoé' } } },
          { id: 2, start_time: '2026-05-09T16:30:00', student: { user: { name: 'Alice' } } },
          { id: 3, start_time: '2026-05-09T09:00:00', student: { user: { name: 'Marc' } } },
        ],
      },
      {
        ymd: '2026-05-16',
        lessons: [
          { id: 4, start_time: '2026-05-16T10:00:00', student: { user: { name: 'Bob' } } },
        ],
      },
    ]
    const { hours, byYmd } = buildKanbanHourAlignedRows(columns, studentKey)
    expect(hours.map((h) => h.key)).toEqual([9, 10, 16])
    const band16 = byYmd['2026-05-09'].find((b) => b.hourKey === 16)!
    expect(band16.slots.map((s) => s?.student?.user?.name)).toEqual(['Alice', 'Zoé'])
    // colonne sans 16h : 2 slots null (max = 2)
    expect(byYmd['2026-05-16'].find((b) => b.hourKey === 16)!.slots).toEqual([null, null])
  })

  it('tie-break start_time puis id si même élève', () => {
    const columns = [
      {
        ymd: '2026-05-09',
        lessons: [
          { id: 2, start_time: '2026-05-09T16:30:00', student: { user: { name: 'Alice' } } },
          { id: 1, start_time: '2026-05-09T16:00:00', student: { user: { name: 'Alice' } } },
          { id: 3, start_time: '2026-05-09T16:00:00', student: { user: { name: 'Alice' } } },
        ],
      },
    ]
    const { byYmd } = buildKanbanHourAlignedRows(columns, studentKey)
    expect(byYmd['2026-05-09'][0].slots.map((s) => s?.id)).toEqual([1, 3, 2])
  })
})

describe('PlanningSlotKanbanView', () => {
  it('affiche une colonne par jour avec cours et pastille congé', async () => {
    const wrapper = mount(PlanningSlotKanbanView, {
      props: {
        title: 'mai 2026',
        slotLabel: 'Samedi • 09:00 – 12:00',
        columns: [
          {
            ymd: '2026-05-09',
            label: 'sam. 9 mai',
            isClosure: false,
            lessons: [
              {
                id: 1,
                start_time: '2026-05-09T09:30:00',
                course_type: { name: 'CSO' },
                student: { user: { name: 'Alice' } },
                teacher: { user: { name: 'Bob' } },
              },
            ],
          },
          {
            ymd: '2026-05-16',
            label: 'sam. 16 mai',
            isClosure: true,
            lessons: [],
          },
        ],
      },
    })
    expect(wrapper.text()).toContain('mai 2026')
    expect(wrapper.text()).toContain('Samedi • 09:00 – 12:00')
    expect(wrapper.text()).toContain('9h')
    const cols = wrapper.findAll('[data-ymd]')
    expect(cols).toHaveLength(2)
    expect(cols[0].text()).toContain('CSO')
    expect(cols[0].text()).toContain('Alice')
    expect(cols[1].text()).toContain('Congé')
    await cols[0].find('header button').trigger('click')
    expect(wrapper.emitted('select-day')?.[0]).toEqual(['2026-05-09'])
  })

  it('émet create-lesson depuis le haut et le bas de colonne', async () => {
    const wrapper = mount(PlanningSlotKanbanView, {
      props: {
        title: 'T2',
        columns: [
          {
            ymd: '2026-05-09',
            label: 'sam. 9',
            isClosure: false,
            lessons: [
              {
                id: 1,
                start_time: '2026-05-09T16:00:00',
                course_type: { name: 'Dressage' },
                student: { user: { name: 'Léa' } },
              },
            ],
          },
        ],
      },
    })
    expect(wrapper.text()).toContain('16h')
    const top = wrapper.find('[data-testid="kanban-create-top"]')
    const bottom = wrapper.find('[data-testid="kanban-create-bottom"]')
    expect(top.exists()).toBe(true)
    expect(bottom.exists()).toBe(true)
    await top.trigger('click')
    expect(wrapper.emitted('create-lesson')?.[0]).toEqual(['2026-05-09'])
    await bottom.trigger('click')
    expect(wrapper.emitted('create-lesson')?.[1]).toEqual(['2026-05-09'])
  })
})
