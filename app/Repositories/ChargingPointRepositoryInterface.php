<?php

namespace App\Repositories;

use App\Models\ChargingPoint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ChargingPointRepositoryInterface
{
    /**
     * Find charging point by ID.
     *
     * @param int $id
     * @return ChargingPoint|null
     */
    public function findById(int $id): ?ChargingPoint;

    /**
     * Find charging point with relations by ID.
     *
     * @param int $id
     * @param array $relations
     * @return ChargingPoint|null
     */
    public function findWithRelations(int $id, array $relations = []): ?ChargingPoint;

    /**
     * Get all charging points.
     *
     * @return Collection
     */
    public function getAll(): Collection;

    /**
     * Get paginated charging points.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new charging point.
     *
     * @param array $data
     * @return ChargingPoint
     */
    public function create(array $data): ChargingPoint;

    /**
     * Update a charging point.
     *
     * @param int $id
     * @param array $data
     * @return ChargingPoint|null
     */
    public function update(int $id, array $data): ?ChargingPoint;

    /**
     * Delete a charging point.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}