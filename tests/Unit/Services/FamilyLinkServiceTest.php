<?php

namespace Tests\Unit\Services;

use App\Models\Student;
use App\Models\User;
use App\Services\FamilyLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class FamilyLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    private FamilyLinkService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FamilyLinkService();
    }

    #[Test]
    public function generates_unique_invite_code_for_student_without_account(): void
    {
        $student = Student::factory()->create(['user_id' => null]);

        $code = $this->service->generateInviteCode($student);

        $this->assertIsString($code);
        $this->assertGreaterThanOrEqual(FamilyLinkService::CODE_LENGTH, strlen($code));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $code);

        $student->refresh();
        $this->assertSame($code, $student->invite_code);
        $this->assertNotNull($student->invite_code_expires_at);
        $this->assertTrue($student->invite_code_expires_at->isFuture());
    }

    #[Test]
    public function refuses_to_generate_code_for_student_already_linked(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $student = Student::factory()->create(['user_id' => $user->id]);

        $this->expectException(RuntimeException::class);

        $this->service->generateInviteCode($student);
    }

    #[Test]
    public function returns_same_code_when_still_valid(): void
    {
        $student = Student::factory()->create(['user_id' => null]);

        $first = $this->service->generateInviteCode($student);
        $second = $this->service->generateInviteCode($student->fresh());

        $this->assertSame($first, $second);
    }

    #[Test]
    public function regenerate_invalidates_previous_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create(['user_id' => null]);

        $first = $this->service->generateInviteCode($student);
        $second = $this->service->regenerateInviteCode($student->fresh(), $admin);

        $this->assertNotSame($first, $second);

        $this->assertDatabaseMissing('students', [
            'id' => $student->id,
            'invite_code' => $first,
        ]);
    }

    #[Test]
    public function link_child_to_parent_atomically_assigns_user_id(): void
    {
        $parent = User::factory()->create(['role' => 'student']);
        $child = Student::factory()->create(['user_id' => null]);
        $code = $this->service->generateInviteCode($child);

        $linked = $this->service->linkChildToParent($code, $parent);

        $this->assertSame($child->id, $linked->id);
        $this->assertSame($parent->id, $linked->user_id);
        $this->assertNull($linked->invite_code);
        $this->assertNotNull($linked->linked_at);
        $this->assertSame($parent->id, $linked->linked_by_user_id);
    }

    #[Test]
    public function link_child_refuses_invalid_role(): void
    {
        $parent = User::factory()->create(['role' => 'teacher']);
        $child = Student::factory()->create(['user_id' => null]);
        $code = $this->service->generateInviteCode($child);

        $this->expectExceptionMessage('parent_role_invalid');

        $this->service->linkChildToParent($code, $parent);
    }

    #[Test]
    public function link_child_refuses_unknown_code(): void
    {
        $parent = User::factory()->create(['role' => 'student']);

        $this->expectExceptionMessage('code_not_found');

        $this->service->linkChildToParent('AAAAAAAAAA', $parent);
    }

    #[Test]
    public function link_child_refuses_already_linked_student(): void
    {
        $parent = User::factory()->create(['role' => 'student']);
        $otherParent = User::factory()->create(['role' => 'student']);
        $child = Student::factory()->create(['user_id' => null]);
        $code = $this->service->generateInviteCode($child);

        $this->service->linkChildToParent($code, $parent);

        $this->expectExceptionMessage('code_not_found');

        $this->service->linkChildToParent($code, $otherParent);
    }

    #[Test]
    public function link_child_refuses_expired_code(): void
    {
        $parent = User::factory()->create(['role' => 'student']);
        $child = Student::factory()->create(['user_id' => null]);
        $code = $this->service->generateInviteCode($child);

        $child->update(['invite_code_expires_at' => now()->subDay()]);

        $this->expectExceptionMessage('code_expired');

        $this->service->linkChildToParent($code, $parent);
    }

    #[Test]
    public function unlink_child_resets_user_id_and_generates_new_code(): void
    {
        $parent = User::factory()->create(['role' => 'student']);
        $child = Student::factory()->create(['user_id' => null]);
        $code = $this->service->generateInviteCode($child);
        $this->service->linkChildToParent($code, $parent);

        $unlinked = $this->service->unlinkChildFromParent($child->fresh(), $parent);

        $this->assertNull($unlinked->user_id);
        $this->assertNotNull($unlinked->invite_code);
        $this->assertNotSame($code, $unlinked->invite_code);
    }

    #[Test]
    public function link_child_inserts_family_link_when_parent_has_own_student_profile(): void
    {
        $parent = User::factory()->create(['role' => 'student']);
        $parentStudent = Student::factory()->create(['user_id' => $parent->id]);
        $child = Student::factory()->create(['user_id' => null]);
        $code = $this->service->generateInviteCode($child);

        $this->service->linkChildToParent($code, $parent);

        $this->assertTrue(
            DB::table('student_family_links')
                ->where('primary_student_id', $parentStudent->id)
                ->where('linked_student_id', $child->id)
                ->where('relationship_type', 'parent')
                ->exists()
        );
    }

    #[Test]
    public function normalize_code_strips_whitespace_and_uppercases(): void
    {
        $this->assertSame('ABCD1234', $this->service->normalizeCode(' ab-cd 12 34 '));
    }
}
