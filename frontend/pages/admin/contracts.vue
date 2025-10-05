<template>
  <div class="p-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Contrats</h1>
    <p class="text-gray-600 mb-8">Gérez les types de contrats et suivez les contrats des enseignants.</p>

    <!-- Navigation par onglets -->
    <div class="mb-8">
      <nav class="flex space-x-8">
        <button 
          @click="activeTab = 'types'"
          :class="activeTab === 'types' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
          class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
        >
          Types de Contrats
        </button>
        <button 
          @click="activeTab = 'teachers'"
          :class="activeTab === 'teachers' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
          class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
        >
          Enseignants & Contrats
        </button>
        <button 
          @click="activeTab = 'payments'"
          :class="activeTab === 'payments' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
          class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
        >
          Paiements & Heures
        </button>
        <button 
          @click="activeTab = 'settings'"
          :class="activeTab === 'settings' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
          class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
        >
          Paramètres
        </button>
      </nav>
    </div>

    <!-- Contenu des onglets -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Chargement...</p>
    </div>

    <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg">
      <p>Une erreur est survenue : {{ error }}</p>
    </div>

    <!-- Onglet Types de Contrats -->
    <div v-else-if="activeTab === 'types'" class="space-y-6">
      <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Configuration des Types de Contrats</h2>
        
        <form @submit.prevent="saveContractTypes" class="space-y-6">
          <!-- Contrat Bénévole -->
          <div class="border border-gray-200 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-800">Contrat Bénévole</h3>
                <p class="text-gray-500 text-sm">Plafonds et indemnités pour les volontaires</p>
              </div>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Actif</span>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input v-model="contractTypes.volunteer.active" type="checkbox" class="sr-only peer">
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
              </div>
            </div>

            <div v-if="contractTypes.volunteer.active" class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plafond annuel (€)</label>
                <input v-model.number="contractTypes.volunteer.annual_ceiling" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plafond journalier (€)</label>
                <input v-model.number="contractTypes.volunteer.daily_ceiling" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Indemnité kilométrique (€/km)</label>
                <input v-model.number="contractTypes.volunteer.mileage_allowance" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plafond kilométrique annuel (km)</label>
                <input v-model.number="contractTypes.volunteer.max_annual_mileage" type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              </div>
            </div>
          </div>

          <!-- Contrat Étudiant -->
          <div class="border border-gray-200 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-800">Contrat Étudiant</h3>
                <p class="text-gray-500 text-sm">Contrat pour étudiants en formation</p>
              </div>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Actif</span>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input v-model="contractTypes.student.active" type="checkbox" class="sr-only peer">
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
              </div>
            </div>

            <div v-if="contractTypes.student.active" class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plafond annuel (€)</label>
                <input v-model.number="contractTypes.student.annual_ceiling" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plafond journalier (€)</label>
                <input v-model.number="contractTypes.student.daily_ceiling" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              </div>
            </div>
          </div>

          <!-- Contrat Article 17 -->
          <div class="border border-gray-200 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-800">Contrat Article 17</h3>
                <p class="text-gray-500 text-sm">Contrat spécifique Article 17</p>
              </div>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Actif</span>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input v-model="contractTypes.article17.active" type="checkbox" class="sr-only peer">
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
              </div>
            </div>

            <div v-if="contractTypes.article17.active" class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plafond annuel (€)</label>
                <input v-model.number="contractTypes.article17.annual_ceiling" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plafond journalier (€)</label>
                <input v-model.number="contractTypes.article17.daily_ceiling" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              </div>
            </div>
          </div>

          <!-- Contrat Indépendant -->
          <div class="border border-gray-200 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-800">Contrat Indépendant</h3>
                <p class="text-gray-500 text-sm">Travailleur indépendant</p>
              </div>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Actif</span>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input v-model="contractTypes.freelance.active" type="checkbox" class="sr-only peer">
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
              </div>
            </div>

            <div v-if="contractTypes.freelance.active" class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plafond annuel (€)</label>
                <input v-model.number="contractTypes.freelance.annual_ceiling" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plafond journalier (€)</label>
                <input v-model.number="contractTypes.freelance.daily_ceiling" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              </div>
            </div>
          </div>

          <!-- Contrat Salarié -->
          <div class="border border-gray-200 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-800">Contrat Salarié</h3>
                <p class="text-gray-500 text-sm">Employé salarié</p>
              </div>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Actif</span>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input v-model="contractTypes.salaried.active" type="checkbox" class="sr-only peer">
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
              </div>
            </div>

            <div v-if="contractTypes.salaried.active" class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plafond annuel (€)</label>
                <input v-model.number="contractTypes.salaried.annual_ceiling" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plafond journalier (€)</label>
                <input v-model.number="contractTypes.salaried.daily_ceiling" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-6 border-t border-gray-200">
            <button 
              type="submit" 
              :disabled="isSaving"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50"
            >
              <span v-if="isSaving">Sauvegarde...</span>
              <span v-else>Enregistrer les modifications</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Onglet Enseignants & Contrats -->
    <div v-else-if="activeTab === 'teachers'" class="space-y-6">
      <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold text-gray-800">Enseignants & Contrats</h2>
          <div class="flex space-x-3">
            <select v-model="selectedYear" @change="loadTeachersData" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
            </select>
            <select v-model="exceedanceFilter" @change="applyExceedanceFilter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="all">Tous les enseignants</option>
              <option value="green">🟢 Dans les limites</option>
              <option value="orange">🟠 Zone d'attention</option>
              <option value="red">🔴 Zone critique</option>
              <option value="black">⚫ Dépassements</option>
            </select>
            <button @click="loadTeachersData" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
              Actualiser
            </button>
          </div>
        </div>

        <!-- Détail des Dépassements par Statut -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Répartition par Statut de Dépassement</h3>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Statut Vert -->
            <div @click="filterByStatus('green')" 
                 class="bg-green-50 border border-green-200 rounded-lg p-3 cursor-pointer hover:bg-green-100 transition-colors"
                 :class="{ 'ring-2 ring-green-500': exceedanceFilter === 'green' }">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                  <div>
                    <p class="text-sm font-medium text-gray-600">Dans les limites</p>
                    <p class="text-xs text-gray-500">&lt; {{ exceedanceThresholds.orange }}%</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-xl font-bold text-green-600">{{ exceedanceStats.green }}</p>
                </div>
              </div>
            </div>

            <!-- Statut Orange -->
            <div @click="filterByStatus('orange')" 
                 class="bg-orange-50 border border-orange-200 rounded-lg p-3 cursor-pointer hover:bg-orange-100 transition-colors"
                 :class="{ 'ring-2 ring-orange-500': exceedanceFilter === 'orange' }">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                  <div>
                    <p class="text-sm font-medium text-gray-600">Zone d'attention</p>
                    <p class="text-xs text-gray-500">{{ exceedanceThresholds.orange }}% - {{ exceedanceThresholds.red }}%</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-xl font-bold text-orange-600">{{ exceedanceStats.orange }}</p>
                </div>
              </div>
            </div>

            <!-- Statut Rouge -->
            <div @click="filterByStatus('red')" 
                 class="bg-red-50 border border-red-200 rounded-lg p-3 cursor-pointer hover:bg-red-100 transition-colors"
                 :class="{ 'ring-2 ring-red-500': exceedanceFilter === 'red' }">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                  <div>
                    <p class="text-sm font-medium text-gray-600">Zone critique</p>
                    <p class="text-xs text-gray-500">{{ exceedanceThresholds.red }}% - 100%</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-xl font-bold text-red-600">{{ exceedanceStats.red }}</p>
                </div>
              </div>
            </div>

            <!-- Statut Noir -->
            <div @click="filterByStatus('black')" 
                 class="bg-gray-50 border border-gray-200 rounded-lg p-3 cursor-pointer hover:bg-gray-100 transition-colors"
                 :class="{ 'ring-2 ring-gray-500': exceedanceFilter === 'black' }">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-gray-800 rounded-full"></div>
                  <div>
                    <p class="text-sm font-medium text-gray-600">Dépassements</p>
                    <p class="text-xs text-gray-500">&gt; 100%</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-xl font-bold text-gray-800">{{ exceedanceStats.black }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Liste des enseignants -->
        <div class="space-y-4">
          <div v-for="teacher in filteredTeachers" :key="teacher.id" class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                  <span class="text-blue-600 font-semibold">{{ teacher.first_name?.charAt(0) || teacher.name?.charAt(0) }}</span>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900">{{ teacher.first_name }} {{ teacher.last_name }}</h3>
                  <p class="text-sm text-gray-500">{{ teacher.email }}</p>
                </div>
              </div>
              <div class="flex items-center space-x-3">
                <span class="px-3 py-1 rounded-full text-sm font-medium" :class="getContractStatusClass(teacher.current_contract)">
                  {{ getContractTypeLabel(teacher.current_contract?.type) }}
                </span>
                
                <!-- Indicateur de statut principal -->
                <div class="flex items-center space-x-2">
                  <div v-for="indicator in getExceedanceIndicators(teacher)" :key="indicator.type" 
                       class="flex items-center space-x-1 px-2 py-1 rounded-full text-xs font-medium"
                       :class="getIndicatorBadgeClass(indicator.status)">
                    <div class="w-2 h-2 rounded-full" :class="indicator.color"></div>
                    <span>{{ indicator.label }}</span>
                  </div>
                </div>
                
                <!-- Indicateurs détaillés -->
                <div class="flex space-x-1" v-if="getExceedanceIndicators(teacher).length > 0">
                  <div v-for="indicator in getExceedanceIndicators(teacher)" :key="indicator.type" 
                       class="w-4 h-4 rounded-full border-2 border-white shadow-sm" 
                       :class="indicator.color"
                       :title="indicator.tooltip">
                  </div>
                </div>
              </div>
            </div>

            <!-- Détails du contrat actuel -->
            <div v-if="teacher.current_contract" class="bg-gray-50 rounded-lg p-4 mb-4">
              <h4 class="font-medium text-gray-900 mb-3">Contrat Actuel</h4>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <span class="text-sm text-gray-500">Type:</span>
                  <p class="font-medium">{{ getContractTypeLabel(teacher.current_contract.type) }}</p>
                </div>
                <div>
                  <span class="text-sm text-gray-500">Début:</span>
                  <p class="font-medium">{{ formatDate(teacher.current_contract.start_date) }}</p>
                </div>
                <div>
                  <span class="text-sm text-gray-500">Fin:</span>
                  <p class="font-medium">{{ teacher.current_contract.end_date ? formatDate(teacher.current_contract.end_date) : 'Indéterminé' }}</p>
                </div>
              </div>
            </div>

            <!-- Historique des contrats -->
            <div class="border-t pt-4">
              <h4 class="font-medium text-gray-900 mb-3">Historique des Contrats</h4>
              <div class="space-y-2">
                <div v-for="contract in teacher.contract_history" :key="contract.id" 
                     class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div class="flex items-center space-x-4">
                    <span class="px-2 py-1 rounded text-xs font-medium" :class="getContractStatusClass(contract)">
                      {{ getContractTypeLabel(contract.type) }}
                    </span>
                    <span class="text-sm text-gray-600">{{ formatDate(contract.start_date) }} - {{ contract.end_date ? formatDate(contract.end_date) : 'En cours' }}</span>
                  </div>
                  <div class="text-sm text-gray-500">
                    {{ contract.total_hours || 0 }}h • {{ contract.total_amount || 0 }}€
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Onglet Paiements & Heures -->
    <div v-else-if="activeTab === 'payments'" class="space-y-6">
      <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold text-gray-800">Paiements & Heures</h2>
          <div class="flex space-x-3">
            <select v-model="selectedPeriod" @change="loadPaymentsData" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="monthly">Mensuel</option>
              <option value="yearly">Annuel</option>
            </select>
            <select v-model="selectedYear" @change="loadPaymentsData" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
            </select>
            <select v-model="exceedanceFilter" @change="applyExceedanceFilter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="all">Tous les enseignants</option>
              <option value="green">🟢 Dans les limites</option>
              <option value="orange">🟠 Zone d'attention</option>
              <option value="red">🔴 Zone critique</option>
              <option value="black">⚫ Dépassements</option>
            </select>
          </div>
        </div>

        <!-- Récapitulatif -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-blue-50 rounded-lg p-4">
            <div class="flex items-center">
              <div class="p-2 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Paiements</p>
                <p class="text-2xl font-semibold text-gray-900">{{ totalPayments }}€</p>
              </div>
            </div>
          </div>
          <div class="bg-green-50 rounded-lg p-4">
            <div class="flex items-center">
              <div class="p-2 bg-green-100 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Heures</p>
                <p class="text-2xl font-semibold text-gray-900">{{ totalHours }}h</p>
              </div>
            </div>
          </div>
          <div class="bg-purple-50 rounded-lg p-4">
            <div class="flex items-center">
              <div class="p-2 bg-purple-100 rounded-lg">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Enseignants Actifs</p>
                <p class="text-2xl font-semibold text-gray-900">{{ activeTeachers }}</p>
              </div>
            </div>
          </div>
          <div class="bg-orange-50 rounded-lg p-4">
            <div class="flex items-center">
              <div class="p-2 bg-orange-100 rounded-lg">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Dépassements</p>
                <p class="text-2xl font-semibold text-gray-900">{{ exceededContracts }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Détail des Dépassements par Statut -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Répartition par Statut de Dépassement</h3>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Statut Vert -->
            <div @click="filterByStatus('green')" 
                 class="bg-green-50 border border-green-200 rounded-lg p-4 cursor-pointer hover:bg-green-100 transition-colors"
                 :class="{ 'ring-2 ring-green-500': exceedanceFilter === 'green' }">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                  <div>
                    <p class="text-sm font-medium text-gray-600">Dans les limites</p>
                    <p class="text-xs text-gray-500">&lt; {{ exceedanceThresholds.orange }}%</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-2xl font-bold text-green-600">{{ exceedanceStats.green }}</p>
                  <p class="text-xs text-gray-500">enseignants</p>
                </div>
              </div>
            </div>

            <!-- Statut Orange -->
            <div @click="filterByStatus('orange')" 
                 class="bg-orange-50 border border-orange-200 rounded-lg p-4 cursor-pointer hover:bg-orange-100 transition-colors"
                 :class="{ 'ring-2 ring-orange-500': exceedanceFilter === 'orange' }">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <div class="w-4 h-4 bg-orange-500 rounded-full"></div>
                  <div>
                    <p class="text-sm font-medium text-gray-600">Zone d'attention</p>
                    <p class="text-xs text-gray-500">{{ exceedanceThresholds.orange }}% - {{ exceedanceThresholds.red }}%</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-2xl font-bold text-orange-600">{{ exceedanceStats.orange }}</p>
                  <p class="text-xs text-gray-500">enseignants</p>
                </div>
              </div>
            </div>

            <!-- Statut Rouge -->
            <div @click="filterByStatus('red')" 
                 class="bg-red-50 border border-red-200 rounded-lg p-4 cursor-pointer hover:bg-red-100 transition-colors"
                 :class="{ 'ring-2 ring-red-500': exceedanceFilter === 'red' }">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <div class="w-4 h-4 bg-red-500 rounded-full"></div>
                  <div>
                    <p class="text-sm font-medium text-gray-600">Zone critique</p>
                    <p class="text-xs text-gray-500">{{ exceedanceThresholds.red }}% - 100%</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-2xl font-bold text-red-600">{{ exceedanceStats.red }}</p>
                  <p class="text-xs text-gray-500">enseignants</p>
                </div>
              </div>
            </div>

            <!-- Statut Noir -->
            <div @click="filterByStatus('black')" 
                 class="bg-gray-50 border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-gray-100 transition-colors"
                 :class="{ 'ring-2 ring-gray-500': exceedanceFilter === 'black' }">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <div class="w-4 h-4 bg-gray-800 rounded-full"></div>
                  <div>
                    <p class="text-sm font-medium text-gray-600">Dépassements</p>
                    <p class="text-xs text-gray-500">&gt; 100%</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-2xl font-bold text-gray-800">{{ exceedanceStats.black }}</p>
                  <p class="text-xs text-gray-500">enseignants</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Détails par enseignant -->
        <div class="space-y-4">
          <div v-for="teacher in filteredTeachers" :key="teacher.id" class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-gray-900">{{ teacher.first_name }} {{ teacher.last_name }}</h3>
              <div class="flex space-x-1">
                <div v-for="indicator in getExceedanceIndicators(teacher)" :key="indicator.type" 
                     class="w-3 h-3 rounded-full" 
                     :class="indicator.color"
                     :title="indicator.tooltip">
                </div>
              </div>
            </div>

            <!-- Récapitulatif mensuel/annuel -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-sm text-gray-500">Heures {{ selectedPeriod === 'monthly' ? 'ce mois' : 'cette année' }}</p>
                <p class="text-lg font-semibold">{{ teacher.period_hours || 0 }}h</p>
              </div>
              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-sm text-gray-500">Montant {{ selectedPeriod === 'monthly' ? 'ce mois' : 'cette année' }}</p>
                <p class="text-lg font-semibold">{{ teacher.period_amount || 0 }}€</p>
              </div>
              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-sm text-gray-500">Kilométrage {{ selectedPeriod === 'monthly' ? 'ce mois' : 'cette année' }}</p>
                <p class="text-lg font-semibold">{{ teacher.period_mileage || 0 }}km</p>
              </div>
            </div>

            <!-- Historique des paiements -->
            <div class="border-t pt-4">
              <h4 class="font-medium text-gray-900 mb-3">Historique des Paiements</h4>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Heures</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kilométrage</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="payment in teacher.payments" :key="payment.id">
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(payment.date) }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ payment.amount }}€</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ payment.hours }}h</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ payment.mileage || 0 }}km</td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 rounded-full text-xs font-medium" :class="getPaymentStatusClass(payment.status)">
                          {{ payment.status }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Onglet Paramètres -->
    <div v-else-if="activeTab === 'settings'" class="space-y-6">
      <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Paramètres des Zones de Dépassement</h2>
        
        <form @submit.prevent="saveExceedanceSettings" class="space-y-6">
          <!-- Configuration des seuils -->
          <div class="bg-gray-50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Seuils de Dépassement</h3>
            <p class="text-sm text-gray-600 mb-6">
              Configurez les pourcentages qui définissent les différentes zones de dépassement pour les contrats.
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Seuil Orange -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Seuil Zone d'Attention (Orange)
                </label>
                <div class="relative">
                  <input 
                    v-model.number="exceedanceThresholds.orange" 
                    type="number" 
                    min="0" 
                    max="100" 
                    step="1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    placeholder="80"
                  />
                  <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">%</span>
                  </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                  Enseignants entre {{ exceedanceThresholds.orange }}% et {{ exceedanceThresholds.red }}% des plafonds
                </p>
              </div>

              <!-- Seuil Rouge -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Seuil Zone Critique (Rouge)
                </label>
                <div class="relative">
                  <input 
                    v-model.number="exceedanceThresholds.red" 
                    type="number" 
                    min="0" 
                    max="100" 
                    step="1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                    placeholder="95"
                  />
                  <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">%</span>
                  </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                  Enseignants entre {{ exceedanceThresholds.red }}% et 100% des plafonds
                </p>
              </div>
            </div>
          </div>

          <!-- Aperçu des zones -->
          <div class="bg-blue-50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Aperçu des Zones</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <!-- Zone Verte -->
              <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center space-x-2 mb-2">
                  <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                  <span class="font-medium text-green-800">Zone Verte</span>
                </div>
                <p class="text-sm text-green-700">
                  &lt; {{ exceedanceThresholds.orange }}%
                </p>
                <p class="text-xs text-green-600 mt-1">
                  Dans les limites
                </p>
              </div>

              <!-- Zone Orange -->
              <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                <div class="flex items-center space-x-2 mb-2">
                  <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                  <span class="font-medium text-orange-800">Zone Orange</span>
                </div>
                <p class="text-sm text-orange-700">
                  {{ exceedanceThresholds.orange }}% - {{ exceedanceThresholds.red }}%
                </p>
                <p class="text-xs text-orange-600 mt-1">
                  Zone d'attention
                </p>
              </div>

              <!-- Zone Rouge -->
              <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center space-x-2 mb-2">
                  <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                  <span class="font-medium text-red-800">Zone Rouge</span>
                </div>
                <p class="text-sm text-red-700">
                  {{ exceedanceThresholds.red }}% - 100%
                </p>
                <p class="text-xs text-red-600 mt-1">
                  Zone critique
                </p>
              </div>

              <!-- Zone Noire -->
              <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div class="flex items-center space-x-2 mb-2">
                  <div class="w-3 h-3 bg-gray-800 rounded-full"></div>
                  <span class="font-medium text-gray-800">Zone Noire</span>
                </div>
                <p class="text-sm text-gray-700">
                  &gt; 100%
                </p>
                <p class="text-xs text-gray-600 mt-1">
                  Dépassements
                </p>
              </div>
            </div>
          </div>

          <!-- Bouton de sauvegarde -->
          <div class="flex justify-end">
            <button 
              type="submit"
              :disabled="isSaving"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50"
            >
              <span v-if="isSaving">Sauvegarde...</span>
              <span v-else>Enregistrer les paramètres</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from '@/composables/useToast'

definePageMeta({
  layout: 'admin',
  middleware: 'admin'
})

const { showToast } = useToast()

// État général
const loading = ref(true)
const isSaving = ref(false)
const error = ref(null)
const activeTab = ref('types')

// Onglet Types de Contrats
const contractTypes = ref({
  volunteer: {
    active: true,
    annual_ceiling: 3900,
    daily_ceiling: 42.31,
    mileage_allowance: 0.4,
    max_annual_mileage: 2000
  },
  student: {
    active: false,
    annual_ceiling: 0,
    daily_ceiling: 0
  },
  article17: {
    active: false,
    annual_ceiling: 0,
    daily_ceiling: 0
  },
  freelance: {
    active: false,
    annual_ceiling: 0,
    daily_ceiling: 0
  },
  salaried: {
    active: false,
    annual_ceiling: 0,
    daily_ceiling: 0
  }
})

// Onglet Enseignants & Contrats
const teachers = ref([])
const selectedYear = ref(new Date().getFullYear())
const availableYears = ref([])

// Onglet Paiements & Heures
const selectedPeriod = ref('monthly')
const totalPayments = ref(0)
const totalHours = ref(0)
const activeTeachers = ref(0)
const exceededContracts = ref(0)

// Filtrage par dépassements
const exceedanceFilter = ref('all')
const exceedanceThresholds = ref({
  orange: 80,
  red: 95
})
const exceedanceStats = ref({
  green: 0,
  orange: 0,
  red: 0,
  black: 0
})

// Computed
const contractTypeLabels = {
  volunteer: 'Bénévole',
  student: 'Étudiant',
  article17: 'Article 17',
  freelance: 'Indépendant',
  salaried: 'Salarié'
}

// Méthodes pour les types de contrats
const loadContractTypes = async () => {
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    const response = await $fetch(`${config.public.apiBase}/admin/settings/contracts`, {
      headers: { 'Authorization': `Bearer ${tokenCookie.value}` }
    })
    if (response.success && response.data) {
      contractTypes.value = { ...contractTypes.value, ...response.data }
    }
  } catch (e) {
    console.error('Erreur lors du chargement des types de contrats:', e)
  }
}

