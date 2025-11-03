<template>
  <!-- ========================================
       MODALE : NOUVEAU COURS
       Composant autonome pour créer un cours
       ======================================== -->
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
    <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-2xl my-8">
      
      <!-- En-tête -->
      <div class="flex items-center justify-between mb-6 pb-4 border-b">
        <div>
          <h3 class="text-xl font-bold text-gray-900">Nouveau cours</h3>
          <p v-if="lessonData.date" class="text-sm text-gray-600 mt-1">
            📅 {{ formatDate(lessonData.date) }} à {{ lessonData.time }}
          </p>
        </div>
        <button 
          @click="close"
          class="text-gray-400 hover:text-gray-600 transition-colors"
          type="button"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Alerte : Pas de types de cours -->
      <div v-if="availableCourseTypes.length === 0" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
        <div class="flex items-start">
          <span class="text-2xl mr-3">⚠️</span>
          <div class="flex-1">
            <p class="font-semibold text-red-800 mb-1">Aucun type de cours disponible</p>
            <p class="text-sm text-red-700 mb-3">
              Ce créneau n'a pas de types de cours configurés ou aucun type ne correspond aux disciplines de votre club.
            </p>
            <button 
              @click="$emit('edit-slot')" 
              type="button"
              class="text-sm bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 font-medium transition-colors"
            >
              📝 Configurer le créneau
            </button>
          </div>
        </div>
      </div>

      <!-- Alerte : Pas d'enseignant -->
      <div v-if="teachers.length === 0" class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded-lg">
        <div class="flex items-start">
          <span class="text-2xl mr-3">⚠️</span>
          <div class="flex-1">
            <p class="font-semibold text-yellow-800 mb-1">Aucun enseignant dans votre club</p>
            <p class="text-sm text-yellow-700">
              Vous devez d'abord ajouter des enseignants pour créer des cours.
            </p>
          </div>
        </div>
      </div>

      <!-- Formulaire -->
      <form 
        v-if="availableCourseTypes.length > 0 && teachers.length > 0" 
        @submit.prevent="submit" 
        class="space-y-5"
      >
        
        <!-- Type de cours -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">Type de cours *</label>
          
          <!-- Un seul type disponible -->
          <div v-if="availableCourseTypes.length === 1" class="p-4 bg-blue-50 border-2 border-blue-200 rounded-lg">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <p class="font-semibold text-gray-900 mb-1">{{ availableCourseTypes[0].name }}</p>
                <div class="flex items-center gap-3 text-sm text-gray-700">
                  <span>⏱️ {{ availableCourseTypes[0].duration_minutes }} min</span>
                  <span>💰 {{ availableCourseTypes[0].price }}€</span>
                  <span v-if="availableCourseTypes[0].is_individual">👤 Individuel</span>
                  <span v-else>👥 Groupe (max {{ availableCourseTypes[0].max_participants }})</span>
                </div>
              </div>
              <span class="text-3xl text-green-600">✓</span>
            </div>
            <p class="text-xs text-blue-700 mt-2">Seul type disponible pour ce créneau</p>
          </div>
          
          <!-- Plusieurs types : sélection -->
          <select 
            v-else
            v-model="form.courseTypeId"
            required
            class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
          >
            <option value="">-- Sélectionner un type de cours --</option>
            <option v-for="type in availableCourseTypes" :key="type.id" :value="type.id">
              {{ type.name }} • {{ type.duration_minutes }}min • {{ type.price }}€ • 
              {{ type.is_individual ? 'Individuel' : `Groupe (${type.max_participants} max)` }}
            </option>
          </select>
          
          <p class="text-xs text-gray-500 mt-2">
            {{ availableCourseTypes.length }} type{{ availableCourseTypes.length > 1 ? 's' : '' }} 
            disponible{{ availableCourseTypes.length > 1 ? 's' : '' }}
          </p>
        </div>

        <!-- Infos calculées automatiquement -->
        <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg border">
          <div>
            <p class="text-xs text-gray-600 mb-1">Durée</p>
            <p class="font-bold text-gray-900">{{ form.duration }} min</p>
          </div>
          <div>
            <p class="text-xs text-gray-600 mb-1">Prix</p>
            <p class="font-bold text-gray-900">{{ form.price }} €</p>
          </div>
        </div>

        <!-- Date et heure (lecture seule) -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Date</label>
            <input 
              type="text" 
              :value="formatDate(lessonData.date)"
              readonly
              class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-gray-100 text-gray-700 cursor-not-allowed"
            >
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Heure de début *</label>
            <input 
              type="time" 
              v-model="form.time"
              required
              class="w-full border-2 border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
            >
            <p class="text-xs text-gray-500 mt-1">Vous pouvez modifier l'heure proposée</p>
          </div>
        </div>

        <!-- Enseignant -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            Enseignant *
            <span class="text-xs font-normal text-gray-500">
              ({{ teachers.length }} disponible{{ teachers.length > 1 ? 's' : '' }})
            </span>
          </label>
          <select 
            v-model="form.teacherId"
            required
            class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
          >
            <option value="">-- Sélectionner un enseignant --</option>
            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
              {{ teacher.name }}
            </option>
          </select>
        </div>

        <!-- Élève (optionnel) -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            Élève
            <span class="text-xs font-normal text-gray-500">(optionnel)</span>
          </label>
          <select 
            v-model="form.studentId"
            class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
          >
            <option value="">Aucun élève spécifique (cours ouvert)</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.name }}
            </option>
          </select>
          <p v-if="students.length > 0" class="text-xs text-gray-500 mt-2">
            {{ students.length }} élève{{ students.length > 1 ? 's' : '' }} dans votre club
          </p>
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            Notes
            <span class="text-xs font-normal text-gray-500">(optionnel)</span>
          </label>
          <textarea 
            v-model="form.notes"
            rows="3"
            placeholder="Informations complémentaires sur le cours..."
            class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t">
          <button 
            type="button"
            @click="close"
            class="px-6 py-2.5 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors font-medium"
          >
            Annuler
          </button>
          <button 
            type="submit"
            :disabled="!canSubmit"
            class="px-8 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed font-semibold transition-all shadow-md hover:shadow-lg"
          >
            <span v-if="loading">⏳ Création...</span>
            <span v-else>✓ Créer le cours</span>
          </button>
        </div>
      </form>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'

