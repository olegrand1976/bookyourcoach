// Configuration globale pour les tests
import { vi } from 'vitest'
import { config as vtuConfig } from '@vue/test-utils'

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

// i18n mock
const i18nMap: Record<string, string> = {
    'loginPage.title': 'Se connecter à votre compte',
    'loginPage.or': 'ou',
    'loginPage.createAccount': 'Créer un compte',
    'auth.email': 'Adresse email',
    'auth.password': 'Mot de passe',
    'auth.rememberMe': 'Se souvenir de moi',
    'auth.forgotPassword': 'Mot de passe oublié',
    'auth.login': 'Se connecter',
    'registerPage.title': 'Créer un compte',
    'registerPage.or': 'ou',
    'registerPage.login': 'Se connecter',
    'registerPage.creatingAccount': 'Création du compte... ',
    'nav.dashboard': 'Tableau de bord',
    'nav.teacherSpace': 'Espace enseignant',
    'nav.profile': 'Profil',
    'nav.admin': 'Administration',
    'nav.logout': 'Se déconnecter',
    'nav.login': 'Se connecter',
    'nav.register': "S'inscrire",
    'footer.description': 'Plateforme de coaching équestre moderne',
    'dashboard.title': 'Bonjour {name}',
    'dashboard.subtitle': 'Votre espace élève',
    'dashboard.upcomingLessons': 'Prochains cours',
    'dashboard.completedLessons': 'Cours terminés',
    'dashboard.totalHours': 'Heures totales',
    'dashboard.viewAll': 'Tout voir',
    'dashboard.noLessons': 'Aucun cours planifié',
    'dashboard.bookLesson': 'Réserver un cours',
    'dashboard.with': 'avec',
    'dashboard.confirmed': 'Confirmé'
}

;(global as any).useI18n = vi.fn(() => ({
    t: (key: string) => i18nMap[key] ?? key,
    locale: { value: 'fr' }
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
    isAuthenticated: false,
    login: vi.fn(),
    logout: vi.fn(),
    register: vi.fn(),
    initializeAuth: vi.fn(),
    userName: 'Utilisateur',
    isAdmin: false,
    canActAsTeacher: false
}))

global.useToast = vi.fn(() => ({
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
    warning: vi.fn()
}))

// Mock Nuxt composables
global.useNuxtApp = vi.fn(() => ({
    $router: {
        push: vi.fn(),
        replace: vi.fn()
    },
    $api: {
        get: vi.fn(async (url: string) => {
            if (url.includes('/lessons')) {
                return { data: { data: [] } }
            }
            if (url.includes('/admin/settings')) {
                return { data: { platform_name: 'BookYourCoach' } }
            }
            if (url.includes('/auth/user')) {
                return { data: { id: 1, email: 'eleve@bookyourcoach.fr', role: 'student', name: 'Élève Test' } }
            }
            return { data: {} }
        })
    }
}))

global.useLazyFetch = vi.fn(() => ({
    data: { value: null },
    pending: { value: false },
    error: { value: null },
    refresh: vi.fn()
}))

global.$fetch = vi.fn()

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
    },
    CalendarIcon: {
        name: 'CalendarIcon',
        template: '<svg></svg>'
    },
    CheckCircleIcon: {
        name: 'CheckCircleIcon',
        template: '<svg></svg>'
    },
    ClockIcon: {
        name: 'ClockIcon',
        template: '<svg></svg>'
    }
}))

// Mock NuxtLink
vi.mock('#app', () => ({
    NuxtLink: {
        name: 'NuxtLink',
        template: '<a data-test-stub="NuxtLink"><slot></slot></a>',
        props: ['to']
    }
}))

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

// Mock app-specific global components used in layouts/pages
vi.mock('#components', () => ({
    Logo: {
        name: 'Logo',
        template: '<div class="logo-stub">Logo</div>',
        props: ['size']
    }
}))

// VTU global mocks/stubs
vtuConfig.global.mocks = {
    ...(vtuConfig.global.mocks || {}),
    $t: (key: string) => i18nMap[key] ?? key
}
vtuConfig.global.stubs = {
    ...(vtuConfig.global.stubs || {}),
    NuxtLink: true,
    Logo: true,
    LanguageSelector: true,
    EquestrianIcon: true
}

// Stub components resolution for VTU mount
;(global as any).defineComponent = (comp: any) => comp

// Provide stubs for components referenced directly
;(global as any).Logo = { name: 'Logo', template: '<div>Logo</div>', props: ['size'] }
;(global as any).EquestrianIcon = { name: 'EquestrianIcon', template: '<span />', props: ['name', 'size'] }
;(global as any).LanguageSelector = { name: 'LanguageSelector', template: '<div />' }

// Mock useSettings composable
;(global as any).useSettings = vi.fn(() => ({
    settings: (global as any).ref({
        platform_name: 'BookYourCoach',
        contact_email: 'contact@bookyourcoach.fr',
        contact_phone: '+32 2 123 45 67',
        company_address: 'Rue de l\'Équitation 123\n1000 Bruxelles\nBelgique'
    }),
    loadSettings: vi.fn(),
    saveSettings: vi.fn()
}))
