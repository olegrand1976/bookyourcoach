<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Email d'un élève côté club : modification depuis sa fiche (PUT /api/club/students/{id})
 * et création (POST /api/club/students).
 *
 * L'email porte le compte de connexion (users.email unique) : le même email sur plusieurs
 * élèves les rattache au même compte, dont le titulaire voit alors tous les élèves.
 */
class ClubStudentEmailUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->club = Club::find($this->actingAsClub()->club_id);
    }

    private function enroll(Student $student): Student
    {
        DB::table('club_students')->insert([
            'club_id' => $this->club->id,
            'student_id' => $student->id,
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $student;
    }

    private function studentWithAccount(string $email, array $userAttributes = []): Student
    {
        $account = User::factory()->create(array_merge(['role' => 'student', 'email' => $email], $userAttributes));

        return $this->enroll(Student::factory()->create(['club_id' => $this->club->id, 'user_id' => $account->id]));
    }

    private function studentWithoutAccount(): Student
    {
        return $this->enroll(Student::factory()->create(['club_id' => $this->club->id, 'user_id' => null]));
    }

    /** Charge utile identique à celle envoyée par EditStudentModal.vue. */
    private function payload(Student $student, string $email, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'date_of_birth' => null,
            'goals' => null,
            'medical_info' => null,
            'disciplines' => null,
            'email' => $email,
            'phone' => (string) ($student->phone ?? ''),
        ], $overrides);
    }

    private function linked(Student $a, Student $b): bool
    {
        return DB::table('student_family_links')
            ->where(fn ($q) => $q->where('primary_student_id', $a->id)->where('linked_student_id', $b->id))
            ->orWhere(fn ($q) => $q->where('primary_student_id', $b->id)->where('linked_student_id', $a->id))
            ->exists();
    }

    #[Test]
    public function le_club_modifie_l_email_d_un_eleve_avec_compte()
    {
        $student = $this->studentWithAccount('eleve@example.com');

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'nouveau@example.com'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['id' => $student->user_id, 'email' => 'nouveau@example.com']);
    }

    #[Test]
    public function l_email_d_un_autre_eleve_fusionne_les_deux_comptes_qui_restent()
    {
        $holder = $this->studentWithAccount('parent@example.com', ['name' => 'Parent Titulaire']);
        $student = $this->studentWithAccount('eleve@example.com');
        $ownAccountId = $student->user_id;

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'parent@example.com'))
            ->assertOk()
            ->assertJson(['success' => true, 'merged_accounts' => true, 'attached_to_account' => false, 'user_created' => false]);

        // Les deux comptes restent, avec leur email ; l'élève reste sur le sien.
        $this->assertSame($ownAccountId, $student->fresh()->user_id);
        $this->assertDatabaseHas('users', ['id' => $ownAccountId, 'email' => 'eleve@example.com', 'household_root_id' => $holder->user_id]);
        $this->assertDatabaseHas('users', ['id' => $holder->user_id, 'email' => 'parent@example.com', 'name' => 'Parent Titulaire', 'household_root_id' => null]);
        $this->assertTrue($this->linked($holder, $student));

        // Chaque connexion voit les élèves des deux comptes.
        foreach ([$holder->user_id, $ownAccountId] as $accountId) {
            $household = User::find($accountId)->getHouseholdStudentIds();
            $this->assertContains($holder->id, $household);
            $this->assertContains($student->id, $household);
        }
    }

    #[Test]
    public function un_troisieme_compte_rejoint_le_groupe_deja_fusionne()
    {
        $a = $this->studentWithAccount('a@example.com');
        $b = $this->studentWithAccount('b@example.com');
        $c = $this->studentWithAccount('c@example.com');

        $this->putJson("/api/club/students/{$a->id}", $this->payload($a, 'b@example.com'))->assertOk();
        // C prend l'email de A (lui-même fusionné avec B) : les trois comptes forment un foyer.
        $this->putJson("/api/club/students/{$c->id}", $this->payload($c, 'a@example.com'))->assertOk();

        foreach ([$a, $b, $c] as $s) {
            $this->assertEqualsCanonicalizing(
                [$a->id, $b->id, $c->id],
                array_values(array_intersect([$a->id, $b->id, $c->id], User::find($s->user_id)->getHouseholdStudentIds()))
            );
        }
        $this->assertSame(2, User::where('household_root_id', $b->user_id)->count());
    }

    #[Test]
    public function fusionner_deux_comptes_deja_fusionnes_ne_change_rien()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $student = $this->studentWithAccount('eleve@example.com');
        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'parent@example.com'))->assertOk();

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student->fresh(), 'parent@example.com'))->assertOk();

        $this->assertSame(1, DB::table('student_family_links')->count());
        $this->assertDatabaseHas('users', ['id' => $student->user_id, 'household_root_id' => $holder->user_id]);
    }

    #[Test]
    public function un_eleve_sans_compte_est_rattache_au_compte_existant_sans_nouveau_compte()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $student = $this->studentWithoutAccount();
        $usersBefore = User::count();

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'Parent@Example.com'))
            ->assertOk()
            ->assertJson(['attached_to_account' => true, 'user_created' => false]);

        $this->assertSame($holder->user_id, $student->fresh()->user_id);
        $this->assertSame($usersBefore, User::count());
    }

    #[Test]
    public function modifier_le_nom_d_un_eleve_rattache_ne_renomme_pas_le_compte_partage()
    {
        $holder = $this->studentWithAccount('parent@example.com', ['name' => 'Parent Titulaire']);
        $child = $this->studentWithoutAccount();
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child, 'parent@example.com'))->assertOk();

        $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), 'parent@example.com', [
            'first_name' => 'Enfant',
            'phone' => '0470000000',
        ]))->assertOk();

        $this->assertDatabaseHas('users', ['id' => $holder->user_id, 'name' => 'Parent Titulaire']);
        $this->assertDatabaseHas('students', ['id' => $child->id, 'first_name' => 'Enfant', 'phone' => '0470000000']);
    }

    #[Test]
    public function un_nouvel_email_sur_un_compte_partage_demande_confirmation()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $child = $this->studentWithoutAccount();
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child, 'parent@example.com'))->assertOk();

        // Un élève du même compte inscrit dans un autre club : compté, jamais nommé.
        $otherClub = Club::factory()->create();
        $foreign = Student::factory()->create(['club_id' => $otherClub->id, 'user_id' => $holder->user_id, 'first_name' => 'Secret']);
        DB::table('club_students')->insert(['club_id' => $otherClub->id, 'student_id' => $foreign->id, 'is_active' => true, 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), 'nouveau@example.com', ['goals' => 'Galop 3']))
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'requires_confirmation' => true,
                'data' => ['account_email' => 'parent@example.com', 'new_email' => 'nouveau@example.com', 'other_students_count' => 1],
            ]);

        $this->assertEqualsCanonicalizing([$holder->id, $child->id], collect($response->json('data.students'))->pluck('id')->all());
        $this->assertStringNotContainsString('Secret', $response->getContent());
        // Rien n'est enregistré tant que le club n'a pas choisi.
        $this->assertDatabaseHas('users', ['id' => $holder->user_id, 'email' => 'parent@example.com']);
        $this->assertNotSame('Galop 3', $child->fresh()->goals);
    }

    #[Test]
    public function changer_l_email_pour_tous_modifie_la_connexion_du_compte_partage()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $child = $this->studentWithoutAccount();
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child, 'parent@example.com'))->assertOk();
        $usersBefore = User::count();

        $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), 'famille.nouveau@example.com', ['email_scope' => 'household']))
            ->assertOk()
            ->assertJson(['user_created' => false]);

        $this->assertDatabaseHas('users', ['id' => $holder->user_id, 'email' => 'famille.nouveau@example.com']);
        $this->assertSame($holder->user_id, $child->fresh()->user_id);
        $this->assertSame($usersBefore, User::count());
        $this->assertTrue($this->linked($holder, $child));
    }

    #[Test]
    public function changer_l_email_seulement_pour_cet_eleve_lui_donne_son_propre_compte()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $child = $this->studentWithoutAccount();
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child, 'parent@example.com'))->assertOk();

        $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), 'enfant@example.com', ['email_scope' => 'student']))
            ->assertOk()
            ->assertJson(['user_created' => true]);

        $child->refresh();
        $this->assertNotSame($holder->user_id, $child->user_id);
        $this->assertDatabaseHas('users', ['id' => $child->user_id, 'email' => 'enfant@example.com']);
        // L'identifiant du titulaire n'a pas bougé, le lien est retiré.
        $this->assertDatabaseHas('users', ['id' => $holder->user_id, 'email' => 'parent@example.com']);
        $this->assertFalse($this->linked($holder, $child));
    }

    #[Test]
    public function un_email_scope_invalide_est_refuse()
    {
        $student = $this->studentWithAccount('eleve@example.com');

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'x@example.com', ['email_scope' => 'tout']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email_scope']);
    }

    #[Test]
    public function vider_l_email_d_un_eleve_rattache_le_detache_du_compte()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $child = $this->studentWithoutAccount();
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child, 'parent@example.com'))->assertOk();

        $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), ''))->assertOk();

        $child->refresh();
        $this->assertNull($child->user_id);
        $this->assertNotNull($child->invite_code);
        $this->assertFalse($this->linked($holder, $child));
        $this->assertDatabaseHas('users', ['id' => $holder->user_id, 'email' => 'parent@example.com']);
    }

    #[Test]
    public function seulement_pour_le_titulaire_d_un_compte_partage_ne_change_pas_la_connexion_des_autres()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $child = $this->studentWithoutAccount();
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child, 'parent@example.com'))->assertOk();
        $sharedUserId = $holder->user_id;

        $this->putJson("/api/club/students/{$holder->id}", $this->payload($holder, 'parent.nouveau@example.com', ['email_scope' => 'student']))
            ->assertOk()
            ->assertJson(['user_created' => true]);

        // Le titulaire part avec un nouveau compte ; l'enfant garde la connexion d'origine.
        $this->assertNotSame($sharedUserId, $holder->fresh()->user_id);
        $this->assertSame($sharedUserId, $child->fresh()->user_id);
        $this->assertDatabaseHas('users', ['id' => $sharedUserId, 'email' => 'parent@example.com']);
        $this->assertFalse($this->linked($holder, $child));
    }

    #[Test]
    public function un_eleve_deja_rattache_qui_garde_le_meme_email_reste_rattache()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $child = $this->studentWithoutAccount();
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child, 'parent@example.com'))->assertOk();

        // Nouvel enregistrement de la fiche sans toucher à l'email (ex. changement de prénom).
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), 'parent@example.com', ['goals' => 'Galop 2']))
            ->assertOk()
            ->assertJson(['attached_to_account' => false, 'user_created' => false]);

        $this->assertSame($holder->user_id, $child->fresh()->user_id);
        $this->assertTrue($this->linked($holder, $child));
        $this->assertSame(1, DB::table('student_family_links')->count());
    }

    #[Test]
    public function un_email_d_enseignant_est_refuse()
    {
        $student = $this->studentWithAccount('eleve@example.com');
        User::factory()->create(['role' => 'teacher', 'email' => 'coach@example.com']);

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'coach@example.com'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseHas('users', ['id' => $student->user_id, 'email' => 'eleve@example.com']);
    }

    #[Test]
    public function un_email_mal_forme_renvoie_une_erreur_de_validation()
    {
        $student = $this->studentWithAccount('eleve@example.com');

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'pas-un-email'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function vider_l_email_du_seul_eleve_d_un_compte_est_refuse_proprement()
    {
        $student = $this->studentWithAccount('eleve@example.com');

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, ''))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseHas('users', ['id' => $student->user_id, 'email' => 'eleve@example.com']);
    }

    #[Test]
    public function ajouter_un_email_libre_a_un_eleve_sans_compte_cree_son_compte()
    {
        $student = $this->studentWithoutAccount();

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'premier@example.com'))
            ->assertOk()
            ->assertJson(['success' => true, 'user_created' => true]);

        $this->assertDatabaseHas('users', ['email' => 'premier@example.com', 'role' => 'student']);
    }

    #[Test]
    public function creer_un_eleve_avec_l_email_d_un_compte_eleve_le_rattache_a_ce_compte()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $usersBefore = User::count();

        $response = $this->postJson('/api/club/students', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'parent@example.com',
        ])
            ->assertStatus(201)
            ->assertJson(['user_created' => false, 'attached_to_account' => true]);

        $student = Student::find($response->json('data.id'));
        $this->assertSame($holder->user_id, $student->user_id);
        $this->assertTrue($this->linked($holder, $student));
        $this->assertSame($usersBefore, User::count());
    }

    /**
     * Flux complet : le club crée le parent puis l'enfant, met le même email sur l'enfant ;
     * le parent retrouve les deux élèves depuis sa connexion, le club voit le lien sur la
     * fiche. Puis l'enfant reçoit son propre email et sort du compte.
     */
    #[Test]
    public function flux_complet_meme_email_puis_separation()
    {
        $clubUser = auth()->user();

        $parentId = $this->postJson('/api/club/students', [
            'first_name' => 'Paul',
            'last_name' => 'Parent',
            'email' => 'famille@example.com',
        ])->assertStatus(201)->assertJson(['user_created' => true])->json('data.id');

        $childId = $this->postJson('/api/club/students', [
            'first_name' => 'Léa',
            'last_name' => 'Parent',
        ])->assertStatus(201)->assertJson(['user_created' => false])->json('data.id');

        $child = Student::find($childId);
        $this->putJson("/api/club/students/{$childId}", $this->payload($child, 'famille@example.com'))
            ->assertOk()
            ->assertJson(['attached_to_account' => true]);

        // Fiche club : l'enfant apparaît lié au parent (et réciproquement).
        $this->getJson("/api/club/students/{$childId}/linked")
            ->assertOk()
            ->assertJsonFragment(['id' => $parentId]);
        $this->getJson("/api/club/students/{$parentId}/linked")
            ->assertOk()
            ->assertJsonFragment(['id' => $childId]);

        // Connexion du parent : les deux élèves sont sur le même compte.
        $account = User::find(Student::find($parentId)->user_id);
        \Laravel\Sanctum\Sanctum::actingAs($account);
        $ids = collect($this->getJson('/api/student/linked-accounts')->assertOk()->json('data'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$parentId, $childId], $ids);
        $children = collect($this->getJson('/api/student/family/children')->assertOk()->json('data'))->pluck('id')->all();
        $this->assertContains($childId, $children);

        // Le club donne à l'enfant son propre email : confirmation demandée (compte
        // partagé), puis choix « seulement cet élève » → il quitte le compte du parent.
        \Laravel\Sanctum\Sanctum::actingAs($clubUser);
        $this->putJson("/api/club/students/{$childId}", $this->payload(Student::find($childId), 'lea@example.com'))
            ->assertStatus(409)
            ->assertJson(['requires_confirmation' => true]);
        $this->putJson("/api/club/students/{$childId}", $this->payload(Student::find($childId), 'lea@example.com', ['email_scope' => 'student']))
            ->assertOk()
            ->assertJson(['user_created' => true]);

        \Laravel\Sanctum\Sanctum::actingAs($account->fresh());
        $ids = collect($this->getJson('/api/student/linked-accounts')->assertOk()->json('data'))->pluck('id')->all();
        $this->assertSame([$parentId], $ids);
    }

    /**
     * Flux complet de fusion : deux élèves ont chacun leur compte ; le club met l'email
     * du parent sur l'ado. Les deux connexions (vrai login, email + mot de passe)
     * restent valides et voient les deux élèves.
     */
    #[Test]
    public function flux_complet_fusion_les_deux_connexions_voient_le_foyer()
    {
        $parentId = $this->postJson('/api/club/students', ['first_name' => 'Paul', 'last_name' => 'Parent', 'email' => 'famille@example.com'])
            ->assertStatus(201)->json('data.id');
        $teenId = $this->postJson('/api/club/students', ['first_name' => 'Tom', 'last_name' => 'Parent', 'email' => 'tom@example.com'])
            ->assertStatus(201)->json('data.id');
        User::whereIn('email', ['famille@example.com', 'tom@example.com'])->update(['password' => \Illuminate\Support\Facades\Hash::make('MotDePasse2026')]);

        $this->putJson("/api/club/students/{$teenId}", $this->payload(Student::find($teenId), 'famille@example.com'))
            ->assertOk()
            ->assertJson(['merged_accounts' => true]);

        // Fiche club : le lien est visible ; la fiche de l'ado garde son propre email.
        $this->getJson("/api/club/students/{$teenId}/linked")->assertOk()->assertJsonFragment(['id' => $parentId]);
        $this->assertSame('tom@example.com', Student::find($teenId)->user->email);

        foreach (['famille@example.com', 'tom@example.com'] as $email) {
            $this->app['auth']->forgetGuards();
            $token = $this->postJson('/api/auth/login', ['email' => $email, 'password' => 'MotDePasse2026'])
                ->assertOk()
                ->json('access_token');

            $this->app['auth']->forgetGuards();
            $ids = collect($this->withToken($token)->getJson('/api/student/linked-accounts')->assertOk()->json('data'))->pluck('id')->all();
            $this->assertEqualsCanonicalizing([$parentId, $teenId], $ids, "Connexion {$email}");
        }
    }

    #[Test]
    public function creer_un_eleve_avec_l_email_d_un_enseignant_est_refuse()
    {
        User::factory()->create(['role' => 'teacher', 'email' => 'coach@example.com']);

        $this->postJson('/api/club/students', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'coach@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertSame(0, User::where('email', 'coach@example.com')->where('role', 'student')->count());
    }
}
