<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function it_can_upload_avatar_when_authenticated()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user)
            ->postJson('/api/upload/avatar', [
                'avatar' => $file
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'path',
                'url'
            ]);

        $this->assertTrue(Storage::disk('public')->exists($response->json('path')));
    }

    /** @test */
    public function it_validates_avatar_file_type()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($user)
            ->postJson('/api/upload/avatar', [
                'avatar' => $file
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    /** @test */
    public function it_can_upload_certificate_when_authenticated()
    {
        $user = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $file = UploadedFile::fake()->create('certificate.pdf', 100);

        $response = $this->actingAs($user)
            ->postJson('/api/upload/certificate', [
                'certificate' => $file,
                'name' => 'Certificat de coaching'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'path',
                'url',
                'name'
            ]);

        $this->assertTrue(Storage::disk('public')->exists($response->json('path')));
    }

    /** @test */
    public function it_can_upload_logo_as_admin()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $file = UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($admin)
            ->postJson('/api/upload/logo', [
                'logo' => $file
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'path',
                'url'
            ]);

        $this->assertTrue(Storage::disk('public')->exists($response->json('path')));
    }

    /** @test */
    public function it_cannot_upload_logo_as_non_admin()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $file = UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($user)
            ->postJson('/api/upload/logo', [
                'logo' => $file
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_requires_authentication_for_file_upload()
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson('/api/upload/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_delete_file_when_authenticated()
    {
        $user = User::factory()->create();
        $filename = 'user-' . $user->id . '-test.jpg';
        Storage::disk('public')->put('avatars/' . $filename, 'fake content');

        $response = $this->actingAs($user)
            ->deleteJson('/api/upload/avatars/' . $filename);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Fichier supprimé avec succès'
            ]);

        $this->assertFalse(Storage::disk('public')->exists('avatars/' . $filename));
    }
}
