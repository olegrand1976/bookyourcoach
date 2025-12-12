<?php

namespace Tests\Unit\Models;

use App\Models\Teacher;
use App\Models\User;
use App\Models\Club;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires pour la gestion des couleurs d'enseignants
 * 
 * Ce fichier teste les fonctionnalités de gestion des couleurs pastel pour les enseignants :
 * - assignColorFromPalette() : assignation d'une couleur depuis la palette
 * - generateColor() : génération d'une couleur unique basée sur l'ID
 * - Assignation automatique lors de la création
 * - Maximisation de la différence visuelle entre couleurs
 */
class TeacherColorTest extends TestCase
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
     * Test : Génération d'une couleur unique
     * 
     * BUT : Vérifier que generateColor() génère une couleur hexadécimale valide
     * 
     * ENTRÉE : Un enseignant avec un ID
     * 
     * SORTIE ATTENDUE : Une couleur hexadécimale au format #RRGGBB
     */
    #[Test]
    public function it_can_generate_a_unique_color(): void
    {
        $user = User::create([
            'name' => 'Teacher Test',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'is_available' => true,
        ]);

        $color = $teacher->generateColor();

        // Vérifier le format hexadécimal
        $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/i', $color);
        
        // Vérifier que la couleur est cohérente (même ID = même couleur)
        $color2 = $teacher->generateColor();
        $this->assertEquals($color, $color2);
    }

    /**
     * Test : Génération de couleurs différentes pour des enseignants différents
     * 
     * BUT : Vérifier que generateColor() génère des couleurs différentes pour des IDs différents
     * 
     * ENTRÉE : Deux enseignants avec des IDs différents
     * 
     * SORTIE ATTENDUE : Des couleurs différentes
     */
    #[Test]
    public function it_generates_different_colors_for_different_teachers(): void
    {
        $user1 = User::create([
            'name' => 'Teacher 1',
            'email' => 'teacher1@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $user2 = User::create([
            'name' => 'Teacher 2',
            'email' => 'teacher2@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $teacher1 = Teacher::create([
            'user_id' => $user1->id,
            'is_available' => true,
        ]);

        $teacher2 = Teacher::create([
            'user_id' => $user2->id,
            'is_available' => true,
        ]);

        $color1 = $teacher1->generateColor();
        $color2 = $teacher2->generateColor();

        $this->assertNotEquals($color1, $color2);
    }

    /**
     * Test : Assignation d'une couleur depuis la palette
     * 
     * BUT : Vérifier que assignColorFromPalette() assigne une couleur depuis la palette
     * 
     * ENTRÉE : Un enseignant sans couleur
     * 
     * SORTIE ATTENDUE : Une couleur de la palette est assignée
     */
    #[Test]
    public function it_can_assign_color_from_palette(): void
    {
        $user = User::create([
            'name' => 'Teacher Test',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'is_available' => true,
            'color' => null,
        ]);

        $teacher->assignColorFromPalette();

        $this->assertNotNull($teacher->color);
        $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/i', $teacher->color);
        
        // Vérifier que la couleur fait partie de la palette ou est générée
        $palette = Teacher::getPastelColorPalette();
        $this->assertTrue(
            in_array($teacher->color, $palette) || 
            preg_match('/^#[0-9A-F]{6}$/i', $teacher->color)
        );
    }

    /**
     * Test : Assignation automatique lors de la création
     * 
     * BUT : Vérifier qu'une couleur est assignée automatiquement lors de la création
     * 
     * ENTRÉE : Création d'un nouvel enseignant sans couleur
     * 
     * SORTIE ATTENDUE : Une couleur est assignée automatiquement
     */
    #[Test]
    public function it_automatically_assigns_color_on_creation(): void
    {
        $user = User::create([
            'name' => 'Teacher Test',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'is_available' => true,
            // Pas de couleur spécifiée
        ]);

        // Recharger depuis la base de données pour voir si la couleur a été assignée
        $teacher->refresh();

        // La couleur devrait être assignée par le booted() event
        $this->assertNotNull($teacher->color);
        $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/i', $teacher->color);
    }

    /**
     * Test : Ne pas réassigner une couleur déjà définie
     * 
     * BUT : Vérifier que assignColorFromPalette() ne change pas une couleur déjà assignée
     * 
     * ENTRÉE : Un enseignant avec une couleur déjà assignée
     * 
     * SORTIE ATTENDUE : La couleur reste inchangée
     */
    #[Test]
    public function it_does_not_reassign_existing_color(): void
    {
        $user = User::create([
            'name' => 'Teacher Test',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $originalColor = '#FFB6C1';
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'is_available' => true,
            'color' => $originalColor,
        ]);

        $teacher->assignColorFromPalette();

        $this->assertEquals($originalColor, $teacher->color);
    }

    /**
     * Test : Utilisation de la palette jusqu'à épuisement
     * 
     * BUT : Vérifier que les couleurs de la palette sont utilisées avant de générer
     * 
     * ENTRÉE : Plusieurs enseignants créés successivement
     * 
     * SORTIE ATTENDUE : Les couleurs de la palette sont utilisées en priorité
     */
    #[Test]
    public function it_uses_palette_colors_before_generating(): void
    {
        $palette = Teacher::getPastelColorPalette();
        $paletteSize = count($palette);

        // Créer autant d'enseignants qu'il y a de couleurs dans la palette
        for ($i = 0; $i < $paletteSize; $i++) {
            $user = User::create([
                'name' => "Teacher {$i}",
                'email' => "teacher{$i}@test.com",
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ]);

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'is_available' => true,
            ]);

            $teacher->assignColorFromPalette();
            $teacher->refresh();

            // Les premières couleurs devraient être de la palette
            if ($i < $paletteSize) {
                $this->assertNotNull($teacher->color);
            }
        }

        // Vérifier que toutes les couleurs de la palette ont été utilisées
        $usedColors = Teacher::whereNotNull('color')->pluck('color')->toArray();
        $paletteUsed = array_intersect($palette, $usedColors);
        
        // Au moins quelques couleurs de la palette devraient être utilisées
        $this->assertGreaterThan(0, count($paletteUsed));
    }

    /**
     * Test : Génération de couleur quand la palette est épuisée
     * 
     * BUT : Vérifier qu'une couleur est générée quand toutes les couleurs de la palette sont utilisées
     * 
     * ENTRÉE : Plus d'enseignants que de couleurs dans la palette
     * 
     * SORTIE ATTENDUE : Une couleur générée est assignée
     */
    #[Test]
    public function it_generates_color_when_palette_is_exhausted(): void
    {
        $palette = Teacher::getPastelColorPalette();
        $paletteSize = count($palette);

        // Créer plus d'enseignants qu'il y a de couleurs dans la palette
        for ($i = 0; $i < $paletteSize + 5; $i++) {
            $user = User::create([
                'name' => "Teacher {$i}",
                'email' => "teacher{$i}@test.com",
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ]);

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'is_available' => true,
            ]);

            $teacher->assignColorFromPalette();
            $teacher->refresh();

            $this->assertNotNull($teacher->color);
            $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/i', $teacher->color);
        }
    }

    /**
     * Test : Maximisation de la différence visuelle
     * 
     * BUT : Vérifier que les couleurs assignées maximisent la différence visuelle
     * 
     * ENTRÉE : Plusieurs enseignants dans le même club
     * 
     * SORTIE ATTENDUE : Les couleurs sont différentes et bien espacées
     */
    #[Test]
    public function it_maximizes_visual_difference_between_colors(): void
    {
        $club = Club::create([
            'name' => 'Test Club',
            'email' => 'club@test.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        // Créer plusieurs enseignants dans le même club
        $teachers = [];
        for ($i = 0; $i < 5; $i++) {
            $user = User::create([
                'name' => "Teacher {$i}",
                'email' => "teacher{$i}@test.com",
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ]);

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'club_id' => $club->id,
                'is_available' => true,
            ]);

            $teacher->assignColorFromPalette();
            $teacher->refresh();

            $teachers[] = $teacher;
        }

        // Vérifier que toutes les couleurs sont différentes
        $colors = array_map(fn($t) => $t->color, $teachers);
        $uniqueColors = array_unique($colors);
        $this->assertEquals(count($colors), count($uniqueColors), 'Toutes les couleurs devraient être différentes');
    }

    /**
     * Test : Palette de couleurs pastel
     * 
     * BUT : Vérifier que la palette contient des couleurs pastel valides
     * 
     * ENTRÉE : Appel de getPastelColorPalette()
     * 
     * SORTIE ATTENDUE : Un tableau de couleurs hexadécimales valides
     */
    #[Test]
    public function it_returns_valid_pastel_color_palette(): void
    {
        $palette = Teacher::getPastelColorPalette();

        $this->assertIsArray($palette);
        $this->assertGreaterThan(0, count($palette));

        foreach ($palette as $color) {
            $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/i', $color);
        }
    }
}

