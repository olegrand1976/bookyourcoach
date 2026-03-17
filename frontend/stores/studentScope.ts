import { defineStore } from 'pinia'

export interface LinkedAccount {
  id: number
  name: string
  email: string | null
  user_id: number
  is_active: boolean
  is_primary: boolean
}

export const useStudentScopeStore = defineStore('studentScope', {
  state: () => ({
    linkedAccounts: [] as LinkedAccount[],
    /** null = vue globale (défaut), number = id d'un élève pour vue par élève */
    activeStudentId: null as number | null,
    loaded: false
  }),

  getters: {
    hasMultipleStudents: (state) => state.linkedAccounts.length > 1,
    /** Paramètre à envoyer aux API (query) : 'all' ou l'id élève */
    apiScopeParam (state): 'all' | number {
      if (state.activeStudentId !== null) return state.activeStudentId
      return 'all'
    },
    activeAccount (state): LinkedAccount | null {
      if (state.activeStudentId === null) return null
      return state.linkedAccounts.find(a => a.id === state.activeStudentId) ?? null
    }
  },

  actions: {
    async loadLinkedAccounts () {
      const { $api } = useNuxtApp()
      try {
        const response = await $api.get<{ success: boolean; data?: LinkedAccount[] }>('/student/linked-accounts')
        if (response.data.success && response.data.data) {
          this.linkedAccounts = response.data.data
          this.loaded = true
          if (this.linkedAccounts.length > 1 && this.activeStudentId !== null) {
            const stillLinked = this.linkedAccounts.some(a => a.id === this.activeStudentId)
            if (!stillLinked) this.activeStudentId = null
          }
        }
      } catch (e) {
        console.error('Erreur chargement comptes liés:', e)
        this.loaded = true
      }
    },

    setScope (studentId: number | null) {
      this.activeStudentId = studentId
    },

    /** Réinitialiser à la vue globale (tous les élèves) */
    setGlobalView () {
      this.activeStudentId = null
    }
  }
})
