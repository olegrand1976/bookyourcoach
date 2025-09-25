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
    const cookieToken = useCookie('auth-token').value
    // Utilise le token du store s'il est disponible (juste après le login) ou celui du cookie
    const token = authStore.token || cookieToken
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  })

  // Intercepteur pour gérer les erreurs
  api.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        // Token expiré ou invalide
        console.warn('Token invalide détecté par l\'intercepteur API')
        const tokenCookie = useCookie('auth-token')
        tokenCookie.value = null
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
