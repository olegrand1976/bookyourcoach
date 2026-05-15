<?php

namespace App\Services;

use App\Models\ClubOpenSlot;
use App\Services\AI\GeminiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Propose des créneaux récurrents alternatifs (validation 26 sem.) à partir des plages club (ClubOpenSlot).
 */
class RecurringPlanningAdviceService
{
    public function __construct(
        protected ?GeminiService $geminiService = null
    ) {
        $this->geminiService = $geminiService ?? app(GeminiService::class);
    }

    /**
     * @return array{
     *   requested: array{valid: bool, day_of_week: int, start_time: string, end_time: string, first_occurrence_date: string, message: ?string},
     *   alternatives: list<array<string, mixed>>,
     *   ai_summary: ?string,
     *   meta: array{candidates_validated: int, timezone: string}
     * }
     */
    public function buildAdvice(
        int $clubId,
        int $teacherId,
        int $studentId,
        Carbon $firstLessonStart,
        Carbon $firstLessonEnd,
        int $recurringInterval,
        ?int $excludeLessonId = null,
        ?int $courseTypeId = null,
    ): array {
        $tz = config('bookyourcoach.recurring_planning_advice.timezone', 'Europe/Brussels');
        $firstLessonStart = $firstLessonStart->copy()->timezone($tz);
        $firstLessonEnd = $firstLessonEnd->copy()->timezone($tz);

        $durationMin = max(1, (int) $firstLessonStart->diffInMinutes($firstLessonEnd));
        $recurringInterval = max(1, min(52, $recurringInterval));

        $validator = new RecurringSlotValidator;
        $reqStartDate = $firstLessonStart->format('Y-m-d');
        $reqDow = (int) $firstLessonStart->dayOfWeek;
        $reqStartT = $firstLessonStart->format('H:i:s');
        $reqEndT = $firstLessonEnd->format('H:i:s');

        $reqValidation = $validator->validateRecurringAvailabilityWithoutOpenSlot(
            $teacherId,
            $studentId,
            $reqStartDate,
            $reqDow,
            $reqStartT,
            $reqEndT,
            $recurringInterval,
            $excludeLessonId,
            $clubId
        );

        $requestedBlock = [
            'valid' => (bool) ($reqValidation['valid'] ?? false),
            'day_of_week' => $reqDow,
            'start_time' => substr($reqStartT, 0, 5),
            'end_time' => substr($reqEndT, 0, 5),
            'first_occurrence_date' => $reqStartDate,
            'message' => $reqValidation['valid'] ? null : ($reqValidation['message'] ?? null),
        ];

        $maxCandidates = (int) config('bookyourcoach.recurring_planning_advice.max_candidates_to_validate', 120);
        $maxReturn = (int) config('bookyourcoach.recurring_planning_advice.max_alternatives_returned', 12);

        $hadOpenSlots = ClubOpenSlot::query()
            ->where('club_id', $clubId)
            ->where('is_active', true)
            ->exists();

        $alternatives = [];
        $validatedCount = 0;

        if (! $reqValidation['valid']) {
            $anchor = $firstLessonStart->copy()->startOfDay();
            $reqStartMin = $this->timeToMinutes($firstLessonStart->format('H:i'));
            $candidates = $this->collectCandidates(
                $clubId,
                $durationMin,
                $anchor,
                $reqDow,
                $reqStartMin,
                $courseTypeId
            );

            usort($candidates, fn (array $a, array $b) => $b['heuristic_score'] <=> $a['heuristic_score']);

            foreach ($candidates as $c) {
                if ($validatedCount >= $maxCandidates || count($alternatives) >= $maxReturn) {
                    break;
                }
                $validatedCount++;

                $startHM = $c['start_time'];
                $startT = strlen((string) $startHM) === 5 ? ((string) $startHM).':00' : (string) $startHM;
                $endParts = explode(':', $c['end_time']);
                $endT = sprintf('%02d:%02d:00', (int) $endParts[0], (int) ($endParts[1] ?? 0));

                $validation = $validator->validateRecurringAvailabilityWithoutOpenSlot(
                    $teacherId,
                    $studentId,
                    $c['first_occurrence_date'],
                    (int) $c['day_of_week'],
                    $startT,
                    $endT,
                    $recurringInterval,
                    $excludeLessonId,
                    $clubId
                );

                if ($validation['valid']) {
                    $alternatives[] = [
                        'day_of_week' => (int) $c['day_of_week'],
                        'weekday_label' => $c['weekday_label'],
                        'start_time' => $c['start_time'],
                        'end_time' => $c['end_time'],
                        'first_occurrence_date' => $c['first_occurrence_date'],
                        'open_slot_id' => $c['open_slot_id'],
                        'open_slot_label' => $c['open_slot_label'],
                        'heuristic_score' => $c['heuristic_score'],
                    ];
                }
            }
        }

        $aiSummary = null;
        if (
            config('bookyourcoach.recurring_planning_advice.use_ai', true)
            && $this->geminiService->isAvailable()
            && (! $reqValidation['valid'])
            && $alternatives !== []
        ) {
            try {
                $aiSummary = $this->geminiService->summarizeRecurringPlanningAdvice([
                    'requested_valid' => false,
                    'conflicts_sample' => array_slice($reqValidation['conflicts'] ?? [], 0, 5),
                    'alternatives' => array_slice($alternatives, 0, 5),
                    'recurring_interval_weeks' => $recurringInterval,
                ]);
            } catch (\Throwable $e) {
                Log::warning('RecurringPlanningAdviceService: Gemini summary failed', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return [
            'requested' => $requestedBlock,
            'alternatives' => $alternatives,
            'ai_summary' => $aiSummary,
            'meta' => [
                'candidates_validated' => $validatedCount,
                'timezone' => $tz,
                'had_open_slots' => $hadOpenSlots,
                'hint' => ! $hadOpenSlots
                    ? 'Aucune plage horaire club (créneaux ouverts) active : ajoutez des plages dans la configuration du planning pour activer les suggestions automatiques.'
                    : null,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function collectCandidates(
        int $clubId,
        int $durationMinutes,
        Carbon $anchor,
        int $reqDow,
        int $reqStartMin,
        ?int $courseTypeId
    ): array {
        $query = ClubOpenSlot::query()
            ->where('club_id', $clubId)
            ->where('is_active', true)
            ->with(['courseTypes', 'discipline']);

        $slots = $query->get();

        if ($courseTypeId) {
            $slots = $slots->filter(function (ClubOpenSlot $slot) use ($courseTypeId) {
                $types = $slot->courseTypes;
                if ($types->isEmpty()) {
                    return true;
                }

                return $types->pluck('id')->contains($courseTypeId);
            });
        }

        $out = [];
        $seen = [];

        foreach ($slots as $slot) {
            $dow = (int) $slot->day_of_week;
            $slotStart = $this->timeToMinutes(is_string($slot->start_time) ? substr($slot->start_time, 0, 5) : '00:00');
            $slotEnd = $this->timeToMinutes(is_string($slot->end_time) ? substr($slot->end_time, 0, 5) : '00:00');
            $step = $this->timeStepForSlot($slot);

            $firstDate = $this->firstOccurrenceDateOnOrAfter($anchor, $dow);
            $weekdayLabel = $this->weekdayLabel($dow);
            $slotLabel = trim(($slot->discipline?->name ?? 'Plage').' '.substr((string) $slot->start_time, 0, 5).'–'.substr((string) $slot->end_time, 0, 5));

            for ($m = $slotStart; $m + $durationMinutes <= $slotEnd; $m += $step) {
                $startHi = $this->minutesToHi($m);
                $endHi = $this->minutesToHi($m + $durationMinutes);
                $key = $dow.'|'.$startHi;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $heuristic = ($dow === $reqDow ? 2000 : 500) - abs($m - $reqStartMin);

                $out[] = [
                    'day_of_week' => $dow,
                    'weekday_label' => $weekdayLabel,
                    'start_time' => $startHi,
                    'end_time' => $endHi,
                    'first_occurrence_date' => $firstDate,
                    'open_slot_id' => (int) $slot->id,
                    'open_slot_label' => $slotLabel,
                    'heuristic_score' => $heuristic,
                ];
            }
        }

        return $out;
    }

    protected function timeStepForSlot(ClubOpenSlot $slot): int
    {
        $durations = $slot->courseTypes
            ->pluck('duration_minutes')
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();
        if ($durations === []) {
            return 15;
        }
        $step = $this->gcdArray($durations);

        return max(5, min(30, $step));
    }

    /**
     * @param  array<int>  $numbers
     */
    protected function gcdArray(array $numbers): int
    {
        if ($numbers === []) {
            return 30;
        }
        if (count($numbers) === 1) {
            return (int) $numbers[0] ?: 30;
        }
        $result = (int) $numbers[0];
        for ($i = 1, $n = count($numbers); $i < $n; $i++) {
            $result = $this->gcd($result, (int) $numbers[$i]);
        }

        return $result ?: 30;
    }

    protected function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }

    protected function firstOccurrenceDateOnOrAfter(Carbon $anchor, int $targetDow): string
    {
        $d = $anchor->copy();
        while ((int) $d->dayOfWeek !== $targetDow) {
            $d->addDay();
        }

        return $d->format('Y-m-d');
    }

    protected function weekdayLabel(int $dow): string
    {
        $map = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

        return $map[$dow] ?? (string) $dow;
    }

    protected function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);

        return ((int) ($parts[0] ?? 0)) * 60 + (int) ($parts[1] ?? 0);
    }

    protected function minutesToHi(int $minutes): string
    {
        $minutes = max(0, $minutes);
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return sprintf('%02d:%02d', $h, $m);
    }
}
