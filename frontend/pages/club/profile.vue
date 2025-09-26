<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 flex items-center">
          <Icon name="building" class="text-4xl mr-3 text-blue-600" />
          Profil du Club
        </h1>
        <p class="mt-2 text-gray-600">Gérez les informations et activités de votre club</p>
      </div>

      <!-- Profile Form -->
      <div class="bg-white shadow-lg rounded-lg border border-gray-200">
        <form @submit.prevent="updateClub" class="space-y-6 p-6">
          <!-- Informations générales -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <Icon name="info" class="text-xl mr-2 text-gray-600" />
              Informations générales
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nom du club</label>
                <input v-model="form.name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Nom de votre club" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email de contact</label>
                <input v-model="form.email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="contact@votreclub.com" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                <input v-model="form.phone" type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="+33 1 23 45 67 89" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Site web</label>
                <input v-model="form.website" type="url" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="https://votreclub.com" />
              </div>
            </div>

            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Décrivez votre club, ses valeurs et ses services..."></textarea>
            </div>
          </div>

          <!-- Adresse -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <Icon name="location" class="text-xl mr-2 text-red-500" />
              Adresse
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
                <input v-model="form.address" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="123 Rue de l'Équitation" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Code postal</label>
                <input v-model="form.postal_code" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="75001" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                <input v-model="form.city" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Paris" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pays</label>
                <input v-model="form.country" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="France" />
              </div>
            </div>
          </div>

          <!-- Configuration des cours -->
          <div v-if="selectedDisciplines.length > 0" class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <Icon name="settings" class="text-xl mr-2 text-gray-600" />
              Configuration des cours
            </h2>
            <p class="text-sm text-gray-600 mb-4">
              Configurez la durée et le prix par défaut pour chaque type de cours que vous proposez.
            </p>

            <div class="space-y-4">
              <div v-for="disciplineId in selectedDisciplines" :key="disciplineId" class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-start justify-between mb-4">
                  <div class="flex items-center">
                    <Icon :name="getActivityById(getDisciplineById(disciplineId)?.activity_type_id)?.icon" class="text-lg mr-2 text-blue-600" />
                    <div>
                      <h4 class="font-medium text-gray-900">{{ getDisciplineById(disciplineId)?.name }}</h4>
                      <p class="text-sm text-gray-500">{{ getActivityById(getDisciplineById(disciplineId)?.activity_type_id)?.name }}</p>
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      ⏱️ Durée par défaut
                    </label>
                    <select 
                      v-model="disciplineSettings[disciplineId].duration"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                      <option v-for="duration in availableDurations" :key="duration" :value="duration">
                        {{ formatDuration(duration) }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      💰 Prix par défaut (€)
                    </label>
                    <input 
                      v-model.number="disciplineSettings[disciplineId].price"
                      type="number" 
                      step="0.01" 
                      min="0"
                      placeholder="25.00"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                  </div>

                  <div class="flex items-end">
                    <div class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3 w-full">
                      <div class="font-medium text-gray-700 mb-1 flex items-center">
                        <Icon name="lightbulb" class="mr-1 text-yellow-500" />
                        Calcul automatique
                      </div>
                      <div v-if="disciplineSettings[disciplineId].duration && disciplineSettings[disciplineId].price">
                        {{ (disciplineSettings[disciplineId].price / disciplineSettings[disciplineId].duration * 60).toFixed(2) }}€/heure
                      </div>
                      <div v-else class="text-gray-400">Renseignez durée et prix</div>
                    </div>
                  </div>
                </div>

                <!-- Informations complémentaires -->
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                      <Icon name="users" class="mr-1 text-blue-500" />
                      Participants (min - max)
                    </label>
                    <div class="flex items-center space-x-2">
                      <input 
                        v-model.number="disciplineSettings[disciplineId].min_participants"
                        type="number" 
                        min="1"
                        placeholder="1"
                        class="w-1/2 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      >
                      <span class="text-gray-500">à</span>
                      <input 
                        v-model.number="disciplineSettings[disciplineId].max_participants"
                        type="number" 
                        min="1"
                        placeholder="8"
                        class="w-1/2 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      >
                    </div>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                      <Icon name="note" class="mr-1 text-gray-500" />
                      Notes (optionnel)
                    </label>
                    <input 
                      v-model="disciplineSettings[disciplineId].notes"
                      type="text" 
                      placeholder="Matériel fourni, niveau requis..."
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Section Horaires de fonctionnement -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Horaires de fonctionnement
            </h2>
            <p class="text-sm text-gray-600 mb-4">
              Configurez vos créneaux d'ouverture par jour de la semaine. Seuls ces créneaux permettront la réservation de cours.
            </p>

            <!-- Configuration des horaires -->
            <div class="space-y-4">
              <div v-for="(day, dayIndex) in scheduleConfig" :key="day.name" class="bg-white border border-gray-300 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                  <h3 class="font-bold text-gray-900">{{ day.name }}</h3>
                  <div class="space-x-2">
                    <button 
                      @click="addPeriod(dayIndex)"
                      class="bg-emerald-600 text-white px-3 py-1 rounded text-sm hover:bg-emerald-700"
                    >
                      <Icon name="plus" class="mr-1" />
                      Ajouter
                    </button>
                    <button 
                      @click="showRecurrenceModal = true; selectedDay = dayIndex"
                      class="bg-cyan-600 text-white px-3 py-1 rounded text-sm hover:bg-cyan-700"
                    >
                      <Icon name="sync" class="mr-1" />
                      Récurrent
                    </button>
                  </div>
                </div>
                
                <!-- Version simplifiée des périodes -->
                <div class="space-y-2">
                  <div v-if="day.periods && day.periods.length > 0">
                    <div v-for="(period, periodIndex) in day.periods" :key="period.id" 
                         class="bg-blue-50 border border-blue-200 rounded p-3">
                      <div class="flex items-center justify-between">
                        <span class="text-sm font-medium flex items-center">
                          <Icon name="calendar-day" class="mr-1 text-blue-500" />
                          Période {{ periodIndex + 1 }}: {{ period.startHour }}:{{ period.startMinute }} - {{ period.endHour }}:{{ period.endMinute }}
                        </span>
                        <button 
                          @click="removePeriod(dayIndex, periodIndex)"
                          class="text-red-500 hover:text-red-700 text-sm"
                        >
                          <Icon name="trash" class="mr-1" />
                          Supprimer
                        </button>
                      </div>
                      
                      <!-- Selects simplifiés -->
                      <div class="grid grid-cols-4 gap-2 mt-2">
                        <select v-model="period.startHour" class="border rounded px-2 py-1 text-sm">
                          <option v-for="hour in hours" :key="hour" :value="hour">{{ hour }}h</option>
                        </select>
                        <select v-model="period.startMinute" class="border rounded px-2 py-1 text-sm">
                          <option v-for="minute in minutes" :key="minute" :value="minute">{{ minute }}</option>
                        </select>
                        <select v-model="period.endHour" class="border rounded px-2 py-1 text-sm">
                          <option v-for="hour in hours" :key="hour" :value="hour">{{ hour }}h</option>
                        </select>
                        <select v-model="period.endMinute" class="border rounded px-2 py-1 text-sm">
                          <option v-for="minute in minutes" :key="minute" :value="minute">{{ minute }}</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  
                  <div v-else class="text-center py-4 text-gray-500">
                    <p class="text-sm flex items-center justify-center">
                      <Icon name="clock" class="mr-1 text-gray-400" />
                      Aucune période configurée
                    </p>
                    <button 
                      @click="addPeriod(dayIndex)"
                      class="mt-2 bg-emerald-600 text-white px-3 py-1 rounded text-sm hover:bg-emerald-700"
                    >
                      <Icon name="plus" class="mr-1" />
                      Ajouter une période
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Récurrence -->
          <div v-if="showRecurrenceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
              <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <Icon name="sync" class="mr-2 text-blue-500" />
                Appliquer à d'autres jours
              </h3>
              <p class="text-sm text-gray-600 mb-4">
                Copiez les périodes de <strong>{{ scheduleConfig[selectedDay]?.name }}</strong> vers d'autres jours :
              </p>
              
              <div class="space-y-2 mb-6">
                <label v-for="(day, dayIndex) in scheduleConfig" :key="day.name" 
                       class="flex items-center space-x-3 cursor-pointer p-2 rounded hover:bg-gray-50">
                  <input 
                    :value="dayIndex"
                    type="checkbox"
                    :checked="dayIndex === selectedDay"
                    :disabled="dayIndex === selectedDay"
                    @change="handleRecurrenceSelection"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  >
                  <span class="text-sm" :class="dayIndex === selectedDay ? 'font-bold text-blue-600' : ''">
                    {{ day.name }} {{ dayIndex === selectedDay ? '(source)' : '' }}
                  </span>
                </label>
              </div>
              
              <div class="flex items-center justify-end space-x-3">
                <button 
                  @click="showRecurrenceModal = false"
                  class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
                >
                  Annuler
                </button>
                <button 
                  @click="applyRecurrenceFromSelection"
                  class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700 transition-colors"
                >
                  Appliquer
                </button>
              </div>
            </div>
          </div>

          <!-- Activités du club -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <Icon name="running" class="text-xl mr-2 text-green-600" />
              Activités proposées
            </h2>

            <div class="mb-4">
              <p class="text-sm text-gray-600 mb-4">Sélectionnez les activités que votre club propose :</p>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="activity in availableActivities" :key="activity.id" 
                     class="flex items-center p-4 border rounded-lg hover:bg-gray-50 transition-colors"
                     :class="selectedActivities.includes(activity.id) ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                  <input :id="'activity-' + activity.id" 
                         v-model="selectedActivities" 
                         :value="activity.id" 
                         type="checkbox" 
                         class="h-4 w-4 text-blue-500 focus:ring-blue-500 border-gray-300 rounded">
                  <label :for="'activity-' + activity.id" class="ml-3 flex items-center cursor-pointer">
                    <Icon :name="activity.icon" class="text-2xl mr-2 text-blue-600" />
                    <div>
                      <div class="font-medium text-gray-900">{{ activity.name }}</div>
                      <div class="text-sm text-gray-500">{{ activity.description }}</div>
                    </div>
                  </label>
                </div>
              </div>
            </div>

            <!-- Spécialités par activité -->
            <div v-if="selectedActivities.length > 0" class="mt-6">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-md font-medium text-gray-900">Cours proposés par activité</h3>
                <button
                  v-if="!showAddSpecialtyForm"
                  @click="showAddSpecialtyForm = true"
                  class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors flex items-center space-x-2 text-sm"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                  <span>Ajouter une spécialité</span>
                </button>
              </div>
              
              <!-- Formulaire d'ajout de spécialité -->
              <AddCustomSpecialtyForm
                v-if="showAddSpecialtyForm"
                :available-activities="availableActivities"
                @cancel="showAddSpecialtyForm = false"
                @success="handleAddSpecialtySuccess"
              />

              <div v-for="activityId in selectedActivities" :key="activityId" class="mb-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                  <h4 class="font-medium text-gray-900 mb-2 flex items-center">
                    <Icon :name="getActivityById(activityId)?.icon" class="text-lg mr-2 text-green-600" />
                    {{ getActivityById(activityId)?.name }}
                  </h4>
                  
                  <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <label v-for="discipline in getDisciplinesByActivity(activityId)" :key="discipline.id"
                           class="flex items-center p-2 text-sm">
                      <input :id="'discipline-' + discipline.id" 
                             v-model="selectedDisciplines" 
                             :value="discipline.id" 
                             type="checkbox" 
                             class="h-3 w-3 text-blue-500 focus:ring-blue-500 border-gray-300 rounded mr-2">
                      <span class="text-gray-700">{{ discipline.name }}</span>
                    </label>
                    
                    <!-- Spécialités personnalisées pour cette activité -->
                    <template v-for="customSpecialty in getCustomSpecialtiesByActivity(activityId)" :key="'custom-' + customSpecialty.id">
                      <!-- Affiche le formulaire de modification si on est en mode édition pour CETTE spécialité -->
                      <div v-if="editingSpecialtyId === customSpecialty.id" class="col-span-2 md:col-span-3">
                        <EditCustomSpecialtyForm
                          :specialty="customSpecialty"
                          :available-activities="availableActivities"
                          @cancel="editingSpecialtyId = null"
                          @success="handleEditSpecialtySuccess"
                        />
                      </div>

                      <!-- Affiche la spécialité normalement sinon -->
                      <div v-else
                           :class="[
                             'flex items-center justify-between p-2 text-sm rounded border',
                             customSpecialty.is_active ? 'bg-blue-50 border-blue-200' : 'bg-gray-100 border-gray-200 opacity-60'
                           ]">
                        <div class="flex items-center">
                          <input :id="'custom-specialty-' + customSpecialty.id" 
                                 v-model="selectedCustomSpecialties" 
                                 :value="customSpecialty.id" 
                                 type="checkbox" 
                                 class="h-3 w-3 text-blue-500 focus:ring-blue-500 border-gray-300 rounded mr-2">
                          <span class="font-medium" :class="[customSpecialty.is_active ? 'text-gray-700' : 'text-gray-500 line-through']">
                            {{ customSpecialty.name }}
                          </span>
                          <span class="text-xs text-blue-600 ml-1">(personnalisée)</span>
                        </div>
                        <div class="flex items-center space-x-2">
                          <button
                            type="button"
                            @click="editingSpecialtyId = customSpecialty.id"
                            class="p-1 text-gray-500 hover:text-blue-700 hover:bg-blue-100 rounded"
                            title="Modifier"
                          >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                          </button>
                          <button
                            type="button"
                            @click="toggleCustomSpecialty(customSpecialty.id)"
                            :class="[
                              'px-2 py-1 text-xs rounded transition-colors',
                              customSpecialty.is_active 
                                ? 'bg-red-100 text-red-700 hover:bg-red-200' 
                                : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'
                            ]"
                            :title="customSpecialty.is_active ? 'Désactiver' : 'Activer'"
                          >
                            {{ customSpecialty.is_active ? 'Désactiver' : 'Activer' }}
                          </button>
                        </div>
                      </div>
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Statut du club -->
          <div class="pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <span class="text-xl mr-2">⚙️</span>
              Paramètres
            </h2>

            <div class="flex items-center">
              <input v-model="form.is_active" type="checkbox" id="is_active" 
                     class="h-4 w-4 text-blue-500 focus:ring-blue-500 border-gray-300 rounded">
              <label for="is_active" class="ml-2 text-sm text-gray-700">
                Club actif (visible sur la plateforme)
              </label>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <button type="button" @click="cancelEdit" 
                    class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
              Annuler
            </button>
            <button type="submit" :disabled="loading"
                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 flex items-center justify-center w-48">
              <svg v-if="loading" class="animate-spin h-4 w-4 text-white mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span class="text-center">Enregistrer les modifications</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useToast } from '~/composables/useToast'
