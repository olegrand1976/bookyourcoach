export default defineNuxtRouteMiddleware((to) => {
    const authStore = useAuthStore()

    // Vérifier si l'utilisateur est connecté
    if (!authStore.isAuthenticated) {
        return navigateTo('/login')
    }

    // Vérifier si l'utilisateur est admin
    if (!authStore.isAdmin) {
        throw createError({
            statusCode: 403,
            statusMessage: 'Accès refusé - Droits administrateur requis'
        })
    }
})
