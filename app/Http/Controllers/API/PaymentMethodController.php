<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Get all active payment methods
     */
    public function index()
    {
        try {
            // Simple and direct approach
            $paymentMethods = PaymentMethod::active()->get();
            
            $result = $paymentMethods->map(function ($method) {
                return [
                    'id' => $method->id,
                    'name' => $method->name,
                    'slug' => $method->slug,
                    'provider' => $method->provider,
                    'description' => $method->description,
                    'logo' => $method->logo,
                    'is_active' => $method->is_active,
                    'sort_order' => $method->sort_order
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Payment methods API error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'error' => 'Erreur lors du chargement des méthodes de paiement',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment method by ID
     */
    public function show($id)
    {
        try {
            $paymentMethod = PaymentMethod::where('id', $id)
                ->where('is_active', true)
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'error' => 'Méthode de paiement non trouvée'
                ], 404);
            }

            return response()->json([
                'id' => $paymentMethod->id,
                'name' => $paymentMethod->name,
                'slug' => $paymentMethod->slug,
                'provider' => $paymentMethod->provider,
                'description' => $paymentMethod->description,
                'logo' => $paymentMethod->logo,
                'is_active' => $paymentMethod->is_active,
                'sort_order' => $paymentMethod->sort_order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors du chargement de la méthode de paiement',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
