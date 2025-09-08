import { useAuthStore } from '~/stores/auth'

export default defineNuxtPlugin(async () => {
    const authStore = useAuthStore()

    // Initialisation avec vérification du token
    if (process.client) {
        // console.log('🔵 Plugin auth: côté client')
        await authStore.initializeAuth()
    } else {
        // console.log('🔴 Plugin auth: côté serveur')
        // Côté serveur, essayer de récupérer le token depuis les cookies
        const tokenCookie = useCookie('auth-token')
        // console.log('🍪 Token cookie:', tokenCookie.value ? 'présent' : 'absent')
        
        if (tokenCookie.value) {
            authStore.token = tokenCookie.value
            authStore.isAuthenticated = true
            // console.log('✅ Authentification côté serveur activée')
            
            // Essayer de récupérer les données utilisateur depuis l'API
            try {
                const config = useRuntimeConfig()
                // console.log('🌐 Appel API:', `${config.public.apiBase}/auth/user`)
                const response = await $fetch(`${config.public.apiBase}/auth/user-test`, {
                    headers: {
                        'Authorization': `Bearer ${tokenCookie.value}`
                    }
                })
                
                if (response.user) {
                    authStore.user = response.user
                    // console.log('👤 Utilisateur chargé:', response.user.email)
                }
            } catch (error) {
                // console.warn('❌ Erreur lors de la récupération des données utilisateur côté serveur:', error)
                // En cas d'erreur, nettoyer l'authentification
                authStore.token = null
                authStore.isAuthenticated = false
                authStore.user = null
            }
        } else {
            // console.log('❌ Pas de token cookie côté serveur')
        }
    }

    return {
        provide: {
            authStore
        }
    }
})
