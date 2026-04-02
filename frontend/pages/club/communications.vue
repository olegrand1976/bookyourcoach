<template>
  <div class="min-h-screen bg-gray-50 p-4 md:p-8">
    <div class="max-w-6xl mx-auto space-y-8">
      <div class="flex flex-wrap items-center gap-3">
        <NuxtLink
          to="/club/dashboard"
          class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800"
        >
          ← Tableau de bord
        </NuxtLink>
      </div>

      <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-lg p-4 md:p-6 border-2 border-blue-100">
        <h1 class="text-xl md:text-3xl font-bold text-gray-900">Communications générales</h1>
        <p class="mt-2 text-sm md:text-base text-gray-600">
          Envoyez un email aux enseignants et/ou aux élèves : à tout le groupe actif ou une sélection précise. Texte brut (HTML retiré). Réponse possible vers l’email du club si renseigné.
        </p>
      </div>

      <!-- Bloc envoi -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h2 class="text-lg font-semibold text-gray-900">Envoi d’emails</h2>
          <p v-if="!loadingCounts" class="mt-1 text-sm text-gray-600">
            Disponibles avec email valide :
            <strong>{{ counts.teachers_with_email }}</strong> enseignant(s),
            <strong>{{ counts.students_with_email }}</strong> élève(s).
            <span v-if="counts.unique_total_for_both !== counts.teachers_with_email + counts.students_with_email" class="text-amber-700">
              ({{ counts.unique_total_for_both }} envois uniques si « tous ».)
            </span>
          </p>
        </div>

        <div v-if="loadingCounts || loadingContacts" class="p-8 text-center text-gray-600">
          Chargement…
        </div>

        <form v-else class="p-4 md:p-6 space-y-6" @submit.prevent="onSubmit">
          <fieldset>
            <legend class="text-sm font-medium text-gray-700 mb-3 block">Mode d’envoi</legend>
            <div class="space-y-3">
              <label class="flex items-start gap-3 min-h-[44px] cursor-pointer">
                <input
                  v-model="form.selection_mode"
                  type="radio"
                  value="all"
                  class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                >
                <span>
                  <span class="font-medium">Tous les destinataires actifs</span>
                  <span class="block text-sm text-gray-600">Choisir un groupe ci-dessous.</span>
                </span>
              </label>
              <label class="flex items-start gap-3 min-h-[44px] cursor-pointer">
                <input
                  v-model="form.selection_mode"
                  type="radio"
                  value="selected"
                  class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                >
                <span>
                  <span class="font-medium">Sélection personnalisée</span>
                  <span class="block text-sm text-gray-600">Cocher des enseignants et/ou des élèves listés plus bas.</span>
                </span>
              </label>
            </div>
          </fieldset>

          <fieldset v-if="form.selection_mode === 'all'">
            <legend class="text-sm font-medium text-gray-700 mb-3 block">Groupe</legend>
            <div class="space-y-3">
              <label class="flex items-center gap-3 min-h-[44px] cursor-pointer">
                <input v-model="form.audience" type="radio" value="teachers" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                <span>Enseignants actifs uniquement</span>
              </label>
              <label class="flex items-center gap-3 min-h-[44px] cursor-pointer">
                <input v-model="form.audience" type="radio" value="students" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                <span>Élèves actifs uniquement</span>
              </label>
              <label class="flex items-center gap-3 min-h-[44px] cursor-pointer">
                <input v-model="form.audience" type="radio" value="both" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                <span>Enseignants et élèves</span>
              </label>
            </div>
          </fieldset>

          <div v-if="form.selection_mode === 'selected'" class="space-y-6 border border-gray-200 rounded-xl p-4 bg-slate-50/50">
            <div>
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                <h3 class="text-sm font-semibold text-gray-900">Enseignants</h3>
                <div class="flex flex-wrap gap-2">
                  <button type="button" class="text-xs px-2 py-1 rounded bg-white border border-gray-300 hover:bg-gray-50" @click="selectAllTeachers">
                    Tout sélectionner
                  </button>
                  <button type="button" class="text-xs px-2 py-1 rounded bg-white border border-gray-300 hover:bg-gray-50" @click="clearTeachers">
                    Effacer
                  </button>
                </div>
              </div>
              <input
                v-model="filterTeacher"
                type="search"
                placeholder="Filtrer par nom ou email…"
                class="w-full mb-2 px-3 py-2 text-sm border border-gray-300 rounded-lg"
              >
              <div class="max-h-48 overflow-y-auto rounded-lg border border-gray-200 bg-white divide-y divide-gray-100">
                <label
                  v-for="t in filteredTeachers"
                  :key="'t-'+t.id"
                  class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 cursor-pointer text-sm"
                >
                  <input v-model="selectedTeacherIds" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" :value="t.id">
                  <span class="flex-1 min-w-0">
                    <span class="font-medium text-gray-900">{{ t.name }}</span>
                    <span class="block text-xs text-gray-500 truncate">{{ t.email }}</span>
                  </span>
                </label>
                <p v-if="filteredTeachers.length === 0" class="px-3 py-4 text-sm text-gray-500 text-center">Aucun enseignant avec email.</p>
              </div>
            </div>

            <div>
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                <h3 class="text-sm font-semibold text-gray-900">Élèves</h3>
                <div class="flex flex-wrap gap-2">
                  <button type="button" class="text-xs px-2 py-1 rounded bg-white border border-gray-300 hover:bg-gray-50" @click="selectAllStudents">
                    Tout sélectionner
                  </button>
                  <button type="button" class="text-xs px-2 py-1 rounded bg-white border border-gray-300 hover:bg-gray-50" @click="clearStudents">
                    Effacer
                  </button>
                </div>
              </div>
              <input
                v-model="filterStudent"
                type="search"
                placeholder="Filtrer par nom ou email…"
                class="w-full mb-2 px-3 py-2 text-sm border border-gray-300 rounded-lg"
              >
              <div class="max-h-48 overflow-y-auto rounded-lg border border-gray-200 bg-white divide-y divide-gray-100">
                <label
                  v-for="s in filteredStudents"
                  :key="'s-'+s.id"
                  class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 cursor-pointer text-sm"
                >
                  <input v-model="selectedStudentIds" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" :value="s.id">
                  <span class="flex-1 min-w-0">
                    <span class="font-medium text-gray-900">{{ s.name }}</span>
                    <span class="block text-xs text-gray-500 truncate">{{ s.email }}</span>
                  </span>
                </label>
                <p v-if="filteredStudents.length === 0" class="px-3 py-4 text-sm text-gray-500 text-center">Aucun élève avec email.</p>
              </div>
            </div>
          </div>

          <div>
            <label for="comm-subject" class="block text-sm font-medium text-gray-700 mb-2">Objet *</label>
            <input
              id="comm-subject"
              v-model="form.subject"
              type="text"
              maxlength="200"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
          </div>

          <div>
            <label for="comm-body" class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
            <textarea
              id="comm-body"
              v-model="form.body"
              required
              rows="10"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-sans text-sm"
            />
            <p class="mt-1 text-xs text-gray-500">HTML supprimé côté serveur.</p>
          </div>

          <div v-if="errorMessage" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            {{ errorMessage }}
          </div>

          <button
            type="submit"
            :disabled="sending"
            class="min-h-[48px] w-full sm:w-auto inline-flex items-center justify-center px-8 py-3 rounded-lg font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50"
          >
            {{ sending ? 'Envoi…' : 'Envoyer' }}
          </button>
        </form>
      </div>

      <!-- Historique enseignants -->
      <section class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 bg-indigo-50">
          <h2 class="text-lg font-semibold text-gray-900">Historique — envois vers les enseignants</h2>
          <p class="text-sm text-gray-600 mt-1">Campagnes ayant ciblé au moins un enseignant.</p>
        </div>
        <div class="p-4 md:p-6">
          <CommunicationHistoryTable
            :rows="historyTeachers.items"
            :loading="historyTeachers.loading"
            empty-text="Aucun envoi enregistré."
          />
          <button
            v-if="historyTeachers.pagination && historyTeachers.pagination.current_page < historyTeachers.pagination.last_page"
            type="button"
            class="mt-4 text-sm font-medium text-blue-600 hover:text-blue-800"
            @click="loadMoreTeachers"
          >
            Charger la suite
          </button>
        </div>
      </section>

      <!-- Historique élèves -->
      <section class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 bg-emerald-50">
          <h2 class="text-lg font-semibold text-gray-900">Historique — envois vers les élèves</h2>
          <p class="text-sm text-gray-600 mt-1">Campagnes ayant ciblé au moins un élève.</p>
        </div>
        <div class="p-4 md:p-6">
          <CommunicationHistoryTable
            :rows="historyStudents.items"
            :loading="historyStudents.loading"
            empty-text="Aucun envoi enregistré."
          />
          <button
            v-if="historyStudents.pagination && historyStudents.pagination.current_page < historyStudents.pagination.last_page"
            type="button"
            class="mt-4 text-sm font-medium text-blue-600 hover:text-blue-800"
            @click="loadMoreStudents"
          >
            Charger la suite
          </button>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from '~/composables/useToast'
