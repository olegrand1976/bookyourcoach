<template>
  <div class="space-y-5">
    <!-- En-tête -->
    <div class="text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl mb-4">
        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
      </div>
      <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ title }}</h2>
      <p class="text-gray-600 text-sm">{{ subtitle }}</p>
    </div>

    <!-- Enrôlement, étape 3 : codes de récupération -->
    <div v-if="recoveryCodes.length" class="space-y-4">
      <div class="bg-amber-50 border-l-4 border-amber-400 rounded-lg p-4 text-sm text-amber-800">
        Conservez ces codes en lieu sûr (gestionnaire de mots de passe, papier). Chacun permet
        <strong>une seule</strong> connexion si vous perdez votre téléphone. Ils ne seront plus affichés.
      </div>
      <ul class="grid grid-cols-2 gap-2 font-mono text-sm bg-gray-50 border border-gray-200 rounded-lg p-4" data-testid="recovery-codes">
        <li v-for="c in recoveryCodes" :key="c" class="text-center text-gray-900">{{ c }}</li>
      </ul>
      <div class="flex gap-3">
        <button type="button" class="flex-1 px-4 py-2 border-2 border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50" @click="copyCodes">
          {{ copied ? 'Copié ✓' : 'Copier' }}
        </button>
        <button type="button" class="flex-1 px-4 py-2 border-2 border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50" @click="downloadCodes">
          Télécharger (.txt)
        </button>
      </div>
      <label class="flex items-center cursor-pointer">
        <input v-model="codesSaved" type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded" />
        <span class="ml-2 text-sm text-gray-700">J'ai sauvegardé mes codes de récupération</span>
      </label>
      <button type="button" :disabled="!codesSaved" :class="primaryButton" @click="finishSetup">
        Continuer
      </button>
    </div>

    <form v-else class="space-y-5" @submit.prevent="submit">
      <!-- Enrôlement, étape 1 : QR code -->
      <div v-if="mode === 'setup'" class="space-y-3">
        <ol class="text-sm text-gray-700 list-decimal list-inside space-y-1">
          <li>Installez une application d'authentification (Google Authenticator, Microsoft Authenticator, 1Password…).</li>
          <li>Scannez ce QR code avec l'application.</li>
          <li>Saisissez le code à 6 chiffres qu'elle affiche.</li>
        </ol>
        <div v-if="setupLoading" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
        </div>
        <template v-else-if="qrSvg">
          <!-- SVG généré par notre API à partir de l'URI otpauth:// -->
          <div class="flex justify-center" data-testid="two-factor-qr" v-html="qrSvg"></div>
          <details class="text-sm text-gray-600">
            <summary class="cursor-pointer text-blue-600">Impossible de scanner ? Saisir la clé manuellement</summary>
            <p class="mt-2 font-mono break-all bg-gray-50 border border-gray-200 rounded p-2 select-all">{{ formattedSecret }}</p>
          </details>
        </template>
      </div>

      <!-- Code TOTP ou code de récupération -->
      <div v-if="!useRecovery">
        <label for="two-factor-code" class="block text-sm font-semibold text-gray-700 mb-2">Code à 6 chiffres</label>
        <input
          id="two-factor-code"
          v-model="code"
          type="text"
          inputmode="numeric"
          autocomplete="one-time-code"
          pattern="[0-9 ]*"
          maxlength="7"
          required
          autofocus
          class="block w-full px-3 py-3 border border-gray-300 rounded-lg text-center text-2xl tracking-[0.5em] font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="000000"
        />
      </div>
      <div v-else>
        <label for="two-factor-recovery" class="block text-sm font-semibold text-gray-700 mb-2">Code de récupération</label>
        <input
          id="two-factor-recovery"
          v-model="recoveryCode"
          type="text"
          autocomplete="off"
          required
          class="block w-full px-3 py-3 border border-gray-300 rounded-lg text-center font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="xxxxx-xxxxx"
        />
      </div>

      <div v-if="mode === 'challenge'" class="flex items-center justify-between">
        <label class="flex items-center cursor-pointer">
          <input v-model="rememberDevice" type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded" />
          <span class="ml-2 text-sm text-gray-700">Faire confiance à cet appareil 30 jours</span>
        </label>
        <button type="button" class="text-sm font-medium text-blue-600 hover:text-blue-700" @click="toggleRecovery">
          {{ useRecovery ? 'Utiliser l\'application' : 'Code de récupération' }}
        </button>
      </div>

      <div v-if="error" class="bg-red-50 border-l-4 border-red-400 rounded-lg p-4 text-sm text-red-700" role="alert">
        {{ error }}
      </div>

      <button type="submit" :disabled="loading || setupLoading" :class="primaryButton">
        <span v-if="loading">Vérification…</span>
        <span v-else>{{ mode === 'setup' ? 'Activer la double authentification' : 'Vérifier' }}</span>
      </button>

      <button type="button" class="w-full text-sm text-gray-600 hover:text-gray-900" @click="cancel()">
        ← Revenir à la connexion
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '~/stores/auth'

