export default defineNuxtPlugin(() => {
    const authStore = useAuthStore()
    const router = useRouter()

    // Vérification périodique du token toutes les 5 minutes
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
                    console.warn('Token invalide détecté, déconnexion automatique')

                    // Token expiré ou invalide, déconnecter automatiquement
                    await authStore.logout()

                    // Rediriger vers la page de connexion avec un message
                    const currentRoute = router.currentRoute.value
                    if (currentRoute.path !== '/login') {
                        await navigateTo('/login?expired=true')
                    }
                }
            }
        }, 5 * 60 * 1000) // 5 minutes
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

    // Nettoyer lors de la destruction
    onBeforeUnmount(() => {
        stopTokenValidation()
    })
})
