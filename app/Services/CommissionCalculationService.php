<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use Carbon\Carbon;

/**
 * Service de calcul des commissions enseignants
 *
 * Ce service gère le calcul des commissions pour deux types d'abonnements :
 * - DCL (est_legacy = false) : Déclaré - Modèle standard et pérenne
 * - NDCL (est_legacy = true ou null) : Non Déclaré - Ancien modèle legacy ou cours non flaggés
 *
 * RÈGLE IMPORTANTE : Les cours/abonnements non flaggés (est_legacy = null) sont traités comme NDCL.
 *
 * Les règles de calcul sont différentes pour chaque type et peuvent être facilement
 * modifiées ou supprimées (pour NDCL) sans affecter le reste du code.
 *
 * Lignes cours : lorsqu’un tarif horaire est défini (pivot club_teachers pour le club du
 * cours si > 0, sinon champ teachers.hourly_rate) et que la durée séance est connue,
 * montant paie = heures × tarif horaire ; sinon recours montant cours / prix / prorata avec
 * notification sur la ligne détaillée.
 */
class CommissionCalculationService
{
    /**
     * Taux de commission pour DCL (Déclaré - standard)
     * 1.00 = 100% de commission (le montant complet)
     */
    private const DCL_COMMISSION_RATE = 1.00;

    /**
     * Taux de commission pour NDCL (Non Déclaré - legacy)
     * 1.00 = 100% de commission (le montant complet)
     *
     * Note : Identique au DCL. Cette méthode peut être supprimée
     *        lorsque tous les abonnements NDCL seront expirés.
     */
    private const NDCL_COMMISSION_RATE = 1.00;

    /**
     * Calculer la commission pour un abonnement DCL (Déclaré - standard)
     *
     * RÈGLE DCL : Commission = montant × taux_dcl
     *
     * @param  float  $montant  Montant de base de l'abonnement
     * @return float Montant de la commission
     */
    private function calculateDclCommission(float $montant): float
    {
        return round($montant * self::DCL_COMMISSION_RATE, 2);
    }

    /**
     * Calculer la commission pour un abonnement NDCL (Non Déclaré - legacy)
     *
     * RÈGLE NDCL : Commission = montant × taux_ndcl
     *
     * Note : Cette méthode peut être supprimée facilement lorsque tous les
     *        abonnements NDCL seront expirés.
     *
     * @param  float  $montant  Montant de base de l'abonnement
     * @return float Montant de la commission
     */
    private function calculateNdclCommission(float $montant): float
    {
        return round($montant * self::NDCL_COMMISSION_RATE, 2);
    }

    /**
     * Calculer la commission pour un abonnement selon son type
     *
     * @param  SubscriptionInstance  $subscriptionInstance  Instance d'abonnement
     * @return float Montant de la commission calculée
     *
     * @throws \InvalidArgumentException Si le montant est manquant ou invalide
     */
    public function calculateCommission(SubscriptionInstance $subscriptionInstance): float
    {
        // Vérifier que le montant est disponible
        if (! $subscriptionInstance->montant || $subscriptionInstance->montant <= 0) {
            throw new \InvalidArgumentException(
                "Le montant de l'abonnement {$subscriptionInstance->id} est manquant ou invalide."
            );
        }

        $montant = (float) $subscriptionInstance->montant;

        // Appliquer la règle selon le type d'abonnement
        // RÈGLE : est_legacy === false → DCL, sinon (true ou null) → NDCL
        if ($subscriptionInstance->est_legacy === false) {
            // DCL (Déclaré - Standard)
            return $this->calculateDclCommission($montant);
        } else {
            // NDCL (Non Déclaré - Legacy) ou non flaggé (null)
            return $this->calculateNdclCommission($montant);
        }
    }

    /**
     * Générer un rapport de paie pour une période donnée
     *
     * Inclut :
     * - Les paiements d'abonnement sans cours déjà reliés dans la période (SubscriptionInstance avec
     *   date_paiement dans le mois, sans ligne subscription_lessons) — ex. paiement avant consommation
     * - Tous les cours (Lesson) retenus dans le mois : hors abonnement et cours issus d'un abonnement,
     *   attribués à l'enseignant du cours (montant ou price, sinon prorata du montant du carnet payé).
     *
     * @param  int  $year  Année (ex: 2025)
     * @param  int  $month  Mois (1-12)
     * @param  int|null  $clubId  ID du club pour filtrer (optionnel, null = tous les clubs)
     * @return array Rapport structuré par enseignant avec DCL/NDCL
     */
    public function generatePayrollReport(int $year, int $month, ?int $clubId = null): array
    {
        return $this->computePayrollReport($year, $month, $clubId)['report'];
    }

