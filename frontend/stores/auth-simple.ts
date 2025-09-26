import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as any,
    token: null as string | null,
    isAuthenticated: false,
    isInitialized: false,
    loading: false
  }),

  getters: {
    canActAsTeacher: (state) => state.user?.role === 'teacher' || state.user?.role === 'admin',
    canActAsStudent: (state) => state.user?.role === 'student' || state.user?.role === 'admin',
    isAdmin: (state) => state.user?.role === 'admin',
    isTeacher: (state) => state.user?.role === 'teacher',
    isStudent: (state) => state.user?.role === 'student',
    isClub: (state) => state.user?.role === 'club',
    userName: (state) => state.user?.name || state.user?.first_name || 'Utilisateur'
  },

  actions: {
    async login(credentials: { email: string, password: string, remember?: boolean }) {
      console.log('🚀 [LOGIN ULTRA SIMPLE] Début connexion:', credentials.email)
      this.loading = true
      
      try {
        const { $api } = useNuxtApp()
        const response = await $api.post('/auth/login', credentials)
        
        console.log('🚀 [LOGIN ULTRA SIMPLE] Réponse reçue:', response.data)

        this.token = response.data.access_token
        this.user = response.data.user
        this.isAuthenticated = true

        console.log('🚀 [LOGIN ULTRA SIMPLE] Token stocké:', this.token?.substring(0, 10) + '...')
        console.log('🚀 [LOGIN ULTRA SIMPLE] User:', this.user?.email, 'Role:', this.user?.role)

        return response.data
      } catch (error) {
        console.error('🚀 [LOGIN ULTRA SIMPLE] Erreur:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async logout() {
      console.log('🚀 [LOGOUT ULTRA SIMPLE] Début déconnexion')
      
      try {
        if (this.token) {
          const { $api } = useNuxtApp()
          await $api.post('/auth/logout')
        }
      } catch (error) {
        console.warn('🚀 [LOGOUT ULTRA SIMPLE] Erreur:', error)
      }

      this.user = null
      this.token = null
      this.isAuthenticated = false
      console.log('🚀 [LOGOUT ULTRA SIMPLE] Store nettoyé')
      
      await navigateTo('/')
    },

    async fetchUser() {
      console.log('🚀 [FETCH USER ULTRA SIMPLE] Début, token présent:', !!this.token)
      if (!this.token) return

      try {
        const { $api } = useNuxtApp()
        const response = await $api.get('/auth/user')
        
        console.log('🚀 [FETCH USER ULTRA SIMPLE] Réponse:', response.data)

        this.user = response.data.user || response.data
        this.isAuthenticated = true
      } catch (error: any) {
        console.error('🚀 [FETCH USER ULTRA SIMPLE] Erreur:', error)

        if (error.status === 401 || error.response?.status === 401) {
          this.logout()
        }

        throw error
      }
    },

    async initializeAuth() {
      if (this.isInitialized) return

      console.log('🚀 [INIT ULTRA SIMPLE] Début - store uniquement')
      
      if (this.token && !this.user) {
        try {
          await this.fetchUser()
        } catch (error) {
          this.clearAuth()
        }
      }
      
      this.isInitialized = true
      console.log('🚀 [INIT ULTRA SIMPLE] Terminé - Auth:', this.isAuthenticated, 'Token:', !!this.token)
    },

    clearAuth() {
      console.log('🚀 [CLEAR ULTRA SIMPLE] Nettoyage store')
      this.user = null
      this.token = null
      this.isAuthenticated = false
    }
  }
})
