<template>
  <Teleport to="body">
    <!-- Fiche élève : panneau latéral sous l'en-tête du site (qui reste visible),
         ouvert par-dessus la page courante pour ne pas perdre le contexte (liste, planning). -->
    <div
      v-if="studentId"
      class="fixed inset-x-0 bottom-0 top-[84px] z-[55] flex justify-end"
      data-testid="student-panel"
    >
      <div class="absolute inset-0 bg-gray-900/20" aria-hidden="true" @click="close" />

      <aside
        ref="panelEl"
        class="relative h-full w-full max-w-4xl bg-gray-50 shadow-2xl flex flex-col border-l border-gray-200"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
      >
        <!-- En-tête : identité + onglets -->
        <header class="shrink-0 bg-white border-b border-gray-200 px-5 pt-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <h2 :id="titleId" class="text-lg font-semibold text-gray-900 truncate">
                {{ displayName }}
              </h2>
              <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-600">
                <span v-if="student?.email" class="truncate">{{ student.email }}</span>
                <span v-if="student?.phone">{{ student.phone }}</span>
                <span v-if="student?.age != null">{{ student.age }} ans</span>
                <span
                  v-if="student"
                  class="px-2 py-0.5 text-xs font-medium rounded-full"
                  :class="student.is_active === false ? 'bg-gray-100 text-gray-700' : 'bg-green-100 text-green-800'"
                >
                  {{ student.is_active === false ? 'Inactif' : 'Actif' }}
                </span>
                <span v-if="student?.is_blocked" class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700">
                  Compte bloqué
                </span>
              </div>
            </div>
            <button
              ref="closeButton"
              type="button"
              class="shrink-0 p-2 rounded-lg text-gray-500 hover:text-gray-800 hover:bg-gray-100"
              aria-label="Fermer la fiche élève"
              @click="close"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <nav class="mt-3 -mb-px flex gap-1 overflow-x-auto" role="tablist" aria-label="Sections de la fiche élève">
            <button
              v-for="t in tabs"
              :key="t.id"
              type="button"
              role="tab"
              :aria-selected="activeTab === t.id"
              class="px-3 py-2 text-sm font-medium border-b-2 whitespace-nowrap transition-colors"
              :class="activeTab === t.id
                ? 'border-emerald-600 text-emerald-700'
                : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'"
              @click="selectTab(t.id)"
            >
              {{ t.label }}
            </button>
          </nav>
        </header>

        <!-- Contenu : chaque onglet reste monté une fois visité (filtres, saisie conservés) -->
        <div class="flex-1 overflow-y-auto">
          <div v-if="loading && !student" class="flex justify-center py-16">
            <div class="animate-spin h-10 w-10 border-2 border-emerald-600 border-t-transparent rounded-full" />
          </div>

          <div v-else-if="error && !student" class="m-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            {{ error }}
          </div>

          <template v-else-if="student">
            <div v-if="visited.has('infos')" v-show="activeTab === 'infos'">
              <EditStudentModal :key="`edit-${formKey}`" :student="student" embedded @success="onEdited" />
            </div>
            <div v-if="visited.has('abonnements')" v-show="activeTab === 'abonnements'">
              <StudentSubscriptionsModal :student="student" embedded @success="emit('updated')" />
            </div>
            <div v-if="visited.has('planning')" v-show="activeTab === 'planning'">
              <PlanningParticipantInfoModal
                :show="true"
                type="student"
                :participant-id="Number(studentId)"
                :fallback-name="displayName"
                embedded
                @close="close"
              />
            </div>
            <div v-if="visited.has('historique')" v-show="activeTab === 'historique'">
              <StudentHistoryModal :student="student" embedded />
            </div>
          </template>
        </div>
      </aside>
    </div>
  </Teleport>
</template>

