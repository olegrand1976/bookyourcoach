import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import TwoFactorStep from '../../components/auth/TwoFactorStep.vue'
import { useAuthStore } from '../../stores/auth'

const post = vi.fn()

const startStep = (mode: 'challenge' | 'setup') => {
    const store = useAuthStore()
    store.twoFactor = { challengeToken: 'c'.repeat(64), mode, remember: false, pendingLogin: null }
    return store
}

describe('TwoFactorStep', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        post.mockReset()
        vi.stubGlobal('useNuxtApp', () => ({ $api: { post } }))
    })

    it('vérifie le code et ouvre la session sans conserver le challenge', async () => {
        const store = startStep('challenge')
        post.mockResolvedValue({ data: { data: { access_token: '1|jeton', user: { role: 'club' } } } })
        const wrapper = mount(TwoFactorStep)

        await wrapper.find('#two-factor-code').setValue('123 456')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(post).toHaveBeenCalledWith('/auth/two-factor/challenge', {
            challenge_token: 'c'.repeat(64),
            code: '123456',
            remember_device: false,
        })
        expect(store.isAuthenticated).toBe(true)
        expect(store.token).toBe('1|jeton')
        expect(store.twoFactor.challengeToken).toBeNull()
        expect(wrapper.emitted('done')).toHaveLength(1)
    })

    it('affiche les essais restants sur un code faux, sans ouvrir de session', async () => {
        const store = startStep('challenge')
        post.mockRejectedValue({
            response: { status: 422, data: { message: 'Code invalide.', data: { remaining_attempts: 3 } } },
        })
        const wrapper = mount(TwoFactorStep)

        await wrapper.find('#two-factor-code').setValue('000000')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(wrapper.text()).toContain('Code invalide. (3 essais restants)')
        expect(store.isAuthenticated).toBe(false)
        expect(wrapper.emitted('done')).toBeUndefined()
    })

    it('renvoie au mot de passe quand le challenge a expiré', async () => {
        const store = startStep('challenge')
        post.mockRejectedValue({ response: { status: 401, data: { message: 'Session de vérification expirée.' } } })
        const wrapper = mount(TwoFactorStep)

        await wrapper.find('#two-factor-code').setValue('123456')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(wrapper.emitted('cancel')?.[0]).toEqual(['Session de vérification expirée.'])
        expect(store.twoFactor.mode).toBeNull()
    })

    it('permet de basculer sur un code de récupération', async () => {
        startStep('challenge')
        post.mockResolvedValue({ data: { data: { access_token: '1|jeton', user: { role: 'admin' } } } })
        vi.stubGlobal('alert', vi.fn())
        const wrapper = mount(TwoFactorStep)

        await wrapper.find('button[type="button"]').trigger('click')
        await wrapper.find('#two-factor-recovery').setValue('abcde-fghij')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(post).toHaveBeenCalledWith('/auth/two-factor/challenge', expect.objectContaining({
            recovery_code: 'abcde-fghij',
        }))
    })

    it('enrôlement : QR code, premier code, puis session ouverte seulement après confirmation des codes', async () => {
        const store = startStep('setup')
        post.mockImplementation((url: string) => {
            if (url === '/auth/two-factor/setup') {
                return Promise.resolve({ data: { data: { qr_svg: '<svg data-qr></svg>', secret: 'ABCDEFGHIJKLMNOP' } } })
            }
            return Promise.resolve({
                data: { data: { access_token: '1|jeton', user: { role: 'club' }, recovery_codes: ['aaaaa-bbbbb', 'ccccc-ddddd'] } },
            })
        })
        const wrapper = mount(TwoFactorStep)
        await flushPromises()

        expect(wrapper.find('[data-testid="two-factor-qr"]').html()).toContain('data-qr')
        expect(wrapper.text()).toContain('ABCD EFGH IJKL MNOP')

        await wrapper.find('#two-factor-code').setValue('123456')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(wrapper.find('[data-testid="recovery-codes"]').text()).toContain('aaaaa-bbbbb')
        // Les codes d'abord : pas de session tant qu'ils ne sont pas sauvegardés.
        expect(store.isAuthenticated).toBe(false)

        const continuer = wrapper.findAll('button').find(b => b.text() === 'Continuer')!
        expect(continuer.attributes('disabled')).toBeDefined()
        await wrapper.find('input[type="checkbox"]').setValue(true)
        await continuer.trigger('click')

        expect(store.isAuthenticated).toBe(true)
        expect(wrapper.emitted('done')).toHaveLength(1)
    })
})
