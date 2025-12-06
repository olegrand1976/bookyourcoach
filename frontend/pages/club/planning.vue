<template>
  <div class="min-h-screen bg-gray-50 p-8">
    <div class="max-w-7xl mx-auto">
    <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Planning</h1>
        <p class="mt-2 text-gray-600">Gestion des cours et créneaux horaires</p>
          </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="text-center">
          <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-600">Chargement des données...</p>
          </div>
        </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <p class="text-red-800">{{ error }}</p>
    </div>

      <!-- Content -->
      <div v-else class="space-y-6">
        <!-- Bloc 1: Liste des cours disponibles (disciplines actives) -->
        <DisciplinesList :disciplines="activeDisciplines" />
        
        <!-- Bloc 2: Gestion des créneaux horaires avec sélection -->
        <SlotsList 
          :slots="openSlots"
          :selected-slot-id="selectedSlot?.id"
          @create-slot="openSlotModal()"
          @edit-slot="openSlotModal"
          @delete-slot="(slot) => deleteSlot(slot.id)"
          @select-slot="handleSlotSelection"
        />
        
        <!-- Bouton "Créer un cours" si un créneau est sélectionné -->
        <div v-if="selectedSlot" class="bg-green-50 border-2 border-green-500 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">Créneau sélectionné</h3>
                <p class="text-sm text-gray-600">
                  {{ getDayName(selectedSlot.day_of_week) }} • 
                  {{ formatTime(selectedSlot.start_time) }} - {{ formatTime(selectedSlot.end_time) }} • 
                  {{ selectedSlot.discipline?.name }}
                </p>
              </div>
            </div>
            <div class="flex gap-2">
              <button 
                @click="openCreateLessonModal(selectedSlot)"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2 shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Créer un cours
              </button>
              <button 
                @click="selectedSlot = null"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                Annuler
              </button>
            </div>
          </div>
        </div>
        
        <!-- Bloc 3: Cours programmés (filtrés par créneau sélectionné) -->
        <div class="bg-white shadow rounded-lg p-6">
          <div class="mb-4">
            <div class="flex items-center justify-between mb-3">
              <div>
                <h2 class="text-xl font-semibold text-gray-900">
                  Cours programmés
                  <span v-if="selectedSlot" class="text-base font-normal text-gray-600">
                    • {{ getDayName(selectedSlot.day_of_week) }} {{ formatTime(selectedSlot.start_time) }}
                  </span>
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                  <span v-if="!selectedSlot" class="text-blue-600 font-medium">
                    ℹ️ Sélectionnez un créneau ci-dessus pour filtrer les cours
                  </span>
                  <span v-else class="font-bold" :class="filteredLessons.length > 0 ? 'text-green-600' : 'text-orange-600'">
                    {{ filteredLessons.length }} cours {{ selectedDate ? `le ${formatDateFull(selectedDate)}` : 'dans ce créneau' }}
                  </span>
                </p>
              </div>
              <div class="flex gap-2">
                <button 
                  @click="showHistoryModal = true"
                  class="px-3 py-2 text-sm border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-50 transition-colors flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Historique complet
                </button>
                <button 
                  v-if="selectedSlot"
                  @click="resetSlotSelection"
                  class="px-3 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                  Voir tous les cours
                </button>
              </div>
            </div>

            <!-- Navigation par date (visible uniquement si un créneau est sélectionné) -->
            <div v-if="selectedSlot" class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
              <button
                @click="navigateToPreviousDate"
                class="p-2 text-blue-700 hover:bg-blue-100 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="!canNavigatePrevious"
                title="Semaine précédente">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </button>

              <div class="flex-1 flex items-center justify-center gap-3">
                <span class="text-sm font-medium text-gray-700">
                  📅 {{ formatDateFull(selectedDate) }}
                </span>
                <input
                  type="date"
                  v-model="selectedDateInput"
                  @change="onDateChange"
                  class="px-3 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :min="getMinDate()"
                  :max="getMaxDate()" />
              </div>

              <button
                @click="navigateToNextDate"
                class="p-2 text-blue-700 hover:bg-blue-100 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="!canNavigateNext"
                title="Semaine suivante">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>

              <button
                v-if="isTodaySlotDay"
                @click="navigateToToday"
                class="px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                title="Aller à aujourd'hui">
                Aujourd'hui
              </button>
              <button
                v-else
                @click="navigateToToday"
                class="px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                title="Aller à la prochaine occurrence">
                Prochain
              </button>
            </div>
          </div>

          <!-- Grille des cours (groupés par plage horaire) -->
          <div v-if="filteredLessons.length > 0" class="space-y-4">
            <!-- Pour chaque plage horaire -->
            <div 
              v-for="timeSlot in lessonsGroupedByTimeSlot" 
              :key="timeSlot.time"
              class="border border-gray-200 rounded-lg overflow-hidden">
              
              <!-- En-tête de la plage horaire -->
              <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-2 flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-white font-semibold text-lg">{{ timeSlot.time }}</span>
                <span class="text-blue-200 text-sm">({{ timeSlot.lessons.length }} cours)</span>
              </div>
              
              <!-- Grille des cours pour cette plage horaire -->
              <div class="p-3 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                  <div 
                    v-for="lesson in timeSlot.lessons" 
                    :key="lesson.id"
                    class="border-2 rounded-lg p-3 transition-all hover:shadow-lg hover:scale-[1.02] cursor-pointer bg-white"
                    :class="getLessonBorderClass(lesson)"
                    :style="getLessonCardStyle(lesson)"
                    @click="openLessonModal(lesson)">
                    
                    <!-- Type de cours et statut -->
                    <div class="flex items-start justify-between mb-2">
                      <h4 class="font-semibold text-gray-900 text-sm leading-tight">
                        {{ lesson.course_type?.name || 'Cours' }}
                      </h4>
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0 ml-2"
                            :class="getStatusBadgeClass(lesson.status)">
                        {{ getStatusLabel(lesson.status) }}
                      </span>
                    </div>
                    
                    <!-- Horaire -->
                    <div class="text-xs text-gray-500 mb-2 flex items-center gap-1">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      {{ formatLessonTime(lesson.start_time) }} - {{ formatLessonTime(lesson.end_time) }}
                    </div>
                    
                    <!-- Élève -->
                    <div class="flex items-center gap-1 text-sm text-gray-700 mb-1">
                      <span class="text-base">👤</span>
                      <span class="font-medium truncate">{{ getLessonStudents(lesson) }}</span>
                      <span 
                        v-if="hasActiveSubscription(lesson)"
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 flex-shrink-0"
                        title="Abonnement actif"
                      >
                        📋
                      </span>
                    </div>
                    
                    <!-- Coach -->
                    <div class="flex items-center gap-1 text-xs text-gray-500 mb-2">
                      <span>🎓</span>
                      <span class="truncate">{{ lesson.teacher?.user?.name || 'Coach' }}</span>
                    </div>
                    
                    <!-- Prix et bouton modifier -->
                    <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                      <span v-if="lesson.price" class="text-sm font-semibold text-gray-700">
                        {{ formatPrice(lesson.price) }} €
                      </span>
                      <span v-else class="text-xs text-gray-400">-</span>
                      <button
                        @click.stop="openEditLessonModal(lesson)"
                        class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors flex items-center gap-1"
                        title="Modifier">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Modifier
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- État vide -->
          <div v-else class="text-center py-12 text-gray-500">
            <div class="text-4xl mb-4">📚</div>
            <p class="text-lg mb-2">
              {{ selectedSlot ? 'Aucun cours dans ce créneau' : 'Aucun cours programmé' }}
            </p>
            <p class="text-sm">
              {{ selectedSlot 
                ? 'Cliquez sur "Créer un cours" ci-dessus pour en ajouter un' 
                : 'Sélectionnez un créneau et créez votre premier cours' 
              }}
            </p>
          </div>
        </div> <!-- Fermeture du v-else class="space-y-6" -->
          
        <!-- Modale Créneau -->
        <div v-if="showSlotModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
              <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">
                  {{ editingSlot ? 'Modifier le créneau' : 'Nouveau créneau' }}
                </h3>
                <button @click="closeSlotModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

              <form @submit.prevent="saveSlot" class="space-y-4">
                <!-- Jour de la semaine -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Jour de la semaine *</label>
                  <select v-model.number="slotForm.day_of_week" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option :value="0">Dimanche</option>
                    <option :value="1">Lundi</option>
                    <option :value="2">Mardi</option>
                    <option :value="3">Mercredi</option>
                    <option :value="4">Jeudi</option>
                    <option :value="5">Vendredi</option>
                    <option :value="6">Samedi</option>
                  </select>
        </div>

                <!-- Horaires -->
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de début *</label>
                    <input v-model="slotForm.start_time" type="time" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
          <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de fin *</label>
                    <input v-model="slotForm.end_time" type="time" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                  </div>
            </div>
            
                <!-- Discipline -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Discipline *</label>
                  <select v-model.number="slotForm.discipline_id" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Sélectionnez une discipline</option>
                    <option v-for="discipline in activeDisciplines" :key="discipline.id" :value="discipline.id">
                      {{ discipline.name }}
              </option>
            </select>
          </div>

                <!-- Durée et Prix -->
                <div class="grid grid-cols-2 gap-4">
            <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Durée (min) *</label>
                    <input v-model.number="slotForm.duration" type="number" min="15" step="5" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix (€) *</label>
                    <input v-model.number="slotForm.price" type="number" min="0" step="0.01" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>

                <!-- Capacité et Plages -->
          <div class="grid grid-cols-2 gap-4">
            <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Participants par créneau *</label>
                    <input v-model.number="slotForm.max_capacity" type="number" min="1" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                    <p class="mt-1 text-xs text-gray-500">Nombre de participants pour UN créneau</p>
            </div>
            <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de plages simultanées *</label>
                    <input v-model.number="slotForm.max_slots" type="number" min="1" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                    <p class="mt-1 text-xs text-gray-500">Ex: 5 couloirs = 5 cours en même temps</p>
            </div>
          </div>

                <!-- Actif -->
                <div class="flex items-center">
                  <input v-model="slotForm.is_active" type="checkbox" id="is_active"
                         class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
                  <label for="is_active" class="ml-2 block text-sm text-gray-700">
                    Créneau actif
            </label>
          </div>

                <!-- Boutons -->
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                  <button type="button" @click="closeSlotModal"
                          class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
              Annuler
            </button>
                  <button type="submit" :disabled="saving"
                          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                    {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modale Détails du Cours -->
      <div v-if="showLessonModal && selectedLesson" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div class="p-6">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-2xl font-bold text-gray-900">
                Détails du cours
              </h3>
              <button @click="closeLessonModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Informations du cours -->
            <div class="space-y-4">
              <!-- Type de cours -->
              <div class="bg-gray-50 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-500 mb-1">Type de cours</label>
                <p class="text-lg font-semibold text-gray-900">
                  {{ selectedLesson.course_type?.name || 'Non défini' }}
                </p>
              </div>

              <!-- Horaires -->
              <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                  <label class="block text-sm font-medium text-gray-500 mb-1">Début</label>
                  <p class="text-base font-semibold text-gray-900">
                    {{ new Date(selectedLesson.start_time).toLocaleString('fr-FR', { 
                      dateStyle: 'short', 
                      timeStyle: 'short' 
                    }) }}
                  </p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                  <label class="block text-sm font-medium text-gray-500 mb-1">Fin</label>
                  <p class="text-base font-semibold text-gray-900">
                    {{ new Date(selectedLesson.end_time).toLocaleString('fr-FR', { 
                      dateStyle: 'short', 
                      timeStyle: 'short' 
                    }) }}
                  </p>
                </div>
              </div>

              <!-- Participants -->
              <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                  <label class="block text-sm font-medium text-gray-500 mb-1">Étudiant(s)</label>
                  <p class="text-base font-semibold text-gray-900">
                    {{ getLessonStudents(selectedLesson) }}
                  </p>
                  <span 
                    v-if="hasActiveSubscription(selectedLesson)"
                    class="mt-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800"
                    title="Avec abonnement actif"
                  >
                    📋 Abonnement
                  </span>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                  <label class="block text-sm font-medium text-gray-500 mb-1">Coach</label>
                  <p class="text-base font-semibold text-gray-900">
                    {{ selectedLesson.teacher?.user?.name || 'Non assigné' }}
                  </p>
                </div>
              </div>

              <!-- Prix -->
              <div class="bg-gray-50 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-500 mb-1">Prix</label>
                <p class="text-lg font-semibold text-gray-900">
                  {{ formatPrice(selectedLesson.price) }} €
                </p>
              </div>

              <!-- Statut -->
              <div class="bg-gray-50 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-500 mb-2">Statut</label>
                <div class="flex flex-wrap gap-2">
                  <button 
                    @click="updateLessonStatus(selectedLesson.id, 'confirmed')"
                    :class="selectedLesson.status === 'confirmed' ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors hover:opacity-80"
                    :disabled="saving">
                    ✓ Confirmé
                  </button>
                  <button 
                    @click="updateLessonStatus(selectedLesson.id, 'pending')"
                    :class="selectedLesson.status === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors hover:opacity-80"
                    :disabled="saving">
                    ⏳ En attente
                  </button>
                  <button 
                    @click="updateLessonStatus(selectedLesson.id, 'completed')"
                    :class="selectedLesson.status === 'completed' ? 'bg-gray-500 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors hover:opacity-80"
                    :disabled="saving">
                    ✓ Terminé
                  </button>
                  <button 
                    @click="updateLessonStatus(selectedLesson.id, 'cancelled')"
                    :class="selectedLesson.status === 'cancelled' ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors hover:opacity-80"
                    :disabled="saving">
                    ✗ Annulé
                  </button>
                </div>
              </div>

              <!-- Notes -->
              <div v-if="selectedLesson.notes" class="bg-gray-50 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-500 mb-1">Notes</label>
                <p class="text-sm text-gray-700">{{ selectedLesson.notes }}</p>
              </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-between gap-3 mt-6 pt-4 border-t">
              <button 
                @click="deleteLesson(selectedLesson.id)"
                :disabled="saving"
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Supprimer
              </button>
              <button 
                @click="closeLessonModal"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                Fermer
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modale Historique complet -->
      <LessonsHistoryModal
        :show="showHistoryModal"
        @close="showHistoryModal = false"
        @view-lesson="handleViewLessonFromHistory"
      />

      <!-- Modale Création de Cours -->
      <CreateLessonModal
        :show="showCreateLessonModal"
        :form="lessonForm"
        :selected-slot="selectedSlotForLesson"
        :teachers="teachers"
        :students="students"
        :course-types="filteredCourseTypes"
        :available-days="availableDaysOfWeek"
        :saving="saving"
        :editing-lesson="editingLesson"
        :open-slots="openSlots"
        @close="closeCreateLessonModal"
        @submit="createLesson"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch, nextTick } from 'vue'
