// Configuration globale pour les tests
import { vi } from 'vitest'

// Mock des composables Nuxt
global.definePageMeta = vi.fn()
global.defineNuxtConfig = vi.fn()
global.useHead = vi.fn()
global.useMeta = vi.fn()
global.useRoute = vi.fn(() => ({
    path: '/',
    name: 'index',
    params: {},
    query: {},
    meta: {}
}))
global.useRouter = vi.fn(() => ({
    push: vi.fn(),
    replace: vi.fn(),
    go: vi.fn(),
    back: vi.fn(),
    forward: vi.fn()
}))
global.navigateTo = vi.fn()
global.useRuntimeConfig = vi.fn(() => ({
    public: {
        apiBase: 'http://localhost:8090/api'
    }
}))

global.useCookie = vi.fn((name) => ({
    value: name === 'auth-token' ? 'mock-token' : null
}))

// Mock Vue Composition API
global.ref = vi.fn((value) => ({
    value,
    __v_isRef: true
}))
global.reactive = vi.fn((obj) => obj)
global.computed = vi.fn((fn) => ({
    value: fn(),
    __v_isRef: true
}))
global.watch = vi.fn()
global.watchEffect = vi.fn()
global.onMounted = vi.fn()
global.onUnmounted = vi.fn()
global.nextTick = vi.fn(() => Promise.resolve())

// Mock Pinia stores
global.useAuthStore = vi.fn(() => ({
    user: null,
    token: 'mock-token',
    isAuthenticated: false,
    login: vi.fn(),
    logout: vi.fn(),
    register: vi.fn(),
    initializeAuth: vi.fn(() => Promise.resolve()),
    clearAuth: vi.fn()
}))

global.useToast = vi.fn(() => ({
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
    warning: vi.fn(),
    showToast: vi.fn() // Ajout de showToast pour compatibilité
}))

// Mock useSettings composable
global.useSettings = vi.fn(() => ({
    currentLanguage: 'fr',
    setLanguage: vi.fn(),
    theme: 'light',
    setTheme: vi.fn(),
    settings: {
        value: {
            platform_name: 'BookYourCoach',
            platform_description: 'La plateforme de référence pour réserver vos cours de sports',
            logo_url: '/logo-activibe.svg'
        }
    },
    loadSettings: vi.fn()
}))

// Mock useI18n composable
global.useI18n = vi.fn(() => ({
    t: vi.fn((key) => key),
    locale: 'fr',
    locales: ['fr', 'en']
}))

// Mock Nuxt composables
const mockApi = {
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  delete: vi.fn()
}

global.useNuxtApp = vi.fn(() => ({
  $api: mockApi,
  $router: {
    push: vi.fn(),
    replace: vi.fn()
  }
}))

global.useLazyFetch = vi.fn(() => ({
    data: { value: null },
    pending: { value: false },
    error: { value: null },
    refresh: vi.fn()
}))

global.$fetch = vi.fn(() => Promise.resolve({
    club: {
        disciplines: [],
        specializations: []
    }
}))

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
    ChevronDownIcon: {
        name: 'ChevronDownIcon',
        template: '<svg></svg>'
    },
    UserIcon: {
        name: 'UserIcon',
        template: '<svg></svg>'
    },
    HomeIcon: {
        name: 'HomeIcon',
        template: '<svg></svg>'
    }
}))

// Mock NuxtLink et useNuxtApp depuis #app
vi.mock('#app', async () => {
    const mockApi = {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn()
    }
    
    return {
        NuxtLink: {
            name: 'NuxtLink',
            template: '<a><slot></slot></a>',
            props: ['to']
        },
        useNuxtApp: () => ({
            $api: mockApi,
            $router: {
                push: vi.fn(),
                replace: vi.fn()
            }
        })
    }
})

// Mock $t function globally
global.$t = vi.fn((key) => key)

// Prevent actual navigation
Object.defineProperty(window, 'location', {
    value: {
        href: 'http://localhost:3000',
        assign: vi.fn(),
        replace: vi.fn(),
        reload: vi.fn()
    },
    writable: true
})

vi.mock('@pinia/nuxt', () => ({
    useNuxtApp: () => ({
        $pinia: {}
    })
}))
