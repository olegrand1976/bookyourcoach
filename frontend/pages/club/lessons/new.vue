<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">
              Nouveau cours
            </h1>
          </div>
          <button 
            @click="navigateTo('/club/dashboard')"
            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors"
          >
            Retour au dashboard
          </button>
        </div>
      </div>

      <!-- Formulaire -->
      <div class="bg-white rounded-xl shadow-lg p-8">
        <form @submit.prevent="createLesson" class="space-y-6">
          <!-- Titre du cours -->
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
              Titre du cours *
            </label>
            <input
              id="title"
              v-model="form.title"
              type="text"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              placeholder="Ex: Cours d'équitation débutant"
            />
          </div>

          <!-- Enseignant -->
          <div>
            <label for="teacher" class="block text-sm font-medium text-gray-700 mb-2">
              Enseignant *
            </label>
            <div class="relative">
              <input
                id="teacher"
                v-model="teacherSearch"
                type="text"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                placeholder="Rechercher un enseignant..."
                @input="searchTeachers"
                @focus="showTeacherDropdown = true"
                @blur="hideTeacherDropdown"
              />
              <div v-if="showTeacherDropdown && filteredTeachers.length > 0" 
                   class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-auto">
                <div v-for="teacher in filteredTeachers" 
                     :key="teacher.id"
                     @click="selectTeacher(teacher)"
                     class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0">
                  <div class="font-medium text-gray-900">{{ teacher.name }}</div>
                  <div class="text-sm text-gray-600">{{ teacher.email }}</div>
                  <div class="text-sm text-blue-600">{{ teacher.specialties }}</div>
                </div>
              </div>
            </div>
            <input type="hidden" v-model="form.teacher_id" />
          </div>

          <!-- Élève -->
          <div>
            <label for="student" class="block text-sm font-medium text-gray-700 mb-2">
              Élève *
            </label>
            <div class="relative">
              <input
                id="student"
                v-model="studentSearch"
                type="text"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                placeholder="Rechercher un élève..."
                @input="searchStudents"
                @focus="showStudentDropdown = true"
                @blur="hideStudentDropdown"
              />
              <div v-if="showStudentDropdown && filteredStudents.length > 0" 
                   class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-auto">
                <div v-for="student in filteredStudents" 
                     :key="student.id"
                     @click="selectStudent(student)"
                     class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0">
                  <div class="font-medium text-gray-900">{{ student.name }}</div>
                  <div class="text-sm text-gray-600">{{ student.email }}</div>
                  <div class="text-sm text-emerald-600">{{ getLevelLabel(student.level) }}</div>
                </div>
              </div>
            </div>
            <input type="hidden" v-model="form.student_id" />
          </div>

          <!-- Date et heure -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
                Date *
              </label>
              <input
                id="date"
                v-model="form.date"
                type="date"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              />
            </div>
            <div>
              <label for="time" class="block text-sm font-medium text-gray-700 mb-2">
                Heure de début *
              </label>
              <input
                id="time"
                v-model="form.time"
                type="time"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              />
            </div>
          </div>

          <!-- Durée -->
          <div>
            <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">
              Durée (minutes) *
            </label>
            <select
              id="duration"
              v-model="form.duration"
              required
              @change="updatePrice"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            >
              <option v-for="duration in availableDurations" :key="duration" :value="duration">
                {{ formatDuration(duration) }}
              </option>
            </select>
          </div>

          <!-- Type de cours -->
          <div>
            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
              Type de cours *
            </label>
            <select
              id="type"
              v-model="form.type"
              required
              @change="updatePrice"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            >
              <option value="lesson">Cours individuel</option>
              <option value="group">Cours de groupe</option>
              <option value="training">Entraînement</option>
              <option value="competition">Compétition</option>
            </select>
          </div>

          <!-- Prix -->
          <div>
            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
              Prix (€) *
            </label>
            <input
              id="price"
              v-model="form.price"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              placeholder="45.00"
            />
          </div>

          <!-- Notes -->
          <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
              Notes (optionnel)
            </label>
            <textarea
              id="notes"
              v-model="form.notes"
              rows="4"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              placeholder="Informations supplémentaires sur le cours..."
            ></textarea>
          </div>

          <!-- Classification DCL/NDCL pour les commissions -->
          <div class="border-t pt-6">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              Classification pour les commissions
            </label>
            <div class="space-y-3">
              <div class="flex items-center">
                <input
                  id="dcl"
                  v-model="form.est_legacy"
                  :value="false"
                  type="radio"
                  class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300"
                />
                <label for="dcl" class="ml-2 block text-sm text-gray-700">
                  <span class="font-medium">DCL</span> (Déclaré) - Commission standard
                </label>
              </div>
              <div class="flex items-center">
                <input
                  id="ndcl"
                  v-model="form.est_legacy"
                  :value="true"
                  type="radio"
                  class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300"
                />
                <label for="ndcl" class="ml-2 block text-sm text-gray-700">
                  <span class="font-medium">NDCL</span> (Non Déclaré) - Commission legacy
                </label>
              </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">
              ⓘ Cette classification détermine le type de commission pour l'enseignant dans les rapports de paie.
            </p>
          </div>

          <!-- Date de paiement et montant (optionnel) -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="date_paiement" class="block text-sm font-medium text-gray-700 mb-2">
                Date de paiement (optionnel)
              </label>
              <input
                id="date_paiement"
                v-model="form.date_paiement"
                type="date"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              />
              <p class="mt-1 text-xs text-gray-500">
                Détermine le mois de commission dans les rapports de paie
              </p>
            </div>
            <div>
              <label for="montant" class="block text-sm font-medium text-gray-700 mb-2">
                Montant payé (optionnel)
              </label>
              <input
                id="montant"
                v-model.number="form.montant"
                type="number"
                step="0.01"
                min="0"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                placeholder="Montant réellement payé"
              />
              <p class="mt-1 text-xs text-gray-500">
                Montant réellement payé (peut différer du prix du cours)
              </p>
            </div>
          </div>

          <!-- Boutons -->
          <div class="flex items-center justify-end space-x-4 pt-6">
            <button
              type="button"
              @click="navigateTo('/club/dashboard')"
              class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading">Création...</span>
              <span v-else>Créer le cours</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// Composable pour les toasts
