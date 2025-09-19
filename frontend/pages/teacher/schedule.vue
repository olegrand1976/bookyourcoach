<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">
                    Mon Planning
                </h1>
                <p class="mt-2 text-gray-600">
                    Gérez vos disponibilités et vos créneaux de cours
                </p>
            </div>

            <!-- Contenu temporaire -->
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <div class="text-6xl mb-4">📅</div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Planning en cours de développement</h2>
                <p class="text-gray-600 mb-6">
                    Cette fonctionnalité sera bientôt disponible pour gérer votre planning.
                </p>
                <NuxtLink to="/teacher/dashboard" 
                    class="btn-primary bg-blue-600 text-white">
                    Retour au dashboard
                </NuxtLink>
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



