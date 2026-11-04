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
})
