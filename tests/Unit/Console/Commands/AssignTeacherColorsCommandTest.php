<?php

namespace Tests\Unit\Console\Commands;

use App\Models\Teacher;
use App\Models\User;
use App\Models\Club;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires pour la commande AssignTeacherColors
 * 
 * Ce fichier teste la commande artisan teachers:assign-colors :
 * - Assignation de couleurs aux enseignants sans couleur
 * - Option --force pour réassigner toutes les couleurs
 * - Gestion des erreurs (colonne inexistante)
 */
class AssignTeacherColorsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer la colonne color si elle n'existe pas (pour les tests)
        if (!Schema::hasColumn('teachers', 'color')) {
            Schema::table('teachers', function ($table) {
                $table->string('color', 7)->nullable()->after('total_lessons');
            });
        }
    }

    /**
     * Test : Assignation de couleurs aux enseignants sans couleur
     * 
     * BUT : Vérifier que la commande assigne des couleurs aux enseignants qui n'en ont pas
     * 
     * ENTRÉE : Des enseignants sans couleur
     * 
     * SORTIE ATTENDUE : Tous les enseignants ont une couleur assignée
     */
    #[Test]
    public function it_assigns_colors_to_teachers_without_color(): void
    {
        // Créer des enseignants sans couleur
        for ($i = 0; $i < 3; $i++) {
            $user = User::create([
                'name' => "Teacher {$i}",
                'email' => "teacher{$i}@test.com",
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ]);

            Teacher::create([
                'user_id' => $user->id,
                'is_available' => true,
                'color' => null,
            ]);
        }

        // Exécuter la commande
        $exitCode = Artisan::call('teachers:assign-colors');

        // Vérifier que la commande a réussi
        $this->assertEquals(0, $exitCode);

        // Vérifier que tous les enseignants ont maintenant une couleur
        $teachers = Teacher::whereNull('color')->get();
        $this->assertCount(0, $teachers, 'Tous les enseignants devraient avoir une couleur');

        // Vérifier que les couleurs sont valides
        $teachersWithColor = Teacher::whereNotNull('color')->get();
        foreach ($teachersWithColor as $teacher) {
            $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/i', $teacher->color);
        }
    }

    /**
     * Test : Ne pas réassigner les couleurs existantes sans --force
     * 
     * BUT : Vérifier que la commande ne change pas les couleurs déjà assignées
     * 
     * ENTRÉE : Des enseignants avec des couleurs déjà assignées
     * 
     * SORTIE ATTENDUE : Les couleurs restent inchangées
     */
    #[Test]
    public function it_does_not_reassign_existing_colors_without_force(): void
    {
        // Créer des enseignants avec des couleurs
        $originalColors = [];
        for ($i = 0; $i < 3; $i++) {
            $user = User::create([
                'name' => "Teacher {$i}",
                'email' => "teacher{$i}@test.com",
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ]);

            $color = "#FF{$i}0000";
            $originalColors[$i] = $color;

            Teacher::create([
                'user_id' => $user->id,
                'is_available' => true,
                'color' => $color,
            ]);
        }

        // Exécuter la commande sans --force
        $exitCode = Artisan::call('teachers:assign-colors');

        // Vérifier que la commande a réussi
        $this->assertEquals(0, $exitCode);

        // Vérifier que les couleurs sont restées inchangées
        $teachers = Teacher::all();
        foreach ($teachers as $index => $teacher) {
            $this->assertEquals($originalColors[$index], $teacher->color);
        }
    }

    /**
     * Test : Réassignation avec --force
     * 
     * BUT : Vérifier que --force réassigne toutes les couleurs
     * 
     * ENTRÉE : Des enseignants avec des couleurs, commande avec --force
     * 
     * SORTIE ATTENDUE : Toutes les couleurs sont réassignées
     */
    #[Test]
    public function it_reassigns_all_colors_with_force_option(): void
    {
        // Créer des enseignants avec des couleurs valides
        $colors = ['#FF0000', '#00FF00', '#0000FF'];
        for ($i = 0; $i < 3; $i++) {
            $user = User::create([
                'name' => "Teacher {$i}",
                'email' => "teacher{$i}@test.com",
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ]);

            Teacher::create([
                'user_id' => $user->id,
                'is_available' => true,
                'color' => $colors[$i],
            ]);
        }

        // Exécuter la commande avec --force
        $exitCode = Artisan::call('teachers:assign-colors', ['--force' => true]);

        // Vérifier que la commande a réussi
        $this->assertEquals(0, $exitCode);

        // Vérifier que toutes les couleurs sont valides (elles ont été réassignées)
        $teachers = Teacher::all();
        foreach ($teachers as $teacher) {
            $teacher->refresh(); // Recharger depuis la base de données
            $this->assertNotNull($teacher->color, "Teacher {$teacher->id} should have a color");
            // Vérifier le format hexadécimal (6 caractères après #)
            $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/i', $teacher->color, "Color '{$teacher->color}' for teacher {$teacher->id} is not in valid hex format");
        }
    }

    /**
     * Test : Message quand tous les enseignants ont déjà une couleur
     * 
     * BUT : Vérifier que la commande affiche un message approprié
     * 
     * ENTRÉE : Tous les enseignants ont déjà une couleur
     * 
     * SORTIE ATTENDUE : Message informatif affiché
     */
    #[Test]
    public function it_shows_message_when_all_teachers_have_colors(): void
    {
        // Créer des enseignants avec des couleurs
        for ($i = 0; $i < 2; $i++) {
            $user = User::create([
                'name' => "Teacher {$i}",
                'email' => "teacher{$i}@test.com",
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ]);

            Teacher::create([
                'user_id' => $user->id,
                'is_available' => true,
                'color' => "#FF{$i}0000",
            ]);
        }

        // Exécuter la commande
        $exitCode = Artisan::call('teachers:assign-colors');
        $output = Artisan::output();

        // Vérifier que la commande a réussi
        $this->assertEquals(0, $exitCode);

        // Vérifier que le message approprié est affiché
        $this->assertStringContainsString('déjà une couleur', $output);
    }

    /**
     * Test : Gestion de l'erreur si la colonne n'existe pas
     * 
     * BUT : Vérifier que la commande gère l'absence de la colonne color
     * 
     * ENTRÉE : Table teachers sans colonne color
     * 
     * SORTIE ATTENDUE : Message d'erreur approprié
     */
    #[Test]
    public function it_handles_missing_color_column(): void
    {
        // Supprimer temporairement la colonne color si elle existe
        if (Schema::hasColumn('teachers', 'color')) {
            Schema::table('teachers', function ($table) {
                $table->dropColumn('color');
            });
        }

        // Créer un enseignant
        $user = User::create([
            'name' => 'Teacher Test',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        Teacher::create([
            'user_id' => $user->id,
            'is_available' => true,
        ]);

        // Exécuter la commande
        $exitCode = Artisan::call('teachers:assign-colors');
        $output = Artisan::output();

        // Vérifier que la commande a échoué avec le code approprié
        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('colonne "color" n\'existe pas', $output);

        // Recréer la colonne pour les autres tests
        Schema::table('teachers', function ($table) {
            $table->string('color', 7)->nullable()->after('total_lessons');
        });
    }
}