    /**
     * Rapport agrégé + lignes détaillées pour export PDF / audit (jour, durée si connue, base, montant).
     *
     * @return array{report: array, lines_by_teacher: array<int, array<int, array<string, mixed>>>}
     */
    public function generatePayrollReportWithLines(int $year, int $month, ?int $clubId = null): array
    {
        return $this->computePayrollReport($year, $month, $clubId);
    }

    /**
     * @return array{report: array, lines_by_teacher: array<int, array<int, array<string, mixed>>>}
     */
    private function computePayrollReport(int $year, int $month, ?int $clubId): array
    {
        // Définir la période de recherche
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Structure de données pour agréger les résultats par enseignant
        $report = [];
        $linesByTeacher = [];
        /** Cumul précis en minutes (sans arrondi par séance) avant conversion VH. */
        $lessonMinutesByTeacher = [];

        // ===== PARTIE 1 : ABONNEMENTS =====
        // Collecter tous les abonnements payés durant la période
        $subscriptionQuery = SubscriptionInstance::whereBetween('date_paiement', [$startDate, $endDate])
            ->whereNotNull('montant')
            ->where('montant', '>', 0)
            ->whereIn('status', ['active', 'completed'])
            ->with(['teacher.user', 'subscription']);

        // Filtrer par club si spécifié
        if ($clubId !== null) {
            $subscriptionQuery->whereHas('subscription', function ($q) use ($clubId) {
                $q->where('club_id', $clubId);
            });
        }

        // Une fois au moins un cours consommé (pivot subscription_lessons), tout le montant passe par
        // les lignes cours ; sinon une double compta apparaît (paiement + séances au prorata).
        $subscriptionQuery->whereDoesntHave('lessons');

        $subscriptionInstances = $subscriptionQuery->get();

        // Boucle & Ventilation : Itérer sur chaque abonnement
        foreach ($subscriptionInstances as $instance) {
            // Déterminer l'enseignant (priorité : teacher_id direct, sinon via les cours)
            $teacherId = $this->determineTeacherId($instance);

            if (! $teacherId) {
                \Log::warning("Abonnement {$instance->id} sans enseignant assigné, ignoré dans le rapport de paie");

                continue;
            }

            // Initialiser l'entrée pour cet enseignant si nécessaire
            $this->initializeTeacherEntry($report, $teacherId);

            // Calculer la commission selon le type
            try {
                $commission = $this->calculateCommission($instance);

                // Ajouter au bon "panier" selon le type (DCL ou NDCL)
                // RÈGLE : est_legacy === false → DCL, sinon (true ou null) → NDCL
                if ($instance->est_legacy === false) {
                    $report[$teacherId]['total_commissions_dcl'] += $commission;
                } else {
                    $report[$teacherId]['total_commissions_ndcl'] += $commission;
                }

                // Arrondir les totaux
                $report[$teacherId]['total_commissions_dcl'] = round(
                    $report[$teacherId]['total_commissions_dcl'],
                    2
                );
                $report[$teacherId]['total_commissions_ndcl'] = round(
                    $report[$teacherId]['total_commissions_ndcl'],
                    2
                );

                $paymentDate = $instance->date_paiement ? Carbon::parse($instance->date_paiement) : null;
                $segment = $instance->est_legacy === false ? 'DCL' : 'NDCL';
                $templateName = $instance->subscription?->name ?? 'Abonnement';
                if (! isset($linesByTeacher[$teacherId])) {
                    $linesByTeacher[$teacherId] = [];
                }
                $linesByTeacher[$teacherId][] = [
                    'kind' => 'subscription_prepayment',
                    'line_type_label' => 'Prépaiement carnet',
                    'sort_date' => $paymentDate ? $paymentDate->format('Y-m-d') : '0000-00-00',
                    'sort_time' => '00:00:00',
                    'date_display' => $paymentDate ? $paymentDate->format('d/m/Y') : '—',
                    'datetime_display' => $paymentDate ? $paymentDate->format('d/m/Y') : '—',
                    'duree_minutes' => null,
                    'hours' => null,
                    'hours_display' => '—',
                    'segment' => $segment,
                    'label' => 'Paiement abonnement (sans séance liée) — '.$templateName,
                    'reference' => 'SI-'.$instance->id,
                    'basis' => 'paiement_carnet',
                    'basis_label' => 'Montant paiement carnet',
                    'student_display' => null,
                    'amount' => round($commission, 2),
                    'notification' => 'Pas de séance : ligne au montant encaissé (tarif horaire hors périmètre).',
                ];
            } catch (\InvalidArgumentException $e) {
                \Log::error("Erreur lors du calcul de commission pour l'abonnement {$instance->id}: ".$e->getMessage());

                continue;
            }
        }

        // ===== PARTIE 2 : COURS (tous les cours retenus, y compris consommés sur un carnet abonnement) =====
        // Utiliser montant OU price, ou à défaut valeur au prorata du montant de l’instance payée /
        // nombre de séances du carnet pour les lignes reliées à un abonnement.
        // RÈGLE :
        // - Si date_paiement est définie et dans la période : cours déjà payé dans cette période
        // - Si date_paiement est null et start_time dans la période : cours à payer dans cette période
        // Note : date_paiement sert à marquer un cours comme payé (étape future du développement)
        $lessonQuery = Lesson::whereNotNull('teacher_id')
            ->whereIn('status', ['confirmed', 'completed'])
            ->where(function ($query) use ($startDate, $endDate) {
                // Soit date_paiement est dans la période (cours payé ou reporté), soit start_time est dans la période (cours à payer)
                // Exclure les cours marqués comme non payés (notes contient [NON PAYÉ])
                $query->where(function ($q) use ($startDate, $endDate) {
                    // Cas 1 : date_paiement est définie et dans la période (cours déjà payé ou reporté dans cette période)
                    $q->whereNotNull('date_paiement')
                        ->whereBetween('date_paiement', [$startDate, $endDate])
                        ->where(function ($subQ) {
                            // Exclure les cours marqués comme non payés
                            $subQ->whereNull('notes')
                                ->orWhere('notes', 'not like', '%[NON PAYÉ]%');
                        });
                })->orWhere(function ($q) use ($startDate, $endDate) {
                    // Cas 2 : date_paiement est null et start_time dans la période (cours à payer dans cette période)
                    // Exclure les cours marqués comme non payés
                    $q->whereNull('date_paiement')
                        ->whereBetween('start_time', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                        ->where(function ($subQ) {
                            $subQ->whereNull('notes')
                                ->orWhere('notes', 'not like', '%[NON PAYÉ]%');
                        });
                });
            })
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('montant')->where('montant', '>', 0);
                })
                    ->orWhere(function ($q) {
                        $q->whereNull('montant')->orWhere('montant', '<=', 0);
                        $q->whereNotNull('price')->where('price', '>', 0);
                    })
                    ->orWhereHas('subscriptionInstances', function ($q) {
                        $q->whereNotNull('montant')->where('montant', '>', 0);
                    });
            })
            ->with([
                'teacher.user',
                'teacher.clubs',
                'club',
                'courseType',
                'student.user',
                'students.user',
                'subscriptionInstances.subscription.template',
            ]);

        // Filtrer par club si spécifié
        if ($clubId !== null) {
            $lessonQuery->where('club_id', $clubId);
        }

        $lessons = $lessonQuery->get();

        // Boucle sur chaque cours retenu
        foreach ($lessons as $lesson) {
            $teacherId = $lesson->teacher_id;

            if (! $teacherId) {
                \Log::warning("Cours {$lesson->id} sans enseignant assigné, ignoré dans le rapport de paie");

                continue;
            }

            // Initialiser l'entrée pour cet enseignant si nécessaire
            $this->initializeTeacherEntry($report, $teacherId);

            // Calculer la commission selon le type (DCL ou NDCL)
            try {
                $payroll = $this->resolveLessonPayrollAmountWithBasis($lesson);
                $amount = $payroll['amount'];
                $basis = $payroll['basis'];
                $basisLabel = $payroll['basis_label'];
                $notification = $payroll['notification'];

                if ($amount <= 0) {
                    \Log::warning("Cours {$lesson->id} sans base de commission (montant, price ou prorata abonnement), ignoré");

                    continue;
                }

                $commission = $amount;
                // RÈGLE : est_legacy sur le cours prioritaire pour les lignes cours ; sinon hériter de l’instance d’abonnement
                // est_legacy === false → DCL, sinon (true ou null) → NDCL
                if ($this->lessonCountsAsDcl($lesson)) {
                    $report[$teacherId]['total_commissions_dcl'] += $commission * self::DCL_COMMISSION_RATE;
                } else {
                    $report[$teacherId]['total_commissions_ndcl'] += $commission * self::NDCL_COMMISSION_RATE;
                }

                // Arrondir les totaux
                $report[$teacherId]['total_commissions_dcl'] = round(
                    $report[$teacherId]['total_commissions_dcl'],
                    2
                );
                $report[$teacherId]['total_commissions_ndcl'] = round(
                    $report[$teacherId]['total_commissions_ndcl'],
                    2
                );

                $durationMinutes = $this->lessonScheduledDurationMinutes($lesson);
                if ($durationMinutes !== null && $durationMinutes > 0) {
                    $lessonMinutesByTeacher[$teacherId] = ($lessonMinutesByTeacher[$teacherId] ?? 0) + $durationMinutes;
                }

                $segment = $this->lessonCountsAsDcl($lesson) ? 'DCL' : 'NDCL';
                $studentDisplay = $this->formatPayrollLessonStudentsLabel($lesson);
                $labelParts = ['Cours #'.$lesson->id];
                if ($lesson->club?->name) {
                    $labelParts[] = $lesson->club->name;
                }
                if ($lesson->courseType?->name) {
                    $labelParts[] = $lesson->courseType->name;
                }
                if ($studentDisplay !== null) {
                    $labelParts[] = 'Élève : '.$studentDisplay;
                }
                if (! isset($linesByTeacher[$teacherId])) {
                    $linesByTeacher[$teacherId] = [];
                }
                $start = $lesson->start_time;
                $vhLine = $durationMinutes !== null ? $durationMinutes / 60.0 : null;
                $linesByTeacher[$teacherId][] = [
                    'kind' => 'lesson',
                    'line_type_label' => 'Cours',
                    'sort_date' => $start ? $start->format('Y-m-d') : '0000-00-00',
                    'sort_time' => $start ? $start->format('H:i:s') : '00:00:00',
                    'date_display' => $start ? $start->format('d/m/Y') : '—',
                    'datetime_display' => $start ? $start->format('d/m/Y H:i') : '—',
                    'duree_minutes' => $durationMinutes,
                    'hours' => $vhLine,
                    'hours_display' => $this->formatLessonDurationLineDisplay($durationMinutes),
                    'segment' => $segment,
                    'label' => implode(' — ', $labelParts),
                    'reference' => 'L-'.$lesson->id,
                    'basis' => $basis,
                    'basis_label' => $basisLabel,
                    'student_display' => $studentDisplay,
                    'amount' => round($commission, 2),
                    'notification' => $notification,
                ];
            } catch (\Exception $e) {
                \Log::error("Erreur lors du calcul de commission pour le cours {$lesson->id}: ".$e->getMessage());

                continue;
            }
        }

        // VH = somme minutes ÷ 60 (évite l’erreur 6×0,33 h ≠ 2 h pour des séances de 20 min).
        foreach ($lessonMinutesByTeacher as $tid => $totalMin) {
            if (! isset($report[$tid])) {
                continue;
            }
            $report[$tid]['total_duree_cours_minutes'] = (int) $totalMin;
            $report[$tid]['total_heures_cours'] = $totalMin > 0 ? round($totalMin / 60, 2) : 0.0;
        }

        // Calculer le total à payer pour chaque enseignant
        foreach ($report as $teacherId => &$data) {
            $data['total_a_payer'] = round(
                $data['total_commissions_dcl'] + $data['total_commissions_ndcl'],
                2
            );
            $data['total_heures_cours'] = round($data['total_heures_cours'] ?? 0.0, 2);
            $data['total_duree_cours_minutes'] = (int) ($data['total_duree_cours_minutes'] ?? 0);
        }
        unset($data);

        foreach ($linesByTeacher as $tid => $rows) {
            usort($linesByTeacher[$tid], function (array $a, array $b): int {
                $c = strcmp($a['sort_date'], $b['sort_date']);
                if ($c !== 0) {
                    return $c;
                }

                return strcmp($a['sort_time'], $b['sort_time']);
            });
        }

        return [
            'report' => $report,
            'lines_by_teacher' => $linesByTeacher,
        ];
    }

    /**
     * Initialiser l'entrée pour un enseignant dans le rapport
     *
     * @param  array  $report  Référence au tableau de rapport
     * @param  int  $teacherId  ID de l'enseignant
     */
    private function initializeTeacherEntry(array &$report, int $teacherId): void
    {
        if (! isset($report[$teacherId])) {
            $teacher = \App\Models\Teacher::with('user')->find($teacherId);
            $report[$teacherId] = [
                'enseignant_id' => $teacherId,
                'nom_enseignant' => $teacher && $teacher->user
                    ? trim(($teacher->user->first_name ?? '').' '.($teacher->user->last_name ?? ''))
                    : "Enseignant #{$teacherId}",
                'total_commissions_dcl' => 0.00,   // DCL = Déclaré (Type 1)
                'total_commissions_ndcl' => 0.00,  // NDCL = Non Déclaré (Type 2)
                'total_a_payer' => 0.00,
                // Sum of lesson durations (start→end) for lines counted in report; excludes prepayment carnet sans séance.
                'total_heures_cours' => 0.00,
                'total_duree_cours_minutes' => 0,
            ];
        }
    }

    /**
     * Déterminer l'ID de l'enseignant pour un abonnement
     *
     * Priorité :
     * 1. teacher_id direct sur l'abonnement
     * 2. Enseignant du premier cours lié à l'abonnement
     *
     * @param  SubscriptionInstance  $instance  Instance d'abonnement
     * @return int|null ID de l'enseignant ou null si aucun trouvé
     */
    private function determineTeacherId(SubscriptionInstance $instance): ?int
    {
        // Priorité 1 : teacher_id direct sur l'abonnement
        if ($instance->teacher_id) {
            return $instance->teacher_id;
        }

        // Priorité 2 : Enseignant du premier cours lié
        $firstLesson = $instance->lessons()
            ->whereNotNull('teacher_id')
            ->orderBy('start_time', 'asc')
            ->first();

        return $firstLesson ? $firstLesson->teacher_id : null;
    }

    /**
     * Base monétaire pour une ligne cours (montant, prix catalogue, ou valeur au prorata du carnet abonnement).
     *
     * @return array{amount: float, basis: string}
     */
    private function resolveLessonCommissionBaseAmountWithBasis(Lesson $lesson): array
    {
        if ($lesson->montant !== null && (float) $lesson->montant > 0.0) {
            return ['amount' => round((float) $lesson->montant, 2), 'basis' => 'montant'];
        }

        if ($lesson->price !== null && (float) $lesson->price > 0.0) {
            return ['amount' => round((float) $lesson->price, 2), 'basis' => 'price'];
        }

        if (! $lesson->relationLoaded('subscriptionInstances')) {
            $lesson->load('subscriptionInstances.subscription.template');
        }

        foreach ($lesson->subscriptionInstances as $instance) {
            $prorated = $this->calculateSubscriptionLessonProRataAmount($instance);
            if ($prorated > 0.0) {
                return ['amount' => $prorated, 'basis' => 'prorata_abonnement'];
            }
        }

        return ['amount' => 0.0, 'basis' => 'none'];
    }

    /**
     * Base monétaire pour une ligne cours (compatibilité).
     */
    private function resolveLessonCommissionBaseAmount(Lesson $lesson): float
    {
        return $this->resolveLessonCommissionBaseAmountWithBasis($lesson)['amount'];
    }

    /**
     * Durée prévue séance en minutes discrètes (début → fin), aucun arrondi intermédiaire.
     * Le VH agrégé est toujours (somme minutes) / 60 pour éviter le biais × séances courtes (ex. 20 min).
     */
    private function lessonScheduledDurationMinutes(Lesson $lesson): ?int
    {
        if (! $lesson->start_time || ! $lesson->end_time) {
            return null;
        }

        $minutes = (int) $lesson->start_time->diffInMinutes($lesson->end_time);

        return $minutes > 0 ? $minutes : null;
    }

    private function formatLessonDurationLineDisplay(?int $minutes): string
    {
        if ($minutes === null || $minutes <= 0) {
            return '—';
        }

        return sprintf(
            '%d min (%s h)',
            $minutes,
            number_format(round($minutes / 60.0, 12), 2, ',', ' ')
        );
    }

    /**
     * Libellé élève(s) pour lignes paie / PDF / UI (évite N+1 : relations déjà eager-loadées).
     */
    private function formatPayrollLessonStudentsLabel(Lesson $lesson): ?string
    {
        $lesson->loadMissing(['student.user', 'students.user']);

        $names = [];
        if ($lesson->relationLoaded('students') && $lesson->students->isNotEmpty()) {
            foreach ($lesson->students as $student) {
                $user = $student->user;
                if (! $user) {
                    continue;
                }
                $n = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
                if ($n === '') {
                    $n = $user->name ?? '';
                }
                if ($n !== '') {
                    $names[] = $n;
                }
            }
            $names = array_values(array_unique($names));
        }

        if ($names === [] && $lesson->student?->user) {
            $user = $lesson->student->user;
            $n = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            if ($n === '') {
                $n = $user->name ?? '';
            }
            if ($n !== '') {
                $names[] = $n;
            }
        }

        if ($names === []) {
            return null;
        }

        if (count($names) === 1) {
            return $names[0];
        }

        return $names[0].' +'.(count($names) - 1);
    }

    /**
     * Tarif horaire pour la paie séance : pivot club × enseignant (si défini et > 0), sinon champ profil Teacher.
     */
    private function resolveEffectiveHourlyRateForLesson(Lesson $lesson): ?float
    {
        $lesson->loadMissing(['teacher.clubs']);

        $teacher = $lesson->teacher;
        if (! $teacher) {
            return null;
        }

        if ($lesson->club_id) {
            $clubTeacher = $teacher->clubs->firstWhere('id', (int) $lesson->club_id);
            $pivotRate = $clubTeacher?->pivot?->hourly_rate ?? null;
            if ($pivotRate !== null && (float) $pivotRate > 0.0) {
                return round((float) $pivotRate, 2);
            }
        }

        $profileRate = $teacher->hourly_rate ?? null;

        return $profileRate !== null && (float) $profileRate > 0.0 ? round((float) $profileRate, 2) : null;
    }

    /**
     * Montant à payer pour un cours : priorité durée × tarif horaire effectif lorsque disponible.
     *
     * @return array{amount: float, basis: string, basis_label: string, notification: ?string}
     */
    private function resolveLessonPayrollAmountWithBasis(Lesson $lesson): array
    {
        $hourlyRate = $this->resolveEffectiveHourlyRateForLesson($lesson);
        $minutes = $this->lessonScheduledDurationMinutes($lesson);

        if ($hourlyRate !== null && $hourlyRate > 0.0 && $minutes !== null && $minutes > 0) {
            // €/h × (minutes réelles ÷ 60) — pas d’arrondi « durée » avant multiplication
            $amount = round($hourlyRate * ($minutes / 60.0), 2);

            return [
                'amount' => $amount,
                'basis' => 'hourly_profile',
                'basis_label' => sprintf(
                    'Tarif horaire (%.2f €/h × %d min)',
                    $hourlyRate,
                    $minutes,
                ),
                'notification' => null,
            ];
        }

        $fallback = $this->resolveLessonCommissionBaseAmountWithBasis($lesson);
        $notification = null;
        if ($hourlyRate === null || $hourlyRate <= 0.0) {
            $notification = 'Aucun tarif horaire valide (rattachement club puis profil enseignant). Montant / prix cours ou prorata carnet utilisé.';
        } else {
            $notification = 'Tarif horaire enregistré mais durée séance introuvable (vérifiez début/fin du cours sur la réservation). Montant / prix ou prorata utilisé.';
        }

        return [
            'amount' => $fallback['amount'],
            'basis' => $fallback['basis'],
            'basis_label' => match ($fallback['basis']) {
                'montant' => 'Montant saisi sur le cours',
                'price' => 'Prix catalogue',
                'prorata_abonnement' => 'Prorata séance (carnet / nb séances)',
                default => $fallback['basis'],
            },
            'notification' => $notification,
        ];
    }

    /**
     * montant du carnet / nombre de séances payantes (template), minimum 1 pour éviter division par zéro.
     */
    private function calculateSubscriptionLessonProRataAmount(SubscriptionInstance $instance): float
    {
        $packAmount = $instance->montant !== null ? (float) $instance->montant : 0.0;
        if ($packAmount <= 0.0) {
            return 0.0;
        }

        $instance->loadMissing('subscription.template');

        $subscription = $instance->subscription;
        if (! $subscription) {
            return round($packAmount, 2);
        }

        $slots = (int) ($subscription->total_available_lessons ?? 0);
        if ($slots < 1) {
            $slots = 1;
        }

        return round($packAmount / $slots, 2);
    }

    /**
     * DCL si le cours est explicitement déclaré ; sinon hériter du type de l’instance d’abonnement liée.
     */
    private function lessonCountsAsDcl(Lesson $lesson): bool
    {
        if ($lesson->est_legacy === false) {
            return true;
        }
        if ($lesson->est_legacy === true) {
            return false;
        }

        if (! $lesson->relationLoaded('subscriptionInstances')) {
            $lesson->load('subscriptionInstances');
        }

        $instance = $lesson->subscriptionInstances->first();
        if ($instance !== null) {
            return $instance->est_legacy === false;
        }

        return false;
    }

    /**
     * Obtenir les taux de commission (pour affichage/debug)
     *
     * @return array Taux de commission par type (DCL/NDCL)
     */
    public function getCommissionRates(): array
    {
        return [
            'dcl_rate' => self::DCL_COMMISSION_RATE,   // DCL = Déclaré
            'ndcl_rate' => self::NDCL_COMMISSION_RATE, // NDCL = Non Déclaré
        ];
    }

    /**
     * Obtenir les périodes disponibles pour les rapports
     *
     * @param  int|null  $clubId  ID du club pour filtrer (optionnel)
     * @return array Liste des périodes avec des données
     */
    public function getAvailableReportPeriods(?int $clubId = null): array
    {
        $periods = [];

        // Chercher dans les abonnements
        $subscriptionQuery = SubscriptionInstance::whereNotNull('date_paiement')
            ->whereNotNull('montant')
            ->where('montant', '>', 0);

        if ($clubId !== null) {
            $subscriptionQuery->whereHas('subscription', function ($q) use ($clubId) {
                $q->where('club_id', $clubId);
            });
        }

        $subscriptionDates = $subscriptionQuery->selectRaw('YEAR(date_paiement) as year, MONTH(date_paiement) as month')
            ->distinct()
            ->get();

        // Chercher dans les cours individuels
        // RÈGLE : Inclure les cours avec date_paiement OU les cours sans date_paiement (basés sur start_time)
        $baseLessonQuery = function ($clubId) {
            $query = \App\Models\Lesson::whereDoesntHave('subscriptionInstances')
                ->whereIn('status', ['confirmed', 'completed'])
                ->where(function ($q) {
                    // Soit montant est défini et > 0, soit price est défini et > 0
                    $q->where(function ($subQ) {
                        $subQ->whereNotNull('montant')->where('montant', '>', 0);
                    })->orWhere(function ($subQ) {
                        $subQ->whereNull('montant')->orWhere('montant', '<=', 0);
                        $subQ->whereNotNull('price')->where('price', '>', 0);
                    });
                });

            if ($clubId !== null) {
                $query->where('club_id', $clubId);
            }

            return $query;
        };

        // Récupérer les dates depuis date_paiement
        $lessonDatesWithPayment = $baseLessonQuery($clubId)
            ->whereNotNull('date_paiement')
            ->selectRaw('YEAR(date_paiement) as year, MONTH(date_paiement) as month')
            ->distinct()
            ->get();

        // Récupérer les dates depuis start_time (pour les cours sans date_paiement)
        $lessonDatesWithoutPayment = $baseLessonQuery($clubId)
            ->whereNull('date_paiement')
            ->selectRaw('YEAR(start_time) as year, MONTH(start_time) as month')
            ->distinct()
            ->get();

        // Combiner les dates de cours (avec et sans date_paiement)
        $lessonDates = $lessonDatesWithPayment->concat($lessonDatesWithoutPayment);

        // Combiner avec les dates d'abonnements et dédupliquer
        $allDates = $subscriptionDates->concat($lessonDates)->unique(function ($item) {
            return $item->year.'-'.$item->month;
        });

        foreach ($allDates as $date) {
            $periods[] = [
                'year' => (int) $date->year,
                'month' => (int) $date->month,
            ];
        }

        // Trier par date décroissante
        usort($periods, function ($a, $b) {
            if ($a['year'] !== $b['year']) {
                return $b['year'] - $a['year'];
            }

            return $b['month'] - $a['month'];
        });

        return $periods;
    }
}
