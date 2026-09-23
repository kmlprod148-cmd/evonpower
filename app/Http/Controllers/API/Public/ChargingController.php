<?php

namespace App\Http\Controllers\API\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Controllers\API\BaseApiController;

class ChargingController extends BaseApiController
{
    /**
     * Start a public charging session.
     */
    public function startCharge(string $id)
    {
        return response()->json(['message' => 'Start public charge for ID: ' . $id]);
    }

    /**
     * Stop a public charging session.
     */
    public function stopCharge(string $id)
    {
        return response()->json(['message' => 'Stop public charge for ID: ' . $id]);
    }
}