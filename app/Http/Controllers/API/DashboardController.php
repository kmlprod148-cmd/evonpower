<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse; // Add ApiResponse trait
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    use ApiResponse; // Use the ApiResponse trait

    /**
     * Get dashboard statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(Request $request)
    {
        // TODO: Implement logic to fetch dashboard statistics
        return $this->sendSuccess(['message' => 'Dashboard stats endpoint']);
    }

    /**
     * Get active recharges.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveRecharges(Request $request)
    {
        // TODO: Implement logic to fetch active recharges
        return $this->sendSuccess(['message' => 'Active recharges endpoint']);
    }
}