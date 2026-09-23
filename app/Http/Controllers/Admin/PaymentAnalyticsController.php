<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentAnalyticsController extends Controller
{
    /**
     * Display analytics dashboard
     */
    public function index()
    {
        $stats = $this->getAnalyticsStats();
        $trends = $this->getRevenueTrends();
        $methods = $this->getMethodPerformance();
        
        return view('admin.payments.analytics', compact('stats', 'trends', 'methods'));
    }

    /**
     * Get comprehensive analytics statistics
     */
    public function getAnalyticsStats()
    {
        $totalPayments = Payment::count();
        $completedPayments = Payment::where('status', 'completed')->count();
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $avgTransaction = Payment::where('status', 'completed')->avg('amount');
        
        // Conversion rate
        $conversionRate = $totalPayments > 0 ? ($completedPayments / $totalPayments) * 100 : 0;
        
        // Average processing time (simulated)
        $avgProcessingTime = 2.3; // seconds
        
        // Failure rate
        $failedPayments = Payment::whereIn('status', ['failed', 'cancelled'])->count();
        $failureRate = $totalPayments > 0 ? ($failedPayments / $totalPayments) * 100 : 0;
        
        // Period comparisons
        $currentPeriod = $this->getCurrentPeriodStats();
        $previousPeriod = $this->getPreviousPeriodStats();
        
        return [
            'total_payments' => $totalPayments,
            'completed_payments' => $completedPayments,
            'total_revenue' => $totalRevenue,
            'avg_transaction' => $avgTransaction,
            'conversion_rate' => round($conversionRate, 1),
            'avg_processing_time' => $avgProcessingTime,
            'failure_rate' => round($failureRate, 1),
            'current_period' => $currentPeriod,
            'previous_period' => $previousPeriod,
            'growth_rate' => $this->calculateGrowthRate($currentPeriod, $previousPeriod),
        ];
    }

    /**
     * Get revenue trends over time
     */
    public function getRevenueTrends($period = '30d')
    {
        $startDate = $this->getStartDate($period);
        
        $trends = Payment::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $trends->pluck('date')->map(function($date) {
                return Carbon::parse($date)->format('M d');
            }),
            'revenue' => $trends->pluck('revenue'),
            'transactions' => $trends->pluck('transactions'),
        ];
    }

    /**
     * Get payment method performance
     */
    public function getMethodPerformance()
    {
        $methods = Payment::with('paymentMethod')
            ->where('status', 'completed')
            ->select(
                'payment_method_id',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('AVG(amount) as avg_amount')
            )
            ->groupBy('payment_method_id')
            ->get();

        $methodStats = [];
        foreach ($methods as $method) {
            $methodStats[] = [
                'name' => $method->paymentMethod->name,
                'total_amount' => $method->total_amount,
                'transaction_count' => $method->transaction_count,
                'avg_amount' => $method->avg_amount,
                'success_rate' => $this->getMethodSuccessRate($method->payment_method_id),
            ];
        }

        return $methodStats;
    }

    /**
     * Get detailed analytics data
     */
    public function getDetailedAnalytics(Request $request)
    {
        $filters = $request->only(['period', 'payment_method', 'status', 'min_amount', 'max_amount']);
        
        $query = Payment::with('paymentMethod', 'reservation');
        
        // Apply filters
        if (!empty($filters['period'])) {
            $startDate = $this->getStartDate($filters['period']);
            $query->where('created_at', '>=', $startDate);
        }
        
        if (!empty($filters['payment_method'])) {
            $query->whereHas('paymentMethod', function($q) use ($filters) {
                $q->where('slug', $filters['payment_method']);
            });
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }
        
        if (!empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }
        
        $payments = $query->get();
        
        return [
            'total_count' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'avg_amount' => $payments->avg('amount'),
            'status_breakdown' => $payments->groupBy('status')->map->count(),
            'method_breakdown' => $payments->groupBy('paymentMethod.name')->map->count(),
            'daily_breakdown' => $payments->groupBy(function($payment) {
                return $payment->created_at->format('Y-m-d');
            })->map(function($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount')
                ];
            }),
        ];
    }

    /**
     * Export analytics data
     */
    public function exportAnalytics(Request $request)
    {
        $type = $request->get('type', 'revenue');
        $period = $request->get('period', '30d');
        
        switch ($type) {
            case 'revenue':
                return $this->exportRevenueReport($period);
            case 'transactions':
                return $this->exportTransactionsReport($period);
            case 'methods':
                return $this->exportMethodsReport($period);
            case 'failures':
                return $this->exportFailuresReport($period);
            default:
                return response()->json(['error' => 'Invalid export type'], 400);
        }
    }

    /**
     * Get real-time analytics
     */
    public function getRealTimeAnalytics()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        
        $todayStats = Payment::whereDate('created_at', $today)->get();
        $yesterdayStats = Payment::whereDate('created_at', $yesterday)->get();
        
        return [
            'today' => [
                'count' => $todayStats->count(),
                'amount' => $todayStats->sum('amount'),
                'completed' => $todayStats->where('status', 'completed')->count(),
                'failed' => $todayStats->whereIn('status', ['failed', 'cancelled'])->count(),
            ],
            'yesterday' => [
                'count' => $yesterdayStats->count(),
                'amount' => $yesterdayStats->sum('amount'),
                'completed' => $yesterdayStats->where('status', 'completed')->count(),
                'failed' => $yesterdayStats->whereIn('status', ['failed', 'cancelled'])->count(),
            ],
            'growth' => [
                'count' => $this->calculateGrowth($todayStats->count(), $yesterdayStats->count()),
                'amount' => $this->calculateGrowth($todayStats->sum('amount'), $yesterdayStats->sum('amount')),
            ]
        ];
    }

    /**
     * Get current period statistics
     */
    private function getCurrentPeriodStats()
    {
        $startDate = Carbon::now()->subDays(30);
        
        return [
            'revenue' => Payment::where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
            'transactions' => Payment::where('created_at', '>=', $startDate)->count(),
            'completed' => Payment::where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->count(),
        ];
    }

    /**
     * Get previous period statistics
     */
    private function getPreviousPeriodStats()
    {
        $startDate = Carbon::now()->subDays(60);
        $endDate = Carbon::now()->subDays(30);
        
        return [
            'revenue' => Payment::where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'transactions' => Payment::whereBetween('created_at', [$startDate, $endDate])->count(),
            'completed' => Payment::where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
        ];
    }

    /**
     * Calculate growth rate
     */
    private function calculateGrowthRate($current, $previous)
    {
        if ($previous['revenue'] == 0) return 0;
        
        return round((($current['revenue'] - $previous['revenue']) / $previous['revenue']) * 100, 1);
    }

    /**
     * Calculate growth percentage
     */
    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) return 0;
        
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get start date based on period
     */
    private function getStartDate($period)
    {
        switch ($period) {
            case '7d':
                return Carbon::now()->subDays(7);
            case '30d':
                return Carbon::now()->subDays(30);
            case '90d':
                return Carbon::now()->subDays(90);
            case '1y':
                return Carbon::now()->subYear();
            default:
                return Carbon::now()->subDays(30);
        }
    }

    /**
     * Get method success rate
     */
    private function getMethodSuccessRate($methodId)
    {
        $total = Payment::where('payment_method_id', $methodId)->count();
        $successful = Payment::where('payment_method_id', $methodId)
            ->where('status', 'completed')
            ->count();
        
        return $total > 0 ? round(($successful / $total) * 100, 1) : 0;
    }

    /**
     * Export revenue report
     */
    private function exportRevenueReport($period)
    {
        $startDate = $this->getStartDate($period);
        
        $data = Payment::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $csv = "Date,Revenus,Transactions\n";
        foreach ($data as $row) {
            $csv .= "{$row->date},{$row->revenue},{$row->transactions}\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="revenue_report_' . $period . '.csv"');
    }

    /**
     * Export transactions report
     */
    private function exportTransactionsReport($period)
    {
        $startDate = $this->getStartDate($period);
        
        $payments = Payment::with('paymentMethod', 'reservation')
            ->where('created_at', '>=', $startDate)
            ->get();

        $csv = "ID,Transaction ID,Amount,Currency,Status,Payment Method,Reservation ID,Created At\n";
        foreach ($payments as $payment) {
            $csv .= implode(',', [
                $payment->id,
                $payment->transaction_id,
                $payment->amount,
                $payment->currency,
                $payment->status,
                $payment->paymentMethod->name,
                $payment->reservation_id,
                $payment->created_at->format('Y-m-d H:i:s')
            ]) . "\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="transactions_report_' . $period . '.csv"');
    }

    /**
     * Export methods report
     */
    private function exportMethodsReport($period)
    {
        $startDate = $this->getStartDate($period);
        
        $methods = Payment::with('paymentMethod')
            ->where('created_at', '>=', $startDate)
            ->select(
                'payment_method_id',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('AVG(amount) as avg_amount')
            )
            ->groupBy('payment_method_id')
            ->get();

        $csv = "Payment Method,Total Amount,Transaction Count,Average Amount,Success Rate\n";
        foreach ($methods as $method) {
            $successRate = $this->getMethodSuccessRate($method->payment_method_id);
            $csv .= "{$method->paymentMethod->name},{$method->total_amount},{$method->transaction_count},{$method->avg_amount},{$successRate}%\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="methods_report_' . $period . '.csv"');
    }

    /**
     * Export failures report
     */
    private function exportFailuresReport($period)
    {
        $startDate = $this->getStartDate($period);
        
        $failures = Payment::with('paymentMethod')
            ->whereIn('status', ['failed', 'cancelled'])
            ->where('created_at', '>=', $startDate)
            ->get();

        $csv = "ID,Transaction ID,Amount,Status,Payment Method,Failure Reason,Created At\n";
        foreach ($failures as $failure) {
            $csv .= implode(',', [
                $failure->id,
                $failure->transaction_id,
                $failure->amount,
                $failure->status,
                $failure->paymentMethod->name,
                $failure->failure_reason ?? 'N/A',
                $failure->created_at->format('Y-m-d H:i:s')
            ]) . "\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="failures_report_' . $period . '.csv"');
    }
}
