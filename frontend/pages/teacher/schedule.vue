<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            Mon Planning
                        </h1>
                        <p class="mt-2 text-gray-600">
                            Gérez vos disponibilités et vos créneaux de cours
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <NuxtLink to="/teacher/dashboard" 
                            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            <span>←</span>
                            <span class="ml-2">Retour au dashboard</span>
                        </NuxtLink>
                    </div>
                </div>
            </div>

            <!-- Intégration Google Calendar -->
            <div class="mb-8">
                <GoogleCalendarIntegration :teacher-id="authStore.user?.id" />
            </div>

            <!-- Calendrier -->
            <TeacherCalendar :teacher-id="authStore.user?.id" />
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



