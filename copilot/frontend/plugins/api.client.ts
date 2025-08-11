import axios from 'axios'

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  
  const api = axios.create({
    baseURL: config.public.apiBase,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    }
  })

  // Intercepteur pour ajouter le token d'authentification
  api.interceptors.request.use((config) => {
    const token = useCookie('auth-token').value
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
        const authStore = useAuthStore()
        authStore.logout()
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
