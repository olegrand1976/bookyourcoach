<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Teacher;
use App\Models\Student;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/auth/register",
     *      operationId="register",
     *      tags={"Authentication"},
     *      summary="Inscription d'un nouvel utilisateur",
     *      description="Crée un nouveau compte utilisateur et retourne un token d'authentification",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name","email","password","password_confirmation"},
     *              @OA\Property(property="name", type="string", example="John Doe"),
     *              @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="password123"),
     *              @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Utilisateur créé avec succès",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="User registered successfully"),
     *              @OA\Property(property="user", ref="#/components/schemas/User"),
     *              @OA\Property(property="token", type="string", example="1|abcdef...")
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Erreur de validation",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Validation failed"),
     *              @OA\Property(property="errors", type="object")
     *          )
     *      )
     * )
     */
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
            'role' => $request->role ?? 'student', // Valeur par défaut
        ]);

        // Créer le profil approprié selon le rôle
        if ($request->role === 'teacher') {
            Teacher::create([
                'user_id' => $user->id,
                'specialties' => [],
                'experience_years' => 0,
                'certifications' => [],
                'hourly_rate' => 0.00,
                'bio' => '',
                'is_available' => true,
                'max_travel_distance' => 10,
                'preferred_locations' => [],
                'rating' => 0.00,
                'total_lessons' => 0,
            ]);
        } elseif ($request->role === 'student') {
            Student::create([
                'user_id' => $user->id,
                'level' => 'débutant',
                'goals' => 'Apprendre les bases',
                'medical_info' => '',
                'emergency_contact' => '',
                'preferred_disciplines' => [],
                'preferred_levels' => [],
                'preferred_formats' => [],
                'location' => '',
                'max_price' => null,
                'max_distance' => 10,
                'notifications_enabled' => true,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    /**
     * @OA\Post(
     *      path="/api/auth/login",
     *      operationId="login",
     *      tags={"Authentication"},
     *      summary="Connexion utilisateur",
     *      description="Authentifie un utilisateur et retourne un token",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="password123")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Connexion réussie",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Login successful"),
     *              @OA\Property(property="user", ref="#/components/schemas/User"),
     *              @OA\Property(property="token", type="string", example="2|abcdef...")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Identifiants invalides",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Invalid credentials")
     *          )
     *      )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Log des tentatives échouées (si la table existe)
            try {
                AuditLog::create([
                    'action' => 'login_failed',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'data' => json_encode([
                        'email' => $request->email,
                        'timestamp' => now()->toISOString()
                    ])
                ]);
            } catch (\Exception $e) {
                // Table n'existe pas ou autre erreur, continuer silencieusement
                logger('Failed to log audit: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Vérifier si l'utilisateur est actif
        if (!$user->is_active) {
            // Log des tentatives sur compte inactif
            try {
                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'login_blocked_inactive',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'data' => json_encode([
                        'email' => $request->email,
                        'timestamp' => now()->toISOString()
                    ])
                ]);
            } catch (\Exception $e) {
                logger('Failed to log audit: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Account is not active'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Log des connexions réussies
        try {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'login_success',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data' => json_encode([
                    'email' => $request->email,
                    'timestamp' => now()->toISOString()
                ])
            ]);
        } catch (\Exception $e) {
            logger('Failed to log audit: ' . $e->getMessage());
        }

        // Ajouter les capacités utilisateur
        $userData = $user->toArray();
        $userData['can_act_as_teacher'] = $user->canActAsTeacher();
        $userData['can_act_as_student'] = $user->canActAsStudent();
        $userData['is_admin'] = $user->isAdmin();

        return response()->json([
            'message' => 'Login successful',
            'user' => $userData,
            'token' => $token
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/auth/logout",
     *      operationId="logout",
     *      tags={"Authentication"},
     *      summary="Déconnexion utilisateur",
     *      description="Révoque le token d'authentification actuel",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Déconnexion réussie",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Logout successful")
     *          )
     *      )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Log de déconnexion
        try {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'logout',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data' => json_encode([
                    'email' => $user->email,
                    'timestamp' => now()->toISOString()
                ])
            ]);
        } catch (\Exception $e) {
            logger('Failed to log audit: ' . $e->getMessage());
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/auth/user",
     *      operationId="getAuthUser",
     *      tags={"Authentication"},
     *      summary="Récupérer l'utilisateur authentifié",
     *      description="Retourne les informations de l'utilisateur actuellement connecté",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Informations utilisateur",
     *          @OA\JsonContent(
     *              @OA\Property(property="user", ref="#/components/schemas/User")
     *          )
     *      )
     * )
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        // Ajouter les capacités utilisateur
        $userData = $user->toArray();
        $userData['can_act_as_teacher'] = $user->canActAsTeacher();
        $userData['can_act_as_student'] = $user->canActAsStudent();
        $userData['is_admin'] = $user->isAdmin();

        return response()->json([
            'user' => $userData
        ]);
    }
}
