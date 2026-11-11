import { describe, expect, it, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import SubscriptionCoherencePanel from '~/components/club/SubscriptionCoherencePanel.vue'

const instance = (id: number, start: string, linkedAfter: number) => ({
  id, number: `N-${id}`, status: 'active', started_at: start, expires_at: null, capacity: 2,
  manual_before: 0, manual_after: 0, manual_reason: null, had_reset: false, linked_before: 0, linked_after: linkedAfter,
})
const lesson = (id: number, date: string, current: number | null, proposed: number | null) => ({
  lesson_id: id, date, time: '10:00', status: 'confirmed', past: true, current, proposed, eligible: [16, 97],
})
const plan = () => ({
  students: [{ id: 83, name: 'Liam Canoot' }],
  instances: [instance(16, '2025-11-15', 2), instance(97, '2026-03-13', 1)],
  lessons: [lesson(1, '2026-01-10', 16, 16), lesson(2, '2026-01-17', 16, 16), lesson(3, '2026-01-24', 16, 97)],
  moves: [{ lesson_id: 3, date: '2026-01-24', from: 16, to: 97 }],
  closure_detach: [{ lesson_id: 9, date: '2026-07-04', from: 97 }],
  uncovered: [],
  manual_changes: 0,
  has_changes: true,
})

describe('SubscriptionCoherencePanel — fiche de cohérence', () => {
  const get = vi.fn()
  const post = vi.fn()

  beforeEach(() => {
    get.mockReset()
    post.mockReset()
    get.mockResolvedValue({ data: { success: true, data: { plan: plan(), history: [{ id: 5, created_at: '2026-11-07T12:00:00Z', summary: { moved: 3 }, reverted_at: null }] } } })
    post.mockResolvedValue({ data: { success: true, message: 'ok' } })
    ;(globalThis as any).useNuxtApp = () => ({ $api: { get, post } })
  })

  const mountPanel = async () => {
    const w = mount(SubscriptionCoherencePanel, { props: { studentId: 83 }, global: { stubs: { NuxtLink: { template: '<a><slot /></a>' } } } })
    await flushPromises()
    await w.find('[data-testid="coherence-open-sheet"]').trigger('click')
    return w
  }

  it('affiche l’état et applique la proposition telle quelle (corps vide)', async () => {
    const w = await mountPanel()
    expect(get).toHaveBeenCalledWith('/club/subscriptions/rebalance/students/83')
    expect(w.text()).toContain('1 cours à réaffecter')
    expect(w.find('[data-testid="coherence-instance-16"]').text()).toContain('Après correction : 2 / 2')

    await w.find('[data-testid="coherence-apply"]').trigger('click')
    expect(post).not.toHaveBeenCalled()
    await w.find('[data-testid="coherence-confirm"]').trigger('click')
    await flushPromises()

    expect(post).toHaveBeenCalledWith('/club/subscriptions/rebalance/students/83/apply', {})
    expect(w.emitted('changed')).toHaveLength(1)
  })

  it('envoie les ajustements de la fiche et recalcule les totaux en direct', async () => {
    const w = await mountPanel()

    await w.find('[data-testid="coherence-select-3"]').setValue('16') // garder sur le 1er carnet
    expect(w.find('[data-testid="coherence-instance-16"]').text()).toContain('Après correction : 3 / 2')
    await w.find('[data-testid="coherence-manual-97"]').setValue('1')
    expect(w.find('[data-testid="coherence-apply"]').text()).toContain('mes modifications (2)')

    await w.find('[data-testid="coherence-apply"]').trigger('click')
    await w.find('[data-testid="coherence-confirm"]').trigger('click')
    await flushPromises()

    expect(post).toHaveBeenCalledWith('/club/subscriptions/rebalance/students/83/apply', {
      assignments: [{ lesson_id: 3, instance_id: 16 }],
      manual: [{ instance_id: 97, value: 1 }],
    })
  })

  it('explique la proposition en clair et ne montre d’abord que les cours qui changent', async () => {
    const w = await mountPanel()

    expect(w.text()).toContain('Comment fonctionne cette vérification ?')
    const summary = w.find('[data-testid="coherence-summary"]').text()
    expect(summary).toContain('1 cours à réaffecter')
    expect(summary).toContain('jour de fermeture')
    expect(w.find('[data-testid="coherence-lesson-1"]').exists()).toBe(false)
    expect(w.find('[data-testid="coherence-lesson-3"]').exists()).toBe(true)

    // Garder le cours 3 sur le 1er carnet : il reste affiché pendant la saisie, et le carnet déborde.
    await w.find('[data-testid="coherence-select-3"]').setValue('16')
    expect(w.find('[data-testid="coherence-lesson-3"]').exists()).toBe(true)
    expect(w.find('[data-testid="coherence-instance-16"]').text()).toContain('dépassé de 1')
    expect(w.text()).toContain('3 cours réaffectés')
  })

  it('revient à la proposition et annule la dernière correction', async () => {
    const w = await mountPanel()
    await w.find('[data-testid="coherence-select-3"]').setValue('16')
    await w.find('[data-testid="coherence-reset"]').trigger('click')
    expect(w.find('[data-testid="coherence-apply"]').text()).toContain('Appliquer la proposition')

    await w.find('[data-testid="coherence-revert"]').trigger('click')
    await flushPromises()
    expect(post).toHaveBeenCalledWith('/club/subscriptions/rebalance/5/revert')
  })
})
