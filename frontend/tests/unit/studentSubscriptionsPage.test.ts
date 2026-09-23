import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref, computed, onMounted } from 'vue'
import SubscriptionsPage from '../../pages/student/subscriptions.vue'

const makeInstance = (id: number, status: string) => ({
    id,
    status,
    lessons_used: 0,
    started_at: '2026-01-01',
    expires_at: '2027-01-01',
    subscription: { subscription_number: `REF-${id}`, template: { total_lessons: 10, free_lessons: 0 } },
    students: []
})

const apiGet = vi.fn()

describe('Page élève « Mes Abonnements »', () => {
    beforeEach(() => {
        // La page s'appuie sur les auto-imports Nuxt : on remplace les mocks non réactifs du setup
        Object.assign(globalThis, { ref, computed, onMounted })
        global.useRoute = vi.fn(() => ({ path: '/student/subscriptions' }))
        global.useNuxtApp = vi.fn(() => ({ $api: { get: apiGet, post: vi.fn() } }))
        global.useStudentScopeStore = vi.fn(() => ({ apiScopeParam: 'all', loadLinkedAccounts: vi.fn() }))
        apiGet.mockReset()
    })

    const mountPage = async () => {
        const wrapper = mount(SubscriptionsPage, {
            global: { stubs: { NuxtPage: true, StudentViewSwitcher: true, StripeSubscribeModal: true } }
        })
        await flushPromises()
        return wrapper
    }

    it('n\'affiche que les abonnements actifs', async () => {
        apiGet.mockResolvedValue({
            data: {
                success: true,
                data: [
                    makeInstance(1, 'active'),
                    makeInstance(2, 'expired'),
                    makeInstance(3, 'completed'),
                    makeInstance(4, 'cancelled')
                ]
            }
        })

        const wrapper = await mountPage()
        const text = wrapper.text()

        expect(text).toContain('REF-1')
        expect(text).not.toContain('REF-2')
        expect(text).not.toContain('REF-3')
        expect(text).not.toContain('REF-4')
        expect(text).not.toContain('Expiré')
    })

    it('affiche l\'état vide quand aucun abonnement n\'est actif', async () => {
        apiGet.mockResolvedValue({
            data: { success: true, data: [makeInstance(2, 'expired'), makeInstance(3, 'completed')] }
        })

        const wrapper = await mountPage()

        expect(wrapper.text()).toContain('Aucun abonnement actif')
        expect(wrapper.text()).not.toContain('REF-2')
    })

    it('masque un abonnement « active » dont l\'échéance est dépassée', async () => {
        apiGet.mockResolvedValue({
            data: {
                success: true,
                data: [
                    { ...makeInstance(1, 'active'), expires_at: '2020-01-01T00:00:00.000000Z' },
                    { ...makeInstance(5, 'active'), expires_at: null }
                ]
            }
        })

        const wrapper = await mountPage()

        expect(wrapper.text()).not.toContain('REF-1')
        expect(wrapper.text()).toContain('REF-5')
    })

    it('tolère une réponse sans données', async () => {
        apiGet.mockResolvedValue({ data: { success: true, data: null } })

        const wrapper = await mountPage()

        expect(wrapper.text()).toContain('Aucun abonnement actif')
    })
})