import AddCustomSpecialtyForm from '~/components/AddCustomSpecialtyForm.vue'
import EditCustomSpecialtyForm from '~/components/EditCustomSpecialtyForm.vue'

const authStore = useAuthStore()
const loading = ref(false)
const toast = useToast()

// Données du formulaire
const form = ref({
  name: '',
  email: '',
  phone: '',
  website: '',
  description: '',
  address: '',
  city: '',
  postal_code: '',
  country: '',
  is_active: true
})

// Configuration des horaires avec périodes dynamiques
const scheduleConfig = ref([
  { 
    name: 'Lundi', 
    dayIndex: 1, 
    periods: [
      { id: 1, startHour: '08', startMinute: '00', endHour: '12', endMinute: '00' },
      { id: 2, startHour: '14', startMinute: '00', endHour: '18', endMinute: '00' }
    ]
  },
  { 
    name: 'Mardi', 
    dayIndex: 2, 
    periods: [
      { id: 3, startHour: '08', startMinute: '00', endHour: '12', endMinute: '00' },
      { id: 4, startHour: '14', startMinute: '00', endHour: '18', endMinute: '00' }
    ]
  },
  { 
    name: 'Mercredi', 
    dayIndex: 3, 
    periods: [
      { id: 5, startHour: '08', startMinute: '00', endHour: '12', endMinute: '00' },
      { id: 6, startHour: '14', startMinute: '00', endHour: '18', endMinute: '00' }
    ]
  },
  { 
    name: 'Jeudi', 
    dayIndex: 4, 
    periods: [
      { id: 7, startHour: '08', startMinute: '00', endHour: '12', endMinute: '00' },
      { id: 8, startHour: '14', startMinute: '00', endHour: '18', endMinute: '00' }
    ]
  },
  { 
    name: 'Vendredi', 
    dayIndex: 5, 
    periods: [
      { id: 9, startHour: '08', startMinute: '00', endHour: '12', endMinute: '00' },
      { id: 10, startHour: '14', startMinute: '00', endHour: '18', endMinute: '00' }
    ]
  },
  { 
    name: 'Samedi', 
    dayIndex: 6, 
    periods: [
      { id: 11, startHour: '09', startMinute: '00', endHour: '12', endMinute: '00' },
      { id: 12, startHour: '14', startMinute: '00', endHour: '17', endMinute: '00' }
    ]
  },
  { 
    name: 'Dimanche', 
    dayIndex: 0, 
    periods: []
  }
])

