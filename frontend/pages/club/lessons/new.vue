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
            <p class="mt-2 text-gray-600">
              Créer un nouveau cours pour votre club
            </p>
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
            <select
              id="teacher"
              v-model="form.teacher_id"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            >
              <option value="">Sélectionner un enseignant</option>
              <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                {{ teacher.name }}
              </option>
            </select>
          </div>

          <!-- Élève -->
          <div>
            <label for="student" class="block text-sm font-medium text-gray-700 mb-2">
              Élève *
            </label>
            <select
              id="student"
              v-model="form.student_id"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            >
              <option value="">Sélectionner un élève</option>
              <option v-for="student in students" :key="student.id" :value="student.id">
                {{ student.name }}
              </option>
            </select>
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
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            >
              <option value="30">30 minutes</option>
              <option value="45">45 minutes</option>
              <option value="60">1 heure</option>
              <option value="90">1h30</option>
              <option value="120">2 heures</option>
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

definePageMeta({
  middleware: ['auth']
})

const loading = ref(false)
const teachers = ref([])
const students = ref([])

const form = ref({
  title: '',
  teacher_id: '',
  student_id: '',
  date: '',
  time: '',
  duration: '60',
  type: 'lesson',
  price: '45.00',
  notes: ''
})

// Charger les enseignants et élèves
const loadData = async () => {
  try {
    // Pour l'instant, utiliser des données de test
    teachers.value = [
      { id: 1, name: 'Sophie Martin' },
      { id: 2, name: 'Pierre Dubois' }
    ]
    
    students.value = [
      { id: 1, name: 'Marie Dupont' },
      { id: 2, name: 'Jean Martin' },
      { id: 3, name: 'Emma Rousseau' }
    ]
  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
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
      notes: form.value.notes
    }
    
    console.log('Données du cours à créer:', lessonData)
    
    // TODO: Appeler l'API pour créer le cours
    // const response = await $fetch('/api/club/lessons', {
    //   method: 'POST',
    //   body: lessonData
    // })
    
    // Simulation de succès
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Rediriger vers le dashboard avec un message de succès
    await navigateTo('/club/dashboard')
    
  } catch (error) {
    console.error('Erreur lors de la création du cours:', error)
    alert('Erreur lors de la création du cours. Veuillez réessayer.')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>