const { success, error: showError } = useToast()

definePageMeta({
  middleware: ['auth']
})

const loading = ref(false)
const teachers = ref([])
const students = ref([])
const teacherSearch = ref('')
const studentSearch = ref('')
const showTeacherDropdown = ref(false)
const showStudentDropdown = ref(false)
const filteredTeachers = ref([])
const filteredStudents = ref([])

// Configuration des prix par défaut selon le type et la durée
const defaultPrices = {
  lesson: { 15: 25, 30: 40, 45: 55, 60: 70 }, // Cours individuel
  group: { 15: 15, 30: 25, 45: 35, 60: 45 },  // Cours de groupe
  training: { 15: 20, 30: 35, 45: 50, 60: 65 }, // Entraînement
  competition: { 15: 30, 30: 50, 45: 70, 60: 90 } // Compétition
}

// Durées disponibles selon le type de cours
const availableDurations = ref([15, 30, 45, 60])

const form = ref({
  title: '',
  teacher_id: '',
  student_id: '',
  date: new Date().toISOString().split('T')[0], // Date du jour
  time: '',
  duration: '60',
  type: 'lesson',
  price: '70.00', // Prix par défaut pour cours individuel 1h
  notes: '',
  // Champs pour les commissions
  est_legacy: false, // Par défaut DCL (false)
  date_paiement: null,
  montant: null
})

// Charger les enseignants et élèves
const loadData = async () => {
  try {
    // Pour l'instant, utiliser des données de test
    teachers.value = [
      { id: 1, name: 'Sophie Martin', email: 'sophie.martin@activibe.com', specialties: 'Équitation, Dressage' },
      { id: 2, name: 'Pierre Dubois', email: 'pierre.dubois@activibe.com', specialties: 'Saut d\'obstacles, Cross' },
      { id: 3, name: 'Marie Leroy', email: 'marie.leroy@activibe.com', specialties: 'Équitation western' }
    ]
    
    students.value = [
      { id: 1, name: 'Marie Dupont', email: 'marie.dupont@example.com', level: 'debutant' },
      { id: 2, name: 'Jean Martin', email: 'jean.martin@example.com', level: 'intermediaire' },
      { id: 3, name: 'Emma Rousseau', email: 'emma.rousseau@example.com', level: 'avance' },
      { id: 4, name: 'Lucas Petit', email: 'lucas.petit@example.com', level: 'debutant' }
    ]
    
    filteredTeachers.value = teachers.value
    filteredStudents.value = students.value
  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
  }
}

