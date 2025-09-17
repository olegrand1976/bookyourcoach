export const useAuth = () => {
    const authStore = useAuthStore()
    const config = useRuntimeConfig()
    
    // Détecter l'environnement
    const isLocal = config.public.apiBase.includes('localhost') || config.public.apiBase.includes('127.0.0.1')
    
    const initializeServerAuth = async () => {
        if (isLocal) {
            // Mode local : initialisation simple
            await authStore.initializeAuth()
        } else {
            // Mode production : initialisation avec Sanctum
            await authStore.initializeAuth()
        }
    }
    
    const login = async (credentials: { email: string, password: string }) => {
        if (isLocal) {
            // Mode local : connexion simple avec token
            return await authStore.login(credentials)
        } else {
            // Mode production : connexion avec Sanctum
            return await authStore.login(credentials)
        }
    }
    
    const logout = async () => {
        if (isLocal) {
            // Mode local : déconnexion simple
            await authStore.logout()
        } else {
            // Mode production : déconnexion avec Sanctum
            await authStore.logout()
        }
    }
    
    return {
        authStore,
        initializeServerAuth,
        login,
        logout,
        isLocal
    }
}