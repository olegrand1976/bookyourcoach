<template>
  <div class="min-h-screen bg-equestrian-cream">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b-4 border-equestrian-gold">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
          <div class="flex items-center">
            <NuxtLink to="/"
              class="flex items-center space-x-3 text-xl font-bold text-equestrian-darkBrown hover:text-equestrian-brown transition-colors">
              <Logo size="md" />
            </NuxtLink>
          </div>

          <div class="flex items-center space-x-6">
            <template v-if="authStore.isAuthenticated">
              <!-- Menu utilisateur authentifié -->
              <div class="relative" v-if="showUserMenu">
                <button @click="toggleUserMenu"
                  class="flex items-center space-x-2 text-equestrian-darkBrown hover:text-equestrian-brown bg-equestrian-cream px-4 py-2 rounded-lg transition-colors">
                  <EquestrianIcon name="helmet" :size="20" />
                  <span class="font-medium">{{ authStore.userName }}</span>
                  <ChevronDownIcon class="w-4 h-4" />
                </button>

                <div v-if="userMenuOpen"
                  class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl py-2 z-50 border border-equestrian-gold/20">
                  <NuxtLink to="/dashboard"
                    class="flex items-center space-x-2 px-4 py-3 text-equestrian-darkBrown hover:bg-equestrian-cream transition-colors">
                    <EquestrianIcon name="dashboard" :size="16" />
                    <span>Tableau de bord</span>
                  </NuxtLink>

                  <NuxtLink v-if="authStore.canActAsTeacher" to="/teacher/dashboard"
                    class="flex items-center space-x-2 px-4 py-3 text-equestrian-darkBrown hover:bg-equestrian-cream transition-colors">
                    <EquestrianIcon name="saddle" :size="16" />
                    <span>Espace Enseignant</span>
                  </NuxtLink>

                  <NuxtLink to="/profile"
                    class="flex items-center space-x-2 px-4 py-3 text-equestrian-darkBrown hover:bg-equestrian-cream transition-colors">
                    <EquestrianIcon name="helmet" :size="16" />
                    <span>Mon Profil</span>
                  </NuxtLink>

                  <NuxtLink v-if="authStore.isAdmin" to="/admin"
                    class="flex items-center space-x-2 px-4 py-3 text-equestrian-darkBrown hover:bg-equestrian-cream transition-colors">
                    <EquestrianIcon name="trophy" :size="16" />
                    <span>Administration</span>
                  </NuxtLink>

                  <hr class="my-2 border-equestrian-gold/20">

                  <button @click="handleLogout"
                    class="flex items-center space-x-2 w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 transition-colors">
                    <span>🚪 Se déconnecter</span>
                  </button>
                </div>
              </div>
            </template>

            <template v-else>
              <!-- Menu utilisateur non authentifié -->
              <NuxtLink to="/login"
                class="text-equestrian-darkBrown hover:text-equestrian-brown font-medium px-4 py-2 rounded-lg hover:bg-equestrian-cream transition-colors">
                Se connecter
              </NuxtLink>
              <NuxtLink to="/register"
                class="btn-primary bg-equestrian-leather hover:bg-equestrian-brown text-white font-semibold px-6 py-2 rounded-lg transition-colors">
                🏇 S'inscrire
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
    <footer class="bg-equestrian-darkBrown text-equestrian-cream border-t-4 border-equestrian-gold mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <!-- Logo et description -->
          <div class="md:col-span-2">
            <div class="flex items-center space-x-3 mb-4">
              <Logo size="sm" />
            </div>
            <p class="text-equestrian-cream/80 mb-4">
              La plateforme de référence pour réserver vos cours d'équitation avec des instructeurs certifiés.
            </p>
            <div class="flex space-x-4">
              <EquestrianIcon name="trophy" :size="24" class="text-equestrian-gold" />
              <EquestrianIcon name="saddle" :size="24" class="text-equestrian-gold" />
              <EquestrianIcon name="helmet" :size="24" class="text-equestrian-gold" />
            </div>
          </div>

          <!-- Liens rapides -->
          <div>
            <h4 class="font-semibold text-equestrian-gold mb-4">Liens Rapides</h4>
            <ul class="space-y-2">
              <li>
                <NuxtLink to="/coaches" class="text-equestrian-cream/80 hover:text-equestrian-gold transition-colors">
                  Nos
                  Instructeurs</NuxtLink>
              </li>
              <li>
                <NuxtLink to="/centers" class="text-equestrian-cream/80 hover:text-equestrian-gold transition-colors">
                  Centres Équestres</NuxtLink>
              </li>
              <li>
                <NuxtLink to="/disciplines"
                  class="text-equestrian-cream/80 hover:text-equestrian-gold transition-colors">
                  Disciplines</NuxtLink>
              </li>
            </ul>
          </div>

          <!-- Contact -->
          <div>
            <h4 class="font-semibold text-equestrian-gold mb-4">Contact</h4>
            <ul class="space-y-2 text-equestrian-cream/80">
              <li>📧 {{ settings.settings.contact_email }}</li>
              <li>📞 {{ settings.settings.contact_phone }}</li>
              <li v-if="settings.settings.company_address">🏠 {{ settings.settings.company_address.split('\n')[0] }}
              </li>
            </ul>
          </div>
        </div>

        <hr class="border-equestrian-gold/30 my-8">

        <div class="text-center text-equestrian-cream/60">
          <p>&copy; 2025 {{ settings.settings.platform_name }}. Tous droits réservés. 🐎</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toast = useToast()
const settings = useSettings()

const userMenuOpen = ref(false)
const showUserMenu = computed(() => authStore.isAuthenticated)

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value
}

const handleLogout = async () => {
  try {
    await authStore.logout()
    toast.success('Déconnexion réussie')
  } catch (error) {
    toast.error('Erreur lors de la déconnexion')
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

// Initialiser l'authentification et les paramètres
onMounted(async () => {
  await authStore.initializeAuth()
  settings.loadSettings()
})
</script>
