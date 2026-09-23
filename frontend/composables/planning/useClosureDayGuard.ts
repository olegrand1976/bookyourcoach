/**
 * Décision, message et charge utile du basculement « Congés » du planning club.
 *
 * Extrait de planning.vue pour être testable : le 2026-09-23, une journée a été
 * fermée sans confirmation parce que le garde-fou lisait la liste locale des cours,
 * vide à ce moment-là faute d'avoir pu être chargée. La règle est désormais que
 * l'impact vient du serveur, et qu'une information manquante interdit d'agir.
 */

export type ClosureImpact = {
  date: string
  already_closed: boolean
  lessons_count: number
  subscription_links_count: number
  students_count: number
  teachers_count: number
  recipients_count: number
}

export type ClosureToggleDecision = 'abort' | 'confirm'

/** Toute demande de congés est validée par mot de passe ou code 2FA. */
export type ClosureConfirmation =
  | { method: 'password'; password: string }
  | { method: 'totp'; code: string }

export type ClosureToggleContext = {
  /** Impact renvoyé par GET /club/closure-days/impact, null si l'appel a échoué. */
  impact: ClosureImpact | null
  /** L'appel d'impact a échoué (réseau, 4xx, 5xx). */
  impactFailed: boolean
  /** Les cours du planning n'ont pas pu être chargés. */
  lessonsLoadFailed?: boolean
}

/**
 * Sans impact fiable, on n'agit pas : c'est le cœur du correctif. Un réseau dégradé
 * doit rendre l'action impossible, pas silencieuse. Sinon, on confirme toujours —
 * même une journée sans cours : la demande passe par mot de passe ou 2FA.
 */
export function resolveClosureToggleDecision(context: ClosureToggleContext): ClosureToggleDecision {
  if (context.impactFailed || !context.impact) return 'abort'
  if (context.lessonsLoadFailed) return 'abort'
  return 'confirm'
}

const plural = (count: number, singular: string, plural_: string) =>
  `${count} ${count > 1 ? plural_ : singular}`

/** Message de confirmation chiffré : le club doit voir ce qu'il déclenche. */
export function buildClosureConfirmationMessage(impact: ClosureImpact): string {
  if (impact.already_closed) {
    return 'Ce jour est déjà marqué comme congés.'
  }

  const parts = [
    `Marquer ce jour comme congés ?`,
    impact.lessons_count > 0
      ? `${plural(impact.lessons_count, 'cours est prévu', 'cours sont prévus')} ce jour-là.`
      : `Aucun cours n'est prévu ce jour-là.`,
  ]

  if (impact.subscription_links_count > 0) {
    parts.push(
      `${plural(impact.subscription_links_count, 'séance sera décomptée', 'séances seront décomptées')} du carnet des élèves et le ou les crédits leur seront restitués.`
    )
  }

  if (impact.recipients_count > 0) {
    parts.push(
      `${plural(impact.recipients_count, 'personne sera prévenue', 'personnes seront prévenues')} par e-mail.`
    )
  }

  return parts.join('\n')
}

/** Message d'abandon, selon ce qui manque. */
export function buildClosureAbortMessage(context: ClosureToggleContext): string {
  if (context.lessonsLoadFailed) {
    return "Les cours de cette période n'ont pas pu être chargés : impossible de savoir ce qu'une fermeture entraînerait. Rechargez le planning avant de marquer un jour en congés."
  }

  return "Impossible de vérifier ce que cette fermeture entraînerait. Aucune modification n'a été faite — réessayez."
}

/** Réouverture : pas d'aperçu serveur, mais le club doit savoir qu'il prévient du monde. */
export function buildReopenConfirmationMessage(): string {
  return 'Annuler le congé de ce jour ?\nLes moniteurs et élèves concernés seront prévenus par e-mail, et les séances décomptées seront rattachées à nouveau à leur carnet.'
}

function confirmationFields(confirmation: ClosureConfirmation) {
  return confirmation.method === 'password'
    ? { confirmation_method: 'password' as const, password: confirmation.password }
    : { confirmation_method: 'totp' as const, code: confirmation.code.replace(/\s+/g, '') }
}

export function buildClosurePostPayload(ymd: string, impact: ClosureImpact, confirmation: ClosureConfirmation) {
  return {
    date: ymd,
    closed: true,
    acknowledge_impact: true,
    expected_impacted_lessons: impact.lessons_count,
    ...confirmationFields(confirmation),
  }
}

export function buildReopenPostPayload(ymd: string, confirmation: ClosureConfirmation) {
  return {
    date: ymd,
    closed: false,
    ...confirmationFields(confirmation),
  }
}

/**
 * Lit un refus 409 du serveur. Tolérant à une réponse malformée : en cas de doute,
 * on ne réessaie pas.
 */
export function parseClosureConflict(error: any): { code: string | null; impact: ClosureImpact | null } {
  const data = error?.response?.data?.data
  const code = typeof data?.code === 'string' ? data.code : null
  const impact = data?.impact && typeof data.impact.lessons_count === 'number' ? (data.impact as ClosureImpact) : null

  return { code, impact }
}
