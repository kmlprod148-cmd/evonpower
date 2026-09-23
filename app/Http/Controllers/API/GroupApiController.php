<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Group;

class GroupApiController extends Controller
{
    /**
     * Get all groups
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $groups = Group::paginate(15);

        return response()->json([
            'success' => true,
            'data' => $groups
        ]);
    }

    /**
     * Get specific group
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $group = Group::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $group
        ]);
    }
}
