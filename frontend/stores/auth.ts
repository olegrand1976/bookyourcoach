import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as any,
    token: null as string | null,
    isAuthenticated: false,
    loading: false
  }),

  getters: {
    canActAsTeacher: (state) => state.user?.role === 'teacher' || state.user?.role === 'admin',
    canActAsStudent: (state) => state.user?.role === 'student' || state.user?.role === 'admin',
    isAdmin: (state) => state.user?.role === 'admin'
  },

  actions: {
    async login(credentials: { email: string, password: string }) {
      console.log('🔑 [LOGIN] Début de la connexion avec:', credentials.email)
      this.loading = true
      
      try {
        const config = useRuntimeConfig()
        
        const response = await $fetch('/auth/login', {
          method: 'POST',
          baseURL: config.public.apiBase,
          body: credentials,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        })
        
        console.log('🔑 [LOGIN] Réponse API:', response)

        this.token = response.token
        this.user = response.user
        this.isAuthenticated = true

        // Stocker le token dans un cookie
        const tokenCookie = useCookie('auth-token', {
          httpOnly: false,
          secure: false,
          maxAge: 60 * 60 * 24 * 7 // 7 jours
        })
        tokenCookie.value = this.token
        console.log('🔑 [LOGIN] Token stocké dans cookie')

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
        const config = useRuntimeConfig()
        
        if (this.token) {
          await $fetch('/auth/logout', {
            method: 'POST',
            baseURL: config.public.apiBase,
            headers: {
              'Authorization': `Bearer ${this.token}`,
              'Accept': 'application/json'
            }
          })
        }
      } catch (error) {
        console.warn('🚪 [LOGOUT] Erreur lors de la déconnexion:', error)
      }

      // Nettoyer l'état local
      this.user = null
      this.token = null
      this.isAuthenticated = false

      // Nettoyer le cookie
      const tokenCookie = useCookie('auth-token')
      tokenCookie.value = null

      // Nettoyer le localStorage
      if (process.client) {
        localStorage.removeItem('user-data')
      }

      console.log('🚪 [LOGOUT] Déconnexion terminée')
      
      // Rediriger vers la page d'accueil après déconnexion
      await navigateTo('/')
    },

    async fetchUser() {
      console.log('🔍 [FETCH USER] Début fetchUser, token présent:', !!this.token)
      if (!this.token) return

      try {
        const config = useRuntimeConfig()
        
        const response = await $fetch('/auth/user', {
          method: 'GET',
          baseURL: config.public.apiBase,
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${this.token}`
          }
        })
        
        console.log('🔍 [FETCH USER] Réponse:', response)

        this.user = response.user || response
        this.isAuthenticated = true

        // Sauvegarder les données utilisateur localement
        if (process.client) {
          const userDataToSave = JSON.stringify(this.user)
          localStorage.setItem('user-data', userDataToSave)
          console.log('🔍 [FETCH USER] Données sauvées en localStorage')
        }
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
      console.log('🔍 [AUTH DEBUG] Début initializeAuth')
      
      if (process.client) {
        const tokenCookie = useCookie('auth-token')
        console.log('🔍 [AUTH DEBUG] Token cookie:', tokenCookie.value ? 'présent' : 'absent')

        if (tokenCookie.value) {
          this.token = tokenCookie.value

          // Essayer de récupérer les données utilisateur depuis localStorage
          const userData = localStorage.getItem('user-data')
          console.log('🔍 [AUTH DEBUG] User data localStorage:', userData ? 'présent' : 'absent')

          if (userData) {
            try {
              this.user = JSON.parse(userData)
              this.isAuthenticated = true
              console.log('🔍 [AUTH DEBUG] User restauré:', this.user.email, 'role:', this.user.role)
            } catch (e) {
              console.warn('Données utilisateur corrompues dans localStorage')
            }
          }

          // Vérifier le token avec l'API
          try {
            console.log('🔍 [AUTH DEBUG] Début vérification token...')
            await this.fetchUser()
            console.log('🔍 [AUTH DEBUG] Authentification réussie, user final:', this.user?.email, 'role:', this.user?.role)
          } catch (error) {
            console.error('Erreur lors de la vérification du token:', error)
            // En cas d'erreur, rediriger vers login
            await navigateTo('/login')
          }
        } else {
          console.log('🔍 [AUTH DEBUG] Aucun token trouvé')
        }
      }
    }
  }
})
