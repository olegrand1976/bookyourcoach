import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises, enableAutoUnmount } from '@vue/test-utils'
import EditStudentModal from '~/components/EditStudentModal.vue'

/**
 * Fiche élève (onglet Infos) : modification de l'email.
 * Le même email qu'un autre élève rattache l'élève à ce compte ; l'API renvoie le
 * message à afficher, y compris en cas de refus (422).
 */
enableAutoUnmount(afterEach)

const student = {
  id: 12,
  user_id: 40,
  first_name: 'Léa',
  last_name: 'Martin',
  email: 'lea@example.be',
  phone: '0470000000',
  date_of_birth: '',
  goals: '',
  medical_info: '',
}

describe('EditStudentModal — email de l’élève', () => {
  const get = vi.fn()
  const put = vi.fn()
  const post = vi.fn()
  const success = vi.fn()
  const error = vi.fn()

  beforeEach(() => {
    vi.clearAllMocks()
    get.mockResolvedValue({ data: { success: true, data: [] } })
    ;(globalThis as any).useNuxtApp = () => ({ $api: { get, put, post, delete: vi.fn() } })
    ;(globalThis as any).useToast = () => ({ success, error, info: vi.fn(), warning: vi.fn() })
  })

  const mountModal = () =>
    mount(EditStudentModal, {
      props: { student, embedded: true },
      global: { stubs: { StudentInviteCodeCard: true } },
    })

  const submitWithEmail = async (w: ReturnType<typeof mountModal>, email: string) => {
    await flushPromises()
    await w.find('input[type="email"]').setValue(email)
    await w.find('form').trigger('submit')
    await flushPromises()
  }

  it('envoie le nouvel email et affiche le message de rattachement renvoyé par l’API', async () => {
    const message = 'Élève mis à jour avec succès. Il est rattaché au compte de parent@example.be : les élèves de ce compte sont accessibles depuis la même connexion.'
    put.mockResolvedValue({ data: { success: true, attached_to_account: true, message, data: student } })
    const w = mountModal()

    await submitWithEmail(w, '  parent@example.be ')

    expect(put).toHaveBeenCalledWith('/club/students/12', expect.objectContaining({ email: 'parent@example.be' }))
    expect(success).toHaveBeenCalledWith(message, 'Succès')
    expect(w.emitted('success')?.[0]?.[0]).toMatchObject({ attached_to_account: true })
  })

  it('affiche le motif précis d’un refus (email d’un compte enseignant)', async () => {
    const message = 'Cette adresse email est celle d\'un compte enseignant ou club : elle ne peut pas être partagée avec un élève.'
    put.mockRejectedValue({ response: { status: 422, data: { message, errors: { email: [message] } } } })
    const w = mountModal()

    await submitWithEmail(w, 'coach@example.be')

    expect(error).toHaveBeenCalledWith(message, 'Erreur')
    expect(w.emitted('success')).toBeUndefined()
  })

  describe('compte partagé : confirmation du changement d’email', () => {
    const conflict = {
      response: {
        status: 409,
        data: {
          success: false,
          requires_confirmation: true,
          message: 'Cet email sert à la connexion de plusieurs élèves.',
          data: {
            account_email: 'famille@example.be',
            new_email: 'nouveau@example.be',
            students: [
              { id: 11, name: 'Paul Martin', is_current: false },
              { id: 12, name: 'Léa Martin', is_current: true },
            ],
            other_students_count: 1,
          },
        },
      },
    }

    it('affiche la confirmation avec les élèves concernés, sans toast d’erreur', async () => {
      put.mockRejectedValueOnce(conflict)
      const w = mountModal()

      await submitWithEmail(w, 'nouveau@example.be')

      const prompt = w.find('[data-testid="email-scope-prompt"]')
      expect(prompt.exists()).toBe(true)
      expect(prompt.text()).toContain('famille@example.be')
      expect(prompt.text()).toContain('Paul Martin')
      expect(prompt.text()).toContain('Léa Martin (cette fiche)')
      expect(prompt.text()).toContain('1 autre(s) élève(s)')
      expect(error).not.toHaveBeenCalled()
      expect(w.emitted('success')).toBeUndefined()
    })

    it.each([
      ['email-scope-household', 'household'],
      ['email-scope-student', 'student'],
    ])('renvoie la mise à jour avec le choix (%s)', async (testId, scope) => {
      put.mockRejectedValueOnce(conflict)
      put.mockResolvedValueOnce({ data: { success: true, message: 'ok', data: student } })
      const w = mountModal()
      await submitWithEmail(w, 'nouveau@example.be')

      await w.find(`[data-testid="${testId}"]`).trigger('click')
      await flushPromises()

      expect(put).toHaveBeenLastCalledWith('/club/students/12', expect.objectContaining({ email: 'nouveau@example.be', email_scope: scope }))
      expect(w.find('[data-testid="email-scope-prompt"]').exists()).toBe(false)
      expect(success).toHaveBeenCalledWith('ok', 'Succès')
    })

    it('propose fusion ou rattachement quand l’email est celui d’un autre compte', async () => {
      const linkConflict = JSON.parse(JSON.stringify(conflict))
      linkConflict.response.data.data = { ...linkConflict.response.data.data, kind: 'link', new_email: 'autre@example.be', target_email: 'autre@example.be' }
      put.mockRejectedValueOnce(linkConflict)
      put.mockResolvedValueOnce({ data: { success: true, message: 'ok', data: student } })
      const w = mountModal()
      await submitWithEmail(w, 'autre@example.be')

      const prompt = w.find('[data-testid="email-scope-prompt"]')
      expect(prompt.text()).toContain('est l\'email d\'un autre compte élève')
      expect(w.find('[data-testid="email-scope-household"]').text()).toBe('Fusionner tout le compte avec celui de autre@example.be')
      expect(w.find('[data-testid="email-scope-student"]').text()).toBe('Rattacher seulement cet élève à autre@example.be')

      await w.find('[data-testid="email-scope-student"]').trigger('click')
      await flushPromises()
      expect(put).toHaveBeenLastCalledWith('/club/students/12', expect.objectContaining({ email: 'autre@example.be', email_scope: 'student' }))
    })

    it('n’envoie pas de choix lors d’un enregistrement normal', async () => {
      put.mockResolvedValue({ data: { success: true, message: 'ok', data: student } })
      const w = mountModal()

      await submitWithEmail(w, 'lea@example.be')

      expect(put.mock.calls[0][1]).not.toHaveProperty('email_scope')
    })

    it('annule la confirmation si l’email est de nouveau modifié', async () => {
      put.mockRejectedValueOnce(conflict)
      const w = mountModal()
      await submitWithEmail(w, 'nouveau@example.be')
      expect(w.find('[data-testid="email-scope-prompt"]').exists()).toBe(true)

      await w.find('input[type="email"]').setValue('autre@example.be')

      expect(w.find('[data-testid="email-scope-prompt"]').exists()).toBe(false)
    })
  })

  describe('compte fusionné : séparation', () => {
    const linkedResponse = (merged: boolean) => ({ data: { success: true, data: [], account_merged: merged } })

    beforeEach(() => {
      get.mockImplementation((url: string) =>
        Promise.resolve(url.endsWith('/linked') ? linkedResponse(true) : { data: { success: true, data: [] } }),
      )
    })

    it('n’affiche le bouton que si le compte est fusionné', async () => {
      const merged = mountModal()
      await flushPromises()
      expect(merged.find('[data-testid="separate-account"]').exists()).toBe(true)

      get.mockImplementation(() => Promise.resolve(linkedResponse(false)))
      const notMerged = mountModal()
      await flushPromises()
      expect(notMerged.find('[data-testid="merged-account-notice"]').exists()).toBe(false)
    })

    it('demande confirmation, sépare le compte puis recharge la fiche', async () => {
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true)
      post.mockResolvedValue({ data: { success: true, message: 'Le compte de lea@example.be est séparé du foyer.' } })
      const w = mountModal()
      await flushPromises()
      get.mockImplementation((url: string) =>
        Promise.resolve(url.endsWith('/linked') ? linkedResponse(false) : { data: { success: true, data: [] } }),
      )

      await w.find('[data-testid="separate-account"]').trigger('click')
      await flushPromises()

      expect(confirmSpy).toHaveBeenCalled()
      expect(post).toHaveBeenCalledWith('/club/students/12/separate-account')
      expect(success).toHaveBeenCalledWith('Le compte de lea@example.be est séparé du foyer.', 'Compte séparé')
      expect(w.find('[data-testid="merged-account-notice"]').exists()).toBe(false)
      confirmSpy.mockRestore()
    })

    it('ne fait rien si la confirmation est refusée', async () => {
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false)
      const w = mountModal()
      await flushPromises()

      await w.find('[data-testid="separate-account"]').trigger('click')
      await flushPromises()

      expect(post).not.toHaveBeenCalled()
      confirmSpy.mockRestore()
    })
  })

  it('envoie une chaîne vide quand l’email est effacé (détachement d’un compte partagé)', async () => {
    put.mockResolvedValue({ data: { success: true, message: 'ok', data: student } })
    const w = mountModal()

    await submitWithEmail(w, '')

    expect(put).toHaveBeenCalledWith('/club/students/12', expect.objectContaining({ email: '' }))
  })
})
