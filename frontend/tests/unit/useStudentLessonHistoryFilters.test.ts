import { describe, it, expect } from 'vitest'
import {
  getCalendarQuarterBounds,
  getPreviousCalendarQuarterBounds,
  resolveLessonHistoryPeriod,
  lessonMatchesHistoryFilters,
  groupLessonsByMonth,
} from '~/composables/useStudentLessonHistoryFilters'

describe('useStudentLessonHistoryFilters', () => {
  it('resolves current quarter from today until end of quarter', () => {
    const ref = new Date(2026, 4, 19, 12, 0, 0) // 19 mai 2026 → T2
    const period = resolveLessonHistoryPeriod('current_quarter', ref)

    expect(period.from.getFullYear()).toBe(2026)
    expect(period.from.getMonth()).toBe(4)
    expect(period.from.getDate()).toBe(19)
    expect(period.to.getMonth()).toBe(5) // fin juin
    expect(period.to.getDate()).toBe(30)
  })

  it('includes previous quarter when mode is with_previous_quarter', () => {
    const ref = new Date(2026, 4, 19)
    const period = resolveLessonHistoryPeriod('with_previous_quarter', ref)
    const prev = getPreviousCalendarQuarterBounds(ref)

    expect(period.from.getTime()).toBe(prev.start.getTime())
    expect(period.to.getTime()).toBe(getCalendarQuarterBounds(ref).end.getTime())
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

    expect(
      lessonMatchesHistoryFilters(
        { start_time: '2025-12-01T10:00:00', status: 'cancelled' },
        'all',
        from,
        to,
      ),
    ).toBe(false)
  })

  it('groups lessons by month in chronological order', () => {
    const groups = groupLessonsByMonth([
      { start_time: '2026-06-10T10:00:00' },
      { start_time: '2026-05-05T10:00:00' },
      { start_time: '2026-05-20T14:00:00' },
    ])

    expect(groups).toHaveLength(2)
    expect(groups[0].key).toBe('2026-05')
    expect(groups[0].lessons).toHaveLength(2)
    expect(groups[1].key).toBe('2026-06')
  })
})
