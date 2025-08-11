<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="File Upload",
 *     description="Gestion des uploads de fichiers (avatars, certificats, logos)"
 * )
 */
class FileUploadController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/upload/avatar",
     *     summary="Upload d'un avatar utilisateur",
     *     tags={"File Upload"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="string",
     *                     format="binary",
     *                     description="Fichier image pour l'avatar"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avatar uploadé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Avatar uploadé avec succès"),
     *             @OA\Property(property="path", type="string", example="avatars/user-123.jpg"),
     *             @OA\Property(property="url", type="string", example="http://localhost:8081/storage/avatars/user-123.jpg")
     *         )
     *     )
     * )
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('avatar');
            $filename = 'user-' . Auth::id() . '-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('avatars', $filename, 'public');

            // Supprimer l'ancien avatar s'il existe
            $user = Auth::user();
            if ($user->profile && $user->profile->avatar) {
                Storage::disk('public')->delete($user->profile->avatar);
            }

            // Mettre à jour le profil utilisateur
            if ($user->profile) {
                $user->profile->update(['avatar' => $path]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Avatar uploadé avec succès',
                'path' => $path,
                'url' => asset('storage/' . $path)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/upload/logo",
     *     summary="Upload d'un logo pour le rebranding (admin seulement)",
     *     tags={"File Upload"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="logo",
     *                     type="string",
     *                     format="binary",
     *                     description="Fichier logo de l'application"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logo uploadé avec succès"
     *     )
     * )
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        if (Auth::user()->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Seuls les administrateurs peuvent uploader des logos.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('logo');
            $filename = 'logo-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('logos', $filename, 'public');

            return response()->json([
                'success' => true,
                'message' => 'Logo uploadé avec succès',
                'path' => $path,
                'url' => asset('storage/' . $path)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/upload/certificate",
     *     summary="Upload d'un certificat pour un enseignant",
     *     tags={"File Upload"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="certificate",
     *                     type="string",
     *                     format="binary",
     *                     description="Fichier certificat"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="Nom du certificat"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Certificat uploadé avec succès"
     *     )
     * )
     */
    public function uploadCertificate(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if ($user->role !== User::ROLE_TEACHER && $user->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Seuls les enseignants peuvent uploader des certificats.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'certificate' => 'required|file|mimes:pdf,jpeg,png,jpg|max:5120', // 5MB max
            'name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('certificate');
            $filename = Str::slug($request->name) . '-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('certificates', $filename, 'public');

            return response()->json([
                'success' => true,
                'message' => 'Certificat uploadé avec succès',
                'path' => $path,
                'url' => asset('storage/' . $path),
                'name' => $request->name
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/upload/{path}",
     *     summary="Supprimer un fichier uploadé",
     *     tags={"File Upload"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="path",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Chemin du fichier à supprimer"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fichier supprimé avec succès"
     *     )
     * )
     */
    public function deleteFile(Request $request, string $path): JsonResponse
    {
        try {
            // Décoder le chemin
            $decodedPath = urldecode($path);
            
            // Vérifications de sécurité
            if (!Storage::disk('public')->exists($decodedPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé'
                ], 404);
            }

            // Vérifier les permissions
            $user = Auth::user();
            if ($user->role !== User::ROLE_ADMIN && !$this->userCanDeleteFile($user, $decodedPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], 403);
            }

            Storage::disk('public')->delete($decodedPath);

            return response()->json([
                'success' => true,
                'message' => 'Fichier supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    private function userCanDeleteFile($user, string $path): bool
    {
        // Seuls les administrateurs ou le propriétaire du fichier peuvent supprimer
        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        // Vérifier si c'est l'avatar de l'utilisateur
        if (str_contains($path, 'avatars/user-' . $user->id)) {
            return true;
        }

        return false;
    }
}
