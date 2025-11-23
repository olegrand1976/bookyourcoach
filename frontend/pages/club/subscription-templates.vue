<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-6">
    <!-- Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Modèles d'Abonnements</h1>
            <p class="text-gray-600">Créez et gérez les modèles d'abonnement (templates) pour créer des abonnements</p>
          </div>
          <div class="flex space-x-3">
            <NuxtLink
              to="/club/subscriptions"
              class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
              <span>Abonnements</span>
            </NuxtLink>
            <button 
              @click="openCreateModal"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span>Nouveau Modèle</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Liste des modèles -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="template in templates" 
        :key="template.id"
        class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all overflow-hidden border-2 border-gray-100 hover:border-blue-300"
      >
        <!-- Header carte -->
        <div class="p-6 border-b border-gray-200">
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900 mb-1">
                Modèle {{ template.model_number }}
              </h3>
            </div>
            <span 
              :class="template.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
              class="px-2 py-1 text-xs font-medium rounded-full"
            >
              {{ template.is_active ? 'Actif' : 'Inactif' }}
            </span>
          </div>

          <!-- Détails du modèle -->
          <div class="space-y-2">
            <div class="flex items-center text-sm">
              <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="text-gray-700">
                <strong>{{ template.total_lessons }}</strong> cours
                <span v-if="template.free_lessons > 0" class="text-green-600">
                  + {{ template.free_lessons }} gratuit{{ template.free_lessons > 1 ? 's' : '' }}
                </span>
              </span>
            </div>
            
            <div class="flex items-center text-sm">
              <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="text-gray-700 font-semibold">{{ template.price }} €</span>
            </div>

            <div class="flex items-center text-sm">
              <svg class="w-4 h-4 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
              <span class="text-gray-700">
                Validité: {{ formatValidity(template) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Types de cours inclus -->
        <div class="p-4 bg-gray-50">
          <div class="text-xs font-medium text-gray-500 uppercase mb-2">Types de cours inclus</div>
          <div class="flex flex-wrap gap-1">
            <span 
              v-for="courseType in template.course_types" 
              :key="courseType.id"
              class="bg-white text-gray-700 px-2 py-1 rounded text-xs border border-gray-200"
            >
              {{ courseType.name }}
            </span>
            <span v-if="!template.course_types?.length" class="text-xs text-gray-500 italic">
              Aucun type défini
            </span>
          </div>
        </div>

        <!-- Actions -->
        <div class="p-4 bg-white border-t border-gray-200">
          <div class="flex items-center justify-between">
            <button 
              @click="editTemplate(template)"
              class="flex-1 text-blue-600 hover:text-blue-800 hover:bg-blue-50 py-2 rounded-lg text-sm font-medium transition-colors"
            >
              Modifier
            </button>
            <div class="w-px h-6 bg-gray-200 mx-2"></div>
            <button 
              @click="deleteTemplate(template)"
              class="flex-1 text-red-600 hover:text-red-800 hover:bg-red-50 py-2 rounded-lg text-sm font-medium transition-colors"
            >
              Supprimer
            </button>
          </div>
        </div>
      </div>
    </div>

      <!-- Message si aucun modèle -->
      <div v-if="templates.length === 0 && !loading" class="bg-white rounded-lg shadow-sm p-12 text-center">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun modèle d'abonnement</h3>
        <p class="text-gray-600 mb-4">
          Créez votre premier modèle d'abonnement pour pouvoir ensuite créer des abonnements pour vos élèves.
        </p>
        <div class="flex justify-center gap-3">
          <button 
            @click="openCreateModal"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center space-x-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span>Créer un modèle</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de création/modification -->
    <div 
      v-if="showCreateModal || showEditModal"
      class="fixed inset-0 z-50 overflow-y-auto"
      @click.self="closeModals"
    >
      <div class="flex items-center justify-center min-h-screen px-4 py-12">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModals"></div>
        
        <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <!-- Header -->
          <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-xl font-bold text-gray-900">
                {{ showEditModal ? 'Modifier le modèle' : 'Nouveau Modèle d\'Abonnement' }}
              </h2>
              <button 
                @click="closeModals"
                class="text-gray-400 hover:text-gray-600"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="p-6 space-y-6">
            <!-- Numéro du modèle (généré automatiquement, affiché uniquement en édition) -->
            <div v-if="showEditModal">
              <label class="block text-sm font-medium text-gray-700 mb-2">Numéro du modèle</label>
              <input 
                v-model="form.model_number"
                type="text"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100"
                readonly
                disabled
              />
              <p class="text-xs text-gray-500 mt-1">Le numéro est généré automatiquement lors de la création</p>
            </div>
            <div v-else class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                  <p class="text-sm font-medium text-blue-900">Numéro généré automatiquement</p>
                  <p class="text-xs text-blue-700 mt-1">Le numéro du modèle sera généré automatiquement au format MOD-XX-Types de cours une fois les types de cours sélectionnés.</p>
                </div>
              </div>
            </div>

            <!-- Nombre de cours -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de cours *</label>
                <input 
                  v-model.number="form.total_lessons"
                  type="number" 
                  min="1"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cours gratuits offerts</label>
                <input 
                  v-model.number="form.free_lessons"
                  type="number" 
                  min="0"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2"
                />
              </div>
            </div>

            <!-- Prix -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Prix de l'abonnement (€) *</label>
              <input 
                v-model.number="form.price"
                type="number" 
                min="0"
                step="0.01"
                class="w-full border border-gray-300 rounded-lg px-3 py-2"
              />
              <p class="text-xs text-gray-500 mt-1">
                <span v-if="form.total_lessons > 0 && form.price > 0">
                  Prix par cours : {{ (form.price / form.total_lessons).toFixed(2) }} €
                </span>
              </p>
            </div>

            <!-- Durée de validité -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Durée de validité *</label>
              <div class="flex gap-3">
                <div class="flex-1">
                  <input 
                    v-model.number="form.validity_value"
                    type="number" 
                    min="1"
                    :max="form.validity_unit === 'weeks' ? 260 : 60"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2"
                    placeholder="12"
                  />
                </div>
                <div class="w-40">
                  <select 
                    v-model="form.validity_unit"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2"
                  >
                    <option value="weeks">Semaines</option>
                    <option value="months">Mois</option>
                  </select>
                </div>
              </div>
              <p class="text-xs text-gray-500 mt-1">
                Durée pendant laquelle l'abonnement reste valide après souscription
                <span v-if="form.validity_unit === 'weeks'">
                  ({{ form.validity_value }} semaine{{ form.validity_value > 1 ? 's' : '' }} = {{ (form.validity_value / 4.33).toFixed(1) }} mois)
                </span>
                <span v-else>
                  ({{ form.validity_value }} mois)
                </span>
              </p>
            </div>

            <!-- Types de cours -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Types de cours inclus *</label>
              <div class="mb-2">
                <p class="text-xs text-gray-500">
                  Les types de cours affichés correspondent aux <strong>{{ clubDisciplinesCount }} discipline(s)</strong> configurée(s) dans votre profil club.
                  <nuxt-link to="/club/profile" class="text-blue-600 hover:text-blue-800 underline ml-1">
                    Modifier les disciplines
                  </nuxt-link>
                </p>
                <p v-if="clubDisciplinesCount > 0" class="text-xs text-blue-600 mt-1 bg-blue-50 border border-blue-200 rounded px-2 py-1">
                  ✅ Seuls les types de cours pour vos disciplines configurées ({{ clubDisciplinesCount }} discipline{{ clubDisciplinesCount > 1 ? 's' : '' }}) sont affichés. Les types génériques sont exclus.
                </p>
                <p v-if="clubDisciplinesCount > 1" class="text-xs text-amber-600 mt-1 bg-amber-50 border border-amber-200 rounded px-2 py-1">
                  ℹ️ Pour modifier les types de cours disponibles, configurez les disciplines dans votre <NuxtLink to="/club/profile" class="text-amber-700 underline">profil</NuxtLink>.
                </p>
              </div>
              <div class="border border-gray-300 rounded-lg p-3 max-h-48 overflow-y-auto space-y-3">
                <!-- Debug : Afficher le nombre de groupes -->
                <div v-if="Object.keys(groupedCourseTypes).length === 0" class="text-xs text-amber-600 bg-amber-50 p-2 rounded mb-2">
                  ⚠️ Aucun groupe créé. Types de cours: {{ availableCourseTypes.length }}
                </div>
                
                <!-- Groupement par Activité → Discipline -->
                <template v-for="(disciplines, activityName) in groupedCourseTypes" :key="activityName || 'empty'">
                  <!-- Niveau Activité -->
                  <div class="mb-4">
                    <div class="text-sm font-bold text-gray-800 mb-2 px-2 pb-1 border-b-2 border-blue-300">
                      {{ activityName === '__GENERIC__' ? 'Types génériques' : activityName }}
                    </div>
                    
                    <!-- Niveau Discipline -->
                    <template v-for="(courseTypes, disciplineName) in disciplines" :key="`${activityName}-${disciplineName}`">
                      <div v-if="Array.isArray(courseTypes) && courseTypes.length > 0" class="mb-3 ml-3">
                        <div class="text-xs font-semibold text-gray-600 uppercase mb-1 px-2">
                          {{ disciplineName === '__GENERIC__' ? 'Types génériques' : disciplineName }}
                        </div>
                        
                        <!-- Types de cours -->
                        <label 
                          v-for="courseType in courseTypes" 
                          :key="`${activityName}-${disciplineName}-${courseType.id}`"
                          class="flex items-center space-x-2 hover:bg-gray-50 p-2 rounded cursor-pointer ml-2"
                        >
                          <input 
                            type="checkbox" 
                            :value="courseType.id"
                            v-model="form.course_type_ids"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                          />
                          <span class="text-sm text-gray-700">{{ formatCourseTypeName(courseType) }}</span>
                        </label>
                      </div>
                    </template>
                  </div>
                </template>
                
                <p v-if="availableCourseTypes.length === 0" class="text-sm text-gray-500 italic">
                  Aucun type de cours disponible
                </p>
              </div>
            </div>

            <!-- Statut actif -->
            <div class="flex items-center space-x-2">
              <input 
                type="checkbox" 
                v-model="form.is_active"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <label class="text-sm text-gray-700">Modèle actif (disponible pour créer des abonnements)</label>
            </div>
          </div>

          <!-- Footer -->
          <div class="sticky bottom-0 bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button
              @click="closeModals"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
            >
              Annuler
            </button>
            <button
              @click="showEditModal ? updateTemplate() : createTemplate()"
              :disabled="!isFormValid"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ showEditModal ? 'Mettre à jour' : 'Créer' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: ['auth'],
  layout: 'default'
})

