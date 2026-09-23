<?php

namespace App\Repositories\Interfaces;

use App\Core\Repositories\Interfaces\BaseRepositoryInterface;
use App\Models\ChargingPoint;
use Illuminate\Support\Collection;

/**
 * Interface ChargingPointRepositoryInterface
 *
 * @package App\Repositories\Interfaces
 */
interface ChargingPointRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get charging points with their relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllWithRelations(array $relations = []): Collection;

    /**
     * Find a charging point by ID with relations.
     *
     * @param int $id
     * @param array $relations
     * @return ChargingPoint|null
     */
    public function findWithRelations(int $id, array $relations = []): ?ChargingPoint;

    // Add specific methods for ChargingPoint data access if needed
}