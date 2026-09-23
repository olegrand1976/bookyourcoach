/**
 * Annonces de nouveautés / corrections, affichées une seule fois.
 *
 * L'état « lu » est conservé dans le navigateur : une annonce fermée ne revient pas. Chaque
 * annonce porte un identifiant versionné, ce qui permet d'en publier une nouvelle sans
 * rouvrir les précédentes.
 *
 * Fonctions pures et sûres côté serveur (SSR) : toute lecture ou écriture de localStorage
 * peut échouer (navigation privée, stockage bloqué, quota) et ne doit jamais casser la page.
 */

const STORAGE_KEY = 'club-release-announcements-dismissed'

export type ReleaseAnnouncement = {
  /** Identifiant versionné : changer d'identifiant fait réapparaître une annonce. */
  id: string
  title: string
  intro?: string
  items: ReleaseAnnouncementItem[]
}

export type ReleaseAnnouncementItem = {
  /** `change` : comportement modifié à connaître. `fix` : correction. `new` : nouveauté. */
  kind: 'change' | 'fix' | 'new'
  title: string
  detail: string
}

function storage(): Storage | null {
  // `typeof localStorage === 'undefined'` suffit : en rendu serveur l'objet n'existe pas.
  // (Ne pas s'appuyer sur `import.meta.client`, absent hors build Nuxt — donc en test.)
  if (typeof localStorage === 'undefined') {
    return null
  }

  try {
    // Sonde : en navigation privée l'accès peut lever à la première écriture.
    localStorage.getItem(STORAGE_KEY)

    return localStorage
  } catch {
    return null
  }
}

/** Identifiants déjà fermés. Renvoie une liste vide si le stockage est indisponible. */
export function readDismissedAnnouncements(): string[] {
  const store = storage()
  if (!store) {
    return []
  }

  try {
    const raw = store.getItem(STORAGE_KEY)
    if (!raw) {
      return []
    }

    const parsed = JSON.parse(raw)

    return Array.isArray(parsed) ? parsed.filter((id): id is string => typeof id === 'string') : []
  } catch {
    return []
  }
}

export function isAnnouncementDismissed(id: string): boolean {
  return readDismissedAnnouncements().includes(id)
}

/** Marque l'annonce comme lue. Sans stockage disponible, l'annonce réapparaîtra — sans casser la page. */
export function dismissAnnouncement(id: string): void {
  const store = storage()
  if (!store) {
    return
  }

  try {
    const dismissed = readDismissedAnnouncements()
    if (dismissed.includes(id)) {
      return
    }

    store.setItem(STORAGE_KEY, JSON.stringify([...dismissed, id]))
  } catch {
    // Quota atteint ou stockage refusé : on n'empêche pas l'utilisateur de continuer.
  }
}

/**
 * Annonce en cours pour les clubs. `null` quand il n'y a rien à annoncer.
 *
 * Pour publier une nouvelle annonce : changer l'`id` et réécrire les `items`.
 */
export const CURRENT_CLUB_ANNOUNCEMENT: ReleaseAnnouncement | null = {
  id: '2026-09-planning-et-decompte',
  title: 'Planning et abonnements : ce qui change',
  intro: 'Quelques corrections viennent d’être mises en ligne. Deux d’entre elles modifient ce que vous voyez.',
  items: [
    {
      kind: 'change',
      title: 'Les places disponibles tiennent compte des réservations à venir',
      detail:
        'Un carnet de 10 séances avec 10 cours déjà réservés affiche désormais 0 place disponible, et non plus 10. Le nombre de séances réellement consommées, lui, ne change pas : il ne compte toujours que les cours qui ont eu lieu.',
    },
    {
      kind: 'change',
      title: 'Le planning distingue les anomalies',
      detail:
        'Les cartes blanches laissent place à une jauge de places par horaire et à un tiroir qui trie ce qu’il y a à traiter : trous de génération, annulations élève, annulations club et incohérences. Les actions (réactiver, ajouter un cours, retirer) restent à portée d’un clic.',
    },
    {
      kind: 'fix',
      title: 'Modifier une série récurrente fonctionne à nouveau',
      detail:
        'Changer l’horaire ou la fréquence d’une récurrence était refusé, le cours étant considéré en conflit avec sa propre série.',
    },
    {
      kind: 'fix',
      title: 'Un cours supprimé rend bien la séance',
      detail:
        'La séance restait décomptée de l’abonnement après suppression du cours, jusqu’au prochain recalcul.',
    },
    {
      kind: 'new',
      title: 'Date de fin estimée d’un abonnement',
      detail:
        'La fiche d’un abonnement indique quand le carnet devrait être épuisé, en tenant compte des congés du club et des annulations déjà connues.',
    },
  ],
}
