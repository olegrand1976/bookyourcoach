<template>
    <div>
        <!-- Hero Section -->
        <section class="bg-gradient-to-br from-blue-600 to-gray-800 text-white py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-4xl md:text-5xl font-bold mb-6 font-serif">
                        🐎 Nos Professeurs
                    </h1>
                    <p class="text-xl text-gray-100 max-w-3xl mx-auto">
                        Découvrez nos instructeurs qualifiés et passionnés
                    </p>
                </div>
            </div>
        </section>

        <!-- Filtres et Recherche -->
        <section class="py-8 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <!-- Barre de recherche -->
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <input v-model="searchQuery" type="text" placeholder="Rechercher un professeur..."
                                class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-equestrian-brown focus:border-blue-600">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <Icon name="heroicons:magnifying-glass" class="h-5 w-5 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    <!-- Filtres -->
                    <div class="flex gap-4">
                        <select v-model="selectedDiscipline"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-equestrian-brown focus:border-blue-600">
                            <option value="">Toutes les disciplines</option>
                            <option value="dressage">Dressage</option>
                            <option value="jumping">Saut d'obstacles</option>
                            <option value="cross">Cross</option>
                            <option value="pony_games">Jeux de poneys</option>
                            <option value="western">Western</option>
                        </select>

                        <select v-model="selectedLevel"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-equestrian-brown focus:border-blue-600">
                            <option value="">Tous les niveaux</option>
                            <option value="beginner">Débutant</option>
                            <option value="intermediate">Intermédiaire</option>
                            <option value="advanced">Avancé</option>
                            <option value="competition">Compétition</option>
                        </select>
                    </div>
                </div>
            </div>
        </section>

        <!-- Liste des Instructeurs -->
        <section class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Loading State -->
                <div v-if="pending" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="i in 6" :key="i" class="animate-pulse">
                        <div class="bg-gray-200 rounded-lg h-64"></div>
                    </div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="text-center py-12">
                    <div class="text-red-600 mb-4">
                        <Icon name="heroicons:exclamation-triangle" class="h-12 w-12 mx-auto mb-4" />
                        <h3 class="text-lg font-semibold">Erreur de chargement</h3>
                        <p class="text-gray-600">{{ error.message }}</p>
                    </div>
                    <button @click="refresh()" class="btn-primary bg-blue-600 text-white">
                        Réessayer
                    </button>
                </div>

                <!-- Teachers Grid -->
                <div v-else-if="filteredTeachers.length > 0"
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="teacher in filteredTeachers" :key="teacher.id"
                        class="bg-white rounded-xl shadow-lg overflow-hidden bg-blue-600:shadow-xl transition-shadow duration-300">
                        <!-- Photo de profil -->
                        <div class="relative h-48 bg-gradient-to-br from-equestrian-cream to-equestrian-lightBrown">
                            <img v-if="teacher.profile_photo_url" :src="teacher.profile_photo_url"
                                :alt="`Photo de ${teacher.first_name} ${teacher.last_name}`"
                                class="w-full h-full object-cover">
                            <div v-else class="flex items-center justify-center h-full">
                                <Icon name="heroicons:user-circle" class="h-20 w-20 text-gray-700" />
                            </div>

                            <!-- Badge de statut -->
                            <div class="absolute top-4 right-4">
                                <span :class="[
                                    'px-2 py-1 text-xs font-semibold rounded-full',
                                    teacher.is_available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                ]">
                                    {{ teacher.is_available ? 'Disponible' : 'Occupé' }}
                                </span>
                            </div>
                        </div>

                        <!-- Informations de l'instructeur -->
                        <div class="p-6">
                            <div class="mb-4">
                                <h3 class="text-xl font-bold text-gray-900 mb-1">
                                    {{ teacher.first_name }} {{ teacher.last_name }}
                                </h3>
                                <p class="text-gray-700">{{ teacher.title || 'Instructeur équestre' }}</p>
                            </div>

                            <!-- Spécialités -->
                            <div v-if="teacher.specialities && teacher.specialities.length" class="mb-4">
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="speciality in teacher.specialities.slice(0, 3)" :key="speciality"
                                        class="px-2 py-1 bg-gray-50 text-gray-900 text-xs rounded-full">
                                        {{ formatSpeciality(speciality) }}
                                    </span>
                                    <span v-if="teacher.specialities.length > 3"
                                        class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                                        +{{ teacher.specialities.length - 3 }}
                                    </span>
                                </div>
                            </div>

                            <!-- Description -->
                            <p v-if="teacher.bio" class="text-gray-600 text-sm mb-4 line-clamp-3">
                                {{ teacher.bio }}
                            </p>

                            <!-- Tarifs -->
                            <div v-if="teacher.hourly_rate" class="mb-4">
                                <span class="text-2xl font-bold text-gray-700">
                                    {{ teacher.hourly_rate }}€
                                </span>
                                <span class="text-gray-500">/heure</span>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2">
                                <NuxtLink :to="`/teachers/${teacher.id}`"
                                    class="flex-1 btn-primary bg-blue-600 text-white text-center">
                                    Voir le profil
                                </NuxtLink>
                                <button v-if="teacher.is_available" @click="bookLesson(teacher)"
                                    class="btn-secondary border-blue-600 text-gray-700 hover:bg-blue-700 hover:text-white">
                                    Réserver
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <Icon name="heroicons:user-group" class="h-16 w-16 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-lg font-semibold text-gray-600 mb-2">Aucun professeur trouvé</h3>
                    <p class="text-gray-500">Essayez de modifier vos critères de recherche</p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-16 bg-equestrian-lightBrown">
            <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">
                    Vous êtes instructeur ?
                </h2>
                <p class="text-xl text-gray-700 mb-8">
                    Rejoignez notre plateforme et partagez votre passion pour l'équitation
                </p>
                <NuxtLink to="/register?type=teacher"
                    class="btn-primary bg-blue-600 text-white inline-flex items-center">
                    <Icon name="heroicons:academic-cap" class="h-5 w-5 mr-2" />
                    Devenir instructeur
                </NuxtLink>
            </div>
        </section>
    </div>