// Props
const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  lessonData: {
    type: Object,
    required: true,
    default: () => ({
      date: '',
      time: '',
      slot: null
    })
  },
  clubDisciplines: {
    type: Array,
    default: () => []
  },
  teachers: {
    type: Array,
    default: () => []
  },
  students: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['close', 'submit', 'edit-slot'])

// État local
const loading = ref(false)
const form = ref({
  courseTypeId: '',
  teacherId: '',
  studentId: '',
  time: '',  // ✅ Ajouté : heure modifiable
  duration: 60,
  price: 0,
  notes: ''
})

// Computed : Types de cours disponibles (filtrés par disciplines du club)
const availableCourseTypes = computed(() => {
  if (!props.lessonData.slot) {
    console.warn('⚠️ [NewLessonModal] Pas de slot fourni')
    return []
  }
  
  const slotCourseTypes = props.lessonData.slot.course_types || []
  if (slotCourseTypes.length === 0) {
    console.warn('⚠️ [NewLessonModal] Aucun type de cours dans le slot')
    return []
  }
  
  // Convertir clubDisciplines en nombres pour comparaison sûre
  const clubDisciplineIds = (props.clubDisciplines || []).map(id => parseInt(id))
  
  console.log('🔍 [NewLessonModal] Données de filtrage:', {
    slotId: props.lessonData.slot?.id,
    slotCourseTypesTotal: slotCourseTypes.length,
    clubDisciplines: clubDisciplineIds,
    clubDisciplinesType: typeof props.clubDisciplines,
    allSlotTypes: slotCourseTypes.map(t => ({
      id: t.id,
      name: t.name,
      discipline_id: t.discipline_id,
      discipline_type: typeof t.discipline_id
    }))
  })
  
  // 🔒 FILTRAGE STRICT : Filtrer par disciplines du club ET discipline du créneau
  const slotDisciplineId = props.lessonData.slot?.discipline_id
  
  const filtered = slotCourseTypes.filter(courseType => {
    // ✅ Le type DOIT avoir une discipline_id (plus de types génériques acceptés)
    if (!courseType.discipline_id || courseType.discipline_id === null) {
      console.warn(`❌ [NewLessonModal] Type générique rejeté: ${courseType.name} (les types génériques ne sont plus acceptés pour garantir la cohérence)`)
      return false
    }
    
    // Convertir en nombre pour comparaison
    const typeDiscId = parseInt(courseType.discipline_id)
    
    // ✅ DOUBLE VALIDATION :
    // 1. Le type doit correspondre à la discipline du créneau (si définie)
    // 2. Le type doit correspondre aux disciplines du club
    
    if (slotDisciplineId && typeDiscId !== parseInt(slotDisciplineId)) {
      console.warn(`❌ [NewLessonModal] Type rejeté: ${courseType.name} (disc:${typeDiscId}) - Créneau demande disc:${slotDisciplineId}`)
      return false
    }
    
    const matchesClub = clubDisciplineIds.includes(typeDiscId)
    
    if (matchesClub) {
      console.log(`✅ [NewLessonModal] Type gardé: ${courseType.name} (disc:${typeDiscId}) - OK avec club et créneau`)
    } else {
      console.warn(`❌ [NewLessonModal] Type filtré: ${courseType.name} (disc:${typeDiscId}) - Club a: [${clubDisciplineIds.join(', ')}]`)
    }
    
    return matchesClub
  })
  
  console.log('🎯 [NewLessonModal] Résultat du filtrage:', {
    totalAvant: slotCourseTypes.length,
    totalApres: filtered.length,
    types: filtered.map(t => `${t.name} (${t.duration_minutes}min, ${t.price}€)`)
  })
  
  return filtered
})

