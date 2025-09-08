export default defineNuxtRouteMiddleware(async (to, from) => {
    // console.log('🔍 Middleware global - Route:', to.path)
    
    // Vérifier si c'est une route protégée
    if (to.path.startsWith('/teacher/') || to.path.startsWith('/student/') || to.path.startsWith('/admin') || to.path.startsWith('/club/')) {
        // console.log('🛡️ Route protégée détectée:', to.path)
        
        // Initialiser l'authentification côté serveur
        const { authStore, initializeServerAuth } = useAuth()
        await initializeServerAuth()
        
        // console.log('🔐 État auth store après initialisation:', {
        //     isAuthenticated: authStore.isAuthenticated,
        //     hasToken: !!authStore.token,
        //     hasUser: !!authStore.user,
        //     canActAsTeacher: authStore.canActAsTeacher,
        //     canActAsStudent: authStore.canActAsStudent,
        //     isAdmin: authStore.isAdmin,
        //     isClub: authStore.user?.role === 'club'
        // })
        
        if (!authStore.isAuthenticated) {
            // console.log('❌ Non authentifié, redirection vers /login')
            return navigateTo('/login')
        }
        
        // Vérifications spécifiques selon la route
        if (to.path.startsWith('/teacher/') && !authStore.canActAsTeacher) {
            // console.log('❌ Pas de droits enseignant')
            return navigateTo('/dashboard')
        }
        
        if (to.path.startsWith('/student/') && !authStore.canActAsStudent) {
            // console.log('❌ Pas de droits étudiant')
            return navigateTo('/dashboard')
        }
        
        if (to.path.startsWith('/admin') && !authStore.isAdmin) {
            // console.log('❌ Pas de droits admin')
            return navigateTo('/dashboard')
        }
        
        if (to.path.startsWith('/club/') && authStore.user?.role !== 'club' && !authStore.isAdmin) {
            // console.log('❌ Pas de droits club')
            return navigateTo('/dashboard')
        }
        
        // console.log('✅ Accès autorisé à:', to.path)
    }
    
    // Redirection automatique selon le rôle pour les utilisateurs authentifiés
    // Si l'utilisateur navigue vers /dashboard, rediriger vers son dashboard spécifique
    if (to.path === '/dashboard') {
        const { authStore } = useAuth()
        
        if (authStore.isAuthenticated && authStore.user) {
            if (authStore.user.role === 'club') {
                // console.log('🔄 Redirection utilisateur club vers /club/dashboard')
                return navigateTo('/club/dashboard')
            } else if (authStore.user.role === 'teacher') {
                // console.log('🔄 Redirection enseignant vers /teacher/dashboard')
                return navigateTo('/teacher/dashboard')
            } else if (authStore.user.role === 'student') {
                // console.log('🔄 Redirection étudiant vers /student/dashboard')
                return navigateTo('/student/dashboard')
            } else if (authStore.user.role === 'admin') {
                // console.log('🔄 Redirection admin vers /admin')
                return navigateTo('/admin')
            }
        }
    }
})
