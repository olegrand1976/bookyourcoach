<?php

namespace Tests\Unit\Services;

use App\Services\QrCodeService;
use App\Models\User;
use App\Models\Club;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class QrCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QrCodeService $qrCodeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qrCodeService = new QrCodeService();
    }

    public function test_generate_for_user_creates_new_qr_code()
    {
        $user = User::factory()->create(['qr_code' => null]);

        $qrCode = $this->qrCodeService->generateForUser($user);

        $this->assertNotNull($qrCode);
        $this->assertStringStartsWith('ACTIVIBE_USER_', $qrCode);
        $this->assertEquals(32, strlen($qrCode)); // 'ACTIVIBE_USER_' + 16 chars
        $this->assertNotNull($user->fresh()->qr_code_generated_at);
    }

    public function test_generate_for_user_returns_existing_qr_code()
    {
        $existingQrCode = 'ACTIVIBE_USER_EXISTING123456';
        $user = User::factory()->create(['qr_code' => $existingQrCode]);

        $qrCode = $this->qrCodeService->generateForUser($user);

        $this->assertEquals($existingQrCode, $qrCode);
    }

    public function test_generate_for_club_creates_new_qr_code()
    {
        $club = Club::factory()->create(['qr_code' => null]);

        $qrCode = $this->qrCodeService->generateForClub($club);

        $this->assertNotNull($qrCode);
        $this->assertStringStartsWith('ACTIVIBE_CLUB_', $qrCode);
        $this->assertEquals(31, strlen($qrCode)); // 'ACTIVIBE_CLUB_' + 16 chars
        $this->assertNotNull($club->fresh()->qr_code_generated_at);
    }

    public function test_generate_for_club_returns_existing_qr_code()
    {
        $existingQrCode = 'ACTIVIBE_CLUB_EXISTING123456';
        $club = Club::factory()->create(['qr_code' => $existingQrCode]);

        $qrCode = $this->qrCodeService->generateForClub($club);

        $this->assertEquals($existingQrCode, $qrCode);
    }

    public function test_find_user_by_qr_code_returns_user()
    {
        $qrCode = 'ACTIVIBE_USER_TEST123456789';
        $user = User::factory()->create(['qr_code' => $qrCode]);

        $foundUser = $this->qrCodeService->findUserByQrCode($qrCode);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($user->id, $foundUser->id);
    }

    public function test_find_user_by_qr_code_returns_null_for_invalid_code()
    {
        $foundUser = $this->qrCodeService->findUserByQrCode('INVALID_CODE');

        $this->assertNull($foundUser);
    }

    public function test_find_club_by_qr_code_returns_club()
    {
        $qrCode = 'ACTIVIBE_CLUB_TEST123456789';
        $club = Club::factory()->create(['qr_code' => $qrCode]);

        $foundClub = $this->qrCodeService->findClubByQrCode($qrCode);

        $this->assertInstanceOf(Club::class, $foundClub);
        $this->assertEquals($club->id, $foundClub->id);
    }

    public function test_find_club_by_qr_code_returns_null_for_invalid_code()
    {
        $foundClub = $this->qrCodeService->findClubByQrCode('INVALID_CODE');

        $this->assertNull($foundClub);
    }

    public function test_create_club_qr_data()
    {
        $club = Club::factory()->create([
            'name' => 'Test Club',
            'email' => 'test@club.com',
            'phone' => '0123456789',
            'address' => '123 Test Street',
            'city' => 'Test City',
            'qr_code' => null
        ]);

        $qrData = $this->qrCodeService->createClubQrData($club);

        $this->assertIsArray($qrData);
        $this->assertEquals($club->id, $qrData['club_id']);
        $this->assertEquals('Test Club', $qrData['name']);
        $this->assertEquals('test@club.com', $qrData['email']);
        $this->assertEquals('0123456789', $qrData['phone']);
        $this->assertEquals('123 Test Street', $qrData['address']);
        $this->assertEquals('Test City', $qrData['city']);
        $this->assertStringStartsWith('ACTIVIBE_CLUB_', $qrData['qr_code']);
        $this->assertIsString($qrData['qr_image']);
        $this->assertIsString($qrData['qr_svg']);
        $this->assertNotNull($qrData['generated_at']);
    }

    public function test_generate_unique_qr_code_creates_unique_codes()
    {
        $user1 = User::factory()->create(['qr_code' => null]);
        $user2 = User::factory()->create(['qr_code' => null]);

        $qrCode1 = $this->qrCodeService->generateForUser($user1);
        $qrCode2 = $this->qrCodeService->generateForUser($user2);

        $this->assertNotEquals($qrCode1, $qrCode2);
    }

    public function test_generate_unique_club_qr_code_creates_unique_codes()
    {
        $club1 = Club::factory()->create(['qr_code' => null]);
        $club2 = Club::factory()->create(['qr_code' => null]);

        $qrCode1 = $this->qrCodeService->generateForClub($club1);
        $qrCode2 = $this->qrCodeService->generateForClub($club2);

        $this->assertNotEquals($qrCode1, $qrCode2);
    }

    public function test_generate_image_returns_base64_string()
    {
        $qrCode = 'TEST_QR_CODE';
        
        // Mock the QrCode facade
        $mockQrCode = Mockery::mock('alias:SimpleSoftwareIO\QrCode\Facades\QrCode');
        $mockQrCode->shouldReceive('format')
            ->with('png')
            ->andReturnSelf();
        $mockQrCode->shouldReceive('size')
            ->with(200)
            ->andReturnSelf();
        $mockQrCode->shouldReceive('margin')
            ->with(1)
            ->andReturnSelf();
        $mockQrCode->shouldReceive('generate')
            ->with($qrCode)
            ->andReturn('mock_png_data');

        $result = $this->qrCodeService->generateImage($qrCode);

        $this->assertEquals('mock_png_data', $result);
    }

    public function test_generate_svg_returns_svg_string()
    {
        $qrCode = 'TEST_QR_CODE';
        
        // Mock the QrCode facade
        $mockQrCode = Mockery::mock('alias:SimpleSoftwareIO\QrCode\Facades\QrCode');
        $mockQrCode->shouldReceive('format')
            ->with('svg')
            ->andReturnSelf();
        $mockQrCode->shouldReceive('size')
            ->with(200)
            ->andReturnSelf();
        $mockQrCode->shouldReceive('margin')
            ->with(1)
            ->andReturnSelf();
        $mockQrCode->shouldReceive('generate')
            ->with($qrCode)
            ->andReturn('<svg>mock_svg_data</svg>');

        $result = $this->qrCodeService->generateSvg($qrCode);

        $this->assertEquals('<svg>mock_svg_data</svg>', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
