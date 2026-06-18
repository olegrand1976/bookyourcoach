<template>
  <div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Mot de passe</h2>
    <p class="text-sm text-gray-600 mb-4">
      Modifiez le mot de passe de votre compte.
    </p>

    <form @submit.prevent="submit" class="space-y-4 max-w-md">
      <div>
        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
          Mot de passe actuel *
        </label>
        <input
          id="current_password"
          v-model="form.current_password"
          type="password"
          required
          autocomplete="current-password"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          :class="{ 'border-red-500': fieldErrors.current_password }"
        />
        <p v-if="fieldErrors.current_password" class="mt-1 text-sm text-red-600">
          {{ fieldErrors.current_password }}
        </p>
      </div>

      <div>
        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
          Nouveau mot de passe *
        </label>
        <input
          id="new_password"
          v-model="form.password"
          type="password"
          required
          minlength="8"
          autocomplete="new-password"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          :class="{ 'border-red-500': fieldErrors.password }"
        />
        <p v-if="fieldErrors.password" class="mt-1 text-sm text-red-600">
          {{ fieldErrors.password }}
        </p>
        <p v-else class="mt-1 text-xs text-gray-500">Minimum 8 caractères</p>
      </div>

      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
          Confirmer le nouveau mot de passe *
        </label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          required
          minlength="8"
          autocomplete="new-password"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
      </div>

      <div class="flex justify-end pt-2">
        <button
          type="submit"
          :disabled="saving"
          class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition-colors text-sm font-medium disabled:opacity-50"
        >
          <span v-if="!saving">Modifier le mot de passe</span>
          <span v-else class="flex items-center">
            <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            Modification...
          </span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useToast } from '~/composables/useToast'

const toast = useToast()
const { $api } = useNuxtApp()

const saving = ref(false)
const fieldErrors = reactive<Record<string, string>>({})

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const resetForm = () => {
  form.current_password = ''
  form.password = ''
  form.password_confirmation = ''
  Object.keys(fieldErrors).forEach((key) => delete fieldErrors[key])
}

const submit = async () => {
  saving.value = true
  Object.keys(fieldErrors).forEach((key) => delete fieldErrors[key])

  try {
    await $api.put('/auth/change-password', {
      current_password: form.current_password,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })

    toast.success('Mot de passe modifié avec succès')
    resetForm()
  } catch (err: any) {
    const validationErrors = err.response?.data?.errors
    if (validationErrors) {
      for (const [field, messages] of Object.entries(validationErrors)) {
        const list = messages as string[]
        if (list.length > 0) {
          fieldErrors[field] = list[0]
        }
      }
    }
    toast.error(err.response?.data?.message || 'Erreur lors de la modification du mot de passe')
  } finally {
    saving.value = false
  }
}
</script>