const { $api } = useNuxtApp()
const templates = ref([])
const availableCourseTypes = ref([])
const loading = ref(false)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingTemplate = ref(null)
const clubDisciplinesCount = ref(0)
const clubDisciplineIds = ref([])
const clubDefaults = ref({
  default_subscription_total_lessons: 10,
  default_subscription_free_lessons: 1,
  default_subscription_price: 180,
  default_subscription_validity_value: 12,
  default_subscription_validity_unit: 'weeks'
})

const form = ref({
  model_number: '',
  total_lessons: 10,
  free_lessons: 1,
  price: 180,
  validity_months: 12,
  validity_unit: 'weeks', // 'weeks' ou 'months'
  validity_value: 12, // Valeur numérique
  course_type_ids: [],
  is_active: true
})

const isFormValid = computed(() => {
  // model_number n'est plus requis car généré automatiquement
  return form.value.total_lessons > 0 && 
         form.value.price >= 0 && 
         form.value.validity_value > 0 &&
         form.value.course_type_ids.length > 0
})

// Grouper les types de cours par Activité → Discipline
const groupedCourseTypes = computed(() => {
  // S'assurer que availableCourseTypes.value est un tableau
  const courseTypes = Array.isArray(availableCourseTypes.value) ? availableCourseTypes.value : []
  
  // 🔒 FILTRAGE STRICT : Les types de cours doivent déjà être filtrés par le backend (only_used_in_slots=true)
  // Mais on applique un filtrage supplémentaire pour être absolument sûr
  const disciplineIds = clubDisciplineIds.value || []
  
  // Filtrer strictement : seulement les types avec discipline_id correspondant au club
  // ET qui sont dans availableCourseTypes (déjà filtrés par le backend pour les créneaux)
  const filteredTypes = courseTypes.filter(ct => {
    // Exclure les types sans discipline_id
    if (!ct.discipline_id && ct.discipline_id !== 0) {
      console.log(`❌ [GROUPEMENT] Type sans discipline exclu: ${ct.name}`)
      return false
    }
    
    // Vérifier que le discipline_id correspond aux disciplines du club
    const typeDisciplineId = parseInt(ct.discipline_id)
    if (disciplineIds.length > 0 && !disciplineIds.includes(typeDisciplineId)) {
      console.log(`❌ [GROUPEMENT] Type exclu (discipline ${typeDisciplineId} non dans le club): ${ct.name}`)
      return false
    }
    
    // Vérifier que le type a bien une discipline chargée
    if (!ct.discipline) {
      console.warn(`⚠️ [GROUPEMENT] Type sans relation discipline chargée: ${ct.name} (discipline_id: ${ct.discipline_id})`)
      return false
    }
    
    return true
  })
  
  console.log('🔄 [GROUPEMENT] Groupement des types de cours...', {
    totalTypesAvant: courseTypes.length,
    totalTypesApresFiltrage: filteredTypes.length,
    clubDisciplineIds: disciplineIds,
    typesAvant: courseTypes.map(ct => ({ id: ct.id, name: ct.name, discipline_id: ct.discipline_id })),
    typesApres: filteredTypes.map(ct => ({ id: ct.id, name: ct.name, discipline_id: ct.discipline_id })),
    sampleTypes: filteredTypes.slice(0, 5).map(ct => ({
      id: ct.id,
      name: ct.name,
      discipline_id: ct.discipline_id,
      discipline_name: ct.discipline?.name,
      activity_name: ct.discipline?.activity?.name
    }))
  })
  
  // Structure: { activityName: { disciplineName: [courseTypes] } }
  const structure = {}
  
  filteredTypes.forEach(courseType => {
    // Ignorer les types sans discipline (ne devrait pas arriver avec le filtrage strict)
    if (!courseType.discipline || !courseType.discipline_id) {
      console.warn(`⚠️ [GROUPEMENT] Type sans discipline ignoré: ${courseType.name}`)
      return
    }
    
    const activityName = courseType.discipline.activity?.name || 'Autres'
    const disciplineName = courseType.discipline.name
    
    if (!structure[activityName]) {
      structure[activityName] = {}
    }
    if (!structure[activityName][disciplineName]) {
      structure[activityName][disciplineName] = []
    }
    
    // Vérifier qu'on n'ajoute pas de doublon
    const alreadyExists = structure[activityName][disciplineName].some(ct => ct.id === courseType.id)
    if (!alreadyExists) {
      structure[activityName][disciplineName].push(courseType)
    } else {
      console.warn(`⚠️ [GROUPEMENT] Doublon détecté et ignoré: ${courseType.name} (ID: ${courseType.id})`)
    }
  })
  
  // Nettoyer les groupes vides
  Object.keys(structure).forEach(activityName => {
    Object.keys(structure[activityName]).forEach(disciplineName => {
      if (!Array.isArray(structure[activityName][disciplineName]) || structure[activityName][disciplineName].length === 0) {
        delete structure[activityName][disciplineName]
      }
    })
    if (Object.keys(structure[activityName]).length === 0) {
      delete structure[activityName]
    }
  })
  
  console.log('📦 [GROUPEMENT] Structure créée:', Object.keys(structure).map(activity => ({
    activity,
    disciplines: Object.keys(structure[activity]),
    totalTypes: Object.values(structure[activity]).flat().filter(Array.isArray).reduce((sum, arr) => sum + arr.length, 0)
  })))
  
  return structure
})

