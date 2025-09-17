export default defineNuxtConfig({
    devtools: { enabled: true },

    modules: [
        '@nuxtjs/tailwindcss',
        '@pinia/nuxt',
        '@nuxtjs/google-fonts'
    ],

    googleFonts: {
        families: {
            Inter: [400, 500, 600, 700]
        }
    },

    runtimeConfig: {
        public: {
            // Configuration alternative pour utiliser le port direct HTTPS
            apiBase: process.env.NUXT_PUBLIC_API_BASE || (process.env.NODE_ENV === 'production' ? 'https://activibe.be:8443/api' : 'http://localhost:8001/api'),
            appName: 'Acti\'Vibe'
        },
        // Configuration côté serveur pour Docker
        apiBase: process.env.NUXT_API_BASE || (process.env.NODE_ENV === 'production' ? 'http://activibe-backend:80/api' : 'http://localhost:8001/api')
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
