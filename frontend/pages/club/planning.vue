<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Planning des Cours</h1>
            <p class="text-gray-600">Gérez les créneaux, bloquez des plages et affectez les enseignants/élèves</p>
          </div>
          <div class="flex items-center space-x-3">
            <button 
              @click="openAddSlotModal()"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span>Ajouter un créneau</span>
            </button>
            <button 
              @click="showCreateLessonModal = true"
              class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors flex items-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span>Nouveau cours</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation avec choix de vue -->
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto py-4">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <!-- Mode d'affichage -->
          <div class="flex items-center bg-gray-100 rounded-lg p-1">
            <button 
              @click="viewMode = 'day'"
              :class="[
                'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                viewMode === 'day' 
                  ? 'bg-white text-blue-600 shadow-sm' 
                  : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              Jour
            </button>
            <button 
              @click="viewMode = 'week'"
              :class="[
                'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                viewMode === 'week' 
                  ? 'bg-white text-blue-600 shadow-sm' 
                  : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              Semaine
            </button>
          </div>
          
          <!-- Navigation -->
          <div class="flex items-center space-x-4">
            <button 
              @click="viewMode === 'week' ? previousWeek() : previousDay()"
              class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            <h2 class="text-lg font-semibold text-gray-900 min-w-[200px] text-center">
              {{ viewMode === 'week' ? formatWeekRange(currentWeek) : formatDayTitle(currentDay) }}
            </h2>
            <button 
              @click="viewMode === 'week' ? nextWeek() : nextDay()"
              class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </button>
          </div>
          
          <button 
            @click="goToToday"
            class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
          >
            Aujourd'hui
          </button>
        </div>
      </div>
    </div>

    <!-- Planning Calendrier -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Légende et informations -->
      <div class="bg-gradient-to-r from-blue-50 to-cyan-50 border border-blue-200 rounded-lg p-5 mb-4 shadow-sm">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Guide d'utilisation
          </h3>
          </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
          <div class="flex items-start gap-3 bg-white/70 rounded-lg p-3">
            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
          </div>
            <div>
              <div class="font-semibold text-gray-900 mb-1">Créer un cours</div>
              <p class="text-xs text-gray-600">Cliquez sur n'importe quelle case pour créer un nouveau cours</p>
          </div>
          </div>
          
          <div class="flex items-start gap-3 bg-white/70 rounded-lg p-3">
            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
              <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
              </svg>
            </div>
            <div>
              <div class="font-semibold text-gray-900 mb-1">Cours simultanés</div>
              <p class="text-xs text-gray-600">Les cours au même horaire s'affichent côte à côte</p>
        </div>
      </div>

      <!-- Liste modifiable des créneaux ouverts -->
      <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold text-gray-900">Créneaux ouverts (modifiables)</h3>
          <button @click="showAddSlotModal = true" class="text-blue-600 hover:text-blue-800 text-sm">+ Ajouter</button>
          </div>
        <div v-if="availableSlots.length === 0" class="text-sm text-gray-500">Aucun créneau pour le moment.</div>
        <div v-else class="divide-y divide-gray-100">
          <div v-for="slot in availableSlots" :key="slot.id" class="py-3 grid grid-cols-12 gap-3 items-center">
            <!-- Jour -->
            <div class="col-span-2">
              <select v-if="slot.editing"
                      v-model.number="editBuffer[slot.id].day_of_week"
                      class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                <option :value="1">Lundi</option>
                <option :value="2">Mardi</option>
                <option :value="3">Mercredi</option>
                <option :value="4">Jeudi</option>
                <option :value="5">Vendredi</option>
                <option :value="6">Samedi</option>
                <option :value="0">Dimanche</option>
              </select>
              <span v-else class="text-sm text-gray-800 font-medium">{{ getDayName(slot.day_of_week) }}</span>
            </div>
            <!-- Heures -->
            <div class="col-span-2 flex items-center gap-1">
              <template v-if="slot.editing">
                <input v-model="editBuffer[slot.id].start_time" type="time" class="border border-gray-300 rounded px-2 py-1 text-sm">
                <span class="text-gray-400 text-xs">→</span>
                <input v-model="editBuffer[slot.id].end_time" type="time" class="border border-gray-300 rounded px-2 py-1 text-sm">
              </template>
              <span v-else class="text-sm text-gray-600">{{ slot.start_time }} - {{ slot.end_time }}</span>
            </div>
            <!-- Discipline -->
            <div class="col-span-4">
              <template v-if="slot.editing">
                <select v-model="editBuffer[slot.id].discipline_id" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                  <option :value="null">(Aucune)</option>
                  <option v-for="d in availableDisciplines" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
              </template>
              <div v-else class="text-sm text-gray-800 truncate" :title="(availableDisciplines.find(d=>d.id===slot.discipline_id)?.name) || '—'">
                {{ (availableDisciplines.find(d=>d.id===slot.discipline_id)?.name) || '—' }}
              </div>
            </div>
            <!-- Capacité -->
            <div class="col-span-1 text-center">
              <template v-if="slot.editing">
                <input v-model.number="editBuffer[slot.id].max_capacity" type="number" min="1" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
              </template>
              <span v-else class="text-sm text-gray-600 font-medium">{{ slot.max_capacity }}</span>
            </div>
            <!-- Actions -->
            <div class="col-span-3 flex justify-end gap-1">
              <template v-if="slot.editing">
                <button @click="saveSlotEdit(slot)" class="text-emerald-600 hover:text-emerald-800 text-xs px-2 py-1 rounded">✓</button>
                <button @click="cancelSlotEdit(slot)" class="text-gray-500 hover:text-gray-700 text-xs px-2 py-1 rounded">✕</button>
              </template>
              <template v-else>
                <button 
                  @click="openAddSlotModal(slot)" 
                  class="text-purple-600 hover:text-purple-800 text-xs px-2 py-1 rounded hover:bg-purple-50"
                  title="Dupliquer ce créneau"
                >
                  📋
                </button>
                <button @click="openEditSlotModal(slot)" class="text-blue-600 hover:text-blue-800 text-xs px-2 py-1 rounded hover:bg-blue-50" title="Éditer">✏️</button>
                <button @click="confirmDeleteSlot(slot)" class="text-red-600 hover:text-red-800 text-xs px-2 py-1 rounded hover:bg-red-50" title="Supprimer">🗑️</button>
              </template>
            </div>
          </div>
        </div>
            </div>
            
          <div class="flex items-start gap-3 bg-white/70 rounded-lg p-3">
            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
              <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
              </svg>
          </div>
            <div>
              <div class="font-semibold text-gray-900 mb-1">Voir les détails</div>
              <p class="text-xs text-gray-600">Cliquez sur un cours pour afficher toutes les informations</p>
        </div>
            </div>
        </div>
              </div>

      <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
        <!-- En-têtes des jours -->
        <div :class="viewMode === 'week' ? 'grid grid-cols-8' : 'flex'" class="bg-gradient-to-b from-gray-50 to-gray-100 border-b-2 border-gray-300">
          <div class="p-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide flex items-center justify-center" :class="viewMode === 'week' ? '' : 'w-20 flex-shrink-0'">
            Horaires
          </div>
          <div v-for="day in displayDays" :key="day.date" 
               class="p-4 text-center border-l border-gray-300 transition-colors"
               :class="[isToday(day.date) ? 'bg-blue-50' : '', viewMode === 'day' ? 'flex-1' : '']"
          >
            <div class="text-sm font-semibold text-gray-900 uppercase tracking-wide">
              {{ day.name }}
            </div>
            <div class="text-lg font-bold mt-1"
                 :class="isToday(day.date) ? 'text-blue-600' : 'text-gray-700'"
            >
              {{ formatDate(day.date) }}
            </div>
            <div v-if="isToday(day.date)" class="text-xs text-blue-600 font-medium mt-1">
              Aujourd'hui
            </div>
          </div>
            </div>

        <!-- Grille des créneaux - Style Google Calendar épuré -->
        <div class="relative overflow-y-auto" style="max-height: calc(100vh - 400px);">
          <!-- Grille de fond minimaliste -->
          <div class="relative" :style="{ height: `${hourRanges.length * 60}px` }">
            <!-- Lignes horaires légères -->
            <div v-for="(hour, index) in hourRanges" :key="`hour-${hour}`" 
                 class="absolute left-0 right-0 border-b border-gray-100"
                 :style="{ top: `${index * 60}px`, height: '60px' }">
              
              <!-- Grille adaptative -->
              <div :class="viewMode === 'week' ? 'grid grid-cols-8' : 'flex'" class="h-full">
                <!-- Colonne horaire -->
                <div class="relative bg-gray-50/50 border-r border-gray-200" :class="viewMode === 'week' ? '' : 'w-20 flex-shrink-0'">
                  <span class="absolute -top-2 right-2 text-xs font-medium text-gray-500 bg-white px-1">
                    {{ hour }}:00
                  </span>
            </div>

                <!-- Colonnes des jours -->
                <div v-for="(day, dayIndex) in displayDays" :key="`grid-${day.date}-${hour}`" 
                     :class="[
                       'relative border-l border-gray-100 transition-colors group',
                       viewMode === 'day' ? 'flex-1' : '',
                       {
                         'bg-today': isToday(day.date),
                         'cursor-pointer hover:bg-blue-50/10': !isSlotFull(day.date, hour),
                         'cursor-not-allowed bg-gray-100/50 opacity-60': isSlotFull(day.date, hour)
                       }
                     ]"
                     @click="!isSlotFull(day.date, hour) && selectTimeSlot(day.date, hour, 0)">
                  
                  <!-- Ligne de 30 minutes -->
                  <div class="absolute top-1/2 left-0 right-0 border-t border-gray-50"></div>
                  
                  <!-- Indicateur "+" au hover pour créer un cours (seulement si pas plein) -->
                  <div v-if="!isSlotFull(day.date, hour)" class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                    <div class="w-8 h-8 bg-blue-500/80 rounded-full flex items-center justify-center shadow-lg">
                      <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                      </svg>
                    </div>
                  </div>
                  
                  <!-- Indicateur "COMPLET" pour les créneaux pleins -->
                  <div v-else class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="px-2 py-1 bg-red-500/80 rounded text-xs font-medium text-white shadow">
                      COMPLET
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Créneaux ouverts - overlay visuel avec colonnes dynamiques -->
            <div v-for="(day, dayIndex) in displayDays" :key="`openslots-${day.date}`"
                 class="absolute top-0 pointer-events-none"
                 :style="{ 
                   left: `${((dayIndex + 1) / totalColumns) * 100}%`, 
                   width: `${(1 / totalColumns) * 100}%`,
                   height: '100%'
                 }">
              <div v-for="slot in getOpenSlotsForDay(day.date)" :key="slot.id"
                   class="absolute left-0 right-0 rounded-md overflow-hidden pointer-events-none"
                   :style="getOpenSlotPosition(slot)">
                <!-- Fond du créneau -->
                <div :class="[
                  'absolute inset-0 border-2 border-dashed',
                  getUsedSlotsForDateTime(day.date, slot.start_time, slot) >= slot.max_capacity
                    ? 'border-red-500 bg-red-50/30'
                    : 'border-green-500 bg-green-50/30'
                ]"></div>
                
                <!-- Colonnes dynamiques pour TOUTES les capacités -->
                <div class="absolute inset-0 flex">
                  <div v-for="i in slot.max_capacity" :key="`slot-${slot.id}-${i}`"
                       :class="[
                         'flex-1 border-r border-dashed transition-all relative',
                         i <= getUsedSlotsForDateTime(day.date, slot.start_time, slot)
                           ? 'bg-red-500/20'
                           : 'bg-green-500/10 hover:bg-green-500/20',
                         i === slot.max_capacity ? 'border-r-0' : '',
                         getUsedSlotsForDateTime(day.date, slot.start_time, slot) >= slot.max_capacity
                           ? 'border-red-400'
                           : 'border-green-400'
                       ]"
                       :title="`Position ${i}${i <= getUsedSlotsForDateTime(day.date, slot.start_time, slot) ? ' - Occupée' : ' - Libre'}`">
                    <!-- Numéro de position (adaptatif selon la largeur) -->
                    <span v-if="slot.max_capacity <= 12" 
                          class="absolute top-1 left-1/2 transform -translate-x-1/2 text-[9px] font-bold opacity-40 bg-white/70 px-1 rounded">
                      {{ i }}
                    </span>
                    <!-- Indicateur visuel pour colonnes étroites (> 12) -->
                    <div v-else
                         :class="[
                           'absolute top-1 left-1/2 transform -translate-x-1/2 w-1 h-1 rounded-full',
                           i <= getUsedSlotsForDateTime(day.date, slot.start_time, slot) ? 'bg-red-500' : 'bg-green-500'
                         ]">
                    </div>
                  </div>
                </div>
                
                <!-- Badge d'information en haut du créneau -->
                <div :class="[
                  'absolute top-0 left-0 right-0 text-[10px] font-medium text-center py-1 z-10',
                  getUsedSlotsForDateTime(day.date, slot.start_time, slot) >= slot.max_capacity
                    ? 'bg-red-500/90 text-white'
                    : 'bg-green-500/90 text-white'
                ]">
                  <span class="font-bold">
                    {{ getUsedSlotsForDateTime(day.date, slot.start_time, slot) }}/{{ slot.max_capacity }}
                  </span>
                  <span class="mx-1">•</span>
                  <span>{{ slot.start_time.substring(0,5) }}-{{ slot.end_time.substring(0,5) }}</span>
                  <span class="mx-1">•</span>
                  <span>{{ getUsedSlotsForDateTime(day.date, slot.start_time, slot) >= slot.max_capacity ? 'COMPLET' : 'Ouvert' }}</span>
                </div>
              </div>
            </div>

            <!-- Cours - positionnés en absolu avec support multi-colonnes -->
            <div v-for="(day, dayIndex) in displayDays" :key="`lessons-${day.date}`"
                 class="absolute top-0 pointer-events-none"
                 :style="{ 
                   left: `${((dayIndex + 1) / totalColumns) * 100}%`, 
                   width: `${(1 / totalColumns) * 100}%`,
                   height: '100%'
                 }">
              
              <div v-for="lesson in getLessonsForDayWithColumns(day.date)" 
                   :key="lesson.id"
                   class="absolute rounded-xl border-l-4 shadow-lg hover:shadow-2xl transition-all cursor-pointer z-20 overflow-hidden pointer-events-auto"
                   :class="[
                     getLessonClass(lesson),
                     lesson.totalColumns > 2 ? 'p-1.5 text-[10px]' : 'p-3 text-xs'
                   ]"
                   :style="getLessonPositionWithColumns(lesson)"
                   @click.stop="viewLesson(lesson)"
                   :title="`${lesson.title} - ${getLessonTime(lesson)}\n${lesson.teacher_name || ''}\n${lesson.student_name || ''}`">
                
                <div class="font-bold truncate mb-1 flex items-center gap-1">
                  <span class="flex-1 truncate">{{ lesson.title }}</span>
                  <span v-if="lesson.totalColumns > 2" class="text-[9px] opacity-60">
                    {{ lesson.teacher_name?.split(' ')[0] }}
                  </span>
            </div>
                
                <div v-if="lesson.totalColumns <= 2" class="text-[11px] opacity-80 font-medium truncate mb-1">
                  ⏰ {{ getLessonTime(lesson) }}
          </div>
          
                <div v-if="lesson.teacher_name && lesson.totalColumns <= 2" class="text-[11px] opacity-75 truncate">
                  👨‍🏫 {{ lesson.teacher_name }}
            </div>
            
                <div v-if="lesson.student_name && lesson.totalColumns === 1" class="text-[11px] opacity-75 truncate mt-0.5">
                  👤 {{ lesson.student_name }}
              </div>
            </div>
          </div>
          </div>
        </div>
      </div>
          </div>

    <!-- Modal : Ajouter un créneau disponible -->
    <!-- Modale d'ajout de créneau -->
    <div v-if="showAddSlotModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ajouter un créneau disponible</h3>
        <div class="space-y-4">
              <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Jour de la semaine</label>
            <select v-model="slotForm.day_of_week" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <option value="1">Lundi</option>
              <option value="2">Mardi</option>
              <option value="3">Mercredi</option>
              <option value="4">Jeudi</option>
              <option value="5">Vendredi</option>
              <option value="6">Samedi</option>
              <option value="0">Dimanche</option>
            </select>
              </div>
          <div class="grid grid-cols-2 gap-4">
              <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heure début</label>
              <input v-model="slotForm.start_time" type="time" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              </div>
              <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heure fin</label>
              <input v-model="slotForm.end_time" type="time" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              </div>
              </div>
          
          <!-- Sélection du sport (si le club a plusieurs sports) -->
          <div v-if="clubActivities.length > 1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Sport *</label>
            <select v-model="slotForm.activity_type_id" @change="onActivityChange" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <option value="">Sélectionner un sport...</option>
              <option v-for="activity in clubActivities" :key="activity.id" :value="activity.id">
                {{ activity.name }}
              </option>
            </select>
          </div>
          
          <!-- Affichage du sport (si le club n'a qu'un seul sport) -->
          <div v-else-if="clubActivities.length === 1" class="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Sport</label>
            <p class="text-sm text-gray-900 font-medium">{{ clubActivities[0].name }}</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type de cours</label>
            <select 
              v-model="slotForm.discipline_id" 
              :disabled="filteredDisciplinesForSlot.length === 0" 
              class="w-full border border-gray-300 rounded-lg px-3 py-2" 
              :class="{'bg-gray-100 cursor-not-allowed': filteredDisciplinesForSlot.length === 0}">
              <option value="">Sélectionner...</option>
              <option v-for="discipline in filteredDisciplinesForSlot" :key="discipline.id" :value="discipline.id">{{ discipline.name }}</option>
            </select>
            <p v-if="clubActivities.length > 1 && !slotForm.activity_type_id" class="text-xs text-gray-500 mt-1">
              Veuillez d'abord sélectionner un sport pour afficher les types de cours
            </p>
            <p v-else-if="filteredDisciplinesForSlot.length === 0" class="text-xs text-red-600 mt-1">
              Aucun type de cours disponible
            </p>
            </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre maximum de cours simultanés</label>
            <input v-model.number="slotForm.max_capacity" type="number" min="1" max="10" class="w-full border border-gray-300 rounded-lg px-3 py-2">
          </div>
          <div class="grid grid-cols-2 gap-4">
          <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Durée du cours (min)</label>
              <input v-model.number="slotForm.duration" type="number" min="15" step="5" class="w-full border border-gray-300 rounded-lg px-3 py-2">
          </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Prix (€)</label>
              <input v-model.number="slotForm.price" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              </div>
            </div>
          </div>
        <div class="flex items-center justify-end space-x-3 mt-6">
          <button @click="showAddSlotModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-800">Annuler</button>
          <button @click="saveSlot" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Enregistrer</button>
        </div>
      </div>
    </div>

    <!-- Modale d'édition de créneau -->
    <div v-if="showEditSlotModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Modifier le créneau</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Jour de la semaine</label>
            <select v-model="slotForm.day_of_week" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <option value="1">Lundi</option>
              <option value="2">Mardi</option>
              <option value="3">Mercredi</option>
              <option value="4">Jeudi</option>
              <option value="5">Vendredi</option>
              <option value="6">Samedi</option>
              <option value="0">Dimanche</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heure début</label>
              <input v-model="slotForm.start_time" type="time" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Heure fin</label>
              <input v-model="slotForm.end_time" type="time" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
          </div>
          
          <!-- Sélection du sport (si le club a plusieurs sports) -->
          <div v-if="clubActivities.length > 1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Sport *</label>
            <select v-model="slotForm.activity_type_id" @change="onActivityChange" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <option value="">Sélectionner un sport...</option>
              <option v-for="activity in clubActivities" :key="activity.id" :value="activity.id">
                {{ activity.name }}
              </option>
            </select>
          </div>
          
          <!-- Affichage du sport (si le club n'a qu'un seul sport) -->
          <div v-else-if="clubActivities.length === 1" class="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Sport</label>
            <p class="text-sm text-gray-900 font-medium">{{ clubActivities[0].name }}</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type de cours</label>
            <select 
              v-model="slotForm.discipline_id" 
              :disabled="filteredDisciplinesForSlot.length === 0" 
              class="w-full border border-gray-300 rounded-lg px-3 py-2" 
              :class="{'bg-gray-100 cursor-not-allowed': filteredDisciplinesForSlot.length === 0}">
              <option value="">Sélectionner...</option>
              <option v-for="discipline in filteredDisciplinesForSlot" :key="discipline.id" :value="discipline.id">{{ discipline.name }}</option>
            </select>
            <p v-if="clubActivities.length > 1 && !slotForm.activity_type_id" class="text-xs text-gray-500 mt-1">
              Veuillez d'abord sélectionner un sport pour afficher les types de cours
            </p>
            <p v-else-if="filteredDisciplinesForSlot.length === 0" class="text-xs text-red-600 mt-1">
              Aucun type de cours disponible
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre maximum de cours simultanés</label>
            <input v-model.number="slotForm.max_capacity" type="number" min="1" max="10" class="w-full border border-gray-300 rounded-lg px-3 py-2">
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Durée du cours (min)</label>
              <input v-model.number="slotForm.duration" type="number" min="15" step="5" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Prix (€)</label>
              <input v-model.number="slotForm.price" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
          </div>
        </div>
        <div class="flex items-center justify-end space-x-3 mt-6">
          <button @click="closeEditSlotModal" class="px-4 py-2 text-gray-600 hover:text-gray-800">Annuler</button>
          <button @click="updateSlot" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Modifier</button>
        </div>
      </div>
    </div>

    <!-- Modal Créer un cours -->
    <div v-if="showCreateLessonModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl my-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Nouveau cours</h3>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input 
              v-model="lessonForm.date"
              type="date" 
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Heure</label>
            <div class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 font-medium">
              {{ lessonForm.time }}
              <span class="text-xs text-gray-500 ml-2">(définie par le créneau sélectionné)</span>
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Durée (en minutes)</label>
            <div class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-700">
              {{ lessonForm.duration }} minutes
            </div>
            <p class="text-xs text-gray-500 mt-1">La durée est définie par le créneau sélectionné</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type de cours</label>
            <div class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 font-medium">
              {{ getSelectedSlotDisciplineName() || 'Défini par le créneau' }}
            </div>
            <p class="text-xs text-gray-500 mt-1">Le type de cours est défini par le créneau sélectionné</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Enseignant
              <span v-if="availableTeachersForLesson.length < teachers.length" class="text-xs text-gray-500">
                ({{ availableTeachersForLesson.length }} disponible{{ availableTeachersForLesson.length > 1 ? 's' : '' }})
              </span>
            </label>
            <select 
              v-model="lessonForm.teacherId"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Sélectionner un enseignant</option>
              <option v-for="teacher in availableTeachersForLesson" :key="teacher.id" :value="teacher.id">
                {{ teacher.name }}
              </option>
            </select>
            <p v-if="availableTeachersForLesson.length === 0" class="text-xs text-red-600 mt-1">
              Aucun enseignant disponible sur ce créneau
            </p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Élève</label>
            <select 
              v-model="lessonForm.studentId"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Sélectionner un élève</option>
              <option v-for="student in students" :key="student.id" :value="student.id">
                {{ student.name }}
              </option>
            </select>
          </div>
          
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Prix (€)</label>
            <div class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 font-semibold">
              {{ lessonForm.price }}€
            </div>
            <p class="text-xs text-gray-500 mt-1">Le prix est fixé automatiquement selon le créneau sélectionné</p>
          </div>
          
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optionnel)</label>
            <textarea 
              v-model="lessonForm.notes"
              rows="3"
              placeholder="Notes complémentaires..."
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
          </div>
        </div>
        
        <div class="flex items-center justify-end space-x-3 mt-6">
          <button 
            @click="showCreateLessonModal = false"
            class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
          >
            Annuler
          </button>
          <button 
            @click="createLesson"
            :disabled="!lessonForm.date || !lessonForm.time || !lessonForm.teacherId"
            class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Créer le cours
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'