import SlotsList from '~/components/planning/SlotsList.vue'
import DisciplinesList from '~/components/planning/DisciplinesList.vue'
import CreateLessonModal from '~/components/planning/CreateLessonModal.vue'
import LessonsHistoryModal from '~/components/planning/LessonsHistoryModal.vue'

// Composable pour les toasts
const { success, error: showError, warning } = useToast()

definePageMeta({
  middleware: ['auth']
})

// Types
interface Discipline {
  id: number
  activity_type_id: number
  name: string
  description: string | null
  slug: string
  is_active: boolean
}

interface DisciplineSettings {
  duration: number
  price: number
  min_participants: number
  max_participants: number
  notes: string
}

interface ClubDiscipline extends Discipline {
  settings: DisciplineSettings
}

interface CourseType {
  id: number
  name: string
  description: string | null
  discipline_id: number | null
  is_individual: boolean
  max_participants: number | null
  is_active: boolean
  duration?: number
  duration_minutes?: number
  price?: number
}

interface OpenSlot {
  id: number
  club_id: number
  day_of_week: number
  start_time: string
  end_time: string
  discipline_id: number | null
  discipline?: Discipline
  max_capacity: number | null
  max_slots: number | null
  duration: number | null
  price: number | null
  is_active: boolean
  course_types?: CourseType[]
}

interface Lesson {
  id: number
  start_time: string // DateTime ISO string
  end_time: string   // DateTime ISO string
  status: string
  price: number
  teacher?: {
    id: number
    user: {
      name: string
    }
  }
  student?: {
    id: number
    user: {
      name: string
    }
    subscription_instances?: any[]
  }
  students?: Array<{
    id: number
    user: {
      name: string
    }
    subscription_instances?: any[]
  }>
  course_type?: CourseType
  location?: any
  notes?: string
}

// State
const loading = ref(true)
const error = ref<string | null>(null)
const clubDisciplines = ref<ClubDiscipline[]>([])
const openSlots = ref<OpenSlot[]>([])
const lessons = ref<Lesson[]>([])
const showSlotModal = ref(false)
const editingSlot = ref<OpenSlot | null>(null)
const saving = ref(false)
const showLessonModal = ref(false)
const selectedLesson = ref<Lesson | null>(null)
const showCreateLessonModal = ref(false)
const showHistoryModal = ref(false)
const selectedSlotForLesson = ref<OpenSlot | null>(null)
const selectedSlot = ref<OpenSlot | null>(null) // Créneau sélectionné pour filtrage
const selectedDate = ref<Date | null>(null) // Date sélectionnée pour filtrage des cours
const selectedDateInput = ref<string>('') // Input date (format YYYY-MM-DD)
const teachers = ref<any[]>([])
const students = ref<any[]>([])
const courseTypes = ref<any[]>([])
const editingLesson = ref<Lesson | null>(null) // Cours en cours d'édition
const lessonForm = ref({
  teacher_id: null as number | null,
  student_id: null as number | null,
  course_type_id: null as number | null,
  date: '',
  time: '',
  start_time: '',
  duration: 60,
  price: 0,
  notes: '',
  // Champs pour les commissions
  est_legacy: false as boolean | null, // Par défaut DCL (false)
  // Déduction d'abonnement (par défaut true)
  deduct_from_subscription: true as boolean | null
})
const availableDaysOfWeek = ref<number[]>([]) // Jours de la semaine où il y a des créneaux

const slotForm = ref({
  day_of_week: 1,
      start_time: '09:00',
      end_time: '10:00',
  discipline_id: null as number | null,
      duration: 60,
  price: 0,
  max_capacity: 1,
  max_slots: 1,
  is_active: true
})

// Computed : Disciplines actives filtrées pour n'afficher que celles avec des types de cours individuels
const activeDisciplines = computed(() => {
  const active = clubDisciplines.value.filter(d => d.is_active)
  
  // Si on a chargé les types de cours, filtrer pour n'afficher que les disciplines
  // qui ont au moins un type de cours individuel
  if (courseTypes.value.length > 0) {
    return active.filter(discipline => {
      // Trouver les types de cours qui correspondent à cette discipline et qui sont individuels
      const individualTypes = courseTypes.value.filter(ct => 
        ct.discipline_id === discipline.id && ct.is_individual === true
      )
      
      // Garder la discipline seulement si elle a au moins un type individuel
      return individualTypes.length > 0
    })
  }
  
  // Si pas de types de cours chargés, retourner toutes les disciplines actives
  return active
})

// Cours filtrés par créneau sélectionné ET par date
const filteredLessons = computed(() => {
  if (!selectedSlot.value) {
    // Si aucun créneau sélectionné, afficher tous les cours
    return lessons.value
  }
  
  // Filtrer les cours qui correspondent au créneau sélectionné
  return lessons.value.filter(lesson => {
    const lessonDate = new Date(lesson.start_time)
    // JavaScript getDay() retourne 0 (Dim) à 6 (Sam) - correspond à Laravel (0=Dim)
    const lessonDay = lessonDate.getDay()
    
    // 🔧 CORRECTION : Extraire l'heure locale au format "HH:mm"
    // Utiliser les méthodes getHours() et getMinutes() pour éviter les problèmes de format
    const lessonHours = String(lessonDate.getHours()).padStart(2, '0')
    const lessonMinutes = String(lessonDate.getMinutes()).padStart(2, '0')
    const lessonTime = `${lessonHours}:${lessonMinutes}` // Format: "09:00"
    
    // Normaliser les heures du créneau (au cas où elles sont en format "HH:mm:ss")
    const slotStartTime = formatTime(selectedSlot.value!.start_time)
    const slotEndTime = formatTime(selectedSlot.value!.end_time)
    
    const dayMatch = lessonDay === selectedSlot.value!.day_of_week
    const timeMatch = lessonTime >= slotStartTime && lessonTime < slotEndTime
    
    // 📅 FILTRE PAR DATE : Si une date est sélectionnée, ne garder que les cours de cette date
    // ⚠️ IMPORTANT : Comparer les dates en LOCAL, pas en UTC (problème de timezone)
    let dateMatch = true
    if (selectedDate.value) {
      // Extraire la date locale (YYYY-MM-DD) de la date sélectionnée
      const selectedYear = selectedDate.value.getFullYear()
      const selectedMonth = String(selectedDate.value.getMonth() + 1).padStart(2, '0')
      const selectedDay = String(selectedDate.value.getDate()).padStart(2, '0')
      const selectedDateStr = `${selectedYear}-${selectedMonth}-${selectedDay}`
      
      // Extraire la date locale (YYYY-MM-DD) du cours
      const lessonYear = lessonDate.getFullYear()
      const lessonMonth = String(lessonDate.getMonth() + 1).padStart(2, '0')
      const lessonDay = String(lessonDate.getDate()).padStart(2, '0')
      const lessonDateStr = `${lessonYear}-${lessonMonth}-${lessonDay}`
      
      dateMatch = lessonDateStr === selectedDateStr
    }
    
    return dayMatch && timeMatch && dateMatch
  })
})

