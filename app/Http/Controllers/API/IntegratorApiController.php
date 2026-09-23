<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Integrator;

class IntegratorApiController extends Controller
{
    /**
     * Get all integrators
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $integrators = Integrator::paginate(15);

        return response()->json([
            'success' => true,
            'data' => $integrators
        ]);
    }

    /**
     * Get specific integrator
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $integrator = Integrator::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $integrator
        ]);
    }
}
