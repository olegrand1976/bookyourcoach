<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="$emit('close')">
    <div class="flex items-center justify-center min-h-screen px-4 py-12">
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="$emit('close')"></div>
      
      <div class="relative bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-500 to-indigo-600">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-bold text-white">Historique de {{ studentDisplayName }}</h2>
              <p class="text-sm text-purple-100 mt-1">Abonnements et cours</p>
            </div>
            <button 
              @click="$emit('close')"
              class="text-white hover:text-gray-200 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="overflow-y-auto max-h-[calc(90vh-180px)]">
          <!-- Loading -->
          <div v-if="loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
          </div>

          <!-- Error -->
          <div v-else-if="error" class="p-6">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
              <p class="text-red-800">{{ error }}</p>
            </div>
          </div>

          <!-- Data -->
          <div v-else class="p-6 space-y-6">
            <!-- Statistiques -->
            <div v-if="historyData?.stats" class="grid grid-cols-2 md:grid-cols-6 gap-4">
              <div class="bg-blue-50 rounded-lg p-4">
                <div class="text-sm text-blue-600 font-medium">Abonnements</div>
                <div class="text-2xl font-bold text-blue-900 mt-1">{{ historyData.stats.total_subscriptions }}</div>
              </div>
              <div class="bg-green-50 rounded-lg p-4">
                <div class="text-sm text-green-600 font-medium">Actifs</div>
                <div class="text-2xl font-bold text-green-900 mt-1">{{ historyData.stats.active_subscriptions }}</div>
              </div>
              <div class="bg-purple-50 rounded-lg p-4">
                <div class="text-sm text-purple-600 font-medium">Cours</div>
                <div class="text-2xl font-bold text-purple-900 mt-1">{{ historyData.stats.total_lessons }}</div>
              </div>
              <div class="bg-emerald-50 rounded-lg p-4">
                <div class="text-sm text-emerald-600 font-medium">Terminés</div>
                <div class="text-2xl font-bold text-emerald-900 mt-1">{{ historyData.stats.completed_lessons }}</div>
              </div>
              <div class="bg-amber-50 rounded-lg p-4">
                <div class="text-sm text-amber-600 font-medium">Dépensé</div>
                <div class="text-2xl font-bold text-amber-900 mt-1">{{ formatPrice(historyData.stats.total_spent) }} €</div>
              </div>
              <!-- Fin théorique des abonnements actifs -->
              <div class="bg-slate-50 rounded-lg p-4">
                <div class="text-sm text-slate-600 font-medium">
                  Fin théorique des abonnements
                </div>
                <div class="text-lg font-bold text-slate-900 mt-1">
                  {{ subscriptionsEarliestEndFormatted || '—' }}
                </div>
              </div>
            </div>

            <!-- Bloc dédié : Validation des certificats médicaux -->
            <div v-if="lessonsWithCertificate.length > 0" class="border border-amber-200 rounded-xl bg-amber-50/50 overflow-hidden">
              <div class="px-6 py-4 border-b border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                  <svg class="w-5 h-5 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Validation des certificats médicaux ({{ lessonsWithCertificate.length }})
                </h3>
                <p class="text-sm text-gray-600 mt-1">Acceptez, refusez avec un message à l'élève, ou clôturez la demande après plusieurs aller-retour.</p>
              </div>
              <div class="p-4 space-y-4">
                <div
                  v-for="lesson in lessonsWithCertificate"
                  :key="`cert-${lesson.id}`"
                  class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 bg-white rounded-lg border border-amber-100 shadow-sm"
                >
                  <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="font-semibold text-gray-900">{{ lesson.course_type?.name || 'Cours' }}</span>
                      <span class="text-sm text-gray-500">{{ formatDateShort(lesson.start_time) }} — {{ formatTimeOnly(lesson.start_time) }}</span>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-2">
                      <span
                        :class="getCancellationSubscriptionImpactClass(lesson)"
                        class="px-2 py-0.5 text-xs font-medium rounded-full"
                      >
                        {{ getCancellationSubscriptionImpact(lesson) }}
                      </span>
                      <span
                        v-if="shouldShowCertificateStatusBadge(lesson)"
                        :class="getCertificateStatusClass(lesson.cancellation_certificate_status)"
                        class="px-2 py-0.5 text-xs font-medium rounded-full"
                      >
                        {{ getCertificateStatusLabel(lesson.cancellation_certificate_status) }}
                      </span>
                    </div>
                    <p v-if="lesson.cancellation_certificate_rejection_reason && (lesson.cancellation_certificate_status === 'rejected' || lesson.cancellation_certificate_status === 'closed')" class="text-xs text-gray-600 mt-2 italic">
                      Message envoyé à l'élève : {{ lesson.cancellation_certificate_rejection_reason }}
                    </p>
                  </div>
                  <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <button
                      type="button"
                      @click="downloadCertificate(lesson.id)"
                      class="px-3 py-1.5 text-sm text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100"
                    >
                      Télécharger
                    </button>
                    <template v-if="lesson.cancellation_certificate_status === 'pending'">
                      <button
                        @click="acceptCertificate(lesson)"
                        :disabled="certificateActionLoading === lesson.id"
                        class="px-3 py-1.5 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50"
                      >
                        Accepter
                      </button>
                      <button
                        @click="openRejectCertificate(lesson)"
                        :disabled="certificateActionLoading === lesson.id"
                        class="px-3 py-1.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50"
                      >
                        Refuser (message à l'élève)
                      </button>
                      <button
                        @click="openCloseCertificate(lesson)"
                        :disabled="certificateActionLoading === lesson.id"
                        class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 disabled:opacity-50"
                        title="Clôturer la demande : l'élève ne pourra plus renvoyer de certificat"
                      >
                        Clôturer la demande
                      </button>
                    </template>
                    <template v-else-if="lesson.cancellation_certificate_status === 'rejected'">
                      <button
                        @click="openCloseCertificate(lesson)"
                        :disabled="certificateActionLoading === lesson.id"
                        class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 disabled:opacity-50"
                        title="Clôturer : l'élève ne pourra plus renvoyer de certificat"
                      >
                        Clôturer la demande
                      </button>
                    </template>
                  </div>
                </div>
              </div>
            </div>

            <!-- Abonnements -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Abonnements ({{ historyData?.subscriptions?.length || 0 }})
              </h3>
              
              <div v-if="!historyData?.subscriptions || historyData.subscriptions.length === 0" class="bg-gray-50 rounded-lg p-6 text-center">
                <p class="text-gray-500">Aucun abonnement pour cet élève</p>
              </div>
              
              <div v-else class="space-y-4">
                <div 
                  v-for="subscription in historyData.subscriptions" 
                  :key="subscription.id"
                  class="bg-gray-50 rounded-lg p-4 border border-gray-200"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center space-x-3 mb-2">
                        <h4 class="font-semibold text-gray-900">
                          {{ subscription.subscription?.template?.model_number || subscription.subscription?.name || 'Abonnement' }}
                        </h4>
                        <span 
                          :class="{
                            'bg-green-100 text-green-800': subscription.status === 'active',
                            'bg-gray-100 text-gray-800': subscription.status === 'completed',
                            'bg-red-100 text-red-800': subscription.status === 'expired',
                            'bg-yellow-100 text-yellow-800': subscription.status === 'cancelled'
                          }"
                          class="px-2 py-1 text-xs font-medium rounded-full"
                        >
                          {{ getStatusLabel(subscription.status) }}
                        </span>
                      </div>
                      
                      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                          <span class="text-gray-600">Cours utilisés:</span>
                          <span class="font-semibold text-gray-900 ml-1">
                            {{ subscription.lessons_used }} / {{ getTotalLessons(subscription) }}
                          </span>
                        </div>
                        <div>
                          <span class="text-gray-600">Début:</span>
                          <span class="font-medium text-gray-900 ml-1">{{ formatDate(subscription.started_at) }}</span>
                        </div>
                        <div>
                          <span class="text-gray-600">Expiration:</span>
                          <span class="font-medium text-gray-900 ml-1">
                            {{ subscription.expires_at ? formatDate(subscription.expires_at) : 'Non définie' }}
                          </span>
                        </div>
                        <div>
                          <span class="text-gray-600">Créé le:</span>
                          <span class="font-medium text-gray-900 ml-1">{{ formatDate(subscription.created_at) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Cours (triés par date croissante) -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                Calendrier des cours ({{ historyData?.lessons?.length || 0 }}) — certificats à valider en tête, puis par date
              </h3>
              
              <div v-if="!historyData?.lessons || historyData.lessons.length === 0" class="bg-gray-50 rounded-lg p-6 text-center">
                <p class="text-gray-500">Aucun cours pour cet élève</p>
              </div>
              
              <template v-else>
                <!-- Fin théorique des abonnements à partir du … -->
                <div 
                  v-if="subscriptionsEarliestEndFormatted" 
                  class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-4"
                >
                  <p class="text-slate-800 font-medium">
                    Fin théorique des abonnements à partir du {{ subscriptionsEarliestEndFormatted }}.
                  </p>
                </div>

                <!-- En-tête du tableau (desktop) -->
                <div class="hidden md:grid md:grid-cols-12 gap-2 px-4 py-2 bg-gray-100 rounded-t-lg border border-b-0 border-gray-200 text-xs font-semibold text-gray-600 uppercase tracking-wide">
                  <div class="col-span-2">Date</div>
                  <div class="col-span-1">Heure</div>
                  <div class="col-span-2">Type de cours</div>
                  <div class="col-span-2">Enseignant</div>
                  <div class="col-span-1">Statut</div>
                  <div class="col-span-1">Prix</div>
                  <div class="col-span-2">Lieu / Abo</div>
                  <div class="col-span-1 text-right">Action</div>
                </div>

                <div class="space-y-2 border border-gray-200 rounded-b-lg md:rounded-t-none overflow-hidden">
                  <div 
                    v-for="lesson in lessonsSortedByDate" 
                    :key="lesson.id"
                    :class="[
                      'rounded-lg md:rounded-none p-4 md:py-3 border-b border-gray-100 last:border-b-0 md:grid md:grid-cols-12 md:gap-2 md:items-center transition-colors',
                      isLessonUncovered(lesson) 
                        ? 'bg-red-50 border-red-200 md:border-0' 
                        : 'bg-white hover:bg-gray-50 md:border-0'
                    ]"
                  >
                    <!-- Date (mise en avant) -->
                    <div class="md:col-span-2 mb-2 md:mb-0">
                      <span class="text-xs text-gray-500 md:hidden">Date</span>
                      <p class="font-semibold text-gray-900">{{ formatDateShort(lesson.start_time) }}</p>
                      <p class="text-xs text-gray-500">{{ formatWeekday(lesson.start_time) }}</p>
                    </div>
                    <!-- Heure -->
                    <div class="md:col-span-1 mb-2 md:mb-0">
                      <span class="text-xs text-gray-500 md:hidden">Heure</span>
                      <p class="font-medium text-gray-900">{{ formatTimeOnly(lesson.start_time) }} – {{ formatTimeOnly(lesson.end_time) }}</p>
                    </div>
                    <!-- Type de cours + badges -->
                    <div class="md:col-span-2 mb-2 md:mb-0">
                      <span class="text-xs text-gray-500 md:hidden">Type</span>
                      <p class="font-medium text-gray-900">{{ lesson.course_type?.name || 'Cours' }}</p>
                      <div class="flex flex-wrap gap-1 mt-1">
                        <span 
                          v-if="lesson.subscription_instances?.length"
                          class="px-1.5 py-0.5 text-xs bg-green-100 text-green-800 rounded"
                        >✓ Abo</span>
                        <span v-else class="px-1.5 py-0.5 text-xs bg-orange-100 text-orange-800 rounded">Séance libre</span>
                        <span 
                          v-if="isLessonUncovered(lesson)"
                          class="px-1.5 py-0.5 text-xs font-bold bg-red-500 text-white rounded"
                        >⚠️ Non couvert</span>
                        <span 
                          v-else-if="lesson.subscription_coverage?.is_future && lesson.subscription_coverage?.is_covered"
                          class="px-1.5 py-0.5 text-xs bg-blue-100 text-blue-800 rounded"
                        >✓ Couvert</span>
                        <span 
                          :class="{
                            'bg-green-100 text-green-800': lesson.status === 'completed',
                            'bg-yellow-100 text-yellow-800': lesson.status === 'pending' || lesson.status === 'confirmed',
                            'bg-red-100 text-red-800': lesson.status === 'cancelled',
                            'bg-gray-100 text-gray-800': !['completed','pending','confirmed','cancelled'].includes(lesson.status)
                          }"
                          class="px-1.5 py-0.5 text-xs rounded"
                        >{{ getLessonStatusLabel(lesson.status) }}</span>
                        <span
                          v-if="lesson.status === 'cancelled' && (lesson.cancellation_count_in_subscription !== undefined || lesson.cancellation_reason === 'medical')"
                          :class="getCancellationSubscriptionImpactClass(lesson)"
                          class="px-1.5 py-0.5 text-xs rounded"
                        >
                          {{ getCancellationSubscriptionImpact(lesson) }}
                        </span>
                      </div>
                    </div>
                    <!-- Enseignant -->
                    <div class="md:col-span-2 mb-2 md:mb-0">
                      <span class="text-xs text-gray-500 md:hidden">Enseignant</span>
                      <p class="text-gray-900">{{ lesson.teacher?.user?.name || '—' }}</p>
                    </div>
                    <!-- Statut (desktop: déjà dans Type) -->
                    <div class="hidden md:block md:col-span-1">
                      <span :class="statusLessonClass(lesson.status)">{{ getLessonStatusLabel(lesson.status) }}</span>
                    </div>
                    <!-- Prix -->
                    <div class="md:col-span-1 mb-2 md:mb-0">
                      <span class="text-xs text-gray-500 md:hidden">Prix</span>
                      <p class="text-gray-900">{{ formatPrice(lesson.price || 0) }} €</p>
                    </div>
                    <!-- Lieu -->
                    <div class="md:col-span-2 mb-2 md:mb-0">
                      <span class="text-xs text-gray-500 md:hidden">Lieu</span>
                      <p class="text-gray-700 text-sm">{{ lesson.location?.name || '—' }}</p>
                    </div>
                    <!-- Action -->
                    <div class="md:col-span-1 flex justify-end items-start gap-2 mt-2 md:mt-0">
                      <template v-if="lesson.status === 'cancelled' && lesson.cancellation_reason === 'medical' && lesson.cancellation_certificate_path">
                        <span
                          :class="{
                            'bg-amber-100 text-amber-800': lesson.cancellation_certificate_status === 'pending',
                            'bg-emerald-100 text-emerald-800': lesson.cancellation_certificate_status === 'accepted',
                            'bg-red-100 text-red-800': lesson.cancellation_certificate_status === 'rejected',
                            'bg-gray-100 text-gray-700': lesson.cancellation_certificate_status === 'closed'
                          }"
                          class="px-1.5 py-0.5 text-xs font-medium rounded"
                        >
                          {{ getCertificateStatusLabel(lesson.cancellation_certificate_status) }}
                        </span>
                        <button
                          type="button"
                          @click="downloadCertificate(lesson.id)"
                          class="px-2 py-1 text-xs text-blue-600 hover:underline"
                        >
                          Télécharger
                        </button>
                        <template v-if="lesson.cancellation_certificate_status === 'pending'">
                          <button
                            @click="acceptCertificate(lesson)"
                            :disabled="certificateActionLoading === lesson.id"
                            class="px-2 py-1 text-xs font-medium text-white bg-emerald-600 rounded hover:bg-emerald-700 disabled:opacity-50"
                          >
                            Accepter
                          </button>
                          <button
                            @click="openRejectCertificate(lesson)"
                            :disabled="certificateActionLoading === lesson.id"
                            class="px-2 py-1 text-xs font-medium text-white bg-red-600 rounded hover:bg-red-700 disabled:opacity-50"
                          >
                            Refuser
                          </button>
                        </template>
                      </template>
                      <button
                        v-if="lesson.status !== 'cancelled' || !lesson.cancellation_certificate_path"
                        @click="openEditLessonModal(lesson)"
                        class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        title="Modifier la déduction d'abonnement"
                      >
                        Modifier
                      </button>
                    </div>

                    <!-- Avertissement non couvert (mobile, sous la ligne) -->
                    <div 
                      v-if="isLessonUncovered(lesson)" 
                      class="md:col-span-12 mt-2 bg-red-100 border border-red-200 rounded p-2 text-red-800 text-sm"
                    >
                      {{ lesson.subscription_coverage?.warning || 'Cours futur non couvert par un abonnement actif.' }}
                    </div>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end">
          <button 
            @click="$emit('close')"
            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- Modale de modification du cours -->
    <div v-if="showEditLessonModal && selectedLesson" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900">
              Modifier le cours
            </h3>
            <button @click="closeEditLessonModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="space-y-4">
            <div>
              <p class="text-sm text-gray-600 mb-2">
                <strong>Cours:</strong> {{ selectedLesson.course_type?.name || 'Cours' }}
              </p>
            </div>

            <!-- Classification DCL/NDCL - uniquement si "Séance non incluse dans l'abonnement" est sélectionné -->
            <div v-if="shouldShowDclNdcl" class="border-b pb-4">
              <label class="block text-sm font-medium text-gray-700 mb-3">
                Classification pour les commissions *
              </label>
              <div class="flex gap-6">
                <div class="flex items-center">
                  <input
                    id="edit_dcl"
                    v-model="editLessonForm.est_legacy"
                    :value="false"
                    type="radio"
                    :required="shouldShowDclNdcl"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                  />
                  <label for="edit_dcl" class="ml-2 block text-sm font-medium text-gray-700">
                    DCL
                  </label>
                </div>
                <div class="flex items-center">
                  <input
                    id="edit_ndcl"
                    v-model="editLessonForm.est_legacy"
                    :value="true"
                    type="radio"
                    :required="shouldShowDclNdcl"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                  />
                  <label for="edit_ndcl" class="ml-2 block text-sm font-medium text-gray-700">
                    NDCL
                  </label>
                </div>
              </div>
              <p class="text-xs text-gray-500 mt-2">
                ⓘ Cette classification s'applique uniquement lorsque la séance n'est pas incluse dans l'abonnement
              </p>
            </div>

            <!-- Créneau -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Créneau *</label>
              <select 
                v-model="editLessonForm.slot_id"
                required
                @change="onSlotChange"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 bg-white text-gray-900">
                <option :value="null">Sélectionnez un créneau</option>
                <option v-for="slot in (openSlots || [])" :key="slot.id" :value="slot.id">
                  {{ getDayName(slot.day_of_week) }} • {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                  <template v-if="slot.discipline || slot.discipline_name">
                    • {{ slot.discipline?.name || slot.discipline_name || 'Non définie' }}
                  </template>
                </option>
              </select>
              <p v-if="editLessonForm.slot_id && currentSelectedSlot" class="text-xs text-green-600 mt-1">
                ✓ Créneau sélectionné : {{ getDayName(currentSelectedSlot.day_of_week) }} de {{ formatTime(currentSelectedSlot.start_time) }} à {{ formatTime(currentSelectedSlot.end_time) }}
              </p>
            </div>

            <!-- Date -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Date *
                <span v-if="currentSelectedSlot" class="text-xs text-blue-600 ml-2 font-medium">
                  (Uniquement les {{ getDayName(currentSelectedSlot?.day_of_week || 0) }}s)
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
                  v-model="editLessonForm.date" 
                  type="date" 
                  required
                  :min="minDate || undefined"
                  @input="validateDate"
                  :class="[
                    'flex-1 px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500',
                    editLessonForm.date && !isDateAvailable(editLessonForm.date) ? 'border-red-500 bg-red-50' : 'border-gray-300 bg-white text-gray-900'
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
              <p v-if="editLessonForm.date && !isDateAvailable(editLessonForm.date)" class="text-xs text-red-600 mt-1">
                ⚠️ Cette date doit être un {{ getDayName(currentSelectedSlot?.day_of_week || 0) }}
              </p>
              <p v-else-if="editLessonForm.date && currentSelectedSlot" class="text-xs text-green-600 mt-1">
                ✓ Date valide pour ce créneau
              </p>
              <!-- Suggestions de dates -->
              <div v-if="currentSelectedSlot && suggestedDates.length > 0" class="mt-2">
                <p class="text-xs text-gray-600 mb-1">Suggestions :</p>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="(suggestedDate, index) in suggestedDates.slice(0, 4)"
                    :key="index"
                    type="button"
                    @click="editLessonForm.date = suggestedDate"
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
                v-model="editLessonForm.time" 
                required
                :class="[
                  'w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500',
                  !availableTimes.length 
                    ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-300' 
                    : 'bg-white text-gray-900 border-gray-300'
                ]">
                <option :value="''">Sélectionnez une heure</option>
                <option v-for="time in availableTimes" :key="time.value" :value="time.value">
                  {{ time.label }}
                </option>
              </select>
              <div v-if="currentSelectedSlot && editLessonForm.date && availableTimes.length === 0" class="mt-2">
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
              <p v-else-if="currentSelectedSlot && editLessonForm.date && availableTimes.length > 0" class="text-xs text-green-600 mt-1">
                ✓ {{ availableTimes.length }} plage(s) horaire(s) disponible(s) (les plages complètes sont automatiquement masquées)
              </p>
              <p v-if="loadingLessons" class="text-xs text-gray-500 mt-1">
                🔄 Chargement des cours existants...
              </p>
            </div>

            <!-- Enseignant -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Enseignant *</label>
              <select 
                v-model="editLessonForm.teacher_id"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 bg-white text-gray-900">
                <option :value="null">Sélectionnez un enseignant</option>
                <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                  {{ teacher.user?.name || teacher.name || 'Enseignant sans nom' }}
                </option>
              </select>
            </div>

            <!-- Déduction d'abonnement -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-3">
                Déduction d'abonnement
              </label>
              <div class="space-y-2">
                <div class="flex items-center">
                  <input
                    id="edit_deduct_subscription"
                    v-model="editLessonForm.deduct_from_subscription"
                    :value="true"
                    type="radio"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                  />
                  <label for="edit_deduct_subscription" class="ml-2 block text-sm font-medium text-gray-700">
                    Déduire d'un abonnement existant
                  </label>
                </div>
                <div class="flex items-center">
                  <input
                    id="edit_no_deduct_subscription"
                    v-model="editLessonForm.deduct_from_subscription"
                    :value="false"
                    type="radio"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                  />
                  <label for="edit_no_deduct_subscription" class="ml-2 block text-sm font-medium text-gray-700">
                    Séance non incluse dans l'abonnement
                  </label>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
              <button 
                type="button" 
                @click="closeEditLessonModal"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Annuler
              </button>
              <button 
                type="button"
                @click="saveLessonChanges"
                :disabled="savingLesson"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50"
              >
                {{ savingLesson ? 'Enregistrement...' : 'Enregistrer' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modale de confirmation pour les cours d'abonnement -->
    <div v-if="showUpdateScopeModal" class="fixed inset-0 z-[60] overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 py-12">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showUpdateScopeModal = false"></div>
        
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Modifier le cours</h3>
            <p class="text-sm text-gray-500 mt-1">Ce cours fait partie d'un abonnement</p>
          </div>
          
          <div class="px-6 py-4">
            <p class="text-gray-700 mb-4">
              Souhaitez-vous appliquer ce changement d'horaire uniquement à ce cours ou à tous les cours suivants de cet abonnement ?
            </p>
            
            <div v-if="futureLessonsCount > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
              <p class="text-sm text-blue-800">
                <strong>{{ futureLessonsCount }}</strong> cours futur(s) seront affectés si vous choisissez "Tous les cours suivants".
              </p>
            </div>
            <div v-else-if="futureLessonsCount === 0 && showUpdateScopeModal" class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
              <p class="text-sm text-yellow-800">
                ⓘ Aucun cours futur trouvé pour cet abonnement. Seul ce cours sera modifié.
              </p>
            </div>
            
            <div class="space-y-3">
              <button
                @click="confirmUpdateSingleLesson"
                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-left"
              >
                <div class="font-semibold">Ce cours uniquement</div>
                <div class="text-sm text-blue-100 mt-1">Modifier uniquement ce cours</div>
              </button>
              
              <button
                @click="confirmUpdateAllFutureLessons"
                :disabled="futureLessonsCount === 0"
                :class="[
                  'w-full px-4 py-3 rounded-lg transition-colors text-left',
                  futureLessonsCount > 0
                    ? 'bg-green-600 text-white hover:bg-green-700'
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                ]"
              >
                <div class="font-semibold">Tous les cours suivants</div>
                <div class="text-sm mt-1" :class="futureLessonsCount > 0 ? 'text-green-100' : 'text-gray-400'">
                  {{ futureLessonsCount > 0 ? `Modifier ce cours et ${futureLessonsCount} cours futur(s)` : 'Aucun cours futur à modifier' }}
                </div>
              </button>
            </div>
          </div>
          
          <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
            <button
              @click="showUpdateScopeModal = false"
              class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Annuler
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modale refus certificat médical -->
  <div v-if="showRejectCertificateModal && lessonToReject" class="fixed inset-0 z-[60] bg-black bg-opacity-50 flex items-center justify-center p-4" @click.self="showRejectCertificateModal = false">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-2">Refuser le certificat médical</h3>
      <p class="text-sm text-gray-600 mb-4">
        Cours du {{ formatDateShort(lessonToReject.start_time) }} — {{ lessonToReject.course_type?.name || 'Cours' }}. Indiquez le motif de refus (obligatoire). Ce message sera envoyé à l'élève par email.
      </p>
      <textarea
        v-model="rejectCertificateReason"
        rows="3"
        maxlength="500"
        placeholder="Motif du refus (envoyé à l'élève)..."
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 text-gray-900"
      />
      <p class="text-xs text-gray-500 mt-1">{{ (rejectCertificateReason || '').length }}/500</p>
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" @click="showRejectCertificateModal = false" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
          Annuler
        </button>
        <button
          type="button"
          @click="confirmRejectCertificate"
          :disabled="!rejectCertificateReason.trim() || certificateActionLoading === lessonToReject?.id"
          class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
        >
          {{ certificateActionLoading === lessonToReject?.id ? 'Envoi...' : 'Refuser le certificat' }}
        </button>
      </div>
    </div>
  </div>

  <!-- Modale clôture demande certificat -->
  <div v-if="showCloseCertificateModal && lessonToClose" class="fixed inset-0 z-[60] bg-black bg-opacity-50 flex items-center justify-center p-4" @click.self="showCloseCertificateModal = false">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-2">Clôturer la demande de certificat</h3>
      <p class="text-sm text-gray-600 mb-4">
        Cours du {{ formatDateShort(lessonToClose.start_time) }} — {{ lessonToClose.course_type?.name || 'Cours' }}. Après clôture, l'élève ne pourra plus renvoyer de certificat pour ce cours. Vous pouvez optionnellement lui envoyer un message explicatif.
      </p>
      <textarea
        v-model="closeCertificateReason"
        rows="3"
        maxlength="500"
        placeholder="Message optionnel à l'élève (ex : demande clôturée après plusieurs échanges, certificat non conforme)..."
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-gray-500 text-gray-900"
      />
      <p class="text-xs text-gray-500 mt-1">{{ (closeCertificateReason || '').length }}/500</p>
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" @click="showCloseCertificateModal = false" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
          Annuler
        </button>
        <button
          type="button"
          @click="confirmCloseCertificate"
          :disabled="certificateActionLoading === lessonToClose?.id"
          class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-800 disabled:opacity-50"
        >
          {{ certificateActionLoading === lessonToClose?.id ? 'Envoi...' : 'Clôturer la demande' }}
        </button>
      </div>
    </div>
  </div>

  <!-- Modale de gestion des conflits de créneaux -->
  <SlotConflictModal
    :is-open="showSlotConflictModal"
    :date="editLessonForm.date"
    :time="conflictTime"
    :duration="selectedLesson?.courseType?.duration_minutes || 60"
    :teacher-id="editLessonForm.teacher_id"
    @close="closeSlotConflictModal"
    @lesson-cancelled="onLessonCancelled"
  />
</template>

<script setup>
import { ref, onMounted, watch, computed, nextTick } from 'vue'
import SlotConflictModal from '~/components/planning/SlotConflictModal.vue'
import {
  getCancellationSubscriptionImpact,
  getCancellationSubscriptionImpactClass,
  getCertificateStatusLabel,
  getCertificateStatusClass,
  shouldShowCertificateStatusBadge,
} from '~/composables/useCancellationLabels'

const props = defineProps({
  student: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'success'])

const loading = ref(false)
const error = ref(null)
const historyData = ref(null)

// Nom affiché : priorité aux données chargées (permet d'ouvrir la modale avec seulement { id } depuis ?openStudent=)
const studentDisplayName = computed(() => getStudentName(historyData.value?.student || props.student))
const showEditLessonModal = ref(false)
const selectedLesson = ref(null)
const savingLesson = ref(false)
const openSlots = ref([])
const currentSelectedSlot = ref(null)
const availableTimes = ref([])
const teachers = ref([])
const existingLessons = ref([])
const loadingLessons = ref(false)
const editLessonForm = ref({
  date: '',
  time: '',
  deduct_from_subscription: true,
  est_legacy: false,
  slot_id: null,
  teacher_id: null
})

// Variables pour la modale de confirmation de mise à jour
const showUpdateScopeModal = ref(false)
const futureLessonsCount = ref(0)
const pendingUpdatePayload = ref(null)
const updateScope = ref(null) // 'single' ou 'all_future'

// Variables pour la modale de conflit de créneaux
const showSlotConflictModal = ref(false)
const conflictTime = ref('')

// Certificat médical (accept / reject / close)
const certificateActionLoading = ref(null)
const showRejectCertificateModal = ref(false)
const lessonToReject = ref(null)
const rejectCertificateReason = ref('')
const showCloseCertificateModal = ref(false)
const lessonToClose = ref(null)
const closeCertificateReason = ref('')

// Ouvrir la modale de conflit
const openSlotConflictModal = () => {
  // Utiliser l'heure de début du créneau sélectionné si aucune heure n'est sélectionnée
  conflictTime.value = editLessonForm.value.time || currentSelectedSlot.value?.start_time?.substring(0, 5) || '09:00'
  showSlotConflictModal.value = true
}

// Fermer la modale de conflit
const closeSlotConflictModal = () => {
  showSlotConflictModal.value = false
}

// Quand un cours est annulé depuis la modale de conflit, recharger les cours existants
const onLessonCancelled = async (lessonIds) => {
  console.log('🗑️ Cours annulé(s):', lessonIds)
  // Recharger les cours existants pour mettre à jour les heures disponibles
  if (editLessonForm.value.date) {
    await loadExistingLessons()
  }
  // Fermer la modale de conflit
  closeSlotConflictModal()
  // Recharger l'historique complet aussi
  await loadHistory()
}

// Computed property pour déterminer si on doit afficher les boutons DCL/NDCL
// Les boutons DCL/NDCL ne s'affichent que si :
// - "Séance non incluse dans l'abonnement" est sélectionné
const shouldShowDclNdcl = computed(() => {
  return editLessonForm.value.deduct_from_subscription === false
})

// Helper pour obtenir le nom de l'élève (supporte student direct ou student.user depuis l'API history)
const getStudentName = (student) => {
  if (student?.name) return student.name
  if (student?.user?.name) return student.user.name
  if (student?.first_name || student?.last_name) {
    const name = ((student.first_name || '') + ' ' + (student.last_name || '')).trim()
    return name || 'Élève sans nom'
  }
  if (student?.user?.first_name || student?.user?.last_name) {
    const name = ((student.user.first_name || '') + ' ' + (student.user.last_name || '')).trim()
    return name || 'Élève sans nom'
  }
  return 'Élève sans nom'
}

// Helper pour vérifier si un cours futur n'est pas couvert par un abonnement
const isLessonUncovered = (lesson) => {
  return lesson?.subscription_coverage?.is_future === true && 
         lesson?.subscription_coverage?.is_covered === false
}

// Cours triés : d'abord ceux demandant une confirmation de certificat médical (en haut), puis par date croissante
const lessonsSortedByDate = computed(() => {
  const lessons = historyData.value?.lessons || []
  const needsCertificateConfirmation = (l) =>
    l?.status === 'cancelled' &&
    l?.cancellation_reason === 'medical' &&
    (l?.cancellation_certificate_status === 'pending' || l?.cancellation_certificate_path)
  return [...lessons].sort((a, b) => {
    const aFirst = needsCertificateConfirmation(a)
    const bFirst = needsCertificateConfirmation(b)
    if (aFirst && !bFirst) return -1
    if (!aFirst && bFirst) return 1
    const t1 = new Date(a.start_time).getTime()
    const t2 = new Date(b.start_time).getTime()
    return t1 - t2
  })
})

// Date de fin la plus proche parmi les abonnements actifs (pour affichage "fin théorique")
const subscriptionsEarliestEndFormatted = computed(() => {
  const subs = historyData.value?.subscriptions || []
  const active = subs.filter(s => s.status === 'active' && s.expires_at)
  if (active.length === 0) return null
  const earliest = active.reduce((min, s) => {
    const d = new Date(s.expires_at).getTime()
    return d < min ? d : min
  }, Infinity)
  if (earliest === Infinity) return null
  return new Date(earliest).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
})

// Cours annulés pour raison médicale avec certificat (pour le bloc validation)
const lessonsWithCertificate = computed(() => {
  const lessons = historyData.value?.lessons || []
  return lessons
    .filter(l => l.status === 'cancelled' && l.cancellation_reason === 'medical' && l.cancellation_certificate_path)
    .sort((a, b) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime())
})

// Télécharger le certificat (appel API avec blob)
const downloadCertificate = async (lessonId) => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get(`/club/lessons/${lessonId}/cancellation-certificate/download`, { responseType: 'blob' })
    const blob = response.data
    const contentType = response.headers?.['content-type'] || ''
    if (contentType.includes('application/json')) {
      const text = await blob.text()
      const json = JSON.parse(text)
      const { error: showError } = useToast()
      showError(json?.message || 'Erreur lors du téléchargement')
      return
    }
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `certificat_lesson_${lessonId}.pdf`
    a.click()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Erreur téléchargement certificat:', err)
    const { error: showError } = useToast()
    let msg = 'Erreur lors du téléchargement'
    const data = err.response?.data
    if (data) {
      if (data instanceof Blob) {
        try {
          const text = await data.text()
          const json = JSON.parse(text)
          if (json?.message) msg = json.message
        } catch (_) { /* ignore */ }
      } else if (typeof data?.message === 'string') {
        msg = data.message
      }
    } else if (err.response?.status === 404) {
      msg = 'Fichier introuvable. Le certificat n\'est peut-être plus disponible sur ce serveur.'
    }
    showError(msg)
  }
}

