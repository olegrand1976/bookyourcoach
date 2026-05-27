<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\LinkChildRequest;
use App\Models\Student;
use App\Models\User;
use App\Services\FamilyLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Gestion "compte famille" côté parent : liste des enfants rattachés,
 * rattachement par code d'invitation, dissociation.
 */
class FamilyController extends Controller
{
    public function __construct(private readonly FamilyLinkService $familyLinkService)
    {
    }

    /**
     * Liste les enfants rattachés au compte parent (foyer hors profil principal).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $primaryStudentId = (int) Student::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->value('id');

        $householdIds = $user->getHouseholdStudentIds();

        $children = Student::with(['user', 'club', 'clubs'])
            ->whereIn('id', $householdIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function (Student $student) use ($primaryStudentId, $user) {
                $isPrimary = (int) $student->id === $primaryStudentId;
                $isLinkedChild = $student->user_id === $user->id && ! $isPrimary;

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'date_of_birth' => $student->date_of_birth,
                    'age' => $student->age,
                    'email' => $student->user?->email,
                    'is_primary' => $isPrimary,
                    'is_linked_child' => $isLinkedChild,
                    'linked_at' => $student->linked_at,
                    'club' => $student->club ? [
                        'id' => $student->club->id,
                        'name' => $student->club->name,
                    ] : null,
                    'clubs' => $student->clubs->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                    ])->values(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $children->values(),
        ]);
    }

    /**
     * Rattache un enfant existant au compte parent via le code d'invitation.
     */
    public function linkChild(LinkChildRequest $request): JsonResponse
    {
        $parent = $request->user();
        $code = (string) $request->input('invite_code');

        try {
            $child = $this->familyLinkService->linkChildToParent($code, $parent);
        } catch (RuntimeException $e) {
            return $this->mapLinkErrorToResponse($e->getMessage());
        } catch (Throwable $e) {
            Log::error('Erreur rattachement enfant', [
                'parent_user_id' => $parent->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rattachement de l\'enfant',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $child->id,
                'name' => $child->name,
                'first_name' => $child->first_name,
                'last_name' => $child->last_name,
                'linked_at' => $child->linked_at,
            ],
            'message' => 'Enfant rattaché avec succès à votre compte famille.',
        ]);
    }

    /**
     * Dissocie un enfant du compte parent.
     */
    public function unlinkChild(Request $request, int $studentId): JsonResponse
    {
        $parent = $request->user();
        if (! $parent) {
            return response()->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $child = Student::find($studentId);
        if (! $child) {
            return response()->json(['success' => false, 'message' => 'Enfant introuvable'], 404);
        }

        if ($child->user_id !== $parent->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cet enfant n\'est pas rattaché à votre compte.'
            ], 403);
        }

        $primaryStudentId = (int) Student::query()
            ->where('user_id', $parent->id)
            ->orderBy('id')
            ->value('id');

        if ((int) $child->id === $primaryStudentId) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de dissocier votre profil principal.'
            ], 422);
        }

        try {
            $this->familyLinkService->unlinkChildFromParent($child, $parent);
        } catch (Throwable $e) {
            Log::error('Erreur dissociation enfant', [
                'parent_user_id' => $parent->id,
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la dissociation',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Enfant dissocié du compte famille.',
        ]);
    }

    private function mapLinkErrorToResponse(string $key): JsonResponse
    {
        return match ($key) {
            'parent_role_invalid' => response()->json([
                'success' => false,
                'message' => 'Seul un compte élève peut rattacher un enfant.',
            ], 403),
            'code_not_found' => response()->json([
                'success' => false,
                'message' => 'Code d\'invitation introuvable. Vérifiez la saisie.',
            ], 404),
            'code_expired' => response()->json([
                'success' => false,
                'message' => 'Ce code d\'invitation a expiré. Demandez à votre club d\'en régénérer un.',
            ], 422),
            'already_linked' => response()->json([
                'success' => false,
                'message' => 'Cet enfant est déjà rattaché à un compte.',
            ], 409),
            default => response()->json([
                'success' => false,
                'message' => 'Erreur lors du rattachement de l\'enfant.',
                'error_key' => $key,
            ], 422),
        };
    }
}