definePageMeta({
  middleware: ['auth']
})

// État réactif
const currentWeek = ref(new Date())
const currentDay = ref(new Date())
const viewMode = ref('day') // 'day' ou 'week'
const selectedSlot = ref(null)
const lessons = ref([])
// Créneaux ouverts (simples, par jour de semaine)
const availableSlots = ref([])
const teachers = ref([])
const students = ref([])
const clubProfile = ref(null) // Profil du club avec horaires et disciplines
const availableDisciplines = ref([]) // Disciplines disponibles du club
const availableCourseTypes = ref([]) // Types de cours disponibles
const lastUsedTeacherId = ref(null) // Dernier enseignant utilisé

// Modals
const showAddSlotModal = ref(false)
const showEditSlotModal = ref(false)
const editingSlotId = ref(null)
const showCreateLessonModal = ref(false)
// Formulaire créneau
const slotForm = ref({
  day_of_week: '1',
  start_time: '09:00',
  end_time: '10:00',
  activity_type_id: '', // Sport sélectionné
  discipline_id: '',
  max_capacity: 3,
  duration: 60,
  price: 25
})

// Jours de la semaine pour récurrence
const weekDaysRecurrence = [
  { value: 1, label: 'Lun' },
  { value: 2, label: 'Mar' },
  { value: 3, label: 'Mer' },
  { value: 4, label: 'Jeu' },
  { value: 5, label: 'Ven' },
  { value: 6, label: 'Sam' },
  { value: 0, label: 'Dim' }
]

