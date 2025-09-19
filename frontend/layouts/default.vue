<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b-4 border-blue-500">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
          <div class="flex items-center">
            <NuxtLink to="/"
              class="flex items-center space-x-3 text-xl font-bold text-gray-900 hover:text-gray-700 transition-colors">
              <img src="/logo-activibe.svg" alt="activibe" class="h-12 w-auto" />
            </NuxtLink>
          </div>

          <div class="flex items-center space-x-6">
            <!-- Sélecteur de langue temporairement désactivé -->
            <!-- <LanguageSelector /> -->
            
            <template v-if="isAuthenticated">
              <!-- Menu utilisateur authentifié -->
              <div class="relative" v-if="showUserMenu">
                <button @click="toggleUserMenu"
                  class="flex items-center space-x-2 text-gray-900 hover:text-gray-700 bg-gray-50 px-4 py-2 rounded-lg transition-colors">
                  <span class="text-lg">👤</span>
                  <span class="font-medium">{{ userName }}</span>
                  <ChevronDownIcon class="w-4 h-4" />
                </button>

                <div v-if="userMenuOpen"
                  class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl py-2 z-50 border border-blue-500/20">
anuuel                  <NuxtLink v-if="isAdmin" to="/admin"
                    class="flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <span>📊</span>
                    <span>Tableau de bord</span>
                  </NuxtLink>
                  <NuxtLink v-else-if="isClub" to="/club/dashboard"
                    class="flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <span>📊</span>
                    <span>Tableau de bord</span>
                  </NuxtLink>
                  <NuxtLink v-else-if="canActAsTeacher" to="/teacher/dashboard"
                    class="flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <span>📊</span>
                    <span>Tableau de bord</span>
                  </NuxtLink>
                  <NuxtLink v-else-if="isStudent" to="/student/dashboard"
                    class="flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <span>📊</span>
                    <span>Tableau de bord</span>
                  </NuxtLink>

                  <NuxtLink v-if="canActAsTeacher && !isAdmin" to="/teacher/dashboard"
                    class="flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <span>🏇</span>
                    <span>Espace Enseignant</span>
                  </NuxtLink>

                  <NuxtLink v-if="isStudent" to="/student/dashboard"
                    class="flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <span>👨‍🎓</span>
                    <span>Espace Étudiant</span>
                  </NuxtLink>

                  <NuxtLink v-if="isClub" to="/club/teachers"
                    class="flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <span>👨‍🏫</span>
                    <span>Enseignants</span>
                  </NuxtLink>

                  <NuxtLink v-if="isClub" to="/club/students"
                    class="flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <span>👨‍🎓</span>
                    <span>Élèves</span>
                  </NuxtLink>

                  <NuxtLink :to="authStore.user?.role === 'club' ? '/club/profile' : '/profile'"
                    class="flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <span>👤</span>
                    <span>Profil</span>
                  </NuxtLink>

                  <NuxtLink v-if="isAdmin" to="/admin"
                    class="flex items-center space-x-2 w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-100 transition-colors">
                    <span>⚙️</span>
                    <span>Administration</span>
                  </NuxtLink>

                  <hr class="my-2 border-gray-200">

                  <button @click="handleLogout"
                    class="flex items-center space-x-2 w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 transition-colors">
                    <span>🚪 Déconnexion</span>
                  </button>
                </div>
              </div>
            </template>

            <template v-else>
              <!-- Menu utilisateur non authentifié -->
              <NuxtLink to="/login"
                class="text-gray-900 hover:text-gray-700 font-medium px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                Connexion
              </NuxtLink>
              <NuxtLink to="/register"
                class="btn-primary bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg transition-colors">
                🏇 Inscription
              </NuxtLink>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Contenu principal -->
    <main>
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-gray-100 border-t-4 border-blue-500 mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <!-- Logo et description -->
          <div class="md:col-span-2">
            <div class="flex items-center space-x-3 mb-4">
              <span class="text-2xl">🐎</span>
              <span class="text-xl font-bold">BookYourCoach</span>
            </div>
            <p class="text-gray-100/80 mb-4">
              La plateforme de référence pour réserver vos cours d'équitation et de natation avec les meilleurs instructeurs.
            </p>
            <div class="flex space-x-4">
              <span class="text-2xl">🏆</span>
              <span class="text-2xl">🏇</span>
              <span class="text-2xl">⛑️</span>
            </div>
          </div>

          <!-- Liens rapides -->
          <div>
            <h4 class="font-semibold text-blue-400 mb-4">Liens Rapides</h4>
            <ul class="space-y-2">
              <li>
                <NuxtLink to="/coaches" class="text-gray-100/80 hover:text-blue-400 transition-colors">
                  Nos Instructeurs</NuxtLink>
              </li>
              <li>
                <NuxtLink to="/centers" class="text-gray-100/80 hover:text-blue-400 transition-colors">
                  Centres Équestres</NuxtLink>
              </li>
              <li>
                <NuxtLink to="/disciplines"
                  class="text-gray-100/80 hover:text-blue-400 transition-colors">
                  Disciplines</NuxtLink>
              </li>
            </ul>
          </div>

          <!-- Contact -->
          <div>
            <h4 class="font-semibold text-blue-400 mb-4">Contact</h4>
            <ul class="space-y-2 text-gray-100/80">
              <li>📧 contact@bookyourcoach.com</li>
              <li>📞 +33 1 23 45 67 89</li>
              <li>🏠 Paris, France</li>
            </ul>
          </div>
        </div>

        <hr class="border-blue-500/30 my-8">

        <div class="text-center text-gray-100/60">
          <p>&copy; 2025 BookYourCoach. Tous droits réservés. 🐎</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

// Utiliser le store d'authentification
const authStore = useAuthStore()
const userMenuOpen = ref(false)

// Computed properties basées sur le store
const isAuthenticated = computed(() => authStore.isAuthenticated)
const userName = computed(() => authStore.userName)
const canActAsTeacher = computed(() => authStore.canActAsTeacher)
const isStudent = computed(() => authStore.isStudent)
const isAdmin = computed(() => authStore.isAdmin)
const isClub = computed(() => authStore.user?.role === 'club')
const showUserMenu = computed(() => isAuthenticated.value)

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value
}

const handleLogout = async () => {
  try {
    await authStore.logout()
  } catch (error) {
    console.error('Erreur lors de la déconnexion:', error)
  }
}

// Fermer le menu utilisateur lors du clic à l'extérieur
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.relative')) {
      userMenuOpen.value = false
    }
  })
})
</script>
