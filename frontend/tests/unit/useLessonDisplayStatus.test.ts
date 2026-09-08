import { describe, it, expect } from 'vitest'
import {
  getLessonDisplayStatus,
  getLessonDisplayStatusPillClassFromStatus,
} from '~/composables/useLessonDisplayStatus'

describe('useLessonDisplayStatus', () => {
  it('affiche Fermeture club pour un cours confirmé un jour de congés', () => {
    const status = getLessonDisplayStatus({
      status: 'confirmed',
      is_on_closure_day: true,
    })

    expect(status.label).toBe('Fermeture club')
    expect(status.class).toContain('orange')
    expect(status.isClosureDay).toBe(true)
    expect(status.isCancelled).toBe(false)
  })

  it('priorise deleted_at sur is_on_closure_day', () => {
    const status = getLessonDisplayStatus({
      status: 'confirmed',
      deleted_at: '2026-03-01T10:00:00Z',
      is_on_closure_day: true,
    })

    expect(status.label).toBe('Supprimé')
    expect(status.isDeleted).toBe(true)
    expect(status.isClosureDay).toBe(false)
  })

  it('priorise cancelled sur is_on_closure_day', () => {
    const status = getLessonDisplayStatus({
      status: 'cancelled',
      is_on_closure_day: true,
      cancelled_by_role: 'club',
    })

    expect(status.label).toBe('Annulé (club)')
    expect(status.isCancelled).toBe(true)
    expect(status.isClosureDay).toBe(false)
  })

  it('variante certificat médical pour annulation élève', () => {
    const status = getLessonDisplayStatus({
      status: 'cancelled',
      cancelled_by_role: 'student',
      cancellation_reason: 'medical',
    })

    expect(status.label).toBe('Annulé (certificat médical)')
    expect(status.class).toContain('amber')
  })

  it('affiche Confirmé pour un statut standard', () => {
    const status = getLessonDisplayStatus({ status: 'confirmed' })

    expect(status.label).toBe('Confirmé')
    expect(status.class).toContain('green')
    expect(status.isClosureDay).toBe(false)
  })

  it('construit une classe pill depuis le statut déjà calculé', () => {
    const status = getLessonDisplayStatus({
      status: 'confirmed',
      is_on_closure_day: true,
    })
    const pill = getLessonDisplayStatusPillClassFromStatus(status)

    expect(pill).toContain('rounded-full')
    expect(pill).toContain('orange')
  })
})
