<template>
  <div class="min-h-screen bg-white">
    <!-- Hero Section avec image de fond -->
    <section class="relative h-screen flex items-center justify-center overflow-hidden">
      <!-- Background avec overlay -->
      <div class="absolute inset-0 bg-gradient-to-br from-blue-900/90 to-purple-900/90 z-10"></div>
      <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1576610616656-d3aa5d1f4534?w=1920&q=80');"></div>
      
      <!-- Contenu Hero -->
      <div class="relative z-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
        <div class="mb-8 animate-fade-in">
          <h1 class="text-5xl md:text-7xl font-extrabold mb-6 leading-tight">
            Natation & Fitness,
            <span class="block text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-400">
              réservez vos cours en un clic
            </span>
          </h1>
          <p class="text-xl md:text-2xl text-gray-200 mb-8 max-w-3xl mx-auto leading-relaxed">
            La plateforme de référence pour réserver vos cours de natation et de fitness avec les meilleurs clubs et coachs
          </p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-12">
          <NuxtLink to="/register"
            class="group bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white font-bold px-10 py-4 rounded-full text-lg transition-all transform hover:scale-105 shadow-2xl flex items-center gap-2">
            <span>Commencer gratuitement</span>
            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
            </svg>
          </NuxtLink>
          <NuxtLink to="/login"
            class="bg-white/10 backdrop-blur-sm hover:bg-white/20 text-white border-2 border-white/30 font-semibold px-10 py-4 rounded-full text-lg transition-all">
            Connexion
          </NuxtLink>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-3 gap-8 max-w-3xl mx-auto pt-8 border-t border-white/20">
          <div>
            <div class="text-4xl font-bold text-cyan-400">2,500+</div>
            <div class="text-sm text-gray-300 mt-1">Élèves actifs</div>
          </div>
          <div>
            <div class="text-4xl font-bold text-cyan-400">150+</div>
            <div class="text-sm text-gray-300 mt-1">Clubs partenaires</div>
          </div>
          <div>
            <div class="text-4xl font-bold text-cyan-400">50,000+</div>
            <div class="text-sm text-gray-300 mt-1">Cours réservés</div>
          </div>
        </div>
      </div>

      <!-- Scroll indicator -->
      <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 z-20 animate-bounce">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
        </svg>
      </div>
    </section>

    <!-- Section Comment ça marche -->
    <section class="py-24 px-4 sm:px-6 lg:px-8 bg-gray-50">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Comment ça marche ?
          </h2>
          <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            Réservez vos cours en 3 étapes simples
          </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
          <!-- Étape 1 -->
          <div class="relative">
            <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-shadow border border-gray-100">
              <div class="absolute -top-6 left-8">
                <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-xl shadow-lg">
                  1
                </div>
              </div>
              <div class="mt-6">
                <div class="w-16 h-16 bg-cyan-100 rounded-2xl flex items-center justify-center mb-6">
                  <svg class="w-8 h-8 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                  </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Trouvez votre club</h3>
                <p class="text-gray-600 leading-relaxed">
                  Parcourez notre catalogue de clubs et trouvez celui qui correspond à vos besoins et à votre localisation.
                </p>
              </div>
            </div>
          </div>

          <!-- Étape 2 -->
          <div class="relative">
            <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-shadow border border-gray-100">
              <div class="absolute -top-6 left-8">
                <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-xl shadow-lg">
                  2
                </div>
              </div>
              <div class="mt-6">
                <div class="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mb-6">
                  <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Réservez en ligne</h3>
                <p class="text-gray-600 leading-relaxed">
                  Consultez les disponibilités en temps réel et réservez votre créneau en quelques clics, 24h/24.
                </p>
              </div>
            </div>
          </div>

          <!-- Étape 3 -->
          <div class="relative">
            <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-shadow border border-gray-100">
              <div class="absolute -top-6 left-8">
                <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-xl shadow-lg">
                  3
                </div>
              </div>
              <div class="mt-6">
                <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mb-6">
                  <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                  </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Profitez de votre cours</h3>
                <p class="text-gray-600 leading-relaxed">
                  Présentez-vous à l'heure avec votre confirmation. Profitez de votre cours avec des instructeurs qualifiés.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Activités -->
    <section class="py-24 px-4 sm:px-6 lg:px-8 bg-white">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Nos spécialités
          </h2>
          <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            Natation et fitness : deux univers, une passion commune
          </p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-5xl mx-auto">
          <!-- Natation -->
          <div class="group relative overflow-hidden rounded-3xl shadow-2xl hover:shadow-3xl transition-all cursor-pointer">
            <div class="aspect-[4/3] bg-gradient-to-br from-blue-500 to-cyan-600"></div>
            <div class="absolute inset-0 bg-cover bg-center opacity-80 group-hover:opacity-100 transition-opacity" style="background-image: url('https://images.unsplash.com/photo-1560089000-7433a4ebbd64?w=800&q=80');"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-8 text-white">
              <div class="text-5xl mb-4">🏊</div>
              <h3 class="text-3xl font-bold mb-3">Natation</h3>
              <p class="text-base text-gray-200 mb-4 leading-relaxed">
                Apprenez à nager ou perfectionnez votre technique avec nos cours adaptés à tous les niveaux
              </p>
              <div class="flex flex-wrap gap-2">
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm">Cours particuliers</span>
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm">Aquagym</span>
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm">Aquabike</span>
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm">Bébés nageurs</span>
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm">Natation synchronisée</span>
              </div>
            </div>
          </div>

          <!-- Fitness -->
          <div class="group relative overflow-hidden rounded-3xl shadow-2xl hover:shadow-3xl transition-all cursor-pointer">
            <div class="aspect-[4/3] bg-gradient-to-br from-purple-500 to-pink-600"></div>
            <div class="absolute inset-0 bg-cover bg-center opacity-80 group-hover:opacity-100 transition-opacity" style="background-image: url('https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=800&q=80');"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-8 text-white">
              <div class="text-5xl mb-4">💪</div>
              <h3 class="text-3xl font-bold mb-3">Fitness</h3>
              <p class="text-base text-gray-200 mb-4 leading-relaxed">
                Atteignez vos objectifs avec nos programmes personnalisés et nos coachs certifiés
              </p>
              <div class="flex flex-wrap gap-2">
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm">Musculation</span>
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm">Cardio</span>
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm">Yoga</span>
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm">Pilates</span>
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm">Cross-training</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Statistiques spécifiques -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mt-16 max-w-4xl mx-auto">
          <div class="text-center">
            <div class="text-3xl font-bold text-blue-600 mb-2">15+</div>
            <div class="text-sm text-gray-600">Piscines partenaires</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-purple-600 mb-2">30+</div>
            <div class="text-sm text-gray-600">Salles de fitness</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-cyan-600 mb-2">200+</div>
            <div class="text-sm text-gray-600">Coachs certifiés</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-pink-600 mb-2">1,500+</div>
            <div class="text-sm text-gray-600">Cours par semaine</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Témoignages -->
    <section class="py-24 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-blue-50 to-purple-50">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Ils utilisent activibe
          </h2>
          <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            Découvrez ce que nos utilisateurs pensent de notre plateforme
          </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Témoignage 1 -->
          <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center gap-1 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
              </svg>
            </div>
            <p class="text-gray-700 mb-6 leading-relaxed italic">
              "Grâce à activibe, je peux réserver mes cours de natation en quelques clics. Fini les appels téléphoniques sans fin ! L'interface est intuitive et je peux gérer tout mon planning facilement. Mes enfants adorent leurs cours de bébés nageurs !"
            </p>
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-gradient-to-br from-pink-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                S
              </div>
              <div>
                <div class="font-semibold text-gray-900">Sophie Martin</div>
                <div class="text-sm text-gray-500">Maman de 2 enfants</div>
              </div>
            </div>
          </div>

          <!-- Témoignage 2 -->
          <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center gap-1 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
              </svg>
            </div>
            <p class="text-gray-700 mb-6 leading-relaxed italic">
              "En tant que gérant de salle de fitness, activibe a transformé notre gestion quotidienne. Nos membres peuvent réserver leurs cours de yoga, pilates ou musculation 24/7. Nous avons réduit nos tâches administratives de 60%. Un vrai game-changer !"
            </p>
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                P
              </div>
              <div>
                <div class="font-semibold text-gray-900">Pierre Dubois</div>
                <div class="text-sm text-gray-500">Gérant salle de fitness</div>
              </div>
            </div>
          </div>

          <!-- Témoignage 3 -->
          <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center gap-1 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
              </svg>
            </div>
            <p class="text-gray-700 mb-6 leading-relaxed italic">
              "J'ai commencé la natation à 45 ans grâce à activibe. Le système de réservation est tellement simple que j'ai réussi à prendre confiance et à réserver mon premier cours sans stress. Aujourd'hui je nage 3 fois par semaine et je me sens en pleine forme !"
            </p>
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                M
              </div>
              <div>
                <div class="font-semibold text-gray-900">Marie Lefebvre</div>
                <div class="text-sm text-gray-500">Nageuse débutante</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Avantages -->
    <section class="py-24 px-4 sm:px-6 lg:px-8 bg-white">
      <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
          <div>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
              Une plateforme pensée pour vous
            </h2>
            <p class="text-xl text-gray-600 mb-8 leading-relaxed">
              Que vous soyez élève ou gestionnaire de club, activibe simplifie votre quotidien sportif.
            </p>
            
            <div class="space-y-6">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-cyan-100 rounded-xl flex items-center justify-center flex-shrink-0">
                  <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-gray-900 mb-2">Réservation instantanée</h3>
                  <p class="text-gray-600">Consultez les disponibilités en temps réel et réservez en 30 secondes chrono.</p>
                </div>
              </div>

              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                  <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-gray-900 mb-2">Disponible 24/7</h3>
                  <p class="text-gray-600">Gérez vos réservations à toute heure, depuis votre ordinateur ou smartphone.</p>
                </div>
              </div>

              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                  <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                  </svg>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-gray-900 mb-2">Notifications intelligentes</h3>
                  <p class="text-gray-600">Recevez des rappels avant vos cours et des notifications pour les annulations.</p>
                </div>
              </div>

              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center flex-shrink-0">
                  <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                  </svg>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-gray-900 mb-2">Paiement sécurisé</h3>
                  <p class="text-gray-600">Payez en ligne en toute sécurité avec notre système de paiement crypté.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="relative">
            <div class="aspect-square rounded-3xl overflow-hidden shadow-2xl">
              <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/20 to-purple-500/20"></div>
              <img src="https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=800&q=80" alt="Planning" class="w-full h-full object-cover">
            </div>
            <!-- Floating card -->
            <div class="absolute -bottom-6 -left-6 bg-white rounded-2xl p-6 shadow-2xl max-w-xs">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                </div>
                <div>
                  <div class="text-2xl font-bold text-gray-900">98%</div>
                  <div class="text-sm text-gray-600">Satisfaction client</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section CTA Finale -->
    <section class="py-24 px-4 sm:px-6 lg:px-8 bg-gradient-to-r from-cyan-600 to-blue-700 text-white relative overflow-hidden">
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl"></div>
      </div>
      
      <div class="max-w-4xl mx-auto text-center relative z-10">
        <h2 class="text-4xl md:text-5xl font-bold mb-6">
          Prêt à simplifier votre pratique sportive ?
        </h2>
        <p class="text-xl mb-10 text-cyan-100 leading-relaxed">
          Rejoignez des milliers d'utilisateurs qui ont déjà choisi activibe pour gérer leurs activités sportives
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
          <NuxtLink to="/register"
            class="group bg-white text-blue-600 hover:bg-gray-100 font-bold px-10 py-4 rounded-full text-lg transition-all transform hover:scale-105 shadow-xl flex items-center gap-2">
            <span>Créer mon compte gratuitement</span>
            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
            </svg>
          </NuxtLink>
          <NuxtLink to="/login"
            class="text-white border-2 border-white hover:bg-white/10 font-semibold px-10 py-4 rounded-full text-lg transition-all">
            J'ai déjà un compte
          </NuxtLink>
        </div>
        
        <p class="text-sm text-cyan-200 mt-6">
          ✨ Aucune carte bancaire requise • Essai gratuit • Support 7j/7
        </p>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div>
            <h3 class="text-white font-bold text-xl mb-4">activibe</h3>
            <p class="text-sm text-gray-400">
              La plateforme tout-en-un pour gérer vos activités sportives en toute simplicité.
            </p>
          </div>
          
          <div>
            <h4 class="text-white font-semibold mb-4">Produit</h4>
            <ul class="space-y-2 text-sm">
              <li><a href="#" class="hover:text-white transition-colors">Fonctionnalités</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Tarifs</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Témoignages</a></li>
            </ul>
          </div>
          
          <div>
            <h4 class="text-white font-semibold mb-4">Entreprise</h4>
            <ul class="space-y-2 text-sm">
              <li><a href="#" class="hover:text-white transition-colors">À propos</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Carrières</a></li>
            </ul>
          </div>
          
          <div>
            <h4 class="text-white font-semibold mb-4">Support</h4>
            <ul class="space-y-2 text-sm">
              <li><a href="#" class="hover:text-white transition-colors">Centre d'aide</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Confidentialité</a></li>
            </ul>
          </div>
        </div>
        
        <div class="border-t border-gray-800 mt-12 pt-8 text-center text-sm text-gray-500">
          <p>&copy; 2025 activibe. Tous droits réservés.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'minimal'
})
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 1s ease-out;
}
</style>