// Heures et minutes pour les selects
const hours = Array.from({ length: 17 }, (_, i) => (i + 6).toString().padStart(2, '0')) // 06-22
const minutes = ['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55']

// Gestion des périodes
const showRecurrenceModal = ref(false)
const selectedDay = ref(null)
const selectedTargetDays = ref([])
let nextPeriodId = 100

// Fonctions pour gérer les périodes
const addPeriod = (dayIndex) => {
  const newPeriod = {
    id: nextPeriodId++,
    startHour: '09',
    startMinute: '00',
    endHour: '17',
    endMinute: '00'
  }
  scheduleConfig.value[dayIndex].periods.push(newPeriod)
}

const removePeriod = (dayIndex, periodIndex) => {
  scheduleConfig.value[dayIndex].periods.splice(periodIndex, 1)
}

const handleRecurrenceSelection = (event) => {
  const dayIndex = parseInt(event.target.value)
  if (event.target.checked) {
    selectedTargetDays.value.push(dayIndex)
  } else {
    selectedTargetDays.value = selectedTargetDays.value.filter(d => d !== dayIndex)
  }
}

const applyRecurrenceFromSelection = () => {
  if (selectedDay.value !== null && selectedTargetDays.value.length > 0) {
    const sourceDay = scheduleConfig.value[selectedDay.value]
    const sourcePeriods = JSON.parse(JSON.stringify(sourceDay.periods)) // Clone profond
    
    selectedTargetDays.value.forEach(targetDayIndex => {
      if (targetDayIndex !== selectedDay.value) {
        // Réassigner des IDs uniques
        const newPeriods = sourcePeriods.map(period => ({
          ...period,
          id: nextPeriodId++
        }))
        scheduleConfig.value[targetDayIndex].periods = newPeriods
      }
    })
  }
  
  showRecurrenceModal.value = false
  selectedTargetDays.value = []
  selectedDay.value = null
}