// Cours groupés par plage horaire pour affichage en grille
const lessonsGroupedByTimeSlot = computed(() => {
  // Grouper les cours par heure de début
  const groups: Record<string, any[]> = {}
  
  filteredLessons.value.forEach(lesson => {
    const date = new Date(lesson.start_time)
    const hours = String(date.getHours()).padStart(2, '0')
    const minutes = String(date.getMinutes()).padStart(2, '0')
    const timeKey = `${hours}:${minutes}`
    
    if (!groups[timeKey]) {
      groups[timeKey] = []
    }
    groups[timeKey].push(lesson)
  })
  
  // Trier par heure et convertir en tableau
  return Object.keys(groups)
    .sort()
    .map(time => ({
      time,
      lessons: groups[time].sort((a, b) => {
        // Trier par nom/prénom de l'enseignant (ordre alphabétique)
        const teacherA = a.teacher?.user?.name || ''
        const teacherB = b.teacher?.user?.name || ''
        
        // Normaliser les noms pour un tri correct (enlever les accents, mettre en majuscules)
        const normalizeName = (name: string) => {
          return name
            .toUpperCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '') // Enlever les accents
        }
        
        return normalizeName(teacherA).localeCompare(normalizeName(teacherB), 'fr')
      })
    }))
})

// Types de cours filtrés - Utilise les courseTypes du créneau sélectionné
// au lieu de filtrer la liste globale (relation directe créneau → types)
const filteredCourseTypes = computed(() => {
  console.log('🔄 [filteredCourseTypes] Computed appelé', {
    hasSlot: !!selectedSlotForLesson.value,
    slotId: selectedSlotForLesson.value?.id,
    slotDisciplineId: selectedSlotForLesson.value?.discipline_id,
    slotHasCourseTypes: !!selectedSlotForLesson.value?.course_types,
    modalOpen: showCreateLessonModal.value,
    clubDisciplinesCount: clubDisciplines.value.length,
    clubDisciplineIds: clubDisciplines.value.map(d => d.id)
  })
  
  // Si la modale n'est pas ouverte, retourner un tableau vide
  if (!showCreateLessonModal.value) {
    console.log('⚠️ [filteredCourseTypes] Modale fermée → tableau vide')
    return []
  }
  
  // Si pas de créneau sélectionné, retourner tableau vide
  if (!selectedSlotForLesson.value) {
    console.log('⚠️ [filteredCourseTypes] Pas de créneau → tableau vide')
    return []
  }
  
  // ✅ Les courseTypes sont déjà filtrés par le backend selon les disciplines du club
  // Le backend (ClubOpenSlotController::index) filtre pour ne garder que :
  // 1. Les types génériques (sans discipline_id)
  // 2. Les types dont la discipline_id est dans les disciplines activées du club
  const slotCourseTypes = selectedSlotForLesson.value.course_types || []
  
  console.log('🎯 [filteredCourseTypes] Types de cours du créneau (déjà filtrés par le backend)', {
    slotId: selectedSlotForLesson.value.id,
    slotDisciplineId: selectedSlotForLesson.value.discipline_id,
    slotDisciplineName: selectedSlotForLesson.value.discipline?.name,
    courseTypesCount: slotCourseTypes.length,
    courseTypes: slotCourseTypes.map(ct => ({ 
      id: ct.id, 
      name: ct.name,
      discipline_id: ct.discipline_id,
      duration: ct.duration || ct.duration_minutes,
      price: ct.price
    }))
  })
  
  // ⚠️ Si aucun type de cours n'est disponible, afficher un avertissement
  if (slotCourseTypes.length === 0) {
    console.warn('⚠️ [filteredCourseTypes] Aucun type de cours disponible !', {
      slotId: selectedSlotForLesson.value.id,
      slotDisciplineId: selectedSlotForLesson.value.discipline_id,
      clubDisciplines: clubDisciplines.value.map(d => ({ id: d.id, name: d.name })),
      message: 'Vérifiez que des types de cours sont associés à ce créneau et correspondent aux disciplines du club'
    })
  }
  
  return slotCourseTypes
})

// Watcher pour initialiser les valeurs quand on sélectionne une discipline
watch(() => slotForm.value.discipline_id, (newDisciplineId) => {
  if (newDisciplineId && !editingSlot.value) {
    // Trouver la discipline sélectionnée
    const selectedDiscipline = clubDisciplines.value.find(d => d.id === newDisciplineId)
    
    if (selectedDiscipline && selectedDiscipline.settings) {
      // Pré-remplir avec les valeurs configurées
      slotForm.value.duration = selectedDiscipline.settings.duration || 60
      slotForm.value.price = selectedDiscipline.settings.price || 0
      slotForm.value.max_capacity = selectedDiscipline.settings.max_participants || 1
      
      console.log('✨ Valeurs initialisées depuis la discipline:', {
        duration: slotForm.value.duration,
        price: slotForm.value.price,
        max_capacity: slotForm.value.max_capacity
      })
    }
  }
})

// Watcher pour pré-remplir durée et prix quand on sélectionne un type de cours
watch(() => lessonForm.value.course_type_id, (newCourseTypeId) => {
  if (newCourseTypeId) {
    // ✅ CORRECTION : Chercher d'abord dans les types de cours filtrés du créneau
    // Si pas trouvé, chercher dans tous les types de cours
    let courseType = filteredCourseTypes.value.find(ct => ct.id === newCourseTypeId)
    if (!courseType) {
      courseType = courseTypes.value.find(ct => ct.id === newCourseTypeId)
    }
    
    if (courseType) {
      // Utiliser duration_minutes en priorité, puis duration
      lessonForm.value.duration = courseType.duration_minutes || courseType.duration || 60
      lessonForm.value.price = courseType.price || 0
      console.log('✨ Durée et prix initialisés depuis type de cours:', {
        name: courseType.name,
        duration: lessonForm.value.duration,
        price: lessonForm.value.price,
        source: filteredCourseTypes.value.find(ct => ct.id === newCourseTypeId) ? 'filtered' : 'all'
      })
    }
  }
})

// Watcher pour réinitialiser le type de cours quand le créneau change
watch(() => selectedSlotForLesson.value, (newSlot, oldSlot) => {
  // Si on change de créneau et que la discipline change
  if (newSlot && oldSlot && newSlot.discipline_id !== oldSlot.discipline_id) {
    // Réinitialiser le type de cours car les options disponibles ont changé
    lessonForm.value.course_type_id = null
    console.log('🔄 Type de cours réinitialisé suite au changement de créneau')
  }
})

// Watcher pour mettre à jour les jours disponibles quand les créneaux changent
watch(openSlots, () => {
  updateAvailableDays()
}, { deep: true })

// Watcher pour combiner date et heure (avec secondes pour Laravel)
watch(() => [lessonForm.value.date, lessonForm.value.time], ([date, time]) => {
  if (date && time) {
    // Ajouter les secondes si elles ne sont pas déjà présentes
    const timeWithSeconds = time.includes(':') && time.split(':').length === 2 
      ? `${time}:00` 
      : time
    lessonForm.value.start_time = `${date}T${timeWithSeconds}`
  }
})

// Fonctions
async function loadClubDisciplines() {
  try {
    loading.value = true
    error.value = null
    
    const { $api } = useNuxtApp()
    const config = useRuntimeConfig()
    
    console.log('🔍 Début du chargement des disciplines...')
    
    // 1. Récupérer le profil du club avec les disciplines configurées
    const profileResponse = await $api.get('/club/profile')
    
    console.log('📥 Réponse profil brute:', profileResponse.data)
    
    if (!profileResponse.data.success || !profileResponse.data.data) {
      throw new Error('Impossible de récupérer le profil du club')
    }
    
    const clubData = profileResponse.data.data
    
    console.log('🏢 Données du club:', {
      id: clubData.id,
      name: clubData.name,
      disciplines_raw: clubData.disciplines,
      disciplines_type: typeof clubData.disciplines,
      discipline_settings_raw: clubData.discipline_settings,
      discipline_settings_type: typeof clubData.discipline_settings
    })
    
    // 2. Récupérer la liste complète des disciplines pour avoir les noms
    const disciplinesResponse = await $fetch(`${config.public.apiBase}/disciplines`)
    const allDisciplines = disciplinesResponse.data || []
    
    console.log('📚 Disciplines disponibles:', allDisciplines.map((d: any) => ({ id: d.id, name: d.name })))
    
    // 3. Parser les données du club
    let clubDisciplineIds = []
    
    if (clubData.disciplines) {
      if (Array.isArray(clubData.disciplines)) {
        clubDisciplineIds = clubData.disciplines
      } else if (typeof clubData.disciplines === 'string') {
        try {
          clubDisciplineIds = JSON.parse(clubData.disciplines)
  } catch (e) {
          console.error('Erreur parsing disciplines:', e)
          clubDisciplineIds = []
        }
      }
    }
    
    let disciplineSettings = {}
    
    if (clubData.discipline_settings) {
      if (typeof clubData.discipline_settings === 'string') {
        try {
          disciplineSettings = JSON.parse(clubData.discipline_settings)
  } catch (e) {
          console.error('Erreur parsing discipline_settings:', e)
          disciplineSettings = {}
        }
      } else if (typeof clubData.discipline_settings === 'object') {
        disciplineSettings = clubData.discipline_settings
      }
    }
    
    console.log('✅ Données parsées:', {
      clubDisciplineIds,
      disciplineSettings
    })
    
    // 4. Construire la liste des disciplines avec leurs settings
    clubDisciplines.value = clubDisciplineIds
      .map((disciplineId: number) => {
        console.log(`🔍 Recherche discipline ID ${disciplineId}...`)
        const discipline = allDisciplines.find((d: Discipline) => d.id === disciplineId)
        
        if (!discipline) {
          console.warn(`❌ Discipline ${disciplineId} non trouvée dans le référentiel`)
          console.log('   IDs disponibles:', allDisciplines.map((d: any) => d.id))
          return null
        }
        
        console.log(`✅ Discipline ${disciplineId} trouvée:`, discipline.name)
        
        const settings = disciplineSettings[disciplineId] || {
          duration: 45,
          price: 25.00,
          min_participants: 1,
          max_participants: 8,
  notes: ''
        }
        
        console.log(`   Settings pour ${discipline.name}:`, settings)
        
      return {
          ...discipline,
          settings
        }
      })
      .filter((d): d is ClubDiscipline => d !== null)
    
    console.log('🎯 RÉSULTAT FINAL:', clubDisciplines.value)
    console.log('📊 Nombre de disciplines actives:', activeDisciplines.value.length)
  } catch (err: any) {
    console.error('❌ ERREUR:', err)
    const errorMessage = err.message || 'Erreur lors du chargement des disciplines'
    error.value = errorMessage
    showError(errorMessage, 'Erreur de chargement')
  } finally {
    loading.value = false
  }
}

