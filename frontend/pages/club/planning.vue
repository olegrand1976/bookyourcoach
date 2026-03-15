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
              <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-2 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span class="text-white font-semibold text-lg">{{ timeSlot.time }}</span>
                  <span class="text-blue-200 text-sm">({{ timeSlot.lessons.length }} cours)</span>
                </div>
                <button
                  v-if="selectedSlot && selectedDate"
                  @click.stop="openCreateLessonModalForTimeSlot(timeSlot.time)"
                  class="px-3 py-1.5 text-sm bg-white text-blue-700 rounded-lg hover:bg-blue-50 transition-colors flex items-center gap-2 font-medium shadow-sm"
                  title="Créer un cours à cette heure">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  Créer un cours
                </button>
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
                      <span v-if="lesson.status === 'cancelled'" class="text-xs text-orange-600 font-semibold ml-1">
                        ⚠️
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
                    
                    <!-- Prix et boutons d'action -->
                    <div class="flex items-center justify-between pt-2 border-t border-gray-100 relative z-10">
                      <span v-if="lesson.price" class="text-sm font-semibold text-gray-700">
                        {{ formatPrice(lesson.price) }} €
                      </span>
                      <span v-else class="text-xs text-gray-400">-</span>
                      <div class="flex items-center gap-1 relative z-20">
                        <button
                          @click.stop.prevent="openEditLessonModal(lesson)"
                          class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors flex items-center gap-1 relative z-30 cursor-pointer"
                          title="Modifier"
                          type="button">
                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                          Modifier
                        </button>
                        <button
                          @click.stop.prevent="confirmAndDeleteLesson(lesson)"
                          class="px-2 py-1 text-xs rounded transition-colors flex items-center gap-1 relative z-30 cursor-pointer"
                          :class="lesson.status === 'cancelled' ? 'bg-red-800 text-white hover:bg-red-900' : 'bg-red-600 text-white hover:bg-red-700'"
                          :title="lesson.status === 'cancelled' ? 'Supprimer définitivement ce cours annulé' : 'Supprimer'"
                          type="button">
                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                        </button>
                      </div>
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
                @click="confirmAndDeleteLesson(selectedLesson)"
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
        @edit-lesson="handleEditLessonFromHistory"
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

      <!-- Modale de confirmation pour la portée de la modification -->
      <div v-if="showUpdateScopeModal" class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-12">
          <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showUpdateScopeModal = false"></div>
          
          <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              Souhaitez-vous appliquer ce changement d'horaire uniquement à ce cours ou à tous les cours suivants de cet abonnement ?
            </h3>
            
            <div v-if="futureLessonsCount > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
              <p class="text-sm text-blue-800">
                <strong>{{ futureLessonsCount }}</strong> cours futur(s) seront affectés si vous choisissez "Tous les cours suivants".
              </p>
            </div>
            
            <div v-else-if="futureLessonsCount === 0 && showUpdateScopeModal" class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
              <p class="text-sm text-yellow-800">
                Aucun cours futur trouvé pour cet abonnement.
              </p>
            </div>
            
            <p v-if="saving" class="mb-4 text-sm text-blue-600 flex items-center gap-2">
              <span class="inline-block w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></span>
              Modification et génération des occurrences en cours…
            </p>
            <div class="flex flex-col gap-3 mb-6">
              <button
                @click="confirmUpdateSingleLesson"
                :disabled="saving"
                class="flex items-center justify-between p-4 border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <div class="font-semibold">Ce cours uniquement</div>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </button>
              
              <button
                @click="confirmUpdateAllFutureLessons"
                :disabled="futureLessonsCount === 0 || saving"
                :class="[
                  'flex items-center justify-between p-4 border-2 rounded-lg transition-colors',
                  futureLessonsCount > 0 && !saving
                    ? 'border-green-300 hover:border-green-500 hover:bg-green-50'
                    : 'border-gray-200 bg-gray-50 cursor-not-allowed'
                ]"
              >
                <div>
                  <div class="font-semibold">Tous les cours suivants</div>
                  <div class="text-sm mt-1" :class="futureLessonsCount > 0 ? 'text-green-700' : 'text-gray-400'">
                    {{ futureLessonsCount > 0 ? `Modifier ce cours et ${futureLessonsCount} cours futur(s)` : 'Aucun cours futur à modifier' }}
                  </div>
                </div>
                <svg 
                  v-if="futureLessonsCount > 0"
                  class="w-5 h-5 text-green-500" 
                  fill="none" 
                  stroke="currentColor" 
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </button>
            </div>
            
            <div class="flex justify-end">
              <button
                @click="showUpdateScopeModal = false"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
              >
                Annuler
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modale de confirmation de suppression -->
      <div 
        v-if="showDeleteScopeModal" 
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click.self="showDeleteScopeModal = false"
      >
        <div class="bg-white rounded-lg max-w-md w-full mx-4 p-6">
          <h3 class="text-xl font-bold text-gray-900 mb-4">
            Confirmer la suppression
          </h3>
          
          <div v-if="lessonToDelete" class="mb-4">
            <p class="text-sm text-gray-600 mb-2">
              <strong>Élève:</strong> {{ getLessonStudents(lessonToDelete) }}
            </p>
            <p class="text-sm text-gray-600 mb-2">
              <strong>Date:</strong> {{ formatDateFull(new Date(lessonToDelete.start_time)) }}
            </p>
            <p class="text-sm text-gray-600 mb-2">
              <strong>Heure:</strong> {{ formatLessonTime(lessonToDelete.start_time) }}
            </p>
            <p class="text-sm text-gray-600 mb-2">
              <strong>Type:</strong> {{ lessonToDelete.course_type?.name || 'Non défini' }}
            </p>
            <p v-if="lessonToDelete.status === 'cancelled'" class="text-sm text-red-600 mb-2 font-semibold">
              <strong>Statut:</strong> ⚠️ Ce cours est déjà annulé
            </p>
          </div>
          
          <div v-if="futureLessonsCountForDelete > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <p class="text-sm text-blue-800">
              <strong>{{ futureLessonsCountForDelete }}</strong> cours futur(s) seront également {{ lessonToDelete?.status === 'cancelled' ? 'supprimés' : 'traités' }} si vous choisissez "Toutes les séances futures".
            </p>
          </div>
          
          <div v-else-if="futureLessonsCountForDelete === 0 && lessonToDelete?.subscription_instances && lessonToDelete.subscription_instances.length > 0 && lessonToDelete.status !== 'cancelled'" class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
            <p class="text-sm text-yellow-800">
              Aucun cours futur trouvé pour cet abonnement.
            </p>
          </div>
          
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Raison de la suppression (optionnel)
            </label>
            <textarea
              v-model="deleteReason"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Ex: Créneau libéré, changement d'horaire..."
            ></textarea>
          </div>
          
          <div class="mb-4">
            <div class="text-sm font-medium text-gray-700 mb-3">Action à effectuer :</div>
            
            <!-- Option 1: Cette séance uniquement -->
            <div class="mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
              <div class="font-semibold text-gray-900 mb-2">Cette séance uniquement</div>
              <div class="flex gap-2">
                <button
                  @click="confirmDeleteSingleLesson('cancel')"
                  class="flex-1 flex items-center justify-center gap-2 px-3 py-2 border-2 border-orange-300 rounded-lg hover:border-orange-500 hover:bg-orange-50 transition-colors"
                >
                  <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  <span class="text-sm font-medium text-orange-700">Annuler</span>
                </button>
                <button
                  @click="confirmDeleteSingleLesson('delete')"
                  class="flex-1 flex items-center justify-center gap-2 px-3 py-2 border-2 border-red-300 rounded-lg hover:border-red-500 hover:bg-red-50 transition-colors"
                >
                  <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                  <span class="text-sm font-medium text-red-700">Supprimer définitivement</span>
                </button>
              </div>
            </div>
            
            <!-- Option 2: Toutes les séances futures (toujours afficher si abonnement lié) -->
            <div v-if="lessonToDelete?.subscription_instances && lessonToDelete.subscription_instances.length > 0" class="p-3 rounded-lg border mb-4" 
                 :class="lessonToDelete?.status === 'cancelled' ? 'bg-orange-50 border-orange-200' : 'bg-red-50 border-red-200'">
              <div class="font-semibold text-gray-900 mb-2">
                Toutes les séances futures 
                <span v-if="futureLessonsCountForDelete > 0">({{ futureLessonsCountForDelete }} séance(s))</span>
                <span v-else class="text-gray-500 text-sm font-normal">(aucune détectée)</span>
              </div>
              <div class="text-xs mb-2" 
                   :class="lessonToDelete?.status === 'cancelled' ? 'text-orange-700' : 'text-gray-600'">
                <template v-if="futureLessonsCountForDelete > 0">
                  <template v-if="lessonToDelete?.status === 'cancelled'">
                    Cette séance annulée et {{ futureLessonsCountForDelete }} séance(s) future(s) également annulée(s) liée(s) au même créneau et abonnement
                  </template>
                  <template v-else>
                    Cette séance et {{ futureLessonsCountForDelete }} séance(s) future(s) liée(s) au même créneau et abonnement
                  </template>
                </template>
                <template v-else>
                  <template v-if="lessonToDelete?.status === 'cancelled'">
                    Cette séance annulée et toutes les séances futures (s'il y en a) liées au même créneau et abonnement seront supprimées définitivement
                  </template>
                  <template v-else>
                    Cette séance et toutes les séances futures (s'il y en a) liées au même créneau et abonnement
                  </template>
                </template>
              </div>
              <div class="flex gap-2">
                <button
                  @click="confirmDeleteAllFutureLessons('cancel')"
                  class="flex-1 flex items-center justify-center gap-2 px-3 py-2 border-2 border-orange-300 rounded-lg hover:border-orange-500 hover:bg-orange-50 transition-colors"
                >
                  <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  <span class="text-sm font-medium text-orange-700">Annuler</span>
                </button>
                <button
                  @click="confirmDeleteAllFutureLessons('delete')"
                  class="flex-1 flex items-center justify-center gap-2 px-3 py-2 border-2 border-red-300 rounded-lg hover:border-red-500 hover:bg-red-50 transition-colors"
                >
                  <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                  <span class="text-sm font-medium text-red-700">Supprimer définitivement</span>
                </button>
              </div>
            </div>
          </div>
          
          <div class="flex justify-end gap-2">
            <button
              @click="showDeleteScopeModal = false"
              class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Annuler
            </button>
          </div>
        </div>
      </div>
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
  deduct_from_subscription: true as boolean | null,
  // Intervalle de récurrence (1 = chaque semaine, 2 = toutes les 2 semaines, etc.)
  recurring_interval: 1,
  // Portée de la mise à jour (pour les récurrences)
  update_scope: 'single' as 'single' | 'all_future'
})
const availableDaysOfWeek = ref<number[]>([]) // Jours de la semaine où il y a des créneaux

