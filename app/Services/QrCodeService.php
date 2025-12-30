<?php

namespace App\Services;

use App\Models\User;
use App\Models\Club;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class QrCodeService
{
    /**
     * Generate a QR code for a user
     */
    public function generateForUser(User $user): string
    {
        // Generate a unique QR code if not exists
        if (!$user->qr_code) {
            $user->qr_code = $this->generateUniqueQrCode();
            $user->qr_code_generated_at = now();
            $user->save();
        }

        return $user->qr_code;
    }

    /**
     * Generate QR code image as base64
     */
    public function generateImage(string $qrCode): string
    {
        return QrCode::format('png')
            ->size(200)
            ->margin(1)
            ->generate($qrCode);
    }

    /**
     * Generate QR code image as SVG
     */
    public function generateSvg(string $qrCode): string
    {
        return QrCode::format('svg')
            ->size(200)
            ->margin(1)
            ->generate($qrCode);
    }

    /**
     * Find user by QR code
     */
    public function findUserByQrCode(string $qrCode): ?User
    {
        return User::where('qr_code', $qrCode)->first();
    }

    /**
     * Generate a QR code for a club
     */
    public function generateForClub(Club $club): string
    {
        // Generate a unique QR code if not exists
        if (!$club->qr_code) {
            $club->qr_code = $this->generateUniqueClubQrCode();
            $club->qr_code_generated_at = now();
            $club->save();
        }

        return $club->qr_code;
    }

    /**
     * Find club by QR code
     */
    public function findClubByQrCode(string $qrCode): ?Club
    {
        return Club::where('qr_code', $qrCode)->first();
    }

    /**
     * Generate a unique QR code string for users
     */
    private function generateUniqueQrCode(): string
    {
        do {
            $qrCode = 'ACTIVIBE_USER_' . Str::random(16);
        } while (User::where('qr_code', $qrCode)->exists());

        return $qrCode;
    }

    /**
     * Generate a unique QR code string for clubs
     */
    private function generateUniqueClubQrCode(): string
    {
        do {
            $qrCode = 'ACTIVIBE_CLUB_' . Str::random(16);
        } while (Club::where('qr_code', $qrCode)->exists());

        return $qrCode;
    }

    /**
     * Regenerate QR code for a club (force new generation)
     */
    public function regenerateForClub(Club $club): string
    {
        // Force regeneration by clearing existing QR code
        $club->qr_code = null;
        $club->qr_code_generated_at = null;
        $club->save();
        
        return $this->generateForClub($club);
    }

    /**
     * Create QR code data for club
     */
    public function createClubQrData(Club $club): array
    {
        $qrCode = $this->generateForClub($club);
        
        return [
            'club_id' => $club->id,
            'name' => $club->name,
            'email' => $club->email,
            'phone' => $club->phone,
            'address' => $club->address,
            'city' => $club->city,
            'qr_code' => $qrCode,
            'qr_image' => base64_encode($this->generateImage($qrCode)),
            'qr_svg' => $this->generateSvg($qrCode),
            'generated_at' => $club->qr_code_generated_at
        ];
    }
}