const acceptCertificate = async (lesson) => {
  try {
    certificateActionLoading.value = lesson.id
    const { $api } = useNuxtApp()
    const { success: showSuccess, error: showError } = useToast()
    const response = await $api.post(`/club/lessons/${lesson.id}/cancellation-certificate/accept`)
    if (response.data?.success) {
      showSuccess(response.data.message || 'Certificat accepté.')
      await loadHistory()
    } else {
      showError(response.data?.message || 'Erreur.')
    }
  } catch (err) {
    const { error: showError } = useToast()
    showError(err.response?.data?.message || 'Erreur lors de l\'acceptation.')
  } finally {
    certificateActionLoading.value = null
  }
}

const openRejectCertificate = (lesson) => {
  lessonToReject.value = lesson
  rejectCertificateReason.value = ''
  showRejectCertificateModal.value = true
}

const confirmRejectCertificate = async () => {
  if (!lessonToReject.value || !rejectCertificateReason.value.trim()) return
  try {
    certificateActionLoading.value = lessonToReject.value.id
    const { $api } = useNuxtApp()
    const { success: showSuccess, error: showError } = useToast()
    const response = await $api.post(`/club/lessons/${lessonToReject.value.id}/cancellation-certificate/reject`, {
      rejection_reason: rejectCertificateReason.value.trim()
    })
    if (response.data?.success) {
      showSuccess(response.data.message || 'Certificat refusé.')
      showRejectCertificateModal.value = false
      lessonToReject.value = null
      rejectCertificateReason.value = ''
      await loadHistory()
    } else {
      showError(response.data?.message || 'Erreur.')
    }
  } catch (err) {
    const { error: showError } = useToast()
    showError(err.response?.data?.message || err.response?.data?.errors?.rejection_reason?.[0] || 'Erreur lors du refus.')
  } finally {
    certificateActionLoading.value = null
  }
}

