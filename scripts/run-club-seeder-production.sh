#!/bin/bash

# Script pour exécuter le seeder des clubs sur le serveur de production
echo "🌱 Exécution du seeder des clubs sur le serveur de production"
echo ""

# Configuration du serveur (à adapter selon votre configuration)
SERVER_HOST="activibe.be"
SERVER_USER="root"  # ou votre utilisateur SSH
SERVER_PATH="/var/www/html"  # ou le chemin vers votre projet Laravel

echo "📋 Configuration du serveur:"
echo "   Host: $SERVER_HOST"
echo "   User: $SERVER_USER"
echo "   Path: $SERVER_PATH"
echo ""

# Vérifier la connexion SSH
echo "🔌 Test de connexion SSH..."
if ! ssh -o ConnectTimeout=10 -o BatchMode=yes $SERVER_USER@$SERVER_HOST "echo 'Connexion SSH OK'" 2>/dev/null; then
    echo "❌ Impossible de se connecter au serveur via SSH"
    echo "💡 Vérifiez:"
    echo "   - Que la clé SSH est configurée"
    echo "   - Que l'utilisateur SSH est correct"
    echo "   - Que le serveur est accessible"
    echo ""
    echo "🔧 Alternative: Exécutez manuellement sur le serveur:"
    echo "   cd $SERVER_PATH"
    echo "   php artisan db:seed --class=ClubTestDataSeeder"
    exit 1
fi

echo "✅ Connexion SSH réussie"
echo ""

# Exécuter le seeder
echo "🌱 Exécution du seeder ClubTestDataSeeder..."
ssh $SERVER_USER@$SERVER_HOST "cd $SERVER_PATH && php artisan db:seed --class=ClubTestDataSeeder"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Seeder exécuté avec succès !"
    echo ""
    echo "📋 Données créées:"
    echo "   - 3 clubs avec des données réalistes"
    echo "   - 3 gestionnaires de clubs (utilisateurs avec rôle 'club')"
    echo "   - Liaisons dans la table club_managers"
    echo "   - Enseignants et étudiants liés aux clubs"
    echo "   - Cours de démonstration"
    echo ""
    echo "🔍 Prochaines étapes:"
    echo "1. Tester l'API: https://activibe.be/api/club/dashboard"
    echo "2. Vérifier que les données s'affichent dans le dashboard"
    echo ""
    echo "🔑 Comptes de gestionnaires créés:"
    echo "   - manager@club-equestre-de-la-vallee-doree.fr"
    echo "   - manager@centre-equestre-des-ecuries-du-soleil.fr"
    echo "   - manager@poney-club-des-petits-cavaliers.fr"
    echo "   Mot de passe: password"
else
    echo ""
    echo "❌ Erreur lors de l'exécution du seeder"
    echo "💡 Vérifiez les logs du serveur pour plus de détails"
fi
