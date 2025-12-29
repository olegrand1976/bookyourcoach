<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Club;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                // Vérifier l'unicité globale de l'email dans toute la table users
                Rule::unique('users', 'email'),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:admin,teacher,student,club'],
            'phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'street' => ['nullable', 'string', 'max:255'],
            'street_number' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            // Champs spécifiques pour les clubs
            'club_name' => ['required_if:role,club', 'string', 'max:255'],
            'club_description' => ['nullable', 'string'],
            // Champs spécifiques pour les enseignants
            'specialties' => ['nullable', 'array'],
            'experience_years' => ['nullable', 'integer', 'min:0'],
        ]);

        // Construire le nom complet
        $fullName = trim($request->first_name . ' ' . $request->last_name);

        // Utiliser une transaction pour garantir la cohérence
        DB::beginTransaction();
        
        try {
            $user = User::create([
                'name' => $fullName,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'phone' => $request->phone,
                'birth_date' => $request->birth_date,
                'street' => $request->street,
                'street_number' => $request->street_number,
                'postal_code' => $request->postal_code,
                'city' => $request->city,
                'country' => $request->country ?? 'Belgium',
                'is_active' => true,
                'status' => 'active',
            ]);

            // Si c'est un club, créer automatiquement le club et lier l'utilisateur
            if ($request->role === 'club') {
                $club = Club::create([
                    'name' => $request->club_name,
                    'description' => $request->club_description,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'street' => $request->street,
                    'street_number' => $request->street_number,
                    'postal_code' => $request->postal_code,
                    'city' => $request->city,
                    'country' => $request->country ?? 'Belgium',
                    'is_active' => true,
                ]);

                // Lier l'utilisateur au club en tant que propriétaire et admin
                $user->clubs()->attach($club->id, [
                    'role' => 'owner',
                    'is_admin' => true,
                    'joined_at' => now(),
                ]);
            }

            DB::commit();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
                'club' => $request->role === 'club' ? $club : null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'inscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::guard('web')->attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        // Utiliser Auth::user() au lieu de User::where() pour éviter l'ambiguïté
        // si plusieurs utilisateurs ont le même email avec des rôles différents
        // Auth::attempt() a déjà trouvé le bon utilisateur grâce au mot de passe
        $user = Auth::user();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $currentToken = $request->user()->currentAccessToken();
            if ($currentToken) {
                $currentToken->delete();
            } else {
                $authHeader = $request->header('Authorization');
                if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                    $plainText = substr($authHeader, 7);
                    $pat = PersonalAccessToken::findToken($plainText);
                    if ($pat) {
                        $pat->delete();
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Logout non-critique: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Logged out']);
    }

    /**
     * Envoyer un lien de réinitialisation de mot de passe
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status),
                'success' => true
            ]);
        }

        return response()->json([
            'message' => __($status),
            'success' => false
        ], 400);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status),
                'success' => true
            ]);
        }

        return response()->json([
            'message' => __($status),
            'success' => false
        ], 400);
    }
}