// Computed : Peut soumettre ?
const canSubmit = computed(() => {
  return !loading.value &&
         form.value.courseTypeId !== '' &&
         form.value.teacherId !== '' &&
         availableCourseTypes.value.length > 0
})

// Fonction : Formater la date
const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('fr-FR', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

// Fonction : Fermer
const close = () => {
  resetForm()
  emit('close')
}

// Fonction : Reset formulaire
const resetForm = () => {
  form.value = {
    courseTypeId: '',
    teacherId: '',
    studentId: '',
    time: props.lessonData.time || '',
    duration: 60,
    price: 0,
    notes: ''
  }
}

// Fonction : Soumettre
const submit = async () => {
  if (!canSubmit.value) return
  
  loading.value = true
  
  try {
    const lessonData = {
      course_type_id: parseInt(form.value.courseTypeId),
      teacher_id: parseInt(form.value.teacherId),
      student_id: form.value.studentId ? parseInt(form.value.studentId) : null,
      start_time: `${props.lessonData.date} ${form.value.time}:00`,  // ✅ Utiliser form.value.time
      duration: parseInt(form.value.duration),
      price: parseFloat(form.value.price),
      notes: form.value.notes || null
    }
    
    console.log('📤 [NewLessonModal] Soumission des données:', lessonData)
    emit('submit', lessonData)
  } finally {
    loading.value = false
  }
}

// Watch : Auto-sélection si un seul type disponible
watch(() => props.show, async (newShow) => {
  if (newShow) {
    // Initialiser l'heure avec celle du slot
    form.value.time = props.lessonData.time || ''
    
    await nextTick()
    
    console.log('🔄 [NewLessonModal] Modale ouverte, types disponibles:', availableCourseTypes.value.length)
    
    if (availableCourseTypes.value.length === 1) {
      const type = availableCourseTypes.value[0]
      form.value.courseTypeId = type.id
      form.value.duration = type.duration_minutes || type.duration || 60
      form.value.price = type.price || 0
      
      console.log('✅ [NewLessonModal] Type auto-sélectionné:', {
        name: type.name,
        id: type.id,
        duration: form.value.duration,
        price: form.value.price
      })
    } else if (availableCourseTypes.value.length > 1) {
      console.log('📋 [NewLessonModal] Plusieurs types disponibles:', 
        availableCourseTypes.value.map(t => `${t.name} (${t.duration_minutes}min)`).join(', ')
      )
    }
  }
})

// Watch : Mettre à jour durée et prix quand le type change
watch(() => form.value.courseTypeId, (newTypeId) => {
  if (!newTypeId) return
  
  console.log('🔄 [NewLessonModal] Changement de type, recherche de:', newTypeId, 'dans', availableCourseTypes.value.length, 'types')
  
  const selectedType = availableCourseTypes.value.find(t => {
    // Comparer en convertissant les deux en string pour éviter les problèmes de type
    return t.id.toString() === newTypeId.toString()
  })
  
  if (selectedType) {
    // Essayer plusieurs propriétés pour la durée (API peut varier)
    form.value.duration = selectedType.duration_minutes || selectedType.duration || 60
    form.value.price = selectedType.price || 0
    
    console.log('✅ [NewLessonModal] Type sélectionné:', {
      name: selectedType.name,
      id: selectedType.id,
      duration_raw: selectedType.duration_minutes || selectedType.duration,
      duration_final: form.value.duration,
      price: form.value.price
    })
  } else {
    console.warn('⚠️ [NewLessonModal] Type non trouvé:', newTypeId, 'Types disponibles:', 
      availableCourseTypes.value.map(t => `${t.id}:${t.name}`)
    )
  }
})
</script>

