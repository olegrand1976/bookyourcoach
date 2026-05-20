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

export function participantDisplayNameFromStudent(raw: {
  name?: string | null
  first_name?: string | null
  last_name?: string | null
  user?: { name?: string | null; first_name?: string | null; last_name?: string | null } | null
} | null): string {
  if (!raw) return 'Élève'
  if (raw.name?.trim()) return raw.name.trim()
  if (raw.user?.name?.trim()) return raw.user.name.trim()
  const fromUser = [raw.user?.first_name, raw.user?.last_name].filter(Boolean).join(' ').trim()
  if (fromUser) return fromUser
  const fromStudent = [raw.first_name, raw.last_name].filter(Boolean).join(' ').trim()
  if (fromStudent) return fromStudent
  return 'Élève'
}

export function participantDisplayNameFromTeacher(raw: {
  user?: { name?: string | null } | null
} | null): string {
  return raw?.user?.name?.trim() || 'Enseignant'
}
