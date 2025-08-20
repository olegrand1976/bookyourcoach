import { useAuthStore } from '~/stores/auth'

export default defineNuxtPlugin(() => {
    const authStore = useAuthStore()

    // Initialisation simple sans appel API async
    authStore.initializeAuth()

    return {
        provide: {
            authStore
        }
    }
})