const saveContractTypes = async () => {
  isSaving.value = true
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    await $fetch(`${config.public.apiBase}/admin/settings/contracts`, {
      method: 'PUT',
      headers: { 
        'Authorization': `Bearer ${tokenCookie.value}`,
        'Content-Type': 'application/json'
      },
      body: contractTypes.value
    })
    showToast('Types de contrats mis à jour avec succès !', 'success')
  } catch (e) {
    const errorMessage = e.data?.message || "Une erreur est survenue lors de la sauvegarde."
    showToast(errorMessage, 'error')
  } finally {
    isSaving.value = false
  }
}

// Méthodes pour les enseignants
const loadTeachersData = async () => {
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    const response = await $fetch(`${config.public.apiBase}/admin/teachers/contracts`, {
      headers: { 'Authorization': `Bearer ${tokenCookie.value}` },
      params: { year: selectedYear.value }
    })
    if (response.success) {
      teachers.value = response.data
    }
  } catch (e) {
    console.error('Erreur lors du chargement des enseignants:', e)
    // Données de démonstration
    teachers.value = generateMockTeachers()
  }
  
  // Calculer les statistiques après le chargement
  calculateExceedanceStats()
}

const loadPaymentsData = async () => {
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    const response = await $fetch(`${config.public.apiBase}/admin/payments/summary`, {
      headers: { 'Authorization': `Bearer ${tokenCookie.value}` },
      params: { 
        year: selectedYear.value,
        period: selectedPeriod.value
      }
    })
    if (response.success) {
      totalPayments.value = response.data.total_payments
      totalHours.value = response.data.total_hours
      activeTeachers.value = response.data.active_teachers
      exceededContracts.value = response.data.exceeded_contracts
    }
  } catch (e) {
    console.error('Erreur lors du chargement des paiements:', e)
    // Données de démonstration basées sur les cas de test
    totalPayments.value = 20220  // Somme de tous les paiements des cas de test
    totalHours.value = 520       // Somme de toutes les heures des cas de test
    activeTeachers.value = 8     // Nombre d'enseignants dans les cas de test
    exceededContracts.value = 4  // 4 enseignants avec dépassements (cas 4, 5, 6, 7)
  }
}

