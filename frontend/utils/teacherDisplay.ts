/**
 * Libellés enseignants (listes déroulantes remplacements, etc.).
 * Les specialties JSON peuvent être [] : en JS [] est truthy, d'où l'ancien affichage "- []".
 */
export function formatSpecialties(raw: unknown): string {
  if (raw == null || raw === '') return ''
  if (Array.isArray(raw)) {
    const parts = raw.map((x) => String(x).trim()).filter(Boolean)
    return parts.length ? parts.join(', ') : ''
  }
  if (typeof raw === 'string') return raw.trim()
  return String(raw)
}

export function formatTeacherOptionLabel(teacher: Record<string, any> | null | undefined): string {
  const name = teacher?.user?.name || teacher?.name || 'Enseignant'
  const spec = formatSpecialties(teacher?.specialties)
  return spec ? `${name} — ${spec}` : name
}
