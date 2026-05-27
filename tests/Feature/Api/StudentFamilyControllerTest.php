<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\Student;
use App\Models\User;
use App\Services\FamilyLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentFamilyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('family-link:user:guest');
    }

    private function makeOrphanChild(): Student
    {
        return Student::factory()->create([
            'user_id' => null,
            'first_name' => 'Léo',
            'last_name' => 'Dupont',
        ]);
    }

    #[Test]
    public function parent_can_link_child_via_invite_code(): void
    {
        $parent = $this->actingAsStudent();
        $child = $this->makeOrphanChild();
        $code = app(FamilyLinkService::class)->generateInviteCode($child);

        $response = $this->postJson('/api/student/family/link-child', [
            'invite_code' => $code,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $child->id);

        $this->assertDatabaseHas('students', [
            'id' => $child->id,
            'user_id' => $parent->id,
            'invite_code' => null,
        ]);
    }

    #[Test]
    public function unknown_code_returns_404(): void
    {
        $this->actingAsStudent();

        $response = $this->postJson('/api/student/family/link-child', [
            'invite_code' => 'NONEXISTENT9',
        ]);

        $response->assertStatus(404)->assertJsonPath('success', false);
    }

    #[Test]
    public function expired_code_returns_422(): void
    {
        $this->actingAsStudent();
        $child = $this->makeOrphanChild();
        $code = app(FamilyLinkService::class)->generateInviteCode($child);
        $child->update(['invite_code_expires_at' => now()->subDay()]);

        $response = $this->postJson('/api/student/family/link-child', [
            'invite_code' => $code,
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    #[Test]
    public function already_linked_child_returns_404_for_other_parent(): void
    {
        $firstParent = User::factory()->create(['role' => 'student']);
        Student::factory()->create(['user_id' => $firstParent->id]);
        $child = $this->makeOrphanChild();
        $code = app(FamilyLinkService::class)->generateInviteCode($child);
        app(FamilyLinkService::class)->linkChildToParent($code, $firstParent);

        $this->actingAsStudent();

        $response = $this->postJson('/api/student/family/link-child', [
            'invite_code' => $code,
        ]);

        $response->assertStatus(404)->assertJsonPath('success', false);
    }

    #[Test]
    public function teacher_cannot_link_child(): void
    {
        $this->actingAsTeacher();
        $child = $this->makeOrphanChild();
        $code = app(FamilyLinkService::class)->generateInviteCode($child);

        $response = $this->postJson('/api/student/family/link-child', [
            'invite_code' => $code,
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function children_endpoint_lists_household(): void
    {
        $parent = $this->actingAsStudent();
        $child = $this->makeOrphanChild();
        $code = app(FamilyLinkService::class)->generateInviteCode($child);
        app(FamilyLinkService::class)->linkChildToParent($code, $parent);

        $response = $this->getJson('/api/student/family/children');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($child->id, $ids);
    }

    #[Test]
    public function parent_can_unlink_child_and_new_code_is_generated(): void
    {
        $parent = $this->actingAsStudent();
        $child = $this->makeOrphanChild();
        $code = app(FamilyLinkService::class)->generateInviteCode($child);
        app(FamilyLinkService::class)->linkChildToParent($code, $parent);

        $response = $this->deleteJson("/api/student/family/children/{$child->id}");

        $response->assertOk()->assertJsonPath('success', true);

        $child->refresh();
        $this->assertNull($child->user_id);
        $this->assertNotNull($child->invite_code);
        $this->assertNotSame($code, $child->invite_code);
    }

    #[Test]
    public function parent_cannot_unlink_child_of_other_account(): void
    {
        $otherParent = User::factory()->create(['role' => 'student']);
        $child = $this->makeOrphanChild();
        $code = app(FamilyLinkService::class)->generateInviteCode($child);
        app(FamilyLinkService::class)->linkChildToParent($code, $otherParent);

        $this->actingAsStudent();

        $response = $this->deleteJson("/api/student/family/children/{$child->id}");

        $response->assertStatus(403);
    }
}
