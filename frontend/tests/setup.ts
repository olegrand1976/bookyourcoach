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
    userName: 'Alice',
    isAuthenticated: false,
    isAdmin: false,
    isTeacher: false,
    canActAsTeacher: false,
    login: vi.fn(),
    logout: vi.fn(),
    register: vi.fn(),
    initializeAuth: vi.fn()
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
    }
}))

global.useLazyFetch = vi.fn(() => ({
    data: { value: null },
    pending: { value: false },
    error: { value: null },
    refresh: vi.fn()
}))

// Mock $fetch
global.$fetch = vi.fn()

// Mock i18n
const tMock = vi.fn((key) => key)
// provide a basic useI18n composable returning t()
// @ts-ignore
global.useI18n = vi.fn(() => ({ t: tMock }))

// Mock useSettings composable returning expected structure
// @ts-ignore
global.useSettings = vi.fn(() => ({
    settings: {
        platform_name: 'BookYourCoach',
        contact_email: 'support@bookyourcoach.com',
        contact_phone: '+33 1 23 45 67 89',
        company_address: '10 rue du Cheval\n75000 Paris'
    },
    loadSettings: vi.fn()
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
    },
    CalendarIcon: {
        name: 'CalendarIcon',
        template: '<svg></svg>'
    }
}))

// Stub NuxtLink
vi.mock('#app', () => ({
    NuxtLink: {
        name: 'NuxtLink',
        template: '<a><slot></slot></a>',
        props: ['to']
    }
}))

// Stub custom components used in templates
global.Logo = { name: 'Logo', template: '<div class="logo"></div>', props: ['size'] }
global.LanguageSelector = { name: 'LanguageSelector', template: '<div class="lang"></div>' }
global.EquestrianIcon = { name: 'EquestrianIcon', template: '<i></i>', props: ['name', 'size'] }

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
