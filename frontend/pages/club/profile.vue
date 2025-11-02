<template>
  <div class="min-h-screen bg-gray-50 p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6 md:mb-8 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-lg p-4 md:p-6 border-2 border-blue-100">
        <div class="flex items-center justify-between">
          <div class="flex-1 min-w-0">
            <h1 class="text-xl md:text-3xl font-bold text-gray-900 break-words">{{ formData.name || 'Profil du Club' }}</h1>
            <p v-if="formData.company_number" class="mt-1 text-xs md:text-sm font-medium text-blue-600">
              N° Entreprise : {{ formData.company_number }}
            </p>
            <p class="mt-1 md:mt-2 text-xs md:text-base text-gray-600">Gérez les informations et activités de votre club</p>
          </div>
          <div class="p-2 md:p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-md flex-shrink-0 ml-4">
            <svg class="w-6 h-6 md:w-8 md:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex items-center justify-center py-20">
        <div class="text-center">
          <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-600">Chargement des données...</p>
        </div>
      </div>

      <!-- Form -->
      <form v-else @submit.prevent="handleSubmit" class="bg-white shadow-lg rounded-lg p-4 md:p-6 space-y-6 md:space-y-8">
        <!-- Informations générales -->
        <section class="border-b pb-4 md:pb-6">
          <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-3 md:mb-4">Informations générales</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nom du club *</label>
              <input v-model="formData.name" type="text" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Numéro d'entreprise *</label>
                <input v-model="formData.company_number" type="text" required placeholder="BE 0123.456.789 ou FR 12 345 678 901"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                <p class="mt-1 text-xs text-gray-500">SIREN, SIRET, TVA intracommunautaire, etc.</p>
              </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
              <input v-model="formData.email" type="email" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
              <input v-model="formData.phone" type="tel"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Site web</label>
              <input v-model="formData.website" type="url"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="formData.description" rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"></textarea>
          </div>
        </section>

        <!-- Adresse -->
        <section class="border-b pb-4 md:pb-6">
          <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-3 md:mb-4">Adresse</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
              <input v-model="formData.address" type="text"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
              <input v-model="formData.postal_code" type="text"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
              <input v-model="formData.city" type="text"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
              <input v-model="formData.country" type="text"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>
        </section>

        <!-- Informations légales (pour la lettre de volontariat) -->
        <section class="border-b pb-4 md:pb-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 md:mb-4 gap-2">
            <div>
              <h2 class="text-lg md:text-xl font-semibold text-gray-900">Informations Légales</h2>
              <p class="text-sm text-gray-600 mt-1">Pour la génération de la lettre de volontariat (enseignants)</p>
            </div>
            <div class="p-2 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>

          <!-- Représentant légal -->
          <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-800 mb-3">Représentant légal</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom du représentant *</label>
                <input v-model="formData.legal_representative_name" type="text" placeholder="Jean Dupont"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fonction *</label>
                <input v-model="formData.legal_representative_role" type="text" placeholder="Président"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
              </div>
            </div>
          </div>

          <!-- Assurance RC (obligatoire) -->
          <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-800 mb-3">Assurance Responsabilité Civile (obligatoire)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Compagnie d'assurance *</label>
                <input v-model="formData.insurance_rc_company" type="text" required placeholder="AXA Belgium"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de police *</label>
                <input v-model="formData.insurance_rc_policy_number" type="text" required placeholder="123456789"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
              </div>
            </div>
          </div>

          <!-- Assurance complémentaire (optionnelle) -->
          <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-800 mb-3">Assurance complémentaire (optionnelle)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Compagnie d'assurance</label>
                <input v-model="formData.insurance_additional_company" type="text" placeholder="Ethias"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de police</label>
                <input v-model="formData.insurance_additional_policy_number" type="text" placeholder="987654321"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Détails de la couverture *</label>
              <textarea v-model="formData.insurance_additional_details" rows="2" required
                        placeholder="Couverture corporelle, accidents sur le trajet, etc."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
          </div>

          <!-- Régime de défraiement -->
          <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-800 mb-3">Régime de défraiement des volontaires</h3>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Type de défraiement *</label>
              <div class="space-y-2">
                <label class="flex items-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer"
                       :class="formData.expense_reimbursement_type === 'forfait' ? 'bg-blue-50 border-blue-500' : ''">
                  <input type="radio" value="forfait" v-model="formData.expense_reimbursement_type"
                         class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                  <div class="ml-3">
                    <span class="font-medium text-gray-900">Défraiement forfaitaire</span>
                    <p class="text-sm text-gray-600">Indemnité fixe sans justificatifs</p>
                  </div>
                </label>
                <label class="flex items-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer"
                       :class="formData.expense_reimbursement_type === 'reel' ? 'bg-blue-50 border-blue-500' : ''">
                  <input type="radio" value="reel" v-model="formData.expense_reimbursement_type"
                         class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                  <div class="ml-3">
                    <span class="font-medium text-gray-900">Remboursement des frais réels</span>
                    <p class="text-sm text-gray-600">Sur présentation des justificatifs</p>
                  </div>
                </label>
                <label class="flex items-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer"
                       :class="formData.expense_reimbursement_type === 'aucun' ? 'bg-blue-50 border-blue-500' : ''">
                  <input type="radio" value="aucun" v-model="formData.expense_reimbursement_type"
                         class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                  <div class="ml-3">
                    <span class="font-medium text-gray-900">Aucun défraiement</span>
                    <p class="text-sm text-gray-600">Pas de remboursement prévu</p>
                  </div>
                </label>
              </div>
            </div>
            <div v-if="formData.expense_reimbursement_type && formData.expense_reimbursement_type !== 'aucun'">
              <label class="block text-sm font-medium text-gray-700 mb-1">Détails du défraiement *</label>
              <textarea v-model="formData.expense_reimbursement_details" rows="4" required
                        :placeholder="formData.expense_reimbursement_type === 'forfait' 
                          ? 'Ex: Indemnité journalière de 15€ par jour de formation, indemnité mensuelle de 50€, etc.' 
                          : 'Ex: Transports en commun sur base du ticket, indemnité kilométrique au taux légal de 0.4182€/km, etc.'"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"></textarea>
              <p class="mt-1 text-xs text-gray-500">
                Ces informations seront intégrées dans la lettre de volontariat
              </p>
            </div>
          </div>
        </section>

        <!-- Activités, Disciplines et Configuration des cours -->
        <section class="border-b pb-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Activités, Disciplines et Cours</h2>
          <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm font-medium text-blue-900 mb-2">📚 Structure hiérarchique :</p>
            <ul class="text-xs text-blue-800 space-y-1 ml-4 list-disc">
              <li><strong>Activité</strong> : Catégorie générale (ex: Équitation, Natation, Fitness)</li>
              <li><strong>Discipline</strong> : Spécialité dans une activité (ex: Dressage, CSO pour Équitation ; Cours individuel enfant pour Natation)</li>
              <li><strong>Configuration</strong> : Paramètres par discipline (durée, prix, participants min/max)</li>
            </ul>
            <p class="text-xs text-blue-700 mt-2">
              💡 Les <strong>Types de cours</strong> (Cours particulier, Cours collectif) sont gérés séparément dans la configuration des créneaux.
            </p>
          </div>
          <p class="text-sm text-gray-600 mb-4">
            Sélectionnez les activités proposées, puis les disciplines et configurez leurs tarifs
          </p>
          
          <!-- Structure en arbre -->
          <div class="space-y-3">
            <div v-for="activity in activities" :key="activity.id" 
                 class="border border-gray-200 rounded-lg overflow-hidden">
              
              <!-- Niveau 1: Activité -->
              <label class="flex items-center p-4 bg-white hover:bg-gray-50 cursor-pointer transition-colors"
                     :class="selectedActivityIds.includes(activity.id) ? 'bg-blue-50' : ''">
                <input type="checkbox" :value="activity.id" v-model="selectedActivityIds"
                       class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
                <span class="ml-3 text-lg font-semibold text-gray-900">
                  {{ activity.name }}
                </span>
                <span v-if="selectedActivityIds.includes(activity.id)" 
                      class="ml-auto text-sm text-blue-600 font-medium">
                  {{ getDisciplinesByActivityId(activity.id).filter(d => selectedDisciplineIds.includes(d.id)).length }} 
                  discipline(s) sélectionnée(s)
                </span>
              </label>

              <!-- Niveau 2: Disciplines (affichées si l'activité est sélectionnée) -->
              <div v-if="selectedActivityIds.includes(activity.id)" 
                   class="bg-gray-50 border-t border-gray-200">
                
                <div v-for="discipline in getDisciplinesByActivityId(activity.id)" 
                     :key="discipline.id" 
                     class="border-b border-gray-200 last:border-b-0">
                  
                  <!-- Niveau 2: Checkbox discipline -->
                  <label class="flex items-center p-3 pl-12 hover:bg-gray-100 cursor-pointer transition-colors"
                         :class="selectedDisciplineIds.includes(discipline.id) ? 'bg-blue-50' : ''">
                    <div class="flex items-center mr-3 text-gray-400">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                      </svg>
                    </div>
                    <input type="checkbox" :value="discipline.id" v-model="selectedDisciplineIds"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
                    <span class="ml-2 font-medium text-gray-900">{{ discipline.name }}</span>
                    <span v-if="selectedDisciplineIds.includes(discipline.id)" 
                          class="ml-auto text-sm text-gray-600">
                      {{ getSettings(discipline.id).duration }}min · {{ getSettings(discipline.id).price }}€
                    </span>
                  </label>

                  <!-- Niveau 3: Configuration du cours (affichée si la discipline est sélectionnée) -->
                  <div v-if="selectedDisciplineIds.includes(discipline.id)" 
                       class="bg-white p-4 pl-20 border-t border-gray-100">
                    
                    <div class="mb-3">
                      <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        Configuration du cours
                      </h5>
                    </div>

                    <!-- Message informatif si les settings sont nouveaux -->
                    <div v-if="!settings[discipline.id]" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md text-sm text-blue-800">
                      <p>💡 Les paramètres par défaut ont été initialisés. Vous pouvez les modifier ci-dessous.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Durée</label>
                        <select v-model.number="getSettings(discipline.id).duration"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                          <!-- Paliers de 5 minutes de 15 à 60 min -->
                          <option :value="15">15 minutes</option>
                          <option :value="20">20 minutes</option>
                          <option :value="25">25 minutes</option>
                          <option :value="30">30 minutes</option>
                          <option :value="35">35 minutes</option>
                          <option :value="40">40 minutes</option>
                          <option :value="45">45 minutes</option>
                          <option :value="50">50 minutes</option>
                          <option :value="55">55 minutes</option>
                          <option :value="60">1 heure (60 min)</option>
                        </select>
                      </div>
                      
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prix (€)</label>
                        <input v-model.number="getSettings(discipline.id).price" 
                               type="number" step="0.01" min="0"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                      </div>
                      
                      <div class="flex items-end">
                        <div class="w-full text-sm bg-blue-50 border border-blue-200 rounded-md p-3">
                          <div class="text-xs font-medium text-gray-600">Prix/heure</div>
                          <div class="text-xl font-bold text-blue-600">
                            {{ calculatePricePerHour(discipline.id) }}€
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                          Participants (min - max)
                        </label>
                        <div class="flex space-x-2">
                          <input v-model.number="getSettings(discipline.id).min_participants" 
                                 type="number" min="1"
                                 placeholder="Min"
                                 class="w-1/2 px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                          <input v-model.number="getSettings(discipline.id).max_participants" 
                                 type="number" min="1"
                                 placeholder="Max"
                                 class="w-1/2 px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                        </div>
                      </div>
                      
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                          Notes (optionnel)
                        </label>
                        <input v-model="getSettings(discipline.id).notes" 
                               type="text"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                               placeholder="Matériel fourni, niveau requis..." />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Message si aucune discipline disponible pour cette activité -->
                <div v-if="getDisciplinesByActivityId(activity.id).length === 0"
                     class="p-4 pl-12 text-sm text-gray-500 italic">
                  Aucune discipline disponible pour cette activité
                </div>
                
                <!-- Message si disciplines disponibles mais aucune sélectionnée -->
                <div v-else-if="getDisciplinesByActivityId(activity.id).filter(d => selectedDisciplineIds.includes(d.id)).length === 0"
                     class="p-4 pl-12 text-sm text-blue-600 italic">
                  Sélectionnez une ou plusieurs disciplines ci-dessus
                </div>
              </div>
            </div>

            <!-- Message si aucune activité disponible -->
            <div v-if="activities.length === 0" class="text-center py-8 text-gray-500">
              <p>Aucune activité disponible</p>
            </div>
          </div>

          <!-- Résumé -->
          <div v-if="selectedActivityIds.length > 0" class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start">
              <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <div class="flex-1 text-sm">
                <p class="font-medium text-gray-900 mb-1">Résumé de votre configuration</p>
                <p class="text-gray-700">
                  <strong>{{ selectedActivityIds.length }}</strong> activité(s) · 
                  <strong>{{ selectedDisciplineIds.length }}</strong> discipline(s) · 
                  <strong>{{ Object.keys(settings).length }}</strong> cours configuré(s)
                </p>
              </div>
            </div>
          </div>
        </section>

        <!-- Valeurs par défaut des abonnements -->
        <section class="pb-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Valeurs par défaut des abonnements</h2>
          <p class="text-sm text-gray-600 mb-4">Ces valeurs seront utilisées par défaut lors de la création d'un nouveau modèle d'abonnement.</p>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de cours par défaut</label>
              <input 
                v-model.number="formData.default_subscription_total_lessons"
                type="number" 
                min="1"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Cours gratuits offerts par défaut</label>
              <input 
                v-model.number="formData.default_subscription_free_lessons"
                type="number" 
                min="0"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Prix par défaut (€)</label>
              <input 
                v-model.number="formData.default_subscription_price"
                type="number" 
                step="0.01"
                min="0"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
              />
            </div>
            
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Durée de validité par défaut</label>
              <div class="flex gap-3">
                <div class="flex-1">
                  <input 
                    v-model.number="formData.default_subscription_validity_value"
                    type="number" 
                    min="1"
                    :max="formData.default_subscription_validity_unit === 'weeks' ? 260 : 60"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div class="w-40">
                  <select 
                    v-model="formData.default_subscription_validity_unit"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="weeks">Semaines</option>
                    <option value="months">Mois</option>
                  </select>
                </div>
              </div>
              <p class="text-xs text-gray-500 mt-1">
                <span v-if="formData.default_subscription_validity_unit === 'weeks'">
                  {{ formData.default_subscription_validity_value }} semaine{{ formData.default_subscription_validity_value > 1 ? 's' : '' }} = {{ (formData.default_subscription_validity_value / 4.33).toFixed(1) }} mois
                </span>
                <span v-else>
                  {{ formData.default_subscription_validity_value }} mois
                </span>
              </p>
            </div>
          </div>
        </section>

        <!-- Statut -->
        <section class="pb-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Paramètres</h2>
          <label class="flex items-center">
            <input v-model="formData.is_active" type="checkbox"
                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
            <span class="ml-2 text-sm text-gray-700">Club actif (visible sur la plateforme)</span>
          </label>
        </section>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 sm:gap-4 pt-4 md:pt-6 border-t">
          <button type="button" @click="goBack"
                  class="w-full sm:w-auto px-4 md:px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 text-sm md:text-base">
            Annuler
          </button>
          <button type="submit" :disabled="isSaving"
                  class="w-full sm:w-auto px-4 md:px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 min-w-[150px] text-sm md:text-base">
            <span v-if="!isSaving">Enregistrer</span>
            <span v-else class="flex items-center justify-center">
              <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Sauvegarde...
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch, onMounted } from 'vue'
import { useToast } from '~/composables/useToast'

