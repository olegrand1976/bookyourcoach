#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Lesson;
use Carbon\Carbon;

echo "=== VÉRIFICATION DES COURS ===\n\n";

// Compter tous les cours
$totalLessons = Lesson::count();
echo "📊 Total des cours dans la base : {$totalLessons}\n\n";

if ($totalLessons > 0) {
    // Afficher les 10 derniers cours
    echo "📋 Les 10 derniers cours créés :\n";
    echo str_repeat("-", 80) . "\n";
    
    $lessons = Lesson::with(['teacher.user', 'student.user', 'courseType', 'club'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    foreach ($lessons as $lesson) {
        $teacher = $lesson->teacher->user->name ?? 'N/A';
        $student = $lesson->student->user->name ?? 'N/A';
        $courseType = $lesson->courseType->name ?? 'N/A';
        $club = $lesson->club->name ?? 'N/A';
        $status = $lesson->status;
        $startTime = Carbon::parse($lesson->start_time)->format('Y-m-d H:i');
        $endTime = Carbon::parse($lesson->end_time)->format('Y-m-d H:i');
        
        echo "\n";
        echo "ID: {$lesson->id}\n";
        echo "Club: {$club}\n";
        echo "Type de cours: {$courseType}\n";
        echo "Enseignant: {$teacher}\n";
        echo "Étudiant: {$student}\n";
        echo "Statut: {$status}\n";
        echo "Début: {$startTime}\n";
        echo "Fin: {$endTime}\n";
        echo "Prix: {$lesson->price} €\n";
    }
    
    echo "\n" . str_repeat("-", 80) . "\n\n";
    
    // Cours de cette semaine
    $today = Carbon::now();
    $startOfWeek = $today->copy()->startOfWeek();
    $endOfWeek = $today->copy()->endOfWeek();
    
    $thisWeekLessons = Lesson::whereBetween('start_time', [$startOfWeek, $endOfWeek])->count();
    echo "📅 Cours de cette semaine ({$startOfWeek->format('Y-m-d')} au {$endOfWeek->format('Y-m-d')}) : {$thisWeekLessons}\n";
    
    // Cours des 2 prochaines semaines
    $twoWeeksFromNow = $today->copy()->addWeeks(2);
    $nextTwoWeeksLessons = Lesson::whereBetween('start_time', [$today, $twoWeeksFromNow])->count();
    echo "🔮 Cours des 2 prochaines semaines : {$nextTwoWeeksLessons}\n\n";
    
    // Répartition par statut
    echo "📊 Répartition par statut :\n";
    $statusCounts = Lesson::selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->get();
    
    foreach ($statusCounts as $statusCount) {
        echo "  - {$statusCount->status} : {$statusCount->count}\n";
    }
    
} else {
    echo "⚠️  Aucun cours trouvé dans la base de données.\n";
    echo "💡 Vous pouvez créer des cours de test via l'API ou les seeders.\n";
}

echo "\n✅ Vérification terminée.\n";

