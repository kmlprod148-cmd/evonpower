<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentManagementController extends Controller
{
    /**
     * Display payments dashboard
     */
    public function index(Request $request)
    {
        $query = Payment::with(['reservation', 'paymentMethod']);

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->has('payment_method') && $request->payment_method !== '') {
            $query->where('payment_method_id', $request->payment_method);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by transaction ID or reservation ID
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhere('external_id', 'like', "%{$search}%")
                  ->orWhereHas('reservation', function ($reservationQuery) use ($search) {
                      $reservationQuery->where('id', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);
        $paymentMethods = PaymentMethod::all();
        
        // Statistics
        $stats = $this->getPaymentStatistics();

        return view('admin.payments.index', compact('payments', 'paymentMethods', 'stats'));
    }

    /**
     * Show payment details
     */
    public function show(Payment $payment)
    {
        $payment->load(['reservation', 'paymentMethod']);
        
        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Update payment status
     */
    public function updateStatus(Request $request, Payment $payment)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,failed,cancelled,refunded',
            'reason' => 'nullable|string|max:500',
        ]);

        $oldStatus = $payment->status;
        
        $payment->update([
            'status' => $request->status,
            'failure_reason' => $request->reason,
        ]);

        // Log status change
        \Log::info('Payment status updated', [
            'payment_id' => $payment->id,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully',
        ]);
    }

    /**
     * Refund payment
     */
    public function refund(Request $request, Payment $payment)
    {
        if ($payment->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed payments can be refunded',
            ], 400);
        }

        try {
            // Implement refund logic based on payment method
            $refundResult = $this->processRefund($payment);
            
            if ($refundResult['success']) {
                $payment->update(['status' => 'refunded']);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Payment refunded successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $refundResult['message'],
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Refund failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment statistics
     */
    public function getStatistics()
    {
        $stats = $this->getPaymentStatistics();
        
        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Export payments
     */
    public function export(Request $request)
    {
        $query = Payment::with(['reservation', 'paymentMethod']);

        // Apply same filters as index
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method') && $request->payment_method !== '') {
            $query->where('payment_method_id', $request->payment_method);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        // Generate CSV
        $csvData = $this->generateCSV($payments);
        
        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="payments_' . date('Y-m-d') . '.csv"');
    }

    /**
     * Get payment statistics
     */
    private function getPaymentStatistics()
    {
        $totalPayments = Payment::count();
        $completedPayments = Payment::where('status', 'completed')->count();
        $pendingPayments = Payment::whereIn('status', ['pending', 'processing'])->count();
        $failedPayments = Payment::whereIn('status', ['failed', 'cancelled'])->count();
        
        $totalAmount = Payment::where('status', 'completed')->sum('amount');
        $todayAmount = Payment::where('status', 'completed')
            ->whereDate('paid_at', today())
            ->sum('amount');

        // Use database-agnostic date formatting
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            $dateFormat = "strftime('%Y-%m', created_at)";
        } else {
            $dateFormat = "DATE_FORMAT(created_at, '%Y-%m')";
        }
        
        $monthlyStats = Payment::select(
                DB::raw($dateFormat . ' as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->where('status', 'completed')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        return [
            'total_payments' => $totalPayments,
            'completed_payments' => $completedPayments,
            'pending_payments' => $pendingPayments,
            'failed_payments' => $failedPayments,
            'total_amount' => $totalAmount,
            'today_amount' => $todayAmount,
            'monthly_stats' => $monthlyStats,
        ];
    }

    /**
     * Process refund
     */
    private function processRefund(Payment $payment)
    {
        // Implement refund logic based on payment method
        // This would integrate with CMI or Stripe APIs
        return ['success' => true, 'message' => 'Refund processed'];
    }

    /**
     * Generate CSV data
     */
    private function generateCSV($payments)
    {
        $csv = "ID,Transaction ID,Amount,Currency,Status,Payment Method,Reservation ID,Created At,Paid At\n";
        
        foreach ($payments as $payment) {
            $csv .= implode(',', [
                $payment->id,
                $payment->transaction_id,
                $payment->amount,
                $payment->currency,
                $payment->status,
                $payment->paymentMethod->name,
                $payment->reservation_id,
                $payment->created_at->format('Y-m-d H:i:s'),
                $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : '',
            ]) . "\n";
        }
        
        return $csv;
    }
}
