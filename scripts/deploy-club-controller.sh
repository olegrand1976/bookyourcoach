#!/bin/bash

# Script pour déployer le contrôleur ClubDashboardController sur le serveur de production
echo "🚀 Déploiement du contrôleur ClubDashboardController sur le serveur de production"
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

# Vérifier que le contrôleur existe
echo "📁 Vérification du contrôleur..."
if [ ! -f "app/Http/Controllers/Api/ClubDashboardController.php" ]; then
    echo "❌ Le contrôleur ClubDashboardController.php n'existe pas"
    exit 1
fi

echo "✅ Contrôleur trouvé"
echo ""

# Vérifier que la route existe
echo "📁 Vérification de la route..."
if ! grep -q "club/dashboard" routes/api.php; then
    echo "❌ La route club/dashboard n'existe pas dans routes/api.php"
    exit 1
fi

echo "✅ Route trouvée"
echo ""

# Ajouter tous les fichiers modifiés
echo "📝 Ajout des fichiers modifiés..."
git add .
echo ""

# Commit des changements
echo "💾 Commit des changements..."
git commit -m "feat: Déploiement du contrôleur ClubDashboardController

- Contrôleur ClubDashboardController pour le dashboard des clubs
- Route /api/club/dashboard avec authentification
- Frontend mis à jour pour utiliser la route propre
- Configuration API corrigée pour le développement local"
echo ""

# Push vers le serveur
echo "🌐 Push vers le serveur de production..."
git push origin main
echo ""

echo "✅ Déploiement terminé !"
echo ""
echo "📋 Prochaines étapes:"
echo "1. Attendre que le serveur redémarre automatiquement (2-3 minutes)"
echo "2. Tester l'API: https://activibe.be/api/club/dashboard"
echo "3. Vérifier que les données des clubs s'affichent"
echo ""
echo "🔍 Pour vérifier le déploiement:"
echo "curl -H 'Authorization: Bearer YOUR_TOKEN' https://activibe.be/api/club/dashboard"
echo ""
echo "⚠️  Si le problème persiste, vérifiez:"
echo "- Que le serveur a bien redémarré"
echo "- Que les données des clubs existent en base"
echo "- Que l'utilisateur est bien lié à un club via club_managers"
