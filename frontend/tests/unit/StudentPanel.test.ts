import { describe, expect, it, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import StudentPanel from '~/components/club/StudentPanel.vue'

const stubs = {
  Teleport: true,
  EditStudentModal: { name: 'EditStudentModal', props: ['student', 'embedded'], template: '<div data-tab="infos">{{ student.name }}</div>' },
  StudentSubscriptionsModal: { name: 'StudentSubscriptionsModal', props: ['student', 'embedded'], template: '<div data-tab="abonnements" />' },
  StudentHistoryModal: { name: 'StudentHistoryModal', props: ['student', 'embedded'], template: '<div data-tab="historique" />' },
  PlanningParticipantInfoModal: { name: 'PlanningParticipantInfoModal', props: ['show', 'type', 'participantId', 'fallbackName', 'embedded'], template: '<div data-tab="planning" />' },
}

describe('StudentPanel (fiche élève unifiée)', () => {
  const get = vi.fn()

  beforeEach(() => {
    get.mockReset()
    get.mockResolvedValue({ data: { success: true, data: [{ id: 7, name: 'Léa Martin', email: 'lea@example.be', is_active: true }] } })
    ;(globalThis as any).useNuxtApp = () => ({ $api: { get } })
  })

  it('charge l’élève du club par son id et affiche son nom', async () => {
    const w = mount(StudentPanel, { props: { studentId: 7, tab: 'abonnements' }, global: { stubs } })
    await flushPromises()

    expect(get).toHaveBeenCalledWith('/club/students', { params: expect.objectContaining({ student_id: 7, status: 'all' }) })
    expect(w.text()).toContain('Léa Martin')
    expect(w.find('[data-tab="abonnements"]').exists()).toBe(true)
    // Onglets non visités : pas encore montés (pas d'appels API inutiles)
    expect(w.find('[data-tab="historique"]').exists()).toBe(false)
  })

  it('change d’onglet sans perdre les onglets déjà ouverts', async () => {
    const w = mount(StudentPanel, { props: { studentId: 7, tab: 'infos' }, global: { stubs } })
    await flushPromises()

    const planningTab = w.findAll('[role="tab"]').find((b) => b.text() === 'Planning')!
    await planningTab.trigger('click')

    expect(w.find('[data-tab="planning"]').exists()).toBe(true)
    expect(w.find('[data-tab="infos"]').exists()).toBe(true)
    expect(w.emitted('update:tab')?.at(-1)).toEqual(['planning'])
  })

  it('se ferme avec le bouton et ne rend rien sans élève', async () => {
    const w = mount(StudentPanel, { props: { studentId: 7 }, global: { stubs } })
    await flushPromises()
    await w.find('[aria-label="Fermer la fiche élève"]').trigger('click')
    expect(w.emitted('close')).toHaveLength(1)

    const empty = mount(StudentPanel, { props: { studentId: null }, global: { stubs } })
    expect(empty.find('[data-testid="student-panel"]').exists()).toBe(false)
  })
})
