<template>
  <div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Double authentification</h2>

    <div v-if="loading" class="text-sm text-gray-500">Chargement…</div>

    <template v-else-if="status">
      <p class="text-sm text-gray-600 mb-4">
        <template v-if="status.enabled">
          Activée<span v-if="status.confirmed_at"> depuis le {{ formatDate(status.confirmed_at) }}</span>.
          Obligatoire pour ce compte : elle ne peut pas être désactivée.
        </template>
        <template v-else>Non configurée : elle vous sera demandée à la prochaine connexion.</template>
      </p>

      <div v-if="status.enabled" class="space-y-6">
        <!-- Codes de récupération -->
        <div
          class="text-sm rounded-lg p-3"
          :class="status.recovery_codes_remaining <= 2 ? 'bg-amber-50 text-amber-800' : 'bg-gray-50 text-gray-700'"
          data-testid="recovery-remaining"
        >
          {{ status.recovery_codes_remaining }} code{{ status.recovery_codes_remaining > 1 ? 's' : '' }} de récupération disponible{{ status.recovery_codes_remaining > 1 ? 's' : '' }}.
          <span v-if="status.recovery_codes_remaining <= 2">Pensez à en générer de nouveaux.</span>
        </div>

        <div v-if="newCodes.length" class="space-y-3 max-w-md">
          <!-- Nouveaux codes affichés une seule fois -->
          <RecoveryCodes :codes="newCodes" />
          <button type="button" class="btn-primary px-4 py-2 rounded-lg" @click="closeAction">Terminé</button>
        </div>

        <form v-else-if="enrolment" class="space-y-3 max-w-md" @submit.prevent="confirmPhone">
          <!-- Changement de téléphone : QR du nouveau secret -->
          <p class="text-sm text-gray-700">Scannez ce QR code avec votre nouvelle application, puis saisissez le code affiché.</p>
          <!-- SVG généré par notre API à partir de l'URI otpauth:// -->
          <div class="flex justify-center" v-html="enrolment.qr_svg"></div>
          <p class="text-xs font-mono break-all bg-gray-50 border border-gray-200 rounded p-2 select-all">{{ enrolment.secret }}</p>
          <input
            id="two-factor-new-code"
            v-model="newCode"
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            maxlength="7"
            required
            placeholder="Code à 6 chiffres"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg font-mono text-center"
          />
          <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
          <div class="flex gap-3">
            <button type="submit" :disabled="saving" class="btn-primary px-4 py-2 rounded-lg disabled:opacity-50">Enregistrer ce téléphone</button>
            <button type="button" class="px-4 py-2 text-gray-600" @click="closeAction">Annuler</button>
          </div>
        </form>

        <form v-else-if="action" class="space-y-3 max-w-md" @submit.prevent="verifyAction">
          <!-- Action sensible : mot de passe + code courant -->
          <p class="text-sm text-gray-700">
            {{ action === 'codes'
              ? 'Les codes actuels cesseront de fonctionner.'
              : 'Votre application actuelle restera valable jusqu\'à la confirmation du nouveau téléphone.' }}
            Confirmez avec votre mot de passe et le code de votre application actuelle.
          </p>
          <input
            id="two-factor-password"
            v-model="password"
            type="password"
            autocomplete="current-password"
            required
            placeholder="Mot de passe"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg"
          />
          <input
            id="two-factor-current-code"
            v-model="currentCode"
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            maxlength="7"
            required
            placeholder="Code à 6 chiffres"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg font-mono text-center"
          />
          <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
          <div class="flex gap-3">
            <button type="submit" :disabled="saving" class="btn-primary px-4 py-2 rounded-lg disabled:opacity-50">Confirmer</button>
            <button type="button" class="px-4 py-2 text-gray-600" @click="closeAction">Annuler</button>
          </div>
        </form>

        <div v-else class="flex flex-wrap gap-3">
          <button type="button" class="px-4 py-2 border-2 border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50" @click="openAction('codes')">
            Générer de nouveaux codes de récupération
          </button>
          <button type="button" class="px-4 py-2 border-2 border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50" @click="openAction('phone')">
            Changer de téléphone
          </button>
        </div>

        <!-- Appareils de confiance -->
        <div>
          <h3 class="text-sm font-semibold text-gray-900 mb-2">Appareils de confiance</h3>
          <p v-if="!status.trusted_devices.length" class="text-sm text-gray-500">
            Aucun : le code est demandé à chaque connexion.
          </p>
          <ul v-else class="divide-y divide-gray-100 border border-gray-200 rounded-lg" data-testid="trusted-devices">
            <li v-for="device in status.trusted_devices" :key="device.id" class="flex items-center justify-between gap-3 p-3 text-sm">
              <div class="min-w-0">
                <p class="text-gray-900 truncate" :title="device.user_agent">{{ describeDevice(device.user_agent) }}</p>
                <p class="text-gray-500">
                  Utilisé le {{ formatDate(device.last_used_at) }} · expire le {{ formatDate(device.expires_at) }}
                </p>
              </div>
              <button type="button" class="text-red-600 hover:text-red-700 shrink-0" @click="revokeDevice(device.id)">Révoquer</button>
            </li>
          </ul>
          <button
            v-if="status.trusted_devices.length > 1"
            type="button"
            class="mt-2 text-sm text-red-600 hover:text-red-700"
            @click="revokeAllDevices"
          >
            Révoquer tous les appareils
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from '~/composables/useToast'
import RecoveryCodes from '~/components/auth/RecoveryCodes.vue'

