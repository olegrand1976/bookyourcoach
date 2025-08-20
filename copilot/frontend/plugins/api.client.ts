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
                // Token expiré ou invalide - laisser le store gérer cela
                console.warn('Token invalide détecté par l\'intercepteur API')
                // Ne pas appeler logout automatiquement, laisser le store gérer
                // Nettoyer juste le cookie pour éviter les boucles
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
