<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthControllerSimple extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'student',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier l'environnement
        $isLocal = app()->environment('local');
        
        // Authentification manuelle pour éviter le problème de guard
        $user = User::where('email', $request->email)->first();
        
        if ($user && Hash::check($request->password, $user->password)) {
            // Créer un token Sanctum
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token
            ], 200);
        }

        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Token manquant'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if ($personalAccessToken) {
            $personalAccessToken->delete();
        }

        // Déconnecter de la session aussi
        Auth::logout();

        return response()->json([
            'message' => 'Logout successful'
        ], 200);
    }

    public function user(Request $request): JsonResponse
    {
        try {
            Log::info('AuthControllerSimple::user - Début de la méthode');
            
            $token = $request->header('Authorization');
            Log::info('AuthControllerSimple::user - Token reçu: ' . ($token ? 'Présent' : 'Absent'));
            
            if (!$token || !str_starts_with($token, 'Bearer ')) {
                Log::warning('AuthControllerSimple::user - Token manquant ou format invalide');
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            $token = substr($token, 7);
            Log::info('AuthControllerSimple::user - Token nettoyé: ' . substr($token, 0, 10) . '...');
            
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {
                Log::warning('AuthControllerSimple::user - Token Sanctum non trouvé');
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            Log::info('AuthControllerSimple::user - Token Sanctum trouvé pour user ID: ' . $personalAccessToken->tokenable_id);
            
            $user = $personalAccessToken->tokenable;
            Log::info('AuthControllerSimple::user - User model récupéré: ' . $user->id);

            // Récupérer les données directement depuis la base de données pour éviter les boucles
            $userData = DB::table('users')->where('id', $user->id)->first();
            
            if (!$userData) {
                Log::error('AuthControllerSimple::user - User non trouvé en DB pour ID: ' . $user->id);
                return response()->json(['error' => 'User not found'], 404);
            }

            Log::info('AuthControllerSimple::user - User data récupéré: ' . $userData->name . ' (' . $userData->email . ')');

            return response()->json([
                'user' => [
                    'id' => $userData->id,
                    'name' => $userData->name ?? null,
                    'first_name' => $userData->first_name ?? null,
                    'last_name' => $userData->last_name ?? null,
                    'email' => $userData->email,
                    'role' => $userData->role,
                    'phone' => $userData->phone ?? null,
                    'street' => $userData->street ?? null,
                    'street_number' => $userData->street_number ?? null,
                    'street_box' => $userData->street_box ?? null,
                    'postal_code' => $userData->postal_code ?? null,
                    'city' => $userData->city ?? null,
                    'country' => $userData->country ?? null,
                    'birth_date' => $userData->birth_date ?? null,
                    'status' => $userData->status ?? 'active',
                    'is_active' => $userData->is_active ?? true,
                    'can_act_as_teacher' => $userData->role === 'admin' || $userData->role === 'teacher',
                    'can_act_as_student' => $userData->role === 'admin' || $userData->role === 'student',
                    'is_admin' => $userData->role === 'admin',
                    'email_verified_at' => $userData->email_verified_at ?? null,
                    'created_at' => $userData->created_at,
                    'updated_at' => $userData->updated_at,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in AuthControllerSimple::user: ' . $e->getMessage());
            Log::error('Error stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
