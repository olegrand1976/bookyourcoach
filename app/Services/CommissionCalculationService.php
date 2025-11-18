<?php

namespace App\Services;

use App\Models\SubscriptionInstance;
use Carbon\Carbon;

/**
 * Service de calcul des commissions enseignants
 * 
 * Ce service gère le calcul des commissions pour deux types d'abonnements :
 * - DCL (est_legacy = false) : Déclaré - Modèle standard et pérenne
 * - NDCL (est_legacy = true) : Non Déclaré - Ancien modèle legacy
 * 
 * Les règles de calcul sont différentes pour chaque type et peuvent être facilement
 * modifiées ou supprimées (pour NDCL) sans affecter le reste du code.
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
     * @param float $montant Montant de base de l'abonnement
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
     * @param float $montant Montant de base de l'abonnement
     * @return float Montant de la commission
     */
    private function calculateNdclCommission(float $montant): float
    {
        return round($montant * self::NDCL_COMMISSION_RATE, 2);
    }

    /**
     * Calculer la commission pour un abonnement selon son type
     * 
     * @param SubscriptionInstance $subscriptionInstance Instance d'abonnement
     * @return float Montant de la commission calculée
     * @throws \InvalidArgumentException Si le montant est manquant ou invalide
     */
    public function calculateCommission(SubscriptionInstance $subscriptionInstance): float
    {
        // Vérifier que le montant est disponible
        if (!$subscriptionInstance->montant || $subscriptionInstance->montant <= 0) {
            throw new \InvalidArgumentException(
                "Le montant de l'abonnement {$subscriptionInstance->id} est manquant ou invalide."
            );
        }

        $montant = (float) $subscriptionInstance->montant;

        // Appliquer la règle selon le type d'abonnement
        if ($subscriptionInstance->est_legacy) {
            // NDCL (Non Déclaré - Legacy)
            return $this->calculateNdclCommission($montant);
        } else {
            // DCL (Déclaré - Standard)
            return $this->calculateDclCommission($montant);
        }
    }

    /**
     * Générer un rapport de paie pour une période donnée
     * 
     * Inclut :
     * - Les abonnements (SubscriptionInstance) payés durant la période
     * - Les cours individuels (Lesson) payés durant la période (non liés à un abonnement)
     * 
     * @param int $year Année (ex: 2025)
     * @param int $month Mois (1-12)
     * @param int|null $clubId ID du club pour filtrer (optionnel, null = tous les clubs)
     * @return array Rapport structuré par enseignant avec DCL/NDCL
     */
    public function generatePayrollReport(int $year, int $month, ?int $clubId = null): array
    {
        // Définir la période de recherche
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Structure de données pour agréger les résultats par enseignant
        $report = [];

        // ===== PARTIE 1 : ABONNEMENTS =====
        // Collecter tous les abonnements payés durant la période
        $subscriptionQuery = SubscriptionInstance::whereBetween('date_paiement', [$startDate, $endDate])
            ->whereNotNull('montant')
            ->where('montant', '>', 0)
            ->whereIn('status', ['active', 'completed'])
            ->with(['teacher.user', 'subscription']);

        // Filtrer par club si spécifié
        if ($clubId !== null) {
            $subscriptionQuery->whereHas('subscription', function($q) use ($clubId) {
                $q->where('club_id', $clubId);
            });
        }

        $subscriptionInstances = $subscriptionQuery->get();

        // Boucle & Ventilation : Itérer sur chaque abonnement
        foreach ($subscriptionInstances as $instance) {
            // Déterminer l'enseignant (priorité : teacher_id direct, sinon via les cours)
            $teacherId = $this->determineTeacherId($instance);

            if (!$teacherId) {
                \Log::warning("Abonnement {$instance->id} sans enseignant assigné, ignoré dans le rapport de paie");
                continue;
            }

            // Initialiser l'entrée pour cet enseignant si nécessaire
            $this->initializeTeacherEntry($report, $teacherId);

            // Calculer la commission selon le type
            try {
                $commission = $this->calculateCommission($instance);

                // Ajouter au bon "panier" selon le type (DCL ou NDCL)
                if ($instance->est_legacy) {
                    $report[$teacherId]['total_commissions_ndcl'] += $commission;
                } else {
                    $report[$teacherId]['total_commissions_dcl'] += $commission;
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
            } catch (\InvalidArgumentException $e) {
                \Log::error("Erreur lors du calcul de commission pour l'abonnement {$instance->id}: " . $e->getMessage());
                continue;
            }
        }

        // ===== PARTIE 2 : COURS INDIVIDUELS =====
        // Collecter tous les cours individuels payés durant la période (non liés à un abonnement)
        // Un cours individuel est un cours qui n'a pas d'entrée dans la table subscription_lessons
        // Utiliser montant OU price (si montant est null ou 0)
        $lessonQuery = \App\Models\Lesson::whereBetween('date_paiement', [$startDate, $endDate])
            ->whereNotNull('date_paiement')
            ->whereNotNull('teacher_id')
            ->whereIn('status', ['confirmed', 'completed']) // Seulement les cours confirmés ou complétés
            ->whereDoesntHave('subscriptionInstances') // Exclure les cours liés à un abonnement via subscription_lessons
            ->where(function($query) {
                // Soit montant est défini et > 0, soit price est défini et > 0
                $query->where(function($q) {
                    $q->whereNotNull('montant')->where('montant', '>', 0);
                })->orWhere(function($q) {
                    $q->whereNull('montant')->orWhere('montant', '<=', 0);
                    $q->whereNotNull('price')->where('price', '>', 0);
                });
            })
            ->with(['teacher.user', 'club']);

        // Filtrer par club si spécifié
        if ($clubId !== null) {
            $lessonQuery->where('club_id', $clubId);
        }

        $lessons = $lessonQuery->get();

        // Boucle & Ventilation : Itérer sur chaque cours individuel
        foreach ($lessons as $lesson) {
            $teacherId = $lesson->teacher_id;

            if (!$teacherId) {
                \Log::warning("Cours {$lesson->id} sans enseignant assigné, ignoré dans le rapport de paie");
                continue;
            }

            // Initialiser l'entrée pour cet enseignant si nécessaire
            $this->initializeTeacherEntry($report, $teacherId);

            // Calculer la commission selon le type (DCL ou NDCL)
            try {
                // Utiliser montant si disponible, sinon price
                $amount = $lesson->montant ?? $lesson->price ?? 0;
                
                if ($amount <= 0) {
                    \Log::warning("Cours {$lesson->id} sans montant, ignoré");
                    continue;
                }

                // Calculer la commission selon le type
                if ($lesson->est_legacy) {
                    $commission = $amount * self::NDCL_COMMISSION_RATE;
                    $report[$teacherId]['total_commissions_ndcl'] += $commission;
                } else {
                    $commission = $amount * self::DCL_COMMISSION_RATE;
                    $report[$teacherId]['total_commissions_dcl'] += $commission;
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
            } catch (\Exception $e) {
                \Log::error("Erreur lors du calcul de commission pour le cours {$lesson->id}: " . $e->getMessage());
                continue;
            }
        }

        // Calculer le total à payer pour chaque enseignant
        foreach ($report as $teacherId => &$data) {
            $data['total_a_payer'] = round(
                $data['total_commissions_dcl'] + $data['total_commissions_ndcl'],
                2
            );
        }

        return $report;
    }

    /**
     * Initialiser l'entrée pour un enseignant dans le rapport
     * 
     * @param array $report Référence au tableau de rapport
     * @param int $teacherId ID de l'enseignant
     */
    private function initializeTeacherEntry(array &$report, int $teacherId): void
    {
        if (!isset($report[$teacherId])) {
            $teacher = \App\Models\Teacher::with('user')->find($teacherId);
            $report[$teacherId] = [
                'enseignant_id' => $teacherId,
                'nom_enseignant' => $teacher && $teacher->user 
                    ? trim(($teacher->user->first_name ?? '') . ' ' . ($teacher->user->last_name ?? ''))
                    : "Enseignant #{$teacherId}",
                'total_commissions_dcl' => 0.00,   // DCL = Déclaré (Type 1)
                'total_commissions_ndcl' => 0.00,  // NDCL = Non Déclaré (Type 2)
                'total_a_payer' => 0.00,
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
     * @param SubscriptionInstance $instance Instance d'abonnement
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
     * @param int|null $clubId ID du club pour filtrer (optionnel)
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
            $subscriptionQuery->whereHas('subscription', function($q) use ($clubId) {
                $q->where('club_id', $clubId);
            });
        }

        $subscriptionDates = $subscriptionQuery->selectRaw('YEAR(date_paiement) as year, MONTH(date_paiement) as month')
            ->distinct()
            ->get();

        // Chercher dans les cours individuels
        $lessonQuery = \App\Models\Lesson::whereNotNull('date_paiement')
            ->whereNotNull('montant')
            ->where('montant', '>', 0)
            ->whereDoesntHave('subscriptionInstances');

        if ($clubId !== null) {
            $lessonQuery->where('club_id', $clubId);
        }

        $lessonDates = $lessonQuery->selectRaw('YEAR(date_paiement) as year, MONTH(date_paiement) as month')
            ->distinct()
            ->get();

        // Combiner et dédupliquer
        $allDates = $subscriptionDates->merge($lessonDates)->unique(function ($item) {
            return $item->year . '-' . $item->month;
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