// Activités et spécialités
const availableActivities = ref([])
const availableDisciplines = ref([])
const selectedActivities = ref([])
const selectedDisciplines = ref([])
const customSpecialties = ref([])
const selectedCustomSpecialties = ref([])
const showAddSpecialtyForm = ref(false)
const editingSpecialtyId = ref(null)

// Configuration des cours par discipline
const disciplineSettings = ref({})

// Durées disponibles par tranches de 5 minutes (15min à 1h)
const availableDurations = computed(() => {
  const durations = []
  for (let duration = 15; duration <= 60; duration += 5) {
    durations.push(duration)
  }
  return durations
})

// Formatage de l'affichage des durées
const formatDuration = (duration) => {
  if (duration < 60) {
    return `${duration} minutes`
  } else {
    const hours = Math.floor(duration / 60)
    const minutes = duration % 60
    if (minutes === 0) {
      return `${hours}h`
    } else {
      return `${hours}h${minutes.toString().padStart(2, '0')}`
    }
  }
}

// Charger les données
const loadClubData = async () => {
  try {
    console.log('🔄 Chargement du profil club...')
    
    // Utiliser $api qui inclut automatiquement le token via l'intercepteur
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/profile')
    
    console.log('✅ Profil club reçu:', response)
    
    if (response.data.success && response.data.data) {
      const club = response.data.data
      form.value = {
        name: club.name || '',
        email: club.email || '',
        phone: club.phone || '',
        website: club.website || '',
        description: club.description || '',
        address: club.address || '',
        city: club.city || '',
        postal_code: club.postal_code || '',
        country: club.country || '',
        is_active: club.is_active !== false
      }
      
      // Charger les activités sélectionnées (avec parsing JSON si nécessaire)
      if (club.activity_types) {
        try {
          const activities = typeof club.activity_types === 'string' 
            ? JSON.parse(club.activity_types) 
            : club.activity_types
          selectedActivities.value = Array.isArray(activities) 
            ? activities.map(activity => typeof activity === 'object' ? activity.id : activity)
            : []
        } catch (e) {
          console.warn('Erreur parsing activity_types:', e)
          selectedActivities.value = []
        }
      }
      
      // Si c'est un nouveau profil (needs_setup), afficher un message informatif
      if (club.needs_setup) {
        console.log('🆕 Nouveau profil club détecté - configuration initiale requise')
        toast.info('Bienvenue ! Configurez votre profil club ci-dessous.', 'Configuration initiale')
      }
      
      // Charger les disciplines sélectionnées (avec parsing JSON si nécessaire)
      if (club.disciplines) {
        try {
          const disciplines = typeof club.disciplines === 'string' 
            ? JSON.parse(club.disciplines) 
            : club.disciplines
          selectedDisciplines.value = Array.isArray(disciplines) 
            ? disciplines.map(discipline => typeof discipline === 'object' ? discipline.id : discipline)
            : []
        } catch (e) {
          console.warn('Erreur parsing disciplines:', e)
          selectedDisciplines.value = []
        }
      }

      // Charger les paramètres des disciplines (avec parsing JSON si nécessaire)
      if (club.discipline_settings) {
        try {
          const settings = typeof club.discipline_settings === 'string' 
            ? JSON.parse(club.discipline_settings) 
            : club.discipline_settings
          disciplineSettings.value = settings || {}
        } catch (e) {
          console.warn('Erreur parsing discipline_settings:', e)
          disciplineSettings.value = {}
        }
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement du profil:', error)
  }
}

// Charger les activités disponibles
const loadActivities = async () => {
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/activity-types`)
    availableActivities.value = response.data || []
  } catch (error) {
    console.error('Erreur lors du chargement des activités:', error)
  }
}

// Charger les disciplines disponibles
const loadDisciplines = async () => {
  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/disciplines`)
    availableDisciplines.value = response.data || []
  } catch (error) {
    console.error('Erreur lors du chargement des disciplines:', error)
  }
}

