import { defineStore } from 'pinia'

// Note: useNuxtApp, useCookie, navigateTo, process sont auto-importés par Nuxt
// Les erreurs TypeScript sont normales pour les auto-imports Nuxt
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck

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

        // Sauvegarder le token dans les cookies pour la persistance (API native)
        if (process.client) {
          const maxAge = credentials.remember ? 60 * 60 * 24 * 30 : 60 * 60 * 24 * 7
          const expires = new Date(Date.now() + maxAge * 1000).toUTCString()
          
          // Utiliser l'API native pour éviter les problèmes de Nuxt
          // Encoder en base64 pour éviter les problèmes d'encodage UTF-8
          const setCookie = (name, value, options = {}) => {
            // Pour les données UTF-8, encoder en base64 pour éviter les problèmes
            const encodedValue = btoa(unescape(encodeURIComponent(value)))
            let cookieString = `${name}=${encodedValue}`
            if (options.expires) cookieString += `; expires=${options.expires}`
            if (options.path) cookieString += `; path=${options.path}`
            if (options.sameSite) cookieString += `; SameSite=${options.sameSite}`
            document.cookie = cookieString
          }
          
          setCookie('auth-token', this.token, {
            expires: expires,
            path: '/',
            sameSite: 'Lax'
          })
          
          setCookie('auth-user', JSON.stringify(this.user), {
            expires: expires,
            path: '/',
            sameSite: 'Lax'
          })
          
          console.log('🚀 [LOGIN ULTRA SIMPLE] Token et user sauvegardés dans les cookies (base64 UTF-8)')
          console.log('🚀 [LOGIN ULTRA SIMPLE] Token:', this.token?.substring(0, 20) + '...')
          console.log('🚀 [LOGIN ULTRA SIMPLE] User name:', this.user?.name)
        }

        console.log('🚀 [LOGIN ULTRA SIMPLE] Token stocké, type:', typeof this.token)
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
      
      // Nettoyer les cookies côté client
      if (process.client) {
        const authTokenCookie = useCookie('auth-token', { default: () => null })
        const authUserCookie = useCookie('auth-user', { 
          default: () => null,
          serialize: JSON.stringify,
          deserialize: JSON.parse
        })
        
        // Supprimer complètement les cookies
        authTokenCookie.value = undefined
        authUserCookie.value = undefined
        
        // Alternative : utiliser la méthode native pour supprimer
        try {
          document.cookie = 'auth-token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/;'
          document.cookie = 'auth-user=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/;'
        } catch (e) {
          console.warn('Erreur lors du nettoyage des cookies:', e)
        }
        
        console.log('🚀 [LOGOUT ULTRA SIMPLE] Cookies nettoyés')
      }
      
      console.log('🚀 [LOGOUT ULTRA SIMPLE] Store nettoyé')
      
      // Rediriger vers la page de connexion après déconnexion
      await navigateTo('/login')
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
      const decodeCookieValue = (value) => {
        if (!value || typeof value !== 'string') return value
        try {
          return decodeURIComponent(escape(atob(value)))
        } catch (e) {
          try {
            return decodeURIComponent(value)
          } catch (e2) {
            return value
          }
        }
      }

      const isDecodedToken = (value) => typeof value === 'string' && value.includes('|')

      // Autoriser une ré-initialisation côté client si l'initialisation SSR a restauré
      // des cookies encodés (base64) au lieu des vraies valeurs.
      if (process.server && this.isInitialized) return
      if (process.client && this.isInitialized && isDecodedToken(this.token) && this.user?.role) return

      console.log('🚀 [INIT ULTRA SIMPLE] Début - restauration depuis cookies')
      
      // Côté serveur, utiliser useCookie de Nuxt
      if (process.server) {
        try {
          const authTokenCookie = useCookie('auth-token', { default: () => null })
          const authUserCookie = useCookie('auth-user', { default: () => null })
          
          if (authTokenCookie.value && authUserCookie.value) {
            const decodedToken = decodeCookieValue(authTokenCookie.value)
            const decodedUserRaw = decodeCookieValue(authUserCookie.value)

            this.token = decodedToken
            if (typeof decodedUserRaw === 'string') {
              this.user = JSON.parse(decodedUserRaw)
            } else {
              this.user = decodedUserRaw
            }
            this.isAuthenticated = !!this.token && !!this.user
            console.log('🚀 [INIT] Token et user restaurés depuis cookies (serveur décodé)')
          }
        } catch (error) {
          console.error('🚀 [INIT] Erreur restauration côté serveur:', error)
        }
      }
      
      // Côté client, restaurer le token et l'utilisateur depuis les cookies
      if (process.client) {
        // Utiliser l'API native des cookies pour éviter les problèmes de Nuxt
        const getCookie = (name) => {
          const value = `; ${document.cookie}`;
          const parts = value.split(`; ${name}=`);
          if (parts.length === 2) {
            const cookieValue = parts.pop().split(';').shift();
            return decodeCookieValue(cookieValue)
          }
          return null;
        }
        
        const authToken = getCookie('auth-token')
        const authUserRaw = getCookie('auth-user')
        
        console.log('🚀 [INIT ULTRA SIMPLE] Cookies lus (API native):', {
          token: authToken ? 'présent' : 'absent',
          user: authUserRaw ? 'présent' : 'absent',
          tokenType: typeof authToken,
          userType: typeof authUserRaw,
          tokenValue: authToken ? authToken.substring(0, 20) + '...' : null,
          userValue: authUserRaw ? authUserRaw.substring(0, 50) + '...' : null
        })
        
        // Traitement des cookies avec l'API native
        if (authToken && authUserRaw && 
            authToken !== 'null' && authUserRaw !== 'null' &&
            typeof authUserRaw === 'string') {
          try {
            this.token = authToken
            // authUserRaw est déjà décodé par getCookie()
            this.user = JSON.parse(authUserRaw)
            this.isAuthenticated = true
            
            console.log('🚀 [INIT ULTRA SIMPLE] Token et user restaurés depuis cookies (API native)')
            console.log('🚀 [INIT ULTRA SIMPLE] User:', this.user?.email, 'Role:', this.user?.role)
            console.log('🚀 [INIT ULTRA SIMPLE] User name:', this.user?.name)
            
            // Vérifier si le nom contient des caractères mal encodés (pattern UTF-8 corrompu)
            // Détecte les patterns communs : Ã© (é), Ã¨ (è), Ã  (à), Ã§ (ç), etc.
            const hasEncodingIssue = this.user?.name && /Ã[\x80-\xFF]/.test(this.user.name)
            if (hasEncodingIssue) {
              console.warn('🚀 [INIT] Détection encodage corrompu dans le nom:', this.user.name)
              
              // Récupérer les vraies données du backend pour corriger
              try {
                await this.fetchUser()
                
                // Re-sauvegarder avec le bon encodage
                const maxAge = 60 * 60 * 24 * 7 // 7 jours par défaut
                const expires = new Date(Date.now() + maxAge * 1000).toUTCString()
                
                const setCookie = (name, value, options = {}) => {
                  const encodedValue = btoa(unescape(encodeURIComponent(value)))
                  let cookieString = `${name}=${encodedValue}`
                  if (options.expires) cookieString += `; expires=${options.expires}`
                  if (options.path) cookieString += `; path=${options.path}`
                  if (options.sameSite) cookieString += `; SameSite=${options.sameSite}`
                  document.cookie = cookieString
                }
                
                setCookie('auth-user', JSON.stringify(this.user), {
                  expires: expires,
                  path: '/',
                  sameSite: 'Lax'
                })
                
                console.log('✅ [INIT] Cookies re-sauvegardés avec encodage correct')
                console.log('✅ [INIT] Nouveau nom:', this.user?.name)
              } catch (error) {
                console.error('❌ [INIT] Erreur correction encodage:', error)
              }
            } else {
              // Vérifier que le token est toujours valide
              try {
                await this.fetchUser()
              } catch (error) {
                console.warn('🚀 [INIT ULTRA SIMPLE] Token invalide, nettoyage')
                this.clearAuth()
              }
            }
          } catch (error) {
            console.error('🚀 [INIT ULTRA SIMPLE] Erreur lors de la restauration:', error)
            console.error('🚀 [INIT ULTRA SIMPLE] Contenu du cookie user:', authUserRaw)
            this.clearAuth()
          }
        } else {
          console.log('🚀 [INIT ULTRA SIMPLE] Pas de cookies valides trouvés (API native)')
        }
      }
      
      // Si on a déjà un token mais pas d'user, récupérer l'utilisateur
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
      this.isInitialized = false
      
      // Nettoyer les cookies côté client
      if (process.client) {
        const authTokenCookie = useCookie('auth-token', { default: () => null })
        const authUserCookie = useCookie('auth-user', { 
          default: () => null,
          serialize: JSON.stringify,
          deserialize: JSON.parse
        })
        
        // Supprimer complètement les cookies
        authTokenCookie.value = undefined
        authUserCookie.value = undefined
        
        // Alternative : utiliser la méthode native pour supprimer
        try {
          document.cookie = 'auth-token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/;'
          document.cookie = 'auth-user=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/;'
        } catch (e) {
          console.warn('Erreur lors du nettoyage des cookies:', e)
        }
        
        console.log('🚀 [CLEAR ULTRA SIMPLE] Cookies nettoyés')
      }
    },

    async forgotPassword(email: string) {
      console.log('🚀 [FORGOT PASSWORD] Demande de réinitialisation pour:', email)
      
      try {
        const config = useRuntimeConfig()
        const response = await $fetch(`${config.public.apiBase}/auth/forgot-password`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: { email }
        })
        
        console.log('🚀 [FORGOT PASSWORD] Email envoyé avec succès')
        return response
      } catch (error) {
        console.error('🚀 [FORGOT PASSWORD] Erreur:', error)
        throw error
      }
    },

    async resetPassword(token: string, password: string, password_confirmation: string) {
      console.log('🚀 [RESET PASSWORD] Réinitialisation du mot de passe')
      
      try {
        const config = useRuntimeConfig()
        const response = await $fetch(`${config.public.apiBase}/auth/reset-password`, {
          method: 'POST',
          body: { 
            token, 
            password, 
            password_confirmation 
          }
        })
        
        console.log('🚀 [RESET PASSWORD] Mot de passe réinitialisé avec succès')
        return response
      } catch (error) {
        console.error('🚀 [RESET PASSWORD] Erreur:', error)
        throw error
      }
    }
  }
})
