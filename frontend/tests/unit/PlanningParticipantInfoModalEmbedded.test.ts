import { describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import PlanningParticipantInfoModal from '~/components/planning/PlanningParticipantInfoModal.vue'

describe('PlanningParticipantInfoModal intégré à la fiche élève', () => {
  it('charge les cours dès le montage quand il est déjà visible', async () => {
    const get = vi.fn().mockResolvedValue({ data: { success: true, data: { student: { id: 7 }, subscriptions: [], lessons: [] } } })
    ;(globalThis as any).useNuxtApp = () => ({ $api: { get } })

    const w = mount(PlanningParticipantInfoModal, {
      props: { show: true, type: 'student', participantId: 7, embedded: true },
    })
    await flushPromises()

    expect(get).toHaveBeenCalledWith('/club/students/7/history')
    // Intégré : ni overlay ni en-tête de modale
    expect(w.find('.fixed.inset-0').exists()).toBe(false)
    expect(w.find('[role="tablist"]').exists()).toBe(false)
  })

  it('affiche si un cours annulé est déduit ou non de l\'abonnement', async () => {
    const base = { course_type: { name: 'Cours' }, teacher: { user: { name: 'Coach' } } }
    const lessons = [
      { ...base, id: 1, status: 'cancelled', start_time: '2026-10-01T10:00:00', end_time: '2026-10-01T11:00:00', cancellation_count_in_subscription: true },
      { ...base, id: 2, status: 'cancelled', start_time: '2026-10-02T10:00:00', end_time: '2026-10-02T11:00:00', cancellation_count_in_subscription: false },
      { ...base, id: 3, status: 'cancelled', start_time: '2026-10-03T10:00:00', end_time: '2026-10-03T11:00:00', cancellation_reason: 'medical', cancellation_certificate_status: 'pending' },
      { ...base, id: 4, status: 'confirmed', start_time: '2026-10-04T10:00:00', end_time: '2026-10-04T11:00:00' },
    ]
    const get = vi.fn().mockResolvedValue({ data: { success: true, data: { student: { id: 7 }, subscriptions: [], lessons } } })
    ;(globalThis as any).useNuxtApp = () => ({ $api: { get } })

    const w = mount(PlanningParticipantInfoModal, {
      props: { show: true, type: 'student', participantId: 7, embedded: true },
    })
    await flushPromises()

    const deducted = w.findAll('[data-test="cancel-impact-deducted"]')
    const notDeducted = w.findAll('[data-test="cancel-impact-not-deducted"]')
    const pending = w.findAll('[data-test="cancel-impact-pending"]')
    expect(deducted).toHaveLength(1)
    expect(deducted[0].text()).toContain('Déduit de l\'abonnement')
    expect(deducted[0].classes()).toContain('bg-red-600')
    expect(notDeducted).toHaveLength(1)
    expect(notDeducted[0].text()).toContain('Non déduit de l\'abonnement')
    expect(notDeducted[0].classes()).toContain('bg-emerald-600')
    expect(pending).toHaveLength(1)
  })
})
