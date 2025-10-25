/**
 * Composable pour gérer la modale de création de cours
 * Centralise la logique d'ouverture/fermeture et de gestion des données
 */

import { ref } from 'vue'

export const useLessonModal = () => {
  // État de la modale
  const showModal = ref(false)
  const modalData = ref({
    date: '',
    time: '',
    slot: null as any
  })

  /**
   * Ouvre la modale de création de cours
   * @param date - Date du cours (format YYYY-MM-DD)
   * @param time - Heure de début (format HH:MM)
   * @param slot - Créneau sélectionné
   */
  const openModal = (date: string, time: string, slot: any) => {
    modalData.value = {
      date,
      time,
      slot
    }
    showModal.value = true

    console.log('📖 [useLessonModal] Modale ouverte:', {
      date,
      time,
      slotId: slot?.id,
      courseTypesCount: slot?.course_types?.length || 0
    })
  }

  /**
   * Ferme la modale
   */
  const closeModal = () => {
    showModal.value = false
    
    // Reset après un délai pour éviter les animations bizarres
    setTimeout(() => {
      modalData.value = {
        date: '',
        time: '',
        slot: null
      }
    }, 300)

    console.log('✖️ [useLessonModal] Modale fermée')
  }

  /**
   * Réinitialise complètement la modale
   */
  const resetModal = () => {
    closeModal()
  }

  return {
    showModal,
    modalData,
    openModal,
    closeModal,
    resetModal
  }
}

