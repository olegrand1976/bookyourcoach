export default defineNuxtRouteMiddleware(async (to, from) => {
  const authStore = useAuthStore()

  // Attendre que l'état d'authentification soit initialisé
  if (authStore.loading) {
    await new Promise(resolve => {
      const unsubscribe = authStore.$onAction(({ name, after }) => {
        if (name === 'initializeAuth') {
          after(() => {
            unsubscribe()
            resolve()
          })
        }
      })
    })
  }
  
  if (!authStore.isAuthenticated) {
    return navigateTo('/login')
  }
  
  if (!authStore.isAdmin) {
    // Rediriger vers une page "non autorisé" serait mieux, mais en attendant, le dashboard principal est un bon repli.
    return navigateTo('/') 
  }
})