const openCloseCertificate = (lesson) => {
  lessonToClose.value = lesson
  closeCertificateReason.value = ''
  showCloseCertificateModal.value = true
}

const confirmCloseCertificate = async () => {
  if (!lessonToClose.value) return
  try {
    certificateActionLoading.value = lessonToClose.value.id
    const { $api } = useNuxtApp()
    const { success: showSuccess, error: showError } = useToast()
    const response = await $api.post(`/club/lessons/${lessonToClose.value.id}/cancellation-certificate/close`, {
      close_reason: closeCertificateReason.value?.trim() || null
    })
    if (response.data?.success) {
      showSuccess(response.data.message || 'Demande clôturée.')
      showCloseCertificateModal.value = false
      lessonToClose.value = null
      closeCertificateReason.value = ''
      await loadHistory()
    } else {
      showError(response.data?.message || 'Erreur.')
    }
  } catch (err) {
    const { error: showError } = useToast()
    showError(err.response?.data?.message || 'Erreur lors de la clôture.')
  } finally {
    certificateActionLoading.value = null
  }
}

// Charger l'historique
const loadHistory = async () => {
  try {
    loading.value = true
    error.value = null
    
    const { $api } = useNuxtApp()
    const response = await $api.get(`/club/students/${props.student.id}/history`)
    
    if (response.data.success) {
      historyData.value = response.data.data
    } else {
      error.value = response.data.message || 'Erreur lors du chargement de l\'historique'
    }
  } catch (err) {
    console.error('Erreur chargement historique:', err)
    error.value = err.response?.data?.message || 'Erreur lors du chargement de l\'historique'
  } finally {
    loading.value = false
  }
}