// Trouver le créneau le plus proche dans le temps
function findNearestSlot(): OpenSlot | null {
  if (openSlots.value.length === 0) {
    return null
  }
  
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const currentTime = now.getHours() * 60 + now.getMinutes() // Minutes depuis minuit
  
  let nearestSlot: OpenSlot | null = null
  let nearestTime: number | null = null
  
  // Parcourir tous les créneaux actifs
  for (const slot of openSlots.value) {
    if (!slot.is_active) continue
    
    // Calculer la prochaine occurrence de ce créneau
    const slotDayOfWeek = slot.day_of_week
    const todayDayOfWeek = today.getDay()
    
    // Extraire l'heure de début du créneau
    const slotTimeParts = slot.start_time.split(':')
    const slotHour = parseInt(slotTimeParts[0])
    const slotMinute = parseInt(slotTimeParts[1] || '0')
    const slotTime = slotHour * 60 + slotMinute // Minutes depuis minuit
    
    // Calculer combien de jours ajouter pour atteindre le jour du créneau
    let daysToAdd = slotDayOfWeek - todayDayOfWeek
    
    // Si le jour est déjà passé cette semaine, aller à la semaine prochaine
    if (daysToAdd < 0) {
      daysToAdd += 7
    }
    
    // Si c'est aujourd'hui mais l'heure est déjà passée, aller à la semaine prochaine
    if (daysToAdd === 0 && slotTime <= currentTime) {
      daysToAdd = 7
    }
    
    // Calculer la date de la prochaine occurrence
    const nextOccurrenceDate = new Date(today)
    nextOccurrenceDate.setDate(today.getDate() + daysToAdd)
    
    // Calculer le timestamp complet (date + heure)
    const nextOccurrence = new Date(nextOccurrenceDate)
    nextOccurrence.setHours(slotHour, slotMinute, 0, 0)
    
    const timeUntilSlot = nextOccurrence.getTime() - now.getTime()
    
    // Garder le créneau le plus proche dans le futur
    if (timeUntilSlot > 0 && (nearestTime === null || timeUntilSlot < nearestTime)) {
      nearestSlot = slot
      nearestTime = timeUntilSlot
    }
  }
  
  return nearestSlot
}

// Charger les créneaux horaires
async function loadOpenSlots() {
  try {
    const { $api } = useNuxtApp()
    console.log('🔄 [Planning] Chargement des créneaux horaires...')
    
    const response = await $api.get('/club/open-slots')
    
    console.log('📥 [Planning] Réponse API créneaux:', {
      success: response.data.success,
      data_type: typeof response.data.data,
      data_is_array: Array.isArray(response.data.data),
      data_length: Array.isArray(response.data.data) ? response.data.data.length : 'N/A',
      message: response.data.message
    })
    
    if (response.data.success) {
      openSlots.value = Array.isArray(response.data.data) ? response.data.data : []
      console.log('✅ Créneaux chargés:', openSlots.value.length, 'créneaux')
      
      if (openSlots.value.length === 0) {
        console.warn('⚠️ Aucun créneau trouvé pour ce club')
      } else {
        // 🎯 Présélectionner automatiquement le créneau le plus proche
        const nearestSlot = findNearestSlot()
        if (nearestSlot) {
          console.log('🎯 Créneau le plus proche trouvé:', {
            id: nearestSlot.id,
            day: getDayName(nearestSlot.day_of_week),
            time: formatTime(nearestSlot.start_time),
            discipline: nearestSlot.discipline?.name
          })
          handleSlotSelection(nearestSlot)
        } else {
          console.log('⚠️ Aucun créneau actif trouvé pour présélectionner')
        }
      }
      
      // 🔍 DEBUG: Vérifier les course_types dans chaque slot
      openSlots.value.forEach((slot, index) => {
        console.log(`🔍 [Slot ${index + 1}] ID: ${slot.id}`, {
          club_id: slot.club_id,
          day_of_week: slot.day_of_week,
          start_time: slot.start_time,
          end_time: slot.end_time,
          is_active: slot.is_active,
          discipline_id: slot.discipline_id,
          discipline_name: slot.discipline?.name,
          has_course_types: !!slot.course_types,
          course_types_count: slot.course_types?.length || 0,
          course_types: slot.course_types?.map(ct => ({
            id: ct.id,
            name: ct.name,
            duration_minutes: ct.duration_minutes,
            price: ct.price
          })) || []
        })
      })
    } else {
      console.error('❌ Erreur chargement créneaux:', response.data.message)
      openSlots.value = []
    }
  } catch (err: any) {
    console.error('❌ Erreur chargement créneaux:', {
      message: err.message,
      response: err.response?.data,
      status: err.response?.status
    })
    openSlots.value = []
    
    let errorMessage = 'Erreur lors du chargement des créneaux horaires'
    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.message) {
      errorMessage = err.message
    }
    
    showError(errorMessage, 'Erreur de chargement')
  }
}

// Variables pour suivre la plage de dates chargée
const loadedLessonsRange = ref<{ start: Date | null, end: Date | null }>({ start: null, end: null })

// Charger les cours réels
async function loadLessons(customStartDate?: Date, customEndDate?: Date) {
  try {
    const { $api } = useNuxtApp()
    // Charger les cours sur une plage plus large pour couvrir toutes les semaines navigables
    const today = new Date()
    const startDate = customStartDate || new Date(today)
    if (!customStartDate) {
      startDate.setDate(today.getDate() - 7) // 1 semaine en arrière
    }
    const endDate = customEndDate || new Date(today)
    if (!customEndDate) {
      endDate.setDate(today.getDate() + 180) // 6 mois en avant pour couvrir toutes les récurrences
    }
    
    const response = await $api.get('/lessons', {
      params: {
        date_from: startDate.toISOString().split('T')[0],
        date_to: endDate.toISOString().split('T')[0],
        limit: 200 // Augmenter la limite pour inclure tous les cours générés
      }
    })
    
    if (response.data.success) {
      // Si on recharge une plage spécifique, fusionner avec les cours existants
      if (customStartDate || customEndDate) {
        const newLessons = response.data.data
        const existingLessonIds = new Set(lessons.value.map((l: any) => l.id))
        const lessonsToAdd = newLessons.filter((l: any) => !existingLessonIds.has(l.id))
        lessons.value = [...lessons.value, ...lessonsToAdd]
        console.log('✅ Cours fusionnés:', { 
          nouveaux: lessonsToAdd.length, 
          total: lessons.value.length 
        })
      } else {
        lessons.value = response.data.data
        console.log('✅ Cours chargés:', lessons.value)
      }
      
      // Mettre à jour la plage chargée
      loadedLessonsRange.value = {
        start: new Date(startDate),
        end: new Date(endDate)
      }
      
      console.log('📊 Nombre total de cours:', lessons.value.length)
      console.log('📋 Plage chargée:', {
        start: loadedLessonsRange.value.start?.toISOString().split('T')[0],
        end: loadedLessonsRange.value.end?.toISOString().split('T')[0]
      })
      console.log('📋 IDs des cours reçus:', lessons.value.map((l: any) => l.id).join(', '))
      // Debug: Afficher le statut de chaque cours avec les élèves
      lessons.value.forEach((lesson: any, index: number) => {
        console.log(`  Cours ${index + 1}:`, {
          id: lesson.id,
          status: lesson.status,
          course_type: lesson.course_type?.name,
          start_time: lesson.start_time,
          student_id: lesson.student_id,
          student: lesson.student ? {
            id: lesson.student.id,
            name: lesson.student.user?.name
          } : null,
          students: lesson.students ? lesson.students.map((s: any) => ({
            id: s.id,
            name: s.user?.name
          })) : []
        })
      })
      
      // Vérifier spécifiquement les cours du 29/11
      const lessonsNov29 = lessons.value.filter((l: any) => {
        if (!l.start_time) return false
        const date = new Date(l.start_time)
        return date.getDate() === 29 && date.getMonth() === 10 && date.getFullYear() === 2025
      })
      console.log('🔍 Cours du 29/11 trouvés:', lessonsNov29.length, lessonsNov29.map((l: any) => ({ id: l.id, start_time: l.start_time })))
    } else {
      console.error('Erreur chargement cours:', response.data.message)
    }
  } catch (err: any) {
    console.error('Erreur chargement cours:', err)
    
    let errorMessage = 'Erreur lors du chargement des cours'
    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.message) {
      errorMessage = err.message
    }
    
    showError(errorMessage, 'Erreur de chargement')
  }
}

// Charger les enseignants du club
async function loadTeachers() {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/teachers')
    console.log('🔍 [Planning] Réponse enseignants:', response.data)
    if (response.data.success) {
      // La clé est 'teachers' et non 'data' (voir ClubController::getTeachers)
      teachers.value = response.data.teachers || response.data.data || []
      console.log('✅ Enseignants chargés:', teachers.value.length)
    }
  } catch (err: any) {
    console.error('Erreur chargement enseignants:', err)
    
    let errorMessage = 'Erreur lors du chargement des enseignants'
    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.message) {
      errorMessage = err.message
    }
    
    showError(errorMessage, 'Erreur de chargement')
  }
}

// Charger les élèves du club
async function loadStudents() {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/students')
    console.log('🔍 [Planning] Réponse élèves:', response.data)
    if (response.data.success) {
      students.value = response.data.data || []
      console.log('✅ Élèves chargés:', students.value.length)
    }
  } catch (err: any) {
    console.error('Erreur chargement élèves:', err)
    
    let errorMessage = 'Erreur lors du chargement des élèves'
    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.message) {
      errorMessage = err.message
    }
    
    showError(errorMessage, 'Erreur de chargement')
  }
}

async function loadCourseTypes() {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/course-types')
    
    if (response.data.success) {
      courseTypes.value = response.data.data
      console.log('✅ Types de cours chargés:', courseTypes.value.length)
      console.log('📋 Détail des types de cours:', courseTypes.value.map(ct => ({
        id: ct.id,
        name: ct.name,
        discipline_id: ct.discipline_id,
        duration_minutes: ct.duration_minutes,
        price: ct.price
      })))
    }
  } catch (err: any) {
    console.error('Erreur chargement types de cours:', err)
    
    let errorMessage = 'Erreur lors du chargement des types de cours'
    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.message) {
      errorMessage = err.message
    }
    
    showError(errorMessage, 'Erreur de chargement')
  }
}

// Calculer les jours de la semaine disponibles basés sur les créneaux
function updateAvailableDays() {
  const days = new Set<number>()
  openSlots.value.forEach(slot => {
    if (slot.is_active) {
      days.add(slot.day_of_week)
    }
  })
  availableDaysOfWeek.value = Array.from(days).sort()
  console.log('📅 Jours disponibles:', availableDaysOfWeek.value)
}