import CommunicationHistoryTable from '~/components/club/CommunicationHistoryTable.vue'

definePageMeta({
  middleware: ['auth']
})

const { $api } = useNuxtApp()
const { success: toastSuccess, error: toastError } = useToast()

type Contact = { id: number; name: string; email: string }
type HistoryRow = {
  id: number
  subject: string
  body_preview: string
  created_at: string | null
  sent_by: { name?: string; email?: string }
  recipient_count: number
  sent_count: number
  failed_count: number
  teacher_recipient_count: number | null
  student_recipient_count: number | null
  audience: string
  selection_mode: string
}

const loadingCounts = ref(true)
const loadingContacts = ref(true)
const sending = ref(false)
const errorMessage = ref('')

const counts = ref({
  teachers_with_email: 0,
  students_with_email: 0,
  unique_total_for_both: 0
})

const teacherContacts = ref<Contact[]>([])
const studentContacts = ref<Contact[]>([])
const filterTeacher = ref('')
const filterStudent = ref('')
const selectedTeacherIds = ref<number[]>([])
const selectedStudentIds = ref<number[]>([])

const form = ref({
  selection_mode: 'all' as 'all' | 'selected',
  audience: 'both' as 'teachers' | 'students' | 'both',
  subject: '',
  body: ''
})

