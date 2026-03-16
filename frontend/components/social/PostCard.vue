<template>
  <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden flex flex-col">
    <div class="p-4 border-b border-gray-100 flex items-center justify-between">
      <span class="text-xs font-semibold px-2 py-1 rounded-full" :class="typeBadgeClass">
        {{ post.type }}
      </span>
      <span class="text-sm text-gray-500">{{ formatDate(post.scheduled_at) }}</span>
    </div>

    <div class="relative aspect-square bg-gray-100">
      <img
        v-if="displayImageUrl"
        :src="displayImageUrl"
        alt="Illustration du post"
        class="w-full h-full object-cover"
      />
      <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
      <div v-if="regenerating" class="absolute inset-0 bg-black/50 flex items-center justify-center">
        <div class="w-10 h-10 border-4 border-white border-t-transparent rounded-full animate-spin" />
      </div>
    </div>

    <div class="p-4 flex-1 flex flex-col gap-3">
      <textarea
        :value="localText"
        @input="onTextInput"
        rows="4"
        class="w-full text-sm text-gray-700 border border-gray-200 rounded-lg p-3 resize-y focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
        placeholder="Texte du post..."
      />

      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          @click="regenerateImage"
          :disabled="regenerating || saving"
          class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <span v-if="regenerating">Génération...</span>
          <span v-else>🔄 Régénérer image</span>
        </button>
        <button
          type="button"
          @click="validate"
          :disabled="saving"
          class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <span v-if="saving">Enregistrement...</span>
          <span v-else>Valider</span>
        </button>
      </div>

      <p v-if="post.status === 'validated'" class="text-xs text-green-600 font-medium">
        ✓ Prêt à publier
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  post: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['updated', 'regenerated'])

const { $api } = useNuxtApp()

const localText = ref(props.post.text)
const saving = ref(false)
const regenerating = ref(false)

watch(() => props.post.text, (v) => { localText.value = v })
watch(() => props.post.image_url, () => {})

const config = useRuntimeConfig()
const displayImageUrl = computed(() => {
  const url = props.post.image_url
  if (!url) return null
  if (url.startsWith('http')) return url
  const base = (config.public?.apiBase || '').replace(/\/api\/?$/, '')
  return base ? `${base}${url.startsWith('/') ? url : '/' + url}` : url
})

const typeBadgeClass = computed(() => {
  const m = {
    'Conseil': 'bg-blue-100 text-blue-700',
    'Promo': 'bg-amber-100 text-amber-700',
    'Fun Fact': 'bg-purple-100 text-purple-700'
  }
  return m[props.post.type] || 'bg-gray-100 text-gray-700'
})

function formatDate (dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}

function onTextInput (e) {
  localText.value = e.target.value
}

async function validate () {
  saving.value = true
  try {
    const res = await $api.put(`/club/social-generator/posts/${props.post.id}`, {
      text: localText.value,
      status: 'validated'
    })
    if (res.data?.success && res.data?.data) {
      emit('updated', res.data.data)
    }
  } catch (err) {
    console.error('Erreur mise à jour post:', err)
  } finally {
    saving.value = false
  }
}

async function regenerateImage () {
  regenerating.value = true
  try {
    const res = await $api.post(`/club/social-generator/posts/${props.post.id}/regenerate-image`)
    if (res.data?.success && res.data?.data) {
      emit('regenerated', res.data.data)
    }
  } catch (err) {
    console.error('Erreur régénération image:', err)
  } finally {
    regenerating.value = false
  }
}
</script>