// Formulaires
const openForm = ref({
  selectedDays: [],
  startHour: '08',
  startMinute: '00',
  endHour: '18',
  endMinute: '00',
  activityTypeId: '', // Sport sélectionné
  disciplineId: '', // Spécialité sélectionnée pour ce sport
  description: ''
})

// Computed pour les heures complètes
const computedStartTime = computed(() => `${openForm.value.startHour}:${openForm.value.startMinute}`)
const computedEndTime = computed(() => `${openForm.value.endHour}:${openForm.value.endMinute}`)

// Computed properties pour les données du profil
const availableHours = computed(() => {
  if (!clubProfile.value?.schedule_config) return hours
  
  // Extraire les heures min/max des horaires d'ouverture du club
  let minHour = 24, maxHour = 0
  
  clubProfile.value.schedule_config.forEach(day => {
    if (day.periods && day.periods.length > 0) {
      day.periods.forEach(period => {
        const startHour = parseInt(period.startHour)
        const endHour = parseInt(period.endHour)
        if (startHour < minHour) minHour = startHour
        if (endHour > maxHour) maxHour = endHour
      })
    }
  })
  
  // Si aucun horaire défini, utiliser les heures par défaut
  if (minHour === 24) return hours
  
  // Générer les heures dans la plage définie
  const result = []
  for (let i = minHour; i <= maxHour; i++) {
    result.push(i.toString().padStart(2, '0'))
  }
  return result
})

const selectedDisciplineSettings = computed(() => {
  if (!openForm.value.disciplineId || !clubProfile.value?.discipline_settings) {
    return null
  }
  return clubProfile.value.discipline_settings[openForm.value.disciplineId] || null
})

const lessonDuration = computed(() => {
  return selectedDisciplineSettings.value?.duration || 60
})

// Computed properties pour les activités et disciplines
const clubActivities = computed(() => {
  if (!clubProfile.value) return []
  
  try {
    console.log('🔍 [clubActivities] Calcul des activités du club...')
    console.log('  - activity_types:', clubProfile.value.activity_types)
    console.log('  - disciplines:', clubProfile.value.disciplines)
    
    // Priorité 1 : Utiliser activity_types si présent
    if (clubProfile.value.activity_types) {
      const activityData = typeof clubProfile.value.activity_types === 'string' 
        ? JSON.parse(clubProfile.value.activity_types) 
        : clubProfile.value.activity_types
      
      console.log('  - activityData après parsing:', activityData)
      
      if (Array.isArray(activityData) && activityData.length > 0) {
        const activityIds = activityData.map(a => typeof a === 'object' ? a.id : a)
        const activities = activityIds.map(activityTypeId => ({
          id: activityTypeId,
          name: getActivityName(activityTypeId),
          icon: getActivityIcon(activityTypeId)
        })).filter(a => a.name) // Filtrer les activités invalides
        
        console.log('✅ [clubActivities] Activités depuis activity_types:', activities)
        return activities
      }
    }
    
    // Priorité 2 : Déduire depuis les disciplines (fallback)
    console.log('  - Fallback: déduction depuis les disciplines')
    if (!clubProfile.value.disciplines) {
      console.log('  - Aucune discipline trouvée')
      return []
    }
    
    const disciplineIds = typeof clubProfile.value.disciplines === 'string' 
      ? JSON.parse(clubProfile.value.disciplines) 
      : clubProfile.value.disciplines
    
    console.log('  - disciplineIds après parsing:', disciplineIds)
    
    if (!Array.isArray(disciplineIds)) return []
    
    // Extraire les activity_type_id uniques des disciplines sélectionnées
    const activityTypeIds = new Set()
    
    disciplineIds.forEach(disciplineId => {
      const id = typeof disciplineId === 'object' ? disciplineId.id : disciplineId
      const discipline = availableDisciplines.value.find(d => d.id === parseInt(id))
      if (discipline && discipline.activity_type_id) {
        console.log(`  - Discipline ${id} -> activity_type ${discipline.activity_type_id}`)
        activityTypeIds.add(discipline.activity_type_id)
      }
    })
    
    // Retourner les activités uniques
    const activities = Array.from(activityTypeIds).map(activityTypeId => ({
      id: activityTypeId,
      name: getActivityName(activityTypeId),
      icon: getActivityIcon(activityTypeId)
    })).filter(a => a.name) // Filtrer les activités invalides
    
    console.log('✅ [clubActivities] Activités déduites:', activities)
    return activities
  } catch (e) {
    console.warn('Erreur parsing disciplines/activités du club:', e)
    return []
  }
})

const availableDisciplinesForActivity = computed(() => {
  if (!openForm.value.activityTypeId || !clubProfile.value?.disciplines) return []
  
  try {
    // Récupérer les disciplines sélectionnées du club
    const clubDisciplineIds = typeof clubProfile.value.disciplines === 'string' 
      ? JSON.parse(clubProfile.value.disciplines) 
      : clubProfile.value.disciplines
    
    if (!Array.isArray(clubDisciplineIds)) return []
    
    // Convertir en nombres si nécessaire
    const clubDisciplineIdNumbers = clubDisciplineIds.map(id => 
      typeof id === 'object' ? id.id : parseInt(id)
    )
    
    // Filtrer les disciplines pour cette activité ET que le club propose
    return availableDisciplines.value.filter(discipline => 
      discipline.activity_type_id === parseInt(openForm.value.activityTypeId) &&
      clubDisciplineIdNumbers.includes(discipline.id)
    )
  } catch (e) {
    console.warn('Erreur parsing disciplines du club pour filtrage:', e)
    return []
  }
})

