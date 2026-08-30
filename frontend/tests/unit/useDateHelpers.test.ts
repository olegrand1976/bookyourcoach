import { describe, it, expect } from 'vitest'
import {
  addCalendarMonths,
  addMonthsForSlotWeekday,
  buildMonthCalendarDays,
  getClubPlanningMaxDate,
  getClubPlanningInitialRange,
  getClubPlanningLoadRangeAround,
  getClubPlanningMinDate,
  getDefaultDateForSlotDay,
  getMonthBounds,
  getQuarterBounds,
  getQuarterMonthAnchors,
  CLUB_PLANNING_INITIAL_WEEKS_BACK,
  CLUB_PLANNING_INITIAL_WEEKS_FORWARD,
  CLUB_PLANNING_LOAD_CHUNK_WEEKS,
  CLUB_PLANNING_MONTHS_FORWARD,
  isDateWithinClubPlanningRange,
} from '~/composables/planning/useDateHelpers'

describe('getDefaultDateForSlotDay', () => {
  it('reste sur aujourd’hui quand le jour du créneau est aujourd’hui', () => {
    const today = new Date(2026, 4, 19, 18, 30, 0) // mardi 19 mai 2026, soir
    const result = getDefaultDateForSlotDay(2, today) // mardi
    expect(result.getFullYear()).toBe(2026)
    expect(result.getMonth()).toBe(4)
    expect(result.getDate()).toBe(19)
  })

  it('cible le prochain jour du créneau si ce n’est pas aujourd’hui', () => {
    const today = new Date(2026, 4, 19) // mardi
    const result = getDefaultDateForSlotDay(6, today) // samedi
    expect(result.getDay()).toBe(6)
    expect(result.getDate()).toBe(23)
  })
})

describe('addMonthsForSlotWeekday', () => {
  it('avance au samedi le plus proche du même jour de mois', () => {
    // Samedi 9 mai 2026
    const sat = new Date(2026, 4, 9)
    const next = addMonthsForSlotWeekday(sat, 1, 6)
    expect(next.getDay()).toBe(6)
    expect(next.getMonth()).toBe(5) // juin
    expect(next.getFullYear()).toBe(2026)
  })

  it('recule d’un mois en conservant le jour de créneau', () => {
    const sat = new Date(2026, 5, 13)
    const prev = addMonthsForSlotWeekday(sat, -1, 6)
    expect(prev.getDay()).toBe(6)
    expect(prev.getMonth()).toBe(4)
  })
})

describe('club planning range', () => {
  it('autorise une date à +18 mois', () => {
    const today = new Date(2026, 4, 19)
    const future = new Date(today)
    future.setMonth(future.getMonth() + CLUB_PLANNING_MONTHS_FORWARD)
    expect(isDateWithinClubPlanningRange(future, today)).toBe(true)
  })

  it('refuse une date au-delà de la fenêtre max', () => {
    const today = new Date(2026, 4, 19)
    const tooFar = getClubPlanningMaxDate(today)
    tooFar.setDate(tooFar.getDate() + 2)
    expect(isDateWithinClubPlanningRange(tooFar, today)).toBe(false)
  })

  it('min date est 6 mois en arrière', () => {
    const today = new Date(2026, 4, 19)
    const min = getClubPlanningMinDate(today)
    expect(min.getMonth()).toBe(10) // novembre 2025
    expect(min.getFullYear()).toBe(2025)
  })
})

describe('club planning loading windows', () => {
  it('calcule une fenêtre initiale autour de la date de référence', () => {
    const today = new Date(2026, 4, 19, 10, 15, 30)
    const { start, end } = getClubPlanningInitialRange(today)
    const expectedStart = new Date(today)
    expectedStart.setDate(expectedStart.getDate() - CLUB_PLANNING_INITIAL_WEEKS_BACK * 7)
    expectedStart.setHours(0, 0, 0, 0)
    const expectedEnd = new Date(today)
    expectedEnd.setDate(expectedEnd.getDate() + CLUB_PLANNING_INITIAL_WEEKS_FORWARD * 7)
    expectedEnd.setHours(23, 59, 59, 999)
    expect(start.getTime()).toBe(expectedStart.getTime())
    expect(end.getTime()).toBe(expectedEnd.getTime())
  })

  it('calcule un chunk de chargement autour d’une date cible', () => {
    const target = new Date(2026, 6, 1, 9, 0, 0)
    const { start, end } = getClubPlanningLoadRangeAround(target)
    const expectedStart = new Date(target)
    expectedStart.setDate(expectedStart.getDate() - CLUB_PLANNING_LOAD_CHUNK_WEEKS * 7)
    expectedStart.setHours(0, 0, 0, 0)
    const expectedEnd = new Date(target)
    expectedEnd.setDate(expectedEnd.getDate() + CLUB_PLANNING_LOAD_CHUNK_WEEKS * 7)
    expectedEnd.setHours(23, 59, 59, 999)
    expect(start.getTime()).toBe(expectedStart.getTime())
    expect(end.getTime()).toBe(expectedEnd.getTime())
  })
})

describe('month / quarter helpers', () => {
  it('getMonthBounds couvre le mois calendaire', () => {
    const { start, end } = getMonthBounds(new Date(2026, 4, 19))
    expect(start.getFullYear()).toBe(2026)
    expect(start.getMonth()).toBe(4)
    expect(start.getDate()).toBe(1)
    expect(end.getMonth()).toBe(4)
    expect(end.getDate()).toBe(31)
  })

  it('getQuarterBounds pour mai → T2 (avr–juin)', () => {
    const { start, end } = getQuarterBounds(new Date(2026, 4, 19))
    expect(start.getMonth()).toBe(3) // avril
    expect(start.getDate()).toBe(1)
    expect(end.getMonth()).toBe(5) // juin
    expect(end.getDate()).toBe(30)
  })

  it('getQuarterMonthAnchors renvoie 3 mois', () => {
    const anchors = getQuarterMonthAnchors(new Date(2026, 4, 19))
    expect(anchors).toHaveLength(3)
    expect(anchors.map((d) => d.getMonth())).toEqual([3, 4, 5])
  })

  it('buildMonthCalendarDays démarre un lundi et fait 42 cases', () => {
    const days = buildMonthCalendarDays(new Date(2026, 4, 1), new Date(2026, 4, 19))
    expect(days).toHaveLength(42)
    expect(new Date(days[0].date + 'T12:00:00').getDay()).toBe(1)
    const todayCell = days.find((d) => d.date === '2026-05-19')
    expect(todayCell?.isToday).toBe(true)
    expect(todayCell?.isCurrentMonth).toBe(true)
  })

  it('addCalendarMonths gère les fins de mois', () => {
    const d = addCalendarMonths(new Date(2026, 0, 31), 1)
    expect(d.getMonth()).toBe(1)
    expect(d.getDate()).toBe(28)
  })
})
