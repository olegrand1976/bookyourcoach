import { describe, it, expect } from 'vitest'
import {
  resolveClosureToggleDecision,
  buildClosureConfirmationMessage,
  buildClosureAbortMessage,
  buildClosurePostPayload,
  parseClosureConflict,
  type ClosureImpact,
} from '~/composables/planning/useClosureDayGuard'

const impact = (overrides: Partial<ClosureImpact> = {}): ClosureImpact => ({
  date: '2026-09-23',
  already_closed: false,
  lessons_count: 22,
  subscription_links_count: 22,
  students_count: 21,
  teachers_count: 3,
  recipients_count: 24,
  ...overrides,
})

describe('resolveClosureToggleDecision', () => {
  it('abandonne quand l’impact n’a pas pu être obtenu', () => {
    // Le cas exact du 2026-09-23 : sans information fiable, on n'agit pas.
    expect(resolveClosureToggleDecision({ impact: null, impactFailed: true })).toBe('abort')
  })

  it('abandonne quand les cours du planning n’ont pas pu être chargés', () => {
    expect(
      resolveClosureToggleDecision({ impact: impact(), impactFailed: false, lessonsLoadFailed: true })
    ).toBe('abort')
  })

  it('demande confirmation dès qu’un cours est impacté', () => {
    expect(resolveClosureToggleDecision({ impact: impact(), impactFailed: false })).toBe('confirm')
    expect(
      resolveClosureToggleDecision({ impact: impact({ lessons_count: 1 }), impactFailed: false })
    ).toBe('confirm')
  })

  it('laisse passer une journée sans cours', () => {
    expect(
      resolveClosureToggleDecision({
        impact: impact({ lessons_count: 0, subscription_links_count: 0, recipients_count: 0 }),
        impactFailed: false,
      })
    ).toBe('proceed')
  })

  it('laisse passer une journée déjà fermée (appel idempotent)', () => {
    expect(
      resolveClosureToggleDecision({ impact: impact({ already_closed: true }), impactFailed: false })
    ).toBe('proceed')
  })
})

describe('buildClosureConfirmationMessage', () => {
  it('chiffre les cours, les carnets et les destinataires', () => {
    const message = buildClosureConfirmationMessage(impact())
    expect(message).toContain('22 cours sont prévus')
    expect(message).toContain('22 séances seront décomptées')
    expect(message).toContain('24 personnes seront prévenues')
  })

  it('accorde au singulier', () => {
    const message = buildClosureConfirmationMessage(
      impact({ lessons_count: 1, subscription_links_count: 1, recipients_count: 1 })
    )
    expect(message).toContain('1 cours est prévu')
    expect(message).toContain('1 séance sera décomptée')
    expect(message).toContain('1 personne sera prévenue')
  })

  it('tait les carnets et les e-mails quand il n’y en a pas', () => {
    const message = buildClosureConfirmationMessage(
      impact({ lessons_count: 2, subscription_links_count: 0, recipients_count: 0 })
    )
    expect(message).toContain('2 cours sont prévus')
    expect(message).not.toContain('décomptée')
    expect(message).not.toContain('e-mail')
  })
})

describe('buildClosureAbortMessage', () => {
  it('nomme la cause quand les cours n’ont pas pu être chargés', () => {
    const message = buildClosureAbortMessage({ impact: null, impactFailed: true, lessonsLoadFailed: true })
    expect(message).toContain('Rechargez le planning')
  })

  it('reste explicite sur l’absence de modification sinon', () => {
    const message = buildClosureAbortMessage({ impact: null, impactFailed: true })
    expect(message).toContain("Aucune modification n'a été faite")
  })
})

describe('buildClosurePostPayload', () => {
  it('annonce l’impact que le client croit provoquer', () => {
    expect(buildClosurePostPayload('2026-09-23', impact())).toEqual({
      date: '2026-09-23',
      closed: true,
      acknowledge_impact: true,
      expected_impacted_lessons: 22,
    })
  })
})

describe('parseClosureConflict', () => {
  it('extrait le code et l’impact d’un refus 409', () => {
    const parsed = parseClosureConflict({
      response: { status: 409, data: { data: { code: 'CLOSURE_IMPACT_MISMATCH', impact: impact() } } },
    })
    expect(parsed.code).toBe('CLOSURE_IMPACT_MISMATCH')
    expect(parsed.impact?.lessons_count).toBe(22)
  })

  it('ne suppose rien d’une réponse malformée', () => {
    expect(parseClosureConflict({})).toEqual({ code: null, impact: null })
    expect(parseClosureConflict({ response: { data: { data: { impact: { foo: 1 } } } } })).toEqual({
      code: null,
      impact: null,
    })
  })
})
