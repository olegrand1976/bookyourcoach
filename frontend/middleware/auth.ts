export default defineNuxtRouteMiddleware((to, from) => {
  // Redirection uniquement côté client (après hydratation) pour éviter 302 en SSR
  // quand les cookies ne sont pas envoyés (prefetch, première requête).
  if (process.server) return
  const authStore = useAuthStore()
  if (!authStore.isAuthenticated) {
    return navigateTo('/login')
  }
})