// Disciplines filtrées pour la modale d'ajout de créneau
const filteredDisciplinesForSlot = computed(() => {
  if (!clubProfile.value?.disciplines) return []
  
  try {
    // Récupérer les disciplines sélectionnées du club
    const clubDisciplineIds = typeof clubProfile.value.disciplines === 'string' 
      ? JSON.parse(clubProfile.value.disciplines) 
      : clubProfile.value.disciplines
    
    if (!Array.isArray(clubDisciplineIds)) return []
    
    // Convertir en nombres si nécessaire
    const clubDisciplineIdNumbers = clubDisciplineIds.map(id => 
      typeof id === 'object' ? id.id : parseInt(id)
    )
    
    // Récupérer les IDs des activités sélectionnées
    const selectedActivityIds = clubActivities.value.map(a => a.id)
    
    // Si le club a plusieurs sports et qu'un sport est sélectionné, filtrer par sport
    if (clubActivities.value.length > 1 && slotForm.value.activity_type_id) {
      return availableDisciplines.value.filter(discipline => 
        discipline.activity_type_id === parseInt(slotForm.value.activity_type_id) &&
        clubDisciplineIdNumbers.includes(discipline.id) &&
        selectedActivityIds.includes(discipline.activity_type_id) // Vérifier que l'activité est sélectionnée
      )
    }
    
    // Si le club n'a qu'un seul sport, afficher toutes les disciplines de ce sport
    if (clubActivities.value.length === 1) {
      const activityId = clubActivities.value[0].id
      return availableDisciplines.value.filter(discipline => 
        discipline.activity_type_id === activityId &&
        clubDisciplineIdNumbers.includes(discipline.id) &&
        selectedActivityIds.includes(discipline.activity_type_id) // Vérifier que l'activité est sélectionnée
      )
    }
    
    // Par défaut, afficher uniquement les disciplines dont l'activité est sélectionnée
    return availableDisciplines.value.filter(discipline => 
      clubDisciplineIdNumbers.includes(discipline.id) &&
      selectedActivityIds.includes(discipline.activity_type_id) // Vérifier que l'activité est sélectionnée
    )
  } catch (e) {
    console.warn('Erreur parsing disciplines du club pour filtrage slot:', e)
    return []
  }
})

// Enseignants disponibles (non occupés) sur le créneau sélectionné
const availableTeachersForLesson = computed(() => {
  if (!lessonForm.value.date || !lessonForm.value.time) {
    return teachers.value
  }

  // Obtenir les cours déjà programmés sur ce créneau
  const lessonsAtSlot = lessons.value.filter(lesson => {
    if (!lesson.start_time) return false
    
    // Extraire la date et l'heure du cours
    let lessonDate, lessonTime
    if (lesson.start_time.includes('T')) {
      [lessonDate, lessonTime] = lesson.start_time.split('T')
      lessonTime = lessonTime.substring(0, 5) // HH:MM
    } else if (lesson.start_time.includes(' ')) {
      [lessonDate, lessonTime] = lesson.start_time.split(' ')
      lessonTime = lessonTime.substring(0, 5) // HH:MM
    } else {
      return false
    }
    
    return lessonDate === lessonForm.value.date && lessonTime === lessonForm.value.time
  })
  
  // IDs des enseignants déjà occupés
  const occupiedTeacherIds = lessonsAtSlot.map(l => l.teacher_id).filter(id => id)
  
  // Retourner uniquement les enseignants disponibles
  return teachers.value.filter(teacher => !occupiedTeacherIds.includes(teacher.id))
})

// Fonctions utilitaires pour récupérer les noms et icônes des activités
const getActivityName = (activityTypeId) => {
  const activityNames = {
    1: 'Équitation',
    2: 'Natation', 
    3: 'Fitness',
    4: 'Sports collectifs',
    5: 'Arts martiaux',
    6: 'Danse',
    7: 'Tennis',
    8: 'Gymnastique'
  }
  return activityNames[activityTypeId] || `Activité ${activityTypeId}`
}

const getActivityIcon = (activityTypeId) => {
  const activityIcons = {
    1: 'horse',
    2: 'swimmer', 
    3: 'dumbbell',
    4: 'futbol',
    5: 'fist-raised',
    6: 'music',
    7: 'table-tennis',
    8: 'child'
  }
  return activityIcons[activityTypeId] || 'star'
}

const lessonForm = ref({
  date: '',
  time: '',
  duration: '60',
  courseTypeId: '',
  teacherId: '',
  studentId: '',
  price: '50.00',
  notes: ''
})

// Configuration des prix par type de cours et durée
// Ces prix seront surchargés par les prix définis dans la base de données si disponibles
const coursePrices = {
  '1': { // Cours individuel (défaut)
    '15': 20,
    '20': 25,
    '30': 35,
    '45': 50,
    '60': 65,
    '90': 90
  },
  '2': { // Cours de groupe (défaut)
    '15': 12,
    '20': 15,
    '30': 20,
    '45': 30,
    '60': 40,
    '90': 55
  },
  '3': { // Entraînement (défaut)
    '15': 25,
    '20': 30,
    '30': 40,
    '45': 55,
    '60': 70,
    '90': 100
  },
  '4': { // Compétition (défaut)
    '15': 30,
    '20': 35,
    '30': 50,
    '45': 70,
    '60': 90,
    '90': 130
  }
}

// Fonction pour mettre à jour le prix automatiquement
const updateLessonPrice = () => {
  const courseTypeId = lessonForm.value.courseTypeId
  const duration = lessonForm.value.duration
  
  if (!courseTypeId) return
  
  // 1. Essayer d'utiliser le prix défini dans le type de cours (si durée correspond)
  const courseType = availableCourseTypes.value.find(ct => ct.id.toString() === courseTypeId)
  if (courseType && courseType.duration && courseType.price && courseType.duration.toString() === duration) {
    lessonForm.value.price = courseType.price.toString()
    return
  }
  
  // 2. Sinon, utiliser la grille de prix par défaut
  if (coursePrices[courseTypeId] && coursePrices[courseTypeId][duration]) {
    lessonForm.value.price = coursePrices[courseTypeId][duration].toString()
    return
  }
  
  // 3. Calculer proportionnellement si le type de cours a un prix de base
  if (courseType && courseType.price && courseType.duration) {
    const pricePerMinute = parseFloat(courseType.price) / parseInt(courseType.duration)
    lessonForm.value.price = (pricePerMinute * parseInt(duration)).toFixed(2)
  }
}

// Configuration des heures à afficher (de 6h à 22h par défaut)
const hourRanges = computed(() => {
  if (!clubProfile.value?.schedule_config) {
    // Fallback: 6h-22h
    return Array.from({ length: 17 }, (_, i) => i + 6)
  }
  
  // Extraire les heures min/max de toutes les périodes configurées
  let minHour = 24, maxHour = 0
  
  clubProfile.value.schedule_config.forEach(day => {
    if (day.periods && day.periods.length > 0) {
      day.periods.forEach(period => {
        const startHour = parseInt(period.startHour)
        const endHour = parseInt(period.endHour)
        if (startHour < minHour) minHour = startHour
        if (endHour > maxHour) maxHour = endHour
      })
    }
  })
  
  // Si aucune période définie, utiliser les heures par défaut
  if (minHour === 24) {
    minHour = 6
    maxHour = 22
  }
  
  // Générer la liste des heures
  const ranges = []
  for (let hour = minHour; hour <= maxHour; hour++) {
    ranges.push(hour)
  }
  
  return ranges
})

