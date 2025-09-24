#!/bin/bash

# Script alternatif pour exécuter le seeder des clubs
# Ce script peut être exécuté directement sur le serveur de production
echo "🌱 Exécution du seeder des clubs (script alternatif)"
echo ""

# Vérifier que nous sommes dans un projet Laravel
if [ ! -f "artisan" ]; then
    echo "❌ Ce script doit être exécuté dans le répertoire racine du projet Laravel"
    echo "💡 Naviguez vers le répertoire du projet et relancez le script"
    exit 1
fi

echo "✅ Projet Laravel détecté"
echo ""

# Vérifier que le seeder existe
if [ ! -f "database/seeders/ClubTestDataSeeder.php" ]; then
    echo "❌ Le seeder ClubTestDataSeeder.php n'existe pas"
    echo "💡 Assurez-vous que le seeder est déployé sur le serveur"
    exit 1
fi

echo "✅ Seeder ClubTestDataSeeder trouvé"
echo ""

# Afficher les informations avant l'exécution
echo "📋 Le seeder va créer:"
echo "   - 3 clubs avec des données réalistes"
echo "   - 3 gestionnaires de clubs (utilisateurs avec rôle 'club')"
echo "   - Liaisons dans la table club_managers"
echo "   - Enseignants et étudiants liés aux clubs"
echo "   - Cours de démonstration"
echo ""

# Demander confirmation
read -p "🤔 Voulez-vous continuer ? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Exécution annulée"
    exit 1
fi

echo ""
echo "🌱 Exécution du seeder ClubTestDataSeeder..."

# Configuration temporaire pour l'accès local à MySQL Docker
echo "🔧 Configuration temporaire pour l'accès local à MySQL..."
export DB_HOST=127.0.0.1
export DB_PORT=3308

# Exécuter le seeder
php artisan db:seed --class=ClubTestDataSeeder

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Seeder exécuté avec succès !"
    echo ""
    echo "🔑 Comptes de gestionnaires créés:"
    echo "   - manager@club-equestre-de-la-vallee-doree.fr"
    echo "   - manager@centre-equestre-des-ecuries-du-soleil.fr"
    echo "   - manager@poney-club-des-petits-cavaliers.fr"
    echo "   Mot de passe: password"
    echo ""
    echo "🔍 Prochaines étapes:"
    echo "1. Tester l'API: https://activibe.be/api/club/dashboard"
    echo "2. Vérifier que les données s'affichent dans le dashboard"
    echo ""
    echo "🧪 Pour tester l'API:"
    echo "curl -H 'Authorization: Bearer YOUR_TOKEN' https://activibe.be/api/club/dashboard"
else
    echo ""
    echo "❌ Erreur lors de l'exécution du seeder"
    echo "💡 Vérifiez les logs pour plus de détails:"
    echo "   tail -f storage/logs/laravel.log"
fi
