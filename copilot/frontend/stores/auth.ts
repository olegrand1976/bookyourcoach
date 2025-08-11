import { defineStore } from 'pinia'

interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'teacher' | 'student'
  is_active: boolean
  profile?: any
  teacher?: any
  student?: any
}

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  loading: boolean
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: null,
    isAuthenticated: false,
    loading: false
  }),

  getters: {
    isAdmin: (state) => state.user?.role === 'admin',
    isTeacher: (state) => state.user?.role === 'teacher',
    isStudent: (state) => state.user?.role === 'student',
    userName: (state) => state.user?.name || 'Utilisateur'
  },

  actions: {
    async login(credentials: { email: string, password: string }) {
      this.loading = true
      try {
        const { $api } = useNuxtApp()
        const response = await $api.post('/auth/login', credentials)
        
        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true
        
        // Stocker le token
        const tokenCookie = useCookie('auth-token', {
          httpOnly: false,
          secure: false,
          maxAge: 60 * 60 * 24 * 7 // 7 jours
        })
        tokenCookie.value = this.token
        
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async register(userData: {
      name: string
      email: string
      password: string
      password_confirmation: string
    }) {
      this.loading = true
      try {
        const { $api } = useNuxtApp()
        const response = await $api.post('/auth/register', userData)
        
        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true
        
        // Stocker le token
        const tokenCookie = useCookie('auth-token')
        tokenCookie.value = this.token
        
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        const { $api } = useNuxtApp()
        await $api.post('/auth/logout')
      } catch (error) {
        console.error('Erreur lors de la déconnexion:', error)
      } finally {
        this.user = null
        this.token = null
        this.isAuthenticated = false
        
        // Supprimer le token
        const tokenCookie = useCookie('auth-token')
        tokenCookie.value = null
        
        await navigateTo('/login')
      }
    },

    async fetchUser() {
      if (!this.token) return
      
      try {
        const { $api } = useNuxtApp()
        const response = await $api.get('/auth/user')
        this.user = response.data
        this.isAuthenticated = true
      } catch (error) {
        // Token invalide, déconnecter
        this.logout()
      }
    },

    initializeAuth() {
      const tokenCookie = useCookie('auth-token')
      if (tokenCookie.value) {
        this.token = tokenCookie.value
        this.fetchUser()
      }
    }
  }
})
