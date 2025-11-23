export default defineNuxtRouteMiddleware(async (to) => {
    const authStore = useAuthStore()

    // Initialiser l'authentification d'abord
    try {
        await authStore.initializeAuth()
    } catch (error) {
        console.error('Erreur lors de l\'initialisation de l\'auth:', error)
        return navigateTo('/login')
    }

    // Si pas de token, rediriger vers login
    if (!authStore.token) {
        console.log('❌ [auth-admin] Pas de token, redirection vers /login')
        return navigateTo('/login')
    }

    // Vérifier que l'utilisateur est toujours valide côté serveur
    try {
        // Ne faire fetchUser que si on n'a pas encore l'utilisateur ou côté serveur
        if (!authStore.user || process.server) {
            await authStore.fetchUser()
        }

        // Vérifier que l'utilisateur est admin (avec vérification robuste)
        if (!authStore.user || authStore.user.role !== 'admin') {
            console.error('❌ [auth-admin] Utilisateur non-admin détecté:', authStore.user?.role)
            return navigateTo('/login')
        }
    } catch (error: any) {
        // Token invalide ou erreur serveur
        console.error('❌ [auth-admin] Erreur vérification admin:', error)

        // Si erreur 401, token expiré ou invalide
        if (error.response?.status === 401 || error.statusCode === 401) {
            await authStore.logout()
            return navigateTo('/login?expired=true')
        }

        // Autre erreur, rediriger vers login plutôt que de lever une erreur 500
        console.error('❌ [auth-admin] Erreur inattendue, redirection vers /login')
        return navigateTo('/login')
    }
})
