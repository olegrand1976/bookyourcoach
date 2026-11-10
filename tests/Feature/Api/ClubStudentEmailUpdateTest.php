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

    private function merge(Student $student, string $targetEmail): void
    {
        $this->putJson("/api/club/students/{$student->id}", $this->payload($student->fresh(), $targetEmail))
            ->assertOk()
            ->assertJson(['merged_accounts' => true]);
    }

    #[Test]
    public function la_fiche_indique_si_le_compte_est_fusionne()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $student = $this->studentWithAccount('eleve@example.com');

        $this->getJson("/api/club/students/{$student->id}/linked")->assertOk()->assertJson(['account_merged' => false]);
        $this->merge($student, 'parent@example.com');

        $this->getJson("/api/club/students/{$student->id}/linked")->assertOk()->assertJson(['account_merged' => true]);
        $this->getJson("/api/club/students/{$holder->id}/linked")->assertOk()->assertJson(['account_merged' => true]);
    }

    #[Test]
    public function separer_un_compte_fusionne_rend_a_chacun_son_foyer()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $student = $this->studentWithAccount('eleve@example.com');
        $this->merge($student, 'parent@example.com');

        $this->postJson("/api/club/students/{$student->id}/separate-account")
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['account_merged' => false]]);

        $this->assertDatabaseHas('users', ['id' => $student->user_id, 'email' => 'eleve@example.com', 'household_root_id' => null]);
        $this->assertDatabaseHas('users', ['id' => $holder->user_id, 'email' => 'parent@example.com', 'household_root_id' => null]);
        $this->assertFalse($this->linked($holder, $student));
        $this->assertNotContains($holder->id, User::find($student->user_id)->getHouseholdStudentIds());
        $this->assertNotContains($student->id, User::find($holder->user_id)->getHouseholdStudentIds());
        $this->getJson("/api/club/students/{$student->id}/linked")->assertOk()->assertJson(['account_merged' => false]);
    }

    #[Test]
    public function separer_le_compte_de_reference_laisse_les_autres_comptes_fusionnes()
    {
        $a = $this->studentWithAccount('a@example.com');
        $b = $this->studentWithAccount('b@example.com');
        $c = $this->studentWithAccount('c@example.com');
        $this->merge($a, 'b@example.com');
        $this->merge($c, 'b@example.com');

        // B est le compte de référence du groupe : on le sépare depuis la fiche de B.
        $this->postJson("/api/club/students/{$b->id}/separate-account")->assertOk();

        $this->assertSame([$b->id], array_values(array_intersect([$a->id, $b->id, $c->id], User::find($b->user_id)->getHouseholdStudentIds())));
        foreach ([$a, $c] as $s) {
            $household = User::find($s->user_id)->getHouseholdStudentIds();
            $this->assertContains($a->id, $household);
            $this->assertContains($c->id, $household);
            $this->assertNotContains($b->id, $household);
        }
        $newRoot = min($a->user_id, $c->user_id);
        $this->assertDatabaseHas('users', ['id' => $newRoot, 'household_root_id' => null]);
        $this->assertDatabaseHas('users', ['id' => max($a->user_id, $c->user_id), 'household_root_id' => $newRoot]);
        $this->assertDatabaseHas('users', ['id' => $b->user_id, 'household_root_id' => null]);
    }

    #[Test]
    public function un_compte_separe_peut_etre_fusionne_a_nouveau()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $student = $this->studentWithAccount('eleve@example.com');
        $this->merge($student, 'parent@example.com');
        $this->postJson("/api/club/students/{$student->id}/separate-account")->assertOk();

        $this->merge($student, 'parent@example.com');

        $this->assertContains($holder->id, User::find($student->user_id)->getHouseholdStudentIds());
        $this->assertTrue($this->linked($holder, $student));
    }

    #[Test]
    public function separer_un_compte_non_fusionne_ou_sans_compte_est_refuse()
    {
        $student = $this->studentWithAccount('eleve@example.com');
        $this->postJson("/api/club/students/{$student->id}/separate-account")
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $noAccount = $this->studentWithoutAccount();
        $this->postJson("/api/club/students/{$noAccount->id}/separate-account")->assertStatus(422);
    }

    #[Test]
    public function separer_le_compte_d_un_eleve_d_un_autre_club_est_refuse()
    {
        $otherClub = Club::factory()->create();
        $holder = $this->studentWithAccount('parent@example.com');
        $foreignUser = User::factory()->create(['role' => 'student', 'email' => 'ailleurs@example.com', 'household_root_id' => $holder->user_id]);
        $foreign = Student::factory()->create(['club_id' => $otherClub->id, 'user_id' => $foreignUser->id]);
        DB::table('club_students')->insert(['club_id' => $otherClub->id, 'student_id' => $foreign->id, 'is_active' => true, 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        $this->postJson("/api/club/students/{$foreign->id}/separate-account")->assertStatus(404);

        $this->assertDatabaseHas('users', ['id' => $foreignUser->id, 'household_root_id' => $holder->user_id]);
    }

    /** Élève avec compte, inscrit uniquement dans un autre club. */
    private function foreignStudentWithAccount(string $email, array $userAttributes = []): Student
    {
        $otherClub = Club::factory()->create();
        $account = User::factory()->create(array_merge(['role' => 'student', 'email' => $email, 'first_name' => 'Inconnu', 'phone' => '0499999999'], $userAttributes));
        $student = Student::factory()->create(['club_id' => $otherClub->id, 'user_id' => $account->id]);
        DB::table('club_students')->insert(['club_id' => $otherClub->id, 'student_id' => $student->id, 'is_active' => true, 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        return $student;
    }

    #[Test]
    public function fusionner_avec_le_compte_d_un_autre_club_est_refuse()
    {
        $foreign = $this->foreignStudentWithAccount('ailleurs@example.com');
        $student = $this->studentWithAccount('eleve@example.com');

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'ailleurs@example.com'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseHas('users', ['id' => $student->user_id, 'household_root_id' => null]);
        $this->assertNotContains($foreign->id, User::find($student->user_id)->getHouseholdStudentIds());
        $this->assertFalse($this->linked($foreign, $student));
    }

    #[Test]
    public function fusionner_quand_mon_foyer_contient_un_eleve_d_un_autre_club_est_refuse()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $student = $this->studentWithAccount('eleve@example.com');
        // Le compte de l'élève est déjà fusionné avec un compte d'un autre club.
        $foreign = $this->foreignStudentWithAccount('ailleurs@example.com', ['household_root_id' => null]);
        User::where('id', $foreign->user_id)->update(['household_root_id' => $student->user_id]);

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'parent@example.com'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertNotContains($foreign->id, User::find($holder->user_id)->getHouseholdStudentIds());
    }

    #[Test]
    public function rattacher_un_eleve_sans_compte_au_compte_d_un_autre_club_est_refuse()
    {
        $foreign = $this->foreignStudentWithAccount('ailleurs@example.com');
        $student = $this->studentWithoutAccount();

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'ailleurs@example.com'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertNull($student->fresh()->user_id);
        $this->assertNotContains($student->id, User::find($foreign->user_id)->getHouseholdStudentIds());
    }

    #[Test]
    public function creer_un_eleve_avec_l_email_d_un_compte_d_un_autre_club_est_refuse_sans_fuite()
    {
        $this->foreignStudentWithAccount('ailleurs@example.com');

        // Refus avant toute écriture (aucune transaction ouverte). Le catch de store fait
        // un rollBack qui, en test, annule aussi la transaction du test : on ne vérifie
        // donc que la réponse, qui ne doit rien révéler du titulaire.
        $response = $this->postJson('/api/club/students', ['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'ailleurs@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertStringNotContainsString('0499999999', $response->getContent());
        $this->assertStringNotContainsString('Inconnu', $response->getContent());
    }

    #[Test]
    public function un_eleve_d_un_autre_club_lie_au_foyer_vise_bloque_la_fusion()
    {
        // Le foyer visé contient, par un lien famille, un élève d'un autre club.
        $holder = $this->studentWithAccount('parent@example.com');
        $foreign = $this->foreignStudentWithAccount('ailleurs@example.com');
        DB::table('student_family_links')->insert(['primary_student_id' => $holder->id, 'linked_student_id' => $foreign->id, 'relationship_type' => 'sibling', 'created_at' => now(), 'updated_at' => now()]);
        $student = $this->studentWithAccount('eleve@example.com');

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'parent@example.com'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertNotContains($foreign->id, User::find($student->user_id)->getHouseholdStudentIds());
    }

    #[Test]
    public function une_inscription_inactive_ne_compte_pas_comme_suivie_par_le_club()
    {
        // L'élève du compte visé a quitté ce club (inscription inactive) et suit un autre club.
        $foreign = $this->foreignStudentWithAccount('ailleurs@example.com');
        DB::table('club_students')->insert(['club_id' => $this->club->id, 'student_id' => $foreign->id, 'is_active' => false, 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $student = $this->studentWithAccount('eleve@example.com');

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'ailleurs@example.com'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function un_compte_sans_eleve_est_refuse_avec_un_message_explicite()
    {
        User::factory()->create(['role' => 'student', 'email' => 'orphelin@example.com']);
        $student = $this->studentWithoutAccount();

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'orphelin@example.com'))
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', fn ($message) => str_contains($message, 'aucun élève chez vous'));
    }

    #[Test]
    public function un_compte_d_un_autre_club_est_refuse_sans_proposer_de_confirmation()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $child = $this->studentWithoutAccount();
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child, 'parent@example.com'))->assertOk();
        $this->foreignStudentWithAccount('ailleurs@example.com');

        // Compte partagé, mais aucun des deux choix ne pourrait aboutir : refus direct.
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), 'ailleurs@example.com'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function la_fusion_du_compte_entier_n_est_pas_proposee_si_un_eleve_est_d_un_autre_club()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $child = $this->studentWithoutAccount();
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child, 'parent@example.com'))->assertOk();
        // Un troisième élève du même compte suit un autre club.
        $otherClub = Club::factory()->create();
        $foreign = Student::factory()->create(['club_id' => $otherClub->id, 'user_id' => $holder->user_id]);
        DB::table('club_students')->insert(['club_id' => $otherClub->id, 'student_id' => $foreign->id, 'is_active' => true, 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $other = $this->studentWithAccount('autre@example.com');

        $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), 'autre@example.com'))
            ->assertStatus(409)
            ->assertJson(['data' => ['kind' => 'link', 'household_allowed' => false]]);

        $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), 'autre@example.com', ['email_scope' => 'household']))
            ->assertStatus(422);
        $this->assertNotContains($foreign->id, User::find($other->user_id)->getHouseholdStudentIds());

        $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), 'autre@example.com', ['email_scope' => 'student']))
            ->assertOk()
            ->assertJson(['attached_to_account' => true]);
        $this->assertSame($other->user_id, $child->fresh()->user_id);
    }

    #[Test]
    public function rattacher_seulement_cet_eleve_retire_ses_liens_de_fusion_avec_l_ancien_groupe()
    {
        // Compte partagé A (titulaire + enfant), fusionné entier avec B depuis la fiche de l'enfant.
        $holder = $this->studentWithAccount('parent@example.com');
        $child = $this->studentWithoutAccount();
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child, 'parent@example.com'))->assertOk();
        $b = $this->studentWithAccount('b@example.com');
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), 'b@example.com', ['email_scope' => 'household']))
            ->assertOk()
            ->assertJson(['merged_accounts' => true]);
        $this->assertTrue($this->linked($b, $child));

        // L'enfant seul part vers un troisième compte C.
        $c = $this->studentWithAccount('c@example.com');
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), 'c@example.com', ['email_scope' => 'student']))
            ->assertOk()
            ->assertJson(['attached_to_account' => true]);

        $this->assertFalse($this->linked($b, $child));
        $this->assertNotContains($child->id, User::find($b->user_id)->getHouseholdStudentIds());
        $this->assertNotContains($child->id, User::find($holder->user_id)->getHouseholdStudentIds());
        $this->assertContains($child->id, User::find($c->user_id)->getHouseholdStudentIds());
    }

    #[Test]
    public function le_type_de_lien_reserve_a_la_fusion_ne_peut_pas_etre_saisi()
    {
        $a = $this->studentWithAccount('a@example.com');
        $b = $this->studentWithAccount('b@example.com');

        $this->postJson("/api/club/students/{$a->id}/link", [
            'linked_student_id' => $b->id,
            'relationship_type' => \App\Services\FamilyLinkService::MERGE_RELATIONSHIP,
        ])->assertStatus(422);

        $this->assertFalse($this->linked($a, $b));
    }

    #[Test]
    public function la_separation_garde_les_liens_poses_a_la_main()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $student = $this->studentWithAccount('eleve@example.com');
        // Fratrie liée par le club avant la fusion.
        $this->postJson("/api/club/students/{$holder->id}/link", ['linked_student_id' => $student->id])->assertOk();
        $this->merge($student, 'parent@example.com');

        $this->postJson("/api/club/students/{$student->id}/separate-account")->assertOk();

        $this->assertTrue($this->linked($holder, $student));
        $this->assertSame(0, DB::table('student_family_links')->where('relationship_type', \App\Services\FamilyLinkService::MERGE_RELATIONSHIP)->count());
    }

    #[Test]
    public function un_compte_partage_et_l_email_d_un_autre_compte_demande_confirmation()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $child = $this->studentWithoutAccount();
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child, 'parent@example.com'))->assertOk();
        $other = $this->studentWithAccount('autre@example.com');

        $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), 'autre@example.com'))
            ->assertStatus(409)
            ->assertJson(['requires_confirmation' => true, 'data' => ['kind' => 'link', 'target_email' => 'autre@example.com']]);

        $this->assertSame($holder->user_id, $child->fresh()->user_id);
        $this->assertDatabaseHas('users', ['id' => $holder->user_id, 'household_root_id' => null]);
        $this->assertNotContains($other->id, User::find($holder->user_id)->getHouseholdStudentIds());
    }

    #[Test]
    public function rattacher_seulement_cet_eleve_au_compte_existant()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $child = $this->studentWithoutAccount();
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child, 'parent@example.com'))->assertOk();
        $other = $this->studentWithAccount('autre@example.com');

        $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), 'autre@example.com', ['email_scope' => 'student']))
            ->assertOk()
            ->assertJson(['attached_to_account' => true, 'merged_accounts' => false]);

        $this->assertSame($other->user_id, $child->fresh()->user_id);
        $this->assertFalse($this->linked($holder, $child));
        // Le compte du parent n'est pas fusionné : il ne voit plus l'enfant ni l'autre compte.
        $household = User::find($holder->user_id)->getHouseholdStudentIds();
        $this->assertNotContains($child->id, $household);
        $this->assertNotContains($other->id, $household);
    }

    #[Test]
    public function fusionner_tout_le_compte_partage_avec_le_compte_existant()
    {
        $holder = $this->studentWithAccount('parent@example.com');
        $child = $this->studentWithoutAccount();
        $this->putJson("/api/club/students/{$child->id}", $this->payload($child, 'parent@example.com'))->assertOk();
        $other = $this->studentWithAccount('autre@example.com');

        $this->putJson("/api/club/students/{$child->id}", $this->payload($child->fresh(), 'autre@example.com', ['email_scope' => 'household']))
            ->assertOk()
            ->assertJson(['merged_accounts' => true]);

        $this->assertSame($holder->user_id, $child->fresh()->user_id);
        foreach ([$holder->user_id, $other->user_id] as $accountId) {
            $household = User::find($accountId)->getHouseholdStudentIds();
            $this->assertContains($holder->id, $household);
            $this->assertContains($child->id, $household);
            $this->assertContains($other->id, $household);
        }
    }

    #[Test]
    public function l_email_d_un_compte_enseignant_relie_a_une_fiche_n_est_pas_modifiable()
    {
        $teacherAccount = User::factory()->create(['role' => 'teacher', 'email' => 'coach@example.com']);
        $student = $this->enroll(Student::factory()->create(['club_id' => $this->club->id, 'user_id' => $teacherAccount->id]));

        $this->putJson("/api/club/students/{$student->id}", $this->payload($student, 'pirate@example.com'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseHas('users', ['id' => $teacherAccount->id, 'email' => 'coach@example.com']);
    }

    #[Test]
    public function supprimer_le_compte_de_reference_garde_les_autres_comptes_fusionnes()
    {
        $a = $this->studentWithAccount('a@example.com');
        $b = $this->studentWithAccount('b@example.com');
        $c = $this->studentWithAccount('c@example.com');
        $this->merge($a, 'b@example.com');
        $this->merge($c, 'b@example.com');

        User::find($b->user_id)->delete();

        $newRoot = min($a->user_id, $c->user_id);
        $this->assertDatabaseHas('users', ['id' => $newRoot, 'household_root_id' => null]);
        $this->assertDatabaseHas('users', ['id' => max($a->user_id, $c->user_id), 'household_root_id' => $newRoot]);
        $household = User::find($a->user_id)->getHouseholdStudentIds();
        $this->assertContains($c->id, $household);
    }

    #[Test]
    public function le_mot_de_passe_saisi_a_la_creation_n_est_pas_journalise()
    {
        \Illuminate\Support\Facades\Log::spy();

        $this->postJson('/api/club/students', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'MotDePasseSecret2026',
        ])->assertStatus(201);

        \Illuminate\Support\Facades\Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context = []) => $message === 'Données validées pour création élève'
                && ! array_key_exists('password', $context['validated'] ?? []))
            ->once();
        \Illuminate\Support\Facades\Log::shouldNotHaveReceived('info', fn ($message, $context = []) => str_contains(json_encode($context), 'MotDePasseSecret2026'));
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
                'data' => ['kind' => 'change', 'account_email' => 'parent@example.com', 'new_email' => 'nouveau@example.com', 'other_students_count' => 1],
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
