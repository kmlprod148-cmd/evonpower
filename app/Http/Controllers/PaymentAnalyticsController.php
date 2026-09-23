<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Reservation;
use Carbon\Carbon;

class PaymentAnalyticsController extends Controller
{
    /**
     * Obtenir les statistiques de paiement
     */
    public function getPaymentStats(Request $request)
    {
        try {
            $period = $request->get('period', '30d'); // 7d, 30d, 90d, 1y
            $startDate = $this->getStartDate($period);
            
            $stats = [
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => now(),
                'total_transactions' => $this->getTotalTransactions($startDate),
                'successful_payments' => $this->getSuccessfulPayments($startDate),
                'failed_payments' => $this->getFailedPayments($startDate),
                'total_revenue' => $this->getTotalRevenue($startDate),
                'payment_methods' => $this->getPaymentMethodStatsData($startDate),
                'daily_stats' => $this->getDailyStats($startDate),
                'conversion_rate' => $this->getConversionRate($startDate),
                'average_transaction_value' => $this->getAverageTransactionValue($startDate)
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Payment analytics error', [
                'error' => $e->getMessage(),
                'period' => $request->get('period')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de paiement par méthode
     */
    public function getPaymentMethodStats(Request $request)
    {
        try {
            $period = $request->get('period', '30d');
            $startDate = $this->getStartDate($period);

            $stats = DB::table('orders')
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('payment_method')
                ->select('payment_method')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw('AVG(amount) as average_amount')
                ->selectRaw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful_count')
                ->selectRaw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count')
                ->groupBy('payment_method')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Payment method stats error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques par méthode'
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de conversion
     */
    public function getConversionStats(Request $request)
    {
        try {
            $period = $request->get('period', '30d');
            $startDate = $this->getStartDate($period);

            $conversionStats = [
                'reservations_created' => Reservation::where('created_at', '>=', $startDate)->count(),
                'payments_initiated' => Order::where('created_at', '>=', $startDate)
                    ->whereIn('payment_method', ['cmi', 'stripe'])
                    ->count(),
                'payments_completed' => Order::where('created_at', '>=', $startDate)
                    ->where('status', 'completed')
                    ->count(),
                'payments_failed' => Order::where('created_at', '>=', $startDate)
                    ->where('status', 'failed')
                    ->count(),
                'offline_payments' => Order::where('created_at', '>=', $startDate)
                    ->where('payment_method', 'offline')
                    ->count()
            ];

            // Calculer les taux de conversion
            $conversionStats['initiation_rate'] = $conversionStats['reservations_created'] > 0 
                ? ($conversionStats['payments_initiated'] / $conversionStats['reservations_created']) * 100 
                : 0;

            $conversionStats['completion_rate'] = $conversionStats['payments_initiated'] > 0 
                ? ($conversionStats['payments_completed'] / $conversionStats['payments_initiated']) * 100 
                : 0;

            $conversionStats['failure_rate'] = $conversionStats['payments_initiated'] > 0 
                ? ($conversionStats['payments_failed'] / $conversionStats['payments_initiated']) * 100 
                : 0;

            return response()->json([
                'success' => true,
                'data' => $conversionStats
            ]);

        } catch (\Exception $e) {
            Log::error('Conversion stats error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques de conversion'
            ], 500);
        }
    }

    /**
     * Obtenir les erreurs de paiement
     */
    public function getPaymentErrors(Request $request)
    {
        try {
            $period = $request->get('period', '7d');
            $startDate = $this->getStartDate($period);

            $errors = DB::table('orders')
                ->where('created_at', '>=', $startDate)
                ->where('status', 'failed')
                ->select('payment_method')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('AVG(amount) as average_amount')
                ->groupBy('payment_method')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Payment errors stats error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des erreurs de paiement'
            ], 500);
        }
    }

    /**
     * Obtenir la date de début selon la période
     */
    private function getStartDate(string $period): Carbon
    {
        switch ($period) {
            case '7d':
                return now()->subDays(7);
            case '30d':
                return now()->subDays(30);
            case '90d':
                return now()->subDays(90);
            case '1y':
                return now()->subYear();
            default:
                return now()->subDays(30);
        }
    }

    /**
     * Obtenir le nombre total de transactions
     */
    private function getTotalTransactions(Carbon $startDate): int
    {
        return Order::where('created_at', '>=', $startDate)->count();
    }

    /**
     * Obtenir le nombre de paiements réussis
     */
    private function getSuccessfulPayments(Carbon $startDate): int
    {
        return Order::where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Obtenir le nombre de paiements échoués
     */
    private function getFailedPayments(Carbon $startDate): int
    {
        return Order::where('created_at', '>=', $startDate)
            ->where('status', 'failed')
            ->count();
    }

    /**
     * Obtenir le revenu total
     */
    private function getTotalRevenue(Carbon $startDate): float
    {
        return Order::where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Obtenir les statistiques par méthode de paiement (méthode privée)
     */
    private function getPaymentMethodStatsData(Carbon $startDate): array
    {
        return DB::table('orders')
            ->where('created_at', '>=', $startDate)
            ->select('payment_method')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('AVG(amount) as average_amount')
            ->groupBy('payment_method')
            ->get()
            ->toArray();
    }

    /**
     * Obtenir les statistiques quotidiennes
     */
    private function getDailyStats(Carbon $startDate): array
    {
        return DB::table('orders')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as transactions')
            ->selectRaw('SUM(amount) as revenue')
            ->selectRaw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful')
            ->selectRaw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Obtenir le taux de conversion
     */
    private function getConversionRate(Carbon $startDate): float
    {
        $total = $this->getTotalTransactions($startDate);
        $successful = $this->getSuccessfulPayments($startDate);
        
        return $total > 0 ? ($successful / $total) * 100 : 0;
    }

    /**
     * Obtenir la valeur moyenne des transactions
     */
    private function getAverageTransactionValue(Carbon $startDate): float
    {
        return Order::where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->avg('amount') ?? 0;
    }
}