</template>

<script setup>
// const { t } = useI18n() // Temporairement désactivé

// Meta tags
useHead({
    title: 'Nos Instructeurs | Acti\'Vibe',
    meta: [
        { name: 'description', content: 'Découvrez nos instructeurs équestres expérimentés et passionnés. Trouvez le coach parfait pour vos cours d\'équitation.' },
        { name: 'keywords', content: 'instructeurs équestres, coaches équitation, cours d\'équitation, dressage, saut d\'obstacles' }
    ]
})

// État réactif
const searchQuery = ref('')
const selectedDiscipline = ref('')
const selectedLevel = ref('')

// Récupération des données
const { data: teachers, pending, error, refresh } = await useFetch('/api/teachers', {
    server: false,
    default: () => [],
    transform: (data) => data?.data || []
})

// Données de fallback si l'API n'est pas disponible
const fallbackTeachers = [
    {
        id: 1,
        first_name: 'Marie',
        last_name: 'Dubois',
        title: 'Instructrice certifiée BPJEPS',
        bio: 'Passionnée d\'équitation depuis plus de 15 ans, je me spécialise dans le dressage et l\'accompagnement des cavaliers débutants.',
        specialities: ['dressage', 'beginner'],
        hourly_rate: 45,
        is_available: true,
        profile_photo_url: null
    },
    {
        id: 2,
        first_name: 'Pierre',
        last_name: 'Martin',
        title: 'Coach en saut d\'obstacles',
        bio: 'Ancien cavalier de compétition, je transmets maintenant ma passion pour le saut d\'obstacles et aide mes élèves à progresser.',
        specialities: ['jumping', 'competition'],
        hourly_rate: 55,
        is_available: true,
        profile_photo_url: null
    },
    {
        id: 3,
        first_name: 'Sophie',
        last_name: 'Bernard',
        title: 'Instructrice polyvalente',
        bio: 'Formatrice expérimentée en équitation western et classique. J\'accompagne tous les niveaux avec patience et bienveillance.',
        specialities: ['western', 'dressage', 'intermediate'],
        hourly_rate: 50,
        is_available: false,
        profile_photo_url: null
    }
]

// Utiliser les données de fallback si pas de données de l'API
const displayTeachers = computed(() => {
    return teachers.value && teachers.value.length > 0 ? teachers.value : fallbackTeachers
})

// Filtrés teachers
const filteredTeachers = computed(() => {
    let filtered = displayTeachers.value

    // Filtre par recherche
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(teacher =>
            `${teacher.first_name} ${teacher.last_name}`.toLowerCase().includes(query) ||
            teacher.bio?.toLowerCase().includes(query)
        )
    }

    // Filtre par discipline
    if (selectedDiscipline.value) {
        filtered = filtered.filter(teacher =>
            teacher.specialities?.includes(selectedDiscipline.value)
        )
    }

    // Filtre par niveau
    if (selectedLevel.value) {
        filtered = filtered.filter(teacher =>
            teacher.specialities?.includes(selectedLevel.value)
        )
    }

    return filtered
})

// Méthodes
const formatSpeciality = (speciality) => {
    const specialityMap = {
        'dressage': 'Dressage',
        'jumping': 'Saut d\'obstacles',
        'cross': 'Cross',
        'pony_games': 'Pony Games',
        'western': 'Western',
        'beginner': 'Débutant',
        'intermediate': 'Intermédiaire',
        'advanced': 'Avancé',
        'competition': 'Compétition'
    }
    return specialityMap[speciality] || speciality
}

const bookLesson = (teacher) => {
    // Rediriger vers la page de réservation ou ouvrir un modal
    navigateTo(`/book?teacher=${teacher.id}`)
}
</script>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
