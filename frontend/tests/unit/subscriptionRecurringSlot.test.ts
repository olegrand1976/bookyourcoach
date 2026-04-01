import { describe, expect, it } from 'vitest'
import {
  calendarDaysBetweenLocal,
  subscriptionRecurringSlotFiresOnDate,
} from '~/utils/subscriptionRecurringSlot'

describe('subscriptionRecurringSlotFiresOnDate', () => {
  it('matches PHP RecurringSlotValidatorRecurringIntervalTest (biweekly from 2026-03-25 Wed)', () => {
    const slot = {
      recurring_interval: 2,
      start_date: '2026-03-25',
      end_date: '2027-06-30',
      day_of_week: 3,
      status: 'active',
    }
    expect(subscriptionRecurringSlotFiresOnDate(slot, '2026-03-25')).toBe(true)
    expect(subscriptionRecurringSlotFiresOnDate(slot, '2026-04-01')).toBe(false)
    expect(subscriptionRecurringSlotFiresOnDate(slot, '2026-04-08')).toBe(true)
  })

  it('fires every week when interval is 1', () => {
    const slot = {
      recurring_interval: 1,
      start_date: '2026-04-04',
      end_date: '2027-12-31',
      day_of_week: 6,
      status: 'active',
    }
    expect(subscriptionRecurringSlotFiresOnDate(slot, '2026-04-04')).toBe(true)
    expect(subscriptionRecurringSlotFiresOnDate(slot, '2026-04-11')).toBe(true)
    expect(subscriptionRecurringSlotFiresOnDate(slot, '2026-04-05')).toBe(false)
  })
})

describe('calendarDaysBetweenLocal', () => {
  it('counts whole calendar days independent of clock length (DST-safe)', () => {
    const a = new Date(2026, 2, 25)
    const b = new Date(2026, 3, 8)
    expect(calendarDaysBetweenLocal(a, b)).toBe(14)
  })
})
