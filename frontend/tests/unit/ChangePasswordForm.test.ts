import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { readFileSync } from 'fs'
import { resolve } from 'path'

const toast = { success: vi.fn(), error: vi.fn() }
vi.mock('~/composables/useToast', () => ({ useToast: () => toast }))

import ChangePasswordForm from '../../components/profile/ChangePasswordForm.vue'

const put = vi.fn()

const fillAndSubmit = async (wrapper: any) => {
    await wrapper.find('#current_password').setValue('ancien-mdp')
    await wrapper.find('#new_password').setValue('nouveau-mdp-123')
    await wrapper.find('#password_confirmation').setValue('nouveau-mdp-123')
    await wrapper.find('form').trigger('submit')
    await flushPromises()
}

describe('ChangePasswordForm', () => {
    beforeEach(() => {
        put.mockReset()
        toast.success.mockReset()
        toast.error.mockReset()
        vi.stubGlobal('useNuxtApp', () => ({ $api: { put } }))
    })

    it('affiche les champs mot de passe actuel, nouveau et confirmation', () => {
        const wrapper = mount(ChangePasswordForm)
        expect(wrapper.find('#current_password').attributes('type')).toBe('password')
        expect(wrapper.find('#new_password').attributes('type')).toBe('password')
        expect(wrapper.find('#password_confirmation').attributes('type')).toBe('password')
    })

    it('envoie les trois valeurs à /auth/change-password puis vide le formulaire', async () => {
        put.mockResolvedValue({ data: { success: true } })
        const wrapper = mount(ChangePasswordForm)

        await fillAndSubmit(wrapper)

        expect(put).toHaveBeenCalledWith('/auth/change-password', {
            current_password: 'ancien-mdp',
            password: 'nouveau-mdp-123',
            password_confirmation: 'nouveau-mdp-123',
        })
        expect(toast.success).toHaveBeenCalled()
        expect((wrapper.find('#current_password').element as HTMLInputElement).value).toBe('')
        expect((wrapper.find('#new_password').element as HTMLInputElement).value).toBe('')
    })

    it('affiche les erreurs de validation renvoyées par l\'API sous le champ concerné', async () => {
        put.mockRejectedValue({
            response: {
                data: {
                    message: 'Données invalides',
                    errors: { current_password: ['Le mot de passe actuel est incorrect.'] },
                },
            },
        })
        const wrapper = mount(ChangePasswordForm)

        await fillAndSubmit(wrapper)

        expect(wrapper.text()).toContain('Le mot de passe actuel est incorrect.')
        expect(toast.error).toHaveBeenCalledWith('Données invalides')
    })
})

// Le composant vit dans components/profile/ : sans import explicite, Nuxt l'enregistre
// sous « ProfileChangePasswordForm » et la balise <ChangePasswordForm /> ne rend rien.
describe('Pages de profil', () => {
    const pages = ['pages/profile.vue', 'pages/club/profile.vue', 'pages/teacher/profile.vue', 'pages/student/profile.vue']

    it.each(pages)('%s importe explicitement ChangePasswordForm', (page) => {
        const source = readFileSync(resolve(__dirname, '../..', page), 'utf-8')
        expect(source).toContain('<ChangePasswordForm />')
        expect(source).toMatch(/import ChangePasswordForm from '~\/components\/profile\/ChangePasswordForm\.vue'/)
    })
})
