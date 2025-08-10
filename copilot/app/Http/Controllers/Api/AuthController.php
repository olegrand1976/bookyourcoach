<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
        ]);

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
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
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
        return response()->json([
            'user' => $request->user()
        ]);
    }
}
