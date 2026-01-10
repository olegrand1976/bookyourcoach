#!/bin/bash

# Script pour trouver les cours avec des séances futures pour test
# Usage: ./scripts/find-recurring-lessons.sh

echo "🔍 Recherche des cours avec abonnements pour test de suppression..."
echo ""

docker compose -f docker-compose.local.yml exec backend php artisan tinker --execute="
\$lessons = App\Models\Lesson::whereHas('subscriptionInstances')
    ->with(['subscriptionInstances', 'student.user', 'teacher.user', 'courseType'])
    ->orderBy('start_time', 'asc')
    ->get();

if (\$lessons->isEmpty()) {
    echo '❌ Aucun cours avec abonnement trouvé' . PHP_EOL;
    exit;
}

echo '✅ ' . \$lessons->count() . ' cours trouvés avec abonnements' . PHP_EOL;
echo '';

\$grouped = \$lessons->groupBy('student_id');

foreach (\$grouped as \$studentId => \$studentLessons) {
    \$firstLesson = \$studentLessons->first();
    \$student = \$firstLesson->student;
    \$studentName = \$student->user ? \$student->user->name : ('Élève #' . \$studentId);
    
    echo '📚 ' . \$studentName . ' (' . \$studentLessons->count() . ' cours)' . PHP_EOL;
    echo '   Abonnement ID: ' . \$firstLesson->subscriptionInstances->first()->id . PHP_EOL;
    echo '   Enseignant: ' . (\$firstLesson->teacher->user->name ?? 'N/A') . PHP_EOL;
    echo '   Prochain cours: ' . \$studentLessons->first()->start_time . PHP_EOL;
    echo '   Dernier cours: ' . \$studentLessons->last()->start_time . PHP_EOL;
    echo '';
}

echo '💡 Pour tester la suppression des cours futurs:' . PHP_EOL;
echo '   1. Allez sur http://localhost:3000/club/planning' . PHP_EOL;
echo '   2. Trouvez un cours d\'un des élèves ci-dessus' . PHP_EOL;
echo '   3. Cliquez sur \"Supprimer\"' . PHP_EOL;
echo '   4. Vous devriez voir l\'option \"Supprimer tous les cours futurs\"' . PHP_EOL;
"
