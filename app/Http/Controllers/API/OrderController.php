<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\TransactionCalculatorService;

class OrderController extends Controller
{
    /**
     * Store new order
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Implementation for creating a new order
        return response()->json([
            'success' => true,
            'message' => 'Order created successfully'
        ]);
    }

    /**
     * Backdoor confirm and create transaction
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function backdoorConfirmAndCreateTransaction(Request $request, int $id, TransactionCalculatorService $service): JsonResponse
    {
        $order = Order::findOrFail($id);

        // Create a simple transaction for demo purposes (normally from payment callback)
        $transaction = Transaction::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'charging_point_id' => $order->charging_point_id,
            'amount' => $order->amount,
            'currency' => 'EUR',
            'status' => 'completed',
        ]);

        // Calculate, update wallets and persist repartition atomically
        $result = $service->calculateAndSettle($transaction);

        return response()->json([
            'success' => true,
            'message' => 'Order confirmed, transaction settled and repartition stored.',
            'data' => [
                'amounts' => $result['amounts'],
                'repartition_id' => $result['repartition']?->id,
                'parties' => $result['parties'] ?? null,
            ]
        ]);
    }
}
