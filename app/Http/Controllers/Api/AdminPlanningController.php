<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClubClosureDay;
use App\Models\ClubOpenSlot;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class AdminPlanningController extends Controller
{
    /**
     * Statuts de cours pris en compte pour déterminer l'amplitude d'ouverture.
     * On ne compte que les cours réellement assurés.
     */
    private const COUNTED_STATUSES = ['confirmed', 'completed'];

    /**
     * Heures d'ouverture du club connecté sur une période.
     *
     * Découpage par plage d'ouverture (ClubOpenSlot) : pour chaque créneau du jour,
     * l'amplitude est mesurée sur les cours réellement donnés à l'intérieur
     * (premier début réel -> dernière fin réelle), puis sommée sur la journée.
     */
    public function openingHours(Request $request): JsonResponse
    {
        $club = $request->user()->getFirstClub();
        if (! $club) {
            return response()->json(['success' => false, 'message' => 'Club non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $clubId = $club->id;

        // Par défaut : mois précédent complet
        $from = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $to = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))->endOfDay()
            : (clone $from)->endOfMonth();

        // Plages d'ouverture actives du club, indexées par jour de semaine (0=dim .. 6=sam)
        $slotsByDow = ClubOpenSlot::where('club_id', $clubId)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        // Cours réellement assurés sur la période
        $lessons = Lesson::where('club_id', $clubId)
            ->whereIn('status', self::COUNTED_STATUSES)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->inDateRange($from, $to)
            ->orderBy('start_time')
            ->get(['id', 'start_time', 'end_time', 'status']);

        $lessonsByDate = $lessons->groupBy(fn (Lesson $l) => $l->start_time->format('Y-m-d'));

        // Jours de fermeture / congés du club sur la période : les cours qui y tombent
        // ne sont pas réellement assurés -> exclus de l'amplitude d'ouverture (ex. vacances de juillet).
        $closedDates = ClubClosureDay::where('club_id', $clubId)
            ->whereBetween('closed_on', [$from->toDateString(), $to->toDateString()])
            ->pluck('closed_on')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->flip();

        $days = [];
        $totalHours = 0.0;

        foreach ($lessonsByDate as $date => $dayLessons) {
            if ($closedDates->has($date)) {
                continue; // club fermé ce jour-là : aucun cours compté
            }

            $dow = Carbon::parse($date)->dayOfWeek; // 0=dim .. 6=sam
            $daySlots = $slotsByDow->get($dow, collect());

            $creneaux = [];
            $assignedIds = [];

            foreach ($daySlots as $slot) {
                $slotStart = $this->timeToMinutes($slot->start_time);
                $slotEnd = $this->timeToMinutes($slot->end_time);

                $inSlot = $dayLessons->filter(function (Lesson $l) use ($slotStart, $slotEnd, $assignedIds) {
                    if (in_array($l->id, $assignedIds, true)) {
                        return false;
                    }
                    $m = (int) $l->start_time->format('H') * 60 + (int) $l->start_time->format('i');

                    return $m >= $slotStart && $m < $slotEnd;
                });

                if ($inSlot->isEmpty()) {
                    continue; // créneau sans cours -> 0h, non listé
                }

                $assignedIds = array_merge($assignedIds, $inSlot->pluck('id')->all());
                $creneaux[] = $this->buildCreneau($inSlot, $slot);
            }

            // Cours hors de toute plage (plage supprimée/modifiée depuis) : rattachés
            // à un créneau "hors plage" pour ne pas perdre d'heures.
            $unassigned = $dayLessons->reject(fn (Lesson $l) => in_array($l->id, $assignedIds, true));
            if ($unassigned->isNotEmpty()) {
                $creneaux[] = $this->buildCreneau($unassigned, null);
            }

            $dayHours = round(array_sum(array_column($creneaux, 'hours')), 2);
            $totalHours += $dayHours;

            $days[$date] = [
                'date' => $date,
                'hours' => $dayHours,
                'lessons_count' => $dayLessons->count(),
                'creneaux' => $creneaux,
            ];
        }

        ksort($days);

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'date_from' => $from->toDateString(),
                    'date_to' => $to->toDateString(),
                ],
                'total_hours' => round($totalHours, 2),
                'days_count' => count($days),
                'days' => array_values($days),
            ],
        ]);
    }

    /**
     * Construit un créneau à partir des cours qu'il contient.
     *
     * @param  Collection<int, Lesson>  $lessons
     */
    private function buildCreneau(Collection $lessons, ?ClubOpenSlot $slot): array
    {
        $firstStart = $lessons->sortBy(fn (Lesson $l) => $l->start_time->timestamp)->first()->start_time;
        $lastEnd = $lessons->sortByDesc(fn (Lesson $l) => $l->end_time->timestamp)->first()->end_time;
        $minutes = $firstStart->diffInMinutes($lastEnd);

        return [
            'slot_id' => $slot?->id,
            'slot_start' => $slot ? substr((string) $slot->start_time, 0, 5) : null,
            'slot_end' => $slot ? substr((string) $slot->end_time, 0, 5) : null,
            'out_of_range' => $slot === null,
            'first_start' => $firstStart->format('H:i'),
            'last_end' => $lastEnd->format('H:i'),
            'hours' => round($minutes / 60, 2),
            'lessons_count' => $lessons->count(),
        ];
    }

    /**
     * Convertit une heure "HH:MM[:SS]" en minutes depuis minuit.
     */
    private function timeToMinutes($time): int
    {
        $parts = array_pad(explode(':', (string) $time), 2, '0');

        return ((int) $parts[0]) * 60 + (int) $parts[1];
    }
}