interface TrustedDevice {
  id: number
  user_agent: string | null
  last_used_at: string | null
  expires_at: string | null
}

interface Status {
  enabled: boolean
  confirmed_at: string | null
  recovery_codes_remaining: number
  trusted_devices: TrustedDevice[]
}

const toast = useToast()
const { $api } = useNuxtApp()

const loading = ref(true)
const saving = ref(false)
const status = ref<Status | null>(null)
const error = ref('')

const action = ref<'codes' | 'phone' | null>(null)
const password = ref('')
const currentCode = ref('')
const enrolment = ref<{ qr_svg: string, secret: string } | null>(null)
const newCode = ref('')
const newCodes = ref<string[]>([])

const load = async () => {
  try {
    const response = await $api.get('/auth/two-factor')
    status.value = response.data.data
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Impossible de charger la double authentification')
  } finally {
    loading.value = false
  }
}

const openAction = (kind: 'codes' | 'phone') => {
  closeAction()
  action.value = kind
}

const closeAction = () => {
  action.value = null
  password.value = ''
  currentCode.value = ''
  enrolment.value = null
  newCode.value = ''
  newCodes.value = []
  error.value = ''
}

const errorMessage = (err: any, fallback: string) => {
  const errors = err.response?.data?.errors
  if (errors) {
    return Object.values(errors).flat()[0] as string
  }
  if (err.response?.status === 429) {
    return 'Trop de tentatives. Patientez quelques minutes.'
  }
  return err.response?.data?.message || fallback
}

const verifyAction = async () => {
  saving.value = true
  error.value = ''
  const body = { password: password.value, code: currentCode.value.replace(/\s/g, '') }
  try {
    if (action.value === 'codes') {
      const response = await $api.post('/auth/two-factor/recovery-codes', body)
      newCodes.value = response.data.data.recovery_codes
      await load()
    } else {
      const response = await $api.post('/auth/two-factor/reconfigure', body)
      enrolment.value = response.data.data
    }
    password.value = ''
    currentCode.value = ''
  } catch (err: any) {
    error.value = errorMessage(err, 'Vérification impossible')
  } finally {
    saving.value = false
  }
}

const confirmPhone = async () => {
  saving.value = true
  error.value = ''
  try {
    const response = await $api.post('/auth/two-factor/reconfigure/confirm', { code: newCode.value.replace(/\s/g, '') })
    enrolment.value = null
    newCodes.value = response.data.data.recovery_codes
    toast.success('Nouveau téléphone enregistré')
    await load()
  } catch (err: any) {
    error.value = errorMessage(err, 'Code invalide')
  } finally {
    saving.value = false
  }
}

const revokeDevice = async (id: number) => {
  try {
    await $api.delete(`/auth/two-factor/trusted-devices/${id}`)
    await load()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Révocation impossible')
  }
}

const revokeAllDevices = async () => {
  try {
    await $api.delete('/auth/two-factor/trusted-devices')
    await load()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Révocation impossible')
  }
}

const formatDate = (value: string | null) =>
  value ? new Date(value).toLocaleDateString('fr-BE', { day: 'numeric', month: 'long', year: 'numeric' }) : '—'

// Résumé lisible du navigateur : de quoi reconnaître l'appareil sans afficher la chaîne brute.
const describeDevice = (agent: string | null) => {
  if (!agent) return 'Appareil inconnu'
  const browser = /Edg\//.test(agent) ? 'Edge' : /Firefox\//.test(agent) ? 'Firefox' : /Chrome\//.test(agent) ? 'Chrome' : /Safari\//.test(agent) ? 'Safari' : 'Navigateur'
  const platform = /iPhone|iPad/.test(agent) ? 'iOS' : /Android/.test(agent) ? 'Android' : /Windows/.test(agent) ? 'Windows' : /Mac OS X/.test(agent) ? 'macOS' : /Linux/.test(agent) ? 'Linux' : ''
  return platform ? `${browser} · ${platform}` : browser
}

onMounted(load)
</script>
