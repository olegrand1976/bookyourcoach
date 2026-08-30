/**
 * Résolution élève / enseignant depuis une carte cours du planning club.
 */

export function resolveLessonPrimaryStudentId(lesson: {
  student_id?: number | null
  student?: { id?: number } | null
  students?: Array<{ id?: number }> | null
} | null): number | null {
  if (!lesson) return null
  if (lesson.student_id) return Number(lesson.student_id)
  if (lesson.student?.id) return Number(lesson.student.id)
  if (lesson.students?.length === 1 && lesson.students[0]?.id) {
    return Number(lesson.students[0].id)
  }
  if (lesson.students?.[0]?.id) return Number(lesson.students[0].id)
  return null
}

export function resolveLessonTeacherId(lesson: {
  teacher_id?: number | null
  teacher?: { id?: number } | null
} | null): number | null {
  if (!lesson) return null
  if (lesson.teacher_id) return Number(lesson.teacher_id)
  if (lesson.teacher?.id) return Number(lesson.teacher.id)
  return null
}

export function formatParticipantPhone(
  phone: string | null | undefined,
  userPhone?: string | null | undefined,
): string | null {
  const p = (phone ?? userPhone ?? '').toString().trim()
  return p !== '' ? p : null
}

export type PlanningStudentLike = {
  id?: number
  name?: string | null
  first_name?: string | null
  last_name?: string | null
  user?: { name?: string | null; first_name?: string | null; last_name?: string | null } | null
}

/** Nom affichable, ou null si aucune donnée (évite le sentinel « Élève »). */
export function resolveStudentDisplayName(raw: PlanningStudentLike | null): string | null {
  if (!raw) return null
  if (raw.name?.trim()) return raw.name.trim()
  if (raw.user?.name?.trim()) return raw.user.name.trim()
  const fromUser = [raw.user?.first_name, raw.user?.last_name].filter(Boolean).join(' ').trim()
  if (fromUser) return fromUser
  const fromStudent = [raw.first_name, raw.last_name].filter(Boolean).join(' ').trim()
  if (fromStudent) return fromStudent
  return null
}

export function participantDisplayNameFromStudent(raw: PlanningStudentLike | null): string {
  return resolveStudentDisplayName(raw) ?? 'Élève'
}

function isStudentIdFallbackLabel(label: string): boolean {
  return /^Élève #\d+$/.test(label)
}

/**
 * Libellé élève(s) pour une carte cours planning (BelongsTo + M2M).
 * Priorité : name / user.name / first+last — dernier recours Élève #id.
 * Un libellé réel remplace un fallback #id pour le même id.
 */
export function formatLessonStudentsLabel(
  lesson: {
    student_id?: number | null
    student?: PlanningStudentLike | null
    students?: PlanningStudentLike[] | null
  } | null,
  options?: { emptyLabel?: string },
): string {
  const emptyLabel = options?.emptyLabel ?? 'Aucun élève'
  if (!lesson) return emptyLabel

  /** id → label (ordre d’insertion conservé) */
  const byId = new Map<number, string>()
  const withoutId: string[] = []

  const pushStudent = (raw: PlanningStudentLike | null | undefined) => {
    if (!raw) return
    const id = raw.id != null ? Number(raw.id) : null
    const resolved = resolveStudentDisplayName(raw)
    const label = resolved ?? (id != null ? `Élève #${id}` : null)
    if (!label) return

    if (id == null) {
      if (!withoutId.includes(label)) withoutId.push(label)
      return
    }

    const existing = byId.get(id)
    if (!existing) {
      byId.set(id, label)
      return
    }
    if (isStudentIdFallbackLabel(existing) && !isStudentIdFallbackLabel(label)) {
      byId.set(id, label)
    }
  }

  pushStudent(lesson.student ?? undefined)
  if (Array.isArray(lesson.students)) {
    for (const s of lesson.students) pushStudent(s)
  }

  const names = [...byId.values(), ...withoutId]
  if (names.length > 0) return names.join(', ')

  const sid = resolveLessonPrimaryStudentId(lesson)
  if (sid != null) return `Élève #${sid}`
  return emptyLabel
}

export function participantDisplayNameFromTeacher(raw: {
  user?: { name?: string | null } | null
} | null): string {
  return raw?.user?.name?.trim() || 'Enseignant'
}
