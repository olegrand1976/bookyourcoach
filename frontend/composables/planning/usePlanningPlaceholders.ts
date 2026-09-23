/**
 * Logique pure des « cartes blanches » du planning club : occurrences d'une série récurrente
 * (SubscriptionRecurringSlot) attendues un jour donné sans cours matérialisé.
 *
 * Extraite de pages/club/planning.vue à comportement strictement identique, pour être testable.
 * Aucune réactivité ici : toutes les dépendances sont passées en paramètres.
 *
 * Règle métier partagée avec le backend : `subscriptionRecurringSlotFiresOnDate` (utils),
 * alignée sur RecurringSlotValidator::subscriptionRecurringSlotFiresOnDate.
 */

import {
  isLessonLikeRecurringSlot,
  subscriptionRecurringSlotFiresOnDate,
  ymdInRange,
} from '~/utils/subscriptionRecurringSlot'

/** Sous-ensemble d'un cours utile ici (cours API ou placeholder). */
export type PlanningLessonLike = {
  id?: number
  start_time: string
  end_time?: string | null
  status?: string | null
  deleted_at?: string | null
  student_id?: number | null
  teacher_id?: number | null
  students?: Array<{ id: number }> | null
  course_type?: { name?: string | null } | null
  courseType?: { name?: string | null } | null
  cancelled_by_role?: string | null
  cancelled_at?: string | null
  cancelled_by_user?: { name?: string | null } | null
  cancellation_count_in_subscription?: boolean | null
  is_recurring_placeholder?: boolean
  is_freed_occurrence?: boolean
  freed_by_lesson_id?: number | null
  recurring_slot_id?: number
  occurrence_date?: string
}

/** Sous-ensemble d'une série récurrente (PlanningRecurringSlotResource). */
export type RecurringSlotLike = {
  id: number
  status?: string | null
  student_id?: number | null
  teacher_id?: number | null
  day_of_week?: number | null
  start_time: string
  end_time: string
  start_date?: string | null
  end_date?: string | null
  recurring_interval?: number | null
  skipped_dates?: string[] | null
  student?: unknown
  teacher?: unknown
  subscription_instance?: { subscription?: { template?: { name?: string | null; price?: unknown } | null } | null } | null
}

/** Fenêtre horaire d'un créneau ouvert (ClubOpenSlot). */
export type OpenSlotWindowLike = {
  day_of_week: number
  start_time: string
  end_time: string
}

/** HH:MM:SS → HH:MM (même contrat que formatTime() de planning.vue). */
export function formatTimeHm(time: string): string {
  if (!time) return ''
  return String(time).substring(0, 5)
}

