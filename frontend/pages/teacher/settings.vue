<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-6 md:mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Paramètres</h1>
                        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">Configurez vos préférences et paramètres</p>
                    </div>
                    <NuxtLink to="/teacher" 
                        class="inline-flex items-center px-4 md:px-6 py-2 md:py-3 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-lg hover:from-gray-700 hover:to-gray-800 transition-all duration-200 font-medium">
                        <span>←</span>
                        <span class="ml-2">Retour à l'espace enseignant</span>
                    </NuxtLink>
                </div>
            </div>

            <!-- Contenu temporaire -->
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <div class="text-6xl mb-4">⚙️</div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Paramètres en cours de développement</h2>
                <p class="text-gray-600 mb-6">
                    Cette fonctionnalité sera bientôt disponible pour configurer vos préférences.
                </p>
                <div class="text-left max-w-md mx-auto">
                    <h3 class="text-base md:text-lg font-semibold mb-3">Fonctionnalités prévues :</h3>
                    <ul class="space-y-2 text-gray-600">
                        <li>• Notifications par email</li>
                        <li>• Préférences de calendrier</li>
                        <li>• Paramètres de synchronisation Google Calendar</li>
                        <li>• Gestion des disponibilités</li>
                        <li>• Paramètres de facturation</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
definePageMeta({
    middleware: ['auth']
})

const authStore = useAuthStore()

// Vérifier que l'utilisateur peut agir comme enseignant
if (!authStore.canActAsTeacher) {
    throw createError({
        statusCode: 403,
        statusMessage: 'Accès refusé - Droits enseignant requis'
    })
}
</script>