// Vérifier si une date correspond à un jour disponible
function isDateAvailable(dateStr: string): boolean {
  if (!dateStr) return false
  const date = new Date(dateStr)
  const dayOfWeek = date.getDay()
  return availableDaysOfWeek.value.includes(dayOfWeek)
}

// Gestion de la modale
async function openSlotModal(slot?: OpenSlot) {
  if (slot) {
    // Recharger le slot depuis la DB pour avoir le statut actuel
    try {
      const { $api } = useNuxtApp()
      console.log('🔄 [openSlotModal] Rechargement du créneau depuis la DB:', slot.id)
      
      const response = await $api.get(`/club/open-slots/${slot.id}`)
      
      if (response.data.success && response.data.data) {
        const freshSlot = response.data.data
        console.log('✅ [openSlotModal] Créneau rechargé depuis la DB:', {
          id: freshSlot.id,
          is_active: freshSlot.is_active
        })
        
        editingSlot.value = freshSlot
        slotForm.value = {
          day_of_week: freshSlot.day_of_week,
          start_time: formatTime(freshSlot.start_time),
          end_time: formatTime(freshSlot.end_time),
          discipline_id: freshSlot.discipline_id,
          duration: freshSlot.duration || 60,
          price: freshSlot.price || 0,
          max_capacity: freshSlot.max_capacity || 1,
          max_slots: freshSlot.max_slots || 1,
          is_active: freshSlot.is_active ?? true // Utiliser le statut de la DB
        }
      } else {
        // Fallback : utiliser le slot passé en paramètre si le rechargement échoue
        console.warn('⚠️ [openSlotModal] Échec rechargement, utilisation du slot local')
        editingSlot.value = slot
        slotForm.value = {
          day_of_week: slot.day_of_week,
          start_time: formatTime(slot.start_time),
          end_time: formatTime(slot.end_time),
          discipline_id: slot.discipline_id,
          duration: slot.duration || 60,
          price: slot.price || 0,
          max_capacity: slot.max_capacity || 1,
          max_slots: slot.max_slots || 1,
          is_active: slot.is_active ?? true
        }
      }
    } catch (error: any) {
      console.error('❌ [openSlotModal] Erreur lors du rechargement du créneau:', error)
      
      // Afficher un avertissement mais continuer avec le slot local
      warning('Impossible de recharger le créneau depuis le serveur. Utilisation des données locales.', 'Avertissement')
      
      // Fallback : utiliser le slot passé en paramètre
      editingSlot.value = slot
      slotForm.value = {
        day_of_week: slot.day_of_week,
        start_time: formatTime(slot.start_time),
        end_time: formatTime(slot.end_time),
        discipline_id: slot.discipline_id,
        duration: slot.duration || 60,
        price: slot.price || 0,
        max_capacity: slot.max_capacity || 1,
        max_slots: slot.max_slots || 1,
        is_active: slot.is_active ?? true
      }
    }
  } else {
    editingSlot.value = null
    slotForm.value = {
      day_of_week: 1,
      start_time: '09:00',
      end_time: '10:00',
      discipline_id: null,
      duration: 60,
      price: 0,
      max_capacity: 1,
      max_slots: 1,
      is_active: true
    }
  }
  showSlotModal.value = true
}

function closeSlotModal() {
  showSlotModal.value = false
  editingSlot.value = null
}

async function saveSlot() {
  try {
    saving.value = true
    const { $api } = useNuxtApp()
    
    // S'assurer que is_active est toujours un booléen (pas undefined)
    const isActive = slotForm.value.is_active !== undefined ? Boolean(slotForm.value.is_active) : true
    
    const payload = {
      day_of_week: slotForm.value.day_of_week,
      start_time: slotForm.value.start_time,
      end_time: slotForm.value.end_time,
      discipline_id: slotForm.value.discipline_id,
      duration: slotForm.value.duration,
      price: slotForm.value.price,
      max_capacity: slotForm.value.max_capacity,
      max_slots: slotForm.value.max_slots,
      is_active: isActive
    }
    
    console.log('💾 [saveSlot] Envoi du payload:', {
      ...payload,
      is_active_type: typeof payload.is_active,
      is_active_value: payload.is_active
    })
    
    if (editingSlot.value) {
      // Mise à jour
      const response = await $api.put(`/club/open-slots/${editingSlot.value.id}`, payload)
      console.log('✅ Créneau mis à jour:', response.data)
    } else {
      // Création
      const response = await $api.post('/club/open-slots', payload)
      console.log('✅ Créneau créé:', response.data)
    }
    
    // Recharger la liste
    await loadOpenSlots()
    success(editingSlot.value ? 'Créneau mis à jour avec succès' : 'Créneau créé avec succès', 'Succès')
    closeSlotModal()
  } catch (err: any) {
    console.error('Erreur sauvegarde créneau:', err)
    console.error('Détails de l\'erreur:', {
      message: err.message,
      response: err.response?.data,
      status: err.response?.status
    })
    
    let errorMessage = 'Erreur lors de la sauvegarde du créneau'
    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
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
    } else if (err.message) {
      errorMessage = err.message
    }
    
    showError(errorMessage, 'Erreur de sauvegarde')
  } finally {
    saving.value = false
  }
}

async function deleteSlot(id: number) {
  if (!confirm('Êtes-vous sûr de vouloir supprimer ce créneau ?')) {
    return
  }
  
  try {
    const { $api } = useNuxtApp()
    await $api.delete(`/club/open-slots/${id}`)
    console.log('✅ Créneau supprimé')
    
    // Recharger la liste
    await loadOpenSlots()
    success('Créneau supprimé avec succès', 'Succès')
  } catch (err: any) {
    console.error('Erreur suppression créneau:', err)
    
    let errorMessage = 'Erreur lors de la suppression du créneau'
    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.message) {
      errorMessage = err.message
    }
    
    showError(errorMessage, 'Erreur de suppression')
  }
}

async function openCreateLessonModal(slot?: OpenSlot) {
  console.log('📝 [openCreateLessonModal] DÉBUT - Avant mise à jour selectedSlotForLesson', {
    hasSlot: !!slot,
    slotId: slot?.id,
    slotDisciplineId: slot?.discipline_id,
    slotDisciplineName: slot?.discipline?.name,
    slotHasCourseTypes: !!slot?.course_types,
    slotCourseTypesCount: slot?.course_types?.length || 0,
    slotCourseTypes: slot?.course_types?.map(ct => ct.name) || [],
    totalCourseTypes: courseTypes.value.length,
    currentSelectedSlot: selectedSlotForLesson.value?.id
  })
  
  selectedSlotForLesson.value = slot || null
  
  console.log('📝 [openCreateLessonModal] APRÈS mise à jour selectedSlotForLesson', {
    newSelectedSlotId: selectedSlotForLesson.value?.id,
    newSelectedSlotDisciplineId: selectedSlotForLesson.value?.discipline_id,
    newSelectedSlotHasCourseTypes: !!selectedSlotForLesson.value?.course_types,
    newSelectedSlotCourseTypesCount: selectedSlotForLesson.value?.course_types?.length || 0
  })
  
  // Ouvrir la modale AVANT d'initialiser le formulaire pour que filteredCourseTypes soit calculé
  showCreateLessonModal.value = true
  
  // Utiliser nextTick pour s'assurer que le computed filteredCourseTypes est recalculé
  await nextTick()
  
  if (slot) {
    // Calculer la prochaine date correspondant au jour du créneau
    const today = new Date()
    const targetDay = slot.day_of_week
    const daysUntilTarget = (targetDay - today.getDay() + 7) % 7
    const nextDate = new Date(today)
    nextDate.setDate(today.getDate() + (daysUntilTarget === 0 ? 7 : daysUntilTarget))
    
    const dateStr = nextDate.toISOString().split('T')[0]
    const timeStr = slot.start_time.substring(0, 5)
    
    // ✅ CORRECTION : Utiliser les types de cours du créneau (slot.course_types) au lieu de tous les types
    // Les types de cours du créneau sont déjà filtrés par le backend selon la discipline
    let courseTypeId = null
    let initialDuration = slot.duration || 60
    let initialPrice = slot.price || 0
    
    // Utiliser les types de cours du créneau s'ils sont disponibles
    const slotCourseTypes = slot.course_types || []
    if (slotCourseTypes.length > 0) {
      // Prendre le premier type de cours du créneau (ou celui qui correspond à la discipline)
      const matchingCourseType = slotCourseTypes.find(ct => 
        ct.discipline_id === slot.discipline_id || !ct.discipline_id
      ) || slotCourseTypes[0]
      
      if (matchingCourseType) {
        courseTypeId = matchingCourseType.id
        // Utiliser la durée et le prix du type de cours si disponibles
        initialDuration = matchingCourseType.duration_minutes || matchingCourseType.duration || initialDuration
        initialPrice = matchingCourseType.price || initialPrice
      }
      
      console.log('🔍 Recherche type de cours pour discipline', slot.discipline_id, ':', {
        found: !!matchingCourseType,
        selectedId: courseTypeId,
        selectedName: matchingCourseType?.name,
        slotCourseTypes: slotCourseTypes.map(ct => ({ id: ct.id, name: ct.name, discipline_id: ct.discipline_id })),
        allTypes: courseTypes.value.map(ct => ({ id: ct.id, name: ct.name, discipline_id: ct.discipline_id }))
      })
    } else {
      // Fallback : chercher dans tous les types de cours si le créneau n'a pas de types
      if (slot.discipline_id) {
        const matchingCourseType = courseTypes.value.find(ct => ct.discipline_id === slot.discipline_id)
        if (matchingCourseType) {
          courseTypeId = matchingCourseType.id
          initialDuration = matchingCourseType.duration_minutes || matchingCourseType.duration || initialDuration
          initialPrice = matchingCourseType.price || initialPrice
        }
        console.log('⚠️ [openCreateLessonModal] Aucun type de cours dans le créneau, recherche dans tous les types:', {
          found: !!matchingCourseType,
          selectedId: courseTypeId
        })
      }
    }
    
    lessonForm.value = {
      teacher_id: null,
      student_id: null,
      course_type_id: courseTypeId,
      date: dateStr,
      time: timeStr,
      start_time: `${dateStr}T${timeStr}:00`, // Format avec secondes pour Laravel
      duration: initialDuration,
      price: initialPrice,
      notes: '',
      // Champs pour les commissions (par défaut DCL)
      est_legacy: false,
      // Déduction d'abonnement (par défaut true)
      deduct_from_subscription: true
    }
  } else {
    // Réinitialiser le formulaire
    lessonForm.value = {
      teacher_id: null,
      student_id: null,
      course_type_id: null,
      date: '',
      time: '',
      start_time: '',
      duration: 60,
      price: 0,
      notes: '',
      // Champs pour les commissions (par défaut DCL)
      est_legacy: false,
      // Déduction d'abonnement (par défaut true)
      deduct_from_subscription: true
    }
  }
}

