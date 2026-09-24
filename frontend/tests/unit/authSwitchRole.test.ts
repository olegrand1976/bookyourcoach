import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '../../stores/auth'

const get = vi.fn()
const dual = { id: 7, role: 'teacher', available_roles: ['club', 'teacher'] }

describe('authStore.switchRole', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    get.mockReset()
    vi.mocked(navigateTo).mockReset()
    vi.stubGlobal('useNuxtApp', () => ({ $api: { get } }))
  })

  const store = (user: any = dual) => {
    const s = useAuthStore()
    s.user = { ...user }
    s.token = '1|jeton'
    s.isAuthenticated = true
    return s
  }

  it('ne propose la bascule qu’à un compte double', () => {
    expect(store().hasDualProfile).toBe(true)
    expect(store({ role: 'teacher', available_roles: ['teacher'] }).hasDualProfile).toBe(false)
    expect(store({ role: 'teacher' }).hasDualProfile).toBe(false)
  })

  it('bascule en club une fois confirmé par le serveur, puis ouvre le tableau de bord club', async () => {
    const s = store()
    // Le serveur répond avec le rôle demandé (celui du store au moment de l'appel).
    get.mockImplementation(async () => ({ data: { user: { ...dual, role: s.user.role } } }))

    await s.switchRole('club')

    expect(get).toHaveBeenCalledWith('/auth/user')
    expect(s.user.role).toBe('club')
    expect(navigateTo).toHaveBeenCalledWith('/club/dashboard')
  })

  it('revient au rôle précédent si le serveur refuse la bascule', async () => {
    const s = store()
    get.mockResolvedValue({ data: { user: { ...dual, role: 'teacher' } } })

    await expect(s.switchRole('club')).rejects.toThrow()

    expect(s.user.role).toBe('teacher')
    expect(navigateTo).not.toHaveBeenCalled()
  })

  it('ignore un rôle que le compte ne détient pas', async () => {
    const s = store({ role: 'teacher', available_roles: ['teacher'] })

    await s.switchRole('club')

    expect(get).not.toHaveBeenCalled()
    expect(s.user.role).toBe('teacher')
  })
})