// Obtenir le nom d'affichage d'un groupe
const getGroupDisplayName = (groupKey) => {
  if (groupKey === '__GENERIC__' || !groupKey) {
    return 'Types génériques'
  }
  return groupKey
}

// Formater le nom du type de cours pour l'affichage
const formatCourseTypeName = (courseType) => {
  // Si c'est un type générique, retourner le nom tel quel
  if (!courseType.discipline_id || !courseType.discipline) {
    return courseType.name
  }
  
  // Pour les types liés à une discipline, le nom seul est suffisant
  // car la discipline est déjà affichée dans le titre du groupe
  const name = courseType.name
  
  // Si le nom commence déjà par la discipline, ne pas répéter
  if (name.toLowerCase().includes(courseType.discipline.name.toLowerCase())) {
    return name
  }
  
  // Sinon, retourner juste le nom (la discipline est dans le titre du groupe)
  return name
}

// Formater l'affichage de la validité
const formatValidity = (template) => {
  // Utiliser validity_value et validity_unit si disponibles (vérifier explicitement null/undefined)
  if (template.validity_value != null && template.validity_unit != null) {
    if (template.validity_unit === 'weeks') {
      return `${template.validity_value} semaine${template.validity_value > 1 ? 's' : ''}`
    } else {
      return `${template.validity_value} mois`
    }
  }
  // Fallback pour les anciens modèles sans validity_value/validity_unit
  const months = template.validity_months || 12
  if (months < 3) {
    const weeks = Math.round(months * 4.33)
    return `${weeks} semaine${weeks > 1 ? 's' : ''} (${months} mois)`
  }
  return `${months} mois`
}