<script lang="ts">
export type StudentPanelTab = 'infos' | 'abonnements' | 'planning' | 'historique'
</script>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import EditStudentModal from '~/components/EditStudentModal.vue'
import StudentSubscriptionsModal from '~/components/StudentSubscriptionsModal.vue'
import StudentHistoryModal from '~/components/StudentHistoryModal.vue'
import PlanningParticipantInfoModal from '~/components/planning/PlanningParticipantInfoModal.vue'

const props = withDefaults(defineProps<{
  studentId: number | string | null
  /** Données déjà connues (ligne de liste) : affichage immédiat, puis rafraîchi. */
  initialStudent?: Record<string, any> | null
  tab?: StudentPanelTab
}>(), {
  initialStudent: null,
  tab: 'infos',
})

const emit = defineEmits<{
  close: []
  updated: []
  'update:tab': [tab: StudentPanelTab]
}>()

const tabs: { id: StudentPanelTab; label: string }[] = [
  { id: 'infos', label: 'Informations' },
  { id: 'abonnements', label: 'Abonnements' },
  { id: 'planning', label: 'Planning' },
  { id: 'historique', label: 'Historique' },
]

const titleId = `student-panel-title-${Math.random().toString(36).slice(2, 8)}`
const student = ref<Record<string, any> | null>(null)
const loading = ref(false)
const error = ref('')
const activeTab = ref<StudentPanelTab>(props.tab)
const visited = reactive(new Set<StudentPanelTab>([props.tab]))
const formKey = ref(0)
const closeButton = ref<HTMLButtonElement | null>(null)
const panelEl = ref<HTMLElement | null>(null)

const displayName = computed(() => studentDisplayName(student.value) || 'Élève')

function studentDisplayName(s: Record<string, any> | null): string {
  if (!s) return ''
  if (s.name) return s.name
  const user = `${s.first_name ?? ''} ${s.last_name ?? ''}`.trim()
  if (user) return user
  return `${s.student_first_name ?? ''} ${s.student_last_name ?? ''}`.trim()
}

function selectTab(id: StudentPanelTab) {
  activeTab.value = id
  visited.add(id)
  emit('update:tab', id)
}

function close() {
  emit('close')
}

async function loadStudent(id: number | string) {
  loading.value = true
  error.value = ''
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/students', {
      params: { student_id: id, status: 'all', per_page: 1, page: 1 },
    })
    // Ignore une réponse arrivée après un changement d'élève
    if (String(id) !== String(props.studentId)) return
    const found = Array.isArray(response.data?.data) ? response.data.data[0] : null
    if (found) {
      student.value = { ...found }
    } else if (!student.value) {
      error.value = 'Élève introuvable dans ce club.'
    }
  } catch (e: any) {
    if (String(id) !== String(props.studentId)) return
    error.value = e?.response?.data?.message || 'Impossible de charger la fiche élève.'
  } finally {
    if (String(id) === String(props.studentId)) loading.value = false
  }
}

async function onEdited() {
  emit('updated')
  if (props.studentId != null) await loadStudent(props.studentId)
  formKey.value++
}

watch(
  () => props.studentId,
  (id) => {
    student.value = props.initialStudent && String(props.initialStudent.id) === String(id)
      ? { ...props.initialStudent }
      : null
    // Nouvel élève : on repart de l'onglet demandé, sans monter les autres onglets.
    activeTab.value = props.tab
    visited.clear()
    visited.add(activeTab.value)
    if (id != null && id !== '') {
      void loadStudent(id)
      void nextTick(() => closeButton.value?.focus())
    }
  },
  { immediate: true },
)

watch(() => props.tab, (t) => {
  if (t && t !== activeTab.value) selectTab(t)
})

function onKeydown(e: KeyboardEvent) {
  // Échap ferme la fiche, sauf si une modale ouverte depuis la fiche (ex. assignation) est affichée
  if (e.key !== 'Escape' || !props.studentId) return
  if (panelEl.value?.querySelector('.fixed.inset-0')) return
  close()
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))
</script>
