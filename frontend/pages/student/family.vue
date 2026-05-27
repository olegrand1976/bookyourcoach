<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <header class="mb-6 md:mb-8">
        <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
              Mon compte famille
            </h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">
              Rattachez et gérez les enfants liés à votre compte.
            </p>
          </div>
          <NuxtLink
            to="/student/dashboard"
            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm md:text-base"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Retour au dashboard
          </NuxtLink>
        </div>
      </header>

      <section
        class="bg-blue-50 border border-blue-100 rounded-xl p-5 mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
        aria-labelledby="link-section-title"
      >
        <div class="flex items-start gap-3">
          <div class="bg-blue-100 rounded-lg p-2 shrink-0" aria-hidden="true">
            <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </div>
          <div>
            <h2 id="link-section-title" class="font-semibold text-gray-900">
              Rattacher un enfant
            </h2>
            <p class="text-sm text-gray-700">
              Le club vous a remis un code d'invitation à 10 caractères ? Utilisez-le ici pour ajouter l'enfant à votre compte.
            </p>
          </div>
        </div>
        <button
          type="button"
          class="px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 whitespace-nowrap"
          @click="openModal = true"
        >
          Saisir un code
        </button>
      </section>

      <div v-if="loading" class="flex justify-center py-12">
        <div class="text-center">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto mb-3"></div>
          <p class="text-gray-600 text-sm">Chargement des membres de la famille…</p>
        </div>
      </div>

      <div v-else-if="errorMessage" class="bg-red-50 border border-red-200 rounded-xl p-5 text-red-800" role="alert">
        {{ errorMessage }}
      </div>

      <div v-else>
        <h2 class="text-lg font-semibold text-gray-900 mb-3">
          Membres de la famille ({{ children.length }})
        </h2>

        <div v-if="children.length === 0" class="bg-white rounded-xl shadow p-8 text-center text-gray-600">
          Vous n'avez pas encore d'enfant rattaché à votre compte.
        </div>

        <ul v-else class="grid gap-4 sm:grid-cols-2">
          <li
            v-for="child in children"
            :key="child.id"
            class="bg-white rounded-xl shadow border border-gray-100 p-5 flex flex-col gap-3"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <h3 class="font-semibold text-gray-900 truncate">
                    {{ child.name || `${child.first_name ?? ''} ${child.last_name ?? ''}`.trim() || 'Élève sans nom' }}
                  </h3>
                  <span
                    v-if="child.is_primary"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                  >Principal</span>
                  <span
                    v-else-if="child.is_linked_child"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                  >Enfant rattaché</span>
                </div>
                <p v-if="child.age" class="text-sm text-gray-500 mt-1">
                  {{ child.age }} ans
                </p>
                <p v-if="child.club" class="text-sm text-gray-600 mt-1">
                  Club : {{ child.club.name }}
                </p>
              </div>
            </div>

            <div class="flex flex-wrap gap-2 mt-auto pt-2 border-t border-gray-100">
              <button
                type="button"
                class="text-sm px-3 py-1.5 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                @click="viewChildLessons(child)"
              >
                Voir les cours
              </button>
              <button
                v-if="child.is_linked_child"
                type="button"
                class="text-sm px-3 py-1.5 rounded-md bg-red-50 text-red-700 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500"
                :disabled="unlinkingId === child.id"
                @click="confirmUnlink(child)"
              >
                <span v-if="unlinkingId === child.id">Dissociation…</span>
                <span v-else>Dissocier</span>
              </button>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <LinkChildModal
      v-model="openModal"
      @linked="handleLinked"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import LinkChildModal from '~/components/family/LinkChildModal.vue'
import { useStudentScopeStore } from '~/stores/studentScope'
import { useToast } from '~/composables/useToast'

definePageMeta({
  middleware: ['auth', 'student'],
  layout: 'student'
})

interface FamilyChild {
  id: number
  name: string | null
  first_name: string | null
  last_name: string | null
  date_of_birth: string | null
  age: number | null
  email: string | null
  is_primary: boolean
  is_linked_child: boolean
  linked_at: string | null
  club: { id: number; name: string } | null
  clubs: Array<{ id: number; name: string }>
}

const { $api } = useNuxtApp()
const router = useRouter()
const toast = useToast()
const scopeStore = useStudentScopeStore()

const children = ref<FamilyChild[]>([])
const loading = ref(true)
const errorMessage = ref<string | null>(null)
const openModal = ref(false)
const unlinkingId = ref<number | null>(null)

const loadChildren = async () => {
  loading.value = true
  errorMessage.value = null
  try {
    const response = await $api.get<{ success: boolean; data: FamilyChild[] }>('/student/family/children')
    if (response.data.success) {
      children.value = response.data.data || []
    } else {
      errorMessage.value = 'Impossible de charger les membres de la famille.'
    }
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Erreur lors du chargement.'
  } finally {
    loading.value = false
  }
}

const handleLinked = async (child: { id: number; name: string }) => {
  toast.success(`${child.name || 'Enfant'} a été rattaché à votre compte.`, 'Compte famille')
  await loadChildren()
}

const viewChildLessons = (child: FamilyChild) => {
  scopeStore.setScope(child.id)
  router.push('/student/bookings')
}

const confirmUnlink = async (child: FamilyChild) => {
  const confirmed = window.confirm(
    `Êtes-vous sûr de vouloir dissocier ${child.name || 'cet enfant'} de votre compte ? Un nouveau code d'invitation sera généré.`
  )
  if (!confirmed) return

  unlinkingId.value = child.id
  try {
    const response = await $api.delete<{ success: boolean; message?: string }>(`/student/family/children/${child.id}`)
    if (response.data.success) {
      toast.success(response.data.message || 'Enfant dissocié.', 'Compte famille')
      await loadChildren()
      await scopeStore.loadLinkedAccounts()
    } else {
      toast.error(response.data.message || 'Erreur lors de la dissociation.')
    }
  } catch (err: any) {
    toast.error(err?.response?.data?.message || 'Erreur lors de la dissociation.')
  } finally {
    unlinkingId.value = null
  }
}

onMounted(() => {
  loadChildren()
})
</script>