// Charger les modèles
const loadTemplates = async () => {
  try {
    loading.value = true
    console.log('🔄 [loadTemplates] Début du chargement...')
    const response = await $api.get('/club/subscription-templates')
    console.log('📥 [loadTemplates] Réponse API:', {
      success: response.data?.success,
      dataLength: response.data?.data?.length || 0,
      hasData: !!response.data?.data,
      data: response.data?.data
    })
    if (response.data.success) {
      templates.value = response.data.data || []
      console.log('✅ [loadTemplates] Modèles chargés:', templates.value.length)
      console.log('📋 [loadTemplates] Détails des modèles:', templates.value.map(t => ({
        id: t.id,
        model_number: t.model_number,
        is_active: t.is_active,
        course_types_count: t.course_types?.length || t.courseTypes?.length || 0
      })))
    } else {
      console.error('❌ [loadTemplates] Réponse non réussie:', response.data)
      templates.value = []
    }
  } catch (error) {
    console.error('❌ [loadTemplates] Erreur lors du chargement des modèles:', error)
    console.error('❌ [loadTemplates] Détails de l\'erreur:', {
      message: error.message,
      response: error.response?.data,
      status: error.response?.status
    })
    alert('Erreur lors du chargement des modèles: ' + (error.message || 'Erreur inconnue'))
    templates.value = []
  } finally {
    loading.value = false
  }
}