// Pour le select de temps dans les modals
const timeSlots = computed(() => {
  const slots = []
  for (let hour = 6; hour <= 22; hour++) {
    for (let minute = 0; minute < 60; minute += 5) {
      const timeStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`
      slots.push(timeStr)
    }
  }
  return slots
})

// Heures et minutes pour les selects
const hours = Array.from({ length: 17 }, (_, i) => (i + 6).toString().padStart(2, '0')) // 06-22
const minutes = Array.from({ length: 12 }, (_, i) => (i * 5).toString().padStart(2, '0')) // 00, 05, 10, ..., 55

// Jours de la semaine courante
const weekDays = computed(() => {
  const start = new Date(currentWeek.value)
  start.setDate(start.getDate() - start.getDay() + 1) // Lundi
  
  const days = []
  for (let i = 0; i < 7; i++) {
    const day = new Date(start)
    day.setDate(start.getDate() + i)
    
    days.push({
      date: day.toISOString().split('T')[0],
      name: day.toLocaleDateString('fr-FR', { weekday: 'short' }),
      dayNumber: day.getDate()
    })
  }
  
  return days
})

// Jour unique pour la vue journée
const singleDay = computed(() => {
  const day = new Date(currentDay.value)
  return [{
    date: day.toISOString().split('T')[0],
    name: day.toLocaleDateString('fr-FR', { weekday: 'long' }),
    dayNumber: day.getDate()
  }]
})

// Jours à afficher selon le mode
const displayDays = computed(() => {
  return viewMode.value === 'week' ? weekDays.value : singleDay.value
})

// Nombre total de colonnes (incluant la colonne horaires)
const totalColumns = computed(() => {
  return viewMode.value === 'week' ? 8 : 2
})

// Navigation semaine
const previousWeek = () => {
  const newWeek = new Date(currentWeek.value)
  newWeek.setDate(newWeek.getDate() - 7)
  currentWeek.value = newWeek
  loadPlanningData()
}

const nextWeek = () => {
  const newWeek = new Date(currentWeek.value)
  newWeek.setDate(newWeek.getDate() + 7)
  currentWeek.value = newWeek
  loadPlanningData()
}

// Navigation jour
const previousDay = () => {
  const newDay = new Date(currentDay.value)
  newDay.setDate(newDay.getDate() - 1)
  currentDay.value = newDay
  loadPlanningData()
}

const nextDay = () => {
  const newDay = new Date(currentDay.value)
  newDay.setDate(newDay.getDate() + 1)
  currentDay.value = newDay
  loadPlanningData()
}

const goToToday = () => {
  const today = new Date()
  currentWeek.value = today
  currentDay.value = today
  loadPlanningData()
}

// Utilitaires dates
const formatWeekRange = (date) => {
  const start = new Date(date)
  start.setDate(start.getDate() - start.getDay() + 1)
  const end = new Date(start)
  end.setDate(start.getDate() + 6)
  
  return `${start.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })} - ${end.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}`
}

const formatDayTitle = (date) => {
  const day = new Date(date)
  return day.toLocaleDateString('fr-FR', { 
    weekday: 'long', 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  })
}

const formatDate = (dateStr) => {
  const date = new Date(dateStr)
  return date.getDate()
}

const isToday = (dateStr) => {
  const today = new Date()
  const date = new Date(dateStr)
  return today.toISOString().split('T')[0] === date.toISOString().split('T')[0]
}

// Gestion des créneaux - nouvelle version flexible
// Ouvrir la modal d'ajout de créneau avec préchargement intelligent
const openAddSlotModal = (slotToDuplicate = null) => {
  if (slotToDuplicate) {
    // Dupliquer un créneau existant
    console.log('🔄 Duplication du créneau:', slotToDuplicate)
    
    // Récupérer l'activity_type_id de la discipline
    const discipline = availableDisciplines.value.find(d => d.id === slotToDuplicate.discipline_id)
    
    slotForm.value = {
      day_of_week: slotToDuplicate.day_of_week.toString(),
      start_time: slotToDuplicate.start_time,
      end_time: slotToDuplicate.end_time,
      activity_type_id: discipline?.activity_type_id?.toString() || 
                        (clubActivities.value.length === 1 ? clubActivities.value[0].id.toString() : ''),
      discipline_id: slotToDuplicate.discipline_id?.toString() || '',
      max_capacity: slotToDuplicate.max_capacity || 3,
      duration: slotToDuplicate.duration || 60,
      price: parseFloat(slotToDuplicate.price) || 25
    }
    
    console.log('✅ Formulaire préchargé:', slotForm.value)
  } else if (availableSlots.value.length > 0) {
    // Précharger avec les valeurs du dernier créneau créé
    const lastSlot = availableSlots.value[availableSlots.value.length - 1]
    const discipline = availableDisciplines.value.find(d => d.id === lastSlot.discipline_id)
    
    slotForm.value = {
      day_of_week: '1', // Lundi par défaut
      start_time: lastSlot.start_time || '09:00',
      end_time: lastSlot.end_time || '10:00',
      activity_type_id: discipline?.activity_type_id?.toString() || 
                        (clubActivities.value.length === 1 ? clubActivities.value[0].id.toString() : ''),
      discipline_id: lastSlot.discipline_id?.toString() || '',
      max_capacity: lastSlot.max_capacity || 3,
      duration: lastSlot.duration || 60,
      price: parseFloat(lastSlot.price) || 25
    }
  } else {
    // Réinitialiser avec des valeurs par défaut
    slotForm.value = {
      day_of_week: '1',
      start_time: '09:00',
      end_time: '10:00',
      activity_type_id: clubActivities.value.length === 1 ? clubActivities.value[0].id.toString() : '',
      discipline_id: '',
      max_capacity: 3,
      duration: 60,
      price: 25
    }
  }
  
  showAddSlotModal.value = true
}

// Fonction pour récupérer les settings d'une discipline depuis le profil club
const getDisciplineSettings = (disciplineId) => {
  if (!clubProfile.value?.discipline_settings || !disciplineId) {
    return { duration: 60, price: 25 }
  }
  
  try {
    const settings = typeof clubProfile.value.discipline_settings === 'string'
      ? JSON.parse(clubProfile.value.discipline_settings)
      : clubProfile.value.discipline_settings
    
    const disciplineSettings = settings[disciplineId]
    if (disciplineSettings) {
      return {
        duration: disciplineSettings.duration || 60,
        price: disciplineSettings.price || 25
      }
    }
  } catch (e) {
    console.warn('Erreur lors de la récupération des settings de la discipline:', e)
  }
  
  return { duration: 60, price: 25 }
}

const selectTimeSlot = (date, hour, minute) => {
  const timeStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`
  
  // Trouver le créneau correspondant à cette date/heure (PRIORITÉ 1)
  const dayOfWeek = new Date(date).getDay()
  const slot = availableSlots.value.find(s => {
    if (parseInt(s.day_of_week) !== dayOfWeek) return false
    
    // Normaliser les heures du slot au format HH:MM pour comparaison
    const slotStart = s.start_time.substring(0, 5)
    const slotEnd = s.end_time.substring(0, 5)
    
    return timeStr >= slotStart && timeStr < slotEnd
  })
  
  // Si aucun créneau ouvert n'existe, vérifier les horaires généraux du club
  if (!slot) {
    if (!isDayHourInSchedule(date, hour)) {
      alert('Ce créneau est en dehors des heures d\'ouverture du club. Configurez d\'abord les horaires dans le profil.')
      return
    }
    alert('Aucun créneau n\'est configuré pour cet horaire. Veuillez d\'abord ajouter un créneau disponible.')
    return
  }
  
  // Vérifier si le créneau n'est pas plein pour cette date/heure spécifique
  const usedCount = getUsedSlotsForDateTime(date, hour, slot)
  if (usedCount >= slot.max_capacity) {
    alert(`Ce créneau est complet (${usedCount}/${slot.max_capacity} cours). Impossible d'ajouter un nouveau cours.`)
    return
  }
  
  selectedSlot.value = { date, hour: timeStr, slot }
  
  // Précharger toutes les données du créneau dans le formulaire de cours
  lessonForm.value.date = date
  lessonForm.value.time = timeStr
  lessonForm.value.duration = slot.duration?.toString() || '60'
  lessonForm.value.price = slot.price?.toString() || '50.00'
  lessonForm.value.courseTypeId = slot.discipline_id ? slot.discipline_id.toString() : ''
  
  // Réinitialiser les champs spécifiques au cours
  lessonForm.value.studentId = ''
  lessonForm.value.notes = ''
  
  // Présélectionner le dernier enseignant utilisé s'il est disponible
  // On attendra que availableTeachersForLesson soit recalculé dans nextTick
  setTimeout(() => {
    if (lastUsedTeacherId.value && availableTeachersForLesson.value.find(t => t.id === lastUsedTeacherId.value)) {
      lessonForm.value.teacherId = lastUsedTeacherId.value.toString()
    } else if (availableTeachersForLesson.value.length === 1) {
      // S'il n'y a qu'un seul enseignant disponible, le sélectionner automatiquement
      lessonForm.value.teacherId = availableTeachersForLesson.value[0].id.toString()
    } else {
      lessonForm.value.teacherId = ''
    }
  }, 50)
  
  showCreateLessonModal.value = true
}

const isSlotSelected = (date, hour) => {
  return selectedSlot.value?.date === date && selectedSlot.value?.hour === hour
}

// Vérifier si une heure est dans les horaires d'ouverture du club
const isDayHourInSchedule = (date, hour) => {
  if (!clubProfile.value?.schedule_config) return true
  
  const dayOfWeek = new Date(date).getDay()
  const scheduleConfig = clubProfile.value.schedule_config
  const dayConfig = scheduleConfig[dayOfWeek === 0 ? 6 : dayOfWeek - 1]
  
  if (!dayConfig || !dayConfig.periods || dayConfig.periods.length === 0) {
    return false
  }
  
  // Vérifier si l'heure est dans une des périodes d'ouverture du jour
  return dayConfig.periods.some(period => {
    const startHour = parseInt(period.startHour)
    const endHour = parseInt(period.endHour)
    return hour >= startHour && hour < endHour
  })
}

// Récupérer tous les cours d'un jour donné
const getLessonsForDay = (date) => {
  return lessons.value.filter(lesson => {
    if (!lesson.start_time) return false
    
    let lessonDate
    
    if (lesson.start_time.includes('T')) {
      lessonDate = lesson.start_time.split('T')[0]
    } else if (lesson.start_time.includes(' ')) {
      lessonDate = lesson.start_time.split(' ')[0]
    } else {
      const lessonDateTime = new Date(lesson.start_time)
      if (isNaN(lessonDateTime.getTime())) return false
      lessonDate = lessonDateTime.toISOString().split('T')[0]
    }
    
    return lessonDate === date
  })
}

// Vérifier si deux cours se chevauchent
const lessonsOverlap = (lesson1, lesson2) => {
  const start1 = getLessonStartMinutes(lesson1)
  const end1 = start1 + lesson1.duration
  const start2 = getLessonStartMinutes(lesson2)
  const end2 = start2 + lesson2.duration
  
  return start1 < end2 && start2 < end1
}

// Obtenir l'heure de début d'un cours en minutes depuis minuit
const getLessonStartMinutes = (lesson) => {
  let startHour, startMinute
  
  if (lesson.start_time.includes('T')) {
    const timePart = lesson.start_time.split('T')[1]
    const [h, m] = timePart.substring(0, 5).split(':')
    startHour = parseInt(h)
    startMinute = parseInt(m)
  } else if (lesson.start_time.includes(' ')) {
    const timePart = lesson.start_time.split(' ')[1]
    const [h, m] = timePart.substring(0, 5).split(':')
    startHour = parseInt(h)
    startMinute = parseInt(m)
  } else {
    const lessonDateTime = new Date(lesson.start_time)
    startHour = lessonDateTime.getHours()
    startMinute = lessonDateTime.getMinutes()
  }
  
  return startHour * 60 + startMinute
}

// Récupérer les cours d'un jour avec calcul des colonnes basé sur les créneaux ouverts
const getLessonsForDayWithColumns = (date) => {
  const dayLessons = getLessonsForDay(date)
  
  if (dayLessons.length === 0) return []
  
  const dow = new Date(date).getDay()
  const slotsForDay = availableSlots.value.filter(s => parseInt(s.day_of_week) === dow)
  
  // Pour chaque cours, trouver son créneau et sa position
  const lessonsWithColumns = dayLessons.map(lesson => {
    // Extraire l'heure de début du cours
    let lessonStartTime
    if (lesson.start_time.includes('T')) {
      lessonStartTime = lesson.start_time.split('T')[1].substring(0, 5)
    } else if (lesson.start_time.includes(' ')) {
      lessonStartTime = lesson.start_time.split(' ')[1].substring(0, 5)
    } else {
      const dt = new Date(lesson.start_time)
      lessonStartTime = `${dt.getHours().toString().padStart(2, '0')}:${dt.getMinutes().toString().padStart(2, '0')}`
    }
    
    // Trouver le créneau ouvert qui contient ce cours
    const slot = slotsForDay.find(s => 
      lessonStartTime >= s.start_time.substring(0, 5) && 
      lessonStartTime < s.end_time.substring(0, 5)
    )
    
    if (!slot) {
      // Cours hors créneau (ne devrait pas arriver)
      return {
        ...lesson,
        column: 0,
        totalColumns: 1,
        slotId: null
      }
    }
    
    // Trouver tous les cours de ce créneau qui commencent en même temps ou avant
    const coursesInSlot = dayLessons.filter(l => {
      let lStartTime
      if (l.start_time.includes('T')) {
        lStartTime = l.start_time.split('T')[1].substring(0, 5)
      } else if (l.start_time.includes(' ')) {
        lStartTime = l.start_time.split(' ')[1].substring(0, 5)
      } else {
        const dt = new Date(l.start_time)
        lStartTime = `${dt.getHours().toString().padStart(2, '0')}:${dt.getMinutes().toString().padStart(2, '0')}`
      }
      return lStartTime >= slot.start_time.substring(0, 5) && 
             lStartTime < slot.end_time.substring(0, 5) &&
             l.id <= lesson.id // Cours avec ID inférieur ou égal (pour l'ordre)
    })
    
    // La position du cours = l'index dans la liste des cours de ce créneau
    const column = coursesInSlot.length - 1
    
    return {
      ...lesson,
      column,
      totalColumns: slot.max_capacity,
      slotId: slot.id,
      slotCapacity: slot.max_capacity
    }
  })
  
  return lessonsWithColumns
}

// Calculer la position d'un cours avec support des colonnes multiples
const getLessonPositionWithColumns = (lesson) => {
  if (!lesson.start_time || !lesson.duration) return { top: '0px', height: '60px', left: '4px', width: 'calc(100% - 8px)' }
  
  // Parser l'heure de début
  let startHour, startMinute
  
  if (lesson.start_time.includes('T')) {
    const timePart = lesson.start_time.split('T')[1]
    const [h, m] = timePart.substring(0, 5).split(':')
    startHour = parseInt(h)
    startMinute = parseInt(m)
  } else if (lesson.start_time.includes(' ')) {
    const timePart = lesson.start_time.split(' ')[1]
    const [h, m] = timePart.substring(0, 5).split(':')
    startHour = parseInt(h)
    startMinute = parseInt(m)
  } else {
    const lessonDateTime = new Date(lesson.start_time)
    startHour = lessonDateTime.getHours()
    startMinute = lessonDateTime.getMinutes()
  }
  
  // Heure de début du calendrier
  const calendarStartHour = hourRanges.value[0] || 6
  
  // Calculer le décalage depuis le début du calendrier en minutes
  const offsetMinutes = (startHour - calendarStartHour) * 60 + startMinute
  
  // Chaque heure fait 60px de hauteur
  const pixelsPerMinute = 60 / 60 // 1px par minute
  const top = offsetMinutes * pixelsPerMinute
  
  // Calculer la hauteur en fonction de la durée
  const height = lesson.duration * pixelsPerMinute
  
  // Calculer la largeur et la position en fonction des colonnes
  const column = lesson.column || 0
  const totalColumns = lesson.totalColumns || 1
  
  // Largeur = (100% / nombre de colonnes) - un petit gap
  const widthPercent = 100 / totalColumns
  const leftPercent = column * widthPercent
  
  // Ajouter un petit espacement entre les cours (2px de chaque côté)
  const gapPx = 2
  
  console.log('📍 Position cours:', {
    lessonId: lesson.id,
    title: lesson.title,
    startTime: lesson.start_time,
    parsed: { startHour, startMinute },
    duration: lesson.duration,
    calendarStartHour,
    calculation: `(${startHour} - ${calendarStartHour}) * 60 + ${startMinute} = ${offsetMinutes}`,
    result: {
      top: `${top}px`,
      height: `${Math.max(height, 40)}px`
    }
  })
  
  return {
    top: `${top}px`,
    height: `${Math.max(height, 40)}px`,
    left: `calc(${leftPercent}% + ${gapPx}px)`,
    width: `calc(${widthPercent}% - ${gapPx * 2}px)`
  }
}

// Formater l'heure d'affichage du cours
const getLessonTime = (lesson) => {
  if (!lesson.start_time || !lesson.duration) return ''
  
  let startHour, startMinute
  
  if (lesson.start_time.includes('T')) {
    const timePart = lesson.start_time.split('T')[1]
    const [h, m] = timePart.substring(0, 5).split(':')
    startHour = parseInt(h)
    startMinute = parseInt(m)
  } else if (lesson.start_time.includes(' ')) {
    const timePart = lesson.start_time.split(' ')[1]
    const [h, m] = timePart.substring(0, 5).split(':')
    startHour = parseInt(h)
    startMinute = parseInt(m)
  } else {
    const lessonDateTime = new Date(lesson.start_time)
    startHour = lessonDateTime.getHours()
    startMinute = lessonDateTime.getMinutes()
  }
  
  // Calculer l'heure de fin
  const endMinutes = startMinute + lesson.duration
  const endHour = startHour + Math.floor(endMinutes / 60)
  const endMinute = endMinutes % 60
  
  return `${startHour.toString().padStart(2, '0')}:${startMinute.toString().padStart(2, '0')} - ${endHour.toString().padStart(2, '0')}:${endMinute.toString().padStart(2, '0')} (${lesson.duration}min)`
}

// Créneaux ouverts - helpers
const getOpenSlotsForDay = (date) => {
  const dow = new Date(date).getDay()
  return availableSlots.value.filter(s => parseInt(s.day_of_week) === dow)
}

const getOpenSlotPosition = (slot) => {
  // Heure de début du calendrier (première heure affichée)
  const calendarStartHour = hourRanges.value[0] || 6
  
  // Parser les heures de début et fin du créneau
  const [startH, startM, startS] = slot.start_time.split(':').map(n => parseInt(n))
  const [endH, endM, endS] = slot.end_time.split(':').map(n => parseInt(n))
  
  // Calculer les offsets en minutes depuis le début du calendrier
  const startOffsetMinutes = (startH - calendarStartHour) * 60 + startM
  const endOffsetMinutes = (endH - calendarStartHour) * 60 + endM
  
  // Convertir en pixels (1 minute = 1 pixel)
  const topPixels = startOffsetMinutes
  const heightPixels = Math.max(endOffsetMinutes - startOffsetMinutes, 20)
  
  // Debug log pour diagnostiquer les décalages
  console.log('🎯 Position créneau DÉTAILLÉ:', {
    slotId: slot.id,
    slotRaw: slot,
    startTime: slot.start_time,
    endTime: slot.end_time,
    parsed: { startH, startM, endH, endM },
    calendarStartHour,
    calculation: {
      startOffsetMinutes: `(${startH} - ${calendarStartHour}) * 60 + ${startM} = ${startOffsetMinutes}`,
      endOffsetMinutes: `(${endH} - ${calendarStartHour}) * 60 + ${endM} = ${endOffsetMinutes}`,
    },
    result: {
      top: `${topPixels}px`,
      height: `${heightPixels}px`
    },
    capacity: slot.max_capacity
  })
  
  return { 
    top: `${topPixels}px`, 
    height: `${heightPixels}px` 
  }
}

const getUsedSlots = (slot) => {
  // Compte le nombre de cours qui démarrent dans la plage ouverte (pour le jour de la semaine)
  return lessons.value.filter(lesson => {
    const dt = lesson.start_time.includes('T') ? new Date(lesson.start_time) : new Date(lesson.start_time.replace(' ', 'T'))
    const dow = dt.getDay()
    const t = dt.toTimeString().substring(0,5)
    return dow === parseInt(slot.day_of_week) && t >= slot.start_time && t < slot.end_time
  }).length
}

// Compte les cours sur une date et heure spécifiques
const getUsedSlotsForDateTime = (date, hour, slot) => {
  if (!slot) return 0
  
  const timeStr = typeof hour === 'string' ? hour : `${hour.toString().padStart(2, '0')}:00`
  
  return lessons.value.filter(lesson => {
    if (!lesson.start_time) return false
    
    // Extraire date et heure du cours
    let lessonDate, lessonTime
    if (lesson.start_time.includes('T')) {
      [lessonDate, lessonTime] = lesson.start_time.split('T')
      lessonTime = lessonTime.substring(0, 5)
    } else if (lesson.start_time.includes(' ')) {
      [lessonDate, lessonTime] = lesson.start_time.split(' ')
      lessonTime = lessonTime.substring(0, 5)
    } else {
      return false
    }
    
    // Normaliser les bornes du slot au format HH:MM
    const slotStart = slot.start_time.substring(0, 5)
    const slotEnd = slot.end_time.substring(0, 5)
    
    // Vérifier si le cours est sur cette date ET dans la plage horaire du slot
    return lessonDate === date && lessonTime >= slotStart && lessonTime < slotEnd
  }).length
}

// Vérifie si un créneau spécifique (date + heure) est complet OU inexistant
const isSlotFull = (date, hour) => {
  const dayOfWeek = new Date(date).getDay()
  
  // Normaliser l'heure au format HH:MM
  const timeStr = typeof hour === 'string' 
    ? hour.substring(0, 5) 
    : `${hour.toString().padStart(2, '0')}:00`
  
  // Trouver le créneau qui contient cette heure
  const slot = availableSlots.value.find(s => {
    if (parseInt(s.day_of_week) !== dayOfWeek) return false
    
    // Normaliser les heures du slot au format HH:MM
    const slotStart = s.start_time.substring(0, 5)
    const slotEnd = s.end_time.substring(0, 5)
    
    // Vérifier si timeStr est dans [slotStart, slotEnd)
    return timeStr >= slotStart && timeStr < slotEnd
  })
  
  // Si aucun créneau n'existe pour cette heure, la case n'est pas cliquable
  if (!slot) return true
  
  // Vérifier si le créneau est plein
  return getUsedSlotsForDateTime(date, timeStr, slot) >= slot.max_capacity
}


const onActivityChange = () => {
  // Réinitialiser la discipline sélectionnée quand le sport change
  slotForm.value.discipline_id = ''
}

const saveSlot = async () => {
  try {
    // Validation : vérifier que le sport est sélectionné si nécessaire
    if (clubActivities.value.length > 1 && !slotForm.value.activity_type_id) {
      alert('Veuillez sélectionner un sport')
      return
    }
    
    // Validation : vérifier qu'un type de cours est sélectionné
    if (!slotForm.value.discipline_id) {
      alert('Veuillez sélectionner un type de cours')
      return
    }
    
    const { $api } = useNuxtApp()
    
    // Préparer les données pour l'API
    const slotData = {
      day_of_week: parseInt(slotForm.value.day_of_week),
      start_time: slotForm.value.start_time,
      end_time: slotForm.value.end_time,
      discipline_id: slotForm.value.discipline_id ? parseInt(slotForm.value.discipline_id) : null,
      max_capacity: parseInt(slotForm.value.max_capacity),
      duration: parseInt(slotForm.value.duration),
      price: parseFloat(slotForm.value.price)
    }
    
    console.log('📤 Envoi du créneau:', slotData)
    console.log('📋 Discipline sélectionnée:', {
      id: slotData.discipline_id,
      type: typeof slotData.discipline_id,
      discipline: availableDisciplines.value.find(d => d.id === slotData.discipline_id)
    })
    console.log('📚 Toutes les disciplines disponibles:', availableDisciplines.value.map(d => ({
      id: d.id,
      name: d.name,
      activity_type_id: d.activity_type_id
    })))
    console.log('🎯 La discipline 11 existe?', availableDisciplines.value.find(d => d.id === 11))
    
    // Appeler l'API backend
    const response = await $api.post('/club/open-slots', slotData)
    
    if (response.data.success) {
      console.log('✅ Créneau créé avec succès:', response.data.data)
      
      // Recharger les créneaux depuis l'API
      await loadOpenSlots()
      
      showAddSlotModal.value = false
      
      // Réinitialiser le formulaire
      slotForm.value = {
        day_of_week: '1',
        start_time: '09:00',
        end_time: '10:00',
        activity_type_id: '',
        discipline_id: '',
        max_capacity: 3,
        duration: 60,
        price: 25
      }
    }
  } catch (e) {
    console.error('Erreur lors de la création du créneau:', e)
    console.error('Détails complets de l\'erreur:', {
      status: e.response?.status,
      statusText: e.response?.statusText,
      data: e.response?.data,
      errors: e.response?.data?.errors,
      message: e.response?.data?.message
    })
    
    if (e.response?.data?.errors) {
      const errors = e.response.data.errors
      console.error('🔴 Erreurs de validation détaillées:', errors)
      
      const errorMessages = Object.entries(errors)
        .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
        .join('\n')
      
      alert(`Erreur de validation:\n\n${errorMessages}`)
    } else if (e.response?.data?.message) {
      alert(`Erreur: ${e.response.data.message}`)
    } else {
      alert('Erreur lors de la création du créneau.')
    }
  }
}


// Helpers pour la modal d'ouverture
const getSelectedDayLabels = () => {
  return openForm.value.selectedDays.map(dayValue => 
    weekDaysRecurrence.find(day => day.value === dayValue)?.label
  ).filter(Boolean)
}

const calculateTimeSlots = () => {
  if (!computedStartTime.value || !computedEndTime.value) return 0
  
  const start = timeToMinutes(computedStartTime.value)
  const end = timeToMinutes(computedEndTime.value)
  const duration = parseInt(openForm.value.lessonDuration)
  
  return Math.floor((end - start) / duration)
}

const timeToMinutes = (time) => {
  const [hours, minutes] = time.split(':').map(Number)
  return hours * 60 + minutes
}

const getLessonClass = (lesson) => {
  const statusClasses = {
    'confirmed': 'bg-blue-100 border-blue-500 text-blue-900',
    'pending': 'bg-yellow-100 border-yellow-500 text-yellow-900',
    'completed': 'bg-green-100 border-green-500 text-green-900',
    'cancelled': 'bg-gray-100 border-gray-400 text-gray-700 line-through'
  }
  return statusClasses[lesson.status] || 'bg-blue-100 border-blue-500 text-blue-900'
}

// Voir les détails d'un cours
const viewLesson = (lesson) => {
  // TODO: Implémenter l'affichage des détails du cours
  console.log('Voir le cours:', lesson)
  alert(`Cours: ${lesson.title}\nEnseignant: ${lesson.teacher_name}\nÉlève: ${lesson.student_name || 'Non assigné'}\nStatut: ${lesson.status}`)
}

// Actions
const openRecurrentSlots = async () => {
  try {
    console.log('✅ Ouverture des créneaux récurrents:', openForm.value)
    
    const selectedDiscipline = availableDisciplines.value.find(d => d.id === parseInt(openForm.value.disciplineId))
    
    // Ajouter localement (en attendant l'API backend)
    const newOpenSlot = {
      id: Date.now(), // ID temporaire
      days: [...openForm.value.selectedDays],
      startTime: computedStartTime.value,
      endTime: computedEndTime.value,
      disciplineId: openForm.value.disciplineId,
      disciplineName: selectedDiscipline?.name || '',
      lessonDuration: lessonDuration.value,
      price: selectedDisciplineSettings.value?.price || 0,
      description: openForm.value.description,
      isActive: true
    }
    
    openSlots.value.push(newOpenSlot)
    
    // TODO: Appeler l'API backend pour persister
    
    showOpenSlotModal.value = false
    openForm.value = {
      selectedDays: [],
      startHour: '08',
      startMinute: '00',
      endHour: '18',
      endMinute: '00',
      activityTypeId: '',
      disciplineId: '',
      description: ''
    }
    
    console.log('✅ Créneaux ouverts avec succès')
    
  } catch (error) {
    console.error('Erreur lors de l\'ouverture des créneaux:', error)
  }
}

// Fonction pour vérifier si un créneau est ouvert et disponible
const isSlotOpen = (date, time) => {
  const dow = new Date(`${date}T${time}`).getDay()
  const slot = availableSlots.value.find(s => 
    parseInt(s.day_of_week) === dow && 
    time >= s.start_time && 
    time < s.end_time
  )
  if (!slot) return false
  return getUsedSlotsCount(date, slot) < slot.max_capacity
}

const createLesson = async () => {
  try {
    console.log('📝 Création du cours:', lessonForm.value)
    
    // Vérifier à nouveau que le créneau est ouvert
    if (!isSlotOpen(lessonForm.value.date, lessonForm.value.time)) {
      alert('Ce créneau n\'est plus ouvert pour les cours.')
      return
    }
    
    const { $api } = useNuxtApp()
    
    // Générer automatiquement le titre du cours
    const disciplineName = getSelectedSlotDisciplineName()
    const teacherName = teachers.value.find(t => t.id === parseInt(lessonForm.value.teacherId))?.name || ''
    const studentName = lessonForm.value.studentId 
      ? students.value.find(s => s.id === parseInt(lessonForm.value.studentId))?.name || ''
      : ''
    
    let generatedTitle = disciplineName || 'Cours'
    if (studentName) {
      generatedTitle += ` - ${studentName}`
    }
    
    // Récupérer le discipline_id du créneau sélectionné
    const disciplineId = selectedSlot.value?.slot?.discipline_id || lessonForm.value.courseTypeId
    
    if (!disciplineId) {
      alert('Type de cours manquant. Veuillez réessayer.')
      return
    }
    
    // Construire les données du cours selon l'API attendue
    const lessonData = {
      teacher_id: parseInt(lessonForm.value.teacherId),
      student_id: lessonForm.value.studentId ? parseInt(lessonForm.value.studentId) : undefined,
      course_type_id: parseInt(disciplineId),
      start_time: `${lessonForm.value.date} ${lessonForm.value.time}:00`,
      duration: parseInt(lessonForm.value.duration),
      price: parseFloat(lessonForm.value.price),
      notes: lessonForm.value.notes || undefined
    }
    
    // Retirer les champs undefined pour ne pas les envoyer
    Object.keys(lessonData).forEach(key => {
      if (lessonData[key] === undefined) {
        delete lessonData[key]
      }
    })
    
    console.log('📤 Données envoyées:', lessonData)
    
    const response = await $api.post('/lessons', lessonData)
    
    if (response.data.success) {
      console.log('✅ Cours créé avec succès')
      
      // Enregistrer le dernier enseignant utilisé
      if (lessonForm.value.teacherId) {
        lastUsedTeacherId.value = parseInt(lessonForm.value.teacherId)
      }
      
      await loadPlanningData() // Recharger les données
      showCreateLessonModal.value = false
      selectedSlot.value = null
      lessonForm.value = { date: '', time: '', duration: '60', courseTypeId: '', teacherId: '', studentId: '', price: '50.00', notes: '' }
    }
    
  } catch (error) {
    console.error('Erreur lors de la création du cours:', error)
    if (error.response?.data?.errors) {
      console.error('Détails de validation:', error.response.data.errors)
      const errorMessages = Object.values(error.response.data.errors).flat().join('\n')
      alert(`Erreur de validation:\n${errorMessages}`)
    } else {
      alert('Erreur lors de la création du cours. Veuillez réessayer.')
    }
  }
}

// Chargement des données
const loadPlanningData = async () => {
  try {
    const { $api } = useNuxtApp()
    
    // Charger les cours selon le mode d'affichage
    let startDate, endDate
    
    if (viewMode.value === 'week') {
      startDate = weekDays.value[0].date
      endDate = weekDays.value[6].date
    } else {
      // Mode jour : charger uniquement le jour sélectionné
      startDate = currentDay.value.toISOString().split('T')[0]
      endDate = startDate
    }
    
    const lessonsResponse = await $api.get(`/lessons?date_from=${startDate}&date_to=${endDate}`)
    if (lessonsResponse.data.success) {
      lessons.value = lessonsResponse.data.data
    }
    
  } catch (error) {
    console.error('Erreur lors du chargement du planning:', error)
  }
}

const loadTeachersAndStudents = async () => {
  try {
    const { $api } = useNuxtApp()
    
    // Charger les enseignants
    const teachersResponse = await $api.get('/club/teachers')
    if (teachersResponse.data.success) {
      teachers.value = teachersResponse.data.data
    }
    
    // Charger les élèves
    const studentsResponse = await $api.get('/club/students')
    if (studentsResponse.data.success) {
      students.value = studentsResponse.data.data
    }
    
  } catch (error) {
    console.error('Erreur lors du chargement des enseignants/élèves:', error)
  }
}

const loadClubProfile = async () => {
  try {
    const { $api } = useNuxtApp()
    
    // Charger le profil du club ET les types de cours en parallèle
    const [profileResponse, courseTypesResponse] = await Promise.all([
      $api.get('/club/profile'),
      $api.get('/course-types')
    ])
    
    if (profileResponse.data.success) {
      clubProfile.value = profileResponse.data.data
      
      // Parser les données JSON si elles sont stockées sous forme de chaînes
      if (typeof clubProfile.value.schedule_config === 'string') {
        try {
          clubProfile.value.schedule_config = JSON.parse(clubProfile.value.schedule_config)
        } catch (e) {
          clubProfile.value.schedule_config = []
        }
      }
      
      if (typeof clubProfile.value.discipline_settings === 'string') {
        try {
          clubProfile.value.discipline_settings = JSON.parse(clubProfile.value.discipline_settings)
        } catch (e) {
          clubProfile.value.discipline_settings = {}
        }
      }
      
      console.log('✅ Profil club chargé:', clubProfile.value)
    }
    
    // Charger les disciplines disponibles
    const disciplinesResponse = await $api.get('/disciplines')
    if (disciplinesResponse.data.success) {
      availableDisciplines.value = disciplinesResponse.data.data
      console.log('✅ Disciplines chargées:', availableDisciplines.value)
      console.log('📊 Résumé disciplines:', {
        total: availableDisciplines.value.length,
        ids: availableDisciplines.value.map(d => d.id),
        disciplines: availableDisciplines.value.map(d => `${d.id}: ${d.name} (activity_type: ${d.activity_type_id})`)
      })
    }
    
    if (courseTypesResponse.data.success) {
      availableCourseTypes.value = courseTypesResponse.data.data
      console.log('✅ Types de cours chargés:', availableCourseTypes.value)
    }
    
  } catch (error) {
    console.error('Erreur lors du chargement du profil du club:', error)
  }
}

// Charger les créneaux ouverts depuis l'API
const loadOpenSlots = async () => {
  try {
    const { $api } = useNuxtApp()
    const res = await $api.get('/club/open-slots')
    if (res.data?.success) {
      availableSlots.value = res.data.data || []
      console.log('✅ Créneaux ouverts chargés:', availableSlots.value)
    }
  } catch (e) {
    console.error('Erreur chargement open-slots:', e)
  }
}

const deleteSlotById = async (slotId) => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.delete(`/club/open-slots/${slotId}`)
    
    if (response.data.success) {
      console.log('✅ Créneau supprimé:', slotId)
      // Recharger les créneaux
      await loadOpenSlots()
    }
  } catch (e) {
    console.error('Erreur suppression open-slot:', e)
    alert('Suppression impossible')
  }
}