function closeCreateLessonModal() {
  console.log('🚪 [closeCreateLessonModal] Fermeture modale')
  showCreateLessonModal.value = false
  
  // Si on était en mode édition, utiliser closeEditLessonModal
  if (editingLesson.value) {
    closeEditLessonModal()
    return
  }
  
  // Réinitialiser le formulaire
  lessonForm.value = {
    teacher_id: null,
    student_id: null,
    course_type_id: null,
    date: '',
    time: '',
    start_time: '',
    duration: 60,
    price: 0,
    notes: '',
    est_legacy: false,
    deduct_from_subscription: true
  }
  
  // Ne pas réinitialiser selectedSlotForLesson immédiatement pour éviter
  // que le computed retourne tous les types pendant la fermeture
  setTimeout(() => {
    selectedSlotForLesson.value = null
    console.log('🧹 [closeCreateLessonModal] selectedSlotForLesson réinitialisé après délai')
  }, 100)
}

// Ouvrir la modale d'édition d'un cours
async function openEditLessonModal(lesson: Lesson) {
  editingLesson.value = lesson
  
  console.log('📝 [openEditLessonModal] Chargement des données du cours:', {
    id: lesson.id,
    start_time: lesson.start_time,
    course_type: lesson.course_type,
    est_legacy: (lesson as any).est_legacy,
    subscription_instances: (lesson as any).subscription_instances,
    teacher: lesson.teacher
  })
  
  // Extraire la date et l'heure depuis start_time
  if (lesson.start_time) {
    const dateTime = new Date(lesson.start_time)
    lessonForm.value.date = dateTime.toISOString().split('T')[0]
    const hours = String(dateTime.getHours()).padStart(2, '0')
    const minutes = String(dateTime.getMinutes()).padStart(2, '0')
    lessonForm.value.time = `${hours}:${minutes}`
    console.log('📅 [openEditLessonModal] Date et heure extraites:', {
      date: lessonForm.value.date,
      time: lessonForm.value.time,
      start_time: lesson.start_time
    })
    
    // Trouver le créneau correspondant au jour de la semaine pour charger les heures disponibles
    const dayOfWeek = dateTime.getDay() // 0 = dimanche, 1 = lundi, etc.
    const matchingSlot = openSlots.value.find(slot => slot.day_of_week === dayOfWeek)
    if (matchingSlot) {
      selectedSlotForLesson.value = matchingSlot
      console.log('🎯 [openEditLessonModal] Créneau trouvé pour le jour:', {
        day_of_week: dayOfWeek,
        slot_id: matchingSlot.id,
        slot_start: matchingSlot.start_time,
        slot_end: matchingSlot.end_time
      })
    } else {
      selectedSlotForLesson.value = null
      console.warn('⚠️ [openEditLessonModal] Aucun créneau trouvé pour le jour:', dayOfWeek)
    }
  }
  
  // Remplir les autres champs
  lessonForm.value.teacher_id = lesson.teacher?.id || null
  lessonForm.value.student_id = lesson.student?.id || (lesson.students && lesson.students.length > 0 ? lesson.students[0].id : null)
  lessonForm.value.course_type_id = lesson.course_type?.id || null
  
  // Calculer la durée en minutes
  if (lesson.start_time && lesson.end_time) {
    const start = new Date(lesson.start_time)
    const end = new Date(lesson.end_time)
    lessonForm.value.duration = Math.round((end.getTime() - start.getTime()) / (1000 * 60))
  }
  
  lessonForm.value.price = lesson.price || 0
  lessonForm.value.notes = lesson.notes || ''
  
  // DCL/NDCL : est_legacy = false pour DCL, true pour NDCL
  lessonForm.value.est_legacy = (lesson as any).est_legacy !== undefined ? Boolean((lesson as any).est_legacy) : false
  console.log('🏷️ [openEditLessonModal] Classification chargée:', {
    est_legacy: lessonForm.value.est_legacy,
    label: lessonForm.value.est_legacy ? 'NDCL' : 'DCL',
    raw_value: (lesson as any).est_legacy
  })
  
  // Déduction d'abonnement : utiliser directement le champ du cours, sinon vérifier les abonnements liés
  if ((lesson as any).deduct_from_subscription !== undefined) {
    lessonForm.value.deduct_from_subscription = Boolean((lesson as any).deduct_from_subscription)
  } else {
    // Fallback : vérifier si le cours a des abonnements liés
    const hasSubscriptionInstances = (lesson as any).subscription_instances && Array.isArray((lesson as any).subscription_instances) && (lesson as any).subscription_instances.length > 0
    lessonForm.value.deduct_from_subscription = hasSubscriptionInstances
  }
  console.log('💳 [openEditLessonModal] Déduction d\'abonnement chargée:', {
    deduct_from_subscription: lessonForm.value.deduct_from_subscription,
    raw_value: (lesson as any).deduct_from_subscription,
    has_subscription_instances: (lesson as any).subscription_instances?.length > 0
  })
  
  showCreateLessonModal.value = true
  
  // Attendre un tick pour que le composant soit monté et charger les cours existants pour la date
  await nextTick()
  if (lessonForm.value.date && selectedSlotForLesson.value) {
    // Le watcher dans CreateLessonModal chargera automatiquement les cours existants
  }
}

// Fermer la modale d'édition
function closeEditLessonModal() {
  editingLesson.value = null
  selectedSlotForLesson.value = null
  showCreateLessonModal.value = false
  // Réinitialiser le formulaire
  lessonForm.value = {
    teacher_id: null,
    student_id: null,
    course_type_id: null,
    date: '',
    time: '',
    start_time: '',
    duration: 60,
    price: 0,
    notes: '',
    est_legacy: false,
    deduct_from_subscription: true
  }
}

// Gestion de la sélection de créneau
function handleSlotSelection(slot: OpenSlot) {
  console.log('🎯 [handleSlotSelection] Créneau sélectionné:', slot.id)
  selectedSlot.value = slot
  
  // 📅 Initialiser la date à la prochaine occurrence du créneau
  selectedDate.value = getNextOccurrence(slot.day_of_week)
  selectedDateInput.value = formatDateForInput(selectedDate.value)
  
  // Fermer automatiquement le dropdown SlotsList
  // (géré par le composant lui-même via isOpen = false)
}

async function createLesson() {
  // Si on est en mode édition, utiliser updateLesson
  if (editingLesson.value) {
    return updateLesson()
  }
  
  try {
    saving.value = true
    const { $api } = useNuxtApp()
    
    // 🔍 DEBUG : Afficher l'état du formulaire
    console.log('🔍 [createLesson] État du formulaire:', {
      teacher_id: lessonForm.value.teacher_id,
      teacher_id_type: typeof lessonForm.value.teacher_id,
      student_id: lessonForm.value.student_id,
      course_type_id: lessonForm.value.course_type_id,
      date: lessonForm.value.date,
      time: lessonForm.value.time,
      duration: lessonForm.value.duration,
      price: lessonForm.value.price,
      fullForm: JSON.parse(JSON.stringify(lessonForm.value))
    })
    
    // Validations
    const validationErrors = []
    
    if (!lessonForm.value.teacher_id) {
      console.error('❌ [createLesson] teacher_id est vide:', lessonForm.value.teacher_id)
      validationErrors.push('Veuillez sélectionner un enseignant')
    }
    
    if (!lessonForm.value.course_type_id) {
      validationErrors.push('Veuillez sélectionner un type de cours')
    }
    
    if (!lessonForm.value.date || !lessonForm.value.time) {
      validationErrors.push('Veuillez sélectionner une date et une heure')
    }
    
    // Vérifier que la date correspond à un jour disponible
    if (lessonForm.value.date && !isDateAvailable(lessonForm.value.date)) {
      validationErrors.push('Cette date ne correspond à aucun créneau disponible pour ce jour de la semaine')
    }
    
    // Vérifier la durée
    if (!lessonForm.value.duration || lessonForm.value.duration < 15) {
      validationErrors.push('La durée du cours doit être d\'au moins 15 minutes')
    }
    
    // Vérifier le prix
    if (lessonForm.value.price === null || lessonForm.value.price === undefined || lessonForm.value.price < 0) {
      validationErrors.push('Le prix du cours doit être un nombre positif')
    }
    
    // Vérifier que le type de cours correspond à la discipline du créneau
    // ⚠️ NOTE : Pour l'instant, les course_types ont tous discipline_id = NULL
    // Cette validation est donc désactivée car elle bloquerait toujours
    // TODO : Activer quand les course_types auront leurs discipline_id correctement renseignés
    /*
    if (selectedSlotForLesson.value && lessonForm.value.course_type_id) {
      const selectedCourseType = courseTypes.value.find(ct => ct.id === lessonForm.value.course_type_id)
      // Vérifier uniquement si le course_type a un discipline_id défini (pas NULL)
      if (selectedCourseType && selectedCourseType.discipline_id && selectedCourseType.discipline_id !== selectedSlotForLesson.value.discipline_id) {
        validationErrors.push('Le type de cours sélectionné ne correspond pas à la discipline du créneau')
      }
    }
    */
    
    // Afficher les erreurs s'il y en a
    if (validationErrors.length > 0) {
      warning(validationErrors.join('\n'), 'Erreurs de validation')
      return
    }
    
    // Formater start_time correctement avec les secondes pour Laravel
    let startTime = lessonForm.value.start_time
    // Toujours construire depuis date et time pour garantir le bon format
    if (lessonForm.value.date && lessonForm.value.time) {
      const timeStr = lessonForm.value.time.includes(':') && lessonForm.value.time.split(':').length === 2
        ? `${lessonForm.value.time}:00`
        : lessonForm.value.time
      startTime = `${lessonForm.value.date}T${timeStr}`
    } else if (startTime && startTime.match(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/)) {
      // Si le format est YYYY-MM-DDTHH:mm (sans secondes), ajouter les secondes
      startTime = `${startTime}:00`
    }
    
    const payload = {
      teacher_id: lessonForm.value.teacher_id,
      student_id: lessonForm.value.student_id,
      course_type_id: lessonForm.value.course_type_id,
      start_time: startTime,
      duration: lessonForm.value.duration,
      price: lessonForm.value.price,
      notes: lessonForm.value.notes,
      // Champs pour les commissions
      // DCL = false, NDCL = true
      // Convertir explicitement en boolean pour garantir la bonne valeur
      est_legacy: Boolean(lessonForm.value.est_legacy === true || lessonForm.value.est_legacy === 'true'),
      // Déduction d'abonnement (par défaut true)
      deduct_from_subscription: lessonForm.value.deduct_from_subscription !== false
    }
    
    console.log('📤 Création du cours avec payload:', payload)
    
    const response = await $api.post('/lessons', payload)
    
    if (response.data.success) {
      console.log('✅ Cours créé:', response.data.data)
      success('Cours créé avec succès', 'Succès')
      await loadLessons()
      closeCreateLessonModal()
    } else {
      showError(response.data.message || 'Erreur lors de la création du cours', 'Erreur')
    }
  } catch (err: any) {
    console.error('Erreur création cours:', err)
    
    // Gérer les différents types d'erreurs
    let errorMessage = 'Erreur lors de la création du cours'
    
    if (err.response?.data?.message) {
      // Message d'erreur direct (conflit horaire, capacité, etc.)
      errorMessage = err.response.data.message
    } else if (err.response?.data?.errors) {
      // Erreurs de validation Laravel
      const errors = err.response.data.errors
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
    } else if (err.message) {
      errorMessage = err.message
    }
    
    showError(errorMessage, 'Erreur de création')
  } finally {
    saving.value = false
  }
}

