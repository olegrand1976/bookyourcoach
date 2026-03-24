/**
 * Aligné sur RecurringSlotValidator::subscriptionRecurringSlotFiresOnDate (Laravel / Carbon).
 * Utilisé par CreateLessonModal et le planning club pour cohérence affichage / dispo.
 */

export function parseYmd(ymd: string): Date {
  const [y, m, d] = ymd.split('-').map((x) => parseInt(x, 10))
  return new Date(y, m - 1, d)
}

export function ymdInRange(d: string, start: string, end: string): boolean {
  const ds = d.substring(0, 10)
  const a = (start || '').substring(0, 10)
  const b = (end || '').substring(0, 10)
  return a <= ds && ds <= b
}

export function subscriptionRecurringSlotFiresOnDate(slot: any, occurrenceDateStr: string): boolean {
  const interval = Math.max(1, Math.min(52, Number(slot.recurring_interval) || 1))
  const occurrence = parseYmd(occurrenceDateStr)
  const slotEnd = parseYmd(String(slot.end_date || '').substring(0, 10))
  const anchorBase = parseYmd(String(slot.start_date || '').substring(0, 10))

  if (occurrence.getDay() !== Number(slot.day_of_week)) return false
  if (occurrence < anchorBase || occurrence > slotEnd) return false

  const anchor = new Date(anchorBase)
  while (anchor.getDay() !== Number(slot.day_of_week)) {
    anchor.setDate(anchor.getDate() + 1)
  }
  if (occurrence < anchor) return false

  const daysBetween = Math.round((occurrence.getTime() - anchor.getTime()) / 86400000)
  if (daysBetween < 0 || daysBetween % 7 !== 0) return false
  const weekIndex = daysBetween / 7
  return weekIndex % interval === 0
}

function timeToMinutes(t: string): number {
  const part = String(t).substring(0, 5)
  const [h, m] = part.split(':').map((x) => parseInt(x, 10))
  return (h ?? 0) * 60 + (m ?? 0)
}

export function recurringSlotWindowMinutes(start: string, end: string): number {
  const sm = timeToMinutes(String(start).substring(0, 5))
  const em = timeToMinutes(String(end).substring(0, 5))
  if (em <= sm) return em + 24 * 60 - sm
  return em - sm
}

/** SubscriptionRecurringSlot::MAX_LESSON_LIKE_WINDOW_MINUTES */
export function isLessonLikeRecurringSlot(slot: { start_time: string; end_time: string }): boolean {
  const w = recurringSlotWindowMinutes(slot.start_time, slot.end_time)
  return w > 0 && w <= 120
}

export function localRangesOverlapOnDate(
  dateStr: string,
  proposedStartHHmm: string,
  durationMinutes: number,
  slotStartRaw: string,
  slotEndRaw: string
): boolean {
  const proposedStart = new Date(`${dateStr}T${proposedStartHHmm}:00`)
  const proposedEnd = new Date(proposedStart.getTime() + durationMinutes * 60000)
  const slotStart = new Date(`${dateStr}T${String(slotStartRaw).substring(0, 5)}:00`)
  let slotEnd = new Date(`${dateStr}T${String(slotEndRaw).substring(0, 5)}:00`)
  if (slotEnd <= slotStart) {
    slotEnd = new Date(slotEnd.getTime() + 86400000)
  }
  return proposedStart < slotEnd && proposedEnd > slotStart
}
