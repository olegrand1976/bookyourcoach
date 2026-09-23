import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { readFileSync } from 'fs'
import { resolve } from 'path'

const toast = { success: vi.fn(), error: vi.fn() }
vi.mock('~/composables/useToast', () => ({ useToast: () => toast }))

import TwoFactorSettings from '../../components/profile/TwoFactorSettings.vue'

const get = vi.fn()
const post = vi.fn()
const del = vi.fn()

const status = (overrides = {}) => ({
    data: {
        data: {
            enabled: true,
            confirmed_at: '2026-09-23T10:00:00+02:00',
            recovery_codes_remaining: 8,
            trusted_devices: [
                { id: 7, user_agent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7) Version/26.6 Mobile Safari/604.1', last_used_at: '2026-09-23T10:00:00+02:00', expires_at: '2026-10-23T10:00:00+02:00' },
            ],
            ...overrides,
        },
    },
})

const clickButton = async (wrapper: any, label: string) => {
    const button = wrapper.findAll('button').find((b: any) => b.text() === label)
    expect(button, `bouton « ${label} »`).toBeTruthy()
    await button.trigger('click')
}

describe('TwoFactorSettings', () => {
    beforeEach(() => {
        get.mockReset()
        post.mockReset()
        del.mockReset()
        toast.success.mockReset()
        toast.error.mockReset()
        vi.stubGlobal('useNuxtApp', () => ({ $api: { get, post, delete: del } }))
    })

    it('affiche le statut, les codes restants et les appareils de confiance', async () => {
        get.mockResolvedValue(status({ recovery_codes_remaining: 2 }))
        const wrapper = mount(TwoFactorSettings)
        await flushPromises()

        expect(get).toHaveBeenCalledWith('/auth/two-factor')
        expect(wrapper.text()).toContain('Activée')
        expect(wrapper.find('[data-testid="recovery-remaining"]').text()).toContain('Pensez à en générer de nouveaux')
        expect(wrapper.find('[data-testid="trusted-devices"]').text()).toContain('Safari · iOS')
    })

    it('régénère les codes avec mot de passe et code, puis les affiche', async () => {
        get.mockResolvedValue(status())
        post.mockResolvedValue({ data: { data: { recovery_codes: ['aaaaa-bbbbb'] } } })
        const wrapper = mount(TwoFactorSettings)
        await flushPromises()

        await clickButton(wrapper, 'Générer de nouveaux codes de récupération')
        await wrapper.find('#two-factor-password').setValue('mot-de-passe')
        await wrapper.find('#two-factor-current-code').setValue('123 456')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(post).toHaveBeenCalledWith('/auth/two-factor/recovery-codes', { password: 'mot-de-passe', code: '123456' })
        expect(wrapper.find('[data-testid="recovery-codes"]').text()).toContain('aaaaa-bbbbb')
    })

    it('change de téléphone en deux étapes', async () => {
        get.mockResolvedValue(status())
        post.mockImplementation((url: string) => url === '/auth/two-factor/reconfigure'
            ? Promise.resolve({ data: { data: { qr_svg: '<svg data-qr></svg>', secret: 'NOUVEAUSECRET' } } })
            : Promise.resolve({ data: { data: { recovery_codes: ['ccccc-ddddd'] } } }))
        const wrapper = mount(TwoFactorSettings)
        await flushPromises()

        await clickButton(wrapper, 'Changer de téléphone')
        await wrapper.find('#two-factor-password').setValue('mot-de-passe')
        await wrapper.find('#two-factor-current-code').setValue('123456')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(wrapper.html()).toContain('data-qr')
        await wrapper.find('#two-factor-new-code').setValue('654321')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(post).toHaveBeenLastCalledWith('/auth/two-factor/reconfigure/confirm', { code: '654321' })
        expect(wrapper.find('[data-testid="recovery-codes"]').text()).toContain('ccccc-ddddd')
    })

    it('affiche l\'erreur de validation renvoyée par l\'API', async () => {
        get.mockResolvedValue(status())
        post.mockRejectedValue({ response: { status: 422, data: { errors: { password: ['Mot de passe incorrect.'] } } } })
        const wrapper = mount(TwoFactorSettings)
        await flushPromises()

        await clickButton(wrapper, 'Changer de téléphone')
        await wrapper.find('#two-factor-password').setValue('faux')
        await wrapper.find('#two-factor-current-code').setValue('123456')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(wrapper.text()).toContain('Mot de passe incorrect.')
    })

    it('révoque un appareil de confiance', async () => {
        get.mockResolvedValue(status())
        del.mockResolvedValue({ data: { success: true } })
        const wrapper = mount(TwoFactorSettings)
        await flushPromises()

        await clickButton(wrapper, 'Révoquer')
        await flushPromises()

        expect(del).toHaveBeenCalledWith('/auth/two-factor/trusted-devices/7')
        expect(get).toHaveBeenCalledTimes(2)
    })
})

// Même piège que ChangePasswordForm : sans import explicite, Nuxt enregistre le
// composant sous « ProfileTwoFactorSettings » et la balise ne rend rien.
describe('Pages de compte club et admin', () => {
    it.each(['pages/club/profile.vue', 'pages/admin/profile.vue'])('%s importe explicitement TwoFactorSettings', (page) => {
        const source = readFileSync(resolve(__dirname, '../..', page), 'utf-8')
        expect(source).toContain('<TwoFactorSettings />')
        expect(source).toMatch(/import TwoFactorSettings from '~\/components\/profile\/TwoFactorSettings\.vue'/)
    })
})
