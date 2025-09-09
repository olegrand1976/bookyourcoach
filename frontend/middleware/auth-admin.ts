export default defineNuxtRouteMiddleware(async (to) => {
    const authStore = useAuthStore()

    // Si pas de token, rediriger vers login
    if (!authStore.token) {
        return navigateTo('/login')
    }

    // Vérifier que l'utilisateur est toujours valide côté serveur
    try {
        await authStore.fetchUser()

        // Vérifier que l'utilisateur est admin (avec vérification robuste)
        if (!authStore.user || authStore.user.role !== 'admin') {
            console.error('Utilisateur non-admin détecté:', authStore.user?.role)
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
            return navigateTo('/login?expired=true')
        }

        // Autre erreur, accès refusé
        throw createError({
            statusCode: 403,
            statusMessage: 'Accès refusé - Droits administrateur requis'
        })
    }
})
