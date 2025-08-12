export default defineNuxtPlugin(async () => {
    const authStore = useAuthStore()

    // Initialiser l'authentification dès le chargement de l'application
    authStore.initializeAuth()

    return {
        provide: {
            authStore
        }
    }
})
