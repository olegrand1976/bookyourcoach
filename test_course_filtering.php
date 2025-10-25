<?php

/**
 * Script de test pour vérifier le filtrage des types de cours par club
 * À utiliser avec : php artisan tinker < test_course_filtering.php
 */

use App\Models\Club;
use App\Models\CourseType;
use App\Models\Discipline;

echo "=== TEST DE FILTRAGE DES TYPES DE COURS ===\n\n";

// Test 1: Récupérer un club
$club = Club::first();
if (!$club) {
    echo "❌ Aucun club trouvé dans la base de données\n";
    exit(1);
}

echo "✅ Club trouvé: {$club->name} (ID: {$club->id})\n\n";

// Test 2: Récupérer les disciplines du club
$disciplines = $club->disciplines()->get();
echo "📊 Disciplines du club ({$disciplines->count()}):\n";
foreach ($disciplines as $d) {
    echo "  - {$d->name} (ID: {$d->id})\n";
}
echo "\n";

// Test 3: Récupérer les IDs des disciplines
$disciplineIds = $club->disciplines()->pluck('id')->toArray();
echo "🔑 IDs des disciplines: " . implode(', ', $disciplineIds) . "\n\n";

// Test 4: Récupérer les types de cours du club
echo "🔍 Types de cours du club:\n";

// Types spécifiques aux disciplines du club
if (!empty($disciplineIds)) {
    $typesForDisciplines = CourseType::whereIn('discipline_id', $disciplineIds)
        ->where('is_active', true)
        ->get();
    
    echo "  📋 Types liés aux disciplines ({$typesForDisciplines->count()}):\n";
    foreach ($typesForDisciplines as $t) {
        echo "    - {$t->name} (discipline_id: {$t->discipline_id})\n";
    }
}

// Types génériques
$genericTypes = CourseType::whereNull('discipline_id')
    ->where('is_active', true)
    ->get();

echo "  ⭐ Types génériques ({$genericTypes->count()}):\n";
foreach ($genericTypes as $t) {
    echo "    - {$t->name} (discipline_id: NULL)\n";
}

// Test 5: Total
$allTypes = CourseType::where(function($query) use ($disciplineIds) {
    if (!empty($disciplineIds)) {
        $query->whereIn('discipline_id', $disciplineIds);
    }
    $query->orWhereNull('discipline_id');
})
->where('is_active', true)
->get();

echo "\n✅ TOTAL types pour ce club: {$allTypes->count()}\n";

// Test 6: Comparaison avec TOUS les types
$allCoursesInSystem = CourseType::where('is_active', true)->get();
echo "❌ TOUS les types du système: {$allCoursesInSystem->count()}\n";

echo "\n✅ Filtrage appliqué avec succès!\n";

