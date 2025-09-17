import { useAuthStore } from '~/stores/auth'

export default defineNuxtPlugin(async () => {
  const authStore = useAuthStore()

  // Initialisation côté client uniquement
  if (process.client) {
    console.log('🔵 Plugin auth: côté client')
    await authStore.initializeAuth()
  } else {
    console.log('🔴 Plugin auth: côté serveur - pas d\'initialisation')
  }

  return {
    provide: {
      authStore
    }
  }
})
