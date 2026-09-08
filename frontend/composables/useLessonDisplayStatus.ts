/**
 * Tag différencié d'un cours (historique élève, fiche planning).
 * Priorité : deleted_at → cancelled → is_on_closure_day → statut standard.
 */

export interface LessonDisplayStatusInput {
  status?: string | null
  deleted_at?: string | null
  is_on_closure_day?: boolean | null
  cancelled_by_role?: string | null
  cancellation_reason?: string | null
}

export interface LessonDisplayStatus {
  label: string
  class: string
  isClosureDay: boolean
  isDeleted: boolean
  isCancelled: boolean
}

export function getLessonDisplayStatus(lesson: LessonDisplayStatusInput | null | undefined): LessonDisplayStatus {
  if (lesson?.deleted_at) {
    return {
      label: 'Supprimé',
      class: 'bg-gray-200 text-gray-600 line-through',
      isClosureDay: false,
      isDeleted: true,
      isCancelled: false,
    }
  }

  if (lesson?.status === 'cancelled') {
    const role = lesson?.cancelled_by_role
    let label = 'Annulé'
    let cls = 'bg-red-100 text-red-800'
    if (role === 'club') {
      label = 'Annulé (club)'
    } else if (role === 'teacher') {
      label = 'Annulé (coach)'
    } else if (role === 'student') {
      if (lesson?.cancellation_reason === 'medical') {
        label = 'Annulé (certificat médical)'
        cls = 'bg-amber-100 text-amber-800'
      } else {
        label = 'Annulé (élève)'
      }
    }
    return {
      label,
      class: cls,
      isClosureDay: false,
      isDeleted: false,
      isCancelled: true,
    }
  }

  if (lesson?.is_on_closure_day) {
    return {
      label: 'Fermeture club',
      class: 'bg-orange-100 text-orange-800',
      isClosureDay: true,
      isDeleted: false,
      isCancelled: false,
    }
  }

  const labels: Record<string, string> = {
    confirmed: 'Confirmé',
    pending: 'En attente',
    completed: 'Terminé',
  }
  const classes: Record<string, string> = {
    confirmed: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-gray-100 text-gray-700',
  }
  const status = lesson?.status ?? ''
  const cls = classes[status] ?? 'bg-blue-100 text-blue-800'

  return {
    label: labels[status] ?? (status || '—'),
    class: cls,
    isClosureDay: false,
    isDeleted: false,
    isCancelled: false,
  }
}

/** Classes badge plein (colonne statut desktop historique). */
export function getLessonDisplayStatusPillClassFromStatus(status: LessonDisplayStatus): string {
  return `px-2 py-0.5 text-xs font-medium rounded-full ${status.class}`
}