// Édition inline des créneaux (gardé pour compatibilité)
const editBuffer = ref({})

const startSlotEdit = (slot) => {
  editBuffer.value[slot.id] = {
    day_of_week: parseInt(slot.day_of_week),
    start_time: slot.start_time,
    end_time: slot.end_time,
    discipline_id: slot.discipline_id ?? null,
    max_capacity: slot.max_capacity,
    duration: slot.duration,
    price: slot.price,
  }
  slot.editing = true
}

// Édition via modale (nouvelle méthode préférée)
const openEditSlotModal = (slot) => {
  editingSlotId.value = slot.id
  
  // Récupérer la discipline pour obtenir son activity_type_id
  const discipline = availableDisciplines.value.find(d => d.id === slot.discipline_id)
  
  // Pré-remplir le formulaire avec les données du créneau
  slotForm.value = {
    day_of_week: slot.day_of_week?.toString() || '1',
    start_time: slot.start_time || '09:00',
    end_time: slot.end_time || '10:00',
    activity_type_id: discipline ? discipline.activity_type_id.toString() : (clubActivities.value[0]?.id?.toString() || ''),
    discipline_id: slot.discipline_id?.toString() || '',
    max_capacity: slot.max_capacity || 5,
    duration: slot.duration || 60,
    price: slot.price || 50
  }
  
  console.log('📝 Ouverture modale édition créneau:', {
    slotId: slot.id,
    slotData: slot,
    formData: slotForm.value
  })
  
  showEditSlotModal.value = true
}