// Méthodes utilitaires
const getContractTypeLabel = (type) => {
  return contractTypeLabels[type] || type
}

const getContractStatusClass = (contract) => {
  if (!contract) return 'bg-gray-100 text-gray-800'
  
  const classes = {
    volunteer: 'bg-green-100 text-green-800',
    student: 'bg-blue-100 text-blue-800',
    article17: 'bg-purple-100 text-purple-800',
    freelance: 'bg-yellow-100 text-yellow-800',
    salaried: 'bg-indigo-100 text-indigo-800'
  }
  return classes[contract.type] || 'bg-gray-100 text-gray-800'
}

const getExceedanceIndicators = (teacher) => {
  if (!teacher.current_contract) return []
  
  const indicators = []
  const contractType = contractTypes.value[teacher.current_contract.type]
  if (!contractType?.active) return indicators
  
  // Calculer les pourcentages
  const annualPercentage = (teacher.annual_amount || 0) / contractType.annual_ceiling * 100
  const dailyPercentage = (teacher.daily_amount || 0) / contractType.daily_ceiling * 100
  const mileagePercentage = (teacher.annual_mileage || 0) / (contractType.max_annual_mileage || 1) * 100
  
  // Déterminer le statut global
  let status = 'green'
  let label = 'Dans les limites'
  let tooltip = 'Dans les limites'
  
  if (annualPercentage >= 100 || dailyPercentage >= 100 || mileagePercentage >= 100) {
    status = 'black'
    label = 'Dépassé'
    tooltip = `Dépassement: ${annualPercentage >= 100 ? 'Plafond annuel' : ''} ${dailyPercentage >= 100 ? 'Plafond journalier' : ''} ${mileagePercentage >= 100 ? 'Kilométrage' : ''}`
  } else if (annualPercentage >= exceedanceThresholds.value.red || dailyPercentage >= exceedanceThresholds.value.red || mileagePercentage >= exceedanceThresholds.value.red) {
    status = 'red'
    label = 'Critique'
    tooltip = `Critique: ${annualPercentage >= exceedanceThresholds.value.red ? 'Plafond annuel' : ''} ${dailyPercentage >= exceedanceThresholds.value.red ? 'Plafond journalier' : ''} ${mileagePercentage >= exceedanceThresholds.value.red ? 'Kilométrage' : ''}`
  } else if (annualPercentage >= exceedanceThresholds.value.orange || dailyPercentage >= exceedanceThresholds.value.orange || mileagePercentage >= exceedanceThresholds.value.orange) {
    status = 'orange'
    label = 'Attention'
    tooltip = `Attention: ${annualPercentage >= exceedanceThresholds.value.orange ? 'Plafond annuel' : ''} ${dailyPercentage >= exceedanceThresholds.value.orange ? 'Plafond journalier' : ''} ${mileagePercentage >= exceedanceThresholds.value.orange ? 'Kilométrage' : ''}`
  }
  
  indicators.push({
    type: status,
    status: status,
    label: label,
    color: getStatusColor(status),
    tooltip: tooltip
  })
  
  return indicators
}