// ============================================================================
// STATE
// ============================================================================
const toast = useToast()
const isLoading = ref(true)
const isSaving = ref(false)
const isInitialLoad = ref(true) // Pour éviter que le watcher n'interfère au chargement

// Données brutes de l'API
const activities = ref([])
const disciplines = ref([])

// Données du formulaire
const formData = reactive({
  name: '',
  company_number: '',
  email: '',
  phone: '',
  website: '',
  description: '',
  address: '',
  city: '',
  postal_code: '',
  country: '',
  is_active: true,
  // Informations légales
  legal_representative_name: '',
  legal_representative_role: '',
  insurance_rc_company: '',
  insurance_rc_policy_number: '',
  insurance_additional_company: '',
  insurance_additional_policy_number: '',
  insurance_additional_details: '',
  expense_reimbursement_type: 'aucun',
  expense_reimbursement_details: '',
  // Valeurs par défaut des abonnements
  default_subscription_total_lessons: 10,
  default_subscription_free_lessons: 1,
  default_subscription_price: 180,
  default_subscription_validity_value: 12,
  default_subscription_validity_unit: 'weeks'
})

// IDs sélectionnés (la source de vérité)
const selectedActivityIds = ref([])
const selectedDisciplineIds = ref([])

// Settings par discipline (clé = ID numérique)
const settings = ref({})