const emit = defineEmits<{
  (e: 'done'): void
  (e: 'cancel', message?: string): void
}>()

const authStore = useAuthStore()
const mode = computed(() => authStore.twoFactor.mode)

const code = ref('')
const recoveryCode = ref('')
const useRecovery = ref(false)
const rememberDevice = ref(false)
const loading = ref(false)
const error = ref('')

const setupLoading = ref(false)
const qrSvg = ref('')
const secret = ref('')
const recoveryCodes = ref<string[]>([])
const codesSaved = ref(false)
const copied = ref(false)

const primaryButton = 'w-full flex items-center justify-center py-3 px-4 rounded-lg text-white font-semibold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg'

const title = computed(() => {
  if (recoveryCodes.value.length) return 'Codes de récupération'
  return mode.value === 'setup' ? 'Activez la double authentification' : 'Vérification en deux étapes'
})

const subtitle = computed(() => {
  if (recoveryCodes.value.length) return 'Dernière étape avant d\'accéder à votre espace.'
  return mode.value === 'setup'
    ? 'Obligatoire pour les comptes club et administrateur : elle protège les données de vos membres.'
    : 'Saisissez le code affiché par votre application d\'authentification.'
})

// Clé lisible par groupes de 4 pour la saisie manuelle.
const formattedSecret = computed(() => secret.value.replace(/(.{4})/g, '$1 ').trim())

const cancel = (message?: string) => {
  authStore.resetTwoFactor()
  emit('cancel', message)
}

const handleError = (err: any) => {
  const status = err?.response?.status
  const message = err?.response?.data?.message
  if (status === 401) {
    // Challenge expiré ou détruit après trop d'essais : repartir du mot de passe.
    cancel(message || 'Session de vérification expirée : reconnectez-vous.')
    return
  }
  if (status === 429) {
    error.value = 'Trop de tentatives. Patientez quelques minutes avant de réessayer.'
    return
  }
  const remaining = err?.response?.data?.data?.remaining_attempts
  error.value = (message || 'Code invalide.') + (remaining ? ` (${remaining} essai${remaining > 1 ? 's' : ''} restant${remaining > 1 ? 's' : ''})` : '')
}

const loadSetup = async () => {
  setupLoading.value = true
  error.value = ''
  try {
    const data = await authStore.setupTwoFactor()
    qrSvg.value = data.qr_svg
    secret.value = data.secret
  } catch (err) {
    handleError(err)
  } finally {
    setupLoading.value = false
  }
}

const toggleRecovery = () => {
  useRecovery.value = !useRecovery.value
  error.value = ''
}

const submit = async () => {
  loading.value = true
  error.value = ''
  try {
    if (mode.value === 'setup') {
      recoveryCodes.value = await authStore.confirmTwoFactorSetup(code.value.replace(/\s/g, ''))
    } else {
      const data = await authStore.verifyTwoFactor(useRecovery.value
        ? { recovery_code: recoveryCode.value, remember_device: rememberDevice.value }
        : { code: code.value.replace(/\s/g, ''), remember_device: rememberDevice.value })
      if (typeof data.remaining_recovery_codes === 'number') {
        // Un code de secours vient de servir : prévenir avant qu'il n'en reste plus.
        alert(`Code de récupération utilisé. Il vous en reste ${data.remaining_recovery_codes}. Pensez à en générer de nouveaux depuis votre profil.`)
      }
      emit('done')
    }
  } catch (err) {
    handleError(err)
    code.value = ''
  } finally {
    loading.value = false
  }
}

const codesAsText = () => recoveryCodes.value.join('\n')

const copyCodes = async () => {
  try {
    await navigator.clipboard.writeText(codesAsText())
    copied.value = true
  } catch {
    copied.value = false
  }
}

const downloadCodes = () => {
  const blob = new Blob([`Codes de récupération activibe\n\n${codesAsText()}\n`], { type: 'text/plain' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'activibe-codes-recuperation.txt'
  link.click()
  URL.revokeObjectURL(url)
}

const finishSetup = () => {
  authStore.finishTwoFactorSetup()
  emit('done')
}

onMounted(() => {
  if (mode.value === 'setup') {
    loadSetup()
  }
})
</script>
