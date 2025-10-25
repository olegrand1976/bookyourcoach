<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Discipline;
use App\Models\Lesson;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FinancialDashboardController extends Controller
{
    /**
     * Vue d'ensemble financière du club
     */
    public function getOverview(Request $request)
    {
        $user = $request->user();
        $club = $user->getFirstClub();
        
        if (!$club) {
            return response()->json(['message' => 'Club non trouvé'], 404);
        }

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfYear = $now->copy()->startOfYear();
        $endOfYear = $now->copy()->endOfYear();

        // CA Total (cours + ventes)
        $totalRevenue = $this->getTotalRevenue($club, $startOfYear, $endOfYear);
        $monthlyRevenue = $this->getTotalRevenue($club, $startOfMonth, $endOfMonth);
        
        // CA par source
        $revenueBySource = $this->getRevenueBySource($club, $startOfMonth, $endOfMonth);
        
        // CA par discipline
        $revenueByDiscipline = $this->getRevenueByDiscipline($club, $startOfMonth, $endOfMonth);
        
        // CA par période (12 derniers mois)
        $revenueByPeriod = $this->getRevenueByPeriod($club, 12);
        
        // Statistiques des ventes
        $salesStats = $this->getSalesStats($club, $startOfMonth, $endOfMonth);
        
        // Produits en rupture de stock
        $lowStockProducts = Product::where('club_id', $club->id)
            ->whereRaw('stock_quantity <= min_stock')
            ->count();

        return response()->json([
            'overview' => [
                'total_revenue' => round($totalRevenue, 2),
                'monthly_revenue' => round($monthlyRevenue, 2),
                'revenue_growth' => $this->calculateGrowth($club, $monthlyRevenue),
                'low_stock_products' => $lowStockProducts
            ],
            'revenue_by_source' => $revenueBySource,
            'revenue_by_discipline' => $revenueByDiscipline,
            'revenue_by_period' => $revenueByPeriod,
            'sales_stats' => $salesStats
        ]);
    }

    // Méthodes privées pour les calculs financiers
    private function getTotalRevenue($club, $startDate, $endDate)
    {
        // CA des cours
        $lessonRevenue = Payment::whereHas('lesson', function($query) use ($club) {
            $query->whereHas('teacher', function($q) use ($club) {
                $q->where('club_id', $club->id);
            });
        })
        ->where('status', 'succeeded')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->sum('amount');

        // CA des ventes
        $salesRevenue = Transaction::where('club_id', $club->id)
            ->where('type', 'sale')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        return $lessonRevenue + $salesRevenue;
    }

    private function getRevenueBySource($club, $startDate, $endDate)
    {
        return [
            'lessons' => $this->getLessonRevenue($club, $startDate, $endDate),
            'sales' => $this->getSalesRevenue($club, $startDate, $endDate),
            'subscriptions' => $this->getSubscriptionRevenue($club, $startDate, $endDate)
        ];
    }

    private function getRevenueByDiscipline($club, $startDate, $endDate)
    {
        return Discipline::whereHas('lessons', function($query) use ($club, $startDate, $endDate) {
            $query->whereHas('teacher', function($q) use ($club) {
                $q->where('club_id', $club->id);
            })
            ->whereBetween('start_time', [$startDate, $endDate]);
        })
        ->withCount(['lessons as revenue' => function($query) use ($club, $startDate, $endDate) {
            $query->whereHas('teacher', function($q) use ($club) {
                $q->where('club_id', $club->id);
            })
            ->whereBetween('start_time', [$startDate, $endDate]);
        }])
        ->get()
        ->map(function($discipline) {
            return [
                'name' => $discipline->name,
                'revenue' => $discipline->revenue * 50 // Prix moyen estimé
            ];
        });
    }

    private function getRevenueByPeriod($club, $months)
    {
        $revenue = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            
            $revenue[] = [
                'month' => $date->format('Y-m'),
                'month_name' => $date->translatedFormat('F Y'),
                'revenue' => $this->getTotalRevenue($club, $startOfMonth, $endOfMonth)
            ];
        }
        return $revenue;
    }

    private function getSalesStats($club, $startDate, $endDate)
    {
        $transactions = Transaction::where('club_id', $club->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        return [
            'total_transactions' => $transactions->count(),
            'average_transaction' => $transactions->avg('amount'),
            'top_products' => $this->getTopProducts($club, $startDate, $endDate)
        ];
    }

    private function calculateGrowth($club, $currentRevenue)
    {
        $lastMonth = Carbon::now()->subMonth();
        $lastMonthRevenue = $this->getTotalRevenue($club, $lastMonth->startOfMonth(), $lastMonth->endOfMonth());
        
        if ($lastMonthRevenue == 0) return 0;
        
        return round((($currentRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2);
    }

    private function getLessonRevenue($club, $startDate, $endDate)
    {
        return Payment::whereHas('lesson', function($query) use ($club) {
            $query->whereHas('teacher', function($q) use ($club) {
                $q->where('club_id', $club->id);
            });
        })
        ->where('status', 'succeeded')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->sum('amount');
    }

    private function getSalesRevenue($club, $startDate, $endDate)
    {
        return Transaction::where('club_id', $club->id)
            ->where('type', 'sale')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');
    }

    private function getSubscriptionRevenue($club, $startDate, $endDate)
    {
        // Pour l'instant, retourner 0 car les abonnements ne sont pas encore implémentés
        return 0;
    }

    private function getTopProducts($club, $startDate, $endDate)
    {
        return TransactionItem::whereHas('transaction', function($query) use ($club, $startDate, $endDate) {
            $query->where('club_id', $club->id)
                ->whereBetween('transaction_date', [$startDate, $endDate]);
        })
        ->with('product')
        ->selectRaw('product_id, sum(quantity) as total_quantity, sum(total_price) as total_revenue')
        ->groupBy('product_id')
        ->orderBy('total_revenue', 'desc')
        ->limit(5)
        ->get()
        ->map(function($item) {
            return [
                'name' => $item->product->name,
                'quantity' => $item->total_quantity,
                'revenue' => $item->total_revenue
            ];
        });
    }
}