const historyTeachers = ref<{
  items: HistoryRow[]
  pagination: { current_page: number; last_page: number; per_page: number; total: number } | null
  loading: boolean
}>({ items: [], pagination: null, loading: true })

const historyStudents = ref<{
  items: HistoryRow[]
  pagination: { current_page: number; last_page: number; per_page: number; total: number } | null
  loading: boolean
}>({ items: [], pagination: null, loading: true })

const filteredTeachers = computed(() => {
  const q = filterTeacher.value.trim().toLowerCase()
  if (!q) {
    return teacherContacts.value
  }
  return teacherContacts.value.filter(
    t => t.name.toLowerCase().includes(q) || t.email.toLowerCase().includes(q)
  )
})

const filteredStudents = computed(() => {
  const q = filterStudent.value.trim().toLowerCase()
  if (!q) {
    return studentContacts.value
  }
  return studentContacts.value.filter(
    s => s.name.toLowerCase().includes(q) || s.email.toLowerCase().includes(q)
  )
})

function estimatedUniqueEmails(): number {
  const emails = new Set<string>()
  if (form.value.selection_mode === 'all') {
    if (form.value.audience === 'teachers' || form.value.audience === 'both') {
      teacherContacts.value.forEach(t => emails.add(t.email))
    }
    if (form.value.audience === 'students' || form.value.audience === 'both') {
      studentContacts.value.forEach(s => emails.add(s.email))
    }
    return emails.size
  }
  selectedTeacherIds.value.forEach(id => {
    const t = teacherContacts.value.find(x => x.id === id)
    if (t) {
      emails.add(t.email)
    }
  })
  selectedStudentIds.value.forEach(id => {
    const s = studentContacts.value.find(x => x.id === id)
    if (s) {
      emails.add(s.email)
    }
  })
  return emails.size
}

