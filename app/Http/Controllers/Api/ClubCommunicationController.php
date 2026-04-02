<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendClubGeneralCommunicationRequest;
use App\Models\ClubCommunicationLog;
use App\Services\ClubCommunicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ClubCommunicationController extends Controller
{
    public function __construct(
        protected ClubCommunicationService $clubCommunicationService
    ) {}

    public function recipientCounts(Request $request): JsonResponse
    {
        $user = $request->user();
        $club = $user?->getFirstClub();

        if (!$club) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun club associé à votre compte.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->clubCommunicationService->recipientCounts($club),
        ]);
    }

    public function contacts(Request $request): JsonResponse
    {
        $user = $request->user();
        $club = $user?->getFirstClub();

        if (!$club) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun club associé à votre compte.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->clubCommunicationService->listContacts($club),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $club = $user?->getFirstClub();

        if (!$club) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun club associé à votre compte.',
            ], 404);
        }

        $scope = $request->query('scope', 'teachers');
        if (!in_array($scope, ['teachers', 'students'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètre scope invalide (teachers ou students).',
            ], 422);
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);
        $paginator = $this->clubCommunicationService->paginateHistory($club, $scope, $perPage);

        $items = $paginator->getCollection()->map(function (ClubCommunicationLog $log) {
            return [
                'id' => $log->id,
                'subject' => $log->subject,
                'body_preview' => Str::limit($log->body, 220),
                'created_at' => $log->created_at?->toIso8601String(),
                'sent_by' => [
                    'name' => $log->sentBy?->name,
                    'email' => $log->sentBy?->email,
                ],
                'recipient_count' => $log->recipient_count,
                'sent_count' => $log->sent_count,
                'failed_count' => $log->failed_count,
                'teacher_recipient_count' => $log->teacher_recipient_count,
                'student_recipient_count' => $log->student_recipient_count,
                'audience' => $log->audience,
                'selection_mode' => $log->selection_mode,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function send(SendClubGeneralCommunicationRequest $request): JsonResponse
    {
        $user = $request->user();
        $club = $user->getFirstClub();

        if (!$club) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun club associé à votre compte.',
            ], 404);
        }

        $validated = $request->validated();
        $subject = trim(strip_tags($validated['subject']));
        $body = trim(strip_tags($validated['body']));

        if ($subject === '' || $body === '') {
            return response()->json([
                'success' => false,
                'message' => 'Le sujet et le message ne peuvent pas être vides.',
            ], 422);
        }

        $selectionMode = $validated['selection_mode'];
        $audience = $validated['audience'] ?? null;
        $teacherIds = $validated['teacher_ids'] ?? [];
        $studentIds = $validated['student_ids'] ?? [];

        try {
            $context = $this->clubCommunicationService->resolveSendContext(
                $club,
                $selectionMode,
                $audience,
                $teacherIds,
                $studentIds
            );
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if (count($context['emails']) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun destinataire avec une adresse email valide pour cette sélection.',
            ], 422);
        }

        $stats = $this->clubCommunicationService->deliverAndLog(
            $club,
            $user,
            $context,
            $subject,
            $body
        );

        $message = sprintf(
            '%d message(s) envoyé(s) sur %d adresse(s) distincte(s).',
            $stats['sent_count'],
            $stats['recipient_count']
        );

        if ($stats['failed_count'] > 0) {
            $message .= sprintf(' %d envoi(s) ont échoué (voir les logs serveur).', $stats['failed_count']);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $stats,
        ]);
    }
}
