<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexClubClosureDayRequest;
use App\Http\Requests\ShowClubClosureDayImpactRequest;
use App\Http\Requests\UpsertClubClosureDayRequest;
use App\Models\ClubClosureDay;
use App\Models\ClubClosureRequest;
use App\Services\ClubClosureDayService;
use App\Services\ClubClosureRequestRecorder;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ClubClosureDayController extends Controller
{
    public function __construct(
        private readonly ClubClosureDayService $clubClosureDayService,
        private readonly ClubClosureRequestRecorder $requestRecorder,
        private readonly TwoFactorService $twoFactor,
    ) {}

    public function index(IndexClubClosureDayRequest $request): JsonResponse
    {
        $club = $request->user()->getFirstClub();
        if (! $club) {
            return response()->json(['success' => false, 'message' => 'Club non trouvé'], 404);
        }

        $dateFrom = $request->validated('date_from');
        $dateTo = $request->validated('date_to');

        $dates = ClubClosureDay::query()
            ->where('club_id', $club->id)
            ->whereBetween('closed_on', [$dateFrom, $dateTo])
            ->orderBy('closed_on')
            ->get()
            ->map(fn (ClubClosureDay $row) => $row->closed_on->format('Y-m-d'))
            ->values();

        return response()->json([
            'success' => true,
            'data' => ['dates' => $dates],
            'message' => null,
        ]);
    }

    /**
     * Aperçu de ce qu'une fermeture entraînerait : le client ne doit plus le déduire
     * de sa propre liste de cours, qui peut être vide pour une simple erreur réseau.
     */
    public function impact(ShowClubClosureDayImpactRequest $request): JsonResponse
    {
        $club = $request->user()->getFirstClub();
        if (! $club) {
            return response()->json(['success' => false, 'message' => 'Club non trouvé'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->clubClosureDayService->impactFor($club, $request->validated('date')),
            'message' => null,
        ]);
    }

    public function upsert(UpsertClubClosureDayRequest $request): JsonResponse
    {
        $club = $request->user()->getFirstClub();
        if (! $club) {
            return response()->json(['success' => false, 'message' => 'Club non trouvé'], 404);
        }

        $dateYmd = $request->validated('date');
        $closed = $request->boolean('closed');
        $action = $closed ? ClubClosureRequest::ACTION_CLOSE : ClubClosureRequest::ACTION_OPEN;
        $method = $request->validated('confirmation_method');
        $impactedLessons = null;

        if ($closed) {
            // L'impact est contrôlé avant le secret : un planning périmé ne doit pas
            // consommer un code 2FA, qui ne sert qu'une fois.
            $impact = $this->clubClosureDayService->impactFor($club, $dateYmd);
            $impactedLessons = $impact['lessons_count'];

            $refusal = $this->refuseUnacknowledgedClosure($request, $impact);
            if ($refusal !== null) {
                $this->requestRecorder->record(
                    $request, $club, $request->user(), $dateYmd, $action, $method,
                    ClubClosureRequest::OUTCOME_IMPACT_MISMATCH, $impactedLessons,
                );

                return $refusal;
            }
        }

        if (! $this->confirmationIsValid($request)) {
            $this->requestRecorder->record(
                $request, $club, $request->user(), $dateYmd, $action, $method,
                ClubClosureRequest::OUTCOME_INVALID_CREDENTIAL, $impactedLessons,
            );

            return response()->json([
                'success' => false,
                'message' => $method === ClubClosureRequest::METHOD_TOTP
                    ? 'Code 2FA incorrect ou déjà utilisé.'
                    : 'Mot de passe incorrect.',
                'data' => ['code' => 'CLOSURE_CONFIRMATION_INVALID'],
            ], 422);
        }

        // Tracée après l'action, pour que « success » veuille dire que c'est fait ;
        // une erreur est tracée aussi, puis remontée telle quelle.
        $trace = fn (string $outcome) => $this->requestRecorder->record(
            $request, $club, $request->user(), $dateYmd, $action, $method, $outcome, $impactedLessons,
        );

        try {
            $response = $closed ? $this->close($club, $dateYmd, $request) : $this->open($club, $dateYmd, $request);
        } catch (\Throwable $e) {
            $trace(ClubClosureRequest::OUTCOME_ERROR);

            throw $e;
        }

        $trace(ClubClosureRequest::OUTCOME_SUCCESS);

        return $response;
    }

    private function close($club, string $dateYmd, UpsertClubClosureDayRequest $request): JsonResponse
    {
        $meta = $this->clubClosureDayService->closeDay($club, $dateYmd, $request->user());

        return response()->json([
            'success' => true,
            'data' => $meta,
            'message' => $meta['notified']
                ? 'Jour marqué comme fermé. Les crédits abonnement ont été restitués pour les cours liés ce jour-là. Les participants ont été notifiés par e-mail.'
                : 'Ce jour était déjà marqué comme fermé.',
        ]);
    }

    private function open($club, string $dateYmd, UpsertClubClosureDayRequest $request): JsonResponse
    {
        $result = $this->clubClosureDayService->openDay($club, $dateYmd, $request->user());

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => $result['reopened']
                ? $this->reopenMessage($result)
                : 'Aucune fermeture enregistrée pour cette date.',
        ]);
    }

    /**
     * Fermer une journée qui porte des cours détache des abonnements et notifie tout le
     * monde : on exige que le client annonce l'impact qu'il croit provoquer. S'il ne
     * l'annonce pas, ou si son chiffre ne correspond pas, il travaille sur une vue
     * périmée — onglet resté ouvert, chargement des cours en échec — et on refuse.
     */
    private function refuseUnacknowledgedClosure(UpsertClubClosureDayRequest $request, array $impact): ?JsonResponse
    {
        // Journée déjà fermée : l'appel est idempotent, rien à confirmer.
        if ($impact['already_closed'] || $impact['lessons_count'] === 0) {
            return null;
        }

        if (! $request->boolean('acknowledge_impact') || $request->input('expected_impacted_lessons') === null) {
            return response()->json([
                'success' => false,
                'message' => 'Cette journée comporte des cours. Rechargez le planning pour voir ce que la fermeture entraînerait, puis confirmez.',
                'data' => ['code' => 'CLOSURE_IMPACT_UNACKNOWLEDGED', 'impact' => $impact],
            ], 409);
        }

        if ((int) $request->input('expected_impacted_lessons') !== $impact['lessons_count']) {
            return response()->json([
                'success' => false,
                'message' => 'Le planning affiché ne correspond plus à la réalité de cette journée. Rechargez la page avant de fermer.',
                'data' => ['code' => 'CLOSURE_IMPACT_MISMATCH', 'impact' => $impact],
            ], 409);
        }

        return null;
    }

    /**
     * Mot de passe du compte, ou code courant de l'application 2FA. Les codes de
     * récupération sont refusés : ils servent à retrouver l'accès, pas à valider
     * une action courante.
     */
    private function confirmationIsValid(UpsertClubClosureDayRequest $request): bool
    {
        $user = $request->user();

        if ($request->validated('confirmation_method') === ClubClosureRequest::METHOD_TOTP) {
            return $user->hasTwoFactorEnabled()
                && $this->twoFactor->verifyCode($user, (string) $request->validated('code'));
        }

        return Hash::check((string) $request->validated('password'), (string) $user->password);
    }

    /**
     * @param  array{reopened: bool, restored_links: int, skipped_links: int}  $result
     */
    private function reopenMessage(array $result): string
    {
        $message = 'Congé annulé. Les moniteurs et élèves concernés ont été notifiés par e-mail.';

        if ($result['restored_links'] > 0) {
            $message .= ' '.$result['restored_links'].' lien(s) abonnement restauré(s).';
        }

        if ($result['skipped_links'] > 0) {
            $message .= ' '.$result['skipped_links'].' lien(s) non restauré(s) : les cours ou carnets concernés n’existent plus.';
        }

        return $message;
    }
}
