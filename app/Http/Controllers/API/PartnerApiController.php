<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Partner;

class PartnerApiController extends Controller
{
    /**
     * Get all partners
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $partners = Partner::paginate(15);

        return response()->json([
            'success' => true,
            'data' => $partners
        ]);
    }

    /**
     * Get specific partner
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $partner = Partner::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $partner
        ]);
    }
}
