<template>
  <Teleport to="body">
    <!-- Fiche élève : modale centrée sous l'en-tête du site, par-dessus la page courante
         (liste, planning). Le scroll de la page est bloqué tant qu'elle est ouverte.
         Sur grand écran, « Vue scindée » affiche 2 onglets côte à côte, chacun scrollable. -->
    <div
      v-if="studentId"
      class="fixed inset-x-0 bottom-0 top-[84px] z-[55] flex items-center justify-center p-2 sm:p-4"
      data-testid="student-panel"
    >
      <div class="absolute inset-0 bg-gray-900/40" aria-hidden="true" @click="close" />

      <section
        ref="panelEl"
        class="relative h-full w-full bg-gray-50 shadow-2xl flex flex-col rounded-xl overflow-hidden border border-gray-200 transition-[max-width] duration-200"
        :class="isSplit ? 'max-w-[1600px]' : 'max-w-4xl'"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
      >
        <!-- En-tête : identité + actions -->
        <header class="shrink-0 bg-white border-b border-gray-200 px-5 py-3">
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
            <div class="shrink-0 flex items-center gap-1">
              <button
                v-if="isWide"
                type="button"
                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium border transition-colors"
                :class="split
                  ? 'border-emerald-600 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                  : 'border-gray-300 text-gray-700 hover:bg-gray-100'"
                :aria-pressed="split"
                title="Afficher deux onglets côte à côte"
                data-testid="student-panel-split-toggle"
                @click="toggleSplit"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="2" />
                  <path stroke-linecap="round" stroke-width="2" d="M12 4v16" />
                </svg>
                Vue scindée
              </button>
              <button
                ref="closeButton"
                type="button"
                class="p-2 rounded-lg text-gray-500 hover:text-gray-800 hover:bg-gray-100"
                aria-label="Fermer la fiche élève"
                @click="close"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </header>

        <div v-if="loading && !student" class="flex-1 flex justify-center py-16">
          <div class="animate-spin h-10 w-10 border-2 border-emerald-600 border-t-transparent rounded-full" />
        </div>

        <div v-else-if="error && !student" class="m-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
          {{ error }}
        </div>

        <!-- Volets : 1 (normal) ou 2 (vue scindée), chacun avec ses onglets et son propre scroll -->
        <div v-else-if="student" class="flex-1 min-h-0 flex divide-x divide-gray-200">
          <div
            v-for="(pane, i) in visiblePanes"
            :key="i"
            class="flex-1 min-w-0 flex flex-col"
            :data-testid="`student-panel-pane-${i}`"
          >
            <nav
              class="shrink-0 bg-white border-b border-gray-200 px-3 flex gap-1 overflow-x-auto"
              role="tablist"
              :aria-label="isSplit ? `Sections de la fiche élève (volet ${i + 1})` : 'Sections de la fiche élève'"
            >
              <button
                v-for="t in tabs"
                :key="t.id"
                type="button"
                role="tab"
                :aria-selected="pane.tab === t.id"
                class="px-3 py-2 text-sm font-medium border-b-2 whitespace-nowrap transition-colors"
                :class="pane.tab === t.id
                  ? 'border-emerald-600 text-emerald-700'
                  : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'"
                :title="isSplit && otherPaneTab(i) === t.id ? 'Affiché dans l\'autre volet — cliquer pour échanger' : undefined"
                @click="selectTab(i, t.id)"
              >
                {{ t.label }}
                <span v-if="isSplit && otherPaneTab(i) === t.id" class="ml-1 text-xs text-gray-400" aria-hidden="true">⇄</span>
              </button>
            </nav>

            <!-- Chaque onglet reste monté une fois visité dans ce volet (filtres, saisie conservés) -->
            <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain">
              <div v-if="pane.visited.has('infos')" v-show="pane.tab === 'infos'">
                <EditStudentModal :key="`edit-${formKey}-${i}`" :student="student" embedded @success="onEdited" />
              </div>
              <div v-if="pane.visited.has('abonnements')" v-show="pane.tab === 'abonnements'">
                <StudentSubscriptionsModal :student="student" embedded @success="emit('updated')" />
              </div>
              <div v-if="pane.visited.has('planning')" v-show="pane.tab === 'planning'">
                <PlanningParticipantInfoModal
                  :show="true"
                  type="student"
                  :participant-id="Number(studentId)"
                  :fallback-name="displayName"
                  embedded
                  @close="close"
                />
              </div>
              <div v-if="pane.visited.has('historique')" v-show="pane.tab === 'historique'">
                <StudentHistoryModal :student="student" embedded />
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </Teleport>
</template>

