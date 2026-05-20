import { describe, it, expect } from 'vitest'
import {
  addMonthsForSlotWeekday,
  getClubPlanningMaxDate,
  getClubPlanningMinDate,
  getDefaultDateForSlotDay,
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