// Charger les valeurs par défaut depuis le profil club
const loadClubDefaults = async () => {
  try {
    const response = await $api.get('/club/profile')
    if (response.data.success && response.data.data) {
      const club = response.data.data
      clubDefaults.value = {
        default_subscription_total_lessons: club.default_subscription_total_lessons ?? 10,
        default_subscription_free_lessons: club.default_subscription_free_lessons ?? 1,
        default_subscription_price: club.default_subscription_price ?? 180,
        default_subscription_validity_value: club.default_subscription_validity_value ?? 12,
        default_subscription_validity_unit: club.default_subscription_validity_unit || 'weeks'
      }
      // Mettre à jour le formulaire avec les valeurs par défaut du club
      resetForm()
    }
  } catch (error) {
    console.error('Erreur lors du chargement des valeurs par défaut:', error)
  }
}

// Charger les types de cours disponibles pour le club
const loadCourseTypes = async () => {
  try {
    console.log('🔄 Chargement des types de cours...')
    // L'API /course-types retourne automatiquement les types de cours du club si l'utilisateur est un club
    // Ajouter un timestamp pour éviter le cache du navigateur
    // Utiliser only_used_in_slots=true pour ne récupérer que les types de cours réellement utilisés dans les créneaux
    const response = await $api.get('/course-types', {
      params: {
        _t: Date.now(),
        only_used_in_slots: true  // 🔒 Filtrer par les types de cours réellement assignés aux créneaux
      }
    })
    
    console.log('📥 Réponse API course-types:', {
      success: response.data?.success,
      dataLength: response.data?.data?.length || 0,
      hasData: !!response.data?.data,
      meta: response.data?.meta,
      club_disciplines: response.data?.meta?.club_disciplines
    })
    
    if (response.data?.success && response.data?.data) {
      // Stocker le nombre de disciplines du club AVANT filtrage
      const clubDisciplines = response.data?.meta?.club_disciplines || []
      clubDisciplinesCount.value = clubDisciplines.length
      clubDisciplineIds.value = clubDisciplines.map(id => parseInt(id))
      console.log('📊 Disciplines du club:', clubDisciplines, 'IDs:', clubDisciplineIds.value)
      
      // 🔒 FILTRAGE STRICT : Ne garder que les types de cours correspondant aux disciplines du club
      // Exclure les types génériques (sans discipline_id) si le club a des disciplines configurées
      let filteredCourseTypes = response.data.data
      
      if (clubDisciplines.length > 0) {
        // Convertir les IDs en nombres pour comparaison sûre
        const clubDisciplineIds = clubDisciplines.map(id => parseInt(id))
        
        console.log('🔍 [FILTRAGE] Début du filtrage strict', {
          clubDisciplinesCount: clubDisciplines.length,
          clubDisciplineIds,
          totalTypesAvant: response.data.data.length,
          sampleTypesAvant: response.data.data.slice(0, 5).map(ct => ({
            id: ct.id,
            name: ct.name,
            discipline_id: ct.discipline_id,
            discipline_name: ct.discipline?.name
          }))
        })
        
        filteredCourseTypes = response.data.data.filter(courseType => {
          // Si le type n'a pas de discipline_id, l'exclure (types génériques)
          if (!courseType.discipline_id && courseType.discipline_id !== 0) {
            console.log(`❌ [FILTRAGE] Type générique exclu: ${courseType.name} (discipline_id: ${courseType.discipline_id})`)
            return false
          }
          
          // Vérifier que le discipline_id correspond à une discipline du club
          const typeDisciplineId = parseInt(courseType.discipline_id)
          const matchesClub = clubDisciplineIds.includes(typeDisciplineId)
          
          if (!matchesClub) {
            console.log(`❌ [FILTRAGE] Type exclu (discipline ${typeDisciplineId} non dans le club [${clubDisciplineIds.join(', ')}]): ${courseType.name}`)
            return false
          }
          
          console.log(`✅ [FILTRAGE] Type conservé: ${courseType.name} (discipline_id: ${typeDisciplineId})`)
          return true
        })
        
        console.log(`🔍 [FILTRAGE] Résultat: ${response.data.data.length} → ${filteredCourseTypes.length} types de cours`, {
          typesConserves: filteredCourseTypes.map(ct => ({
            id: ct.id,
            name: ct.name,
            discipline_id: ct.discipline_id
          }))
        })
      } else {
        // Si aucune discipline configurée, ne garder que les types génériques
        filteredCourseTypes = response.data.data.filter(courseType => !courseType.discipline_id)
        console.log(`🔍 Aucune discipline configurée: ${filteredCourseTypes.length} types génériques conservés`)
      }
      
      availableCourseTypes.value = filteredCourseTypes
      console.log(`✅ ${availableCourseTypes.value.length} types de cours chargés (après filtrage)`)
      
      // Log détaillé des types de cours avec leurs disciplines
      console.log('📋 Détail des types de cours chargés:', availableCourseTypes.value.map(ct => ({
        id: ct.id,
        name: ct.name,
        discipline_id: ct.discipline_id,
        discipline: ct.discipline ? { id: ct.discipline.id, name: ct.discipline.name } : null
      })))
      
      if (clubDisciplines.length > 3) {
        console.warn(`⚠️ Le club a ${clubDisciplines.length} disciplines configurées. Seuls les types de cours de ces disciplines sont affichés.`)
      }
      
      if (availableCourseTypes.value.length === 0) {
        console.warn('⚠️ Aucun type de cours disponible pour ce club')
        const { warning } = useToast()
        warning('Aucun type de cours disponible. Assurez-vous d\'avoir configuré des disciplines dans votre profil.')
      }
    } else {
      console.error('❌ Réponse API invalide:', response.data)
      const { error: showError } = useToast()
      showError('Erreur lors du chargement des types de cours')
    }
  } catch (error) {
    console.error('❌ Erreur lors du chargement des types de cours:', error)
    const { error: showError } = useToast()
    showError('Erreur lors du chargement des types de cours: ' + (error.message || 'Erreur inconnue'))
    availableCourseTypes.value = []
  }
}

