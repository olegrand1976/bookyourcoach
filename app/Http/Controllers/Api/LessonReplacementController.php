<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBulkLessonReplacementRequest;
use App\Models\Club;
use App\Models\Lesson;
use App\Models\LessonReplacement;
use App\Models\Teacher;
use App\Services\LessonReplacementRequestService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessonReplacementController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected LessonReplacementRequestService $replacementRequestService
    ) {}

    /**
     * Liste des demandes de remplacement pour l'enseignant connecté
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable',
                ], 404);
            }

            $replacements = LessonReplacement::with([
                'lesson.student.user',
                'lesson.courseType',
                'lesson.club',
                'originalTeacher.user',
                'replacementTeacher.user',
            ])
                ->where(function ($query) use ($teacher) {
                    $query->where('original_teacher_id', $teacher->id)
                        ->orWhere('replacement_teacher_id', $teacher->id);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $replacements,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ [LessonReplacement] Erreur lors de la récupération des remplacements: '.$e->getMessage());
            Log::error('❌ [LessonReplacement] Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des remplacements',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Créer une demande de remplacement
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'lesson_id' => 'required|exists:lessons,id',
                'replacement_teacher_id' => 'required|exists:teachers,id',
                'reason' => 'required|string|max:500',
                'notes' => 'nullable|string|max:1000',
            ]);

            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable',
                ], 404);
            }

            $replacementTeacher = Teacher::findOrFail($validated['replacement_teacher_id']);
            $lesson = Lesson::findOrFail($validated['lesson_id']);

            $error = $this->replacementRequestService->validateLessonForReplacement($teacher, $lesson, $replacementTeacher);
            if ($error !== null) {
                $status = str_contains($error, 'ne vous appartient pas') ? 403 : 400;

                return response()->json([
                    'success' => false,
                    'message' => $error,
                ], $status);
            }

            $replacement = LessonReplacement::create([
                'lesson_id' => $validated['lesson_id'],
                'original_teacher_id' => $teacher->id,
                'replacement_teacher_id' => $validated['replacement_teacher_id'],
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]);

            $replacement->load([
                'lesson.student.user',
                'lesson.courseType',
                'lesson.club',
                'originalTeacher.user',
                'replacementTeacher.user',
            ]);

            $this->notificationService->notifyReplacementRequest($replacement);

            if ($replacement->lesson->club_id) {
                $club = Club::find($replacement->lesson->club_id);
                if ($club) {
                    $this->notificationService->mailClubAdminsReplacementRequestDigest(
                        $club,
                        $replacement->originalTeacher,
                        $replacement->replacementTeacher,
                        $replacement->reason,
                        $replacement->notes,
                        collect([$replacement->lesson])
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Demande de remplacement créée avec succès',
                'data' => $replacement,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la demande de remplacement: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la demande',
            ], 500);
        }
    }

    /**
     * Créer plusieurs demandes de remplacement (même remplaçant, même club).
     */
    public function storeBulk(StoreBulkLessonReplacementRequest $request)
    {
        try {
            $teacher = $request->user()->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable',
                ], 404);
            }

            $validated = $request->validated();

            $result = $this->replacementRequestService->validateBulkLessons(
                $teacher,
                $validated['lesson_ids'],
                (int) $validated['replacement_teacher_id']
            );

            if ($result['error'] !== null) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'],
                ], 400);
            }

            /** @var \Illuminate\Support\Collection<int, Lesson> $lessons */
            $lessons = $result['lessons'];
            $replacementTeacher = $result['replacementTeacher'];
            $club = Club::findOrFail((int) $result['clubId']);

            $created = DB::transaction(function () use ($lessons, $teacher, $replacementTeacher, $validated) {
                $rows = [];
                foreach ($lessons as $lesson) {
                    $rows[] = LessonReplacement::create([
                        'lesson_id' => $lesson->id,
                        'original_teacher_id' => $teacher->id,
                        'replacement_teacher_id' => $replacementTeacher->id,
                        'reason' => $validated['reason'],
                        'notes' => $validated['notes'] ?? null,
                        'status' => 'pending',
                    ]);
                }

                return $rows;
            });

            foreach ($created as $replacement) {
                $replacement->load([
                    'lesson.student.user',
                    'lesson.courseType',
                    'lesson.club',
                    'originalTeacher.user',
                    'replacementTeacher.user',
                ]);
                $this->notificationService->notifyReplacementRequest($replacement);
            }

            $this->notificationService->mailClubAdminsReplacementRequestDigest(
                $club,
                $teacher,
                $replacementTeacher,
                $validated['reason'],
                $validated['notes'] ?? null,
                $lessons
            );

            return response()->json([
                'success' => true,
                'message' => count($created).' demande(s) de remplacement créée(s) avec succès',
                'data' => $created,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création groupée de demandes de remplacement: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création des demandes',
            ], 500);
        }
    }

    /**
     * Répondre à une demande de remplacement (accepter/refuser)
     */
    public function respond(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'action' => 'required|in:accept,reject',
            ]);

            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable',
                ], 404);
            }

            $replacement = LessonReplacement::with([
                'lesson',
                'originalTeacher.user',
                'replacementTeacher.user',
            ])->findOrFail($id);

            if ($replacement->replacement_teacher_id !== $teacher->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à répondre à cette demande',
                ], 403);
            }

            if ($replacement->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette demande a déjà été traitée',
                ], 400);
            }

            DB::beginTransaction();

            try {
                if ($validated['action'] === 'accept') {
                    $replacement->status = 'accepted';
                    $replacement->responded_at = now();
                    $replacement->save();

                    $lesson = $replacement->lesson;
                    $originalTeacherId = $lesson->teacher_id;
                    $lesson->teacher_id = $replacement->replacement_teacher_id;
                    $lesson->save();

                    $lesson->refresh();
                    if ($lesson->teacher_id !== $replacement->replacement_teacher_id) {
                        Log::error('❌ [LessonReplacement] Échec du transfert du cours', [
                            'lesson_id' => $lesson->id,
                            'expected_teacher_id' => $replacement->replacement_teacher_id,
                            'actual_teacher_id' => $lesson->teacher_id,
                        ]);
                        throw new \Exception('Échec du transfert du cours vers le remplaçant');
                    }

                    Log::info('✅ [LessonReplacement] Cours transféré automatiquement', [
                        'lesson_id' => $lesson->id,
                        'original_teacher_id' => $originalTeacherId,
                        'new_teacher_id' => $replacement->replacement_teacher_id,
                        'replacement_id' => $replacement->id,
                    ]);

                    DB::commit();

                    $replacement->load(['lesson.club', 'lesson.teacher.user', 'lesson.courseType', 'originalTeacher.user', 'replacementTeacher.user']);
                    $this->notificationService->notifyReplacementAccepted($replacement);

                    return response()->json([
                        'success' => true,
                        'message' => 'Remplacement accepté avec succès. Le cours a été transféré automatiquement.',
                        'data' => $replacement,
                    ]);
                }

                $replacement->status = 'rejected';
                $replacement->responded_at = now();
                $replacement->save();

                DB::commit();

                $replacement->load(['originalTeacher.user', 'replacementTeacher.user']);
                $this->notificationService->notifyReplacementRejected($replacement);

                return response()->json([
                    'success' => true,
                    'message' => 'Remplacement refusé',
                    'data' => $replacement,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la réponse à la demande de remplacement: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement de la réponse',
            ], 500);
        }
    }

    /**
     * Annuler une demande de remplacement (par le professeur d'origine)
     */
    public function cancel(Request $request, $id)
    {
        try {
            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant introuvable',
                ], 404);
            }

            $replacement = LessonReplacement::findOrFail($id);

            if ($replacement->original_teacher_id !== $teacher->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à annuler cette demande',
                ], 403);
            }

            if ($replacement->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'annuler une demande déjà traitée',
                ], 400);
            }

            $replacement->status = 'cancelled';
            $replacement->save();

            return response()->json([
                'success' => true,
                'message' => 'Demande de remplacement annulée',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'annulation de la demande: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation',
            ], 500);
        }
    }
}