// ============================================================================
// COMPUTED
// ============================================================================
const getActivityName = (id) => {
  return activities.value.find(a => a.id === id)?.name || ''
}

const getDisciplineName = (id) => {
  return disciplines.value.find(d => d.id === id)?.name || ''
}

const getDisciplinesByActivityId = (activityId) => {
  const filtered = disciplines.value.filter(d => d.activity_type_id === activityId)
  if (filtered.length === 0 && disciplines.value.length > 0) {
    console.log(`🔍 Aucune discipline pour activité ${activityId}:`, {
      totalDisciplines: disciplines.value.length,
      disciplinesForActivity: filtered,
      allDisciplines: disciplines.value.map(d => ({ id: d.id, name: d.name, activity_type_id: d.activity_type_id }))
    })
  }
  return filtered
}

// Log pour debug de l'affichage
watch([activities, selectedActivityIds], ([newActivities, newSelectedIds]) => {
  if (newActivities.length > 0 && !isLoading.value) {
    console.log('🎨 RENDU: État des activités')
    console.log('  - Activités disponibles:', newActivities.map(a => `${a.id}: ${a.name}`))
    console.log('  - selectedActivityIds:', newSelectedIds)
    console.log('  - Activités qui devraient être cochées:', newSelectedIds.map(id => {
      const act = newActivities.find(a => a.id === id)
      return act ? `${id}: ${act.name}` : `${id}: INTROUVABLE`
    }))
  }
}, { immediate: true })