// Réinitialiser le formulaire avec les valeurs par défaut du club
const resetForm = () => {
  const defaults = clubDefaults.value
  form.value = {
    model_number: '', // Sera généré automatiquement côté serveur
    total_lessons: defaults.default_subscription_total_lessons,
    free_lessons: defaults.default_subscription_free_lessons,
    price: defaults.default_subscription_price,
    validity_months: defaults.default_subscription_validity_unit === 'weeks' 
      ? Math.round((defaults.default_subscription_validity_value / 4.33) * 10) / 10
      : defaults.default_subscription_validity_value,
    validity_unit: defaults.default_subscription_validity_unit,
    validity_value: defaults.default_subscription_validity_value,
    course_type_ids: [],
    is_active: true
  }
}

// Convertir la validité en mois pour l'API
// Fonction supprimée : on envoie maintenant validity_value et validity_unit directement
// Le backend calcule validity_months automatiquement

// Créer un modèle
const createTemplate = async () => {
  try {
    const payload = {
      ...form.value,
      // Envoyer validity_value et validity_unit au lieu de convertir en mois
      // Le backend calculera validity_months automatiquement
    }
    // Supprimer les champs frontend qui ne sont pas dans l'API
    delete payload.validity_months // Ne pas envoyer, sera calculé côté serveur
    delete payload.model_number // Généré automatiquement côté serveur
    
    const response = await $api.post('/club/subscription-templates', payload)
    if (response.data.success) {
      await loadTemplates()
      closeModals()
      alert('Modèle créé avec succès')
    }
  } catch (error) {
    console.error('Erreur lors de la création:', error)
    if (error.response?.data?.errors) {
      const errorMessages = Object.values(error.response.data.errors).flat().join('\n')
      alert(`Erreur de validation:\n${errorMessages}`)
    } else {
      alert('Erreur lors de la création du modèle')
    }
  }
}

