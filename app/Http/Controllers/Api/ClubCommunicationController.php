<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendClubGeneralCommunicationRequest;
use App\Services\ClubCommunicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClubCommunicationController extends Controller
{
    public function __construct(
        protected ClubCommunicationService $clubCommunicationService
    ) {}

    /**
     * Nombre de destinataires potentiels (emails valides) par groupe.
     */
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
        $audience = $validated['audience'];
        $subject = trim(strip_tags($validated['subject']));
        $body = trim(strip_tags($validated['body']));

        if ($subject === '' || $body === '') {
            return response()->json([
                'success' => false,
                'message' => 'Le sujet et le message ne peuvent pas être vides.',
            ], 422);
        }

        $resolved = $this->clubCommunicationService->resolveRecipientEmails($club, $audience);

        if (count($resolved['emails']) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun destinataire avec une adresse email valide pour ce groupe.',
            ], 422);
        }

        $stats = $this->clubCommunicationService->sendGeneralCommunication(
            $club,
            $user,
            $audience,
            $subject,
            $body
        );

        $message = sprintf(
            '%d message(s) envoyé(s) sur %d destinataire(s).',
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
