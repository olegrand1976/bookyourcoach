/**
 * Fusion des données du planning club (cours, séries récurrentes) lors des rechargements.
 *
 * Contexte : après une correction, le planning rechargeait la plage initiale (±2 semaines)
 * et écrasait tout le reste ; un jour consulté hors de cette plage perdait alors tous ses
 * élèves. Une erreur réseau sur les séries vidait aussi tous les élèves des trous.
 * Ces helpers remplacent les données plage par plage, sans jamais vider ce qui est hors plage.
 */

type WithId = { id?: number | string | null }
type DatedLesson = WithId & { start_time?: string | null }

export type LoadedRange = { start: Date | null; end: Date | null }

function idKey(item: WithId): string | null {
  return item?.id == null ? null : String(item.id)
}

/**
 * Remplace les cours de la plage [start, end] par `incoming` et garde ceux hors plage.
 * Un cours déplacé (même id) n'est jamais dupliqué : la version reçue fait foi.
 */
export function replaceLessonsInRange<T extends DatedLesson>(
  existing: T[],
  incoming: T[],
  start: Date,
  end: Date,
): T[] {
  // L'API filtre par jour (date_from / date_to) : on remplace des journées entières.
  const dayStart = new Date(start)
  dayStart.setHours(0, 0, 0, 0)
  const dayEnd = new Date(end)
  dayEnd.setHours(23, 59, 59, 999)
  const s = dayStart.getTime()
  const e = dayEnd.getTime()
  const incomingIds = new Set(incoming.map(idKey).filter((k): k is string => k !== null))
  const kept = existing.filter((l) => {
    const k = idKey(l)
    if (k !== null && incomingIds.has(k)) return false
    if (!l?.start_time) return false
    const t = new Date(l.start_time).getTime()
    return t < s || t > e
  })
  return [...kept, ...incoming]
}

/** Ajoute ou met à jour par id (la version reçue fait foi), sans rien retirer. */
export function upsertById<T extends WithId>(existing: T[], incoming: T[]): T[] {
  const incomingIds = new Set(incoming.map(idKey).filter((k): k is string => k !== null))
  return [...existing.filter((x) => {
    const k = idKey(x)
    return k === null || !incomingIds.has(k)
  }), ...incoming]
}

/**
 * Plage à recharger après une modification : celle déjà chargée (elle contient le jour affiché),
 * étendue si besoin au jour sélectionné ; à défaut, la plage de repli.
 */
export function planningRefreshRange(
  loaded: LoadedRange,
  selected: Date | null,
  fallback: { start: Date; end: Date },
): { start: Date; end: Date } {
  let start = loaded.start ? new Date(loaded.start) : new Date(fallback.start)
  let end = loaded.end ? new Date(loaded.end) : new Date(fallback.end)
  if (selected) {
    const dayStart = new Date(selected)
    dayStart.setHours(0, 0, 0, 0)
    const dayEnd = new Date(selected)
    dayEnd.setHours(23, 59, 59, 999)
    if (dayStart.getTime() < start.getTime()) start = dayStart
    if (dayEnd.getTime() > end.getTime()) end = dayEnd
  }
  return { start, end }
}

/**
 * Garde « dernier rafraîchissement gagnant » : une réponse partie avant le dernier
 * rafraîchissement complet est ignorée (elle pourrait réinjecter un état périmé).
 */
export function createRefreshEpoch() {
  let epoch = 0
  return {
    current: () => epoch,
    bump: () => ++epoch,
    isCurrent: (captured: number) => captured === epoch,
  }
}
