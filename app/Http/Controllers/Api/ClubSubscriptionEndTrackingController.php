<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLastSubscriptionRequest;
use App\Models\AuditLog;
use App\Models\SubscriptionInstance;
use App\Services\ProlongRecurringSlotsService;
use App\Services\SubscriptionExpiryWatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Suivi des fins d'abonnement (espace club).
 */
class ClubSubscriptionEndTrackingController extends Controller
{
    public function __construct(
        private readonly ProlongRecurringSlotsService $prolongService,
        private readonly SubscriptionExpiryWatchService $watchService,
    ) {}

    /**
     * Écran de suivi + indicateur du tableau de bord : fins d'abonnement de tout le club.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'club') {
            return response()->json(['success' => false, 'message' => 'Accès réservé aux clubs'], 403);
        }
        $club = $user->getFirstClub();
        if (! $club) {
            return response()->json(['success' => false, 'message' => 'Aucun club associé'], 404);
        }

        $tz = (string) config('bookyourcoach.subscription_expiry_watch.timezone', 'Europe/Brussels');
        $lookback = max(1, min(180, (int) $request->input('ended_days', 30)));

        return response()->json([
            'success' => true,
            'data' => $this->watchService->buildEndTracking($club, $tz, $lookback),
            'message' => 'Suivi des fins d\'abonnement.',
        ]);
    }

    /**
     * Mention « dernier abonnement » : cochée, les séries de l'élève s'arrêtent à la fin de
     * l'abonnement ; décochée, elles redeviennent ouvertes sans fin. Appliqué tout de suite.
     */
    public function updateLastSubscription(UpdateLastSubscriptionRequest $request, int $instanceId): JsonResponse
    {
        $user = $request->user();
        $club = $user->getFirstClub();
        if (! $club) {
            return response()->json(['success' => false, 'message' => 'Aucun club associé'], 404);
        }

        // Cloisonnement : uniquement un abonnement de ce club.
        $instance = SubscriptionInstance::query()
            ->whereHas('subscription', fn ($q) => $q->forClub($club->id))
            ->with('students:id')
            ->find($instanceId);
        if (! $instance) {
            return response()->json(['success' => false, 'message' => 'Abonnement introuvable'], 404);
        }

        $old = (bool) $instance->is_last_subscription;
        $new = (bool) $request->validated('is_last_subscription');

        if ($old !== $new) {
            $instance->is_last_subscription = $new;
            $instance->save();

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'subscription_instance_updated',
                'model_type' => SubscriptionInstance::class,
                'model_id' => $instance->id,
                'data' => [
                    'changes' => ['is_last_subscription' => ['old' => $old, 'new' => $new]],
                    'instance_id' => $instance->id,
                    'subscription_id' => $instance->subscription_id,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            foreach ($instance->students as $student) {
                try {
                    $this->prolongService->prolongActiveRecurrences((int) $student->id, (int) $club->id);
                } catch (\Throwable $e) {
                    // La mention est enregistrée ; la tâche de nuit réappliquera la règle.
                    Log::warning('Dernier abonnement : réapplication des récurrences impossible', [
                        'instance_id' => $instance->id,
                        'student_id' => $student->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $instance->id,
                'is_last_subscription' => (bool) $instance->is_last_subscription,
            ],
            'message' => $new
                ? 'Dernier abonnement : la récurrence s\'arrêtera à sa fin.'
                : 'La récurrence continue au-delà de cet abonnement.',
        ]);
    }
}