// Modifier un modèle
const editTemplate = (template) => {
  editingTemplate.value = template
  
  // Utiliser validity_value et validity_unit si disponibles, sinon calculer depuis validity_months
  let validityValue = template.validity_value
  let validityUnit = template.validity_unit
  
  // Si validity_value et validity_unit ne sont pas définis (anciens modèles), les déduire de validity_months
  // Vérifier explicitement null/undefined (pas juste falsy pour gérer validity_value = 0)
  if (validityValue == null || validityUnit == null) {
    const validityMonths = template.validity_months || 12
    // Déterminer si on affiche en semaines ou mois (par défaut semaines si < 3 mois)
    validityUnit = validityMonths < 3 ? 'weeks' : 'months'
    validityValue = validityUnit === 'weeks' 
      ? Math.round(validityMonths * 4.33) 
      : validityMonths
  }
  
  form.value = {
    model_number: template.model_number,
    total_lessons: template.total_lessons,
    free_lessons: template.free_lessons,
    price: parseFloat(template.price),
    validity_months: template.validity_months || 12,
    validity_unit: validityUnit,
    validity_value: validityValue,
    course_type_ids: template.course_types?.map(ct => ct.id) || [],
    is_active: template.is_active
  }
  showEditModal.value = true
}

const updateTemplate = async () => {
  try {
    const payload = {
      ...form.value,
      // Envoyer validity_value et validity_unit au lieu de convertir en mois
      // Le backend calculera validity_months automatiquement
    }
    // Supprimer les champs frontend qui ne sont pas dans l'API
    delete payload.validity_months // Ne pas envoyer, sera calculé côté serveur
    
    const response = await $api.put(`/club/subscription-templates/${editingTemplate.value.id}`, payload)
    if (response.data.success) {
      await loadTemplates()
      closeModals()
      alert('Modèle mis à jour avec succès')
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour:', error)
    if (error.response?.data?.errors) {
      const errorMessages = Object.values(error.response.data.errors).flat().join('\n')
      alert(`Erreur de validation:\n${errorMessages}`)
    } else {
      alert('Erreur lors de la mise à jour du modèle')
    }
  }
}

// Supprimer un modèle
const deleteTemplate = async (template) => {
  if (!confirm(`Voulez-vous vraiment supprimer le modèle ${template.model_number} ?`)) {
    return
  }

  try {
    const response = await $api.delete(`/club/subscription-templates/${template.id}`)
    if (response.data.success) {
      await loadTemplates()
      alert('Modèle supprimé avec succès')
    } else {
      alert(response.data.message || 'Erreur lors de la suppression')
    }
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    alert(error.response?.data?.message || 'Erreur lors de la suppression du modèle')
  }
}

const openCreateModal = () => {
  editingTemplate.value = null
  showEditModal.value = false
  resetForm()
  showCreateModal.value = true
}

const closeModals = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingTemplate.value = null
  resetForm()
}

