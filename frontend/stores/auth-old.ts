import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as any,
    token: null as string | null,
    isAuthenticated: false,
    isInitialized: false, // Ajout pour suivre l'état d'initialisation
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
      console.log('🔑 [LOGIN] Début de la connexion avec:', credentials.email)
      this.loading = true
      
      try {
        const config = useRuntimeConfig()
        
        // Utiliser $api au lieu de $fetch pour cohérence avec l'intercepteur
        const { $api } = useNuxtApp()
        const response = await $api.post('/auth/login', credentials)
        
        console.log('🔑 [LOGIN] Réponse API:', response.data)

        this.token = response.data.access_token
        this.user = response.data.user
        this.isAuthenticated = true

        // Stocker le token dans un cookie avec durée selon "Se souvenir de moi"
        const remember = credentials.remember || false
        const maxAge = remember ? 60 * 60 * 24 * 30 : 60 * 60 * 24 * 7 // 30 jours ou 7 jours
        
        // Token directement dans le store - plus simple et plus fiable
        console.log('🔑 [LOGIN] Token stocké dans le store:', this.token?.substring(0, 10) + '...')

        // Mettre à jour l'état utilisateur immédiatement pour éviter les race conditions
        await this.fetchUser()

        // Sauvegarder les données utilisateur localement
        if (process.client) {
          const userDataToSave = JSON.stringify(this.user)
          localStorage.setItem('user-data', userDataToSave)
          console.log('�� [LOGIN] Données sauvées en localStorage')
        }

        return response
      } catch (error) {
        console.error('🔑 [LOGIN] Erreur de connexion:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async logout() {
      console.log('🚪 [LOGOUT] Début de la déconnexion')
      
      try {
        if (this.token) {
          const { $api } = useNuxtApp()
          await $api.post('/auth/logout')
        }
      } catch (error) {
        console.warn('🚪 [LOGOUT] Erreur lors de la déconnexion:', error)
      }

      // SIMPLIFICATION RADICALE : Nettoyer uniquement le store
      this.user = null
      this.token = null
      this.isAuthenticated = false
      console.log('🚀 [LOGOUT SIMPLIFIÉ] Store nettoyé')
      
      // Rediriger vers la page d'accueil après déconnexion
      await navigateTo('/')
    },

    async fetchUser() {
      console.log('🔍 [FETCH USER] Début fetchUser, token présent:', !!this.token)
      if (!this.token) return

      try {
        const { $api } = useNuxtApp()
        const response = await $api.get('/auth/user')
        
        console.log('🚀 [FETCH USER SIMPLIFIÉ] Réponse:', response.data)

        this.user = response.data.user || response.data
        this.isAuthenticated = true
      } catch (error: any) {
        console.error('🔍 [FETCH USER] Erreur lors de la récupération de l\'utilisateur:', error)

        // Si c'est une erreur 401 (token expiré), déconnecter silencieusement
        if (error.status === 401 || error.response?.status === 401) {
          this.logout()
        }

        throw error
      }
    },

    async initializeAuth() {
      if (this.isInitialized) return

      console.log('🚀 [INIT SIMPLIFIÉ] Début - store seulement')
      
      // ULTRA SIMPLE : Le store fait foi
      if (this.token && !this.user) {
        try {
          await this.fetchUser()
        } catch (error) {
          this.clearAuth()
        }
      }
      
      this.isInitialized = true
      console.log('🚀 [INIT SIMPLIFIÉ] Terminé - Auth:', this.isAuthenticated, 'Token:', !!this.token)
    },

    clearAuth() {
      console.log('🚀 [CLEAR SIMPLIFIÉ] Nettoyage store')
      this.user = null
      this.token = null
      this.isAuthenticated = false
    },

    async forgotPassword(email: string) {
      console.log('🔑 [FORGOT PASSWORD] Demande de réinitialisation pour:', email)
      
      try {
        const config = useRuntimeConfig()
        
        const response = await $fetch('/auth/forgot-password', {
          method: 'POST',
          baseURL: config.public.apiBase,
          body: { email },
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        })
        
        console.log('🔑 [FORGOT PASSWORD] Réponse:', response)
        return response
      } catch (error) {
        console.error('🔑 [FORGOT PASSWORD] Erreur:', error)
        throw error
      }
    },

    async resetPassword(data: { email: string, token: string, password: string, password_confirmation: string }) {
      console.log('🔑 [RESET PASSWORD] Réinitialisation pour:', data.email)
      
      try {
        const config = useRuntimeConfig()
        
        const response = await $fetch('/auth/reset-password', {
          method: 'POST',
          baseURL: config.public.apiBase,
          body: data,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        })
        
        console.log('🔑 [RESET PASSWORD] Réponse:', response)
        return response
      } catch (error) {
        console.error('🔑 [RESET PASSWORD] Erreur:', error)
        throw error
      }
    }
  }
})
