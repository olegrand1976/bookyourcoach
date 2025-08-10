<?php

namespace Tests\Unit\Models;

use App\Models\Profile;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_required_fields()
    {
        $user = User::factory()->create();
        
        $profileData = [
            'user_id' => $user->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ];

        $profile = Profile::create($profileData);

        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertEquals('Jean', $profile->first_name);
        $this->assertEquals('Dupont', $profile->last_name);
        $this->assertEquals($user->id, $profile->user_id);
    }

    /** @test */
    public function it_has_user_relationship()
    {
        $profile = Profile::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $profile->user());
        $this->assertInstanceOf(User::class, $profile->user);
    }

    /** @test */
    public function it_casts_date_of_birth_as_date()
    {
        $profile = Profile::factory()->create([
            'date_of_birth' => '1990-05-15'
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $profile->date_of_birth);
        $this->assertEquals('1990-05-15', $profile->date_of_birth->format('Y-m-d'));
    }

    /** @test */
    public function it_casts_preferences_as_array()
    {
        $preferences = [
            'language' => 'fr',
            'notifications' => true,
            'newsletter' => false,
        ];

        $profile = Profile::factory()->create([
            'preferences' => $preferences
        ]);

        $this->assertIsArray($profile->preferences);
        $this->assertEquals($preferences, $profile->preferences);
    }

    /** @test */
    public function it_has_full_name_accessor()
    {
        $profile = Profile::factory()->create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);

        $this->assertEquals('Jean Dupont', $profile->getFullNameAttribute());
        $this->assertEquals('Jean Dupont', $profile->full_name);
    }

    /** @test */
    public function it_trims_whitespace_in_full_name()
    {
        $profile = Profile::factory()->create([
            'first_name' => '  Jean  ',
            'last_name' => '  Dupont  ',
        ]);

        $this->assertEquals('Jean Dupont', $profile->full_name);
    }

    /** @test */
    public function it_handles_empty_names_in_full_name()
    {
        $profile = Profile::factory()->create([
            'first_name' => '',
            'last_name' => 'Dupont',
        ]);

        $this->assertEquals('Dupont', $profile->full_name);

        $profile = Profile::factory()->create([
            'first_name' => 'Jean',
            'last_name' => '',
        ]);

        $this->assertEquals('Jean', $profile->full_name);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'user_id',
            'first_name',
            'last_name',
            'phone',
            'date_of_birth',
            'gender',
            'address',
            'city',
            'postal_code',
            'country',
            'emergency_contact_name',
            'emergency_contact_phone',
            'medical_notes',
            'avatar',
            'bio',
            'preferences'
        ];

        $profile = new Profile();
        $this->assertEquals($fillable, $profile->getFillable());
    }

    /** @test */
    public function it_can_store_optional_fields()
    {
        $user = User::factory()->create();
        
        $profileData = [
            'user_id' => $user->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'phone' => '+33123456789',
            'address' => '123 Rue de la Paix',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'France',
            'emergency_contact_name' => 'Marie Dupont',
            'emergency_contact_phone' => '+33987654321',
            'medical_notes' => 'Allergie aux abeilles',
            'bio' => 'Passionné d\'équitation depuis 10 ans',
        ];

        $profile = Profile::create($profileData);

        $this->assertEquals('+33123456789', $profile->phone);
        $this->assertEquals('123 Rue de la Paix', $profile->address);
        $this->assertEquals('Paris', $profile->city);
        $this->assertEquals('75001', $profile->postal_code);
        $this->assertEquals('France', $profile->country);
        $this->assertEquals('Marie Dupont', $profile->emergency_contact_name);
        $this->assertEquals('+33987654321', $profile->emergency_contact_phone);
        $this->assertEquals('Allergie aux abeilles', $profile->medical_notes);
        $this->assertEquals('Passionné d\'équitation depuis 10 ans', $profile->bio);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($profile->user->is($user));
        $this->assertEquals($user->id, $profile->user_id);
    }
}