<script lang="ts">
export type StudentPanelTab = 'infos' | 'abonnements' | 'planning' | 'historique'

// Verrou de scroll partagé entre instances (plusieurs fiches ne doivent pas se marcher dessus).
let scrollLocks = 0
let savedOverflow = ''
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
const SPLIT_STORAGE_KEY = 'club.studentPanel.split'

interface Pane { tab: StudentPanelTab; visited: Set<StudentPanelTab> }
// Volet 0 = onglet principal (synchronisé avec le parent / l'URL) ; volet 1 = vue scindée.
const panes = reactive<Pane[]>([
  { tab: props.tab, visited: new Set([props.tab]) },
  { tab: defaultSecondTab(props.tab), visited: new Set() },
])
const split = ref(readSplitPref())
const isWide = ref(false)
const isSplit = computed(() => split.value && isWide.value)
const visiblePanes = computed(() => (isSplit.value ? panes : [panes[0]]))
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

function defaultSecondTab(first: StudentPanelTab): StudentPanelTab {
  return first === 'abonnements' ? 'historique' : 'abonnements'
}

function readSplitPref(): boolean {
  try { return typeof window !== 'undefined' && window.localStorage.getItem(SPLIT_STORAGE_KEY) === '1' } catch { return false }
}

function otherPaneTab(i: number): StudentPanelTab {
  return panes[i === 0 ? 1 : 0].tab
}

function showTab(i: number, id: StudentPanelTab) {
  panes[i].tab = id
  panes[i].visited.add(id)
  if (i === 0) emit('update:tab', id)
}

function selectTab(i: number, id: StudentPanelTab) {
  // En vue scindée, choisir l'onglet affiché dans l'autre volet échange les deux volets.
  if (isSplit.value && otherPaneTab(i) === id) {
    const other = i === 0 ? 1 : 0
    showTab(other, panes[i].tab)
  }
  showTab(i, id)
}

function toggleSplit() {
  split.value = !split.value
  try { window.localStorage.setItem(SPLIT_STORAGE_KEY, split.value ? '1' : '0') } catch { /* stockage indisponible */ }
  if (split.value) {
    if (panes[1].tab === panes[0].tab) panes[1].tab = defaultSecondTab(panes[0].tab)
    panes[1].visited.add(panes[1].tab)
  }
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
    panes[0].tab = props.tab
    panes[0].visited.clear()
    panes[0].visited.add(props.tab)
    panes[1].tab = defaultSecondTab(props.tab)
    panes[1].visited.clear()
    if (split.value) panes[1].visited.add(panes[1].tab)
    if (id != null && id !== '') {
      void loadStudent(id)
      void nextTick(() => closeButton.value?.focus())
    }
  },
  { immediate: true },
)

watch(() => props.tab, (t) => {
  if (t && t !== panes[0].tab) selectTab(0, t)
})

// Bloque le scroll de la page en arrière-plan tant que la fiche est ouverte.
let holdsLock = false
function lockScroll(lock: boolean) {
  if (typeof document === 'undefined' || lock === holdsLock) return
  const root = document.documentElement
  holdsLock = lock
  if (lock) {
    if (scrollLocks++ === 0) {
      savedOverflow = root.style.overflow
      root.style.overflow = 'hidden'
    }
  } else if (--scrollLocks === 0) {
    root.style.overflow = savedOverflow
  }
}
watch(() => props.studentId, (id) => lockScroll(id != null && id !== ''), { immediate: true })

let wideQuery: MediaQueryList | null = null
function onWideChange() { isWide.value = !!wideQuery?.matches }

function onKeydown(e: KeyboardEvent) {
  // Échap ferme la fiche, sauf si une modale ouverte depuis la fiche (ex. assignation) est affichée
  if (e.key !== 'Escape' || !props.studentId) return
  if (panelEl.value?.querySelector('.fixed.inset-0')) return
  close()
}

onMounted(() => {
  window.addEventListener('keydown', onKeydown)
  wideQuery = window.matchMedia('(min-width: 1024px)')
  onWideChange()
  wideQuery.addEventListener('change', onWideChange)
  if (split.value) panes[1].visited.add(panes[1].tab)
})
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
  wideQuery?.removeEventListener('change', onWideChange)
  lockScroll(false)
})
</script>
