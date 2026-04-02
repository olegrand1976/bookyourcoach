<template>
  <div class="min-h-screen bg-gray-50 p-4 md:p-8">
    <div class="max-w-3xl mx-auto">
      <div class="mb-6 flex flex-wrap items-center gap-3">
        <NuxtLink
          to="/club/dashboard"
          class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800"
        >
          ← Tableau de bord
        </NuxtLink>
      </div>

      <div class="mb-6 md:mb-8 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-lg p-4 md:p-6 border-2 border-blue-100">
        <h1 class="text-xl md:text-3xl font-bold text-gray-900">Communications générales</h1>
        <p class="mt-2 text-sm md:text-base text-gray-600">
          Envoyez un message par email aux enseignants et/ou aux élèves actifs de votre club. Le texte est envoyé en clair (pas de mise en forme HTML).
        </p>
      </div>

      <div v-if="loadingCounts" class="bg-white rounded-xl shadow p-8 text-center text-gray-600">
        Chargement des destinataires…
      </div>

      <div v-else class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h2 class="text-lg font-semibold text-gray-900">Destinataires disponibles</h2>
          <ul class="mt-2 text-sm text-gray-600 space-y-1">
            <li>
              <strong>{{ counts.teachers_with_email }}</strong> enseignant(s) avec email valide
            </li>
            <li>
              <strong>{{ counts.students_with_email }}</strong> élève(s) avec email valide
            </li>
            <li v-if="counts.unique_total_for_both !== counts.teachers_with_email + counts.students_with_email" class="text-amber-700">
              En « Les deux », <strong>{{ counts.unique_total_for_both }}</strong> envoi(s) unique(s) (doublons d’email fusionnés).
            </li>
          </ul>
        </div>

        <form class="p-4 md:p-6 space-y-6" @submit.prevent="onSubmit">
          <fieldset>
            <legend class="text-sm font-medium text-gray-700 mb-3 block">Destinataires</legend>
            <div class="space-y-3">
              <label class="flex items-center gap-3 min-h-[44px] cursor-pointer">
                <input
                  v-model="form.audience"
                  type="radio"
                  value="teachers"
                  class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                >
                <span>Enseignants actifs uniquement</span>
              </label>
              <label class="flex items-center gap-3 min-h-[44px] cursor-pointer">
                <input
                  v-model="form.audience"
                  type="radio"
                  value="students"
                  class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                >
                <span>Élèves actifs uniquement</span>
              </label>
              <label class="flex items-center gap-3 min-h-[44px] cursor-pointer">
                <input
                  v-model="form.audience"
                  type="radio"
                  value="both"
                  class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                >
                <span>Enseignants et élèves</span>
              </label>
            </div>
          </fieldset>

          <div>
            <label for="comm-subject" class="block text-sm font-medium text-gray-700 mb-2">Objet de l’email *</label>
            <input
              id="comm-subject"
              v-model="form.subject"
              type="text"
              maxlength="200"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Ex. Fermeture exceptionnelle du club"
            >
          </div>

          <div>
            <label for="comm-body" class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
            <textarea
              id="comm-body"
              v-model="form.body"
              required
              rows="12"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-sans text-sm"
              placeholder="Rédigez votre message ici…"
            />
            <p class="mt-1 text-xs text-gray-500">
              Les balises HTML seront supprimées. Les retours à la ligne sont conservés.
            </p>
          </div>

          <div
            v-if="errorMessage"
            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
            role="alert"
          >
            {{ errorMessage }}
          </div>

          <div class="flex flex-col sm:flex-row gap-3 pt-2">
            <button
              type="submit"
              :disabled="sending"
              class="min-h-[48px] inline-flex items-center justify-center px-6 py-3 rounded-lg font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-md"
            >
              <svg
                v-if="sending"
                class="animate-spin -ml-1 mr-2 h-5 w-5 text-white"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
              {{ sending ? 'Envoi en cours…' : 'Envoyer les emails' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

definePageMeta({
  middleware: ['auth']
})

const { $api } = useNuxtApp()
const toast = useToast()

const loadingCounts = ref(true)
const sending = ref(false)
const errorMessage = ref('')

const counts = ref({
  teachers_with_email: 0,
  students_with_email: 0,
  unique_total_for_both: 0
})

const form = ref({
  audience: 'both' as 'teachers' | 'students' | 'both',
  subject: '',
  body: ''
})

onMounted(async () => {
  try {
    const res = await $api.get('/club/communications/recipient-counts')
    if (res.data?.success && res.data?.data) {
      counts.value = { ...counts.value, ...res.data.data }
    }
  } catch {
    toast.error('Impossible de charger le nombre de destinataires.')
  } finally {
    loadingCounts.value = false
  }
})

async function onSubmit() {
  errorMessage.value = ''
  const audience = form.value.audience
  let expected = 0
  if (audience === 'teachers') {
    expected = counts.value.teachers_with_email
  } else if (audience === 'students') {
    expected = counts.value.students_with_email
  } else {
    expected = counts.value.unique_total_for_both
  }
  if (expected === 0) {
    errorMessage.value = 'Aucun destinataire pour ce groupe. Vérifiez que vos membres ont un email valide et sont actifs.'
    return
  }

  const ok = confirm(
    `Confirmer l’envoi à environ ${expected} destinataire(s) ? Les emails seront envoyés immédiatement.`
  )
  if (!ok) {
    return
  }

  sending.value = true
  try {
    const res = await $api.post('/club/communications/send', {
      audience: form.value.audience,
      subject: form.value.subject,
      body: form.value.body
    })
    if (res.data?.success) {
      toast.success(res.data.message || 'Communication envoyée.')
      form.value.subject = ''
      form.value.body = ''
    } else {
      errorMessage.value = res.data?.message || 'Erreur lors de l’envoi.'
    }
  } catch (err: any) {
    errorMessage.value =
      err.response?.data?.message || err.message || 'Erreur réseau lors de l’envoi.'
    toast.error(errorMessage.value)
  } finally {
    sending.value = false
  }
}
</script>