// Méthodes utilitaires
const getActivityById = (id) => {
  return availableActivities.value.find(activity => activity.id === id)
}

const getDisciplineById = (id) => {
  return availableDisciplines.value.find(discipline => discipline.id === id)
}

const getDisciplinesByActivity = (activityId) => {
  return availableDisciplines.value.filter(discipline => discipline.activity_type_id === activityId)
}

const getCustomSpecialtiesByActivity = (activityId) => {
  // Affiche toutes les spécialités, actives ou non
  return customSpecialties.value.filter(specialty => specialty.activity_type_id === activityId)
}

// Initialiser les paramètres pour une nouvelle discipline
const initializeDisciplineSettings = (disciplineId) => {
  if (!disciplineSettings.value[disciplineId]) {
    const discipline = getDisciplineById(disciplineId)
    disciplineSettings.value[disciplineId] = {
      duration: discipline?.duration_minutes || 45, // Durée par défaut
      price: discipline?.base_price || 25.00, // Prix par défaut
      min_participants: discipline?.min_participants || 1,
      max_participants: discipline?.max_participants || 8,
      notes: ''
    }
  }
}

// Watcher pour initialiser les paramètres des nouvelles disciplines
watch(selectedDisciplines, (newDisciplines) => {
  newDisciplines.forEach(disciplineId => {
    initializeDisciplineSettings(disciplineId)
  })
  
  // Nettoyer les paramètres des disciplines désélectionnées
  Object.keys(disciplineSettings.value).forEach(disciplineId => {
    if (!newDisciplines.includes(parseInt(disciplineId))) {
      delete disciplineSettings.value[disciplineId]
    }
  })
}, { immediate: true })

