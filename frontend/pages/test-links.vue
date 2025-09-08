<template>
  <div class="p-8">
    <h1 class="text-2xl font-bold mb-4">Test des liens du menu</h1>
    
    <div class="space-y-4">
      <div class="p-4 border rounded">
        <h3 class="font-semibold mb-2">État d'authentification :</h3>
        <p><strong>isAuthenticated:</strong> {{ authStore.isAuthenticated }}</p>
        <p><strong>canActAsTeacher:</strong> {{ authStore.canActAsTeacher }}</p>
        <p><strong>isStudent:</strong> {{ authStore.isStudent }}</p>
        <p><strong>isAdmin:</strong> {{ authStore.isAdmin }}</p>
        <p><strong>User:</strong> {{ authStore.user ? authStore.user.email : 'Aucun' }}</p>
      </div>
      
      <div class="p-4 border rounded">
        <h3 class="font-semibold mb-2">Test des liens :</h3>
        <button @click="testTeacherLink" class="px-4 py-2 bg-blue-500 text-white rounded mr-2">
          Test Espace Enseignant
        </button>
        <button @click="testStudentLink" class="px-4 py-2 bg-green-500 text-white rounded mr-2">
          Test Espace Étudiant
        </button>
        <button @click="testDashboardLink" class="px-4 py-2 bg-purple-500 text-white rounded">
          Test Dashboard
        </button>
      </div>
      
      <div class="p-4 border rounded">
        <h3 class="font-semibold mb-2">Résultat :</h3>
        <p>{{ result }}</p>
      </div>
      
      <div class="p-4 border rounded">
        <h3 class="font-semibold mb-2">Test direct des routes :</h3>
        <NuxtLink to="/teacher/dashboard" class="px-4 py-2 bg-red-500 text-white rounded mr-2">
          Lien direct Teacher Dashboard
        </NuxtLink>
        <NuxtLink to="/student/dashboard" class="px-4 py-2 bg-orange-500 text-white rounded mr-2">
          Lien direct Student Dashboard
        </NuxtLink>
        <NuxtLink to="/dashboard" class="px-4 py-2 bg-gray-500 text-white rounded">
          Lien direct Dashboard
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup>
const authStore = useAuthStore()
const result = ref('')

const testTeacherLink = () => {
  result.value = 'Test du lien Espace Enseignant...'
  try {
    navigateTo('/teacher/dashboard')
    result.value = 'Navigation vers /teacher/dashboard réussie'
  } catch (error) {
    result.value = 'Erreur: ' + error.message
  }
}

const testStudentLink = () => {
  result.value = 'Test du lien Espace Étudiant...'
  try {
    navigateTo('/student/dashboard')
    result.value = 'Navigation vers /student/dashboard réussie'
  } catch (error) {
    result.value = 'Erreur: ' + error.message
  }
}

const testDashboardLink = () => {
  result.value = 'Test du lien Dashboard...'
  try {
    navigateTo('/dashboard')
    result.value = 'Navigation vers /dashboard réussie'
  } catch (error) {
    result.value = 'Erreur: ' + error.message
  }
}

// Initialiser l'authentification
onMounted(async () => {
  await authStore.initializeAuth()
  console.log('État auth après initialisation:', {
    isAuthenticated: authStore.isAuthenticated,
    canActAsTeacher: authStore.canActAsTeacher,
    user: authStore.user
  })
})
</script>