const calculatePricePerHour = (disciplineId) => {
  const s = getSettings(disciplineId)
  if (!s || !s.duration || !s.price) return '0.00'
  return ((s.price / s.duration) * 60).toFixed(2)
}

// Fonction helper pour obtenir les settings d'une discipline, en créant des valeurs par défaut si nécessaire
const getSettings = (disciplineId) => {
  if (!settings.value[disciplineId]) {
    // Créer des settings par défaut si ils n'existent pas
    settings.value[disciplineId] = {
      duration: 45,
      price: 25.00,
      min_participants: 1,
      max_participants: 8,
      notes: ''
    }
  }
  return settings.value[disciplineId]
}

// ============================================================================
// WATCHERS - Gestion automatique des settings
// ============================================================================
watch(selectedDisciplineIds, (newIds, oldIds) => {
  // Ne rien faire pendant le chargement initial pour éviter d'écraser les données du serveur
  if (isInitialLoad.value) {
    return
  }
  
  // Ajouter les settings pour les nouvelles disciplines
  newIds.forEach(id => {
    if (!settings.value[id]) {
      settings.value[id] = {
        duration: 45,
        price: 25.00,
        min_participants: 1,
        max_participants: 8,
        notes: ''
      }
    }
  })
  
  // Supprimer les settings des disciplines désélectionnées
  if (oldIds) {
    oldIds.forEach(id => {
      if (!newIds.includes(id)) {
        delete settings.value[id]
      }
    })
  }
}, { deep: true })

