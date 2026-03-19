import axios from 'axios'
import { useAuthStore } from '~/stores/auth'

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()

  const normalizeToken = (value?: string | null) => {
    if (!value || typeof value !== 'string') return null
    if (value.includes('|')) return value
    try {
      return decodeURIComponent(escape(atob(value)))
    } catch (e) {
      return value
    }
  }

  const api = axios.create({
    baseURL: config.public.apiBase,
    headers: {
      'Accept': 'application/json'
    }
  })

  // Intercepteur : token + Content-Type uniquement pour le JSON (jamais pour FormData)
  api.interceptors.request.use((config) => {
    const isFormData = config.data && typeof FormData !== 'undefined' && (
      config.data instanceof FormData ||
      (typeof config.data.constructor !== 'undefined' && config.data.constructor.name === 'FormData')
    )
    if (!isFormData) {
      config.headers['Content-Type'] = 'application/json'
    }
    // FormData : ne pas toucher à Content-Type, le navigateur enverra multipart/form-data; boundary=...

    const authStore = useAuthStore()
    
    // Essayer d'abord depuis le store
    let token = normalizeToken(authStore.token)
    
    // Si pas dans le store, essayer depuis les cookies (pour SSR/initialisation)
    if (!token && process.client) {
      const cookies = document.cookie.split(';')
      const tokenCookie = cookies.find(c => c.trim().startsWith('auth-token='))
      if (tokenCookie) {
        try {
          const encodedValue = tokenCookie.split('=')[1]
          token = normalizeToken(encodedValue)
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