// Charger les spécialités personnalisées
const loadCustomSpecialties = async () => {
  try {
    console.log('🔄 Chargement des spécialités personnalisées...')
    
    // Utiliser $api qui inclut automatiquement le token via l'intercepteur
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/custom-specialties')
    
    console.log('✅ Spécialités reçues:', response)
    
    if (response.data.success) {
      customSpecialties.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement des spécialités personnalisées:', error)
  }
}

// Désactiver une spécialité personnalisée
const toggleCustomSpecialty = async (specialtyId) => {
  try {
    const specialty = customSpecialties.value.find(s => s.id === specialtyId)
    if (!specialty) return

    console.log('🔄 Basculement spécialité:', specialtyId)
    
    // Utiliser $api qui inclut automatiquement le token via l'intercepteur
    const { $api } = useNuxtApp()
    await $api.patch(`/club/custom-specialty/${specialtyId}/toggle`)

    // Mettre à jour localement
    specialty.is_active = !specialty.is_active
    
    toast.success(
      specialty.is_active ? 'Spécialité activée' : 'Spécialité désactivée', 
      'Modification réussie'
    )
  } catch (error) {
    console.error('Erreur lors de la modification de la spécialité:', error)
    toast.error('Erreur lors de la modification', 'Échec')
  }
}

// Actions
const updateClub = async () => {
  loading.value = true
  try {
    console.log('🔄 Mise à jour du profil club...')
    
    const updateData = {
      ...form.value,
      activity_types: selectedActivities.value,
      disciplines: selectedDisciplines.value,
      discipline_settings: disciplineSettings.value
    }
    
    // Utiliser $api qui inclut automatiquement le token via l'intercepteur
    const { $api } = useNuxtApp()
    await $api.put('/club/profile', updateData)
    
    console.log('✅ Profil club mis à jour avec succès')
    
    // Afficher le message de succès
    toast.success('Profil du club mis à jour avec succès', 'Sauvegarde réussie')
    
    // Rediriger vers le dashboard après un court délai
    setTimeout(async () => {
      await navigateTo('/club/dashboard')
    }, 1500)
  } catch (error) {
    console.error('Erreur lors de la mise à jour du club:', error)
    toast.error('Erreur lors de la mise à jour du profil', 'Échec de la sauvegarde')
  } finally {
    loading.value = false
  }
}

const cancelEdit = () => {
  navigateTo('/club/dashboard')
}

const handleAddSpecialtySuccess = (newSpecialty) => {
  loadCustomSpecialties()
  showAddSpecialtyForm.value = false
}

const handleEditSpecialtySuccess = (updatedSpecialty) => {
  loadCustomSpecialties()
  editingSpecialtyId.value = null
}

// Initialisation
onMounted(async () => {
  await Promise.all([
    loadClubData(),
    loadActivities(),
    loadDisciplines(),
    loadCustomSpecialties()
  ])
})

useHead({
  title: 'Profil du Club | activibe',
  meta: [
    { name: 'description', content: 'Gérez les informations et activités de votre club sur activibe' }
  ]
})
</script>
