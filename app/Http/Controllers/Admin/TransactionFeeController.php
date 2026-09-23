<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Reservation;
use App\Services\ReservationFeeDisplayService;
use Illuminate\Http\Request;

class TransactionFeeController extends Controller
{
    protected $feeDisplayService;

    public function __construct(ReservationFeeDisplayService $feeDisplayService)
    {
        $this->feeDisplayService = $feeDisplayService;
    }

    /**
     * Affiche les détails des frais pour une transaction
     */
    public function showTransactionFees(Transaction $transaction)
    {
        $feeDetails = $this->feeDisplayService->calculateTransactionFeeDetails($transaction);

        return view('admin.transactions.fee-details', [
            'transaction' => $transaction,
            'feeDetails' => $feeDetails
        ]);
    }

    /**
     * Affiche les détails des frais pour une réservation
     */
    public function showReservationFees(Reservation $reservation)
    {
        $totalAmount = $reservation->estimated_cost ?? 0;
        $feeDetails = $this->feeDisplayService->calculateFeeDetails($reservation, $totalAmount);

        return view('admin.transactions.fee-details', [
            'reservation' => $reservation,
            'feeDetails' => $feeDetails
        ]);
    }

    /**
     * Affiche les détails des frais pour une transaction avec un montant personnalisé
     */
    public function showCustomFees(Request $request, Transaction $transaction)
    {
        $customAmount = $request->input('amount', $transaction->price_total);
        $feeDetails = $this->feeDisplayService->calculateTransactionFeeDetails($transaction);

        // Modifier le montant total pour le calcul
        $feeDetails['breakdown']['total_amount'] = $customAmount;
        $feeDetails['net_revenue'] = max(0, $customAmount - $feeDetails['total_fees']);

        return view('admin.transactions.fee-details', [
            'transaction' => $transaction,
            'feeDetails' => $feeDetails,
            'customAmount' => $customAmount
        ]);
    }

    /**
     * Exporte les détails des frais en JSON
     */
    public function exportFees(Transaction $transaction)
    {
        $feeDetails = $this->feeDisplayService->calculateTransactionFeeDetails($transaction);

        return response()->json([
            'transaction_id' => $transaction->id,
            'fee_details' => $feeDetails,
            'exported_at' => now()->toISOString()
        ]);
    }

    /**
     * Compare les frais entre deux transactions
     */
    public function compareFees(Request $request)
    {
        $transaction1Id = $request->input('transaction1_id');
        $transaction2Id = $request->input('transaction2_id');

        $transaction1 = Transaction::findOrFail($transaction1Id);
        $transaction2 = Transaction::findOrFail($transaction2Id);

        $fees1 = $this->feeDisplayService->calculateTransactionFeeDetails($transaction1);
        $fees2 = $this->feeDisplayService->calculateTransactionFeeDetails($transaction2);

        return view('admin.transactions.fee-comparison', [
            'transaction1' => $transaction1,
            'transaction2' => $transaction2,
            'fees1' => $fees1,
            'fees2' => $fees2
        ]);
    }
}