function selectAllTeachers() {
  selectedTeacherIds.value = filteredTeachers.value.map(t => t.id)
}
function clearTeachers() {
  selectedTeacherIds.value = []
}
function selectAllStudents() {
  selectedStudentIds.value = filteredStudents.value.map(s => s.id)
}
function clearStudents() {
  selectedStudentIds.value = []
}

async function fetchHistory(scope: 'teachers' | 'students', page: number, append: boolean) {
  const state = scope === 'teachers' ? historyTeachers : historyStudents
  state.value.loading = true
  try {
    const res = await $api.get('/club/communications/history', {
      params: { scope, page, per_page: 15 }
    })
    if (res.data?.success && res.data?.data) {
      const { items, pagination } = res.data.data
      if (append) {
        state.value.items = [...state.value.items, ...items]
      } else {
        state.value.items = items
      }
      state.value.pagination = pagination
    }
  } catch {
    toastError('Impossible de charger l’historique.')
  } finally {
    state.value.loading = false
  }
}

function loadMoreTeachers() {
  const p = historyTeachers.value.pagination
  if (!p || p.current_page >= p.last_page) {
    return
  }
  fetchHistory('teachers', p.current_page + 1, true)
}

function loadMoreStudents() {
  const p = historyStudents.value.pagination
  if (!p || p.current_page >= p.last_page) {
    return
  }
  fetchHistory('students', p.current_page + 1, true)
}

onMounted(async () => {
  try {
    const [cRes, ctRes] = await Promise.all([
      $api.get('/club/communications/recipient-counts'),
      $api.get('/club/communications/contacts')
    ])
    if (cRes.data?.success && cRes.data?.data) {
      counts.value = { ...counts.value, ...cRes.data.data }
    }
    if (ctRes.data?.success && ctRes.data?.data) {
      teacherContacts.value = ctRes.data.data.teachers || []
      studentContacts.value = ctRes.data.data.students || []
    }
  } catch {
    toastError('Impossible de charger les destinataires.')
  } finally {
    loadingCounts.value = false
    loadingContacts.value = false
  }

  await Promise.all([fetchHistory('teachers', 1, false), fetchHistory('students', 1, false)])
})

async function onSubmit() {
  errorMessage.value = ''

  if (form.value.selection_mode === 'selected') {
    if (selectedTeacherIds.value.length === 0 && selectedStudentIds.value.length === 0) {
      errorMessage.value = 'Cochez au moins un enseignant ou un élève.'
      return
    }
  } else {
    const n = estimatedUniqueEmails()
    if (n === 0) {
      errorMessage.value = 'Aucun destinataire avec email pour ce groupe.'
      return
    }
  }

  const n = estimatedUniqueEmails()
  if (form.value.selection_mode === 'selected' && n === 0) {
    errorMessage.value = 'Les personnes sélectionnées n’ont pas d’email exploitable.'
    return
  }

  const msg =
    form.value.selection_mode === 'all'
      ? `Confirmer l’envoi à environ ${n} adresse(s) email distincte(s) ?`
      : `Confirmer l’envoi à environ ${n} adresse(s) distincte(s) (${selectedTeacherIds.value.length} enseignant(s) coché(s), ${selectedStudentIds.value.length} élève(s) coché(s)) ?`

  if (!confirm(msg)) {
    return
  }

  const payload: Record<string, unknown> = {
    selection_mode: form.value.selection_mode,
    subject: form.value.subject,
    body: form.value.body
  }
  if (form.value.selection_mode === 'all') {
    payload.audience = form.value.audience
  } else {
    payload.teacher_ids = [...selectedTeacherIds.value]
    payload.student_ids = [...selectedStudentIds.value]
  }

  sending.value = true
  try {
    const res = await $api.post('/club/communications/send', payload)
    if (res.data?.success) {
      toastSuccess(res.data.message || 'Envoyé.')
      form.value.subject = ''
      form.value.body = ''
      await Promise.all([fetchHistory('teachers', 1, false), fetchHistory('students', 1, false)])
    } else {
      errorMessage.value = res.data?.message || 'Erreur.'
    }
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || err.message || 'Erreur réseau.'
    toastError(errorMessage.value)
  } finally {
    sending.value = false
  }
}
</script>
