import { defineStore } from 'pinia'

interface User {
    id: number
    name: string
    email: string
    role: 'admin' | 'teacher' | 'student'
    is_active: boolean
    profile?: any
    teacher?: any
    student?: any
}

interface AuthState {
    user: User | null
    token: string | null
    isAuthenticated: boolean
    loading: boolean
}

export const useAuthStore = defineStore('auth', {
    state: (): AuthState => ({
        user: null,
        token: null,
        isAuthenticated: false,
        loading: false
    }),

    getters: {
        isAdmin: (state) => state.user?.role === 'admin',
        isTeacher: (state) => state.user?.role === 'teacher',
        isStudent: (state) => state.user?.role === 'student',
        userName: (state) => state.user?.name || 'Utilisateur'
    },

    actions: {
        async login(credentials: { email: string, password: string }) {
            console.log('🔑 [LOGIN] Début de la connexion avec:', credentials.email)
            this.loading = true
            try {
                const { $api } = useNuxtApp()
                console.log('🔑 [LOGIN] Appel API /auth/login...')
                const response = await $api.post('/auth/login', credentials)
                console.log('🔑 [LOGIN] Réponse API:', response.data)

                this.token = response.data.token
                this.user = response.data.user
                this.isAuthenticated = true

                console.log('🔑 [LOGIN] Utilisateur connecté:', {
                    id: this.user.id,
                    email: this.user.email,
                    role: this.user.role,
                    name: this.user.name
                })

                // Stocker le token
                const tokenCookie = useCookie('auth-token', {
                    httpOnly: false,
                    secure: false,
                    maxAge: 60 * 60 * 24 * 7 // 7 jours
                })
                tokenCookie.value = this.token
                console.log('🔑 [LOGIN] Token stocké dans cookie')

                return response.data
            } catch (error) {
                console.error('🔑 [LOGIN] Erreur de connexion:', error)
                throw error
            } finally {
                this.loading = false
            }
        },

        async register(userData: {
            name: string
            email: string
            password: string
            password_confirmation: string
        }) {
            this.loading = true
            try {
                const { $api } = useNuxtApp()
                const response = await $api.post('/auth/register', userData)

                this.token = response.data.token
                this.user = response.data.user
                this.isAuthenticated = true

                // Stocker le token
                const tokenCookie = useCookie('auth-token')
                tokenCookie.value = this.token

                return response.data
            } catch (error) {
                throw error
            } finally {
                this.loading = false
            }
        },

        async logout() {
            try {
                const { $api } = useNuxtApp()
                await $api.post('/auth/logout')
            } catch (error) {
                console.error('Erreur lors de la déconnexion:', error)
            } finally {
                this.user = null
                this.token = null
                this.isAuthenticated = false

                // Supprimer le token
                const tokenCookie = useCookie('auth-token')
                tokenCookie.value = null

                // Nettoyer le localStorage si présent
                if (process.client) {
                    localStorage.removeItem('auth-token')
                    localStorage.removeItem('user-data')
                }

                await navigateTo('/login')
            }
        },

        async fetchUser() {
            console.log('🔍 [FETCH USER] Début fetchUser, token présent:', !!this.token)
            if (!this.token) return

            try {
                const { $api } = useNuxtApp()
                console.log('🔍 [FETCH USER] Appel API /auth/user...')
                const response = await $api.get('/auth/user')
                console.log('🔍 [FETCH USER] Réponse complète:', JSON.stringify(response.data, null, 2))

                this.user = response.data.user || response.data
                this.isAuthenticated = true

                console.log('🔍 [FETCH USER] User assigné:', {
                    id: this.user.id,
                    email: this.user.email,
                    role: this.user.role,
                    name: this.user.name
                })

                // Sauvegarder les données utilisateur localement
                if (process.client) {
                    const userDataToSave = JSON.stringify(this.user)
                    localStorage.setItem('user-data', userDataToSave)
                    console.log('🔍 [FETCH USER] Données sauvées en localStorage:', userDataToSave)
                }
            } catch (error: any) {
                console.error('🔍 [FETCH USER] Erreur lors de la récupération de l\'utilisateur:', error)

                // Si c'est une erreur 401 (token expiré), déconnecter silencieusement
                if (error.response?.status === 401) {
                    this.user = null
                    this.token = null
                    this.isAuthenticated = false

                    const tokenCookie = useCookie('auth-token')
                    tokenCookie.value = null

                    if (process.client) {
                        localStorage.removeItem('auth-token')
                        localStorage.removeItem('user-data')
                    }
                }

                throw error
            }
        },

        async initializeAuth() {
            console.log('🔍 [AUTH DEBUG] Début initializeAuth')
            if (process.client) {
                const tokenCookie = useCookie('auth-token')
                console.log('🔍 [AUTH DEBUG] Token cookie:', tokenCookie.value ? 'présent' : 'absent')

                if (tokenCookie.value) {
                    this.token = tokenCookie.value

                    // Essayer de récupérer les données utilisateur depuis localStorage
                    const userData = localStorage.getItem('user-data')
                    console.log('🔍 [AUTH DEBUG] User data localStorage:', userData ? 'présent' : 'absent')

                    if (userData) {
                        try {
                            this.user = JSON.parse(userData)
                            this.isAuthenticated = true
                            console.log('🔍 [AUTH DEBUG] User restauré:', this.user.email, 'role:', this.user.role)
                        } catch (e) {
                            console.warn('Données utilisateur corrompues dans localStorage')
                        }
                    }

                    // Vérifier la validité du token de manière synchrone
                    try {
                        console.log('🔍 [AUTH DEBUG] Début vérification token...')
                        const isValid = await this.verifyToken()
                        console.log('🔍 [AUTH DEBUG] Token valide:', isValid)

                        if (!isValid) {
                            console.warn('Token invalide lors de la vérification')
                            // Token invalide, rediriger vers login
                            await navigateTo('/login')
                        } else {
                            console.log('🔍 [AUTH DEBUG] Authentification réussie, user final:', this.user?.email, 'role:', this.user?.role)
                        }
                    } catch (error) {
                        console.error('Erreur lors de la vérification du token:', error)
                        // En cas d'erreur, rediriger vers login
                        await navigateTo('/login')
                    }
                } else {
                    console.log('🔍 [AUTH DEBUG] Aucun token trouvé')
                }
            }
        },

        // Nouvelle méthode pour forcer la vérification du token
        async verifyToken() {
            if (!this.token) {
                return false
            }

            try {
                await this.fetchUser()
                return true
            } catch (error: any) {
                if (error.response?.status === 401) {
                    // Nettoyer les données d'authentification
                    this.user = null
                    this.token = null
                    this.isAuthenticated = false

                    const tokenCookie = useCookie('auth-token')
                    tokenCookie.value = null

                    if (process.client) {
                        localStorage.removeItem('auth-token')
                        localStorage.removeItem('user-data')
                    }
                }
                return false
            }
        }
    }
})
