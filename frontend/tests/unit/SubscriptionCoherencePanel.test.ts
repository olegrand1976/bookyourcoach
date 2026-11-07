import { describe, expect, it, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import SubscriptionCoherencePanel from '~/components/club/SubscriptionCoherencePanel.vue'

const plan = {
  students: [{ id: 83, name: 'Liam Canoot' }],
  instances: [
    { id: 16, number: 'A', started_at: '2025-11-15', expires_at: '2026-03-15', capacity: 11, manual_before: 0, manual_after: 0, manual_reason: null, linked_before: 18, linked_after: 11 },
  ],
  moves: [{ lesson_id: 1, date: '2026-01-31', from: 16, to: 97 }],
  closure_detach: [{ lesson_id: 2, date: '2026-07-04', from: 169 }],
  uncovered: [{ lesson_id: 3, date: '2026-12-05' }],
  manual_changes: 1,
  has_changes: true,
}

describe('SubscriptionCoherencePanel', () => {
  const get = vi.fn()
  const post = vi.fn()

  beforeEach(() => {
    get.mockReset()
    post.mockReset()
    get.mockResolvedValue({ data: { success: true, data: { plan, history: [{ id: 5, created_at: '2026-11-07T12:00:00Z', summary: { moved: 3 }, reverted_at: null }] } } })
    post.mockResolvedValue({ data: { success: true, message: 'ok' } })
    ;(globalThis as any).useNuxtApp = () => ({ $api: { get, post } })
  })

  it('résume les écarts et applique la correction après confirmation', async () => {
    const w = mount(SubscriptionCoherencePanel, { props: { studentId: 83 }, global: { stubs: { NuxtLink: true } } })
    await flushPromises()

    expect(get).toHaveBeenCalledWith('/club/subscriptions/rebalance/students/83')
    expect(w.text()).toContain('1 cours à réaffecter')
    expect(w.text()).toContain('1 cours sans abonnement')

    await w.find('[data-testid="coherence-apply"]').trigger('click')
    expect(post).not.toHaveBeenCalled()
    await w.find('[data-testid="coherence-confirm"]').trigger('click')
    await flushPromises()

    expect(post).toHaveBeenCalledWith('/club/subscriptions/rebalance/students/83/apply')
    expect(w.emitted('changed')).toHaveLength(1)
  })

  it('annule la dernière correction', async () => {
    const w = mount(SubscriptionCoherencePanel, { props: { studentId: 83 }, global: { stubs: { NuxtLink: true } } })
    await flushPromises()
    await w.find('[data-testid="coherence-revert"]').trigger('click')
    await flushPromises()
    expect(post).toHaveBeenCalledWith('/club/subscriptions/rebalance/5/revert')
  })
})
