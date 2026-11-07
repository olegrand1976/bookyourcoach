/**
 * Lien vers la fiche d'un abonnement (instance) : la page Abonnements ouvre
 * directement son détail via `?instance=`.
 */
export function subscriptionInstanceLink(instanceId: number | string): { path: string; query: { instance: string } } {
  return { path: '/club/subscriptions', query: { instance: String(instanceId) } }
}
