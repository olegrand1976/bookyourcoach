export default defineNuxtRouteMiddleware((to) => {
    const authStore = useAuthStore()

    if (!authStore.isAuthenticated || !authStore.isAdmin) {
        throw createError({
            statusCode: 403,
            statusMessage: 'Accès non autorisé'
        })
    }
})
