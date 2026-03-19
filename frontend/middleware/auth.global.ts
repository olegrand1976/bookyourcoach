import { useAuthStore } from '~/stores/auth'

export default defineNuxtRouteMiddleware(async (to, from) => {
  console.log('🔍 Middleware global - Route:', to.path)
  
  // Vérifier si c'est une route protégée
  if (to.path.startsWith('/teacher/') || to.path.startsWith('/student/') || to.path.startsWith('/admin') || to.path.startsWith('/club/')) {
    console.log('🛡️ Route protégée détectée:', to.path)
    
    const authStore = useAuthStore()
    
    // Côté serveur : initialiser l'auth (cookies de la requête) mais ne pas rediriger.
    // La redirection est faite côté client uniquement, car le prefetch NuxtLink ou certaines
    // requêtes initiales n'envoient pas les cookies, ce qui provoquerait un 302 vers /login à tort.
    if (process.server) {
      await authStore.initializeAuth()
      // On ne redirige pas ici : on laisse le client trancher après hydratation (cookies disponibles).
    }
    
    // Côté client uniquement : initialiser l'auth, rediriger si non authentifié ou mauvais rôle.
    // En SSR on ne redirige jamais (store peut être vide si cookies non envoyés).
    if (process.client) {
      await authStore.initializeAuth()
      console.log('🔐 État auth store après initialisation:', {
        isAuthenticated: authStore.isAuthenticated,
        hasToken: !!authStore.token,
        hasUser: !!authStore.user,
        canActAsTeacher: authStore.canActAsTeacher,
        canActAsStudent: authStore.canActAsStudent,
        isAdmin: authStore.isAdmin,
        isClub: authStore.user?.role === 'club'
      })
      if (!authStore.isAuthenticated) {
        console.log('❌ Non authentifié côté client, redirection vers /login')
        return navigateTo('/login')
      }
      if (to.path.startsWith('/teacher/') && !authStore.canActAsTeacher) {
        console.log('❌ Pas de droits enseignant')
        return navigateTo('/dashboard')
      }
      if (to.path.startsWith('/student/') && !authStore.canActAsStudent) {
        console.log('❌ Pas de droits étudiant')
        return navigateTo('/dashboard')
      }
      if (to.path.startsWith('/admin') && !authStore.isAdmin) {
        console.log('❌ Pas de droits admin')
        return navigateTo('/dashboard')
      }
      if (to.path.startsWith('/club/') && authStore.user?.role !== 'club' && !authStore.isAdmin) {
        console.log('❌ Pas de droits club')
        return navigateTo('/dashboard')
      }
    }
    console.log('✅ Accès autorisé à:', to.path)
  }
  
  // Redirection automatique selon le rôle pour les utilisateurs authentifiés
  if (to.path === '/dashboard') {
    // Côté serveur, redirection basique
    if (process.server) {
      return navigateTo('/login')
    }
    
    const authStore = useAuthStore()
    
    if (authStore.isAuthenticated && authStore.user) {
      if (authStore.user.role === 'club') {
        console.log('🔄 Redirection utilisateur club vers /club/dashboard')
        return navigateTo('/club/dashboard')
      } else if (authStore.user.role === 'teacher') {
        console.log('🔄 Redirection enseignant vers /teacher/dashboard')
        return navigateTo('/teacher/dashboard')
      } else if (authStore.user.role === 'student') {
        console.log('🔄 Redirection étudiant vers /student/dashboard')
        return navigateTo('/student/dashboard')
      } else if (authStore.user.role === 'admin') {
        console.log('🔄 Redirection admin vers /admin')
        return navigateTo('/admin')
      }
    }
  }
})
