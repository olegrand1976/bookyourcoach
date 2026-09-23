<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorCodeRequest;
use App\Http\Requests\Auth\TwoFactorSensitiveActionRequest;
use App\Http\Resources\TrustedDeviceResource;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Réglages 2FA de son propre compte, et réinitialisation par un administrateur.
 *
 * Pas de « désactiver » : la 2FA est obligatoire pour les comptes concernés. On
 * peut en revanche changer de téléphone, renouveler ses codes de récupération et
 * révoquer ses appareils de confiance.
 */
class TwoFactorAccountController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'required' => $user->requiresTwoFactor(),
                'enabled' => $user->hasTwoFactorEnabled(),
                'confirmed_at' => $user->two_factor_confirmed_at?->toIso8601String(),
                'recovery_codes_remaining' => $this->twoFactor->remainingRecoveryCodes($user),
                'trusted_devices' => TrustedDeviceResource::collection(
                    $user->trustedDevices()->active()->orderByDesc('last_used_at')->get()
                ),
            ],
        ]);
    }

    public function regenerateRecoveryCodes(TwoFactorSensitiveActionRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($denied = $this->ensureEnabled($user)) {
            return $denied;
        }
        if (! $this->twoFactor->verifyCode($user, (string) $request->input('code'))) {
            return $this->invalidCode();
        }

        $codes = $this->twoFactor->regenerateRecoveryCodes($user);
        Log::info('Codes de récupération 2FA régénérés', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'Nouveaux codes de récupération générés : les anciens ne fonctionnent plus.',
            'data' => ['recovery_codes' => $codes],
        ]);
    }

    /**
     * Changement de téléphone, étape 1 : prouve la possession de l'ancien, reçoit le
     * QR code du nouveau. L'ancien secret reste actif jusqu'à la confirmation.
     */
    public function reconfigure(TwoFactorSensitiveActionRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($denied = $this->ensureEnabled($user)) {
            return $denied;
        }
        if (! $this->twoFactor->verifyCode($user, (string) $request->input('code'))) {
            return $this->invalidCode();
        }

        $enrolment = $this->twoFactor->startEnrolment($user);

        return response()->json([
            'success' => true,
            'message' => 'Scannez ce QR code avec votre nouvelle application, puis saisissez le code affiché.',
            'data' => [
                'qr_svg' => $enrolment['qr_svg'],
                'secret' => $enrolment['secret'],
                'otpauth_url' => $enrolment['otpauth_url'],
            ],
        ]);
    }

    /**
     * Changement de téléphone, étape 2 : le nouveau secret remplace l'ancien. Les
     * appareils de confiance tombent, l'ancien téléphone pouvant être perdu.
     */
    public function confirmReconfigure(TwoFactorCodeRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($denied = $this->ensureEnabled($user)) {
            return $denied;
        }
        if (! $this->twoFactor->hasPendingEnrolment($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun changement de téléphone en cours, ou il a expiré : recommencez.',
            ], 409);
        }

        $codes = $this->twoFactor->confirmEnrolment($user, (string) $request->input('code'));
        if ($codes === null) {
            return $this->invalidCode();
        }

        $user->trustedDevices()->delete();
        Log::info('Double authentification reconfigurée', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'Nouvelle application enregistrée.',
            'data' => ['recovery_codes' => $codes],
        ]);
    }

    public function revokeTrustedDevice(Request $request, int $id): JsonResponse
    {
        // Filtré sur le propriétaire : l'id d'un appareil d'autrui répond 404.
        $device = $request->user()->trustedDevices()->whereKey($id)->first();
        if (! $device) {
            return response()->json(['success' => false, 'message' => 'Appareil introuvable.'], 404);
        }

        $device->delete();

        return response()->json(['success' => true, 'message' => 'Appareil révoqué.']);
    }

    public function revokeAllTrustedDevices(Request $request): JsonResponse
    {
        $count = $request->user()->trustedDevices()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tous les appareils de confiance ont été révoqués.',
            'data' => ['revoked' => $count],
        ]);
    }

    /**
     * Admin : remet à zéro la 2FA d'un compte (téléphone perdu, codes égarés). La
     * personne refera l'enrôlement à sa prochaine connexion ; ses sessions tombent.
     */
    public function resetForUser(Request $request, int $id): JsonResponse
    {
        $admin = $request->user();
        $target = User::find($id);

        if (! $target) {
            return response()->json(['success' => false, 'message' => 'Utilisateur introuvable.'], 404);
        }

        // Se réinitialiser soi-même couperait sa propre session sans filet : cela
        // passe par un autre administrateur.
        if ($target->is($admin)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas réinitialiser votre propre double authentification.',
            ], 422);
        }

        $this->twoFactor->reset($target);

        Log::warning('Double authentification réinitialisée par un administrateur', [
            'admin_id' => $admin->id,
            'user_id' => $target->id,
            'role' => $target->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Double authentification réinitialisée : l\'utilisateur la reconfigurera à sa prochaine connexion.',
            'data' => ['user_id' => $target->id, 'two_factor_enabled' => false],
        ]);
    }

    private function ensureEnabled(User $user): ?JsonResponse
    {
        if ($user->hasTwoFactorEnabled()) {
            return null;
        }

        return response()->json([
            'success' => false,
            'code' => 'two_factor_setup_required',
            'message' => 'La double authentification n\'est pas configurée sur ce compte.',
        ], 403);
    }

    private function invalidCode(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Code invalide.',
            'errors' => ['code' => ['Code invalide.']],
        ], 422);
    }
}
