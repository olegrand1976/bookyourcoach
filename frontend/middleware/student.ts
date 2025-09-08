export default defineNuxtRouteMiddleware((to) => {
    const authStore = useAuthStore()

    // Vérifier l'authentification
    if (!authStore.isAuthenticated) {
        throw createError({
            statusCode: 401,
            statusMessage: 'Authentification requise'
        })
    }

    // Vérifier que l'utilisateur peut agir comme étudiant
    if (!authStore.canActAsStudent) {
        console.warn(`Tentative d'accès non autorisé à ${to.path} - Capacité étudiant requise`)
        
        throw createError({
            statusCode: 403,
            statusMessage: 'Accès réservé aux étudiants'
        })
    }
})
