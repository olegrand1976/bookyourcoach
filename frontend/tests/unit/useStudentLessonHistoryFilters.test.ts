import { describe, it, expect } from 'vitest'
import {
  getPreviousCalendarQuarterBounds,
  resolveUpcomingLessonPeriod,
  resolvePastLessonPeriod,
  resolveFullLessonPeriod,
  lessonMatchesHistoryFilters,
  groupLessonsByMonth,
  comparePastLessonsForHistoryDisplay,
} from '~/composables/useStudentLessonHistoryFilters'

describe('useStudentLessonHistoryFilters', () => {
  it('resolves upcoming period from today until end of quarter', () => {
    const ref = new Date(2026, 4, 19, 12, 0, 0) // 19 mai 2026 → T2
    const period = resolveUpcomingLessonPeriod('upcoming_quarter', ref)

    expect(period.from.getFullYear()).toBe(2026)
    expect(period.from.getMonth()).toBe(4)
    expect(period.from.getDate()).toBe(19)
    expect(period.to.getMonth()).toBe(5) // fin juin
    expect(period.to.getDate()).toBe(30)
  })

  it('includes previous quarter for past period when mode is with_previous_quarter', () => {
    const ref = new Date(2026, 4, 19)
    const period = resolvePastLessonPeriod('with_previous_quarter', ref)
    const prev = getPreviousCalendarQuarterBounds(ref)

    expect(period).not.toBeNull()
    expect(period!.from.getTime()).toBe(prev.start.getTime())
  })

  it('filters by status and date range', () => {
    const from = new Date(2026, 0, 1)
    const to = new Date(2026, 2, 31, 23, 59, 59, 999)

    expect(
      lessonMatchesHistoryFilters(
        { start_time: '2026-02-15T10:00:00', status: 'cancelled' },
        'cancelled',
        from,
        to,
      ),
    ).toBe(true)

    expect(
      lessonMatchesHistoryFilters(
        { start_time: '2026-02-15T10:00:00', status: 'completed' },
        'cancelled',
        from,
        to,
      ),
    ).toBe(false)
  })

  it('groups lessons by month and sorts past in descending order', () => {
    const groups = groupLessonsByMonth(
      [
        { start_time: '2026-06-10T10:00:00' },
        { start_time: '2026-05-05T10:00:00' },
        { start_time: '2026-05-20T14:00:00' },
      ],
      comparePastLessonsForHistoryDisplay,
      { sortMonthsDescending: true },
    )

    expect(groups).toHaveLength(2)
    expect(groups[0].key).toBe('2026-06')
    expect(groups[1].key).toBe('2026-05')
    expect(groups[1].lessons[0].start_time).toBe('2026-05-20T14:00:00')
  })

  it('resolves all-history upcoming and past periods without quarter cutoff', () => {
    const ref = new Date(2026, 0, 2) // début T1 — past du trimestre serait null hors mode all
    const upcoming = resolveUpcomingLessonPeriod('all', ref)
    const past = resolvePastLessonPeriod('all', ref)

    expect(upcoming.from.getFullYear()).toBe(2026)
    expect(upcoming.from.getMonth()).toBe(0)
    expect(upcoming.from.getDate()).toBe(2)
    expect(upcoming.to.getFullYear()).toBe(2076)

    expect(past).not.toBeNull()
    expect(past!.from.getFullYear()).toBe(1970)
    expect(past!.to.getDate()).toBe(1)
  })

  it('resolves full display period for planning fiche', () => {
    const ref = new Date(2026, 7, 31) // T3
    const quarter = resolveFullLessonPeriod('upcoming_quarter', ref)
    const two = resolveFullLessonPeriod('with_previous_quarter', ref)
    const all = resolveFullLessonPeriod('all', ref)

    expect(quarter.from.getMonth()).toBe(6) // 1 juil.
    expect(quarter.to.getMonth()).toBe(8) // 30 sept.

    expect(two.from.getMonth()).toBe(3) // 1 avr. (T2)
    expect(two.to.getMonth()).toBe(8)

    expect(all.from.getFullYear()).toBe(1970)
    expect(all.to.getFullYear()).toBe(2076)
  })

  it('treats quarter_all like upcoming_quarter for full display window', () => {
    const ref = new Date(2026, 7, 31)
    const a = resolveFullLessonPeriod('upcoming_quarter', ref)
    const b = resolveFullLessonPeriod('quarter_all', ref)

    expect(b.from.getTime()).toBe(a.from.getTime())
    expect(b.to.getTime()).toBe(a.to.getTime())
  })
})
