import { computed, ref, type Ref } from 'vue'

/** Styles de case calendrier pour un jour de fermeture club (aligné PlanningMonthView). */
export function useClubClosureCalendar(closureDates: Ref<string[]>) {
  const closureSet = computed(() => new Set(closureDates.value))

  function isClubClosureDay(ymd: string): boolean {
    return closureSet.value.has(ymd)
  }

  function monthDayCellClass(ymd: string, isCurrentMonth: boolean, isToday: boolean): string {
    const parts: string[] = []
    if (!isCurrentMonth) {
      parts.push('bg-gray-50')
    } else if (isClubClosureDay(ymd)) {
      parts.push('bg-gray-100 border-gray-300')
    } else if (isToday) {
      parts.push('bg-blue-50 border-blue-300')
    } else if (isCurrentMonth) {
      parts.push('bg-white')
    }
    return parts.join(' ')
  }

  return {
    closureSet,
    isClubClosureDay,
    monthDayCellClass,
  }
}

export function monthRangeYmd(displayDate: Date): { date_from: string; date_to: string } {
  const year = displayDate.getFullYear()
  const month = displayDate.getMonth()
  const firstDay = new Date(year, month, 1)
  const startDate = new Date(firstDay)
  startDate.setDate(firstDay.getDate() - (firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1))
  const endDate = new Date(startDate)
  endDate.setDate(startDate.getDate() + 41)
  const fmt = (d: Date) => {
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
  }
  return { date_from: fmt(startDate), date_to: fmt(endDate) }
}

export async function fetchParticipantClosureDates(
  apiGet: (url: string, config?: { params?: Record<string, string | number> }) => Promise<{ data?: { success?: boolean; data?: { dates?: string[] } } }>,
  basePath: 'student' | 'teacher',
  range: { date_from: string; date_to: string },
  clubId?: number | null,
): Promise<string[]> {
  const params: Record<string, string | number> = { ...range }
  if (basePath === 'teacher' && clubId != null) {
    params.club_id = clubId
  }
  const response = await apiGet(`/${basePath}/closure-days`, { params })
  if (!response.data?.success) return []
  return response.data.data?.dates ?? []
}
