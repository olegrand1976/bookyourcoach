<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/profiles",
     *      operationId="getProfilesList",
     *      tags={"Profiles"},
     *      summary="Liste des profils",
     *      description="Retourne la liste de tous les profils utilisateurs",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Liste des profils récupérée avec succès",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="profiles",
     *                  type="array",
     *                  @OA\Items(ref="#/components/schemas/Profile")
     *              )
     *          )
     *      )
     * )
     */
    public function index(): JsonResponse
    {
        $profiles = Profile::with('user')->get();
        return response()->json([
            'profiles' => $profiles
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/profiles",
     *      operationId="createProfile",
     *      tags={"Profiles"},
     *      summary="Créer un profil utilisateur",
     *      description="Crée un nouveau profil pour l'utilisateur authentifié",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"first_name","last_name"},
     *              @OA\Property(property="first_name", type="string", example="Jean"),
     *              @OA\Property(property="last_name", type="string", example="Dupont"),
     *              @OA\Property(property="phone", type="string", example="+33123456789"),
     *              @OA\Property(property="address", type="string", example="123 Rue de la Paix"),
     *              @OA\Property(property="city", type="string", example="Paris"),
     *              @OA\Property(property="postal_code", type="string", example="75001"),
     *              @OA\Property(property="country", type="string", example="France"),
     *              @OA\Property(property="date_of_birth", type="string", format="date", example="1990-05-15"),
     *              @OA\Property(property="emergency_contact_name", type="string", example="Marie Dupont"),
     *              @OA\Property(property="emergency_contact_phone", type="string", example="+33987654321")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Profil créé avec succès",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Profile created successfully"),
     *              @OA\Property(property="profile", ref="#/components/schemas/Profile")
     *          )
     *      )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = Profile::create([
            'user_id' => $request->user()->id,
            ...$request->only([
                'first_name',
                'last_name',
                'phone',
                'address',
                'city',
                'postal_code',
                'country',
                'date_of_birth',
                'emergency_contact_name',
                'emergency_contact_phone'
            ])
        ]);

        return response()->json([
            'message' => 'Profile created successfully',
            'profile' => $profile
        ], 201);
    }

    /**
     * @OA\Get(
     *      path="/api/profiles/{id}",
     *      operationId="getProfile",
     *      tags={"Profiles"},
     *      summary="Détails d'un profil",
     *      description="Retourne les détails d'un profil spécifique",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="ID du profil",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Détails du profil",
     *          @OA\JsonContent(
     *              @OA\Property(property="profile", ref="#/components/schemas/Profile")
     *          )
     *      )
     * )
     */
    public function show(string $id): JsonResponse
    {
        $profile = Profile::with('user')->findOrFail($id);

        return response()->json([
            'profile' => $profile
        ]);
    }

    /**
     * @OA\Put(
     *      path="/api/profiles/{id}",
     *      operationId="updateProfile",
     *      tags={"Profiles"},
     *      summary="Modifier un profil",
     *      description="Met à jour les informations d'un profil",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="ID du profil",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Profile")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Profil mis à jour avec succès",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *              @OA\Property(property="profile", ref="#/components/schemas/Profile")
     *          )
     *      )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $profile = Profile::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile->update($request->only([
            'first_name',
            'last_name',
            'phone',
            'address',
            'city',
            'postal_code',
            'country',
            'date_of_birth',
            'emergency_contact_name',
            'emergency_contact_phone'
        ]));

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile
        ]);
    }

    /**
     * @OA\Delete(
     *      path="/api/profiles/{id}",
     *      operationId="deleteProfile",
     *      tags={"Profiles"},
     *      summary="Supprimer un profil",
     *      description="Supprime un profil utilisateur",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="ID du profil",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Profil supprimé avec succès",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Profile deleted successfully")
     *          )
     *      )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $profile = Profile::findOrFail($id);
        $profile->delete();

        return response()->json([
            'message' => 'Profile deleted successfully'
        ]);
    }

    /**
     * Get the current authenticated user's profile
     */
    public function currentUserProfile(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Get or create profile for the user
        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile) {
            $profile = Profile::create([
                'user_id' => $user->id,
                'first_name' => $user->name,
                'last_name' => '',
                'phone' => $user->phone,
            ]);
        }

        $result = [
            'profile' => $profile,
            'user' => $user
        ];

        // Add teacher data if user is a teacher
        if ($user->role === 'teacher') {
            $teacher = $user->teacher;
            $result['teacher'] = $teacher;
        }

        // Add student data if user is a student
        if ($user->role === 'student') {
            $student = $user->student;
            $result['student'] = $student;
        }

        return response()->json($result);
    }

    /**
     * Update the current authenticated user's profile
     */
    public function updateCurrentUserProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            // Teacher specific fields
            'specialties' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
            'certifications' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric|min:0',
            // Student specific fields
            'riding_level' => 'nullable|string',
            'course_preferences' => 'nullable|string',
            'emergency_contact' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update user basic info
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }
        $user->save();

        // Get or create profile
        $profile = Profile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $user->name,
                'last_name' => '',
            ]
        );

        // Update profile
        if ($request->has('birth_date')) {
            $profile->date_of_birth = $request->birth_date;
        }
        if ($request->has('phone')) {
            $profile->phone = $request->phone;
        }
        $profile->save();

        // Update teacher data if user is a teacher
        if ($user->role === 'teacher' && $user->teacher) {
            $teacher = $user->teacher;
            if ($request->has('specialties')) {
                $teacher->specialties = $request->specialties;
            }
            if ($request->has('experience_years')) {
                $teacher->experience_years = $request->experience_years;
            }
            if ($request->has('certifications')) {
                $teacher->certifications = $request->certifications;
            }
            if ($request->has('hourly_rate')) {
                $teacher->hourly_rate = $request->hourly_rate;
            }
            $teacher->save();
        }

        // Update student data if user is a student
        if ($user->role === 'student' && $user->student) {
            $student = $user->student;
            if ($request->has('riding_level')) {
                $student->level = $request->riding_level;
            }
            if ($request->has('course_preferences')) {
                $student->course_preferences = $request->course_preferences;
            }
            if ($request->has('emergency_contact')) {
                $student->emergency_contact = $request->emergency_contact;
            }
            $student->save();
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile,
            'user' => $user->fresh()
        ]);
    }
}