const closeEditSlotModal = () => {
  showEditSlotModal.value = false
  editingSlotId.value = null
  // Réinitialiser le formulaire
  slotForm.value = {
    day_of_week: '1',
    start_time: '09:00',
    end_time: '10:00',
    activity_type_id: clubActivities.value[0]?.id?.toString() || '',
    discipline_id: '',
    max_capacity: 5,
    duration: 60,
    price: 50
  }
}

const updateSlot = async () => {
  try {
    const { $api } = useNuxtApp()
    
    // Validation
    if (!slotForm.value.discipline_id) {
      alert('Veuillez sélectionner un type de cours')
      return
    }
    
    const slotData = {
      day_of_week: parseInt(slotForm.value.day_of_week),
      start_time: slotForm.value.start_time,
      end_time: slotForm.value.end_time,
      discipline_id: slotForm.value.discipline_id ? parseInt(slotForm.value.discipline_id) : null,
      max_capacity: parseInt(slotForm.value.max_capacity),
      duration: parseInt(slotForm.value.duration),
      price: parseFloat(slotForm.value.price)
    }
    
    console.log('📤 Mise à jour du créneau ID', editingSlotId.value, ':', slotData)
    
    const response = await $api.put(`/club/open-slots/${editingSlotId.value}`, slotData)
    
    if (response.data.success) {
      console.log('✅ Créneau modifié avec succès')
      // Recharger les créneaux
      await loadOpenSlots()
      closeEditSlotModal()
    }
  } catch (error) {
    console.error('Erreur lors de la modification du créneau:', error)
    if (error.response?.data?.errors) {
      const errorMessages = Object.values(error.response.data.errors).flat().join('\n')
      alert(`Erreur de validation:\n\n${errorMessages}`)
    } else {
      alert('Erreur lors de la modification du créneau')
    }
  }
}

