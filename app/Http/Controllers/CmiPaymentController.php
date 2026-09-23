<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CmiPaymentController extends Controller
{
    /**
     * Handle CMI payment callback
     */
    public function handleCallback(Request $request)
    {
        // Log the callback for debugging
        \Log::info('CMI Callback received', $request->all());
        
        // For now, just return a success response
        // In a real implementation, you would verify the payment and update the reservation status
        return response()->json(['status' => 'success', 'message' => 'CMI callback received']);
    }
}