// Variables pour la modale de confirmation de modification
const showUpdateScopeModal = ref(false)
const futureLessonsCount = ref(0)
const pendingUpdatePayload = ref<any>(null)
const originalLessonTime = ref<{ date: string; time: string } | null>(null)

// Variables pour la modale de confirmation de suppression
const showDeleteScopeModal = ref(false)
const futureLessonsCountForDelete = ref(0)
const lessonToDelete = ref<Lesson | null>(null)
const deleteReason = ref<string>('')

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
      startDate.setMonth(today.getMonth() - 3) // 3 mois en arrière
    }
    const endDate = customEndDate || new Date(today)
    if (!customEndDate) {
      endDate.setMonth(today.getMonth() + 3) // 3 mois en avant
    }
    
    const response = await $api.get('/lessons', {
      params: {
        date_from: startDate.toISOString().split('T')[0],
        date_to: endDate.toISOString().split('T')[0]
        // Pas de limite : on filtre uniquement par période (3 mois)
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
    // Charger tous les élèves actifs pour la modale de création de cours
    // Utiliser un per_page élevé pour obtenir tous les élèves en une seule requête
    const response = await $api.get('/club/students', {
      params: {
        per_page: 1000, // Nombre élevé pour obtenir tous les élèves
        page: 1,
        status: 'active' // Seulement les élèves actifs
      }
    })
    console.log('🔍 [Planning] Réponse élèves:', response.data)
    if (response.data.success) {
      students.value = response.data.data || []
      console.log('✅ Élèves chargés:', students.value.length)
      
      // Si on a reçu exactement le nombre de per_page, il pourrait y avoir plus d'élèves
      // Dans ce cas, charger les pages suivantes
      if (response.data.pagination && response.data.pagination.last_page > 1) {
        const allStudents = [...students.value]
        for (let page = 2; page <= response.data.pagination.last_page; page++) {
          try {
            const nextPageResponse = await $api.get('/club/students', {
              params: {
                per_page: 1000,
                page: page,
                status: 'active'
              }
            })
            if (nextPageResponse.data.success && nextPageResponse.data.data) {
              allStudents.push(...nextPageResponse.data.data)
            }
          } catch (pageErr) {
            console.warn(`Erreur chargement page ${page} des élèves:`, pageErr)
          }
        }
        students.value = allStudents
        console.log('✅ Tous les élèves chargés:', students.value.length)
      }
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

async function openCreateLessonModal(slot?: OpenSlot, customTime?: string) {
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

  // Initialiser le formulaire AVANT d'ouvrir la modale pour que l'heure de la plage soit bien
  // reprise à l'ouverture (évite que les watchers de la modale réinitialisent form.time)
  if (slot) {
    let dateToUse: Date
    if (selectedDate.value && selectedDate.value.getDay() === slot.day_of_week) {
      dateToUse = new Date(selectedDate.value)
      console.log('📅 [openCreateLessonModal] Utilisation de la date sélectionnée:', formatDateForInput(dateToUse))
    } else {
      const today = new Date()
      const targetDay = slot.day_of_week
      const daysUntilTarget = (targetDay - today.getDay() + 7) % 7
      dateToUse = new Date(today)
      dateToUse.setDate(today.getDate() + (daysUntilTarget === 0 ? 7 : daysUntilTarget))
      console.log('📅 [openCreateLessonModal] Calcul de la prochaine date:', formatDateForInput(dateToUse))
    }

    const dateStr = formatDateForInput(dateToUse)
    // Heure de la plage (bouton sur une plage) ou heure de début du créneau (bouton vert)
    const timeStr = customTime ?? slot.start_time.substring(0, 5)

    let courseTypeId = null
    let initialDuration = slot.duration || 60
    let initialPrice = slot.price || 0

    const slotCourseTypes = slot.course_types || []
    if (slotCourseTypes.length > 0) {
      const matchingCourseType = slotCourseTypes.find(ct =>
        ct.discipline_id === slot.discipline_id || !ct.discipline_id
      ) || slotCourseTypes[0]

      if (matchingCourseType) {
        courseTypeId = matchingCourseType.id
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
      start_time: `${dateStr}T${timeStr}:00`,
      duration: initialDuration,
      price: initialPrice,
      notes: '',
      est_legacy: false,
      deduct_from_subscription: true,
      recurring_interval: 1,
      update_scope: 'single'
    }
  } else {
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
      deduct_from_subscription: true,
      recurring_interval: 1,
      update_scope: 'single'
    }
  }

  showCreateLessonModal.value = true
  await nextTick()
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
    deduct_from_subscription: true,
    recurring_interval: 1,
    update_scope: 'single'
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
    // Utiliser formatDateForInput pour éviter les problèmes de timezone (toISOString convertit en UTC)
    lessonForm.value.date = formatDateForInput(dateTime)
    // Extraire l'heure en utilisant formatLessonTime pour garantir la cohérence avec l'affichage
    // formatLessonTime retourne "HH:MM" depuis une chaîne datetime ISO
    const timeString = formatLessonTime(lesson.start_time)
    lessonForm.value.time = timeString
    
    // Sauvegarder l'horaire original pour détecter les changements
    originalLessonTime.value = {
      date: lessonForm.value.date,
      time: lessonForm.value.time
    }
    
    console.log('📅 [openEditLessonModal] Date et heure extraites:', {
      date: lessonForm.value.date,
      time: lessonForm.value.time,
      start_time: lesson.start_time,
      dateTimeLocal: dateTime.toLocaleString('fr-FR'),
      timeString: timeString,
      originalTime: originalLessonTime.value
    })
    
    // Trouver le créneau qui contient l'heure du cours (même jour ET plage horaire contenant start_time)
    // pour éviter de sélectionner le premier créneau du jour et écraser l'heure par la 1ère plage
    const dayOfWeek = dateTime.getDay() // 0 = dimanche, 1 = lundi, etc.
    const slotsSameDay = openSlots.value.filter(slot => slot.day_of_week === dayOfWeek)
    const lessonStartStr = timeString // "HH:MM"
    const duration = lesson.start_time && lesson.end_time
      ? Math.round((new Date(lesson.end_time).getTime() - new Date(lesson.start_time).getTime()) / (1000 * 60))
      : 60
    const matchingSlot = findSlotContainingTime(slotsSameDay, lessonStartStr, duration) ?? slotsSameDay[0] ?? null
    if (matchingSlot) {
      selectedSlotForLesson.value = matchingSlot
      console.log('🎯 [openEditLessonModal] Créneau trouvé (contenant l\'heure du cours):', {
        day_of_week: dayOfWeek,
        slot_id: matchingSlot.id,
        slot_start: matchingSlot.start_time,
        slot_end: matchingSlot.end_time,
        lesson_time: lessonStartStr
      })
    } else {
      selectedSlotForLesson.value = null
      console.warn('⚠️ [openEditLessonModal] Aucun créneau trouvé pour le jour:', dayOfWeek)
    }
    
    // La modale CreateLessonModal chargera automatiquement les cours existants
    // via ses watchers quand elle sera montée, donc pas besoin de charger ici
  }
  
  // Remplir les autres champs
  lessonForm.value.teacher_id = lesson.teacher?.id || null
  lessonForm.value.student_id = lesson.student?.id || (lesson.students && lesson.students.length > 0 ? lesson.students[0].id : null)
  lessonForm.value.course_type_id = lesson.course_type?.id || null
  lessonForm.value.update_scope = 'single' // Par défaut, modifier uniquement ce cours
  
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
  // Réinitialiser les variables de confirmation
  showUpdateScopeModal.value = false
  pendingUpdatePayload.value = null
  futureLessonsCount.value = 0
  originalLessonTime.value = null
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
    deduct_from_subscription: true,
    recurring_interval: 1,
    update_scope: 'single'
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
      deduct_from_subscription: lessonForm.value.deduct_from_subscription !== false,
      // Intervalle de récurrence (1 = chaque semaine, 2 = toutes les 2 semaines, etc.) — toujours envoyé quand déduction abo
      recurring_interval: Math.max(1, Math.min(52, Number(lessonForm.value.recurring_interval) || 1))
    }
    
    console.log('📤 Création du cours avec payload:', payload)
    
    const response = await $api.post('/lessons', payload)
    
    if (response.data.success) {
      console.log('✅ Cours créé:', response.data.data)
      success('Cours créé avec succès', 'Succès')
      
      // Recharger les cours pour inclure le nouveau cours
      await loadLessons()
      
      // Conserver la date sélectionnée dans "Cours programmés" si elle existe
      // et correspond au jour du créneau sélectionné
      if (selectedDate.value && selectedSlot.value && 
          selectedDate.value.getDay() === selectedSlot.value.day_of_week) {
        // La date est déjà correcte, pas besoin de la modifier
        console.log('📅 [createLesson] Conservation de la date sélectionnée:', selectedDate.value.toISOString().split('T')[0])
      } else if (selectedSlot.value && lessonForm.value.date) {
        // Si une date a été sélectionnée dans le formulaire et qu'un créneau est sélectionné,
        // mettre à jour selectedDate pour rester sur cette date
        const createdDate = new Date(lessonForm.value.date + 'T00:00:00')
        if (createdDate.getDay() === selectedSlot.value.day_of_week) {
          selectedDate.value = createdDate
          selectedDateInput.value = formatDateForInput(createdDate)
          console.log('📅 [createLesson] Mise à jour de selectedDate avec la date du cours créé:', selectedDate.value.toISOString().split('T')[0])
        }
      }
      
      closeCreateLessonModal()
    } else {
      showError(response.data.message || 'Erreur lors de la création du cours', 'Erreur')
    }
  } catch (err: any) {
    console.error('Erreur création cours:', err)
    
    // Gérer les différents types d'erreurs
    let errorMessage = 'Erreur lors de la création du cours'
    const data = err.response?.data
    
    if (data?.message) {
      errorMessage = data.message
      // Enrichir avec les conflits de récurrence si présents (422)
      if (data.conflicts?.length) {
        const dates = data.conflicts.slice(0, 6).map((c: { date?: string }) => c.date).filter(Boolean)
        if (dates.length) {
          errorMessage += '\nDates concernées : ' + dates.join(', ')
        }
        errorMessage += '\n\nCréez le cours sans récurrence ou choisissez un autre créneau/horaire.'
      } else if (data.errors?.recurring?.length) {
        const recurringErrors = Array.isArray(data.errors.recurring) ? data.errors.recurring : [data.errors.recurring]
        errorMessage += '\n' + recurringErrors.slice(0, 5).join('\n')
        errorMessage += '\n\nCréez le cours sans récurrence ou choisissez un autre créneau.'
      }
    } else if (data?.errors) {
      const errors = data.errors
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

// Vérifier si l'horaire a changé
function hasTimeChanged(): boolean {
  if (!editingLesson.value || !originalLessonTime.value) return false
  
  return lessonForm.value.date !== originalLessonTime.value.date || 
         lessonForm.value.time !== originalLessonTime.value.time
}

// Charger le nombre de cours futurs de l'abonnement
async function loadFutureLessonsCount() {
  if (!editingLesson.value) {
    futureLessonsCount.value = 0
    return
  }
  
  const lesson = editingLesson.value as any
  const subscriptionInstances = lesson.subscription_instances || 
                                lesson.student?.subscription_instances ||
                                (lesson.students && lesson.students.length > 0 ? lesson.students[0].subscription_instances : null)
  
  console.log('🔍 [loadFutureLessonsCount] Début du chargement', {
    hasLesson: !!editingLesson.value,
    hasSubscriptionInstances: !!subscriptionInstances,
    subscriptionInstancesCount: subscriptionInstances?.length || 0
  })
  
  if (!subscriptionInstances || subscriptionInstances.length === 0) {
    console.log('⚠️ [loadFutureLessonsCount] Aucune instance d\'abonnement trouvée')
    futureLessonsCount.value = 0
    return
  }
  
  try {
    const { $api } = useNuxtApp()
    const subscriptionInstanceId = subscriptionInstances[0].id
    const currentLessonDate = new Date(editingLesson.value.start_time)
    
    console.log('📅 [loadFutureLessonsCount] Paramètres', {
      subscriptionInstanceId,
      currentLessonDate: currentLessonDate.toISOString().split('T')[0],
      startTime: editingLesson.value.start_time
    })
    
    // Récupérer les cours futurs de cet abonnement
    const response = await $api.get(`/club/subscription-instances/${subscriptionInstanceId}/future-lessons`, {
      params: {
        after_date: currentLessonDate.toISOString().split('T')[0]
      }
    })
    
    console.log('✅ [loadFutureLessonsCount] Réponse API', {
      success: response.data.success,
      count: response.data.data?.count,
      data: response.data.data
    })
    
    if (response.data.success) {
      futureLessonsCount.value = response.data.data?.count || 0
      console.log('✅ [loadFutureLessonsCount] Nombre de cours futurs:', futureLessonsCount.value)
    } else {
      console.warn('⚠️ [loadFutureLessonsCount] Réponse non réussie:', response.data)
      futureLessonsCount.value = 0
    }
  } catch (err: any) {
    console.error('❌ [loadFutureLessonsCount] Erreur chargement cours futurs:', err)
    console.error('❌ [loadFutureLessonsCount] Détails erreur:', {
      message: err.message,
      response: err.response?.data,
      status: err.response?.status
    })
    futureLessonsCount.value = 0
  }
}

// Confirmer la mise à jour pour ce cours uniquement
async function confirmUpdateSingleLesson() {
  try {
    lessonForm.value.update_scope = 'single'
    await performUpdate(pendingUpdatePayload.value, 'single')
  } finally {
    showUpdateScopeModal.value = false
  }
}

// Confirmer la mise à jour pour tous les cours futurs
async function confirmUpdateAllFutureLessons() {
  if (futureLessonsCount.value === 0) return
  try {
    lessonForm.value.update_scope = 'all_future'
    await performUpdate(pendingUpdatePayload.value, 'all_future')
  } finally {
    showUpdateScopeModal.value = false
  }
}

// Effectuer la mise à jour
async function performUpdate(updatePayload: any, scope: 'single' | 'all_future') {
  if (!editingLesson.value) return
  
  try {
    saving.value = true
    const { $api } = useNuxtApp()
    
    // Ajouter le scope à la payload
    const payloadWithScope = {
      ...updatePayload,
      update_scope: scope // 'single' ou 'all_future'
    }
    
    // Inclure recurring_interval si la portée est 'all_future'
    if (scope === 'all_future' && lessonForm.value.recurring_interval) {
      payloadWithScope.recurring_interval = lessonForm.value.recurring_interval
    }
    
    console.log('📤 Mise à jour du cours avec payload:', payloadWithScope)
    
    // Mettre à jour le cours
    const response = await $api.put(`/lessons/${editingLesson.value.id}`, payloadWithScope)
    
    if (!response.data.success) {
      showError(response.data.message || 'Erreur lors de la modification', 'Erreur')
      return
    }
    
    const message = scope === 'all_future' 
      ? `Cours modifié avec succès. ${futureLessonsCount.value} cours futur(s) ont également été mis à jour.`
      : 'Cours modifié avec succès'
    
    success(message, 'Succès')
    
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
    
    // Réinitialiser les variables
    pendingUpdatePayload.value = null
    futureLessonsCount.value = 0
    originalLessonTime.value = null
  } catch (err: any) {
    console.error('Erreur modification cours:', err)
    showError(err.response?.data?.message || 'Erreur lors de la modification', 'Erreur')
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
      saving.value = false
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
    
    // Vérifier si le cours fait partie d'un abonnement et si l'horaire a changé
    const lesson = editingLesson.value as any
    const isPartOfSubscription = (lesson.subscription_instances && lesson.subscription_instances.length > 0) ||
                                  (lesson.student?.subscription_instances && lesson.student.subscription_instances.length > 0) ||
                                  (lesson.students && lesson.students.length > 0 && lesson.students[0].subscription_instances && lesson.students[0].subscription_instances.length > 0)
    const timeChanged = hasTimeChanged()
    
    // Si le cours fait partie d'un abonnement et que l'horaire a changé, demander confirmation
    if (isPartOfSubscription && timeChanged) {
      pendingUpdatePayload.value = payload
      await loadFutureLessonsCount()
      showUpdateScopeModal.value = true
      saving.value = false
      return
    }
    
    // Sinon, mettre à jour directement
    await performUpdate(payload, lessonForm.value.update_scope || 'single')
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

function handleEditLessonFromHistory(lesson: any) {
  // Ouvrir la modale d'édition avec le cours sélectionné
  openEditLessonModal(lesson)
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

// Ouvrir la modale de création pour une plage horaire spécifique
async function openCreateLessonModalForTimeSlot(timeSlot: string) {
  if (!selectedSlot.value || !selectedDate.value) {
    warning('Veuillez sélectionner un créneau et une date', 'Information')
    return
  }
  
  // Vérifier que l'heure de la plage horaire correspond au créneau
  const slotStartTime = formatTime(selectedSlot.value.start_time)
  const slotEndTime = formatTime(selectedSlot.value.end_time)
  
  if (timeSlot < slotStartTime || timeSlot >= slotEndTime) {
    warning(`L'heure ${timeSlot} ne correspond pas au créneau sélectionné (${slotStartTime} - ${slotEndTime})`, 'Information')
    return
  }
  
  // Utiliser le créneau sélectionné et la date sélectionnée avec l'heure de la plage horaire
  await openCreateLessonModal(selectedSlot.value, timeSlot)
  
  console.log('📅 [openCreateLessonModalForTimeSlot] Modale ouverte avec:', {
    date: formatDateForInput(selectedDate.value),
    time: timeSlot,
    slot: selectedSlot.value.id,
    slotTimeRange: `${slotStartTime} - ${slotEndTime}`
  })
}

// Fonction pour confirmer et supprimer un cours depuis les cartes
// Nouvelle méthode : ouvrir la modale de confirmation au lieu de confirm() natif
async function confirmAndDeleteLesson(lesson: Lesson) {
  console.log('🗑️ [confirmAndDeleteLesson] Demande de suppression pour cours ID:', lesson.id)
  
  // Vérifier si le cours a des séances futures liées à un abonnement
  await checkFutureLessonsForDelete(lesson)
  
  showDeleteScopeModal.value = true
}

// Vérifier le nombre de cours futurs pour la suppression
async function checkFutureLessonsForDelete(lesson: Lesson) {
  console.log(`🚀 [checkFutureLessonsForDelete] DÉBUT - Cours ID: ${lesson.id}, start_time: ${lesson.start_time}`)
  
  try {
    const { $api } = useNuxtApp()
    
    // Réinitialiser le compteur
    futureLessonsCountForDelete.value = 0
    
    // Charger les détails complets du cours pour avoir les subscription_instances
    console.log(`🔍 [checkFutureLessonsForDelete] Chargement des détails du cours ID ${lesson.id}`)
    const response = await $api.get(`/lessons/${lesson.id}`, {
      params: {
        include: 'subscription_instances'
      }
    })
    
    console.log(`📥 [checkFutureLessonsForDelete] Réponse /lessons/${lesson.id}:`, response.data)
    
    if (response.data.success && response.data.data) {
      const fullLesson = response.data.data
      
      console.log(`📋 [checkFutureLessonsForDelete] Cours chargé:`, {
        id: fullLesson.id,
        start_time: fullLesson.start_time,
        subscription_instances_count: fullLesson.subscription_instances?.length || 0,
        subscription_instances: fullLesson.subscription_instances
      })
      
      // Mettre à jour lessonToDelete avec les données complètes (incluant subscription_instances)
      lessonToDelete.value = fullLesson
      
      // Si le cours a des subscription_instances, vérifier les cours futurs
      if (fullLesson.subscription_instances && fullLesson.subscription_instances.length > 0) {
        const subscriptionInstance = fullLesson.subscription_instances[0]
        
        console.log(`✅ [checkFutureLessonsForDelete] Abonnement trouvé: ID ${subscriptionInstance.id}`, subscriptionInstance)
        
        const lessonDate = new Date(fullLesson.start_time || lesson.start_time)
        const afterDate = lessonDate.toISOString().split('T')[0]
        
        try {
            const includeCancelled = fullLesson.status === 'cancelled'
            
            // Extraire les caractéristiques du créneau pour filtrer les cours futurs
            const lessonStartDateTime = new Date(fullLesson.start_time)
            const lessonEndDateTime = new Date(fullLesson.end_time || fullLesson.start_time)
            const lessonDayOfWeek = lessonStartDateTime.getDay()
            const lessonDayOfWeekMySQL = lessonDayOfWeek === 0 ? 1 : (lessonDayOfWeek + 1)
            
            const lessonStartTime = String(lessonStartDateTime.getHours()).padStart(2, '0') + ':' +
                                   String(lessonStartDateTime.getMinutes()).padStart(2, '0') + ':' +
                                   String(lessonStartDateTime.getSeconds()).padStart(2, '0')
            const lessonEndTime = String(lessonEndDateTime.getHours()).padStart(2, '0') + ':' +
                                 String(lessonEndDateTime.getMinutes()).padStart(2, '0') + ':' +
                                 String(lessonEndDateTime.getSeconds()).padStart(2, '0')
            
            const studentId = fullLesson.student_id || fullLesson.student?.id
            const clubId = fullLesson.club_id || fullLesson.club?.id
            
            if (!studentId || !clubId) {
              console.warn('⚠️ [checkFutureLessonsForDelete] student_id ou club_id manquant', {
                student_id: studentId,
                club_id: clubId
              })
              futureLessonsCountForDelete.value = 0
              return
            }
            
            console.log(`🔍 [checkFutureLessonsForDelete] Appel API future-lessons pour abonnement ${subscriptionInstance.id}`, {
              after_date: afterDate,
              includeCancelled: includeCancelled,
              day_of_week: lessonDayOfWeekMySQL,
              start_time: lessonStartTime,
              student_id: studentId,
              club_id: clubId
            })
            
            const futureLessonsResponse = await $api.get(`/club/subscription-instances/${subscriptionInstance.id}/future-lessons`, {
              params: {
                after_date: afterDate,
                include_cancelled: includeCancelled ? 'true' : 'false',
                reference_lesson_time: lessonStartTime,
                reference_lesson_end_time: lessonEndTime,
                reference_student_id: studentId,
                reference_club_id: clubId,
                reference_day_of_week: lessonDayOfWeekMySQL
              }
            })
            
            console.log(`📥 [checkFutureLessonsForDelete] Réponse API future-lessons:`, futureLessonsResponse.data)
          
          if (futureLessonsResponse.data.success && futureLessonsResponse.data.data) {
            const lessonStartDateTime = new Date(fullLesson.start_time)
            
            const futureLessons = futureLessonsResponse.data.data.lessons.filter((l: any) => {
              const lessonTime = new Date(l.start_time)
              const isAfterStartTime = lessonTime > lessonStartDateTime
              const isNotCurrentLesson = l.id !== fullLesson.id
              
              if (fullLesson.status === 'cancelled') {
                return isNotCurrentLesson && l.status === 'cancelled' && isAfterStartTime
              } else {
                return isNotCurrentLesson && l.status !== 'cancelled' && isAfterStartTime
              }
            })
            
            futureLessonsCountForDelete.value = futureLessons.length
            console.log(`✅ [checkFutureLessonsForDelete] Cours futurs trouvés: ${futureLessons.length}`)
          } else {
            futureLessonsCountForDelete.value = 0
            console.log('ℹ️ [checkFutureLessonsForDelete] Aucun cours futur trouvé')
          }
        } catch (apiError: any) {
          console.error('❌ [checkFutureLessonsForDelete] Erreur API:', apiError)
          futureLessonsCountForDelete.value = 0
        }
      } else {
        futureLessonsCountForDelete.value = 0
        console.log('ℹ️ Aucune instance d\'abonnement liée à ce cours')
      }
    } else {
      futureLessonsCountForDelete.value = 0
      console.log('ℹ️ Impossible de charger les détails du cours')
    }
  } catch (err: any) {
    console.error('❌ [checkFutureLessonsForDelete] ERREUR:', err)
    futureLessonsCountForDelete.value = 0
  }
}

// Confirmer suppression d'un seul cours
async function confirmDeleteSingleLesson(action: 'cancel' | 'delete') {
  if (!lessonToDelete.value) return
  
  await executeDeleteLesson(lessonToDelete.value.id, 'single', action, deleteReason.value)
  showDeleteScopeModal.value = false
  deleteReason.value = ''
  lessonToDelete.value = null
  showLessonModal.value = false
}

// Confirmer suppression de tous les cours futurs
async function confirmDeleteAllFutureLessons(action: 'cancel' | 'delete') {
  if (!lessonToDelete.value) return
  
  await executeDeleteLesson(lessonToDelete.value.id, 'all_future', action, deleteReason.value)
  showDeleteScopeModal.value = false
  deleteReason.value = ''
  lessonToDelete.value = null
  showLessonModal.value = false
}

// Exécuter la suppression avec l'API
async function executeDeleteLesson(lessonId: number, scope: 'single' | 'all_future', action: 'cancel' | 'delete', reason: string) {
  try {
    const { $api } = useNuxtApp()
    
    console.log(`🗑️ [executeDeleteLesson] Exécution - ID: ${lessonId}, scope: ${scope}, action: ${action}`)
    
    const response = await $api.delete(`/club/lessons/${lessonId}`, {
      data: {
        cancel_scope: scope,
        action: action,
        reason: reason || (action === 'delete' ? 'Supprimé définitivement par le club' : 'Annulé par le club')
      }
    })
    
    if (response.data.success) {
      const actionLabel = action === 'delete' ? 'supprimé' : 'annulé'
      const scopeLabel = scope === 'single' ? 'Cours' : `Cours et ${response.data.processed_count - 1} séance(s) future(s)`
      
      success(`${scopeLabel} ${actionLabel} avec succès`)
      
      // Recharger les cours
      await loadLessons()
    } else {
      showError(response.data.message || 'Erreur lors de la suppression')
    }
  } catch (error: any) {
    console.error('❌ [executeDeleteLesson] Erreur:', error)
    showError(error.response?.data?.message || 'Erreur lors de la suppression du cours')
  }
}

// Ancienne fonction deleteLesson conservée pour compatibilité (redirige vers nouvelle méthode)
async function deleteLesson(lessonId: number) {
  await executeDeleteLesson(lessonId, 'single', 'delete', '')
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
  
  // Si aucune couleur n'est définie, générer une couleur basée sur l'ID
  // Cela garantit qu'un professeur aura toujours la même couleur (même sur différentes journées)
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
  
  // Calculer la luminosité relative (0-1) selon la formule WCAG
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
  
  // Calculer une couleur de bordure avec un contraste encore plus marqué
  // Assombrir la couleur pour la bordure si elle est trop claire
  let borderR = r
  let borderG = g
  let borderB = b
  
  if (luminance > 0.65) {
    // Si la couleur est claire, assombrir significativement pour la bordure (augmenté de 60 à 80)
    borderR = Math.max(0, r - 80)
    borderG = Math.max(0, g - 80)
    borderB = Math.max(0, b - 80)
  } else if (luminance < 0.45) {
    // Si la couleur est foncée, éclaircir plus pour la bordure (augmenté de 30 à 50)
    borderR = Math.min(255, r + 50)
    borderG = Math.min(255, g + 50)
    borderB = Math.min(255, b + 50)
  } else {
    // Pour les couleurs moyennes, ajuster modérément pour plus de contraste
    if (luminance > 0.55) {
      borderR = Math.max(0, r - 50)
      borderG = Math.max(0, g - 50)
      borderB = Math.max(0, b - 50)
    } else {
      borderR = Math.min(255, r + 40)
      borderG = Math.min(255, g + 40)
      borderB = Math.min(255, b + 40)
    }
  }
  
  const borderColor = `rgb(${borderR}, ${borderG}, ${borderB})`
  
  // Utiliser une opacité encore plus élevée pour le fond (35 = ~21% d'opacité) pour un contraste maximal
  // Convertir RGB en hex pour l'opacité
  const hexR = r.toString(16).padStart(2, '0')
  const hexG = g.toString(16).padStart(2, '0')
  const hexB = b.toString(16).padStart(2, '0')
  
  return {
    'border-left': `5px solid ${borderColor}`, // Bordure plus épaisse (4px -> 5px) pour plus de visibilité
    'background-color': `#${hexR}${hexG}${hexB}35` // Opacité de 35 (~21%) pour un contraste maximal
  }
}

// Palette de couleurs bien différenciées pour les professeurs
// Couleurs choisies pour maximiser la différenciation visuelle et le contraste
const TEACHER_COLOR_PALETTE = [
  '#FF6B6B', // Rouge corail
  '#4ECDC4', // Turquoise
  '#45B7D1', // Bleu ciel
  '#FFA07A', // Saumon
  '#98D8C8', // Vert menthe
  '#F7DC6F', // Jaune doré
  '#BB8FCE', // Violet lavande
  '#85C1E2', // Bleu clair
  '#F8B739', // Orange
  '#52BE80', // Vert émeraude
  '#E74C3C', // Rouge vif
  '#3498DB', // Bleu royal
  '#9B59B6', // Violet
  '#1ABC9C', // Turquoise foncé
  '#F39C12', // Orange foncé
  '#E67E22', // Orange-rouge
  '#2ECC71', // Vert
  '#16A085', // Vert océan
  '#27AE60', // Vert forêt
  '#2980B9', // Bleu
  '#8E44AD', // Violet foncé
  '#C0392B', // Rouge brique
  '#D35400', // Orange brûlé
  '#7F8C8D', // Gris bleuté
]

// Générer une couleur basée sur un ID (garantit la cohérence pour un même professeur)
function generateColorFromId(id: number): string {
  // Utiliser l'ID pour sélectionner une couleur de la palette de manière déterministe
  // Cela garantit qu'un professeur aura toujours la même couleur
  const index = id % TEACHER_COLOR_PALETTE.length
  return TEACHER_COLOR_PALETTE[index]
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
    startDate.setMonth(targetDate.getMonth() - 3) // 3 mois avant
    const endDate = new Date(targetDate)
    endDate.setMonth(targetDate.getMonth() + 3) // 3 mois après
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
      newStartDate.setMonth(targetDate.getMonth() - 3) // 3 mois avant
    }
    
    // Si la date est après la plage chargée, étendre vers le futur
    if (targetDate > loadedEnd) {
      newEndDate = new Date(targetDate)
      newEndDate.setMonth(targetDate.getMonth() + 3) // 3 mois après
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

// Formater une date pour l'input (YYYY-MM-DD)
function formatDateForInput(date: Date): string {
  if (!date) return ''
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Retourne le créneau dont la plage horaire contient l'heure de début du cours (et durée)
function findSlotContainingTime(slots: { start_time?: string; end_time?: string }[], lessonStartStr: string, durationMinutes: number): { start_time?: string; end_time?: string } | null {
  if (!slots.length || !lessonStartStr) return null
  const toMinutes = (t: string) => {
    const part = (t || '').substring(0, 5)
    const [h, m] = part.split(':').map(Number)
    return (h ?? 0) * 60 + (m ?? 0)
  }
  const startMin = toMinutes(lessonStartStr)
  const endMin = startMin + durationMinutes
  for (const slot of slots) {
    const slotStart = toMinutes(slot.start_time)
    const slotEnd = toMinutes(slot.end_time)
    if (slotStart <= startMin && slotEnd >= endMin) return slot
  }
  return null
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

// Obtenir la date minimum (3 mois avant aujourd'hui)
function getMinDate(): string {
  const minDate = new Date()
  minDate.setMonth(minDate.getMonth() - 3) // 3 mois avant
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
  minDate.setMonth(minDate.getMonth() - 3) // 3 mois avant
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