onMounted(async () => {
  // Charger dans l'ordre pour s'assurer que tout est prêt
  await Promise.all([
    loadTemplates(),
    loadCourseTypes(),
    loadClubDefaults()
  ])
  
  // Log final pour vérification
  console.log('✅ Page subscription-templates chargée:', {
    templatesCount: templates.value.length,
    courseTypesCount: availableCourseTypes.value.length,
    clubDisciplinesCount: clubDisciplinesCount.value,
    groupedKeys: Object.keys(groupedCourseTypes.value),
    sampleCourseTypes: availableCourseTypes.value.slice(0, 3).map(ct => ({
      id: ct.id,
      name: ct.name,
      discipline_id: ct.discipline_id,
      discipline: ct.discipline
    }))
  })
})

// Watcher pour debug du groupement
watch(groupedCourseTypes, (newGroups) => {
  console.log('🔄 groupedCourseTypes mis à jour:', {
    groupKeys: Object.keys(newGroups),
    totalGroups: Object.keys(newGroups).length,
    groupDetails: Object.keys(newGroups).map(key => {
      const group = newGroups[key]
      // Si c'est un objet (activité avec disciplines), compter les types dans toutes les disciplines
      if (typeof group === 'object' && !Array.isArray(group)) {
        const allTypes = Object.values(group).flat().filter(Array.isArray)
        return {
          key,
          displayName: getGroupDisplayName(key),
          count: allTypes.length,
          sampleNames: allTypes.flat().slice(0, 2).map(ct => ct?.name || 'N/A')
        }
      }
      // Si c'est directement un tableau
      const typesArray = Array.isArray(group) ? group : []
      return {
        key,
        displayName: getGroupDisplayName(key),
        count: typesArray.length,
        sampleNames: typesArray.slice(0, 2).map(ct => ct?.name || 'N/A')
      }
    })
  })
}, { deep: true, immediate: true })
</script>

