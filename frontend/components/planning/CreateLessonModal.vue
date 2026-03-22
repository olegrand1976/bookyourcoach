<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-5xl w-full max-h-[95vh] overflow-y-auto">
      <div class="p-8">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-2xl font-bold text-gray-900">
            {{ editingLesson ? 'Modifier le cours' : 'Créer un nouveau cours' }}
          </h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Informations du créneau sélectionné -->
        <div v-if="selectedSlot" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <h4 class="font-semibold text-blue-900 mb-2">Créneau sélectionné</h4>
          <div class="text-sm text-blue-800 space-y-1">
            <p><strong>Jour :</strong> {{ getDayName(selectedSlot.day_of_week) }}</p>
            <p><strong>Horaire :</strong> {{ selectedSlot.start_time?.substring(0, 5) }} - {{ selectedSlot.end_time?.substring(0, 5) }}</p>
            <p><strong>Discipline :</strong> {{ selectedSlot.discipline?.name || 'Non définie' }}</p>
            <p v-if="selectedSlot.discipline_id" class="text-xs text-blue-600 mt-2">
              🔍 Les types de cours affichés sont filtrés pour cette discipline
            </p>
          </div>
        </div>

        <!-- Formulaire -->
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Section 1: Informations du créneau et horaire -->
          <div class="bg-gray-50 rounded-lg p-6 space-y-4">
            <h4 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">📅 Créneau et horaire</h4>
            
            <!-- 2.5. Créneau (en mode édition uniquement) -->
            <div v-if="editingLesson" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Créneau *</label>
                <select 
                  v-model="selectedSlotId"
                  required
                  @change="onSlotChange"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 bg-white text-gray-900">
                  <option :value="null">Sélectionnez un créneau</option>
                  <option v-for="slot in (openSlots || [])" :key="slot.id" :value="slot.id">
                    {{ getDayName(slot.day_of_week) }} • {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                    <template v-if="slot.discipline || (slot as any).discipline_name">
                      • {{ slot.discipline?.name || (slot as any).discipline_name || 'Non définie' }}
                    </template>
                  </option>
                </select>
                <p v-if="selectedSlotId && currentSelectedSlot" class="text-xs text-green-600 mt-1">
                  ✓ Créneau sélectionné : {{ getDayName(currentSelectedSlot.day_of_week) }} de {{ formatTime(currentSelectedSlot.start_time) }} à {{ formatTime(currentSelectedSlot.end_time) }}
                </p>
              </div>
            </div>

            <!-- 2. Type de cours (masqué en mode édition) -->
            <div v-if="!editingLesson" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type de cours *</label>
                <select v-model.number="form.course_type_id" required
                        :disabled="courseTypes.length === 0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                  <option :value="null">
                    {{ courseTypes.length === 0 ? 'Aucun type de cours pour cette discipline' : 'Sélectionnez un type de cours' }}
                  </option>
                  <option v-for="courseType in courseTypes" :key="courseType.id" :value="courseType.id">
                    {{ courseType.name }} 
                    ({{ courseType.duration_minutes || courseType.duration }}min - {{ courseType.price }}€)
                  </option>
                </select>
                <p v-if="selectedSlot && courseTypes.length === 0" class="text-xs text-red-600 mt-1">
                  ⚠️ Aucun type de cours disponible pour ce créneau
                  <br>
                  <span class="text-xs">
                    Vérifiez que :
                    <br>• Des types de cours sont associés à ce créneau
                    <br>• Ces types correspondent aux disciplines activées pour votre club
                  </span>
                </p>
                <p v-else-if="selectedSlot && courseTypes.length > 0" class="text-xs text-green-600 mt-1">
                  ✓ {{ courseTypes.length }} type(s) de cours disponible(s) pour ce créneau
                </p>
              </div>
            </div>

            <!-- 3. Date et Heure -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Date -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Date *
                  <span v-if="(editingLesson ? currentSelectedSlot : selectedSlot)" class="text-xs text-blue-600 ml-2 font-medium">
                    (Uniquement les {{ getDayName((editingLesson ? currentSelectedSlot : selectedSlot)?.day_of_week || 0) }}s)
                  </span>
                  <span v-else-if="availableDays.length > 0" class="text-xs text-gray-500 ml-2">
                    (Jours disponibles: {{ availableDays.map(d => getDayName(d)).join(', ') }})
                  </span>
                </label>
                <!-- Conteneur avec flèches de navigation -->
                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    @click="navigateDate(-1)"
                    :disabled="!canNavigateDate(-1)"
                    :class="[
                      'px-3 py-2 border rounded-md transition-colors',
                      canNavigateDate(-1)
                        ? 'border-gray-300 bg-white hover:bg-gray-50 text-gray-700'
                        : 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed'
                    ]"
                    title="Date précédente"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                  </button>
                  <input 
                    v-model="form.date" 
                    type="date" 
                    required
                    :min="minDate || undefined"
                    @input="validateDate"
                    :class="[
                      'flex-1 px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500',
                      form.date && !isDateAvailable(form.date) ? 'border-red-500 bg-red-50' : 'border-gray-300'
                    ]" />
                  <button
                    type="button"
                    @click="navigateDate(1)"
                    :disabled="!canNavigateDate(1)"
                    :class="[
                      'px-3 py-2 border rounded-md transition-colors',
                      canNavigateDate(1)
                        ? 'border-gray-300 bg-white hover:bg-gray-50 text-gray-700'
                        : 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed'
                    ]"
                    title="Date suivante"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </button>
                </div>
                <p v-if="form.date && !isDateAvailable(form.date)" class="text-xs text-red-600 mt-1">
                  ⚠️ Cette date doit être un {{ getDayName((editingLesson ? currentSelectedSlot : selectedSlot)?.day_of_week || 0) }}
                </p>
                <p v-else-if="form.date && (editingLesson ? currentSelectedSlot : selectedSlot)" class="text-xs text-green-600 mt-1">
                  ✓ Date valide pour ce créneau
                </p>
                <!-- Suggestions de dates -->
                <div v-if="(editingLesson ? currentSelectedSlot : selectedSlot) && suggestedDates.length > 0" class="mt-2">
                  <p class="text-xs text-gray-600 mb-1">Suggestions :</p>
                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="(suggestedDate, index) in suggestedDates.slice(0, 4)"
                      :key="index"
                      type="button"
                      @click="form.date = suggestedDate"
                      class="px-3 py-1 text-xs bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                      {{ formatSuggestedDate(suggestedDate) }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- Heure -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Heure *</label>
                <select 
                  v-model="form.time" 
                  required
                  :disabled="!availableTimes.length && !editingLesson"
                  :class="[
                    'w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500',
                    (!availableTimes.length && !editingLesson) 
                      ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-300' 
                      : 'bg-white text-gray-900 border-gray-300'
                  ]">
                  <option :value="''">
                    {{ editingLesson ? 'Sélectionnez une heure' : (availableTimes.length === 0 ? 'Aucune heure disponible' : 'Sélectionnez une heure') }}
                  </option>
                  <option
                    v-for="opt in timeOptionsForSelect"
                    :key="opt.value"
                    :value="opt.value"
                    :disabled="opt.disabled">
                    {{ opt.label }}
                  </option>
                </select>
                <div v-if="!editingLesson && selectedSlot && form.date && availableTimes.length === 0" class="mt-2">
                  <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                    <p class="text-sm text-red-700 font-medium mb-2">
                      ⚠️ Créneau complet - Toutes les plages sont occupées
                    </p>
                    <button 
                      type="button"
                      @click="openSlotConflictModal"
                      class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors flex items-center gap-1"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      Voir les cours et libérer un créneau
                    </button>
                  </div>
                </div>
                <p v-else-if="!editingLesson && selectedSlot && form.date && availableTimes.length > 0" class="text-xs text-green-600 mt-1">
                  ✓ {{ availableTimes.length }} plage(s) horaire(s) disponible(s) (les plages complètes sont automatiquement masquées)
                </p>
                <p v-if="loadingLessons" class="text-xs text-gray-500 mt-1">
                  🔄 Chargement des cours existants...
                </p>
              </div>
            </div>
          </div>

          <!-- Section 2: Participants -->
          <div class="bg-blue-50 rounded-lg p-6 space-y-4">
            <h4 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">👥 Participants</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Enseignant -->
              <div>
                <Autocomplete
                  v-model="form.teacher_id"
                  :items="teachers"
                  label="Enseignant *"
                  placeholder="Rechercher un enseignant..."
                  :required="true"
                  :get-item-label="(teacher) => teacher.user?.name || teacher.name || 'Enseignant sans nom'"
                  :get-item-id="(teacher) => teacher.id"
                  :is-item-unavailable="(teacher) => !isTeacherAvailable(teacher.id)"
                  :filter-function="filterTeacher"
                >
                  <template #item="{ item: teacher, isUnavailable }">
                    <div :class="isUnavailable ? 'bg-red-50' : ''">
                      <div class="font-medium flex items-center gap-2">
                        {{ teacher.user?.name || teacher.name || 'Enseignant sans nom' }}
                        <span v-if="isUnavailable" class="text-xs text-red-600 font-normal">(Non disponible)</span>
                      </div>
                      <div v-if="teacher.user?.email" class="text-xs" :class="isUnavailable ? 'text-red-400' : 'text-gray-500'">
                        {{ teacher.user.email }}
                      </div>
                    </div>
                  </template>
                </Autocomplete>
              </div>

              <!-- Élève (optionnel) (masqué en mode édition) -->
              <div v-if="!editingLesson">
                <Autocomplete
                  v-model="form.student_id"
                  :items="students"
                  label="Élève (optionnel)"
                  placeholder="Rechercher un élève..."
                  :max-results="500"
                  :get-item-label="(student) => {
                    const name = student.user?.name || student.name || 'Élève sans nom'
                    const age = student.age ? ` (${student.age} ans)` : ''
                    return name + age
                  }"
                  :get-item-id="(student) => student.id"
                  :is-item-unavailable="(student) => !isStudentAvailable(student.id)"
                  :filter-function="filterStudent"
                >
                  <template #item="{ item: student, isUnavailable }">
                    <div :class="isUnavailable ? 'bg-red-50' : ''">
                      <div class="font-medium flex items-center gap-2">
                        {{ student.user?.name || student.name || 'Élève sans nom' }}
                        <span v-if="student.age" class="text-xs" :class="isUnavailable ? 'text-red-400' : 'text-gray-500'">
                          ({{ student.age }} ans)
                        </span>
                        <span v-if="isUnavailable" class="text-xs text-red-600 font-normal">(Non disponible)</span>
                      </div>
                      <div v-if="student.user?.email" class="text-xs" :class="isUnavailable ? 'text-red-400' : 'text-gray-500'">
                        {{ student.user.email }}
                      </div>
                    </div>
                  </template>
                </Autocomplete>
              </div>
            </div>
          </div>

          <!-- Section 3: Détails du cours -->
          <div class="bg-green-50 rounded-lg p-6 space-y-4">
            <h4 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">📋 Détails du cours</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Classification pour les commissions (DCL/NDCL) - uniquement si "Séance non incluse dans l'abonnement" est sélectionné -->
              <div v-if="shouldShowDclNdcl" class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Classification pour les commissions *
                </label>
                <div class="flex gap-6">
                  <div class="flex items-center">
                    <input
                      id="dcl"
                      v-model="form.est_legacy"
                      :value="false"
                      type="radio"
                      :required="shouldShowDclNdcl"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                    />
                    <label for="dcl" class="ml-2 block text-sm font-medium text-gray-700">
                      DCL
                    </label>
                  </div>
                  <div class="flex items-center">
                    <input
                      id="ndcl"
                      v-model="form.est_legacy"
                      :value="true"
                      type="radio"
                      :required="shouldShowDclNdcl"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                    />
                    <label for="ndcl" class="ml-2 block text-sm font-medium text-gray-700">
                      NDCL
                    </label>
                  </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                  ⓘ Cette classification s'applique uniquement lorsque la séance n'est pas incluse dans l'abonnement
                </p>
              </div>

              <!-- Déduction d'abonnement -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Déduction d'abonnement
                </label>
                <div class="space-y-2">
                  <div class="flex items-center">
                    <input
                      id="deduct_subscription"
                      v-model="form.deduct_from_subscription"
                      :value="true"
                      type="radio"
                      :disabled="editingLesson ? false : !form.student_id"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                    />
                    <label 
                      for="deduct_subscription" 
                      :class="[
                        'ml-2 block text-sm font-medium',
                        (editingLesson || form.student_id) ? 'text-gray-700' : 'text-gray-400'
                      ]"
                    >
                      Déduire d'un abonnement existant
                    </label>
                  </div>
                  <div class="flex items-center">
                    <input
                      id="no_deduct_subscription"
                      v-model="form.deduct_from_subscription"
                      :value="false"
                      type="radio"
                      :disabled="editingLesson ? false : !form.student_id"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                    />
                    <label 
                      for="no_deduct_subscription"
                      :class="[
                        'ml-2 block text-sm font-medium',
                        (editingLesson || form.student_id) ? 'text-gray-700' : 'text-gray-400'
                      ]"
                    >
                      Séance non incluse dans l'abonnement
                    </label>
                  </div>
                </div>
                <p v-if="editingLesson || form.student_id" class="text-xs text-gray-500 mt-2">
                  ⓘ Par défaut, le cours sera déduit d'un abonnement actif si disponible
                </p>
                <p v-else-if="!editingLesson" class="text-xs text-orange-600 mt-2">
                  ⚠️ Sélectionnez un élève pour activer cette option
                </p>
              </div>

              <!-- Intervalle de récurrence (uniquement si un élève est sélectionné et déduction d'abonnement activée) -->
              <!-- En mode création OU en mode édition avec portée 'all_future' -->
              <!-- Alerte : pas d'abonnement actif pour déduction / récurrence -->
              <div v-if="!editingLesson && needsSubscriptionCheck && hasActiveSubscription === false" class="md:col-span-2 p-3 rounded-lg bg-amber-50 border border-amber-200">
                <p class="text-sm text-amber-800 flex items-start gap-2">
                  <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <span>Cet élève n'a pas d'abonnement actif pour ce type de cours. Créez le cours sans déduction (décocher « Déduire de l'abonnement ») ou assignez un abonnement à l'élève.</span>
                </p>
              </div>
              <div v-if="((!editingLesson && form.student_id && form.deduct_from_subscription === true) || (editingLesson && form.update_scope === 'all_future'))" class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Fréquence de récurrence
                  <span class="text-xs font-normal text-gray-500 ml-2">{{ editingLesson ? '(pour tous les cours futurs)' : '(pour les cours réguliers)' }}</span>
                </label>
                <select 
                  v-model.number="form.recurring_interval"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 bg-white text-gray-900"
                >
                  <option :value="1">Chaque semaine</option>
                  <option :value="2">Toutes les 2 semaines</option>
                  <option :value="3">Toutes les 3 semaines</option>
                  <option :value="4">Toutes les 4 semaines</option>
                </select>
                <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                  <p class="text-xs text-blue-800 flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span v-if="!editingLesson">
                      <strong>Exemple :</strong> Si vous créez un cours le {{ formatExampleDate() }} avec "{{ getRecurringIntervalLabel() }}", 
                      les prochains cours seront automatiquement créés {{ getNextDatesExample() }}.
                    </span>
                    <span v-else>
                      <strong>Attention :</strong> Tous les cours futurs planifiés seront supprimés et recréés avec le nouvel intervalle de récurrence "{{ getRecurringIntervalLabel() }}".
                    </span>
                  </p>
                </div>
              </div>

              <!-- Durée (affichage uniquement) (masqué en mode édition) -->
              <div v-if="!editingLesson">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Durée (minutes)
                </label>
                <div class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-700">
                  {{ form.duration || 0 }} minutes
                </div>
                <p class="text-xs text-gray-500 mt-1">
                  ⓘ Définie automatiquement selon le type de cours sélectionné
                </p>
              </div>

              <!-- Prix (affichage uniquement) (masqué en mode édition) -->
              <div v-if="!editingLesson">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Prix (€)
                </label>
                <div class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-700">
                  {{ formatPrice(form.price || 0) }} €
                </div>
                <p class="text-xs text-gray-500 mt-1">
                  ⓘ Défini automatiquement selon le type de cours sélectionné
                </p>
              </div>

              <!-- Notes (masqué en mode édition) -->
              <div v-if="!editingLesson" class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea v-model="form.notes" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                          placeholder="Notes sur le cours..."></textarea>
              </div>
            </div>

            <!-- Portée de la mise à jour (uniquement en mode édition) -->
            <div v-if="editingLesson" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Portée de la modification
              </label>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input 
                    type="radio" 
                    :value="'single'"
                    v-model="form.update_scope"
                    class="mr-2 text-blue-600 focus:ring-blue-500"
                  />
                  <span class="text-sm text-gray-700">Modifier uniquement ce cours</span>
                </label>
                <label class="flex items-center">
                  <input 
                    type="radio" 
                    :value="'all_future'"
                    v-model="form.update_scope"
                    class="mr-2 text-blue-600 focus:ring-blue-500"
                  />
                  <span class="text-sm text-gray-700">Modifier ce cours et tous les cours futurs de l'abonnement</span>
                </label>
                <p class="text-xs text-gray-500 mt-2">
                  ⓘ Si vous modifiez la date, l'heure ou l'enseignant, cette modification sera appliquée à tous les cours futurs liés au même abonnement.
                </p>
              </div>
            </div>
          </div>

          <!-- Boutons -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <button type="button" @click="$emit('close')"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
              Annuler
            </button>
            <button type="submit" :disabled="saving || (needsSubscriptionCheck && hasActiveSubscription === false)"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
              <span v-if="saving" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" aria-hidden="true"></span>
              {{ saving ? (editingLesson ? 'Modification...' : (form.recurring_interval >= 1 ? 'Création du cours et des occurrences en cours…' : 'Création...')) : (editingLesson ? 'Modifier le cours' : 'Créer le cours') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modale de gestion des conflits de créneaux -->
    <SlotConflictModal
      :is-open="showSlotConflictModal"
      :date="form.date"
      :time="conflictTime"
      :duration="form.duration"
      :teacher-id="form.teacher_id"
      @close="closeSlotConflictModal"
      @lesson-cancelled="onLessonCancelled"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, watch, ref, nextTick } from 'vue'
import Autocomplete from '~/components/Autocomplete.vue'
import SlotConflictModal from '~/components/planning/SlotConflictModal.vue'

interface OpenSlot {
  id: number
  day_of_week: number
  start_time: string
  end_time?: string
  discipline_id?: number
  discipline?: any
  duration?: number
  price?: number
  /** Nombre max de cours simultanés sur cette plage (défini par le créneau). */
  max_slots?: number | null
}

interface LessonForm {
  teacher_id: number | null
  student_id: number | null
  course_type_id: number | null
  date: string
  time: string
  duration: number
  price: number
  notes: string
  // Champs pour les commissions
  est_legacy: boolean | null
  // Déduction d'abonnement (par défaut true)
  deduct_from_subscription: boolean | null
  // Intervalle de récurrence (1 = chaque semaine, 2 = toutes les 2 semaines, etc.)
  recurring_interval: number
  // Portée de la mise à jour (pour les récurrences)
  update_scope?: 'single' | 'all_future'
}

interface Props {
  show: boolean
  form: LessonForm
  /** Heure demandée à l'ouverture (clic sur une plage) — prioritaire pour éviter toute course avec les watchers. */
  requestedTime?: string | null
  selectedSlot: OpenSlot | null
  teachers: any[]
  students: any[]
  courseTypes: any[]
  availableDays: number[]
  saving: boolean
  editingLesson?: any | null
  openSlots?: OpenSlot[] // Créneaux disponibles pour trouver le créneau correspondant à une date
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'close': []
  'submit': [form: LessonForm]
}>()

const dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']

// Vérification abonnement actif pour déduction / récurrence (création uniquement)
const hasActiveSubscription = ref<boolean | null>(null)
const loadingCheckSubscription = ref(false)
const needsSubscriptionCheck = computed(() => {
  const f = props.form
  return !props.editingLesson && !!f.student_id && !!f.course_type_id && (f.deduct_from_subscription === true || (f.recurring_interval ?? 0) >= 1)
})

// Fonction pour normaliser les chaînes (supprimer les accents) pour la recherche
function normalizeString(str: string): string {
  if (!str) return ''
  return str
    .toLowerCase()
    .normalize('NFD') // Décompose les caractères accentués
    .replace(/[\u0300-\u036f]/g, '') // Supprime les diacritiques (accents)
}

// Fonction de filtrage pour les enseignants avec normalisation des accents
function filterTeacher(teacher: any, query: string): boolean {
  const normalizedQuery = normalizeString(query)
  
  // Rechercher dans le nom de l'utilisateur
  if (teacher.user?.name) {
    if (normalizeString(teacher.user.name).includes(normalizedQuery)) {
      return true
    }
  }
  
  // Rechercher dans l'email
  if (teacher.user?.email) {
    if (normalizeString(teacher.user.email).includes(normalizedQuery)) {
      return true
    }
  }
  
  // Rechercher dans le nom direct (fallback)
  if (teacher.name) {
    if (normalizeString(teacher.name).includes(normalizedQuery)) {
      return true
    }
  }
  
  return false
}

// Fonction de filtrage pour les élèves avec normalisation des accents
function filterStudent(student: any, query: string): boolean {
  const normalizedQuery = normalizeString(query)
  
  // Rechercher dans le nom de l'utilisateur
  if (student.user?.name) {
    if (normalizeString(student.user.name).includes(normalizedQuery)) {
      return true
    }
  }
  
  // Rechercher dans first_name et last_name de l'utilisateur
  if (student.user) {
    const userFirstName = normalizeString(student.user.first_name || '')
    const userLastName = normalizeString(student.user.last_name || '')
    const userFullName = `${userFirstName} ${userLastName}`.trim()
    if (userFullName && userFullName.includes(normalizedQuery)) {
      return true
    }
    if (userFirstName && userFirstName.includes(normalizedQuery)) {
      return true
    }
    if (userLastName && userLastName.includes(normalizedQuery)) {
      return true
    }
  }
  
  // Rechercher dans first_name et last_name de l'élève
  const firstName = normalizeString(student.first_name || '')
  const lastName = normalizeString(student.last_name || '')
  const fullName = `${firstName} ${lastName}`.trim()
  if (fullName && fullName.includes(normalizedQuery)) {
    return true
  }
  if (firstName && firstName.includes(normalizedQuery)) {
    return true
  }
  if (lastName && lastName.includes(normalizedQuery)) {
    return true
  }
  
  // Rechercher dans le nom direct (fallback)
  if (student.name) {
    if (normalizeString(student.name).includes(normalizedQuery)) {
      return true
    }
  }
  
  // Rechercher dans l'email
  if (student.user?.email) {
    if (normalizeString(student.user.email).includes(normalizedQuery)) {
      return true
    }
  }
  
  return false
}

// Référence pour le créneau sélectionné en mode édition
const selectedSlotId = ref<number | null>(null)

// Variables pour la modale de conflit de créneaux
const showSlotConflictModal = ref(false)
const conflictTime = ref('')

// Ouvrir la modale de conflit
const openSlotConflictModal = () => {
  // Utiliser l'heure de début du créneau sélectionné si aucune heure n'est sélectionnée
  const slotToUse = props.editingLesson ? currentSelectedSlot.value : props.selectedSlot
  conflictTime.value = props.form.time || slotToUse?.start_time?.substring(0, 5) || '09:00'
  showSlotConflictModal.value = true
}

// Fermer la modale de conflit
const closeSlotConflictModal = () => {
  showSlotConflictModal.value = false
}

// Quand un cours est annulé, recharger les cours existants
const onLessonCancelled = async (lessonIds: number[]) => {
  console.log('🗑️ Cours annulé(s):', lessonIds)
  // Recharger les cours existants pour mettre à jour les heures disponibles
  if (props.form.date) {
    await loadExistingLessons(props.form.date)
  }
  // Fermer la modale de conflit
  closeSlotConflictModal()
}

// Computed property pour déterminer si on doit afficher les boutons DCL/NDCL
// Les boutons DCL/NDCL ne s'affichent que si :
// - "Séance non incluse dans l'abonnement" est sélectionné
const shouldShowDclNdcl = computed(() => {
  return props.form.deduct_from_subscription === false
})

// Fonction pour formater l'heure (HH:mm)
function formatTime(time: string | undefined): string {
  if (!time) return ''
  return time.substring(0, 5) // Retourne HH:mm
}

// Fonction appelée quand le créneau change
function onSlotChange() {
  if (!selectedSlotId.value || !props.openSlots) return
  
  const slot = props.openSlots.find(s => s.id === selectedSlotId.value)
  if (slot) {
    currentSelectedSlot.value = slot
    console.log('🎯 [CreateLessonModal] Créneau sélectionné manuellement:', {
      slot_id: slot.id,
      day_of_week: slot.day_of_week,
      start_time: slot.start_time,
      end_time: slot.end_time
    })
    
    // Si une date est déjà sélectionnée, vérifier qu'elle correspond au jour du créneau
    if (props.form.date) {
      const date = new Date(props.form.date + 'T00:00:00')
      const dayOfWeek = date.getDay()
      if (dayOfWeek !== slot.day_of_week) {
        // Trouver la prochaine date correspondant au jour du créneau
        const today = new Date()
        let daysToAdd = slot.day_of_week - today.getDay()
        if (daysToAdd < 0) daysToAdd += 7
        const nextDate = new Date(today)
        nextDate.setDate(today.getDate() + daysToAdd)
        props.form.date = nextDate.toISOString().split('T')[0]
        console.log('📅 [CreateLessonModal] Date ajustée au jour du créneau:', props.form.date)
      }
    }
  }
}

function getDayName(dayOfWeek: number): string {
  return dayNames[dayOfWeek] || 'Inconnu'
}

// Date minimale : pas de restriction (permet l'encodage dans le passé)
const minDate = computed(() => {
  // Retourner null pour permettre toutes les dates
  return null
})

// Génère les 4 prochaines dates valides pour le créneau sélectionné
const suggestedDates = computed(() => {
  const slotToUse = props.editingLesson ? currentSelectedSlot.value : props.selectedSlot
  if (!slotToUse) return []
  
  const dates: string[] = []
  const today = new Date()
  const targetDay = slotToUse.day_of_week
  
  for (let i = 0; i < 28; i++) { // 4 semaines
    const checkDate = new Date(today)
    checkDate.setDate(today.getDate() + i)
    
    if (checkDate.getDay() === targetDay) {
      dates.push(checkDate.toISOString().split('T')[0])
    }
    
    if (dates.length >= 4) break
  }
  
  return dates
})

// Formate une date pour l'affichage des suggestions
function formatSuggestedDate(dateStr: string): string {
  const date = new Date(dateStr + 'T00:00:00')
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const tomorrow = new Date(today)
  tomorrow.setDate(today.getDate() + 1)
  
  if (date.getTime() === today.getTime()) {
    return 'Aujourd\'hui'
  } else if (date.getTime() === tomorrow.getTime()) {
    return 'Demain'
  } else {
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
  }
}

// Vérifie si une date est disponible
function isDateAvailable(dateStr: string): boolean {
  if (!dateStr) return false
  const date = new Date(dateStr + 'T00:00:00')
  const dayOfWeek = date.getDay()
  
  // En mode édition, permettre toutes les dates qui ont un créneau correspondant
  if (props.editingLesson) {
    // Vérifier si un créneau existe pour ce jour de la semaine
    // Les créneaux sont passés via props, mais on peut aussi vérifier availableDays
    return props.availableDays.includes(dayOfWeek)
  }
  
  // Si un créneau est sélectionné, vérifier uniquement ce jour
  if (props.selectedSlot) {
    return dayOfWeek === props.selectedSlot.day_of_week
  }
  
  // Sinon, vérifier tous les jours disponibles
  return props.availableDays.includes(dayOfWeek)
}

// Valide la date lors de la saisie
function validateDate(event: Event) {
  const input = event.target as HTMLInputElement
  const dateStr = input.value
  
  if (dateStr && !isDateAvailable(dateStr)) {
    console.warn('⚠️ Date invalide sélectionnée:', dateStr)
    
    // Suggérer automatiquement la prochaine date valide
    if (suggestedDates.value.length > 0) {
      const nextValidDate = suggestedDates.value[0]
      setTimeout(() => {
        props.form.date = nextValidDate
        console.log('✓ Date corrigée automatiquement:', nextValidDate)
      }, 100)
    }
  }
}

// Navigue vers la date précédente ou suivante du même jour de la semaine
function navigateDate(direction: number) {
  const slotToUse = props.editingLesson ? currentSelectedSlot.value : props.selectedSlot
  if (!props.form.date || !slotToUse) return
  
  // Parser la date en local (pas UTC) pour éviter les problèmes de timezone
  const [year, month, day] = props.form.date.split('-').map(Number)
  const currentDate = new Date(year, month - 1, day, 12, 0, 0) // Utiliser midi pour éviter les problèmes de timezone
  const targetDayOfWeek = slotToUse.day_of_week
  const currentDayOfWeek = currentDate.getDay()
  
  let daysToAdd = 0
  
  if (currentDayOfWeek === targetDayOfWeek) {
    // Si on est déjà sur le bon jour, avancer/reculer d'une semaine complète
    daysToAdd = direction * 7
    console.log('🔍 Navigation: déjà sur le bon jour', {
      currentDate: props.form.date,
      currentDay: currentDayOfWeek,
      direction,
      daysToAdd
    })
  } else {
    // Si on n'est pas sur le bon jour, trouver le prochain/précédent jour cible
    let diff = targetDayOfWeek - currentDayOfWeek
    
    if (direction > 0) {
      // Navigation vers l'avenir (flèche droite)
      // Toujours aller au jour cible suivant (semaine suivante si nécessaire)
      if (diff > 0) {
        // Le jour cible est plus tard cette semaine → aller directement à ce jour
        daysToAdd = diff
      } else {
        // Le jour cible est déjà passé cette semaine → aller à la semaine suivante
        // diff est négatif, donc 7 + diff donne le nombre de jours jusqu'au jour cible de la semaine suivante
        daysToAdd = 7 + diff
      }
      console.log('🔍 Navigation droite calculée', {
        currentDay: currentDayOfWeek,
        targetDay: targetDayOfWeek,
        diff,
        daysToAdd
      })
    } else {
      // Navigation vers le passé (flèche gauche)
      // Toujours aller au jour cible précédent (semaine précédente)
      // On trouve d'abord le jour cible de cette semaine, puis on recule d'une semaine
      // diff peut être positif ou négatif selon où on se trouve dans la semaine
      // Exemple: si on est vendredi (5) et cible mercredi (3), diff = -2
      //          si on est lundi (1) et cible mercredi (3), diff = 2
      // Dans les deux cas, on veut le mercredi précédent
      
      // Normaliser diff pour trouver le jour cible de cette semaine
      let daysToTargetThisWeek = diff
      if (daysToTargetThisWeek < 0) {
        // Le jour cible est déjà passé cette semaine
        daysToTargetThisWeek = 7 + diff
      }
      
      // Aller au jour cible de la semaine précédente
      daysToAdd = daysToTargetThisWeek - 7
    }
  }
  
  // Créer une nouvelle date en ajoutant les jours
  const newDate = new Date(currentDate)
  newDate.setDate(currentDate.getDate() + daysToAdd)
  
  // Vérifier que la nouvelle date correspond bien au jour du créneau
  const newDayOfWeek = newDate.getDay()
  // Formater la date en YYYY-MM-DD en local (pas UTC)
  const newDateStr = `${newDate.getFullYear()}-${String(newDate.getMonth() + 1).padStart(2, '0')}-${String(newDate.getDate()).padStart(2, '0')}`
  
  console.log('🔍 Navigation calculée', {
    currentDate: props.form.date,
    currentDay: currentDayOfWeek,
    targetDay: targetDayOfWeek,
    direction,
    daysToAdd,
    newDate: newDateStr,
    newDay: newDayOfWeek,
    expectedDay: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'][targetDayOfWeek],
    actualDay: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'][newDayOfWeek]
  })
  
  if (newDayOfWeek !== targetDayOfWeek) {
    console.warn('⚠️ Erreur de navigation : le jour ne correspond pas au créneau', {
      currentDay: currentDayOfWeek,
      targetDay: targetDayOfWeek,
      newDay: newDayOfWeek,
      daysToAdd,
      currentDate: props.form.date,
      newDate: newDateStr
    })
    return
  }
  
  // Permettre la navigation vers le passé pour encoder des cours dans le passé
  props.form.date = newDateStr
}

// Vérifie si on peut naviguer dans une direction donnée
// Toujours autoriser la navigation (vers le passé et l'avenir)
function canNavigateDate(direction: number): boolean {
  if (!props.form.date || !props.selectedSlot) return false
  
  // Permettre toujours la navigation (vers le passé et l'avenir)
  return true
}

function handleSubmit() {
  if (needsSubscriptionCheck.value && hasActiveSubscription.value === false) {
    return
  }
  emit('submit', props.form)
}

// Formater le prix pour l'affichage
function formatPrice(price: number | string | null | undefined): string {
  // Convertir en nombre si c'est une chaîne
  const numPrice = typeof price === 'string' ? parseFloat(price) : (price || 0)
  // Vérifier que c'est un nombre valide
  if (isNaN(numPrice)) {
    return '0,00'
  }
  return numPrice.toFixed(2).replace('.', ',')
}

// Watcher : vérifier si l'élève a un abonnement actif pour ce type de cours (déduction / récurrence)
watch(
  () => [
    props.show,
    props.form.student_id,
    props.form.course_type_id,
    props.form.deduct_from_subscription,
    props.form.recurring_interval
  ],
  async ([show, studentId, courseTypeId, deduct, recurring]) => {
    const needsCheck = show && !props.editingLesson && studentId && courseTypeId &&
      (deduct === true || (recurring ?? 0) >= 1)
    if (!needsCheck) {
      hasActiveSubscription.value = null
      return
    }
    loadingCheckSubscription.value = true
    hasActiveSubscription.value = null
    try {
      const { $api } = useNuxtApp()
      const response = await $api.get('/club/students/check-active-subscription', {
        params: { student_id: studentId, course_type_id: courseTypeId }
      })
      if (response?.data?.success === true) {
        hasActiveSubscription.value = response.data.has_active === true
      }
    } catch {
      hasActiveSubscription.value = null
    } finally {
      loadingCheckSubscription.value = false
    }
  },
  { immediate: true }
)

// Watcher pour auto-sélectionner le type de cours s'il n'y en a qu'un seul
watch(() => props.courseTypes, (newCourseTypes) => {
  if (props.show && newCourseTypes) {
    console.log('🔍 [CreateLessonModal] Props mis à jour:', {
      courseTypesCount: newCourseTypes.length,
      slotDisciplineId: props.selectedSlot?.discipline_id,
      slotDisciplineName: props.selectedSlot?.discipline?.name,
      types: newCourseTypes.map(ct => ct.name)
    })
    
    // Auto-sélectionner s'il n'y a qu'un seul type de cours
    if (newCourseTypes.length === 1 && !props.form.course_type_id) {
      const courseType = newCourseTypes[0]
      props.form.course_type_id = courseType.id
      // Pré-remplir durée et prix
      props.form.duration = courseType.duration_minutes || courseType.duration || 60
      props.form.price = courseType.price || 0
      console.log('✨ [CreateLessonModal] Type de cours auto-sélectionné:', courseType.name)
    }
  }
}, { deep: true, immediate: true })

// Watcher pour auto-remplir durée et prix quand un type de cours est sélectionné
watch(() => props.form.course_type_id, async (newCourseTypeId, oldCourseTypeId) => {
  // Mettre à jour automatiquement à chaque changement de type de cours
  if (newCourseTypeId && props.courseTypes.length > 0) {
    const selectedCourseType = props.courseTypes.find(ct => ct.id === newCourseTypeId)
    if (selectedCourseType) {
      // Toujours mettre à jour la durée et le prix selon le type de cours sélectionné
      props.form.duration = selectedCourseType.duration_minutes || selectedCourseType.duration || 60
      props.form.price = selectedCourseType.price || 0
      
      console.log('✨ [CreateLessonModal] Durée et prix mis à jour automatiquement:', {
        duration: props.form.duration,
        price: props.form.price,
        courseType: selectedCourseType.name,
        previousType: oldCourseTypeId
      })
      
      // Attendre que availableTimes soit recalculé avec la nouvelle durée
      await nextTick()
      // Auto-sélectionner la première heure disponible
      if (availableTimes.value.length > 0 && props.form.date) {
        props.form.time = availableTimes.value[0].value
        console.log('✨ [CreateLessonModal] Première heure disponible auto-sélectionnée après changement de type de cours:', availableTimes.value[0].value)
      }
    }
  }
}, { immediate: false })

// Charger les cours existants pour calculer les heures disponibles
const existingLessons = ref<any[]>([])
const loadingLessons = ref(false)

// Fonction pour charger les cours existants pour une date donnée
async function loadExistingLessons(date: string) {
  // En mode édition, on peut charger les cours même sans selectedSlot (on utilise currentSelectedSlot)
  const hasSlot = props.selectedSlot || (props.editingLesson && currentSelectedSlot.value)
  if (!date || !hasSlot) {
    existingLessons.value = []
    return
  }
  
  try {
    loadingLessons.value = true
    const { $api } = useNuxtApp()
    const response = await $api.get('/lessons', {
      params: {
        date_from: date,
        date_to: date
      }
    })
    
    if (response.data.success) {
      existingLessons.value = response.data.data || []
    } else {
      existingLessons.value = []
    }
  } catch (err) {
    console.error('Erreur chargement cours existants:', err)
    existingLessons.value = []
  } finally {
    loadingLessons.value = false
  }
}

// Convertir une heure (HH:MM) en minutes depuis minuit
function timeToMinutes(time: string): number {
  const [hours, minutes] = time.split(':').map(Number)
  return hours * 60 + minutes
}

// Convertir des minutes depuis minuit en heure (HH:MM)
function minutesToTime(minutes: number): string {
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`
}

/** Créneaux récurrents (abonnements) du club — pour aligner la modale sur RecurringSlotValidator. */
const clubRecurringSlots = ref<any[]>([])

async function loadClubRecurringSlots() {
  try {
    const { $api } = useNuxtApp()
    const res = await $api.get('/club/recurring-slots')
    if (res.data?.success && Array.isArray(res.data.data)) {
      clubRecurringSlots.value = res.data.data
    } else {
      clubRecurringSlots.value = []
    }
  } catch (e) {
    console.warn('⚠️ [CreateLessonModal] Chargement recurring-slots impossible:', e)
    clubRecurringSlots.value = []
  }
}

const appliesRecurringUiBlock = computed(() => {
  if (props.editingLesson) return false
  const f = props.form
  return f.deduct_from_subscription === true || (f.recurring_interval ?? 0) >= 1
})

function getLessonTeacherId(lesson: any): number | null {
  const id = lesson?.teacher_id ?? lesson?.teacher?.id
  return id != null && id !== '' ? Number(id) : null
}

function parseYmd(ymd: string): Date {
  const [y, m, d] = ymd.split('-').map((x) => parseInt(x, 10))
  return new Date(y, m - 1, d)
}

function ymdInRange(d: string, start: string, end: string): boolean {
  const ds = d.substring(0, 10)
  const a = (start || '').substring(0, 10)
  const b = (end || '').substring(0, 10)
  return a <= ds && ds <= b
}

/** Même logique que RecurringSlotValidator::subscriptionRecurringSlotFiresOnDate (Carbon). */
function subscriptionRecurringSlotFiresOnDate(slot: any, occurrenceDateStr: string): boolean {
  const interval = Math.max(1, Math.min(52, Number(slot.recurring_interval) || 1))
  const occurrence = parseYmd(occurrenceDateStr)
  const slotEnd = parseYmd(String(slot.end_date || '').substring(0, 10))
  const anchorBase = parseYmd(String(slot.start_date || '').substring(0, 10))

  if (occurrence.getDay() !== Number(slot.day_of_week)) return false
  if (occurrence < anchorBase || occurrence > slotEnd) return false

  const anchor = new Date(anchorBase)
  while (anchor.getDay() !== Number(slot.day_of_week)) {
    anchor.setDate(anchor.getDate() + 1)
  }
  if (occurrence < anchor) return false

  const daysBetween = Math.round((occurrence.getTime() - anchor.getTime()) / 86400000)
  if (daysBetween < 0 || daysBetween % 7 !== 0) return false
  const weekIndex = daysBetween / 7
  return weekIndex % interval === 0
}

function recurringSlotWindowMinutes(start: string, end: string): number {
  const sm = timeToMinutes(String(start).substring(0, 5))
  const em = timeToMinutes(String(end).substring(0, 5))
  if (em <= sm) return em + 24 * 60 - sm
  return em - sm
}

/** SubscriptionRecurringSlot::MAX_LESSON_LIKE_WINDOW_MINUTES */
function isLessonLikeRecurringSlot(slot: { start_time: string; end_time: string }): boolean {
  const w = recurringSlotWindowMinutes(slot.start_time, slot.end_time)
  return w > 0 && w <= 120
}

function localRangesOverlapOnDate(
  dateStr: string,
  proposedStartHHmm: string,
  durationMinutes: number,
  slotStartRaw: string,
  slotEndRaw: string
): boolean {
  const proposedStart = new Date(`${dateStr}T${proposedStartHHmm}:00`)
  const proposedEnd = new Date(proposedStart.getTime() + durationMinutes * 60000)
  const slotStart = new Date(`${dateStr}T${String(slotStartRaw).substring(0, 5)}:00`)
  let slotEnd = new Date(`${dateStr}T${String(slotEndRaw).substring(0, 5)}:00`)
  if (slotEnd <= slotStart) {
    slotEnd = new Date(slotEnd.getTime() + 86400000)
  }
  return proposedStart < slotEnd && proposedEnd > slotStart
}

/**
 * Indique si la création avec récurrence serait refusée pour la date choisie (1re occurrence),
 * à cause d’un SubscriptionRecurringSlot actif qui chevauche (élève ou enseignant) — aligné backend.
 */
function recurringTimeBlocked(
  dateStr: string,
  startHHmm: string,
  durationMin: number,
  studentId: number,
  teacherId: number
): boolean {
  for (const slot of clubRecurringSlots.value) {
    if (slot.status !== 'active') continue
    if (!ymdInRange(dateStr, String(slot.start_date), String(slot.end_date))) continue
    if (!isLessonLikeRecurringSlot(slot)) continue
    if (!subscriptionRecurringSlotFiresOnDate(slot, dateStr)) continue
    if (!localRangesOverlapOnDate(dateStr, startHHmm, durationMin, slot.start_time, slot.end_time)) continue

    const sid = Number(slot.student_id)
    const tid = Number(slot.teacher_id)
    if (sid === studentId || tid === teacherId) {
      return true
    }
  }
  return false
}

function isTimeSlotAvailableForBooking(
  date: string,
  timeValue: string,
  duration: number,
  maxSlots: number,
  editingLessonId: number | null | undefined
): boolean {
  const timeStart = new Date(`${date}T${timeValue}:00`)
  const timeEnd = new Date(timeStart.getTime() + duration * 60000)

  let overlappingCount = 0
  let teacherLessonOverlap = false
  const formTeacherId = props.form.teacher_id != null ? Number(props.form.teacher_id) : null

  for (const lesson of existingLessons.value) {
    if (editingLessonId != null && lesson.id === editingLessonId) continue
    if (lesson.status === 'cancelled') continue

    const lessonStart = new Date(lesson.start_time)
    let lessonEnd: Date
    if (lesson.end_time) {
      lessonEnd = new Date(lesson.end_time)
    } else if (lesson.course_type?.duration_minutes) {
      lessonEnd = new Date(lessonStart.getTime() + lesson.course_type.duration_minutes * 60000)
    } else {
      lessonEnd = new Date(lessonStart.getTime() + 60 * 60000)
    }

    if (timeStart < lessonEnd && timeEnd > lessonStart) {
      overlappingCount++
      if (formTeacherId != null && getLessonTeacherId(lesson) === formTeacherId) {
        teacherLessonOverlap = true
      }
    }
  }

  if (overlappingCount >= maxSlots) return false
  if (formTeacherId != null && teacherLessonOverlap) return false

  const sid = props.form.student_id != null ? Number(props.form.student_id) : null
  const tid = formTeacherId
  if (
    appliesRecurringUiBlock.value &&
    sid != null &&
    !Number.isNaN(sid) &&
    tid != null &&
    !Number.isNaN(tid) &&
    recurringTimeBlocked(date, timeValue, duration, sid, tid)
  ) {
    return false
  }

  return true
}

/** Nombre max de cours simultanés sur la plage (défini par le créneau). Utilise openSlots si le slot n'a pas max_slots. */
function getSlotMaxSlots(slot: OpenSlot | null | undefined): number {
  if (!slot) return 1
  const fromSlot = slot.max_slots
  if (fromSlot != null && fromSlot >= 1) return fromSlot
  const fromList = props.openSlots?.find(s => s.id === slot.id)?.max_slots
  if (fromList != null && fromList >= 1) return fromList
  return 1
}

// Générer toutes les heures possibles pour le mode édition (00:00 à 23:30)
const allPossibleTimes = computed(() => {
  const times: { value: string; label: string }[] = []
  for (let hour = 0; hour < 24; hour++) {
    times.push({
      value: `${String(hour).padStart(2, '0')}:00`,
      label: `${String(hour).padStart(2, '0')}:00`
    })
    times.push({
      value: `${String(hour).padStart(2, '0')}:30`,
      label: `${String(hour).padStart(2, '0')}:30`
    })
  }
  return times
})

// Calculer les heures disponibles pour le créneau sélectionné
const availableTimes = computed(() => {
  // En mode édition, utiliser le créneau trouvé ou toutes les heures possibles
  if (props.editingLesson) {
    // Si un créneau est trouvé pour la date, utiliser les heures du créneau
    const slotToUse = currentSelectedSlot.value || props.selectedSlot
    if (slotToUse && props.form.date && props.form.duration) {
      const slot = slotToUse
      const duration = props.form.duration || 60
      const date = props.form.date
      
      // Extraire les heures de début et fin du créneau
      const slotStart = slot.start_time?.substring(0, 5) || '09:00'
      const slotEnd = slot.end_time?.substring(0, 5) || '18:00'
      
      const slotStartMinutes = timeToMinutes(slotStart)
      const slotEndMinutes = timeToMinutes(slotEnd)
      
      // Calculer le pas de temps (utiliser la durée du cours comme pas)
      const timeStep = duration
      
      // Générer toutes les heures possibles dans le créneau
      const allTimes: { value: string; label: string; minutes: number }[] = []
      
      for (let minutes = slotStartMinutes; minutes + duration <= slotEndMinutes; minutes += timeStep) {
        const timeStr = minutesToTime(minutes)
        allTimes.push({
          value: timeStr,
          label: timeStr,
          minutes
        })
      }
      
      // Filtrer les heures qui sont déjà complètes (max_slots du créneau = nombre de cours simultanés possibles)
      const maxSlots = getSlotMaxSlots(slot)
      const editId = props.editingLesson?.id ?? null

      const available = allTimes.filter(time =>
        isTimeSlotAvailableForBooking(date, time.value, duration, maxSlots, editId)
      )

      return available
    }
    // Sinon, retourner toutes les heures possibles
    return allPossibleTimes.value
  }
  
  const slotToUse = currentSelectedSlot.value || props.selectedSlot
  if (!slotToUse || !props.form.date || !props.form.duration) {
    return []
  }
  
  const slot = slotToUse
  const duration = props.form.duration || 60
  const date = props.form.date
  
  // Extraire les heures de début et fin du créneau
  const slotStart = slot.start_time?.substring(0, 5) || '09:00'
  const slotEnd = slot.end_time?.substring(0, 5) || '18:00'
  
  const slotStartMinutes = timeToMinutes(slotStart)
  const slotEndMinutes = timeToMinutes(slotEnd)
  
  // Calculer le pas de temps (utiliser la durée du cours comme pas)
  const timeStep = duration
  
  // Générer toutes les heures possibles dans le créneau
  const allTimes: { value: string; label: string; minutes: number }[] = []
  
  for (let minutes = slotStartMinutes; minutes + duration <= slotEndMinutes; minutes += timeStep) {
    const timeStr = minutesToTime(minutes)
    allTimes.push({
      value: timeStr,
      label: timeStr,
      minutes
    })
  }
  
  // Filtrer les heures qui sont déjà complètes (max_slots du créneau = nombre de cours simultanés possibles)
  const maxSlots = getSlotMaxSlots(slot)
  
  const available = allTimes.filter(time => {
    const ok = isTimeSlotAvailableForBooking(date, time.value, duration, maxSlots, null)
    if (!ok) {
      let overlappingCount = 0
      const timeStart = new Date(`${date}T${time.value}:00`)
      const timeEnd = new Date(timeStart.getTime() + duration * 60000)
      for (const lesson of existingLessons.value) {
        if (lesson.status === 'cancelled') continue
        const lessonStart = new Date(lesson.start_time)
        let lessonEnd: Date
        if (lesson.end_time) {
          lessonEnd = new Date(lesson.end_time)
        } else if (lesson.course_type?.duration_minutes) {
          lessonEnd = new Date(lessonStart.getTime() + lesson.course_type.duration_minutes * 60000)
        } else {
          lessonEnd = new Date(lessonStart.getTime() + 60 * 60000)
        }
        if (timeStart < lessonEnd && timeEnd > lessonStart) overlappingCount++
      }
      console.log(
        `🚫 [availableTimes] Plage ${time.value} indisponible (cours ${overlappingCount}/${maxSlots}, enseignant ou récurrence abonnement) — masquée du select`
      )
    }
    return ok
  })

  // Inclure l'heure déjà saisie (ex. clic sur plage 10:40) si elle est dans le créneau et sans conflit,
  // pour qu'elle ne soit pas écrasée par les watchers avec la première plage (9h).
  const formTime = props.form.time ? props.form.time.substring(0, 5) : ''
  if (formTime && /^\d{1,2}:\d{2}$/.test(formTime)) {
    const formMinutes = timeToMinutes(formTime)
    const inSlot = formMinutes >= slotStartMinutes && formMinutes + duration <= slotEndMinutes
    const alreadyInList = available.some(t => t.value === formTime || t.value.substring(0, 5) === formTime)
    if (inSlot && !alreadyInList) {
      if (isTimeSlotAvailableForBooking(date, formTime, duration, maxSlots, null)) {
        available.push({ value: formTime, label: formTime, minutes: formMinutes })
        available.sort((a, b) => a.minutes - b.minutes)
      }
    }
  }

  console.log(`✅ [availableTimes] ${available.length} plage(s) horaire(s) disponible(s) sur ${allTimes.length} possibles`)
  
  return available
})

// Heure de la plage demandée à l'ouverture (clic sur "Créer un cours" sur une plage), pour l'afficher en "(complet)" si elle est pleine.
const requestedTimeFromSlot = ref<string | null>(null)

watch(() => props.show, async (isOpen) => {
  if (isOpen) {
    await loadClubRecurringSlots()
    // Priorité à la prop explicitement passée par le parent (évite toute course avec les watchers)
    const requested = (props.requestedTime ?? props.form.time) ? String(props.requestedTime ?? props.form.time).substring(0, 5) : ''
    if (requested && /^\d{1,2}:\d{2}$/.test(requested)) {
      requestedTimeFromSlot.value = requested
      props.form.time = requested
      if (props.form.date) {
        props.form.start_time = `${props.form.date}T${requested}:00`
      }
    } else if (props.form.time) {
      requestedTimeFromSlot.value = props.form.time.substring(0, 5)
    } else {
      requestedTimeFromSlot.value = null
    }
  } else {
    requestedTimeFromSlot.value = null
  }
}, { immediate: true })

// Options pour le select Heure : inclure la plage cliquée en "(complet)" si elle n'est pas disponible,
// pour éviter l'impression de décalage (heures qui commencent à la plage suivante).
const timeOptionsForSelect = computed(() => {
  const available = availableTimes.value
  const requested = requestedTimeFromSlot.value
  const isRequestedFull = requested && !available.some(t => t.value === requested)
  if (isRequestedFull) {
    return [
      { value: `__complet__${requested}`, label: `${requested} (complet)`, disabled: true },
      ...available.map(t => ({ value: t.value, label: t.label, disabled: false }))
    ]
  }
  return available.map(t => ({ value: t.value, label: t.label, disabled: false }))
})

// Watcher pour mettre à jour le créneau quand la date change en mode édition
const currentSelectedSlot = ref<OpenSlot | null>(props.selectedSlot)

watch(() => props.selectedSlot, (newSlot) => {
  currentSelectedSlot.value = newSlot
  if (newSlot && props.editingLesson) {
    selectedSlotId.value = newSlot.id
  }
})

// Initialiser selectedSlotId quand editingLesson change
watch(() => props.editingLesson, (newEditingLesson) => {
  if (newEditingLesson && currentSelectedSlot.value) {
    selectedSlotId.value = currentSelectedSlot.value.id
  } else if (!newEditingLesson) {
    selectedSlotId.value = null
  }
}, { immediate: true })

watch(() => props.form.date, async (newDate, oldDate) => {
  // En mode édition, trouver le créneau correspondant au nouveau jour de la semaine
  // Mais seulement si aucun créneau n'a été sélectionné manuellement
  if (props.editingLesson && newDate && props.openSlots && props.openSlots.length > 0) {
    const date = new Date(newDate + 'T00:00:00')
    const dayOfWeek = date.getDay() // 0 = dimanche, 1 = lundi, etc.
    
    // Si un créneau est déjà sélectionné manuellement, vérifier qu'il correspond au jour
    if (selectedSlotId.value) {
      const selectedSlot = props.openSlots.find(s => s.id === selectedSlotId.value)
      if (selectedSlot && selectedSlot.day_of_week === dayOfWeek) {
        // Le créneau sélectionné correspond au jour, tout est OK
        currentSelectedSlot.value = selectedSlot
        return
      } else if (selectedSlot && selectedSlot.day_of_week !== dayOfWeek) {
        // Le créneau sélectionné ne correspond pas au jour, trouver un créneau correspondant
        const matchingSlot = props.openSlots.find(slot => slot.day_of_week === dayOfWeek)
        if (matchingSlot) {
          selectedSlotId.value = matchingSlot.id
          currentSelectedSlot.value = matchingSlot
          console.log('🎯 [CreateLessonModal] Créneau ajusté pour correspondre à la date:', {
            date: newDate,
            day_of_week: dayOfWeek,
            slot_id: matchingSlot.id
          })
        } else {
          currentSelectedSlot.value = null
          selectedSlotId.value = null
          console.warn('⚠️ [CreateLessonModal] Aucun créneau trouvé pour le jour:', dayOfWeek)
        }
        return
      }
    }
    
    // Aucun créneau sélectionné manuellement, trouver automatiquement
    const matchingSlot = props.openSlots.find(slot => slot.day_of_week === dayOfWeek)
    if (matchingSlot) {
      currentSelectedSlot.value = matchingSlot
      selectedSlotId.value = matchingSlot.id
      console.log('🎯 [CreateLessonModal] Créneau mis à jour pour la nouvelle date:', {
        date: newDate,
        day_of_week: dayOfWeek,
        slot_id: matchingSlot.id,
        slot_start: matchingSlot.start_time,
        slot_end: matchingSlot.end_time
      })
    } else {
      currentSelectedSlot.value = null
      selectedSlotId.value = null
      console.warn('⚠️ [CreateLessonModal] Aucun créneau trouvé pour le jour:', dayOfWeek)
    }
  }
}, { immediate: true })

// Watcher pour charger les cours existants quand la date change
watch(() => props.form.date, async (newDate, oldDate) => {
  if (newDate && (currentSelectedSlot.value || props.editingLesson || props.selectedSlot)) {
    await loadExistingLessons(newDate)
    // Attendre que le computed availableTimes soit recalculé
    await nextTick()
    // En mode édition, ne pas changer l'heure si elle est déjà définie et disponible
    if (props.editingLesson && props.form.time) {
      const isCurrentTimeAvailable = availableTimes.value.some(t => t.value === props.form.time)
      if (!isCurrentTimeAvailable && availableTimes.value.length > 0) {
        // L'heure actuelle n'est plus disponible, sélectionner la première disponible
        props.form.time = availableTimes.value[0].value
        console.log('⚠️ [CreateLessonModal] Heure actuelle non disponible, première heure disponible sélectionnée:', availableTimes.value[0].value)
      } else if (isCurrentTimeAvailable) {
        console.log('✅ [CreateLessonModal] Heure actuelle toujours disponible:', props.form.time)
      }
    } else if (!props.editingLesson && currentSelectedSlot.value) {
      const currentTime = props.form.time ? props.form.time.substring(0, 5) : ''
      const isCurrentTimeAvailable = currentTime && availableTimes.value.some(t => t.value === currentTime || (t.value && t.value.substring(0, 5) === currentTime))
      const requested = requestedTimeFromSlot.value ? requestedTimeFromSlot.value.substring(0, 5) : null
      const requestedInList = requested && availableTimes.value.some(t => t.value === requested || (t.value && t.value.substring(0, 5) === requested))

      if (isCurrentTimeAvailable) {
        console.log('✅ [CreateLessonModal] Heure actuelle toujours disponible, conservée:', currentTime)
      } else if (requested && requestedInList) {
        props.form.time = requested
        console.log('✅ [CreateLessonModal] Heure de la plage cliquée conservée après changement de date:', requested)
      } else if (availableTimes.value.length > 0 && props.form.course_type_id) {
        if (!currentTime || !isCurrentTimeAvailable) {
          props.form.time = availableTimes.value[0].value
          console.log('✨ [CreateLessonModal] Première heure disponible auto-sélectionnée après changement de date:', availableTimes.value[0].value)
        }
      } else if (availableTimes.value.length === 0) {
        if (requested && props.form.time && props.form.time.substring(0, 5) === requested) {
          // Garder l'heure demandée (sera affichée en "complet" si besoin)
          return
        }
        props.form.time = ''
        console.log('⚠️ [CreateLessonModal] Aucune heure disponible pour cette date')
      }
    }
  } else {
    existingLessons.value = []
    if (!props.editingLesson) {
      if (!requestedTimeFromSlot.value) props.form.time = ''
    }
  }
}, { immediate: true })

// Watcher pour auto-sélectionner la première heure disponible quand availableTimes change
watch(() => availableTimes.value, (newTimes, oldTimes) => {
  // En mode édition, ne pas changer l'heure automatiquement SAUF si elle n'est plus disponible
  if (props.editingLesson) {
    // Vérifier si l'heure actuelle est toujours disponible
    if (props.form.time) {
      const isCurrentTimeAvailable = newTimes.some(t => t.value === props.form.time)
      if (!isCurrentTimeAvailable && newTimes.length > 0) {
        // L'heure actuelle n'est plus disponible, sélectionner la première disponible
        props.form.time = newTimes[0].value
        console.log('⚠️ [CreateLessonModal] Heure actuelle non disponible en mode édition, première heure disponible sélectionnée:', newTimes[0].value)
      } else if (isCurrentTimeAvailable) {
        console.log('✅ [CreateLessonModal] Heure actuelle toujours disponible en mode édition:', props.form.time)
      }
    }
    return
  }
  
  // Auto-sélectionner la première heure disponible si :
  // - Il y a des heures disponibles
  // - La date et le type de cours sont définis
  // - Aucune heure n'est sélectionnée OU l'heure sélectionnée n'est plus disponible
  if (newTimes.length > 0 && props.form.date && props.form.course_type_id) {
    const currentTime = props.form.time ? props.form.time.substring(0, 5) : ''
    const isCurrentTimeAvailable = currentTime && newTimes.some(t => t.value === currentTime || (t.value && t.value.substring(0, 5) === currentTime))
    // Heure demandée à l'ouverture (clic sur une plage horaire) : la conserver si elle est dans la liste
    const requested = requestedTimeFromSlot.value ? requestedTimeFromSlot.value.substring(0, 5) : null
    const requestedInList = requested && newTimes.some(t => t.value === requested || (t.value && t.value.substring(0, 5) === requested))

    if (requested && requestedInList && (!currentTime || !isCurrentTimeAvailable)) {
      props.form.time = requested
      console.log('✅ [CreateLessonModal] Heure de la plage cliquée conservée:', requested)
      return
    }
    if (!currentTime || !isCurrentTimeAvailable) {
      props.form.time = newTimes[0].value
      console.log('✨ [CreateLessonModal] Première heure disponible auto-sélectionnée depuis availableTimes:', newTimes[0].value)
    }
  } else if (newTimes.length === 0 && props.form.time) {
    // Ne pas réinitialiser si l'heure correspond à la plage demandée à l'ouverture (elle peut être affichée en "(complet)")
    if (requestedTimeFromSlot.value && props.form.time.substring(0, 5) === requestedTimeFromSlot.value.substring(0, 5)) {
      return
    }
    props.form.time = ''
    console.log('⚠️ [CreateLessonModal] Plus d\'heures disponibles, heure réinitialisée')
  }
}, { immediate: true })

// Watcher pour recharger les cours quand le créneau change (via selectedSlot ou currentSelectedSlot)
watch(() => [props.selectedSlot, currentSelectedSlot.value, selectedSlotId.value], async ([newSlot, newCurrentSlot, newSlotId]) => {
  const slotToUse = props.editingLesson ? newCurrentSlot : newSlot
  if (slotToUse && props.form.date) {
    await loadExistingLessons(props.form.date)
    // Attendre que le computed availableTimes soit recalculé
    await nextTick()
    
    // En mode édition, vérifier si l'heure actuelle est toujours disponible
    if (props.editingLesson && props.form.time) {
      const isCurrentTimeAvailable = availableTimes.value.some(t => t.value === props.form.time)
      if (!isCurrentTimeAvailable && availableTimes.value.length > 0) {
        // L'heure actuelle n'est plus disponible, sélectionner la première disponible
        props.form.time = availableTimes.value[0].value
        console.log('⚠️ [CreateLessonModal] Heure actuelle non disponible après changement de créneau en mode édition, première heure disponible sélectionnée:', availableTimes.value[0].value)
      } else if (isCurrentTimeAvailable) {
        console.log('✅ [CreateLessonModal] Heure actuelle toujours disponible après changement de créneau en mode édition:', props.form.time)
      }
    } else if (!props.editingLesson) {
      if (availableTimes.value.length > 0 && props.form.course_type_id) {
        const currentTime = props.form.time ? props.form.time.substring(0, 5) : ''
        const isCurrentTimeAvailable = currentTime && availableTimes.value.some(t => t.value === currentTime || (t.value && t.value.substring(0, 5) === currentTime))
        const requested = requestedTimeFromSlot.value ? requestedTimeFromSlot.value.substring(0, 5) : null
        const requestedInList = requested && availableTimes.value.some(t => t.value === requested || (t.value && t.value.substring(0, 5) === requested))

        if (isCurrentTimeAvailable) {
          console.log('✅ [CreateLessonModal] Heure actuelle toujours disponible après changement de créneau, conservée:', currentTime)
        } else if (requested && requestedInList) {
          props.form.time = requested
          console.log('✅ [CreateLessonModal] Heure de la plage cliquée conservée après changement de créneau:', requested)
        } else if (!currentTime || !isCurrentTimeAvailable) {
          props.form.time = availableTimes.value[0].value
          console.log('✨ [CreateLessonModal] Première heure disponible auto-sélectionnée après changement de créneau:', availableTimes.value[0].value)
        }
      }
    }
  } else {
    existingLessons.value = []
    if (!props.editingLesson && !requestedTimeFromSlot.value) {
      props.form.time = ''
    }
  }
})

// Watcher pour recharger les cours quand la durée change (pour recalculer les heures disponibles)
watch(() => props.form.duration, async () => {
  if (props.form.date && (props.selectedSlot || (props.editingLesson && currentSelectedSlot.value)) && props.form.course_type_id) {
    await loadExistingLessons(props.form.date)
    await nextTick()
    if (availableTimes.value.length > 0) {
      // En mode édition : garder l'heure actuelle si elle est encore disponible
      if (props.editingLesson && props.form.time) {
        const stillAvailable = availableTimes.value.some(t => t.value === props.form.time)
        if (stillAvailable) {
          console.log('✅ [CreateLessonModal] Heure conservée après changement de durée:', props.form.time)
          return
        }
      }
      const requested = requestedTimeFromSlot.value ? requestedTimeFromSlot.value.substring(0, 5) : null
      const requestedInList = requested && availableTimes.value.some(t => t.value === requested || (t.value && t.value.substring(0, 5) === requested))
      if (requested && requestedInList) {
        props.form.time = requested
      } else {
        props.form.time = availableTimes.value[0].value
        console.log('✨ [CreateLessonModal] Première heure disponible auto-sélectionnée après changement de durée:', availableTimes.value[0].value)
      }
    } else {
      if (!requestedTimeFromSlot.value) {
        props.form.time = ''
        console.log('⚠️ [CreateLessonModal] Plus d\'heures disponibles après changement de durée')
      }
    }
  }
})

// Watcher pour recalculer la disponibilité quand l'heure change
watch(() => props.form.time, () => {
  // La disponibilité est recalculée automatiquement via les fonctions isTeacherAvailable et isStudentAvailable
  // Pas besoin de recharger les cours, ils sont déjà chargés pour la date
})

// Gérer est_legacy selon le choix de déduction d'abonnement
watch(() => props.form.deduct_from_subscription, (newValue) => {
  if (newValue === true) {
    // Si "Déduire d'un abonnement existant" est sélectionné, utiliser la valeur de l'abonnement
    // Le backend récupérera automatiquement la valeur de l'abonnement actif de l'élève
    props.form.est_legacy = null
    console.log('🔄 [CreateLessonModal] Déduction d\'abonnement activée - est_legacy mis à null (sera défini par le backend depuis l\'abonnement)')
  } else {
    // Si "Séance non incluse dans l'abonnement" est sélectionné, on garde la valeur actuelle (ou on la laisse modifier)
    // La valeur sera modifiable via les boutons DCL/NDCL
    console.log('🔄 [CreateLessonModal] Séance non incluse dans l\'abonnement - DCL/NDCL modifiable')
  }
})

// Vérifier si un enseignant est disponible pour la plage horaire sélectionnée
function isTeacherAvailable(teacherId: number): boolean {
  if (!props.form.date || !props.form.time || !props.form.duration) {
    return true // Si pas de date/heure/durée, considérer comme disponible
  }
  
  const lessonStart = new Date(`${props.form.date}T${props.form.time}:00`)
  const lessonEnd = new Date(lessonStart.getTime() + props.form.duration * 60000)
  
  // Vérifier si l'enseignant a déjà un cours qui se chevauche
  for (const lesson of existingLessons.value) {
    if (lesson.status === 'cancelled') continue
    if (lesson.teacher_id !== teacherId) continue
    
    const existingStart = new Date(lesson.start_time)
    let existingEnd: Date
    
    // Calculer la fin du cours existant
    if (lesson.end_time) {
      existingEnd = new Date(lesson.end_time)
    } else if (lesson.course_type?.duration_minutes) {
      existingEnd = new Date(existingStart.getTime() + lesson.course_type.duration_minutes * 60000)
    } else {
      existingEnd = new Date(existingStart.getTime() + 60 * 60000) // 60 min par défaut
    }
    
    // Vérifier le chevauchement
    if (lessonStart < existingEnd && lessonEnd > existingStart) {
      return false // L'enseignant n'est pas disponible
    }
  }
  
  return true // L'enseignant est disponible
}

// Vérifier si un élève est disponible pour la plage horaire sélectionnée
function isStudentAvailable(studentId: number): boolean {
  if (!props.form.date || !props.form.time || !props.form.duration) {
    return true // Si pas de date/heure/durée, considérer comme disponible
  }
  
  const lessonStart = new Date(`${props.form.date}T${props.form.time}:00`)
  const lessonEnd = new Date(lessonStart.getTime() + props.form.duration * 60000)
  
  // Vérifier si l'élève a déjà un cours qui se chevauche
  for (const lesson of existingLessons.value) {
    if (lesson.status === 'cancelled') continue
    
    // Vérifier si l'élève est l'étudiant principal
    if (lesson.student_id === studentId) {
      const existingStart = new Date(lesson.start_time)
      let existingEnd: Date
      
      // Calculer la fin du cours existant
      if (lesson.end_time) {
        existingEnd = new Date(lesson.end_time)
      } else if (lesson.course_type?.duration_minutes) {
        existingEnd = new Date(existingStart.getTime() + lesson.course_type.duration_minutes * 60000)
      } else {
        existingEnd = new Date(existingStart.getTime() + 60 * 60000) // 60 min par défaut
      }
      
      // Vérifier le chevauchement
      if (lessonStart < existingEnd && lessonEnd > existingStart) {
        return false // L'élève n'est pas disponible
      }
    }
    
    // Vérifier si l'élève est dans la relation many-to-many
    if (lesson.students && Array.isArray(lesson.students)) {
      const isInStudents = lesson.students.some((s: any) => s.id === studentId)
      if (isInStudents) {
        const existingStart = new Date(lesson.start_time)
        let existingEnd: Date
        
        // Calculer la fin du cours existant
        if (lesson.end_time) {
          existingEnd = new Date(lesson.end_time)
        } else if (lesson.course_type?.duration_minutes) {
          existingEnd = new Date(existingStart.getTime() + lesson.course_type.duration_minutes * 60000)
        } else {
          existingEnd = new Date(existingStart.getTime() + 60 * 60000) // 60 min par défaut
        }
        
        // Vérifier le chevauchement
        if (lessonStart < existingEnd && lessonEnd > existingStart) {
          return false // L'élève n'est pas disponible
        }
      }
    }
  }
  
  return true // L'élève est disponible
}

// Fonction pour formater la date de l'exemple
function formatExampleDate(): string {
  if (!props.form.date) return '13 novembre 2025'
  const date = new Date(props.form.date + 'T00:00:00')
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}

// Fonction pour obtenir le label de l'intervalle de récurrence
function getRecurringIntervalLabel(): string {
  const interval = props.form.recurring_interval || 1
  switch (interval) {
    case 1: return 'Chaque semaine'
    case 2: return 'Toutes les 2 semaines'
    case 3: return 'Toutes les 3 semaines'
    case 4: return 'Toutes les 4 semaines'
    default: return `Toutes les ${interval} semaines`
  }
}

// Fonction pour obtenir l'exemple des prochaines dates
function getNextDatesExample(): string {
  if (!props.form.date) return 'aux dates correspondantes'
  
  const interval = props.form.recurring_interval || 1
  const startDate = new Date(props.form.date + 'T00:00:00')
  
  // Générer les 2 prochaines dates
  const date1 = new Date(startDate)
  date1.setDate(startDate.getDate() + (7 * interval))
  
  const date2 = new Date(startDate)
  date2.setDate(startDate.getDate() + (7 * interval * 2))
  
  const formatShort = (d: Date) => d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
  
  return `le ${formatShort(date1)}, le ${formatShort(date2)}, etc.`
}

</script>

