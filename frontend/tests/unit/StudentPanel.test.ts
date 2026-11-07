import { describe, expect, it, vi, beforeEach } from 'vitest'
import { mount, flushPromises, enableAutoUnmount } from '@vue/test-utils'
import { afterEach } from 'vitest'
import StudentPanel from '~/components/club/StudentPanel.vue'

const stubs = {
  Teleport: true,
  EditStudentModal: { name: 'EditStudentModal', props: ['student', 'embedded'], template: '<div data-tab="infos">{{ student.name }}</div>' },
  StudentSubscriptionsModal: { name: 'StudentSubscriptionsModal', props: ['student', 'embedded'], template: '<div data-tab="abonnements" />' },
  StudentHistoryModal: { name: 'StudentHistoryModal', props: ['student', 'embedded'], template: '<div data-tab="historique" />' },
  PlanningParticipantInfoModal: { name: 'PlanningParticipantInfoModal', props: ['show', 'type', 'participantId', 'fallbackName', 'embedded'], template: '<div data-tab="planning" />' },
}

enableAutoUnmount(afterEach)

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

  it('bloque le scroll de la page tant que la fiche est ouverte', async () => {
    const w = mount(StudentPanel, { props: { studentId: 7 }, global: { stubs } })
    await flushPromises()
    expect(document.documentElement.style.overflow).toBe('hidden')
    await w.setProps({ studentId: null })
    expect(document.documentElement.style.overflow).toBe('')
    w.unmount()
  })

  it('vue scindée : deux volets indépendants, échange si on choisit l’onglet de l’autre volet', async () => {
    window.localStorage.removeItem('club.studentPanel.split')
    window.matchMedia = vi.fn().mockReturnValue({ matches: true, addEventListener: vi.fn(), removeEventListener: vi.fn() }) as any
    const w = mount(StudentPanel, { props: { studentId: 7, tab: 'abonnements' }, global: { stubs } })
    await flushPromises()

    expect(w.find('[data-testid="student-panel-pane-1"]').exists()).toBe(false)
    await w.find('[data-testid="student-panel-split-toggle"]').trigger('click')

    const pane0 = () => w.find('[data-testid="student-panel-pane-0"]')
    const pane1 = () => w.find('[data-testid="student-panel-pane-1"]')
    expect(pane0().find('[data-tab="abonnements"]').exists()).toBe(true)
    expect(pane1().find('[data-tab="historique"]').exists()).toBe(true)

    // Choisir « Historique » à gauche : les volets s'échangent
    await pane0().findAll('[role="tab"]').find((b) => b.text().startsWith('Historique'))!.trigger('click')
    expect(pane0().find('[role="tab"][aria-selected="true"]').text()).toContain('Historique')
    expect(pane1().find('[role="tab"][aria-selected="true"]').text()).toContain('Abonnements')
    expect(w.emitted('update:tab')?.at(-1)).toEqual(['historique'])
    expect(window.localStorage.getItem('club.studentPanel.split')).toBe('1')
    window.localStorage.removeItem('club.studentPanel.split')
  })
})