// ============================================================================
// FONCTIONS DE CHARGEMENT
// ============================================================================
async function loadData() {
  try {
    isLoading.value = true
    const { $api } = useNuxtApp()
    const config = useRuntimeConfig()
    
    // 1. Charger les référentiels (pas de token nécessaire)
    try {
      const [activitiesRes, disciplinesRes] = await Promise.all([
        $fetch(`${config.public.apiBase}/activity-types`),
        $fetch(`${config.public.apiBase}/disciplines`)
      ])
      
      // Gérer les différents formats de réponse API
      activities.value = activitiesRes?.data || activitiesRes || []
      disciplines.value = disciplinesRes?.data || disciplinesRes || []
      
      console.log('✅ Référentiels chargés:', activities.value.length, 'activités,', disciplines.value.length, 'disciplines')
      console.log('📋 Format réponse activités:', {
        hasSuccess: !!activitiesRes?.success,
        hasData: !!activitiesRes?.data,
        directArray: Array.isArray(activitiesRes),
        keys: Object.keys(activitiesRes || {})
      })
      console.log('📋 Format réponse disciplines:', {
        hasSuccess: !!disciplinesRes?.success,
        hasData: !!disciplinesRes?.data,
        directArray: Array.isArray(disciplinesRes),
        keys: Object.keys(disciplinesRes || {}),
        disciplinesCount: disciplinesRes?.data?.length || disciplinesRes?.length || 0
      })
      
      if (disciplines.value.length === 0 && disciplinesRes) {
        console.error('⚠️ Aucune discipline chargée mais réponse API existe:', disciplinesRes)
      }
    } catch (error) {
      console.error('❌ Erreur lors du chargement des référentiels:', error)
      toast.error('Erreur lors du chargement des activités et disciplines')
      // Continuer quand même avec des tableaux vides
      activities.value = []
      disciplines.value = []
    }
    
    // 2. Charger le profil du club
    const profileRes = await $api.get('/club/profile')
    
    if (profileRes.data.success && profileRes.data.data) {
      const club = profileRes.data.data
      console.log('✅ Profil club reçu:', club)
      
      // Remplir le formulaire
      formData.name = club.name || ''
      formData.company_number = club.company_number || ''
      formData.email = club.email || ''
      formData.phone = club.phone || ''
      formData.website = club.website || ''
      formData.description = club.description || ''
      formData.address = club.address || ''
      formData.city = club.city || ''
      formData.postal_code = club.postal_code || ''
      formData.country = club.country || ''
      formData.is_active = club.is_active !== false
      
      // Informations légales
      formData.legal_representative_name = club.legal_representative_name || ''
      formData.legal_representative_role = club.legal_representative_role || ''
      formData.insurance_rc_company = club.insurance_rc_company || ''
      formData.insurance_rc_policy_number = club.insurance_rc_policy_number || ''
      formData.insurance_additional_company = club.insurance_additional_company || ''
      formData.insurance_additional_policy_number = club.insurance_additional_policy_number || ''
      formData.insurance_additional_details = club.insurance_additional_details || ''
      formData.expense_reimbursement_type = club.expense_reimbursement_type || 'aucun'
      formData.expense_reimbursement_details = club.expense_reimbursement_details || ''
      
      // Valeurs par défaut des abonnements
      formData.default_subscription_total_lessons = club.default_subscription_total_lessons ?? 10
      formData.default_subscription_free_lessons = club.default_subscription_free_lessons ?? 1
      formData.default_subscription_price = club.default_subscription_price ?? 180
      formData.default_subscription_validity_value = club.default_subscription_validity_value ?? 12
      formData.default_subscription_validity_unit = club.default_subscription_validity_unit || 'weeks'
      
      // Si c'est un nouveau profil (needs_setup), afficher un message informatif
      if (club.needs_setup) {
        console.log('🆕 Nouveau profil club détecté - configuration initiale requise')
        toast.info('Bienvenue ! Configurez votre profil club ci-dessous.', 'Configuration initiale')
      }
      
      // Charger les disciplines sélectionnées (avec parsing JSON si nécessaire)
      console.log('🔍 ÉTAPE 1: Traitement des disciplines')
      console.log('  - club.disciplines présent:', !!club.disciplines)
      console.log('  - Type:', typeof club.disciplines)
      console.log('  - Valeur brute:', club.disciplines)
      
      if (club.disciplines) {
        const disciplineData = typeof club.disciplines === 'string' 
          ? JSON.parse(club.disciplines) 
          : club.disciplines
        
        console.log('📋 Disciplines après parsing:', disciplineData)
        console.log('  - Est un tableau?', Array.isArray(disciplineData))
        console.log('  - Longueur:', disciplineData?.length)
        
        if (Array.isArray(disciplineData)) {
          selectedDisciplineIds.value = disciplineData
            .map(item => {
              console.log('  - Traitement item:', item, 'Type:', typeof item)
              if (typeof item === 'number') {
                console.log('    → ID numérique:', item)
                return item // Déjà un ID
              }
              if (typeof item === 'object' && item.id) {
                console.log('    → Objet avec ID:', item.id)
                return item.id // Objet avec ID
              }
              
              // Nom de discipline → chercher l'ID avec matching flexible
              if (typeof item === 'string') {
                // Normaliser les chaînes pour la comparaison (retirer accents, casse, espaces)
                const normalizeString = (str) => {
                  return str.toLowerCase()
                    .trim()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '') // Retirer accents
                    .replace(/\s+/g, ' ') // Normaliser espaces
                }
                
                const normalizedItem = normalizeString(item)
                const found = disciplines.value.find(d => {
                  const normalizedName = normalizeString(d.name)
                  return normalizedName === normalizedItem
                })
                
                if (found) {
                  console.log(`  ✓ "${item}" → ID ${found.id} (${found.name})`)
                  return found.id
                }
                
                // Essai avec matching partiel (si le nom contient le terme ou vice versa)
                const partialMatch = disciplines.value.find(d => {
                  const normalizedName = normalizeString(d.name)
                  return normalizedName.includes(normalizedItem) || normalizedItem.includes(normalizedName)
                })
                
                if (partialMatch) {
                  console.log(`  ✓ "${item}" → ID ${partialMatch.id} (match partiel avec "${partialMatch.name}")`)
                  return partialMatch.id
                }
                
                console.warn(`  ⚠️ Discipline "${item}" introuvable dans ${disciplines.value.length} disciplines disponibles`)
                console.warn(`     Disciplines disponibles:`, disciplines.value.map(d => d.name).join(', '))
              }
              return null
            })
            .filter(id => id !== null)
        }
        
        console.log('✅ selectedDisciplineIds.value après conversion:', selectedDisciplineIds.value)
        console.log('  - Type:', typeof selectedDisciplineIds.value)
        console.log('  - Valeurs:', JSON.stringify(selectedDisciplineIds.value))
      } else {
        console.warn('⚠️ Aucune discipline dans le profil club')
      }
      
      // Déduire les activités depuis les disciplines sélectionnées
      // (car le backend ne renvoie pas toujours activity_types)
      console.log('🔍 ÉTAPE 2: Déduction des activités depuis les disciplines')
      console.log('  - selectedDisciplineIds.value.length:', selectedDisciplineIds.value.length)
      console.log('  - selectedDisciplineIds.value:', selectedDisciplineIds.value)
      console.log('  - disciplines.value disponibles:', disciplines.value.length)
      
      if (selectedDisciplineIds.value.length > 0) {
        const uniqueActivityIds = new Set()
        selectedDisciplineIds.value.forEach(disciplineId => {
          console.log(`  - Recherche discipline ID ${disciplineId}`)
          const discipline = disciplines.value.find(d => d.id === disciplineId)
          console.log(`    → Trouvée:`, discipline)
          if (discipline && discipline.activity_type_id) {
            console.log(`    → activity_type_id: ${discipline.activity_type_id}`)
            uniqueActivityIds.add(discipline.activity_type_id)
          } else {
            console.warn(`    ⚠️ Pas d'activity_type_id pour discipline ${disciplineId}`)
          }
        })
        
        console.log('  - uniqueActivityIds Set:', uniqueActivityIds)
        selectedActivityIds.value = Array.from(uniqueActivityIds)
        console.log('✅ selectedActivityIds.value après déduction:', selectedActivityIds.value)
        console.log('  - Type:', typeof selectedActivityIds.value)
        console.log('  - Valeurs:', JSON.stringify(selectedActivityIds.value))
      } else {
        console.warn('⚠️ Aucune discipline sélectionnée pour déduire les activités')
      }
      
      // Si activity_types est fourni explicitement, l'utiliser (prioritaire)
      if (club.activity_types) {
        const activityData = typeof club.activity_types === 'string' 
          ? JSON.parse(club.activity_types) 
          : club.activity_types
        const explicitActivityIds = Array.isArray(activityData) 
          ? activityData.map(a => typeof a === 'object' ? a.id : a)
          : []
        if (explicitActivityIds.length > 0) {
          selectedActivityIds.value = explicitActivityIds
          console.log('✅ Activités explicites utilisées:', selectedActivityIds.value)
        }
      }
      
      // Traiter les settings - Gère les IDs numériques ET les noms
      console.log('🔍 ÉTAPE 3: Traitement des settings')
      console.log('  - club.discipline_settings présent:', !!club.discipline_settings)
      console.log('  - Type:', typeof club.discipline_settings)
      
      if (club.discipline_settings) {
        const settingsData = typeof club.discipline_settings === 'string' 
          ? JSON.parse(club.discipline_settings) 
          : club.discipline_settings
        
        console.log('📋 Settings après parsing:', settingsData)
        console.log('  - Type:', typeof settingsData)
        console.log('  - Clés:', Object.keys(settingsData))
        
        if (typeof settingsData === 'object') {
          Object.entries(settingsData).forEach(([key, value]) => {
            console.log(`  - Traitement clé "${key}":`, value)
            let disciplineId = null
            
            // Cas 1: La clé est déjà un ID numérique (ex: "11", "12")
            const numericKey = parseInt(key)
            console.log(`    → numericKey: ${numericKey}, isNaN: ${isNaN(numericKey)}`)
            
            if (!isNaN(numericKey)) {
              const foundDiscipline = disciplines.value.find(d => d.id === numericKey)
              console.log(`    → Discipline trouvée pour ID ${numericKey}:`, foundDiscipline)
              
              if (foundDiscipline) {
                disciplineId = numericKey
                console.log(`  ✓ Settings ID ${key} (déjà numérique)`)
              }
            }
            
            // Cas 2: La clé est un nom de discipline (ex: "Dressage", "dressage")
            if (disciplineId === null) {
              // Normaliser les chaînes pour la comparaison (retirer accents, casse, espaces)
              const normalizeString = (str) => {
                return str.toLowerCase()
                  .trim()
                  .normalize('NFD')
                  .replace(/[\u0300-\u036f]/g, '') // Retirer accents
                  .replace(/\s+/g, ' ') // Normaliser espaces
              }
              
              const normalizedKey = normalizeString(key)
              const found = disciplines.value.find(d => {
                const normalizedName = normalizeString(d.name)
                return normalizedName === normalizedKey
              })
              
              if (found) {
                disciplineId = found.id
                console.log(`  ✓ Settings "${key}" → ID ${found.id} (${found.name})`)
              } else {
                // Essai avec matching partiel
                const partialMatch = disciplines.value.find(d => {
                  const normalizedName = normalizeString(d.name)
                  return normalizedName.includes(normalizedKey) || normalizedKey.includes(normalizedName)
                })
                
                if (partialMatch) {
                  disciplineId = partialMatch.id
                  console.log(`  ✓ Settings "${key}" → ID ${partialMatch.id} (match partiel avec "${partialMatch.name}")`)
                } else {
                  console.warn(`  ⚠️ Settings pour "${key}" : discipline introuvable parmi ${disciplines.value.length} disciplines`)
                }
              }
            }
            
            // Stocker les settings si la discipline est valide
            // On stocke même si elle n'est pas encore dans selectedDisciplineIds car elle pourrait l'être
            console.log(`    → disciplineId final: ${disciplineId}`)
            console.log(`    → Est dans selectedDisciplineIds?`, selectedDisciplineIds.value.includes(disciplineId))
            
            if (disciplineId) {
              const settingToStore = {
                duration: value.duration || 45,
                price: value.price || 25.00,
                min_participants: value.min_participants || 1,
                max_participants: value.max_participants || 8,
                notes: value.notes || ''
              }
              console.log(`    → Stockage settings pour ID ${disciplineId}:`, settingToStore)
              settings.value[disciplineId] = settingToStore
            } else {
              console.warn(`    ⚠️ Settings non stockés pour ${key} (disciplineId invalide)`)
            }
          })
        }
        
        console.log('✅ settings.value après conversion:', settings.value)
        console.log('  - Clés:', Object.keys(settings.value))
      } else {
        console.warn('⚠️ Aucun discipline_settings dans le profil club')
      }
      
      // Créer des settings par défaut pour les disciplines sélectionnées qui n'en ont pas
      console.log('🔍 ÉTAPE 4: Création des settings par défaut manquants')
      selectedDisciplineIds.value.forEach(id => {
        console.log(`  - Vérification discipline ID ${id}`)
        console.log(`    → A déjà des settings?`, !!settings.value[id])
        if (!settings.value[id]) {
          settings.value[id] = {
            duration: 45,
            price: 25.00,
            min_participants: 1,
            max_participants: 8,
            notes: ''
          }
          console.log(`  ➕ Settings par défaut créés pour discipline ID ${id}`)
        }
      })
      
      // RÉSUMÉ FINAL
      console.log('═══════════════════════════════════════════════')
      console.log('📊 RÉSUMÉ FINAL DU CHARGEMENT')
      console.log('═══════════════════════════════════════════════')
      console.log('✅ Activités sélectionnées:', selectedActivityIds.value)
      console.log('   Détail:', selectedActivityIds.value.map(id => {
        const act = activities.value.find(a => a.id === id)
        return act ? `${id}: ${act.name}` : `${id}: ???`
      }))
      console.log('✅ Disciplines sélectionnées:', selectedDisciplineIds.value)
      console.log('   Détail:', selectedDisciplineIds.value.map(id => {
        const disc = disciplines.value.find(d => d.id === id)
        return disc ? `${id}: ${disc.name}` : `${id}: ???`
      }))
      console.log('✅ Settings configurés:', Object.keys(settings.value))
      console.log('   Détail:', settings.value)
      console.log('═══════════════════════════════════════════════')
    }
  } catch (error) {
    console.error('❌ Erreur chargement:', error)
    toast.error('Erreur lors du chargement des données')
  } finally {
    isLoading.value = false
    // Réactiver le watcher après le chargement initial
    isInitialLoad.value = false
  }
}