function localYmd(d: Date): string {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function lessonStudentId(lesson: PlanningLessonLike): number {
  return Number(lesson.student_id ?? lesson.students?.[0]?.id)
}

/** Bornes [début, fin) d'une série ce jour-là ; une fin ≤ début passe au lendemain. */
function slotBoundsOnDate(rs: { start_time: string; end_time: string }, dateStr: string): { start: Date; end: Date } {
  const start = new Date(`${dateStr}T${String(rs.start_time).substring(0, 5)}:00`)
  let end = new Date(`${dateStr}T${String(rs.end_time).substring(0, 5)}:00`)
  if (end <= start) end = new Date(end.getTime() + 86400000)
  return { start, end }
}

/**
 * Cours inactif sur la grille : annulé ou soft-supprimé (n'occupe pas une voie).
 */
export function isInactivePlanningLesson(lesson: PlanningLessonLike | null | undefined): boolean {
  if (!lesson || lesson.is_recurring_placeholder) return false
  if (lesson.status === 'cancelled') return true
  return lesson.deleted_at != null && String(lesson.deleted_at) !== ''
}

/**
 * Le cours « matérialise » l'occurrence du créneau récurrent ce jour-là (donc pas de carte placeholder).
 * Même élève + chevauchement horaire : inclut le remplacement ponctuel d'enseignant sur la séance.
 */
export function lessonMaterializesRecurringOnDate(
  lesson: PlanningLessonLike,
  slot: RecurringSlotLike,
  dateStr: string,
): boolean {
  if (lesson.is_recurring_placeholder) return false
  if (isInactivePlanningLesson(lesson)) return false
  const ls = new Date(lesson.start_time)
  if (localYmd(ls) !== dateStr) return false
  if (lessonStudentId(lesson) !== Number(slot.student_id)) return false
  const le = new Date(lesson.end_time ?? lesson.start_time)
  const { start: rs, end: re } = slotBoundsOnDate(slot, dateStr)
  return ls < re && le > rs
}

/**
 * Cours annulé ou soft-supprimé qui « libère » l'occurrence de la série ce jour
 * (même élève + chevauchement horaire), ou null. La série reste active pour les semaines suivantes.
 */
export function findInactiveLessonFreeingOccurrence(
  rs: RecurringSlotLike,
  dateStr: string,
  dayLessons: readonly PlanningLessonLike[],
): PlanningLessonLike | null {
  const sid = Number(rs.student_id)
  if (!sid) return null
  const { start: rsStart, end: rsEnd } = slotBoundsOnDate(rs, dateStr)

  for (const lesson of dayLessons) {
    if (lesson.is_recurring_placeholder) continue
    if (!isInactivePlanningLesson(lesson)) continue
    // Élève + chevauchement horaire (le coach série peut avoir changé après soft-delete)
    if (sid !== lessonStudentId(lesson)) continue
    const ls = new Date(lesson.start_time)
    const le = new Date(lesson.end_time ?? lesson.start_time)
    if (ls < rsEnd && le > rsStart) return lesson
  }
  return null
}

/** Variante booléenne (sémantique historique de planning.vue). */
export function recurringOccurrenceFreedByInactiveLesson(
  rs: RecurringSlotLike,
  dateStr: string,
  dayLessons: readonly PlanningLessonLike[],
): boolean {
  return findInactiveLessonFreeingOccurrence(rs, dateStr, dayLessons) !== null
}

/**
 * Pseudo-cours représentant l'occurrence attendue d'une série ce jour.
 * `id` négatif dérivé de la série (pas de l'occurrence) : ne pas l'utiliser comme clé Vue,
 * préférer `placeholderKey()`.
 */
export function buildRecurringPlaceholder(
  rs: RecurringSlotLike,
  dateStr: string,
  freedBy: PlanningLessonLike | null = null,
): PlanningLessonLike & Record<string, unknown> {
  const sh = String(rs.start_time).substring(0, 5)
  const eh = String(rs.end_time).substring(0, 5)
  const tmpl = rs.subscription_instance?.subscription?.template ?? null
  const templateLabel = tmpl?.name ?? null
  const priceNum = tmpl != null && typeof tmpl.price === 'number' ? tmpl.price : undefined
  return {
    id: -(10_000_000 + Number(rs.id)),
    club_id: 0,
    student_id: rs.student_id ?? null,
    teacher_id: rs.teacher_id ?? null,
    course_type_id: 0,
    location_id: 0,
    start_time: `${dateStr}T${sh}:00`,
    end_time: `${dateStr}T${eh}:00`,
    status: 'recurring_series',
    price: priceNum ?? 0,
    is_recurring_placeholder: true,
    is_freed_occurrence: freedBy !== null,
    freed_by_lesson_id: freedBy?.id ?? null,
    recurring_slot_id: Number(rs.id),
    occurrence_date: dateStr,
    student: rs.student,
    teacher: rs.teacher,
    course_type: {
      id: 0,
      name: templateLabel ? `Abonnement · ${templateLabel}` : 'Réservation récurrente (abonnement)',
      description: null,
      discipline_id: null,
      is_individual: true,
      max_participants: 1,
      is_active: true,
    },
  }
}

/** Clé stable par occurrence (deux occurrences d'une même série ne collisionnent pas). */
export function placeholderKey(p: { recurring_slot_id?: number; occurrence_date?: string }): string {
  return `${p.recurring_slot_id ?? 'x'}-${p.occurrence_date ?? ''}`
}

export type ComputeRecurringPlaceholdersInput = {
  /** Séries du club (statut, plage de dates, jour, horaire, intervalle, skipped_dates). */
  recurringSlots: readonly RecurringSlotLike[]
  /** Jour affiché, YYYY-MM-DD local. */
  dateStr: string
  /** Créneau ouvert sélectionné (jour + fenêtre horaire). */
  slot: OpenSlotWindowLike
  /** Cours réels déjà filtrés pour ce créneau / ce jour (matérialisation). */
  dayFilteredLessons: readonly PlanningLessonLike[]
  /** Tous les cours du jour (détection des occurrences libérées par annulation). */
  dayLessons: readonly PlanningLessonLike[]
  /** HH:MM:SS → HH:MM ; défaut identique à formatTime() de planning.vue. */
  formatTime?: (t: string) => string
  /**
   * false (défaut, comportement historique) : une occurrence libérée par un cours annulé /
   * soft-supprimé n'est pas émise. true : elle est émise avec `is_freed_occurrence = true`.
   */
  includeFreed?: boolean
}

/**
 * Toutes les séries actives qui tombent ce jour dans la plage horaire du créneau ouvert
 * et n'ont pas de cours qui les matérialise. Les 8 conditions sont celles de planning.vue.
 */
export function computeRecurringPlaceholders(input: ComputeRecurringPlaceholdersInput): PlanningLessonLike[] {
  const fmt = input.formatTime ?? formatTimeHm
  const { dateStr, slot } = input
  const slotStart = fmt(slot.start_time)
  const slotEnd = fmt(slot.end_time)

  // Index élève → cours du créneau (lookup O(1) vs find nested)
  const byStudentId = new Map<number, PlanningLessonLike[]>()
  for (const l of input.dayFilteredLessons) {
    const sid = lessonStudentId(l)
    if (!sid) continue
    const bucket = byStudentId.get(sid)
    if (bucket) bucket.push(l)
    else byStudentId.set(sid, [l])
  }

  const out: PlanningLessonLike[] = []
  for (const rs of input.recurringSlots) {
    if (rs.status !== 'active') continue
    if (!ymdInRange(dateStr, String(rs.start_date ?? ''), String(rs.end_date ?? ''))) continue
    if (Number(rs.day_of_week) !== slot.day_of_week) continue
    if (!isLessonLikeRecurringSlot(rs)) continue
    if (!subscriptionRecurringSlotFiresOnDate(rs, dateStr)) continue
    const rsStart = fmt(rs.start_time)
    if (!(rsStart >= slotStart && rsStart < slotEnd)) continue
    const candidates = byStudentId.get(Number(rs.student_id)) ?? []
    if (candidates.some((l) => lessonMaterializesRecurringOnDate(l, rs, dateStr))) continue
    // Cours annulé / soft-supprimé ce jour : plage libérée, série inchangée pour les occurrences futures
    const freedBy = findInactiveLessonFreeingOccurrence(rs, dateStr, input.dayLessons)
    if (freedBy && !input.includeFreed) continue
    out.push(buildRecurringPlaceholder(rs, dateStr, freedBy))
  }
  return out
}

/** Occupe une « voie » : cours actif ou placeholder récurrent non libéré ; annulés / soft-supprimés ne comptent pas. */
export function countOccupiedParallelLanes(lessons: readonly PlanningLessonLike[]): number {
  let n = 0
  for (const l of lessons) {
    if (l.is_recurring_placeholder) {
      if (!l.is_freed_occurrence) n += 1
    } else if (!isInactivePlanningLesson(l)) {
      n += 1
    }
  }
  return n
}

export type LaneKind = 'lesson' | 'series' | 'freed' | 'free'

export type Lane = { kind: LaneKind; label: string }

export type TimeSlotAvailability = {
  maxSlots: number
  occupied: number
  free: number
  hasRecurringPlaceholder: boolean
  hasCancelledPlaceholder: boolean
  /** Une entrée par voie, longueur max(maxSlots, occupied) : le surbooking reste visible. */
  lanes: Lane[]
}

function lessonLaneLabel(l: PlanningLessonLike): string {
  return l.course_type?.name || l.courseType?.name || 'Cours'
}

/**
 * Disponibilité d'une plage horaire. `gridLessons` peut contenir des placeholders (comportement
 * historique) ; `extraPlaceholders` permet de les passer à part une fois sortis de la grille.
 */
export function buildTimeSlotAvailability(
  gridLessons: readonly PlanningLessonLike[],
  maxSlots: number,
  extraPlaceholders: readonly PlanningLessonLike[] = [],
): TimeSlotAvailability {
  const all = [...gridLessons, ...extraPlaceholders]
  const occupied = countOccupiedParallelLanes(all)
  const free = Math.max(0, maxSlots - occupied)
  const hasRecurringPlaceholder = all.some((l) => l.is_recurring_placeholder)
  const hasCancelledPlaceholder = all.some((l) => l.is_recurring_placeholder && l.is_freed_occurrence)

  const lanes: Lane[] = []
  for (const l of all) {
    if (l.is_recurring_placeholder || isInactivePlanningLesson(l)) continue
    lanes.push({ kind: 'lesson', label: lessonLaneLabel(l) })
  }
  for (const l of all) {
    if (l.is_recurring_placeholder && !l.is_freed_occurrence) lanes.push({ kind: 'series', label: 'Série attendue' })
  }
  // Les places libérées par annulation sont des voies libres, signalées comme récupérables.
  const freedCount = all.filter((l) => l.is_recurring_placeholder && l.is_freed_occurrence).length
  const freedShown = Math.min(freedCount, free)
  for (let i = 0; i < freedShown; i++) lanes.push({ kind: 'freed', label: 'Place libérée' })
  for (let i = freedShown; i < free; i++) lanes.push({ kind: 'free', label: 'Libre' })

  return { maxSlots, occupied, free, hasRecurringPlaceholder, hasCancelledPlaceholder, lanes }
}

// ---------------------------------------------------------------------------
// Classification des anomalies (B2)
// ---------------------------------------------------------------------------

export type PlanningAnomalyKind =
  | 'generation_gap'
  | 'student_cancellation'
  | 'club_cancellation'
  | 'inconsistency'

export type PlanningAnomaly = {
  kind: PlanningAnomalyKind
  /** Motif, auteur, code de diagnostic… */
  detail?: string
  /** Annulation tardive comptée dans l'abonnement (élève). */
  countsInSubscription?: boolean
  recurringSlotId: number
  occurrenceDate: string
  sourceLessonId?: number
}

/** Codes de SubscriptionRecurringSlotDiagnosticsService relevant d'une incohérence structurelle. */
export const INCONSISTENCY_DIAGNOSTIC_CODES: readonly string[] = [
  'schedule_drift',
  'teacher_mismatch',
  'orphan_lessons',
  'cancelled_srs_with_futures',
  'inverted_date_range',
  'no_future_lessons',
]

export const ANOMALY_LABELS: Record<PlanningAnomalyKind, string> = {
  generation_gap: 'Trou de génération',
  student_cancellation: 'Annulation élève',
  club_cancellation: 'Annulation club',
  inconsistency: 'Incohérence',
}

/** Libellés au pluriel (le pluriel français ne s'obtient pas en suffixant le groupe nominal). */
export const ANOMALY_LABELS_PLURAL: Record<PlanningAnomalyKind, string> = {
  generation_gap: 'Trous de génération',
  student_cancellation: 'Annulations élève',
  club_cancellation: 'Annulations club',
  inconsistency: 'Incohérences',
}

/** « 1 trou de génération », « 2 trous de génération ». */
export function anomalyCountLabel(kind: PlanningAnomalyKind, count: number): string {
  const label = count > 1 ? ANOMALY_LABELS_PLURAL[kind] : ANOMALY_LABELS[kind]
  return `${count} ${label.toLowerCase()}`
}

/** Ordre d'affichage stable des familles (du plus actionnable au plus informatif). */
export const ANOMALY_KINDS_ORDER: readonly PlanningAnomalyKind[] = [
  'generation_gap',
  'student_cancellation',
  'club_cancellation',
  'inconsistency',
]

export type AnomalySummaryEntry = { kind: PlanningAnomalyKind; count: number }

/** Compte les anomalies par famille, dans l'ordre d'affichage, sans les familles absentes. */
export function summarizeAnomalies(anomalies: readonly PlanningAnomaly[]): AnomalySummaryEntry[] {
  const counts = new Map<PlanningAnomalyKind, number>()
  for (const a of anomalies) counts.set(a.kind, (counts.get(a.kind) ?? 0) + 1)
  return ANOMALY_KINDS_ORDER
    .filter((k) => (counts.get(k) ?? 0) > 0)
    .map((k) => ({ kind: k, count: counts.get(k)! }))
}

/** Le tiroir s'ouvre de lui-même quand le club doit agir : trou de génération ou place récupérable. */
export function anomaliesRequireAttention(anomalies: readonly PlanningAnomaly[]): boolean {
  return anomalies.some((a) => a.kind === 'generation_gap' || a.kind === 'student_cancellation')
}

/**
 * Classe une occurrence sans cours. Une seule famille principale (fermeture club > annulation
 * élève > annulation club > trou), à laquelle se cumulent les incohérences structurelles.
 * `gap_in_series` (diagnostics) recouvre le trou déjà déduit : il n'est pas doublé.
 */
export function classifyOccurrence(
  placeholder: { recurring_slot_id?: number; occurrence_date?: string; freed_by_lesson_id?: number | null },
  freedByLesson: PlanningLessonLike | null,
  isClosureDay: boolean,
  diagnosticIssueCodes: readonly string[] = [],
): PlanningAnomaly[] {
  const base = {
    recurringSlotId: Number(placeholder.recurring_slot_id ?? 0),
    occurrenceDate: String(placeholder.occurrence_date ?? ''),
  }
  const out: PlanningAnomaly[] = []

  if (isClosureDay) {
    out.push({ ...base, kind: 'club_cancellation', detail: 'Jour de fermeture du club' })
  } else if (freedByLesson) {
    const role = freedByLesson.cancelled_by_role ?? null
    const who = freedByLesson.cancelled_by_user?.name ?? null
    if (role === 'student') {
      out.push({
        ...base,
        kind: 'student_cancellation',
        detail: who ? `Annulé par ${who}` : "Annulé par l'élève",
        countsInSubscription: freedByLesson.cancellation_count_in_subscription === true,
        sourceLessonId: freedByLesson.id,
      })
    } else {
      const label = role === 'teacher' ? "Annulé par l'enseignant" : freedByLesson.status === 'cancelled' ? 'Annulé par le club' : 'Supprimé par le club'
      out.push({
        ...base,
        kind: 'club_cancellation',
        detail: who ? `${label} (${who})` : label,
        sourceLessonId: freedByLesson.id,
      })
    }
  } else {
    out.push({ ...base, kind: 'generation_gap', detail: 'Occurrence attendue, aucun cours' })
  }

  for (const code of diagnosticIssueCodes) {
    if (!INCONSISTENCY_DIAGNOSTIC_CODES.includes(code)) continue
    out.push({ ...base, kind: 'inconsistency', detail: code })
  }

  return out
}
