import { ref, readonly } from 'vue'

/**
 * Composable pour la gestion des paramètres système
 */
export const useSettings = () => {
    // État réactif pour les paramètres
    const settings = ref({
        platform_name: 'BookYourCoach',
        contact_email: 'contact@bookyourcoach.fr',
        contact_phone: '+32 2 123 45 67',
        company_address: 'Rue de l\'Équitation 123\n1000 Bruxelles\nBelgique',
        timezone: 'Europe/Brussels',
        logo_url: '/logo.png',
        favicon_url: '/favicon.ico'
    })

    // Charger les paramètres depuis l'API
    const loadSettings = async () => {
        try {
            const { $api } = useNuxtApp()
            const response = await $api.get('/admin/settings')

            if (response.data?.success && response.data?.data) {
                settings.value = { ...settings.value, ...response.data.data }
            }
        } catch (error) {
            console.warn('Impossible de charger les paramètres:', error)
            // Les valeurs par défaut sont conservées
        }
    }

    // Sauvegarder les paramètres
    const saveSettings = async (newSettings: Partial<typeof settings.value>) => {
        try {
            const { $api } = useNuxtApp()
            const response = await $api.post('/admin/settings', newSettings)

            if (response.data?.success) {
                settings.value = { ...settings.value, ...newSettings }
                return true
            }
            return false
        } catch (error) {
            console.error('Erreur lors de la sauvegarde:', error)
            return false
        }
    }

    return {
        settings: readonly(settings),
        loadSettings,
        saveSettings
    }
}
