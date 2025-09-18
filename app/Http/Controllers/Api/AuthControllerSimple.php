<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
        
        // Authentification simple sans guard spécifique
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            
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
        $token = $request->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $user = $personalAccessToken->tokenable;

        // Récupérer les données directement depuis la base de données pour éviter les boucles
        $userData = \DB::table('users')->where('id', $user->id)->first();

        return response()->json([
            'user' => [
                'id' => $userData->id,
                'name' => $userData->name,
                'first_name' => $userData->first_name,
                'last_name' => $userData->last_name,
                'email' => $userData->email,
                'role' => $userData->role,
                'phone' => $userData->phone,
                'street' => $userData->street,
                'street_number' => $userData->street_number,
                'street_box' => $userData->street_box,
                'postal_code' => $userData->postal_code,
                'city' => $userData->city,
                'country' => $userData->country,
                'birth_date' => $userData->birth_date,
                'status' => $userData->status,
                'is_active' => $userData->is_active,
                'can_act_as_teacher' => $userData->role === 'admin' || $userData->role === 'teacher',
                'can_act_as_student' => $userData->role === 'admin' || $userData->role === 'student',
                'is_admin' => $userData->role === 'admin',
                'email_verified_at' => $userData->email_verified_at,
                'created_at' => $userData->created_at,
                'updated_at' => $userData->updated_at,
            ]
        ], 200);
    }
}
