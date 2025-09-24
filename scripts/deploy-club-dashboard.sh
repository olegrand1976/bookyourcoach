#!/bin/bash

# Script pour déployer les corrections du dashboard des clubs
echo "🚀 Déploiement des corrections du dashboard des clubs"
echo ""

# Vérifier que nous sommes sur la branche main
echo "📋 Vérification de la branche..."
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "main" ]; then
    echo "❌ Vous n'êtes pas sur la branche main (actuellement sur: $CURRENT_BRANCH)"
    echo "💡 Veuillez basculer sur la branche main avant de déployer"
    exit 1
fi

echo "✅ Branche main confirmée"
echo ""

# Vérifier les fichiers modifiés
echo "📁 Fichiers modifiés pour le dashboard des clubs:"
git status --porcelain | grep -E "(ClubDashboardController|api\.php|dashboard\.vue|nuxt\.config\.ts)"
echo ""

# Ajouter les fichiers modifiés
echo "📝 Ajout des fichiers modifiés..."
git add app/Http/Controllers/Api/ClubDashboardController.php
git add routes/api.php
git add frontend/pages/club/dashboard.vue
git add frontend/nuxt.config.ts
echo ""

# Commit des changements
echo "💾 Commit des changements..."
git commit -m "feat: Ajout du dashboard des clubs avec API propre

- Création du contrôleur ClubDashboardController
- Ajout de la route /api/club/dashboard
- Mise à jour du frontend pour utiliser la route propre
- Suppression des routes de test
- Correction de la configuration API pour le développement"
echo ""

# Push vers le serveur
echo "🌐 Push vers le serveur de production..."
git push origin main
echo ""

echo "✅ Déploiement terminé !"
echo ""
echo "📋 Prochaines étapes:"
echo "1. Attendre que le serveur redémarre automatiquement"
echo "2. Vérifier que les données des clubs sont présentes"
echo "3. Tester l'API: https://activibe.be/api/club/dashboard"
echo ""
echo "🔍 Pour vérifier le déploiement:"
echo "curl -H 'Authorization: Bearer YOUR_TOKEN' https://activibe.be/api/club/dashboard"
