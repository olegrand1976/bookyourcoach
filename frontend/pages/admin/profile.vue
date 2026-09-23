<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Mon compte</h1>
      <p class="mb-6 text-gray-600">{{ authStore.user?.email }}</p>

      <div class="space-y-6">
        <ChangePasswordForm />
        <TwoFactorSettings />
      </div>
    </div>
  </div>
</template>

<script setup>
import ChangePasswordForm from '~/components/profile/ChangePasswordForm.vue'
import TwoFactorSettings from '~/components/profile/TwoFactorSettings.vue'

definePageMeta({
  layout: 'admin',
  middleware: 'admin'
})

const authStore = useAuthStore()

useHead({ title: 'Mon compte | activibe' })
</script>
