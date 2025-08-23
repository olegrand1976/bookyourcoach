import { useAuthStore } from '~/stores/auth'

export default defineNuxtPlugin(async () => {
    const authStore = useAuthStore()

    // Initialisation avec vérification du token
    await authStore.initializeAuth()

    return {
        provide: {
            authStore
        }
    }
})
