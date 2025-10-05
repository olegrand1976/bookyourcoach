export default defineNuxtPlugin(() => {
    const authStore = useAuthStore()
    const router = useRouter()

    // Vérification périodique du token toutes les 10 minutes (moins fréquent)
    let tokenCheckInterval: NodeJS.Timeout | null = null

    const startTokenValidation = () => {
        if (tokenCheckInterval) {
            clearInterval(tokenCheckInterval)
        }

        tokenCheckInterval = setInterval(async () => {
            if (authStore.isAuthenticated && authStore.token) {
                try {
                    // Tenter de récupérer les informations utilisateur pour valider le token
                    await authStore.fetchUser()
                } catch (error: any) {
                    console.warn('Token invalide détecté lors de la vérification périodique')

                    // Token expiré ou invalide, nettoyer les données
                    authStore.user = null
                    authStore.token = null
                    authStore.isAuthenticated = false

                    // Nettoyer le store via clearAuth
                    authStore.clearAuth()

                    // Rediriger vers la page de connexion avec un message
                    const currentRoute = router.currentRoute.value
                    if (currentRoute.path !== '/login') {
                        await navigateTo('/login?expired=true')
                    }
                }
            }
        }, 10 * 60 * 1000) // 10 minutes au lieu de 5
    }

    const stopTokenValidation = () => {
        if (tokenCheckInterval) {
            clearInterval(tokenCheckInterval)
            tokenCheckInterval = null
        }
    }

    // Démarrer la validation si l'utilisateur est connecté
    if (authStore.isAuthenticated) {
        startTokenValidation()
    }

    // Surveiller les changements d'état d'authentification
    watch(() => authStore.isAuthenticated, (isAuthenticated) => {
        if (isAuthenticated) {
            startTokenValidation()
        } else {
            stopTokenValidation()
        }
    })

    // Nettoyer lors de la destruction du plugin
    if (process.client) {
        window.addEventListener('beforeunload', () => {
            stopTokenValidation()
        })
    }
})
