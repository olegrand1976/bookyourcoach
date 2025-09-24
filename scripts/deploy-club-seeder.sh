#!/bin/bash

# Script pour exécuter le seeder des clubs sur le serveur de production
echo "🌱 Exécution du seeder des clubs sur le serveur de production"
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

# Vérifier que le seeder existe
echo "📁 Vérification du seeder..."
if [ ! -f "database/seeders/ClubTestDataSeeder.php" ]; then
    echo "❌ Le seeder ClubTestDataSeeder.php n'existe pas"
    exit 1
fi

echo "✅ Seeder trouvé"
echo ""

# Ajouter le seeder s'il a été modifié
echo "📝 Ajout des fichiers modifiés..."
git add database/seeders/ClubTestDataSeeder.php
echo ""

# Commit des changements
echo "💾 Commit des changements..."
git commit -m "feat: Mise à jour du seeder des clubs

- Amélioration du ClubTestDataSeeder
- Création des gestionnaires de clubs
- Liaison des utilisateurs aux clubs via club_managers"
echo ""

# Push vers le serveur
echo "🌐 Push vers le serveur de production..."
git push origin main
echo ""

echo "✅ Déploiement du seeder terminé !"
echo ""
echo "📋 Prochaines étapes:"
echo "1. Attendre que le serveur redémarre automatiquement (2-3 minutes)"
echo "2. Exécuter le seeder sur le serveur de production"
echo "3. Tester l'API: https://activibe.be/api/club/dashboard"
echo ""
echo "🔧 Pour exécuter le seeder sur le serveur:"
echo "ssh user@activibe.be 'cd /path/to/project && php artisan db:seed --class=ClubTestDataSeeder'"
echo ""
echo "🔍 Ou exécuter tous les seeders:"
echo "ssh user@activibe.be 'cd /path/to/project && php artisan db:seed'"
echo ""
echo "⚠️  Note: Le seeder va créer:"
echo "- 3 clubs avec des données réalistes"
echo "- 3 gestionnaires de clubs (utilisateurs avec rôle 'club')"
echo "- Liaisons dans la table club_managers"
echo "- Enseignants et étudiants liés aux clubs"
echo "- Cours de démonstration"
