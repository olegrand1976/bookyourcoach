export const useAuth = () => {
    const authStore = useAuthStore()
    
    // Fonction pour initialiser l'authentification côté serveur
    const initializeServerAuth = async () => {
        if (process.server) {
            console.log('🔴 [AUTH] Initialisation côté serveur')
            
            // Récupérer le token depuis les cookies
            const tokenCookie = useCookie('auth-token')
            console.log('🍪 [AUTH] Token cookie:', tokenCookie.value ? 'présent' : 'absent')
            
            if (tokenCookie.value) {
                authStore.token = tokenCookie.value
                authStore.isAuthenticated = true
                console.log('✅ [AUTH] Authentification côté serveur activée')
                
                try {
                    const config = useRuntimeConfig()
                    // Utiliser l'URL côté serveur pour Docker
                    const apiUrl = config.apiBase || config.public.apiBase
                    console.log('🌐 [AUTH] Appel API:', `${apiUrl}/auth/user-test`)
                    
                    const response = await $fetch(`${apiUrl}/auth/user-test`, {
                        headers: {
                            'Authorization': `Bearer ${tokenCookie.value}`
                        }
                    })
                    
                    if (response.user) {
                        authStore.user = response.user
                        console.log('👤 [AUTH] Utilisateur chargé:', response.user.email)
                        console.log('🔐 [AUTH] Droits:', {
                            canActAsTeacher: response.user.can_act_as_teacher,
                            canActAsStudent: response.user.can_act_as_student,
                            isAdmin: response.user.is_admin
                        })
                    }
                } catch (error) {
                    console.warn('❌ [AUTH] Erreur API côté serveur:', error)
                    authStore.token = null
                    authStore.isAuthenticated = false
                    authStore.user = null
                }
            } else {
                console.log('❌ [AUTH] Pas de token cookie côté serveur')
            }
        }
    }
    
    return {
        authStore,
        initializeServerAuth
    }
}
