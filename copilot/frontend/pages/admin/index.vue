<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Dashboard Administrateur</h1>
                <p class="mt-2 text-gray-600">Vue d'ensemble de la plateforme BookYourCoach</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Utilisateurs</h3>
                    <p class="text-2xl font-bold text-primary-600">3</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Enseignants</h3>
                    <p class="text-2xl font-bold text-primary-600">1</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Élèves</h3>
                    <p class="text-2xl font-bold text-primary-600">1</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Clubs</h3>
                    <p class="text-2xl font-bold text-primary-600">0</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Actions rapides</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button class="btn-primary">Créer un club</button>
                    <button class="btn-primary">Ajouter un utilisateur</button>
                    <button class="btn-primary">Paramètres système</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
definePageMeta({
    middleware: 'auth'
})
</script>
