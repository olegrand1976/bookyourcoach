<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Paramètres Système</h1>
            <p class="mb-6 text-gray-600">Configuration générale de la plateforme</p>

            <!-- Paramètres généraux -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Paramètres généraux</h2>
                <form @submit.prevent="saveGeneralSettings">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-1">Nom de la plateforme</label>
                            <input v-model="settings.platform_name" class="input-field" required />
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-1">Email de contact</label>
                            <input v-model="settings.contact_email" class="input-field" required type="email" />
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-1">Téléphone de contact</label>
                            <input v-model="settings.contact_phone" class="input-field" />
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-1">Fuseau horaire</label>
                            <input v-model="settings.timezone" class="input-field" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 mb-1">Adresse de la société</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Rue</label>
                                    <input v-model="settings.company_street" class="input-field" placeholder="Nom de la rue">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Numéro</label>
                                    <input v-model="settings.company_street_number" class="input-field" placeholder="Numéro">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Code postal</label>
                                    <input v-model="settings.company_postal_code" class="input-field" placeholder="Code postal">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Ville</label>
                                    <input v-model="settings.company_city" class="input-field" placeholder="Ville">
                                </div>
                                  <div>
                                      <label class="block text-sm text-gray-600 mb-1">Pays</label>
                                      <input v-model="settings.company_country" class="input-field" placeholder="France">
                                  </div>
                            </div>
                        </div>
                    </div>


                    <div class="form-button-group mt-6">
                        <button type="submit" class="btn-primary" :disabled="loading">
                            {{ loading ? 'Sauvegarde...' : 'Sauvegarder' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Statistiques système -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Informations système</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 p-4 rounded">
                        <div class="text-2xl font-bold text-blue-600">{{ stats.users || 0 }}</div>
                        <div class="text-sm text-gray-600">Utilisateurs total</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded">
                        <div class="text-2xl font-bold text-green-600">{{ stats.teachers || 0 }}</div>
                        <div class="text-sm text-gray-600">Enseignants</div>
                    </div>
                    <div class="bg-orange-50 p-4 rounded">
                        <div class="text-2xl font-bold text-orange-600">{{ stats.students || 0 }}</div>
                        <div class="text-sm text-gray-600">Étudiants</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
definePageMeta({
    layout: 'admin',
    middleware: 'admin'
})

const loading = ref(false)

// Utiliser le composable de paramètres global
const { settings, loadSettings, saveSettings } = useSettings()

const stats = ref({
    users: 0,
    teachers: 0,
    students: 0
})

// Charger les statistiques
const loadStats = async () => {
    try {
        const { $api } = useNuxtApp()
        const response = await $api.get('/admin/stats')
        if (response.data.stats) {
            stats.value = {
                users: response.data.stats.total_users || 0,
                teachers: response.data.stats.total_teachers || 0,
                students: response.data.stats.total_students || 0,
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error)
    }
}

// Sauvegarder les paramètres généraux
const saveGeneralSettings = async () => {
    loading.value = true
    try {
        console.log('🔧 [SETTINGS] Début sauvegarde des paramètres:', settings.value)

        // Utiliser le composable global pour sauvegarder ET mettre à jour le header
        const success = await saveSettings(settings.value)

        if (success) {
            console.log('✅ [SETTINGS] Paramètres sauvegardés avec succès')
            alert('Paramètres sauvegardés avec succès ! Le nom de la plateforme est maintenant mis à jour dans le header.')
        } else {
            alert('Erreur lors de la sauvegarde. Consultez la console pour plus de détails.')
        }
    } catch (error) {
        console.error('❌ [SETTINGS] Erreur lors de la sauvegarde:', error)
        alert('Erreur lors de la sauvegarde. Consultez la console pour plus de détails.')
    } finally {
        loading.value = false
    }
}

// Initialisation au montage du composant
onMounted(() => {
    loadSettings()
    loadStats()
})
</script>