const cancelSlotEdit = (slot) => {
  delete editBuffer.value[slot.id]
  slot.editing = false
}

const saveSlotEdit = async (slot) => {
  try {
    const { $api } = useNuxtApp()
    const payload = editBuffer.value[slot.id]
    
    // Préparer les données pour l'API
    const updateData = {
      day_of_week: parseInt(payload.day_of_week),
      start_time: payload.start_time,
      end_time: payload.end_time,
      discipline_id: payload.discipline_id ? parseInt(payload.discipline_id) : null,
      max_capacity: parseInt(payload.max_capacity),
      duration: parseInt(payload.duration),
      price: parseFloat(payload.price)
    }
    
    console.log('📤 Mise à jour du créneau:', updateData)
    
    // Appeler l'API backend
    const response = await $api.put(`/club/open-slots/${slot.id}`, updateData)
    
    if (response.data.success) {
      console.log('✅ Créneau modifié:', response.data.data)
      // Recharger les créneaux
      await loadOpenSlots()
      cancelSlotEdit(slot)
    }
  } catch (e) {
    console.error('Erreur sauvegarde édition slot:', e)
    if (e.response?.data?.errors) {
      const errorMessages = Object.values(e.response.data.errors).flat().join('\n')
      alert(`Erreur de validation:\n${errorMessages}`)
    } else {
      alert('Sauvegarde impossible')
    }
  }
}

const confirmDeleteSlot = async (slot) => {
  if (!confirm('Supprimer ce créneau ?')) return
  await deleteSlotById(slot.id)
}

// Fonctions utilitaires manquantes
const getDayName = (dayOfWeek) => {
  const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
  return days[parseInt(dayOfWeek)] || 'Jour'
}

const getUsedSlotsCount = (date, openSlot) => {
  const dow = new Date(date).getDay()
  if (dow !== parseInt(openSlot.day_of_week)) return 0
  
  return lessons.value.filter(lesson => {
    const dt = lesson.start_time.includes('T') ? new Date(lesson.start_time) : new Date(lesson.start_time.replace(' ', 'T'))
    const lessonDow = dt.getDay()
    const lessonTime = dt.toTimeString().substring(0,5)
    return lessonDow === dow && lessonTime >= openSlot.start_time && lessonTime < openSlot.end_time
  }).length
}

// Récupérer le nom de la discipline du créneau sélectionné
const getSelectedSlotDisciplineName = () => {
  if (!selectedSlot.value?.slot) return ''
  const slot = selectedSlot.value.slot
  const discipline = availableDisciplines.value.find(d => d.id === parseInt(slot.discipline_id))
  return discipline?.name || ''
}

// Watch pour initialiser automatiquement le sport s'il n'y en a qu'un seul
watch(() => clubActivities.value, (activities) => {
  if (activities.length === 1 && !slotForm.value.activity_type_id) {
    slotForm.value.activity_type_id = activities[0].id.toString()
  }
}, { immediate: true })

// Watch pour pré-remplir automatiquement durée et prix quand une discipline est sélectionnée
watch(() => slotForm.value.discipline_id, (newDisciplineId) => {
  if (newDisciplineId) {
    const settings = getDisciplineSettings(parseInt(newDisciplineId))
    console.log('📝 Pré-remplissage automatique depuis les settings:', settings)
    slotForm.value.duration = settings.duration
    slotForm.value.price = settings.price
  }
})

// Watch pour recharger les données quand on change de mode d'affichage
watch(viewMode, () => {
  loadPlanningData()
})

// Initialisation
onMounted(async () => {
  console.log('🚀 Initialisation du planning club')
  await Promise.all([
    loadPlanningData(),
    loadTeachersAndStudents(),
    loadClubProfile(),
    loadOpenSlots()
  ])
  // Initialiser le prix par défaut
  updateLessonPrice()
})
</script>

<style scoped>
.bg-today {
  background-color: rgba(219, 234, 254, 0.15) !important;
}

.bg-today:hover {
  background-color: rgba(219, 234, 254, 0.3) !important;
}
</style>