// Fonctions d'autocomplétion pour les enseignants
const searchTeachers = () => {
  if (!teacherSearch.value) {
    filteredTeachers.value = teachers.value
  } else {
    filteredTeachers.value = teachers.value.filter(teacher =>
      teacher.name.toLowerCase().includes(teacherSearch.value.toLowerCase()) ||
      teacher.email.toLowerCase().includes(teacherSearch.value.toLowerCase()) ||
      teacher.specialties.toLowerCase().includes(teacherSearch.value.toLowerCase())
    )
  }
}

const selectTeacher = (teacher) => {
  form.value.teacher_id = teacher.id
  teacherSearch.value = teacher.name
  showTeacherDropdown.value = false
}

const hideTeacherDropdown = () => {
  setTimeout(() => {
    showTeacherDropdown.value = false
  }, 200)
}

// Fonctions d'autocomplétion pour les élèves
const searchStudents = () => {
  if (!studentSearch.value) {
    filteredStudents.value = students.value
  } else {
    filteredStudents.value = students.value.filter(student =>
      student.name.toLowerCase().includes(studentSearch.value.toLowerCase()) ||
      student.email.toLowerCase().includes(studentSearch.value.toLowerCase())
    )
  }
}

const selectStudent = (student) => {
  form.value.student_id = student.id
  studentSearch.value = student.name
  showStudentDropdown.value = false
}

const hideStudentDropdown = () => {
  setTimeout(() => {
    showStudentDropdown.value = false
  }, 200)
}

// Fonction pour formater la durée
const formatDuration = (minutes) => {
  if (minutes < 60) {
    return `${minutes} minutes`
  } else {
    const hours = Math.floor(minutes / 60)
    const remainingMinutes = minutes % 60
    if (remainingMinutes === 0) {
      return `${hours}h`
    } else {
      return `${hours}h${remainingMinutes}`
    }
  }
}

// Fonction pour obtenir le label du niveau
const getLevelLabel = (level) => {
  const labels = {
    debutant: '🌱 Débutant',
    intermediaire: '📈 Intermédiaire',
    avance: '⭐ Avancé',
    expert: '🏆 Expert'
  }
  return labels[level] || level
}

// Fonction pour mettre à jour le prix selon le type et la durée
const updatePrice = () => {
  const type = form.value.type
  const duration = parseInt(form.value.duration)
  
  if (defaultPrices[type] && defaultPrices[type][duration]) {
    form.value.price = defaultPrices[type][duration].toFixed(2)
  }
}

// Créer le cours
const createLesson = async () => {
  loading.value = true
  
  try {
    // Calculer la date/heure de fin
    const startDateTime = new Date(`${form.value.date}T${form.value.time}`)
    const endDateTime = new Date(startDateTime.getTime() + (form.value.duration * 60000))
    
    const lessonData = {
      title: form.value.title,
      teacher_id: form.value.teacher_id,
      student_id: form.value.student_id,
      start_time: startDateTime.toISOString(),
      end_time: endDateTime.toISOString(),
      duration: parseInt(form.value.duration),
      type: form.value.type,
      price: parseFloat(form.value.price),
      notes: form.value.notes,
      // Champs pour les commissions
      est_legacy: form.value.est_legacy === true || form.value.est_legacy === 'true',
      date_paiement: form.value.date_paiement || null,
      montant: form.value.montant ? parseFloat(form.value.montant) : null
    }
    
    console.log('Données du cours à créer:', lessonData)
    
    // Appeler l'API pour créer le cours
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/club/lessons`, {
      method: 'POST',
      body: lessonData
    })
    
    console.log('✅ Cours créé:', response)
    
    // Afficher un message de succès
    success('Cours créé avec succès', 'Succès')
    
    // Rediriger vers le dashboard
    await navigateTo('/club/dashboard')
    
  } catch (error) {
    console.error('Erreur lors de la création du cours:', error)
    
    // Gérer les différents types d'erreurs
    let errorMessage = 'Erreur lors de la création du cours. Veuillez réessayer.'
    
    if (error.data?.message) {
      errorMessage = error.data.message
    } else if (error.data?.errors) {
      const errors = error.data.errors
      if (typeof errors === 'object') {
        const formattedErrors = Object.entries(errors)
          .map(([field, msgs]) => {
            const messages = Array.isArray(msgs) ? msgs : [msgs]
            return messages.join(', ')
          })
          .join('\n')
        errorMessage = formattedErrors
      } else {
        errorMessage = errors
      }
    } else if (error.message) {
      errorMessage = error.message
    }
    
    showError(errorMessage, 'Erreur de création')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>
