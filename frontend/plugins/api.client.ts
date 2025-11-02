import axios from 'axios'
import { useAuthStore } from '~/stores/auth'

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()

  const api = axios.create({
    baseURL: config.public.apiBase,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    }
  })

  // Intercepteur pour ajouter le token d'authentification
  api.interceptors.request.use((config) => {
    const authStore = useAuthStore()
    
    // Essayer d'abord depuis le store
    let token = authStore.token
    
    // Si pas dans le store, essayer depuis les cookies (pour SSR/initialisation)
    if (!token && process.client) {
      const cookies = document.cookie.split(';')
      const tokenCookie = cookies.find(c => c.trim().startsWith('auth-token='))
      if (tokenCookie) {
        try {
          const encodedValue = tokenCookie.split('=')[1]
          token = decodeURIComponent(escape(atob(encodedValue)))
        } catch (e) {
          console.warn('🚀 [API SIMPLIFIÉ] Erreur lors du décodage du cookie token:', e)
        }
      }
    }
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
      console.log('🚀 [API SIMPLIFIÉ] Token ajouté:', token.substring(0, 10) + '...', 'URL:', config.url)
    } else {
      console.warn('🚀 [API SIMPLIFIÉ] ⚠️ Pas de token disponible pour:', config.url)
    }
    return config
  })

  // Intercepteur pour gérer les erreurs
  api.interceptors.response.use(
    (response) => response,
    (error) => {
      console.log('🚀 [API INTERCEPTOR] Erreur détectée:', {
        status: error.response?.status,
        message: error.response?.data?.message,
        path: error.config?.url
      })
      
      if (error.response?.status === 401) {
        // Token expiré ou invalide - nettoyer le store
        console.warn('🚨 [API INTERCEPTOR] Token invalide détecté - nettoyage du store')
        const authStore = useAuthStore()
        authStore.clearAuth()
      } else if (error.response?.status === 403) {
        // Accès interdit - log pour debugging mais ne pas déconnecter
        console.warn('🚨 [API INTERCEPTOR] Accès interdit (403):', {
          path: error.config?.url,
          user_role: error.response?.data?.user_role,
          required_role: error.response?.data?.required_role,
          message: error.response?.data?.message
        })
      }
      return Promise.reject(error)
    }
  )

  return {
    provide: {
      api
    }
  }
})
