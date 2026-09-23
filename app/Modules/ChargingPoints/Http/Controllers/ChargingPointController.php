<?php

namespace App\Modules\ChargingPoints\Http\Controllers;

use App\Core\Http\Controllers\BaseResourceController;
use App\Modules\ChargingPoints\Services\ChargingPointService;
use App\Http\Requests\ChargingPoint\ChargingPointStoreRequest;
use App\Http\Requests\ChargingPoint\ChargingPointUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use App\Exceptions\ChargingPoint\ChargingPointException;
use Throwable;
use App\Models\ChargingPoint;

class ChargingPointController extends BaseResourceController
{
    /**
     * @var ChargingPointService
     */
    protected ChargingPointService $service;

    /**
     * ChargingPointController constructor.
     *
     * @param ChargingPointService $service
     */
    public function __construct(ChargingPointService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the charging points.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->all();
            $perPage = $request->input('per_page', 15);
            $data = $this->service->getPaginated($perPage, $filters);

            // For web, render a view; for API, return JSON
            return $this->successResponse($request, $data, 'Charging points retrieved', 'charging-points.index');
        } catch (Throwable $e) {
            return $this->errorResponse($request, $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified charging point.
     */
    public function show(Request $request, int $id)
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        $this->authorize('view', $chargingPoint);
        try {
            $data = $this->service->getById($id);
            return $this->successResponse($request, $data, 'Charging point details', 'charging-points.show');
        } catch (ChargingPointException $e) {
            return $this->errorResponse($request, $e->getMessage(), 404);
        } catch (Throwable $e) {
            return $this->errorResponse($request, $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created charging point.
     */
    public function store(ChargingPointStoreRequest $request)
    {
        try {
            $data = $this->service->createWithRelations($request->validated());
            return $this->successResponse($request, $data, 'Charging point created successfully', 'charging-points.show', 201);
        } catch (ChargingPointException $e) {
            return $this->errorResponse($request, $e->getMessage(), 400);
        } catch (Throwable $e) {
            return $this->errorResponse($request, $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified charging point.
     */
    public function update(ChargingPointUpdateRequest $request, int $id)
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        $this->authorize('update', $chargingPoint);
        try {
            $data = $this->service->updateWithValidation($id, $request->validated());
            return $this->successResponse($request, $data, 'Charging point updated successfully', 'charging-points.show');
        } catch (ChargingPointException $e) {
            return $this->errorResponse($request, $e->getMessage(), 400);
        } catch (Throwable $e) {
            return $this->errorResponse($request, $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified charging point.
     */
    public function destroy(Request $request, int $id)
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        $this->authorize('delete', $chargingPoint);
        try {
            $this->service->deleteWithDependencyCheck($id);
            return $this->successResponse($request, null, 'Charging point deleted successfully');
        } catch (ChargingPointException $e) {
            return $this->errorResponse($request, $e->getMessage(), 400);
        } catch (Throwable $e) {
            return $this->errorResponse($request, $e->getMessage(), 500);
        }
    }

    /**
     * Bulk operations on charging points.
     */
    public function bulk(Request $request)
    {
        try {
            $result = $this->service->bulkOperations($request->all());
            return $this->successResponse($request, $result, 'Bulk operation completed');
        } catch (ChargingPointException $e) {
            return $this->errorResponse($request, $e->getMessage(), 400);
        } catch (Throwable $e) {
            return $this->errorResponse($request, $e->getMessage(), 500);
        }
    }
}