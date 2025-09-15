export default defineNuxtConfig({
    devtools: { enabled: true },

    modules: [
        '@nuxtjs/tailwindcss',
        '@pinia/nuxt',
        '@nuxtjs/google-fonts'
        // '@nuxtjs/i18n' // Temporairement désactivé pour résoudre le problème de build
    ],

    googleFonts: {
        families: {
            Inter: [400, 500, 600, 700]
        }
    },

    // Configuration i18n temporairement commentée
    // i18n: {
    //     locales: [
    //         { code: 'fr', name: 'Français', file: 'fr.json' },
    //         { code: 'en', name: 'English', file: 'en.json' },
    //         { code: 'nl', name: 'Nederlands', file: 'nl.json' },
    //         { code: 'de', name: 'Deutsch', file: 'de.json' },
    //         { code: 'it', name: 'Italiano', file: 'pt.json' },
    //         { code: 'es', name: 'Español', file: 'pt.json' },
    //         { code: 'pt', name: 'Português', file: 'pt.json' },
    //         { code: 'hu', name: 'Magyar', file: 'pt.json' },
    //         { code: 'pl', name: 'Polski', file: 'pt.json' },
    //         { code: 'zh', name: '中文', file: 'pt.json' },
    //         { code: 'ja', name: '日本語', file: 'pt.json' },
    //         { code: 'sv', name: 'Svenska', file: 'pt.json' },
    //         { code: 'no', name: 'Norsk', file: 'pt.json' },
    //         { code: 'fi', name: 'Suomi', file: 'pt.json' },
    //         { code: 'da', name: 'Dansk', file: 'pt.json' }
    //     ],
    //     defaultLocale: 'fr',
    //     langDir: './locales/',
    //     strategy: 'prefix_except_default',
    //     detectBrowserLanguage: {
    //         useCookie: true,
    //         cookieKey: 'i18n_redirected',
    //         redirectOn: 'root'
    //     }
    // },

    runtimeConfig: {
        public: {
            apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8081/api',
            appName: 'Acti\'Vibe'
        },
        // Configuration côté serveur pour Docker
        apiBase: process.env.NUXT_API_BASE || 'http://activibe-backend:80/api'
    },

    css: ['~/assets/css/main.css'],

    tailwindcss: {
        config: {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fdf7f0',
                            100: '#fbeee0',
                            200: '#f5d9c1',
                            300: '#efc19c',
                            400: '#e8a575',
                            500: '#d4824a',
                            600: '#c26d3f',
                            700: '#a15634',
                            800: '#814530',
                            900: '#693829',
                        }
                    },
                    fontFamily: {
                        serif: ['Merriweather', 'ui-serif', 'Georgia'],
                    },
                    backgroundImage: {
                        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                        'gradient-conic': 'conic-gradient(from 180deg at 50% 50%, var(--tw-gradient-stops))',
                    }
                }
            }
        }
    },

    nitro: {
        devProxy: {
            '/api': {
                target: 'http://localhost:8081/api',
                changeOrigin: true,
                prependPath: true,
            }
        }
    },

    vite: {
        server: {
            hmr: {
                protocol: 'ws',
                host: 'localhost',
            }
        }
    }
})
