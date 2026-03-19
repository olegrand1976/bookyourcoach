export default defineNuxtRouteMiddleware((to) => {
    // En SSR, ne pas bloquer : laisser le client trancher après hydratation (cookies disponibles).
    if (process.server) return

    const authStore = useAuthStore()
    if (!authStore.isAuthenticated) {
        throw createError({
            statusCode: 401,
            statusMessage: 'Authentification requise'
        })
    }
    if (!authStore.canActAsStudent) {
        console.warn(`Tentative d'accès non autorisé à ${to.path} - Capacité étudiant requise`)
        throw createError({
            statusCode: 403,
            statusMessage: 'Accès réservé aux étudiants'
        })
    }
})
