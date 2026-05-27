<template>
  <section
    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
    aria-labelledby="invite-code-card-title"
  >
    <header class="flex items-start justify-between gap-3 mb-3">
      <div>
        <h3 id="invite-code-card-title" class="text-base font-semibold text-gray-900">
          Code d'invitation parent
        </h3>
        <p class="text-sm text-gray-600">
          Permet au parent de rattacher cet enfant à son compte famille.
        </p>
      </div>
      <span
        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
        :class="statusBadgeClass"
        :aria-label="`Statut : ${statusLabel}`"
      >
        {{ statusLabel }}
      </span>
    </header>

    <div v-if="isLinked" class="rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800">
      Cet élève est déjà rattaché au compte
      <span class="font-medium">{{ parentEmail || '(sans email)' }}</span>.
    </div>

    <div v-else>
      <div v-if="loading" class="py-6 flex items-center justify-center">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-emerald-600"></div>
      </div>

      <div v-else-if="loadError" class="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-800" role="alert">
        {{ loadError }}
      </div>

      <div v-else>
        <div
          class="rounded-lg bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 p-4 flex items-center justify-between gap-3 mb-3"
        >
          <div class="min-w-0">
            <div class="text-xs uppercase tracking-wide text-emerald-700 font-medium mb-1">
              Code
            </div>
            <div class="font-mono text-2xl tracking-widest text-emerald-900 truncate">
              {{ currentCode || '—' }}
            </div>
            <div v-if="expiresAtLabel" class="text-xs text-emerald-700 mt-1">
              Expire le {{ expiresAtLabel }}
            </div>
            <div v-if="isExpired" class="text-xs text-red-600 mt-1">
              Code expiré — régénérez-le.
            </div>
          </div>
          <button
            type="button"
            class="px-3 py-2 rounded-md bg-white border border-emerald-300 text-emerald-700 text-sm font-medium hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!currentCode || isExpired"
            @click="copyCode"
          >
            <span v-if="copied">Copié</span>
            <span v-else>Copier</span>
          </button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="px-3 py-2 rounded-md bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="regenerating"
            @click="regenerate"
          >
            <span v-if="regenerating">Génération…</span>
            <span v-else-if="currentCode">Régénérer le code</span>
            <span v-else>Générer un code</span>
          </button>
          <p class="text-xs text-gray-500">
            La régénération invalide immédiatement l'ancien code.
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'

interface InviteCodeStudent {
  id: number
  user_id: number | null
  invite_code?: string | null
  invite_code_expires_at?: string | null
  user?: { email?: string | null } | null
}

const props = defineProps<{
  student: InviteCodeStudent
}>()

const emit = defineEmits<{
  (e: 'updated', payload: { invite_code: string | null; invite_code_expires_at: string | null }): void
}>()

const { $api } = useNuxtApp()

const currentCode = ref<string | null>(props.student.invite_code ?? null)
const expiresAt = ref<string | null>(props.student.invite_code_expires_at ?? null)
const loading = ref(false)
const loadError = ref<string | null>(null)
const regenerating = ref(false)
const copied = ref(false)

const isLinked = computed(() => props.student.user_id !== null)
const parentEmail = computed(() => props.student.user?.email ?? null)

const isExpired = computed(() => {
  if (!expiresAt.value) return false
  return new Date(expiresAt.value).getTime() < Date.now()
})

const statusLabel = computed(() => {
  if (isLinked.value) return 'Rattaché'
  if (!currentCode.value) return 'Sans code'
  if (isExpired.value) return 'Expiré'
  return 'Actif'
})

const statusBadgeClass = computed(() => {
  if (isLinked.value) return 'bg-green-100 text-green-800'
  if (!currentCode.value || isExpired.value) return 'bg-red-100 text-red-800'
  return 'bg-emerald-100 text-emerald-800'
})

const expiresAtLabel = computed(() => {
  if (!expiresAt.value) return null
  try {
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    }).format(new Date(expiresAt.value))
  } catch {
    return expiresAt.value
  }
})

const fetchCurrentCode = async () => {
  if (isLinked.value) return
  loading.value = true
  loadError.value = null
  try {
    const response = await $api.get<{
      success: boolean
      data?: {
        invite_code: string | null
        invite_code_expires_at: string | null
        is_expired: boolean
      }
      message?: string
    }>(`/club/students/${props.student.id}/invite-code`)

    if (response.data.success && response.data.data) {
      currentCode.value = response.data.data.invite_code
      expiresAt.value = response.data.data.invite_code_expires_at
      emit('updated', {
        invite_code: currentCode.value,
        invite_code_expires_at: expiresAt.value
      })
    } else {
      loadError.value = response.data.message || 'Impossible de récupérer le code.'
    }
  } catch (err: any) {
    loadError.value = err?.response?.data?.message || 'Erreur lors du chargement du code.'
  } finally {
    loading.value = false
  }
}

const regenerate = async () => {
  regenerating.value = true
  loadError.value = null
  try {
    const response = await $api.post<{
      success: boolean
      data?: {
        invite_code: string
        invite_code_expires_at: string | null
      }
      message?: string
    }>(`/club/students/${props.student.id}/invite-code/regenerate`)

    if (response.data.success && response.data.data) {
      currentCode.value = response.data.data.invite_code
      expiresAt.value = response.data.data.invite_code_expires_at
      emit('updated', {
        invite_code: currentCode.value,
        invite_code_expires_at: expiresAt.value
      })
    } else {
      loadError.value = response.data.message || 'Erreur lors de la régénération.'
    }
  } catch (err: any) {
    loadError.value = err?.response?.data?.message || 'Erreur lors de la régénération.'
  } finally {
    regenerating.value = false
  }
}

const copyCode = async () => {
  if (!currentCode.value) return
  try {
    await navigator.clipboard.writeText(currentCode.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  } catch {
    copied.value = false
  }
}

watch(
  () => props.student.id,
  () => {
    currentCode.value = props.student.invite_code ?? null
    expiresAt.value = props.student.invite_code_expires_at ?? null
    if (!isLinked.value && !currentCode.value) {
      fetchCurrentCode()
    }
  }
)

onMounted(() => {
  if (!isLinked.value && !currentCode.value) {
    fetchCurrentCode()
  }
})
</script>
