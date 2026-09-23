<?php

namespace App\Repositories;

use App\Models\ChargingPoint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentChargingPointRepository implements ChargingPointRepositoryInterface
{
    /**
     * Find charging point by ID.
     *
     * @param int $id
     * @return ChargingPoint|null
     */
    public function findById(int $id): ?ChargingPoint
    {
        return ChargingPoint::find($id);
    }

    /**
     * Find charging point with relations by ID.
     *
     * @param int $id
     * @param array $relations
     * @return ChargingPoint|null
     */
    public function findWithRelations(int $id, array $relations = []): ?ChargingPoint
    {
        return ChargingPoint::with($relations)->find($id);
    }

    /**
     * Get all charging points.
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return ChargingPoint::all();
    }

    /**
     * Get paginated charging points.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return ChargingPoint::with(['integrator', 'partner', 'group', 'pricingPlan'])->paginate($perPage);
    }

    /**
     * Create a new charging point.
     *
     * @param array $data
     * @return ChargingPoint
     */
    public function create(array $data): ChargingPoint
    {
        return ChargingPoint::create($data);
    }

    /**
     * Update a charging point.
     *
     * @param int $id
     * @param array $data
     * @return ChargingPoint|null
     */
    public function update(int $id, array $data): ?ChargingPoint
    {
        $chargingPoint = $this->findById($id);
        if (!$chargingPoint) {
            return null;
        }
        
        $chargingPoint->update($data);
        return $chargingPoint;
    }

    /**
     * Delete a charging point.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $chargingPoint = $this->findById($id);
        if (!$chargingPoint) {
            return false;
        }
        
        return $chargingPoint->delete();
    }
}