export default defineNuxtRouteMiddleware(async (to) => {
    const authStore = useAuthStore()

    // Si pas de token, rediriger vers login
    if (!authStore.token) {
        return navigateTo('/auth/login')
    }

    // Vérifier que l'utilisateur est toujours valide côté serveur
    try {
        await authStore.fetchUser()

        // Vérifier que l'utilisateur est admin
        if (!authStore.isAdmin) {
            throw createError({
                statusCode: 403,
                statusMessage: 'Accès refusé - Droits administrateur requis'
            })
        }
    } catch (error: any) {
        // Token invalide ou erreur serveur
        console.error('Erreur vérification admin:', error)

        // Si erreur 401, token expiré
        if (error.response?.status === 401) {
            await authStore.logout()
            return navigateTo('/auth/login?expired=true')
        }

        // Autre erreur, accès refusé
        throw createError({
            statusCode: 403,
            statusMessage: 'Accès refusé - Droits administrateur requis'
        })
    }
})
