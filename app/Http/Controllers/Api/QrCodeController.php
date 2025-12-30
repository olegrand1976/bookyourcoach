<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use App\Models\Club;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QrCodeController extends Controller
{
    protected $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Obtenir le QR code d'un utilisateur
     */
    public function getUserQrCode($userId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Vérifier que l'utilisateur demande son propre QR code ou est admin
            if ($user->id != $userId && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $targetUser = User::findOrFail($userId);
            $qrCode = $this->qrCodeService->generateForUser($targetUser);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => $qrCode,
                    'qr_image' => base64_encode($this->qrCodeService->generateImage($qrCode)),
                    'qr_svg' => $this->qrCodeService->generateSvg($qrCode),
                    'generated_at' => $targetUser->qr_code_generated_at
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération du QR code utilisateur: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du QR code'
            ], 500);
        }
    }

    /**
     * Obtenir le QR code d'un club
     */
    public function getClubQrCode($clubId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Vérifier que l'utilisateur est un club et que c'est son club
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux clubs'
                ], 403);
            }

            // Vérifier que le club appartient à l'utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('club_id', $clubId)
                ->where('is_admin', true)
                ->first();

            if (!$clubUser && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce club ne vous appartient pas'
                ], 403);
            }

            $club = Club::findOrFail($clubId);
            $qrData = $this->qrCodeService->createClubQrData($club);
            
            return response()->json([
                'success' => true,
                'data' => $qrData
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération du QR code club: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du QR code'
            ], 500);
        }
    }

    /**
     * Régénérer le QR code d'un club
     */
    public function regenerateClubQrCode($clubId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Vérifier que l'utilisateur est un club et que c'est son club
            if ($user->role !== 'club') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux clubs'
                ], 403);
            }

            // Vérifier que le club appartient à l'utilisateur
            $clubUser = DB::table('club_user')
                ->where('user_id', $user->id)
                ->where('club_id', $clubId)
                ->where('is_admin', true)
                ->first();

            if (!$clubUser && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce club ne vous appartient pas'
                ], 403);
            }

            $club = Club::findOrFail($clubId);
            $qrCode = $this->qrCodeService->regenerateForClub($club);
            $qrData = $this->qrCodeService->createClubQrData($club);
            
            return response()->json([
                'success' => true,
                'data' => $qrData,
                'message' => 'QR code régénéré avec succès'
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la régénération du QR code club: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la régénération du QR code'
            ], 500);
        }
    }

    /**
     * Scanner un QR code (route publique ou authentifiée selon le cas)
     */
    public function scanQrCode(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'qr_code' => 'required|string'
            ]);

            $qrCode = $request->input('qr_code');
            
            // Chercher d'abord un utilisateur
            $user = $this->qrCodeService->findUserByQrCode($qrCode);
            if ($user) {
                return response()->json([
                    'success' => true,
                    'type' => 'user',
                    'data' => [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role
                    ]
                ]);
            }

            // Chercher un club
            $club = $this->qrCodeService->findClubByQrCode($qrCode);
            if ($club) {
                return response()->json([
                    'success' => true,
                    'type' => 'club',
                    'data' => [
                        'club_id' => $club->id,
                        'name' => $club->name,
                        'email' => $club->email
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'QR code non trouvé'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du scan du QR code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du scan du QR code'
            ], 500);
        }
    }
}