// Mettre à jour un cours existant
async function updateLesson() {
  if (!editingLesson.value || saving.value) return
  
  try {
    saving.value = true
    const { $api } = useNuxtApp()
    
    // Validation
    const validationErrors: string[] = []
    
    if (!lessonForm.value.teacher_id) {
      validationErrors.push('Veuillez sélectionner un enseignant')
    }
    if (!lessonForm.value.course_type_id) {
      validationErrors.push('Veuillez sélectionner un type de cours')
    }
    if (!lessonForm.value.date) {
      validationErrors.push('Veuillez sélectionner une date')
    }
    if (!lessonForm.value.time) {
      validationErrors.push('Veuillez sélectionner une heure')
    }
    
    // Afficher les erreurs s'il y en a
    if (validationErrors.length > 0) {
      warning(validationErrors.join('\n'), 'Erreurs de validation')
      return
    }
    
    // Formater start_time et end_time
    let startTime = ''
    let endTime = ''
    if (lessonForm.value.date && lessonForm.value.time) {
      const timeStr = lessonForm.value.time.includes(':') && lessonForm.value.time.split(':').length === 2
        ? `${lessonForm.value.time}:00`
        : lessonForm.value.time
      startTime = `${lessonForm.value.date}T${timeStr}`
      
      // Calculer end_time depuis start_time et duration
      // Utiliser la même approche que pour start_time pour éviter les problèmes de timezone
      const [hours, minutes] = lessonForm.value.time.split(':').map(Number)
      const [year, month, day] = lessonForm.value.date.split('-').map(Number)
      
      // Créer une date locale (pas UTC) pour éviter les décalages de timezone
      const startDate = new Date(year, month - 1, day, hours, minutes, 0)
      const endDate = new Date(startDate.getTime() + lessonForm.value.duration * 60000)
      
      // Formater end_time au format attendu par le backend (YYYY-MM-DD HH:mm:ss avec espace)
      const endYear = endDate.getFullYear()
      const endMonth = String(endDate.getMonth() + 1).padStart(2, '0')
      const endDay = String(endDate.getDate()).padStart(2, '0')
      const endHours = String(endDate.getHours()).padStart(2, '0')
      const endMinutes = String(endDate.getMinutes()).padStart(2, '0')
      const endSeconds = String(endDate.getSeconds()).padStart(2, '0')
      
      endTime = `${endYear}-${endMonth}-${endDay} ${endHours}:${endMinutes}:${endSeconds}`
    }
    
    const payload: any = {
      teacher_id: lessonForm.value.teacher_id,
      student_id: lessonForm.value.student_id,
      course_type_id: lessonForm.value.course_type_id,
      start_time: startTime,
      duration: lessonForm.value.duration,
      price: typeof lessonForm.value.price === 'string' ? parseFloat(lessonForm.value.price) : lessonForm.value.price,
      notes: lessonForm.value.notes,
      est_legacy: Boolean(lessonForm.value.est_legacy === true || lessonForm.value.est_legacy === 'true'),
      deduct_from_subscription: lessonForm.value.deduct_from_subscription !== false
    }
    
    // Ajouter end_time seulement s'il est défini et valide (après start_time)
    if (endTime) {
      // Vérifier que end_time est après start_time en comparant les dates
      const startDateObj = new Date(startTime)
      const endDateObj = new Date(endTime.replace(' ', 'T')) // Convertir pour la comparaison
      if (endDateObj > startDateObj) {
        payload.end_time = endTime
      } else {
        console.warn('⚠️ [updateLesson] end_time calculé incorrectement, omis du payload:', {
          start_time: startTime,
          end_time: endTime,
          duration: lessonForm.value.duration
        })
      }
    }
    
    console.log('📤 Mise à jour du cours avec payload:', payload)
    
    const response = await $api.put(`/lessons/${editingLesson.value.id}`, payload)
    
    if (response.data.success) {
      console.log('✅ Cours mis à jour:', response.data.data)
      success('Cours modifié avec succès', 'Succès')
      
      // Mettre à jour la relation abonnement si nécessaire
      if (editingLesson.value.id) {
        try {
          await $api.put(`/lessons/${editingLesson.value.id}/subscription`, {
            deduct_from_subscription: lessonForm.value.deduct_from_subscription !== false
          })
        } catch (subErr) {
          console.warn('Erreur lors de la mise à jour de la relation abonnement:', subErr)
        }
      }
      
      await loadLessons()
      closeEditLessonModal()
    } else {
      showError(response.data.message || 'Erreur lors de la modification du cours', 'Erreur')
    }
  } catch (err: any) {
    console.error('Erreur modification cours:', err)
    
    let errorMessage = 'Erreur lors de la modification du cours'
    
    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
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
    } else if (err.message) {
      errorMessage = err.message
    }
    
    showError(errorMessage, 'Erreur de modification')
  } finally {
    saving.value = false
  }
}

// Gestion de la modale de cours
function openLessonModal(lesson: Lesson) {
  selectedLesson.value = lesson
  showLessonModal.value = true
}

function closeLessonModal() {
  showLessonModal.value = false
  selectedLesson.value = null
}

function handleViewLessonFromHistory(lesson: any) {
  // Trouver le cours dans la liste locale ou le charger
  const existingLesson = lessons.value.find((l: any) => l.id === lesson.id)
  if (existingLesson) {
    selectedLesson.value = existingLesson
    showLessonModal.value = true
  } else {
    // Si le cours n'est pas dans la liste locale, l'ajouter temporairement
    selectedLesson.value = lesson
    showLessonModal.value = true
  }
}

async function updateLessonStatus(lessonId: number, newStatus: string) {
  try {
    saving.value = true
    const { $api } = useNuxtApp()
    
    const response = await $api.put(`/lessons/${lessonId}`, {
      status: newStatus
    })
    
    if (response.data.success) {
      success('Statut du cours mis à jour avec succès', 'Succès')
      // Recharger les cours
      await loadLessons()
      closeLessonModal()
    } else {
      showError(response.data.message || 'Erreur lors de la mise à jour du statut', 'Erreur')
    }
  } catch (err: any) {
    console.error('Erreur mise à jour cours:', err)
    
    let errorMessage = 'Erreur lors de la mise à jour du statut'
    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.message) {
      errorMessage = err.message
    }
    
    showError(errorMessage, 'Erreur')
  } finally {
    saving.value = false
  }
}

async function deleteLesson(lessonId: number) {
  if (!confirm('Êtes-vous sûr de vouloir supprimer ce cours ?')) return
  
  try {
    saving.value = true
    const { $api } = useNuxtApp()
    
    const response = await $api.delete(`/lessons/${lessonId}`)
    
    if (response.data.success) {
      success('Cours supprimé avec succès', 'Succès')
      await loadLessons()
      closeLessonModal()
    } else {
      showError(response.data.message || 'Erreur lors de la suppression', 'Erreur')
    }
  } catch (err: any) {
    console.error('Erreur suppression cours:', err)
    
    let errorMessage = 'Erreur lors de la suppression du cours'
    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.message) {
      errorMessage = err.message
    }
    
    showError(errorMessage, 'Erreur')
  } finally {
    saving.value = false
  }
}

// Fonctions utilitaires
function getDayName(dayNumber: number): string {
  const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
  return days[dayNumber] || 'Inconnu'
}

function formatTime(time: string): string {
  if (!time) return ''
  // Si le format est HH:MM:SS, on prend seulement HH:MM
  return time.substring(0, 5)
}

function formatPrice(price: any): string {
  const numPrice = typeof price === 'string' ? parseFloat(price) : price
  return isNaN(numPrice) ? '0.00' : numPrice.toFixed(2)
}

