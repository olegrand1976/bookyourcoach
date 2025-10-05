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
    
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`
      console.log('🚀 [API SIMPLIFIÉ] Token ajouté du store:', authStore.token.substring(0, 10) + '...')
    } else {
      console.log('🚀 [API SIMPLIFIÉ] Pas de token dans store')
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
