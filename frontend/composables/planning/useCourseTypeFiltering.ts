/**
 * Composable pour le filtrage des types de cours
 * Filtre les types de cours selon les disciplines du club
 */

export const useCourseTypeFiltering = () => {
  /**
   * Filtre les types de cours d'un créneau selon les disciplines du club
   * @param slotCourseTypes - Types de cours assignés au créneau
   * @param clubDisciplineIds - IDs des disciplines du club
   * @returns Types de cours filtrés
   */
  const filterCourseTypesByClubDisciplines = (
    slotCourseTypes: any[],
    clubDisciplineIds: number[]
  ): any[] => {
    if (!slotCourseTypes || slotCourseTypes.length === 0) {
      console.warn('⚠️ [useCourseTypeFiltering] Aucun type de cours fourni')
      return []
    }

    // Filtrer : garder les types génériques OU ceux qui correspondent aux disciplines du club
    const filtered = slotCourseTypes.filter((courseType) => {
      // Type générique (pas de discipline) : toujours garder
      if (!courseType.discipline_id) return true

      // Type avec discipline : garder seulement si dans les disciplines du club
      const matchesClub = clubDisciplineIds.includes(courseType.discipline_id)

      if (!matchesClub) {
        console.debug(
          `🔍 [useCourseTypeFiltering] Type "${courseType.name}" (disc:${courseType.discipline_id}) filtré car ne correspond pas aux disciplines du club [${clubDisciplineIds.join(', ')}]`
        )
      }

      return matchesClub
    })

    console.log('✅ [useCourseTypeFiltering] Filtrage appliqué:', {
      totalAvant: slotCourseTypes.length,
      totalApres: filtered.length,
      clubDisciplines: clubDisciplineIds,
      types: filtered.map((t) => `${t.name} (disc:${t.discipline_id || 'N/A'})`)
    })

    return filtered
  }

  /**
   * Vérifie si un type de cours correspond aux disciplines du club
   * @param courseType - Type de cours à vérifier
   * @param clubDisciplineIds - IDs des disciplines du club
   * @returns true si le type correspond
   */
  const courseTypeMatchesClub = (
    courseType: any,
    clubDisciplineIds: number[]
  ): boolean => {
    // Type générique : toujours OK
    if (!courseType.discipline_id) return true

    // Type avec discipline : vérifier si dans le club
    return clubDisciplineIds.includes(courseType.discipline_id)
  }

  /**
   * Filtre les types de cours disponibles pour un club
   * (Utilisé pour afficher tous les types lors de la configuration des créneaux)
   * @param allCourseTypes - Tous les types de cours
   * @param clubDisciplineIds - IDs des disciplines du club
   * @returns Types de cours filtrés
   */
  const filterAllCourseTypesForClub = (
    allCourseTypes: any[],
    clubDisciplineIds: number[]
  ): any[] => {
    if (!allCourseTypes || allCourseTypes.length === 0) return []

    if (clubDisciplineIds.length === 0) {
      console.warn('⚠️ [useCourseTypeFiltering] Aucune discipline configurée pour le club')
      // Retourner uniquement les types génériques
      return allCourseTypes.filter((type) => !type.discipline_id)
    }

    return allCourseTypes.filter(
      (type) => !type.discipline_id || clubDisciplineIds.includes(type.discipline_id)
    )
  }

  return {
    filterCourseTypesByClubDisciplines,
    courseTypeMatchesClub,
    filterAllCourseTypesForClub
  }
}

