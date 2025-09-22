#!/bin/bash

# Script pour exécuter le seeder des données de clubs
# Usage: ./scripts/seed-club-data.sh

echo "🏇 Exécution du seeder des données de clubs..."
echo ""

# Exécuter le seeder
docker-compose -f docker-compose.local.yml exec -T backend php artisan db:seed --class=ClubTestDataSeeder

echo ""
echo "📊 Résumé des données créées :"
echo ""

# Afficher les statistiques
docker-compose -f docker-compose.local.yml exec -T backend php artisan tinker --execute="
echo 'Clubs: ' . App\Models\Club::count() . PHP_EOL;
echo 'Gestionnaires de clubs: ' . App\Models\User::where('role', 'club')->count() . PHP_EOL;
echo 'Enseignants: ' . App\Models\Teacher::count() . PHP_EOL;
echo 'Étudiants: ' . App\Models\Student::count() . PHP_EOL;
echo 'Cours: ' . App\Models\Lesson::count() . PHP_EOL;
echo 'Relations club_teachers: ' . DB::table('club_teachers')->count() . PHP_EOL;
echo 'Relations club_students: ' . DB::table('club_students')->count() . PHP_EOL;
echo 'Relations club_managers: ' . DB::table('club_managers')->count() . PHP_EOL;
"

echo ""
echo "🏇 Clubs créés :"
echo ""

# Afficher les clubs
docker-compose -f docker-compose.local.yml exec -T backend php artisan tinker --execute="
App\Models\Club::all()->each(function(\$club) { 
    echo '• ' . \$club->name . ' (' . \$club->city . ')' . PHP_EOL;
    echo '  Email: ' . \$club->email . PHP_EOL;
    echo '  Téléphone: ' . \$club->phone . PHP_EOL;
    echo '  Prix abonnement: ' . \$club->subscription_price . '€' . PHP_EOL;
    echo '';
});
"

echo ""
echo "✅ Données de test des clubs créées avec succès !"
echo ""
echo "🔑 Comptes de gestionnaires de clubs créés :"
echo "   (Tous les mots de passe sont: password)"
echo ""

# Afficher les comptes de gestionnaires
docker-compose -f docker-compose.local.yml exec -T backend php artisan tinker --execute="
App\Models\User::where('role', 'club')->get()->each(function(\$user) { 
    echo '   ' . \$user->email . ' / password' . PHP_EOL;
});
"

echo ""
echo "🌐 Vous pouvez maintenant tester le dashboard des clubs !"
