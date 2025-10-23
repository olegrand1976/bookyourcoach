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
        <!-- Bloc 1: Gestion des créneaux horaires -->
        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-xl font-semibold text-gray-900">Créneaux horaires</h2>
              <p class="text-sm text-gray-500 mt-1">Gérez vos créneaux disponibles pour les cours</p>
            </div>
            <button 
              @click="openSlotModal()"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Nouveau créneau
            </button>
          </div>
          
          <!-- Liste des créneaux -->
          <div v-if="openSlots.length > 0" class="space-y-3">
            <div v-for="slot in openSlots" 
                 :key="slot.id"
                 class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <!-- Jour et horaire -->
                  <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      {{ getDayName(slot.day_of_week) }}
                    </span>
                    <span class="text-sm font-semibold text-gray-900">
                      {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                    </span>
                  </div>
          
                  <!-- Discipline -->
                  <h3 class="font-medium text-gray-900 mb-2">
                    {{ slot.discipline?.name || 'Discipline non définie' }}
                  </h3>

                  <!-- Informations du créneau -->
                  <div class="flex items-center gap-4 text-sm text-gray-500">
                    <span v-if="slot.duration">
                      <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      {{ slot.duration }} min
                    </span>
                    <span v-if="slot.price">
                      <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      {{ formatPrice(slot.price) }} €
                    </span>
                    <span v-if="slot.max_capacity">
                      <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                      {{ slot.max_capacity }} {{ slot.max_capacity === 1 ? 'participant' : 'participants' }}
                    </span>
                    <span v-if="slot.max_slots && slot.max_slots > 1" class="font-medium text-blue-600">
                      <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      × {{ slot.max_slots }} plages simultanées
                    </span>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col items-end gap-2">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="slot.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                    {{ slot.is_active ? 'Actif' : 'Inactif' }}
                  </span>
                  <div class="flex gap-2">
                    <button 
                      @click="openSlotModal(slot)"
                      class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                      title="Modifier">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button 
                      @click="deleteSlot(slot.id)"
                      class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                      title="Supprimer">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Message si aucun créneau -->
          <div v-else class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun créneau horaire</h3>
            <p class="mt-1 text-sm text-gray-500">
              Créez vos premiers créneaux horaires pour organiser vos cours.
            </p>
          </div>
        </div>
        
        <!-- Bloc 2: Liste des cours disponibles -->
        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-gray-900">Cours disponibles</h2>
            <span class="text-sm text-gray-500">{{ activeDisciplines.length }} cours</span>
          </div>
          
          <!-- Liste des cours -->
          <div v-if="activeDisciplines.length > 0" class="space-y-3">
            <div v-for="discipline in activeDisciplines" 
                 :key="discipline.id"
                 class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <h3 class="font-medium text-gray-900">{{ discipline.name }}</h3>
                  <p v-if="discipline.description" class="text-sm text-gray-600 mt-1">
                    {{ discipline.description }}
                  </p>
                  <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                    <span v-if="discipline.settings.duration">
                      <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
                      {{ discipline.settings.duration }} min
                    </span>
                    <span v-if="discipline.settings.price">
                      <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
                      {{ discipline.settings.price.toFixed(2) }} €
                    </span>
                    <span>
                      <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
                      {{ discipline.settings.min_participants }} - {{ discipline.settings.max_participants }} participants
                    </span>
          </div>
                  <p v-if="discipline.settings.notes" class="text-xs text-gray-500 mt-2 italic">
                    {{ discipline.settings.notes }}
                  </p>
            </div>
                <!-- Badge de statut retiré car non pertinent ici -->
        </div>
      </div>
    </div>

          <!-- Message si aucun cours -->
          <div v-else class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun cours disponible</h3>
            <p class="mt-1 text-sm text-gray-500">
              Configurez les disciplines de votre club dans 
              <NuxtLink to="/club/profile" class="text-blue-600 hover:underline">votre profil</NuxtLink>
              pour voir les cours disponibles.
            </p>
          </div>
          </div>
          
        <!-- Bloc 3: Calendrier Hebdomadaire -->
        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h2 class="text-xl font-semibold text-gray-900">Calendrier Hebdomadaire</h2>
              <span class="text-sm text-gray-500">
                <span class="font-bold" :class="lessons.length > 0 ? 'text-green-600' : 'text-orange-600'">{{ lessons.length }} cours programmés</span>
              </span>
              <div v-if="lessons.length === 0" class="text-xs text-orange-600 mt-1">
                ℹ️ Aucun cours programmé - Cliquez sur un créneau disponible pour ajouter un cours
              </div>
        </div>
            <div class="flex gap-3">
              <button 
                @click="showCreateLessonModal = true"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
                Nouveau cours
              </button>
        </div>
              </div>

          <!-- Vue Calendrier -->
          <div class="overflow-x-auto">
            <!-- Légende -->
            <div class="mb-4 flex items-center gap-6 text-sm">
              <div class="flex items-center gap-2">
                <div class="w-12 h-6 bg-blue-100/50 border-l-2 border-dashed border-blue-300 rounded"></div>
                <span class="text-gray-700">Créneaux disponibles</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-12 h-6 bg-green-500 border-l-4 border-green-800 rounded"></div>
                <span class="text-gray-700">Cours confirmés</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-12 h-6 bg-yellow-500 border-l-4 border-yellow-800 rounded"></div>
                <span class="text-gray-700">Cours en attente</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-12 h-6 bg-gray-400 border-l-4 border-gray-700 rounded"></div>
                <span class="text-gray-700">Cours terminés</span>
            </div>
            </div>
            
            <div class="min-w-[900px]">
              <!-- En-tête des jours -->
              <div class="grid grid-cols-8 gap-px bg-gray-200 mb-px sticky top-0 z-10">
                <div class="bg-gradient-to-br from-gray-100 to-gray-50 p-3 text-center font-bold text-sm text-gray-600 border-b-2 border-gray-300">
                  Horaire
          </div>
                <div v-for="day in 7" :key="day" 
                     class="bg-gradient-to-br from-blue-50 to-white p-3 text-center border-b-2 border-blue-200">
                  <div class="font-bold text-gray-900 text-base">{{ getDayName(day % 7) }}</div>
          </div>
        </div>

              <!-- Grille horaire -->
              <div class="grid grid-cols-8 gap-px bg-gray-200">
                <template v-for="hour in timeSlots" :key="hour">
                <!-- Colonne horaire -->
                  <div class="bg-gradient-to-r from-gray-50 to-white p-3 text-sm text-gray-700 font-bold text-right border-r-2 border-gray-200">
                    {{ hour }}
            </div>

                <!-- Colonnes des jours -->
                  <div v-for="day in 7" :key="`${hour}-${day}`" 
                       class="relative min-h-[80px] border-r border-b border-gray-100"
                     :class="[
                         isSlotActiveInCell(day % 7, hour) ? '' : 'bg-white'
                       ]">
                    
                    <!-- CRÉNEAUX DISPONIBLES (arrière-plan très pâle) - Cliquables pour créer un cours -->
                    <!-- Créneaux pour ce jour/heure (seulement à l'heure de début) -->
                    <div v-for="slot in getSlotsForDayAndHour(day % 7, hour)" 
                         :key="`slot-${slot.id}`"
                         @click.stop="openCreateLessonModal(slot)"
                         class="absolute inset-0 p-2 cursor-pointer transition-all hover:shadow-sm border-l-2 border-dashed opacity-40 hover:opacity-60 group"
                         :class="getSlotColorClassMuted(slot)"
                         :style="{ height: `${getSlotHeight(slot)}px`, zIndex: 5 }"
                         title="Cliquez pour créer un cours">
                      <div class="font-medium truncate text-[10px]">{{ slot.discipline?.name }}</div>
                      <div class="text-[9px] mt-0.5 opacity-75">
                        {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                  </div>
                      <div class="text-[9px] opacity-75">
                        {{ slot.max_capacity }}p × {{ slot.max_slots || 1 }}
                    </div>
                      <!-- Indication pour ajouter un cours (apparaît au survol) -->
                      <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <div class="bg-white/90 px-2 py-1 rounded text-[10px] font-medium shadow-sm">
                          + Ajouter un cours
                        </div>
                      </div>
                  </div>
                  
                    <!-- Fond coloré pour les cellules qui sont dans la durée d'un créneau mais pas au début -->
                    <div v-for="slot in getSlotsSpanningCell(day % 7, hour)" 
                         :key="`span-slot-${slot.id}`"
                         class="absolute inset-0 border-l-2 border-dashed opacity-30"
                         :class="getSlotColorClassMuted(slot)"
                         :style="{ zIndex: 4 }">
                    </div>
                    
                    <!-- COURS RÉELS (couleurs vives au premier plan avec bordure épaisse) -->
                    <!-- Cours pour ce jour/heure (seulement à l'heure de début) -->
                    <div v-for="lesson in getLessonsForDayAndHour(day % 7, hour)" 
                         :key="`lesson-${lesson.id}`"
                         @click.stop="openLessonModal(lesson)"
                         class="absolute inset-0 m-0.5 p-3 cursor-pointer transition-all hover:shadow-2xl hover:scale-[1.02] rounded-md shadow-lg"
                         :class="getLessonColorClass(lesson)"
                         :style="{ height: `${getLessonHeight(lesson) - 1}px`, zIndex: 20 }">
                      <div class="font-bold truncate text-sm mb-1">{{ lesson.course_type?.name || 'Cours' }}</div>
                      <div class="text-xs font-semibold truncate">
                        👤 {{ lesson.student?.user?.name || 'Étudiant' }}
                  </div>
                      <div class="text-xs truncate opacity-90">
                        🎓 {{ lesson.teacher?.user?.name || 'Coach' }}
                </div>
                      <div class="text-xs mt-1.5 font-medium">
                        🕐 {{ new Date(lesson.start_time).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
                        - {{ new Date(lesson.end_time).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
              </div>
                      <div v-if="lesson.status" class="text-[10px] mt-1.5 font-bold uppercase tracking-wide px-2 py-0.5 bg-white/20 rounded inline-block">
                        {{ lesson.status === 'confirmed' ? '✓ Confirmé' : 
                           lesson.status === 'pending' ? '⏳ En attente' : 
                           lesson.status === 'cancelled' ? '✗ Annulé' : 
                           lesson.status === 'completed' ? '✓ Terminé' : lesson.status }}
                  </div>
                </div>
                
                    <!-- Fond coloré pour les cellules qui sont dans la durée d'un cours mais pas au début -->
                    <div v-for="lesson in getLessonsSpanningCell(day % 7, hour)" 
                         :key="`span-lesson-${lesson.id}`"
                         class="absolute inset-0 m-0.5 rounded-md shadow-md"
                         :class="getLessonColorClass(lesson)"
                         :style="{ zIndex: 19 }">
            </div>
            </div>
                </template>
        </div>
      </div>
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
                  <label class="block text-sm font-medium text-gray-500 mb-1">Étudiant</label>
                  <p class="text-base font-semibold text-gray-900">
                    {{ selectedLesson.student?.user?.name || 'Non assigné' }}
                  </p>
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
      
      <!-- Modale Création de Cours -->
      <div v-if="showCreateLessonModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div class="p-6">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-2xl font-bold text-gray-900">
                Créer un nouveau cours
              </h3>
              <button @click="closeCreateLessonModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Informations du créneau sélectionné -->
            <div v-if="selectedSlotForLesson" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
              <h4 class="font-semibold text-blue-900 mb-2">Créneau sélectionné</h4>
              <div class="text-sm text-blue-800 space-y-1">
                <p><strong>Jour :</strong> {{ getDayName(selectedSlotForLesson.day_of_week) }}</p>
                <p><strong>Discipline :</strong> {{ selectedSlotForLesson.discipline?.name }}</p>
              </div>
            </div>

            <!-- Formulaire -->
            <form @submit.prevent="createLesson" class="space-y-4">
              <!-- Enseignant -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Enseignant *</label>
                <select v-model.number="lessonForm.teacher_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                  <option :value="null">Sélectionnez un enseignant</option>
                  <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                    {{ teacher.user?.name || teacher.name }}
                  </option>
                </select>
              </div>

              <!-- Étudiant (optionnel) -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Étudiant (optionnel)</label>
                <select v-model.number="lessonForm.student_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                  <option :value="null">Aucun étudiant assigné</option>
                  <option v-for="student in students" :key="student.id" :value="student.id">
                    {{ student.user?.name || student.name }}
                  </option>
                </select>
              </div>

              <!-- Date et heure -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date et heure *</label>
                <input v-model="lessonForm.start_time" type="datetime-local" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
              </div>

              <!-- Durée -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Durée (minutes) *</label>
                <input v-model.number="lessonForm.duration" type="number" min="15" step="5" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
              </div>

              <!-- Prix -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prix (€) *</label>
                <input v-model.number="lessonForm.price" type="number" min="0" step="0.01" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
              </div>

              <!-- Notes -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea v-model="lessonForm.notes" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                          placeholder="Notes sur le cours..."></textarea>
              </div>

              <!-- Boutons -->
              <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" @click="closeCreateLessonModal"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                  Annuler
                </button>
                <button type="submit" :disabled="saving"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50">
                  {{ saving ? 'Création...' : 'Créer le cours' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'

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
  }
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
const selectedSlotForLesson = ref<OpenSlot | null>(null)
const teachers = ref<any[]>([])
const students = ref<any[]>([])
const lessonForm = ref({
  teacher_id: null as number | null,
  student_id: null as number | null,
  start_time: '',
  duration: 60,
  price: 0,
  notes: ''
})

// Créneaux horaires pour le calendrier (8h - 20h)
const timeSlots = ref([
  '08:00', '09:00', '10:00', '11:00', '12:00', 
  '13:00', '14:00', '15:00', '16:00', '17:00', 
  '18:00', '19:00', '20:00'
])

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

// Computed
const activeDisciplines = computed(() => {
  return clubDisciplines.value.filter(d => d.is_active)
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
    error.value = err.message || 'Erreur lors du chargement des cours disponibles'
  } finally {
    loading.value = false
  }
}

// Charger les créneaux horaires
async function loadOpenSlots() {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/open-slots')
    
    if (response.data.success) {
      openSlots.value = response.data.data
      console.log('✅ Créneaux chargés:', openSlots.value)
  } else {
      console.error('Erreur chargement créneaux:', response.data.message)
    }
  } catch (err: any) {
    console.error('Erreur chargement créneaux:', err)
  }
}

// Charger les cours réels
async function loadLessons() {
  try {
    const { $api } = useNuxtApp()
    // Charger les cours de la semaine en cours et prochaines semaines
  const today = new Date()
    const nextWeek = new Date(today)
    nextWeek.setDate(today.getDate() + 14) // 2 semaines
    
    const response = await $api.get('/lessons', {
      params: {
        date_from: today.toISOString().split('T')[0],
        date_to: nextWeek.toISOString().split('T')[0]
      }
    })
    
    if (response.data.success) {
      lessons.value = response.data.data
      console.log('✅ Cours chargés:', lessons.value)
      // Debug: Afficher le statut de chaque cours
      lessons.value.forEach((lesson, index) => {
        console.log(`  Cours ${index + 1}:`, {
          id: lesson.id,
          status: lesson.status,
          course_type: lesson.course_type?.name,
          start_time: lesson.start_time
        })
      })
    } else {
      console.error('Erreur chargement cours:', response.data.message)
    }
  } catch (err: any) {
    console.error('Erreur chargement cours:', err)
  }
}

// Charger les enseignants du club
async function loadTeachers() {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/teachers')
    if (response.data.success) {
      teachers.value = response.data.data
      console.log('✅ Enseignants chargés:', teachers.value.length)
    }
  } catch (err) {
    console.error('Erreur chargement enseignants:', err)
  }
}

// Charger les étudiants du club
async function loadStudents() {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/students')
    if (response.data.success) {
      students.value = response.data.data
      console.log('✅ Étudiants chargés:', students.value.length)
    }
  } catch (err) {
    console.error('Erreur chargement étudiants:', err)
  }
}

// Gestion de la modale
function openSlotModal(slot?: OpenSlot) {
    if (slot) {
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
      is_active: slot.is_active
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
    
    const payload = {
      day_of_week: slotForm.value.day_of_week,
      start_time: slotForm.value.start_time,
      end_time: slotForm.value.end_time,
      discipline_id: slotForm.value.discipline_id,
      duration: slotForm.value.duration,
      price: slotForm.value.price,
      max_capacity: slotForm.value.max_capacity,
      max_slots: slotForm.value.max_slots,
      is_active: slotForm.value.is_active
    }
    
    if (editingSlot.value) {
      // Mise à jour
      await $api.put(`/club/open-slots/${editingSlot.value.id}`, payload)
      console.log('✅ Créneau mis à jour')
    } else {
      // Création
      await $api.post('/club/open-slots', payload)
      console.log('✅ Créneau créé')
    }
    
    // Recharger la liste
      await loadOpenSlots()
    closeSlotModal()
  } catch (err: any) {
    console.error('Erreur sauvegarde créneau:', err)
    alert('Erreur lors de la sauvegarde du créneau')
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
  } catch (err: any) {
    console.error('Erreur suppression créneau:', err)
    alert('Erreur lors de la suppression du créneau')
  }
}

// Fonctions calendrier
function getSlotsForDayAndHour(day: number, hour: string): OpenSlot[] {
  return openSlots.value.filter(slot => {
    if (slot.day_of_week !== day) return false
    
    const slotStartHour = formatTime(slot.start_time)
    const hourPlusOne = `${String(parseInt(hour.split(':')[0]) + 1).padStart(2, '0')}:00`
    
    // Le créneau est affiché si son heure de début est dans cette tranche horaire
    return slotStartHour >= hour && slotStartHour < hourPlusOne
  })
}

function getSlotColorClass(slot: OpenSlot): string {
  const disciplineColors = [
    'bg-blue-400 text-white border-l-blue-700 hover:bg-blue-500',
    'bg-green-400 text-white border-l-green-700 hover:bg-green-500',
    'bg-purple-400 text-white border-l-purple-700 hover:bg-purple-500',
    'bg-orange-400 text-white border-l-orange-700 hover:bg-orange-500',
    'bg-pink-400 text-white border-l-pink-700 hover:bg-pink-500',
    'bg-indigo-400 text-white border-l-indigo-700 hover:bg-indigo-500',
    'bg-amber-400 text-gray-900 border-l-amber-700 hover:bg-amber-500',
    'bg-red-400 text-white border-l-red-700 hover:bg-red-500',
    'bg-cyan-400 text-white border-l-cyan-700 hover:bg-cyan-500',
    'bg-teal-400 text-white border-l-teal-700 hover:bg-teal-500',
    'bg-lime-400 text-gray-900 border-l-lime-700 hover:bg-lime-500',
    'bg-rose-400 text-white border-l-rose-700 hover:bg-rose-500',
  ]
  
  // Utiliser l'ID de la discipline pour choisir une couleur cohérente
  const colorIndex = (slot.discipline_id || 0) % disciplineColors.length
  return disciplineColors[colorIndex]
}

// Calcule la hauteur du créneau en pixels en fonction de sa durée
function getSlotHeight(slot: OpenSlot): number {
  const startTime = new Date(`2000-01-01 ${slot.start_time}`)
  const endTime = new Date(`2000-01-01 ${slot.end_time}`)
  const durationHours = (endTime.getTime() - startTime.getTime()) / (1000 * 60 * 60)
  
  // Hauteur de base d'une cellule (80px min-h) + bordures
  const cellHeight = 81 // 80px + 1px bordure
  return Math.round(durationHours * cellHeight)
}

// Vérifie si une cellule est occupée par un créneau (début ou span)
function isSlotActiveInCell(day: number, hour: string): boolean {
  return getSlotsForDayAndHour(day, hour).length > 0 || getSlotsSpanningCell(day, hour).length > 0
}

// Retourne les créneaux qui traversent cette cellule (mais ne commencent pas dans cette cellule)
function getSlotsSpanningCell(day: number, hour: string): OpenSlot[] {
  return openSlots.value.filter(slot => {
    if (slot.day_of_week !== day) return false
    
    const slotStart = formatTime(slot.start_time)
    const slotEnd = formatTime(slot.end_time)
    const hourPlusOne = `${String(parseInt(hour.split(':')[0]) + 1).padStart(2, '0')}:00`
    
    // Le créneau traverse cette cellule s'il commence avant cette heure et finit après
    return slotStart < hour && slotEnd > hour
  })
}

// Fonctions pour les cours (lessons)
function getLessonsForDayAndHour(day: number, hour: string): Lesson[] {
  const filteredLessons = lessons.value.filter(lesson => {
    const lessonDate = new Date(lesson.start_time)
    const lessonDay = lessonDate.getDay()
    
    // Debug
    if (lessons.value.length > 0 && day === 1 && hour === '09:00') {
      console.log(`🔍 Filtrage lesson ID ${lesson.id}:`, {
        lessonDay,
        targetDay: day,
        lessonStartTime: lesson.start_time,
        lessonHour: lessonDate.getHours(),
        targetHour: hour
      })
    }
    
    if (lessonDay !== day) return false
    
    const lessonStartHour = `${String(lessonDate.getHours()).padStart(2, '0')}:${String(lessonDate.getMinutes()).padStart(2, '0')}`
    const hourPlusOne = `${String(parseInt(hour.split(':')[0]) + 1).padStart(2, '0')}:00`
    
    return lessonStartHour >= hour && lessonStartHour < hourPlusOne
  })
  
  if (day === 1 && hour === '09:00' && lessons.value.length > 0) {
    console.log(`📊 Résultat pour Lundi 09:00: ${filteredLessons.length} cours trouvés sur ${lessons.value.length} cours totaux`)
  }
  
  return filteredLessons
}

function getLessonsSpanningCell(day: number, hour: string): Lesson[] {
  return lessons.value.filter(lesson => {
    const lessonStartDate = new Date(lesson.start_time)
    const lessonEndDate = new Date(lesson.end_time)
    const lessonDay = lessonStartDate.getDay()
    
    if (lessonDay !== day) return false
    
    const lessonStartHour = `${String(lessonStartDate.getHours()).padStart(2, '0')}:${String(lessonStartDate.getMinutes()).padStart(2, '0')}`
    const lessonEndHour = `${String(lessonEndDate.getHours()).padStart(2, '0')}:${String(lessonEndDate.getMinutes()).padStart(2, '0')}`
    
    return lessonStartHour < hour && lessonEndHour > hour
  })
}

function getLessonHeight(lesson: Lesson): number {
  const startTime = new Date(lesson.start_time)
  const endTime = new Date(lesson.end_time)
  const durationHours = (endTime.getTime() - startTime.getTime()) / (1000 * 60 * 60)
  
  const cellHeight = 81
  return Math.round(durationHours * cellHeight)
}

function getLessonColorClass(lesson: Lesson): string {
  // Couleurs vives pour les cours réels avec bordure épaisse
  const statusColors: Record<string, string> = {
    'confirmed': 'bg-green-500 text-white border-l-[6px] border-l-green-800',
    'pending': 'bg-yellow-500 text-gray-900 border-l-[6px] border-l-yellow-800',
    'cancelled': 'bg-red-400 text-white border-l-[6px] border-l-red-800',
    'completed': 'bg-gray-400 text-white border-l-[6px] border-l-gray-700'
  }
  
  return statusColors[lesson.status] || 'bg-blue-500 text-white border-l-[6px] border-l-blue-800'
}

function getSlotColorClassMuted(slot: OpenSlot): string {
  // Couleurs pâles pour les créneaux disponibles (en arrière-plan)
  const disciplineColors = [
    'bg-blue-100/50 text-blue-600 border-l-blue-300',
    'bg-green-100/50 text-green-600 border-l-green-300',
    'bg-purple-100/50 text-purple-600 border-l-purple-300',
    'bg-orange-100/50 text-orange-600 border-l-orange-300',
    'bg-pink-100/50 text-pink-600 border-l-pink-300',
    'bg-indigo-100/50 text-indigo-600 border-l-indigo-300',
    'bg-amber-100/50 text-amber-700 border-l-amber-300',
    'bg-red-100/50 text-red-600 border-l-red-300',
    'bg-cyan-100/50 text-cyan-600 border-l-cyan-300',
    'bg-teal-100/50 text-teal-600 border-l-teal-300',
    'bg-lime-100/50 text-lime-700 border-l-lime-300',
    'bg-rose-100/50 text-rose-600 border-l-rose-300',
  ]
  
  const colorIndex = (slot.discipline_id || 0) % disciplineColors.length
  return disciplineColors[colorIndex]
}

function openCreateLessonModal(slot?: OpenSlot) {
  selectedSlotForLesson.value = slot || null
  
  if (slot) {
    // Calculer la prochaine date correspondant au jour du créneau
    const today = new Date()
    const targetDay = slot.day_of_week
    const daysUntilTarget = (targetDay - today.getDay() + 7) % 7
    const nextDate = new Date(today)
    nextDate.setDate(today.getDate() + (daysUntilTarget === 0 ? 7 : daysUntilTarget))
    
    const dateStr = nextDate.toISOString().split('T')[0]
    const timeStr = slot.start_time.substring(0, 5)
    
    lessonForm.value = {
      teacher_id: null,
      student_id: null,
      start_time: `${dateStr}T${timeStr}`,
      duration: slot.duration || 60,
      price: slot.price || 0,
      notes: ''
    }
  }
  
  showCreateLessonModal.value = true
  console.log('📝 Ouverture modale création cours pour créneau:', slot)
}

function closeCreateLessonModal() {
  showCreateLessonModal.value = false
  selectedSlotForLesson.value = null
}

async function createLesson() {
  try {
    saving.value = true
    const { $api } = useNuxtApp()
    
    if (!lessonForm.value.teacher_id) {
      alert('Veuillez sélectionner un enseignant')
      return
    }
    
    // Trouver un course_type correspondant à la discipline du créneau
    let courseTypeId = null
    if (selectedSlotForLesson.value?.discipline_id) {
      // TODO: Récupérer le course_type de la discipline
      // Pour l'instant, on laisse null et on gère côté backend
    }
    
    const payload = {
      teacher_id: lessonForm.value.teacher_id,
      student_id: lessonForm.value.student_id,
      course_type_id: courseTypeId,
      start_time: lessonForm.value.start_time,
      duration: lessonForm.value.duration,
      price: lessonForm.value.price,
      notes: lessonForm.value.notes
    }
    
    console.log('📤 Création du cours avec payload:', payload)
    
    const response = await $api.post('/lessons', payload)
    
    if (response.data.success) {
      console.log('✅ Cours créé:', response.data.data)
      await loadLessons()
      closeCreateLessonModal()
    } else {
      alert(response.data.message || 'Erreur lors de la création du cours')
    }
  } catch (err: any) {
    console.error('Erreur création cours:', err)
    alert(err.response?.data?.message || 'Erreur lors de la création du cours')
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

async function updateLessonStatus(lessonId: number, newStatus: string) {
  try {
    saving.value = true
    const { $api } = useNuxtApp()
    
    const response = await $api.put(`/lessons/${lessonId}`, {
      status: newStatus
    })
    
    if (response.data.success) {
      // Recharger les cours
      await loadLessons()
      closeLessonModal()
    } else {
      alert('Erreur lors de la mise à jour du statut')
    }
  } catch (err: any) {
    console.error('Erreur mise à jour cours:', err)
    alert('Erreur lors de la mise à jour du statut')
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
      await loadLessons()
      closeLessonModal()
    } else {
      alert('Erreur lors de la suppression')
    }
  } catch (err: any) {
    console.error('Erreur suppression cours:', err)
    alert('Erreur lors de la suppression')
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

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadClubDisciplines(),
    loadOpenSlots(),
    loadLessons(),
    loadTeachers(),
    loadStudents()
  ])
})
</script>
