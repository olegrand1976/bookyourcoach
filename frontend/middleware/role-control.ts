export default defineNuxtRouteMiddleware((to) => {
    const authStore = useAuthStore()

    // Vérifier l'authentification
    if (!authStore.isAuthenticated) {
        throw createError({
            statusCode: 401,
            statusMessage: 'Authentification requise'
        })
    }

    // Contrôle d'accès par rôle
    const requiredRole = to.meta.requiresRole as string

    if (requiredRole && authStore.user?.role !== requiredRole) {
        // Log de tentative d'accès non autorisé (côté client)
        console.warn(`Tentative d'accès non autorisé à ${to.path} - Rôle requis: ${requiredRole}, Rôle utilisateur: ${authStore.user?.role}`)

        throw createError({
            statusCode: 403,
            statusMessage: 'Accès non autorisé pour ce rôle'
        })
    }

    // Contrôle d'accès par permissions spécifiques
    const requiredPermissions = to.meta.requiresPermissions as string[]

    if (requiredPermissions && requiredPermissions.length > 0) {
        // Vérifier si l'utilisateur a toutes les permissions requises
        const userPermissions = getUserPermissions(authStore.user?.role)
        const hasAllPermissions = requiredPermissions.every(permission =>
            userPermissions.includes(permission)
        )

        if (!hasAllPermissions) {
            throw createError({
                statusCode: 403,
                statusMessage: 'Permissions insuffisantes'
            })
        }
    }
})

// Fonction helper pour obtenir les permissions selon le rôle
function getUserPermissions(role: string | undefined): string[] {
    switch (role) {
        case 'admin':
            return ['read', 'write', 'delete', 'manage_users', 'manage_settings']
        case 'teacher':
            return ['read', 'write', 'manage_lessons', 'view_students']
        case 'student':
            return ['read', 'book_lessons', 'view_profile']
        default:
            return []
    }
}
