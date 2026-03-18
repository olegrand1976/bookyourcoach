/**
 * Libellés unifiés pour les annulations et certificats médicaux.
 * Utilisé par : student/dashboard, StudentHistoryModal, StudentCalendar.
 */

export const CERTIFICATE_STATUS_LABELS: Record<string, string> = {
  pending: 'Certificat en attente',
  accepted: 'Certificat accepté',
  rejected: 'Certificat refusé',
  closed: 'Demande clôturée',
}

export const IMPACT_DEDUCTED = 'Déduit de l\'abonnement'
export const IMPACT_NOT_DEDUCTED = 'Non déduit de l\'abonnement'
export const IMPACT_PENDING_VALIDATION = 'En attente de validation du certificat'

export function getCertificateStatusLabel(status: string | null | undefined): string {
  if (!status) return ''
  return CERTIFICATE_STATUS_LABELS[status] ?? status
}

export function getCertificateStatusClass(status: string | null | undefined): string {
  const s = status
  if (s === 'pending') return 'bg-amber-100 text-amber-800'
  if (s === 'accepted') return 'bg-emerald-100 text-emerald-800'
  if (s === 'rejected') return 'bg-red-100 text-red-800'
  if (s === 'closed') return 'bg-gray-100 text-gray-700'
  return 'bg-gray-100 text-gray-800'
}

/**
 * Impact abonnement : Déduit / Non déduit / En attente de validation.
 * Quand medical + certificat pas encore accepté → "En attente de validation du certificat" uniquement.
 */
export function getCancellationSubscriptionImpact(lesson: {
  cancellation_reason?: string
  cancellation_certificate_status?: string
  cancellation_count_in_subscription?: boolean
} | null): string {
  if (!lesson) return ''
  if (lesson.cancellation_reason === 'medical' && lesson.cancellation_certificate_status !== 'accepted') {
    return IMPACT_PENDING_VALIDATION
  }
  return lesson.cancellation_count_in_subscription ? IMPACT_DEDUCTED : IMPACT_NOT_DEDUCTED
}

export function getCancellationSubscriptionImpactClass(lesson: {
  cancellation_reason?: string
  cancellation_certificate_status?: string
  cancellation_count_in_subscription?: boolean
} | null): string {
  if (!lesson) return 'bg-gray-100 text-gray-800'
  if (lesson.cancellation_reason === 'medical' && lesson.cancellation_certificate_status !== 'accepted') {
    return 'bg-red-600 text-white'
  }
  return lesson.cancellation_count_in_subscription ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'
}

/**
 * Afficher le badge "statut certificat" seulement si raison médicale et statut définitif (accepté/refusé/clôturé).
 * Quand pending, on n'affiche que le badge impact "En attente de validation du certificat" pour éviter la redondance.
 */
export function shouldShowCertificateStatusBadge(lesson: {
  cancellation_reason?: string
  cancellation_certificate_status?: string
} | null): boolean {
  if (!lesson || lesson.cancellation_reason !== 'medical') return false
  const s = lesson.cancellation_certificate_status
  return s === 'accepted' || s === 'rejected' || s === 'closed'
}
