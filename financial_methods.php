<?php

// Méthodes privées pour FinancialDashboardController

private function getTotalRevenue($club, $startDate, $endDate)
{
    // Revenus des cours
    $lessonRevenue = Payment::whereHas('lesson', function($query) use ($club) {
        $query->whereHas('teacher', function($q) use ($club) {
            $q->whereIn('user_id', $club->teachers()->pluck('users.id'));
        });
    })->where('status', 'succeeded')
    ->whereBetween('processed_at', [$startDate, $endDate])
    ->sum('amount');

    // Revenus des ventes
    $salesRevenue = Transaction::where('club_id', $club->id)
        ->where('type', 'sale')
        ->whereBetween('processed_at', [$startDate, $endDate])
        ->sum('amount');

    return $lessonRevenue + $salesRevenue;
}

private function getRevenueBySource($club, $startDate, $endDate)
{
    // Revenus des cours
    $lessonRevenue = Payment::whereHas('lesson', function($query) use ($club) {
        $query->whereHas('teacher', function($q) use ($club) {
            $q->whereIn('user_id', $club->teachers()->pluck('users.id'));
        });
    })->where('status', 'succeeded')
    ->whereBetween('processed_at', [$startDate, $endDate])
    ->sum('amount');

    // Revenus des ventes
    $salesRevenue = Transaction::where('club_id', $club->id)
        ->where('type', 'sale')
        ->whereBetween('processed_at', [$startDate, $endDate])
        ->sum('amount');

    return [
        ['name' => 'Cours', 'revenue' => round($lessonRevenue, 2), 'percentage' => $lessonRevenue + $salesRevenue > 0 ? round(($lessonRevenue / ($lessonRevenue + $salesRevenue)) * 100, 1) : 0],
        ['name' => 'Ventes', 'revenue' => round($salesRevenue, 2), 'percentage' => $lessonRevenue + $salesRevenue > 0 ? round(($salesRevenue / ($lessonRevenue + $salesRevenue)) * 100, 1) : 0]
    ];
}

private function getRevenueByDiscipline($club, $startDate, $endDate)
{
    return Discipline::where('activity_type_id', $club->activity_type_id)
        ->with(['activityType'])
        ->get()
        ->map(function($discipline) use ($club, $startDate, $endDate) {
            $revenue = Lesson::whereHas('teacher', function($query) use ($club) {
                $query->whereIn('user_id', $club->teachers()->pluck('users.id'));
            })
            ->where('course_type_id', $discipline->id)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('price');

            return [
                'discipline' => $discipline->name,
                'activity_type' => $discipline->activityType->name,
                'revenue' => round($revenue, 2),
                'lessons_count' => Lesson::whereHas('teacher', function($query) use ($club) {
                    $query->whereIn('user_id', $club->teachers()->pluck('users.id'));
                })
                ->where('course_type_id', $discipline->id)
                ->whereBetween('start_time', [$startDate, $endDate])
                ->where('status', 'completed')
                ->count()
            ];
        })
        ->sortByDesc('revenue')
        ->values();
}

private function getRevenueByPeriod($club, $months)
{
    $revenueByPeriod = [];
    
    for ($i = $months - 1; $i >= 0; $i--) {
        $startOfMonth = Carbon::now()->subMonths($i)->startOfMonth();
        $endOfMonth = Carbon::now()->subMonths($i)->endOfMonth();
        
        $revenue = $this->getTotalRevenue($club, $startOfMonth, $endOfMonth);
        
        $revenueByPeriod[] = [
            'month' => $startOfMonth->format('Y-m'),
            'month_name' => $startOfMonth->format('F Y'),
            'revenue' => round($revenue, 2)
        ];
    }
    
    return $revenueByPeriod;
}

private function getSalesStats($club, $startDate, $endDate)
{
    $transactions = Transaction::where('club_id', $club->id)
        ->where('type', 'sale')
        ->whereBetween('processed_at', [$startDate, $endDate]);

    return [
        'total_transactions' => $transactions->count(),
        'average_transaction' => $transactions->count() > 0 ? round($transactions->avg('amount'), 2) : 0,
        'total_items_sold' => TransactionItem::join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.club_id', $club->id)
            ->where('transactions.type', 'sale')
            ->whereBetween('transactions.processed_at', [$startDate, $endDate])
            ->sum('transaction_items.quantity')
    ];
}

private function calculateGrowth($club, $currentRevenue)
{
    $lastMonth = Carbon::now()->subMonth();
    $lastMonthRevenue = $this->getTotalRevenue($club, $lastMonth->startOfMonth(), $lastMonth->endOfMonth());
    
    if ($lastMonthRevenue == 0) {
        return $currentRevenue > 0 ? 100 : 0;
    }
    
    return round((($currentRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1);
}