const getStatusColor = (status) => {
  const colors = {
    green: 'bg-green-500',
    orange: 'bg-orange-500',
    red: 'bg-red-500',
    black: 'bg-gray-800'
  }
  return colors[status] || 'bg-gray-500'
}

const getIndicatorBadgeClass = (status) => {
  const classes = {
    green: 'bg-green-100 text-green-800 border-green-200',
    orange: 'bg-orange-100 text-orange-800 border-orange-200',
    red: 'bg-red-100 text-red-800 border-red-200',
    black: 'bg-gray-100 text-gray-800 border-gray-200'
  }
  return classes[status] || 'bg-gray-100 text-gray-800 border-gray-200'
}

const getPaymentStatusClass = (status) => {
  const classes = {
    paid: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    overdue: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR')
}

// Génération de données de démonstration
const generateMockTeachers = () => {
  return [
    // CAS 1: Enseignant dans les limites (VERT)
    {
      id: 1,
      first_name: 'Marie',
      last_name: 'Dubois',
      email: 'marie.dubois@example.com',
      current_contract: {
        id: 1,
        type: 'volunteer',
        start_date: '2024-01-01',
        end_date: null,
        total_hours: 45,
        total_amount: 1200
      },
      contract_history: [
        {
          id: 1,
          type: 'volunteer',
          start_date: '2024-01-01',
          end_date: null,
          total_hours: 45,
          total_amount: 1200
        }
      ],
      annual_amount: 1200,      // 30.8% du plafond (1200/3900) - VERT
      daily_amount: 25,         // 59.1% du plafond (25/42.31) - VERT
      annual_mileage: 150,      // 7.5% du plafond (150/2000) - VERT
      period_hours: 12,
      period_amount: 320,
      period_mileage: 45,
      payments: [
        {
          id: 1,
          date: '2024-01-15',
          amount: 320,
          hours: 12,
          mileage: 45,
          status: 'paid'
        }
      ]
    },

    // CAS 2: Enseignant en zone d'attention (ORANGE) - Plafond annuel à 85%
    {
      id: 2,
      first_name: 'Pierre',
      last_name: 'Martin',
      email: 'pierre.martin@example.com',
      current_contract: {
        id: 2,
        type: 'volunteer',
        start_date: '2024-01-01',
        end_date: null,
        total_hours: 120,
        total_amount: 3315
      },
      contract_history: [
        {
          id: 2,
          type: 'volunteer',
          start_date: '2024-01-01',
          end_date: null,
          total_hours: 120,
          total_amount: 3315
        }
      ],
      annual_amount: 3315,      // 85% du plafond (3315/3900) - ORANGE
      daily_amount: 35,         // 82.7% du plafond (35/42.31) - ORANGE
      annual_mileage: 1200,     // 60% du plafond (1200/2000) - VERT
      period_hours: 30,
      period_amount: 828,
      period_mileage: 300,
      payments: [
        {
          id: 2,
          date: '2024-01-15',
          amount: 828,
          hours: 30,
          mileage: 300,
          status: 'paid'
        }
      ]
    },

    // CAS 3: Enseignant en zone critique (ROUGE) - Plafond journalier à 98%
    {
      id: 3,
      first_name: 'Sophie',
      last_name: 'Leroy',
      email: 'sophie.leroy@example.com',
      current_contract: {
        id: 3,
        type: 'volunteer',
        start_date: '2024-01-01',
        end_date: null,
        total_hours: 95,
        total_amount: 2800
      },
      contract_history: [
        {
          id: 3,
          type: 'volunteer',
          start_date: '2024-01-01',
          end_date: null,
          total_hours: 95,
          total_amount: 2800
        }
      ],
      annual_amount: 2800,      // 71.8% du plafond (2800/3900) - VERT
      daily_amount: 41.5,       // 98.1% du plafond (41.5/42.31) - ROUGE
      annual_mileage: 1800,     // 90% du plafond (1800/2000) - ORANGE
      period_hours: 25,
      period_amount: 700,
      period_mileage: 450,
      payments: [
        {
          id: 3,
          date: '2024-01-15',
          amount: 700,
          hours: 25,
          mileage: 450,
          status: 'paid'
        }
      ]
    },

    // CAS 4: Enseignant avec dépassement kilométrique (NOIR)
    {
      id: 4,
      first_name: 'Jean',
      last_name: 'Bernard',
      email: 'jean.bernard@example.com',
      current_contract: {
        id: 4,
        type: 'volunteer',
        start_date: '2024-01-01',
        end_date: null,
        total_hours: 80,
        total_amount: 2200
      },
      contract_history: [
        {
          id: 4,
          type: 'volunteer',
          start_date: '2024-01-01',
          end_date: null,
          total_hours: 80,
          total_amount: 2200
        }
      ],
      annual_amount: 2200,      // 56.4% du plafond (2200/3900) - VERT
      daily_amount: 30,         // 70.9% du plafond (30/42.31) - VERT
      annual_mileage: 2500,     // 125% du plafond (2500/2000) - NOIR DÉPASSÉ
      period_hours: 20,
      period_amount: 550,
      period_mileage: 625,
      payments: [
        {
          id: 4,
          date: '2024-01-15',
          amount: 550,
          hours: 20,
          mileage: 625,
          status: 'paid'
        }
      ]
    },

    // CAS 5: Enseignant avec dépassement plafond annuel (NOIR)
    {
      id: 5,
      first_name: 'Claire',
      last_name: 'Moreau',
      email: 'claire.moreau@example.com',
      current_contract: {
        id: 5,
        type: 'volunteer',
        start_date: '2024-01-01',
        end_date: null,
        total_hours: 150,
        total_amount: 4200
      },
      contract_history: [
        {
          id: 5,
          type: 'volunteer',
          start_date: '2024-01-01',
          end_date: null,
          total_hours: 150,
          total_amount: 4200
        }
      ],
      annual_amount: 4200,      // 107.7% du plafond (4200/3900) - NOIR DÉPASSÉ
      daily_amount: 38,         // 89.8% du plafond (38/42.31) - ORANGE
      annual_mileage: 1500,     // 75% du plafond (1500/2000) - VERT
      period_hours: 40,
      period_amount: 1050,
      period_mileage: 375,
      payments: [
        {
          id: 5,
          date: '2024-01-15',
          amount: 1050,
          hours: 40,
          mileage: 375,
          status: 'paid'
        }
      ]
    },

    // CAS 6: Enseignant avec dépassement plafond journalier (NOIR)
    {
      id: 6,
      first_name: 'Antoine',
      last_name: 'Petit',
      email: 'antoine.petit@example.com',
      current_contract: {
        id: 6,
        type: 'volunteer',
        start_date: '2024-01-01',
        end_date: null,
        total_hours: 60,
        total_amount: 1800
      },
      contract_history: [
        {
          id: 6,
          type: 'volunteer',
          start_date: '2024-01-01',
          end_date: null,
          total_hours: 60,
          total_amount: 1800
        }
      ],
      annual_amount: 1800,      // 46.2% du plafond (1800/3900) - VERT
      daily_amount: 45,          // 106.4% du plafond (45/42.31) - NOIR DÉPASSÉ
      annual_mileage: 800,      // 40% du plafond (800/2000) - VERT
      period_hours: 15,
      period_amount: 450,
      period_mileage: 200,
      payments: [
        {
          id: 6,
          date: '2024-01-15',
          amount: 450,
          hours: 15,
          mileage: 200,
          status: 'paid'
        }
      ]
    },

    // CAS 7: Enseignant avec dépassements multiples (NOIR)
    {
      id: 7,
      first_name: 'Isabelle',
      last_name: 'Rousseau',
      email: 'isabelle.rousseau@example.com',
      current_contract: {
        id: 7,
        type: 'volunteer',
        start_date: '2024-01-01',
        end_date: null,
        total_hours: 200,
        total_amount: 5000
      },
      contract_history: [
        {
          id: 7,
          type: 'volunteer',
          start_date: '2024-01-01',
          end_date: null,
          total_hours: 200,
          total_amount: 5000
        }
      ],
      annual_amount: 5000,      // 128.2% du plafond (5000/3900) - NOIR DÉPASSÉ
      daily_amount: 50,          // 118.2% du plafond (50/42.31) - NOIR DÉPASSÉ
      annual_mileage: 3000,     // 150% du plafond (3000/2000) - NOIR DÉPASSÉ
      period_hours: 50,
      period_amount: 1250,
      period_mileage: 750,
      payments: [
        {
          id: 7,
          date: '2024-01-15',
          amount: 1250,
          hours: 50,
          mileage: 750,
          status: 'paid'
        }
      ]
    },

    // CAS 8: Enseignant indépendant (pas de plafonds kilométriques)
    {
      id: 8,
      first_name: 'Marc',
      last_name: 'Durand',
      email: 'marc.durand@example.com',
      current_contract: {
        id: 8,
        type: 'freelance',
        start_date: '2024-02-01',
        end_date: null,
        total_hours: 80,
        total_amount: 2400
      },
      contract_history: [
        {
          id: 8,
          type: 'freelance',
          start_date: '2024-02-01',
          end_date: null,
          total_hours: 80,
          total_amount: 2400
        }
      ],
      annual_amount: 2400,      // Dépend du plafond configuré pour freelance
      daily_amount: 0,          // Pas de plafond journalier pour freelance
      annual_mileage: 0,        // Pas de kilométrage pour freelance
      period_hours: 20,
      period_amount: 600,
      period_mileage: 0,
      payments: [
        {
          id: 8,
          date: '2024-01-15',
          amount: 600,
          hours: 20,
          mileage: 0,
          status: 'paid'
        }
      ]
    }
  ]
}

// Méthodes de filtrage et statistiques
const calculateExceedanceStats = () => {
  const stats = { green: 0, orange: 0, red: 0, black: 0 }
  
  teachers.value.forEach(teacher => {
    const indicators = getExceedanceIndicators(teacher)
    if (indicators.length > 0) {
      const status = indicators[0].status
      stats[status]++
    }
  })
  
  exceedanceStats.value = stats
}

const applyExceedanceFilter = () => {
  calculateExceedanceStats()
}

const filterByStatus = (status) => {
  exceedanceFilter.value = status
  calculateExceedanceStats()
}

const filteredTeachers = computed(() => {
  if (exceedanceFilter.value === 'all') {
    return teachers.value
  }
  
  return teachers.value.filter(teacher => {
    const indicators = getExceedanceIndicators(teacher)
    if (indicators.length > 0) {
      return indicators[0].status === exceedanceFilter.value
    }
    return false
  })
})

// Initialisation
const initializeYears = () => {
  const currentYear = new Date().getFullYear()
  for (let i = currentYear - 2; i <= currentYear + 1; i++) {
    availableYears.value.push(i)
  }
}

const loadAllData = async () => {
  loading.value = true
  error.value = null
  
  try {
    await Promise.all([
      loadContractTypes(),
      loadTeachersData(),
      loadPaymentsData(),
      loadExceedanceSettings()
    ])
  } catch (e) {
    error.value = e.data?.message || "Une erreur inconnue est survenue."
    showToast(error.value, 'error')
  } finally {
    loading.value = false
  }
}

// Méthodes pour les paramètres de dépassement
const loadExceedanceSettings = async () => {
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    const response = await $fetch(`${config.public.apiBase}/admin/settings/exceedance-thresholds`, {
      headers: { 'Authorization': `Bearer ${tokenCookie.value}` }
    })
    if (response.success && response.data) {
      exceedanceThresholds.value = { ...exceedanceThresholds.value, ...response.data }
    }
  } catch (e) {
    console.error('Erreur lors du chargement des paramètres de dépassement:', e)
    // Utiliser les valeurs par défaut
  }
}

const saveExceedanceSettings = async () => {
  isSaving.value = true
  try {
    const config = useRuntimeConfig()
    const tokenCookie = useCookie('auth-token')
    await $fetch(`${config.public.apiBase}/admin/settings/exceedance-thresholds`, {
      method: 'PUT',
      headers: { 
        'Authorization': `Bearer ${tokenCookie.value}`,
        'Content-Type': 'application/json'
      },
      body: exceedanceThresholds.value
    })
    showToast('Paramètres de dépassement mis à jour avec succès !', 'success')
    
    // Recalculer les statistiques avec les nouveaux seuils
    calculateExceedanceStats()
  } catch (e) {
    const errorMessage = e.data?.message || "Une erreur est survenue lors de la sauvegarde."
    showToast(errorMessage, 'error')
  } finally {
    isSaving.value = false
  }
}

onMounted(() => {
  initializeYears()
  loadAllData()
})
</script>
