import { describe, expect, it } from 'vitest'
import {
  createRefreshEpoch,
  planningRefreshRange,
  replaceLessonsInRange,
  upsertById,
} from '~/composables/planning/usePlanningDataMerge'

const d = (s: string) => new Date(s)

describe('replaceLessonsInRange', () => {
  const existing = [
    { id: 1, start_time: '2026-11-02T10:00:00', student: 'A' },
    { id: 2, start_time: '2026-12-15T10:00:00', student: 'B' }, // hors plage rechargée
    { id: 3, start_time: '2026-11-03T10:00:00', student: 'C' }, // supprimé côté serveur
  ]

  it('garde les cours hors plage (le jour consulté ne perd plus ses élèves)', () => {
    const out = replaceLessonsInRange(existing, [{ id: 1, start_time: '2026-11-02T10:00:00', student: 'A2' }], d('2026-11-01T00:00:00'), d('2026-11-30T23:59:59'))
    expect(out.map((l) => l.id).sort()).toEqual([1, 2])
    expect(out.find((l) => l.id === 1)?.student).toBe('A2')
  })

  it('ne duplique pas un cours déplacé hors de la plage', () => {
    const out = replaceLessonsInRange(existing, [{ id: 2, start_time: '2026-11-20T10:00:00', student: 'B' }], d('2026-11-01T00:00:00'), d('2026-11-30T23:59:59'))
    expect(out.filter((l) => l.id === 2)).toHaveLength(1)
    expect(out.find((l) => l.id === 2)?.start_time).toBe('2026-11-20T10:00:00')
  })
})

describe('upsertById', () => {
  it('met à jour sans rien retirer', () => {
    const out = upsertById([{ id: 1, v: 'a' }, { id: 2, v: 'b' }], [{ id: 2, v: 'B' }, { id: 3, v: 'c' }])
    expect(out).toEqual([{ id: 1, v: 'a' }, { id: 2, v: 'B' }, { id: 3, v: 'c' }])
  })
})

describe('planningRefreshRange', () => {
  const fallback = { start: d('2026-10-21T00:00:00'), end: d('2026-11-18T23:59:59') }

  it('reprend la plage chargée plutôt que la plage initiale', () => {
    const loaded = { start: d('2026-10-21T00:00:00'), end: d('2027-01-15T23:59:59') }
    const r = planningRefreshRange(loaded, d('2027-01-10T00:00:00'), fallback)
    expect(r.end.getTime()).toBe(loaded.end.getTime())
  })

  it('étend la plage au jour sélectionné si besoin', () => {
    const r = planningRefreshRange({ start: null, end: null }, d('2027-02-01T12:00:00'), fallback)
    expect(r.start.getTime()).toBe(fallback.start.getTime())
    expect(r.end.getTime()).toBeGreaterThanOrEqual(d('2027-02-01T23:00:00').getTime())
  })
})

describe('createRefreshEpoch', () => {
  it('invalide les réponses parties avant le dernier rafraîchissement', () => {
    const e = createRefreshEpoch()
    const old = e.current()
    e.bump()
    expect(e.isCurrent(old)).toBe(false)
    expect(e.isCurrent(e.current())).toBe(true)
  })
})

describe('replaceLessonsInRange — journées entières', () => {
  it('retire un cours supprimé le matin même si la plage commence plus tard dans la journée', () => {
    const existing = [{ id: 9, start_time: '2026-11-02T08:00:00' }]
    const out = replaceLessonsInRange(existing, [], new Date('2026-11-02T14:00:00'), new Date('2026-11-05T10:00:00'))
    expect(out).toEqual([])
  })
})