// ============================================================================
// SOUMISSION
// ============================================================================
async function handleSubmit() {
  try {
    isSaving.value = true
    const { $api } = useNuxtApp()
    
    // S'assurer que tous les champs obligatoires sont présents
    const payload = {
      ...formData,
      activity_types: selectedActivityIds.value,
      disciplines: selectedDisciplineIds.value,
      discipline_settings: settings.value
    }
    
    // Log détaillé pour vérifier les champs importants
    console.log('📤 Envoi du profil club:', {
      company_number: payload.company_number,
      legal_representative_name: payload.legal_representative_name,
      legal_representative_role: payload.legal_representative_role,
      insurance_rc_company: payload.insurance_rc_company,
      insurance_rc_policy_number: payload.insurance_rc_policy_number,
      insurance_additional_details: payload.insurance_additional_details,
      expense_reimbursement_type: payload.expense_reimbursement_type,
      expense_reimbursement_details: payload.expense_reimbursement_details,
      full_payload: payload
    })
    
    await $api.put('/club/profile', payload)
    
    toast.success('Profil mis à jour avec succès')
    
    setTimeout(() => {
      navigateTo('/club/dashboard')
    }, 1000)
  } catch (error) {
    console.error('❌ Erreur sauvegarde:', error)
    
    // Afficher un message d'erreur plus détaillé
    if (error.response?.data?.errors) {
      const errors = error.response.data.errors
      const errorMessages = Object.entries(errors).map(([field, messages]) => {
        return `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`
      }).join('\n')
      toast.error(`Erreurs de validation:\n${errorMessages}`)
    } else if (error.response?.data?.message) {
      toast.error(error.response.data.message)
    } else {
      toast.error('Erreur lors de la sauvegarde')
    }
  } finally {
    isSaving.value = false
  }
}

function goBack() {
  navigateTo('/club/dashboard')
}

// ============================================================================
// INITIALISATION
// ============================================================================
onMounted(() => {
  loadData()
})

useHead({
  title: 'Profil du Club | activibe'
})
</script>