function formatLessonDate(datetime: string): string {
  const date = new Date(datetime)
  return date.toLocaleDateString('fr-FR', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

function formatLessonTime(datetime: string): string {
  const date = new Date(datetime)
  return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    'confirmed': '✓ Confirmé',
    'pending': '⏳ En attente',
    'cancelled': '✗ Annulé',
    'completed': '✓ Terminé'
  }
  return labels[status] || status
}

function getStatusBadgeClass(status: string): string {
  const classes: Record<string, string> = {
    'confirmed': 'bg-green-100 text-green-800',
    'pending': 'bg-yellow-100 text-yellow-800',
    'cancelled': 'bg-red-100 text-red-800',
    'completed': 'bg-gray-100 text-gray-600'
  }
  return classes[status] || 'bg-blue-100 text-blue-800'
}

function getLessonBorderClass(lesson: Lesson): string {
  const classes: Record<string, string> = {
    'confirmed': 'border-green-300 bg-green-50',
    'pending': 'border-yellow-300 bg-yellow-50',
    'cancelled': 'border-red-300 bg-red-50',
    'completed': 'border-gray-300 bg-gray-50'
  }
  return classes[lesson.status] || 'border-blue-300 bg-blue-50'
}

function getLessonCardStyle(lesson: Lesson): Record<string, string> {
  // Récupérer la couleur de l'enseignant si disponible
  let teacherColor = lesson.teacher?.color || null
  
  // Si aucune couleur n'est définie, générer une couleur temporaire basée sur l'ID
  if (!teacherColor && lesson.teacher?.id) {
    teacherColor = generateColorFromId(lesson.teacher.id)
  }
  
  if (!teacherColor) {
    return {}
  }
  
  // Convertir la couleur hex en RGB pour calculer la luminosité
  const hex = teacherColor.replace('#', '')
  const r = parseInt(hex.substr(0, 2), 16)
  const g = parseInt(hex.substr(2, 2), 16)
  const b = parseInt(hex.substr(4, 2), 16)
  
  // Calculer la luminosité relative (0-1)
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
  
  // Si la couleur est trop claire, utiliser une bordure plus foncée
  // Sinon, utiliser la couleur pastel comme bordure gauche
  const borderColor = luminance > 0.8 
    ? `rgba(${Math.max(0, r - 40)}, ${Math.max(0, g - 40)}, ${Math.max(0, b - 40)}, 0.6)`
    : teacherColor
  
  return {
    'border-left': `4px solid ${borderColor}`,
    'background-color': `${teacherColor}15` // Ajouter de la transparence (15 = ~8% d'opacité)
  }
}

// Générer une couleur pastel basée sur un ID (pour affichage temporaire)
function generateColorFromId(id: number): string {
  // Simple hash basé sur l'ID
  const hash = (id * 2654435761) >>> 0 // Hash simple
  
  // Générer des valeurs RGB pastel (150-255 pour avoir des couleurs claires)
  const r = 150 + (hash % 105)
  const g = 150 + ((hash >> 8) % 105)
  const b = 150 + ((hash >> 16) % 105)
  
  return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`
}

// ═══════════════════════════════════════════════════════════════════
// 📅 NAVIGATION PAR DATE
// ═══════════════════════════════════════════════════════════════════

// Calculer la prochaine occurrence d'un jour de la semaine
function getNextOccurrence(dayOfWeek: number): Date {
  const today = new Date()
  const todayDayOfWeek = today.getDay() // 0 = Dimanche, 1 = Lundi, etc.
  
  // Calculer combien de jours ajouter pour atteindre le prochain jour désiré
  let daysToAdd = dayOfWeek - todayDayOfWeek
  
  // Si le jour est déjà passé cette semaine, aller à la semaine prochaine
  if (daysToAdd < 0) {
    daysToAdd += 7
  }
  
  // Si c'est aujourd'hui mais l'heure est déjà passée, aller à la semaine prochaine
  if (daysToAdd === 0 && selectedSlot.value) {
    const now = new Date()
    const slotTime = selectedSlot.value.start_time.split(':')
    const slotHour = parseInt(slotTime[0])
    const slotMinute = parseInt(slotTime[1])
    
    if (now.getHours() > slotHour || (now.getHours() === slotHour && now.getMinutes() >= slotMinute)) {
      daysToAdd = 7 // Aller à la semaine prochaine
    }
  }
  
  const nextDate = new Date(today)
  nextDate.setDate(today.getDate() + daysToAdd)
  nextDate.setHours(0, 0, 0, 0) // Reset à minuit
  
  return nextDate
}

// Naviguer vers la date précédente (même jour, semaine précédente)
function navigateToPreviousDate() {
  if (!selectedDate.value) return
  
  const newDate = new Date(selectedDate.value)
  newDate.setDate(newDate.getDate() - 7) // Soustraire 7 jours
  
  selectedDate.value = newDate
  selectedDateInput.value = formatDateForInput(newDate)
  
  // Recharger les cours si nécessaire pour couvrir la nouvelle plage
  checkAndReloadLessonsIfNeeded(newDate)
}

// Naviguer vers la date suivante (même jour, semaine suivante)
function navigateToNextDate() {
  if (!selectedDate.value) return
  
  const newDate = new Date(selectedDate.value)
  newDate.setDate(newDate.getDate() + 7) // Ajouter 7 jours
  
  selectedDate.value = newDate
  selectedDateInput.value = formatDateForInput(newDate)
  
  // Recharger les cours si nécessaire pour couvrir la nouvelle plage
  checkAndReloadLessonsIfNeeded(newDate)
}

// Vérifier si on doit recharger les cours pour couvrir la nouvelle date
async function checkAndReloadLessonsIfNeeded(targetDate: Date) {
  // Vérifier si la date cible est dans la plage actuellement chargée
  const loadedStart = loadedLessonsRange.value.start
  const loadedEnd = loadedLessonsRange.value.end
  
  if (!loadedStart || !loadedEnd) {
    // Si aucune plage n'est chargée, charger autour de la date cible
    console.log('🔄 Aucune plage chargée, chargement autour de la date:', targetDate.toISOString().split('T')[0])
    const startDate = new Date(targetDate)
    startDate.setDate(targetDate.getDate() - 7) // 1 semaine avant
    const endDate = new Date(targetDate)
    endDate.setDate(targetDate.getDate() + 180) // 6 mois après
    await loadLessons(startDate, endDate)
    return
  }
  
  // Si la date cible est en dehors de la plage chargée, étendre la plage
  const marginDays = 7 // Marge de sécurité
  const needsReload = targetDate < new Date(loadedStart.getTime() + marginDays * 24 * 60 * 60 * 1000) || 
                      targetDate > new Date(loadedEnd.getTime() - marginDays * 24 * 60 * 60 * 1000)
  
  if (needsReload) {
    console.log('🔄 Extension de la plage de cours pour couvrir la date:', targetDate.toISOString().split('T')[0])
    
    // Calculer la nouvelle plage à charger
    let newStartDate = new Date(loadedStart)
    let newEndDate = new Date(loadedEnd)
    
    // Si la date est avant la plage chargée, étendre vers le passé
    if (targetDate < loadedStart) {
      newStartDate = new Date(targetDate)
      newStartDate.setDate(targetDate.getDate() - 7) // 1 semaine avant
    }
    
    // Si la date est après la plage chargée, étendre vers le futur
    if (targetDate > loadedEnd) {
      newEndDate = new Date(targetDate)
      newEndDate.setDate(targetDate.getDate() + 180) // 6 mois après
    }
    
    // Charger seulement la partie manquante
    await loadLessons(newStartDate, newEndDate)
  }
}

// Aller à la prochaine occurrence (aujourd'hui ou prochain jour du créneau)
function navigateToToday() {
  if (!selectedSlot.value) return
  
  selectedDate.value = getNextOccurrence(selectedSlot.value.day_of_week)
  selectedDateInput.value = formatDateForInput(selectedDate.value)
}

// Gérer le changement de date via l'input
async function onDateChange() {
  if (!selectedDateInput.value) return
  
  const newDate = new Date(selectedDateInput.value + 'T00:00:00')
  
  // Vérifier que c'est le bon jour de la semaine
  if (selectedSlot.value && newDate.getDay() !== selectedSlot.value.day_of_week) {
    warning(`Cette date ne correspond pas au jour du créneau (${getDayName(selectedSlot.value.day_of_week)})`, 'Date invalide')
    selectedDateInput.value = formatDateForInput(selectedDate.value!)
    return
  }
  
  selectedDate.value = newDate
  
  // Recharger les cours si nécessaire pour couvrir la nouvelle date
  await checkAndReloadLessonsIfNeeded(newDate)
}

// Réinitialiser la sélection de créneau et de date
function resetSlotSelection() {
  selectedSlot.value = null
  selectedDate.value = null
  selectedDateInput.value = ''
}

// Formater une date pour l'input (YYYY-MM-DD)
function formatDateForInput(date: Date): string {
  if (!date) return ''
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Formater une date complète (ex: "Mercredi 6 novembre 2025")
// Fonction pour obtenir les élèves d'un cours (student_id ou relation many-to-many)
function getLessonStudents(lesson: Lesson | null): string {
  if (!lesson) return 'Aucun élève'
  
  const studentNames: string[] = []
  
  // Ajouter l'élève principal (student_id) s'il existe
  if (lesson.student?.user?.name) {
    studentNames.push(lesson.student.user.name)
  } else if (lesson.student_id) {
    // Fallback : si student_id existe mais que la relation n'est pas chargée,
    // chercher l'élève dans la liste des élèves chargés
    const foundStudent = students.value.find((s: any) => s.id === lesson.student_id)
    if (foundStudent) {
      const studentName = foundStudent.user?.name || foundStudent.name || `Élève #${foundStudent.id}`
      studentNames.push(studentName)
    } else {
      // Debug si l'élève n'est pas trouvé
      console.warn('⚠️ [getLessonStudents] student_id existe mais élève non trouvé dans la liste:', {
        lesson_id: lesson.id,
        student_id: lesson.student_id,
        students_loaded: students.value.length
      })
    }
  }
  
  // Ajouter les élèves de la relation many-to-many
  if (lesson.students && Array.isArray(lesson.students)) {
    lesson.students.forEach((student: any) => {
      if (student.user?.name && !studentNames.includes(student.user.name)) {
        studentNames.push(student.user.name)
      }
    })
  }
  
  // Debug si aucun élève trouvé mais qu'il y a un student_id
  if (studentNames.length === 0 && lesson.student_id) {
    console.warn('⚠️ [getLessonStudents] Aucun élève trouvé mais student_id existe:', {
      lesson_id: lesson.id,
      student_id: lesson.student_id,
      student: lesson.student,
      students: lesson.students,
      students_loaded_count: students.value.length
    })
  }
  
  return studentNames.length > 0 ? studentNames.join(', ') : 'Aucun élève'
}

// Fonction pour vérifier si un cours a un abonnement actif
function hasActiveSubscription(lesson: Lesson | null): boolean {
  if (!lesson) return false
  
  // Vérifier l'élève principal
  if (lesson.student?.subscription_instances && lesson.student.subscription_instances.length > 0) {
    return true
  }
  
  // Vérifier les élèves de la relation many-to-many
  if (lesson.students && Array.isArray(lesson.students)) {
    return lesson.students.some((student: any) => 
      student.subscription_instances && student.subscription_instances.length > 0
    )
  }
  
  return false
}

function formatDateFull(date: Date | null): string {
  if (!date) return ''
  
  const options: Intl.DateTimeFormatOptions = { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  }
  
  return date.toLocaleDateString('fr-FR', options)
}

// Obtenir la date minimum (par exemple, 2 semaines avant aujourd'hui)
function getMinDate(): string {
  const minDate = new Date()
  minDate.setDate(minDate.getDate() - 14) // 2 semaines avant
  return formatDateForInput(minDate)
}

// Obtenir la date maximum (par exemple, 3 mois après aujourd'hui)
function getMaxDate(): string {
  const maxDate = new Date()
  maxDate.setMonth(maxDate.getMonth() + 3) // 3 mois après
  return formatDateForInput(maxDate)
}

// Computed: Peut-on naviguer vers la date précédente ?
const canNavigatePrevious = computed(() => {
  if (!selectedDate.value) return false
  const minDate = new Date()
  minDate.setDate(minDate.getDate() - 14)
  return selectedDate.value > minDate
})

// Computed: Peut-on naviguer vers la date suivante ?
const canNavigateNext = computed(() => {
  if (!selectedDate.value) return false
  const maxDate = new Date()
  maxDate.setMonth(maxDate.getMonth() + 3)
  return selectedDate.value < maxDate
})

// Computed: Est-ce que le jour actuel correspond au jour du créneau sélectionné ?
const isTodaySlotDay = computed(() => {
  if (!selectedSlot.value) return false
  const today = new Date()
  const todayDayOfWeek = today.getDay() // 0 = Dimanche, 1 = Lundi, etc.
  return todayDayOfWeek === selectedSlot.value.day_of_week
})

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadClubDisciplines(),
    loadOpenSlots(),
    loadLessons(),
    loadTeachers(),
    loadStudents(),
    loadCourseTypes()
  ])
  updateAvailableDays()
})
</script>

