<template>
    <div>
        <!-- Loading State -->
        <div v-if="pending" class="min-h-screen flex items-center justify-center">
            <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-equestrian-brown"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="min-h-screen flex items-center justify-center">
            <div class="text-center">
                <Icon name="heroicons:exclamation-triangle" class="h-16 w-16 text-red-500 mx-auto mb-4" />
                <h1 class="text-2xl font-bold text-gray-800 mb-4">Instructeur non trouvé</h1>
                <p class="text-gray-600 mb-8">Cet instructeur n'existe pas ou n'est plus disponible.</p>
                <NuxtLink to="/teachers" class="btn-primary bg-equestrian-brown text-white">
                    Retour aux instructeurs
                </NuxtLink>
            </div>
        </div>

        <!-- Teacher Profile -->
        <div v-else-if="teacher">
            <!-- Hero Section -->
            <section class="bg-gradient-to-br from-equestrian-brown to-equestrian-darkBrown text-white py-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col lg:flex-row items-center gap-8">
                        <!-- Photo de profil -->
                        <div class="flex-shrink-0">
                            <div class="w-32 h-32 lg:w-48 lg:h-48 rounded-full overflow-hidden bg-equestrian-cream">
                                <img v-if="teacher.profile_photo_url" :src="teacher.profile_photo_url"
                                    :alt="`Photo de ${teacher.first_name} ${teacher.last_name}`"
                                    class="w-full h-full object-cover">
                                <div v-else class="flex items-center justify-center h-full">
                                    <Icon name="heroicons:user-circle"
                                        class="h-24 w-24 lg:h-32 lg:w-32 text-equestrian-brown" />
                                </div>
                            </div>
                        </div>

                        <!-- Informations principales -->
                        <div class="flex-1 text-center lg:text-left">
                            <h1 class="text-3xl lg:text-4xl font-bold mb-2">
                                {{ teacher.first_name }} {{ teacher.last_name }}
                            </h1>
                            <p class="text-xl text-equestrian-cream mb-4">
                                {{ teacher.title || 'Instructeur équestre' }}
                            </p>
                            <div class="flex flex-wrap gap-2 justify-center lg:justify-start mb-6">
                                <span v-for="speciality in teacher.specialities" :key="speciality"
                                    class="px-3 py-1 bg-equestrian-cream text-equestrian-darkBrown text-sm rounded-full">
                                    {{ formatSpeciality(speciality) }}
                                </span>
                            </div>

                            <!-- Status et tarif -->
                            <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                                <span :class="[
                                    'px-4 py-2 text-sm font-semibold rounded-full',
                                    teacher.is_available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                ]">
                                    {{ teacher.is_available ? '✅ Disponible' : '❌ Occupé' }}
                                </span>

                                <div v-if="teacher.hourly_rate" class="text-2xl font-bold">
                                    {{ teacher.hourly_rate }}€<span class="text-lg font-normal">/heure</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Details Section -->
            <section class="py-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Informations détaillées -->
                        <div class="lg:col-span-2">
                            <!-- Biographie -->
                            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                                <h2 class="text-2xl font-bold text-equestrian-darkBrown mb-4">
                                    À propos
                                </h2>
                                <p class="text-gray-700 leading-relaxed">
                                    {{ teacher.bio || 'Aucune description disponible pour cet instructeur.' }}
                                </p>
                            </div>

                            <!-- Certifications et expérience -->
                            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                                <h2 class="text-2xl font-bold text-equestrian-darkBrown mb-4">
                                    Certifications & Expérience
                                </h2>
                                <div v-if="teacher.certifications && teacher.certifications.length" class="space-y-3">
                                    <div v-for="cert in teacher.certifications" :key="cert.id"
                                        class="flex items-center p-3 bg-equestrian-cream rounded-lg">
                                        <Icon name="heroicons:academic-cap"
                                            class="h-6 w-6 text-equestrian-brown mr-3" />
                                        <div>
                                            <h3 class="font-semibold text-equestrian-darkBrown">{{ cert.name }}</h3>
                                            <p class="text-sm text-equestrian-brown">{{ cert.organization }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-gray-500 italic">
                                    Aucune certification renseignée
                                </div>
                            </div>

                            <!-- Avis et évaluations -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h2 class="text-2xl font-bold text-equestrian-darkBrown mb-4">
                                    Avis des élèves
                                </h2>
                                <div v-if="teacher.reviews && teacher.reviews.length" class="space-y-4">
                                    <div v-for="review in teacher.reviews.slice(0, 3)" :key="review.id"
                                        class="border-l-4 border-equestrian-brown pl-4">
                                        <div class="flex items-center mb-2">
                                            <div class="flex text-yellow-400 mr-2">
                                                <Icon v-for="star in 5" :key="star" name="heroicons:star-solid"
                                                    :class="star <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                                                    class="h-4 w-4" />
                                            </div>
                                            <span class="text-sm text-gray-600">{{ review.student_name }}</span>
                                        </div>
                                        <p class="text-gray-700">{{ review.comment }}</p>
                                    </div>
                                </div>
                                <div v-else class="text-gray-500 italic">
                                    Aucun avis pour le moment
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar - Actions et infos -->
                        <div class="space-y-6">
                            <!-- Card de réservation -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="text-xl font-bold text-equestrian-darkBrown mb-4">
                                    Réserver un cours
                                </h3>

                                <div v-if="teacher.is_available" class="space-y-4">
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-equestrian-brown mb-1">
                                            {{ teacher.hourly_rate || 50 }}€
                                        </div>
                                        <div class="text-gray-600">par heure</div>
                                    </div>

                                    <button @click="bookLesson"
                                        class="w-full btn-primary bg-equestrian-brown text-white">
                                        <Icon name="heroicons:calendar-days" class="h-5 w-5 mr-2" />
                                        Réserver maintenant
                                    </button>

                                    <button @click="contactTeacher"
                                        class="w-full btn-secondary border-equestrian-brown text-equestrian-brown hover:bg-equestrian-brown hover:text-white">
                                        <Icon name="heroicons:chat-bubble-left-right" class="h-5 w-5 mr-2" />
                                        Contacter
                                    </button>
                                </div>

                                <div v-else class="text-center text-gray-500">
                                    <Icon name="heroicons:clock" class="h-12 w-12 mx-auto mb-2 text-gray-400" />
                                    <p>Cet instructeur n'est pas disponible actuellement</p>
                                </div>
                            </div>

                            <!-- Informations de contact -->
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <h3 class="text-xl font-bold text-equestrian-darkBrown mb-4">
                                    Informations
                                </h3>

                                <div class="space-y-3">
                                    <div v-if="teacher.location" class="flex items-center">
                                        <Icon name="heroicons:map-pin" class="h-5 w-5 text-equestrian-brown mr-3" />
                                        <span class="text-gray-700">{{ teacher.location }}</span>
                                    </div>

                                    <div v-if="teacher.experience_years" class="flex items-center">
                                        <Icon name="heroicons:star" class="h-5 w-5 text-equestrian-brown mr-3" />
                                        <span class="text-gray-700">{{ teacher.experience_years }} ans
                                            d'expérience</span>
                                    </div>

                                    <div v-if="teacher.languages" class="flex items-center">
                                        <Icon name="heroicons:language" class="h-5 w-5 text-equestrian-brown mr-3" />
                                        <span class="text-gray-700">{{ teacher.languages.join(', ') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Retour aux instructeurs -->
                            <div class="bg-equestrian-cream rounded-xl p-6">
                                <NuxtLink to="/teachers"
                                    class="flex items-center justify-center w-full btn-secondary border-equestrian-brown text-equestrian-brown hover:bg-equestrian-brown hover:text-white">
                                    <Icon name="heroicons:arrow-left" class="h-5 w-5 mr-2" />
                                    Voir tous les instructeurs
                                </NuxtLink>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
// Récupération de l'ID depuis l'URL
const route = useRoute()
const teacherId = route.params.id

// Meta tags dynamiques
useHead({
    title: computed(() => teacher.value ? `${teacher.value.first_name} ${teacher.value.last_name} | BookYourCoach` : 'Instructeur | BookYourCoach'),
    meta: [
        {
            name: 'description',
            content: computed(() => teacher.value?.bio || 'Profil d\'instructeur équestre sur BookYourCoach')
        }
    ]
})

// Récupération des données de l'instructeur
const { data: teacher, pending, error } = await useFetch(`/api/teachers/${teacherId}`, {
    server: false,
    default: () => null,
    transform: (data) => data?.data
})

// Données de fallback pour le développement
if (!teacher.value && !pending.value) {
    // Simuler des données pour le développement
    const fallbackData = {
        id: parseInt(teacherId),
        first_name: 'Marie',
        last_name: 'Dubois',
        title: 'Instructrice certifiée BPJEPS',
        bio: 'Passionnée d\'équitation depuis plus de 15 ans, je me spécialise dans le dressage et l\'accompagnement des cavaliers débutants. Mon approche pédagogique se base sur la confiance mutuelle entre le cavalier et sa monture. J\'ai eu la chance de participer à plusieurs compétitions nationales avant de me tourner vers l\'enseignement.',
        specialities: ['dressage', 'beginner', 'intermediate'],
        hourly_rate: 45,
        is_available: true,
        profile_photo_url: null,
        location: 'Centre équestre de Fontainebleau',
        experience_years: 12,
        languages: ['Français', 'Anglais'],
        certifications: [
            {
                id: 1,
                name: 'BPJEPS Équitation',
                organization: 'Ministère des Sports'
            },
            {
                id: 2,
                name: 'Galop 7 FFE',
                organization: 'Fédération Française d\'Équitation'
            }
        ],
        reviews: [
            {
                id: 1,
                student_name: 'Claire M.',
                rating: 5,
                comment: 'Excellente instructrice ! Très patiente et pédagogue. Mes progrès ont été rapides grâce à ses conseils.'
            },
            {
                id: 2,
                student_name: 'Thomas L.',
                rating: 5,
                comment: 'Marie a su me redonner confiance après une chute. Je recommande vivement ses cours.'
            }
        ]
    }

    teacher.value = fallbackData
}

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

const bookLesson = () => {
    // Rediriger vers la page de réservation
    navigateTo(`/book?teacher=${teacherId}`)
}

const contactTeacher = () => {
    // Rediriger vers la page de contact ou ouvrir un modal
    navigateTo(`/contact?teacher=${teacherId}`)
}
</script>