// Formatters
const formatDate = (date) => {
  if (!date) return 'Non définie'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (dateTime) => {
  if (!dateTime) return 'Non définie'
  return new Date(dateTime).toLocaleString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDateShort = (dateTime) => {
  if (!dateTime) return '—'
  return new Date(dateTime).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

const formatWeekday = (dateTime) => {
  if (!dateTime) return ''
  return new Date(dateTime).toLocaleDateString('fr-FR', { weekday: 'long' })
}

const formatTimeOnly = (dateTime) => {
  if (!dateTime) return '—'
  return new Date(dateTime).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

const getLessonStatusLabel = (status) => {
  const labels = { pending: 'En attente', confirmed: 'Confirmé', completed: 'Terminé', cancelled: 'Annulé' }
  return labels[status] || status || '—'
}

const statusLessonClass = (status) => {
  const base = 'text-xs font-medium px-2 py-0.5 rounded'
  const map = {
    completed: 'bg-green-100 text-green-800',
    confirmed: 'bg-blue-100 text-blue-800',
    pending: 'bg-yellow-100 text-yellow-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return `${base} ${map[status] || 'bg-gray-100 text-gray-800'}`
}

const formatPrice = (price) => {
  return parseFloat(price || 0).toFixed(2)
}

const getStatusLabel = (status) => {
  const labels = {
    active: 'Actif',
    completed: 'Terminé',
    expired: 'Expiré',
    cancelled: 'Annulé'
  }
  return labels[status] || status
}

// Charger les créneaux disponibles
const loadOpenSlots = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/open-slots')
    if (response.data.success) {
      openSlots.value = response.data.data || []
    }
  } catch (err) {
    console.error('Erreur chargement créneaux:', err)
  }
}

// Helper pour obtenir le nom du jour
const getDayName = (dayOfWeek) => {
  const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
  return days[dayOfWeek] || ''
}

// Helper pour formater l'heure
const formatTime = (time) => {
  if (!time) return ''
  return time.substring(0, 5) // Retourne HH:mm
}

// Fonction appelée quand le créneau change
const onSlotChange = () => {
  if (!editLessonForm.value.slot_id || !openSlots.value) return
  
  const slot = openSlots.value.find(s => s.id === editLessonForm.value.slot_id)
  if (slot) {
    currentSelectedSlot.value = slot
    updateAvailableTimes()
    
    // Si une date est déjà sélectionnée, vérifier qu'elle correspond au jour du créneau
    if (editLessonForm.value.date) {
      const date = new Date(editLessonForm.value.date + 'T00:00:00')
      const dayOfWeek = date.getDay()
      if (dayOfWeek !== slot.day_of_week) {
        // Trouver la prochaine date correspondant au jour du créneau
        const today = new Date()
        let daysToAdd = slot.day_of_week - today.getDay()
        if (daysToAdd < 0) daysToAdd += 7
        const nextDate = new Date(today)
        nextDate.setDate(today.getDate() + daysToAdd)
        editLessonForm.value.date = nextDate.toISOString().split('T')[0]
      }
    }
  }
}

// Mettre à jour les heures disponibles
const updateAvailableTimes = async () => {
  if (!currentSelectedSlot.value || !editLessonForm.value.date) {
    availableTimes.value = []
    return
  }
  
  // Charger les cours existants pour cette date
  await loadExistingLessons(editLessonForm.value.date)
  
  const slot = currentSelectedSlot.value
  const slotStart = slot.start_time?.substring(0, 5) || '09:00'
  const slotEnd = slot.end_time?.substring(0, 5) || '18:00'
  
  const slotStartMinutes = timeToMinutes(slotStart)
  const slotEndMinutes = timeToMinutes(slotEnd)
  
  // Utiliser la durée du cours existant si disponible, sinon 60 minutes par défaut
  const duration = selectedLesson.value?.course_type?.duration_minutes || 
                   (selectedLesson.value?.end_time && selectedLesson.value?.start_time 
                     ? Math.round((new Date(selectedLesson.value.end_time) - new Date(selectedLesson.value.start_time)) / (1000 * 60))
                     : 60)
  
  // Générer toutes les heures possibles dans le créneau
  const allTimes = []
  for (let minutes = slotStartMinutes; minutes + duration <= slotEndMinutes; minutes += duration) {
    allTimes.push({
      value: minutesToTime(minutes),
      label: minutesToTime(minutes),
      minutes
    })
  }
  
  // Filtrer les heures qui sont déjà complètes (max_slots atteint)
  const maxSlots = slot.max_slots || 1
  
  const available = allTimes.filter(time => {
    // Vérifier combien de cours se chevauchent avec cette heure
    const timeStart = new Date(`${editLessonForm.value.date}T${time.value}:00`)
    const timeEnd = new Date(timeStart.getTime() + duration * 60000)
    
    let overlappingCount = 0
    
    for (const lesson of existingLessons.value) {
      // Exclure le cours en cours d'édition
      if (selectedLesson.value && lesson.id === selectedLesson.value.id) {
        continue
      }
      
      if (lesson.status === 'cancelled') continue
      
      const lessonStart = new Date(lesson.start_time)
      let lessonEnd
      
      // Calculer la fin du cours existant
      if (lesson.end_time) {
        lessonEnd = new Date(lesson.end_time)
      } else if (lesson.course_type?.duration_minutes) {
        lessonEnd = new Date(lessonStart.getTime() + lesson.course_type.duration_minutes * 60000)
      } else {
        lessonEnd = new Date(lessonStart.getTime() + 60 * 60000) // 60 min par défaut
      }
      
      // Vérifier le chevauchement
      if (timeStart < lessonEnd && timeEnd > lessonStart) {
        overlappingCount++
      }
    }
    
    // L'heure est disponible si le nombre de cours qui se chevauchent est strictement inférieur à max_slots
    return overlappingCount < maxSlots
  })
  
  availableTimes.value = available
}

// Charger les enseignants
const loadTeachers = async () => {
  try {
    const { $api } = useNuxtApp()
    const response = await $api.get('/club/teachers')
    if (response.data.success) {
      teachers.value = response.data.teachers || []
    }
  } catch (err) {
    console.error('Erreur chargement enseignants:', err)
  }
}

// Fonction pour charger les cours existants pour une date donnée
async function loadExistingLessons(date) {
  const slotToUse = currentSelectedSlot.value
  if (!date || !slotToUse) {
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
function timeToMinutes(time) {
  const [hours, minutes] = time.split(':').map(Number)
  return hours * 60 + minutes
}

// Convertir des minutes depuis minuit en heure (HH:MM)
function minutesToTime(minutes) {
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`
}

// Date minimale (aujourd'hui)
const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

// Vérifier si une date est disponible pour le créneau sélectionné
function isDateAvailable(date) {
  if (!date || !currentSelectedSlot.value) return true
  
  const selectedDate = new Date(date + 'T00:00:00')
  const dayOfWeek = selectedDate.getDay()
  
  return dayOfWeek === currentSelectedSlot.value.day_of_week
}

// Valider la date
function validateDate() {
  if (editLessonForm.value.date && currentSelectedSlot.value) {
    if (!isDateAvailable(editLessonForm.value.date)) {
      // Trouver la prochaine date valide
      const today = new Date()
      const targetDayOfWeek = currentSelectedSlot.value.day_of_week
      let daysToAdd = targetDayOfWeek - today.getDay()
      if (daysToAdd < 0) daysToAdd += 7
      const nextDate = new Date(today)
      nextDate.setDate(today.getDate() + daysToAdd)
      editLessonForm.value.date = nextDate.toISOString().split('T')[0]
    }
  }
}

// Navigation de date
function canNavigateDate(direction) {
  if (!currentSelectedSlot.value) return false
  
  const today = new Date()
  const targetDayOfWeek = currentSelectedSlot.value.day_of_week
  
  if (direction === -1) {
    // Date précédente : trouver le dernier jour correspondant avant aujourd'hui
    let daysToSubtract = today.getDay() - targetDayOfWeek
    if (daysToSubtract <= 0) daysToSubtract += 7
    const prevDate = new Date(today)
    prevDate.setDate(today.getDate() - daysToSubtract)
    return prevDate >= new Date(minDate.value)
  } else {
    // Date suivante : toujours possible
    return true
  }
}

function navigateDate(direction) {
  if (!currentSelectedSlot.value) return
  
  const today = new Date()
  const targetDayOfWeek = currentSelectedSlot.value.day_of_week
  let currentDate = editLessonForm.value.date ? new Date(editLessonForm.value.date + 'T00:00:00') : today
  
  if (direction === -1) {
    // Date précédente
    let daysToSubtract = currentDate.getDay() - targetDayOfWeek
    if (daysToSubtract <= 0) daysToSubtract += 7
    const prevDate = new Date(currentDate)
    prevDate.setDate(currentDate.getDate() - daysToSubtract)
    if (prevDate >= new Date(minDate.value)) {
      editLessonForm.value.date = prevDate.toISOString().split('T')[0]
    }
  } else {
    // Date suivante
    let daysToAdd = targetDayOfWeek - currentDate.getDay()
    if (daysToAdd <= 0) daysToAdd += 7
    const nextDate = new Date(currentDate)
    nextDate.setDate(currentDate.getDate() + daysToAdd)
    editLessonForm.value.date = nextDate.toISOString().split('T')[0]
  }
}

// Générer des suggestions de dates
const suggestedDates = computed(() => {
  if (!currentSelectedSlot.value) return []
  
  const today = new Date()
  const targetDayOfWeek = currentSelectedSlot.value.day_of_week
  const suggestions = []
  
  // Prochaine date correspondant au jour
  let daysToAdd = targetDayOfWeek - today.getDay()
  if (daysToAdd < 0) daysToAdd += 7
  if (daysToAdd === 0) daysToAdd = 7 // Si c'est aujourd'hui, prendre la semaine prochaine
  
  for (let i = 0; i < 4; i++) {
    const date = new Date(today)
    date.setDate(today.getDate() + daysToAdd + (i * 7))
    if (date >= new Date(minDate.value)) {
      suggestions.push(date.toISOString().split('T')[0])
    }
  }
  
  return suggestions
})

// Formater une date suggérée
function formatSuggestedDate(dateStr) {
  const date = new Date(dateStr + 'T00:00:00')
  const today = new Date()
  const diffTime = date - today
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return "Aujourd'hui"
  if (diffDays === 1) return "Demain"
  if (diffDays === 7) return "Dans 1 semaine"
  if (diffDays === 14) return "Dans 2 semaines"
  
  return date.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' })
}

const openEditLessonModal = async (lesson) => {
  selectedLesson.value = lesson
  
  // Charger les créneaux et enseignants si pas encore chargés
  if (openSlots.value.length === 0) {
    await loadOpenSlots()
  }
  if (teachers.value.length === 0) {
    await loadTeachers()
  }
  
  // Extraire la date et l'heure depuis start_time
  if (lesson.start_time) {
    const dateTime = new Date(lesson.start_time)
    editLessonForm.value.date = dateTime.toISOString().split('T')[0]
    const hours = String(dateTime.getHours()).padStart(2, '0')
    const minutes = String(dateTime.getMinutes()).padStart(2, '0')
    editLessonForm.value.time = `${hours}:${minutes}`
    
    // Trouver le créneau correspondant au jour de la semaine
    const dayOfWeek = dateTime.getDay()
    const matchingSlot = openSlots.value.find(slot => slot.day_of_week === dayOfWeek)
    if (matchingSlot) {
      editLessonForm.value.slot_id = matchingSlot.id
      currentSelectedSlot.value = matchingSlot
      await loadExistingLessons(editLessonForm.value.date)
      updateAvailableTimes()
    }
  } else {
    editLessonForm.value.date = ''
    editLessonForm.value.time = ''
    editLessonForm.value.slot_id = null
  }
  
  // Enseignant
  editLessonForm.value.teacher_id = lesson.teacher?.id || null
  
  // Déduction d'abonnement
  editLessonForm.value.deduct_from_subscription = lesson.subscription_instances && lesson.subscription_instances.length > 0
  
  // DCL/NDCL : initialiser selon le choix de déduction
  if (editLessonForm.value.deduct_from_subscription) {
    // Si "Déduire d'un abonnement existant" est sélectionné, utiliser la valeur de l'abonnement
    if (lesson.subscription_instances && lesson.subscription_instances.length > 0) {
      const subscriptionInstance = lesson.subscription_instances[0]
      if (subscriptionInstance.est_legacy !== undefined && subscriptionInstance.est_legacy !== null) {
        editLessonForm.value.est_legacy = Boolean(subscriptionInstance.est_legacy)
      } else {
        editLessonForm.value.est_legacy = null
      }
    } else {
      editLessonForm.value.est_legacy = null
    }
  } else {
    // Si "Séance non incluse dans l'abonnement" est sélectionné, utiliser la valeur du cours (sera modifiable via les boutons DCL/NDCL)
    editLessonForm.value.est_legacy = lesson.est_legacy !== undefined ? Boolean(lesson.est_legacy) : false
  }
  
  showEditLessonModal.value = true
}

// Watcher pour mettre à jour les heures disponibles quand la date change
watch(() => editLessonForm.value.date, async () => {
  await updateAvailableTimes()
})

// Watcher pour mettre à jour le créneau quand la date change
watch(() => editLessonForm.value.date, async (newDate) => {
  if (newDate && openSlots.value.length > 0) {
    const date = new Date(newDate + 'T00:00:00')
    const dayOfWeek = date.getDay()
    const matchingSlot = openSlots.value.find(slot => slot.day_of_week === dayOfWeek)
    if (matchingSlot && (!editLessonForm.value.slot_id || editLessonForm.value.slot_id !== matchingSlot.id)) {
      editLessonForm.value.slot_id = matchingSlot.id
      currentSelectedSlot.value = matchingSlot
      await updateAvailableTimes()
    }
  }
})

const closeEditLessonModal = () => {
  showEditLessonModal.value = false
  selectedLesson.value = null
  currentSelectedSlot.value = null
  availableTimes.value = []
  editLessonForm.value = {
    date: '',
    time: '',
    deduct_from_subscription: true,
    est_legacy: false,
    slot_id: null
  }
}

// Vérifier si l'horaire a changé
const hasTimeChanged = () => {
  if (!selectedLesson.value) return false
  
  const originalDate = new Date(selectedLesson.value.start_time)
  const originalDateStr = originalDate.toISOString().split('T')[0]
  const originalHours = String(originalDate.getHours()).padStart(2, '0')
  const originalMinutes = String(originalDate.getMinutes()).padStart(2, '0')
  const originalTimeStr = `${originalHours}:${originalMinutes}`
  
  return editLessonForm.value.date !== originalDateStr || editLessonForm.value.time !== originalTimeStr
}

// Charger le nombre de cours futurs de l'abonnement
const loadFutureLessonsCount = async () => {
  console.log('🔍 [loadFutureLessonsCount] Début du chargement', {
    hasLesson: !!selectedLesson.value,
    hasSubscriptionInstances: !!(selectedLesson.value?.subscription_instances),
    subscriptionInstancesCount: selectedLesson.value?.subscription_instances?.length || 0,
    subscriptionInstances: selectedLesson.value?.subscription_instances
  })
  
  if (!selectedLesson.value || !selectedLesson.value.subscription_instances || selectedLesson.value.subscription_instances.length === 0) {
    console.log('⚠️ [loadFutureLessonsCount] Aucune instance d\'abonnement trouvée')
    futureLessonsCount.value = 0
    return
  }
  
  try {
    const { $api } = useNuxtApp()
    const subscriptionInstanceId = selectedLesson.value.subscription_instances[0].id
    const currentLessonDate = new Date(selectedLesson.value.start_time)
    
    console.log('📅 [loadFutureLessonsCount] Paramètres', {
      subscriptionInstanceId,
      currentLessonDate: currentLessonDate.toISOString().split('T')[0],
      startTime: selectedLesson.value.start_time
    })
    
    // Récupérer les cours futurs de cet abonnement (avec le préfixe /club)
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
  } catch (err) {
    console.error('❌ [loadFutureLessonsCount] Erreur chargement cours futurs:', err)
    console.error('❌ [loadFutureLessonsCount] Détails erreur:', {
      message: err.message,
      response: err.response?.data,
      status: err.response?.status
    })
    futureLessonsCount.value = 0
  }
}

const saveLessonChanges = async () => {
  if (!selectedLesson.value) return
  
  // Validation
  if (!editLessonForm.value.date || !editLessonForm.value.time) {
    const { error: showError } = useToast()
    showError('Veuillez remplir la date et l\'heure')
    return
  }
  
  // Construire start_time depuis date et time (format local pour éviter les problèmes de timezone)
  const dateStr = editLessonForm.value.date
  const timeStr = editLessonForm.value.time
  const startTime = `${dateStr}T${timeStr}:00`
  
  // Calculer end_time (utiliser la durée du cours existant)
  const lessonStart = new Date(selectedLesson.value.start_time)
  const lessonEnd = selectedLesson.value.end_time ? new Date(selectedLesson.value.end_time) : null
  const duration = lessonEnd ? Math.round((lessonEnd.getTime() - lessonStart.getTime()) / (1000 * 60)) : 60 // Durée en minutes
  
  // Créer les dates en utilisant le format local pour éviter les problèmes de timezone
  const newStartTime = new Date(startTime)
  const newEndTime = new Date(newStartTime.getTime() + duration * 60000)
  
  // Formater end_time manuellement au format YYYY-MM-DD HH:mm:ss (avec espace)
  const endYear = newEndTime.getFullYear()
  const endMonth = String(newEndTime.getMonth() + 1).padStart(2, '0')
  const endDay = String(newEndTime.getDate()).padStart(2, '0')
  const endHours = String(newEndTime.getHours()).padStart(2, '0')
  const endMinutes = String(newEndTime.getMinutes()).padStart(2, '0')
  const endSeconds = String(newEndTime.getSeconds()).padStart(2, '0')
  const endTimeFormatted = `${endYear}-${endMonth}-${endDay} ${endHours}:${endMinutes}:${endSeconds}`
  
  // Mettre à jour le cours avec toutes les modifications
  const updatePayload = {
    start_time: startTime,
    end_time: endTimeFormatted,
    est_legacy: editLessonForm.value.est_legacy,
    deduct_from_subscription: editLessonForm.value.deduct_from_subscription,
    teacher_id: editLessonForm.value.teacher_id
  }
  
  // Vérifier si le cours fait partie d'un abonnement et si l'horaire a changé
  const isPartOfSubscription = selectedLesson.value.subscription_instances && 
                               selectedLesson.value.subscription_instances.length > 0
  const timeChanged = hasTimeChanged()
  
  // Si le cours fait partie d'un abonnement et que l'horaire a changé, demander confirmation
  if (isPartOfSubscription && timeChanged) {
    pendingUpdatePayload.value = updatePayload
    await loadFutureLessonsCount()
    showUpdateScopeModal.value = true
    return
  }
  
  // Sinon, mettre à jour directement
  await performUpdate(updatePayload, 'single')
}

// Confirmer la mise à jour pour ce cours uniquement
const confirmUpdateSingleLesson = async () => {
  showUpdateScopeModal.value = false
  updateScope.value = 'single'
  await performUpdate(pendingUpdatePayload.value, 'single')
}

// Confirmer la mise à jour pour tous les cours futurs
const confirmUpdateAllFutureLessons = async () => {
  if (futureLessonsCount.value === 0) return
  
  showUpdateScopeModal.value = false
  updateScope.value = 'all_future'
  await performUpdate(pendingUpdatePayload.value, 'all_future')
}

// Effectuer la mise à jour
const performUpdate = async (updatePayload, scope) => {
  if (!selectedLesson.value) return
  
  try {
    savingLesson.value = true
    const { $api } = useNuxtApp()
    const { success: showSuccess, error: showError } = useToast()
    
    // Ajouter le scope à la payload
    const payloadWithScope = {
      ...updatePayload,
      update_scope: scope // 'single' ou 'all_future'
    }
    
    // Mettre à jour le cours
    const updateResponse = await $api.put(`/lessons/${selectedLesson.value.id}`, payloadWithScope)
    
    if (!updateResponse.data.success) {
      showError(updateResponse.data.message || 'Erreur lors de la modification')
      return
    }
    
    const message = scope === 'all_future' 
      ? `Cours modifié avec succès. ${futureLessonsCount.value} cours futur(s) ont également été mis à jour.`
      : 'Cours modifié avec succès'
    
    showSuccess(message)
    await loadHistory()
    closeEditLessonModal()
    
    // Réinitialiser les variables
    pendingUpdatePayload.value = null
    updateScope.value = null
    futureLessonsCount.value = 0
  } catch (err) {
    console.error('Erreur modification cours:', err)
    const { error: showError } = useToast()
    showError(err.response?.data?.message || 'Erreur lors de la modification')
  } finally {
    savingLesson.value = false
  }
}

const getTotalLessons = (subscription) => {
  const template = subscription.subscription?.template
  if (template) {
    return (template.total_lessons || 0) + (template.free_lessons || 0)
  }
  return subscription.subscription?.total_lessons || 0
}

// Charger l'historique, les créneaux et les enseignants quand le composant est monté
onMounted(() => {
  loadHistory()
  loadOpenSlots()
  loadTeachers()
})

// Recharger si le student change
watch(() => props.student, () => {
  loadHistory()
}, { immediate: true })

// Gérer est_legacy selon le choix de déduction d'abonnement
watch(() => editLessonForm.value.deduct_from_subscription, (newValue) => {
  if (newValue === true) {
    // Si "Déduire d'un abonnement existant" est sélectionné, utiliser la valeur de l'abonnement
    if (selectedLesson.value && selectedLesson.value.subscription_instances && selectedLesson.value.subscription_instances.length > 0) {
      const subscriptionInstance = selectedLesson.value.subscription_instances[0]
      // Utiliser la valeur est_legacy de l'abonnement si disponible
      if (subscriptionInstance.est_legacy !== undefined && subscriptionInstance.est_legacy !== null) {
        editLessonForm.value.est_legacy = Boolean(subscriptionInstance.est_legacy)
        console.log('🔄 [StudentHistoryModal] est_legacy récupéré depuis l\'abonnement:', editLessonForm.value.est_legacy)
      } else {
        // Si l'abonnement n'a pas de valeur définie, mettre à null pour que le backend le définisse
        editLessonForm.value.est_legacy = null
        console.log('🔄 [StudentHistoryModal] est_legacy mis à null (sera défini par le backend)')
      }
    } else {
      // Pas d'abonnement associé, mettre à null
      editLessonForm.value.est_legacy = null
      console.log('🔄 [StudentHistoryModal] est_legacy mis à null (pas d\'abonnement associé)')
    }
  } else {
    // Si "Séance non incluse dans l'abonnement" est sélectionné, on garde la valeur actuelle (ou on la laisse modifier)
    // La valeur sera modifiable via les boutons DCL/NDCL
    console.log('🔄 [StudentHistoryModal] Séance non incluse dans l\'abonnement - DCL/NDCL modifiable')
  }
})
</script>

