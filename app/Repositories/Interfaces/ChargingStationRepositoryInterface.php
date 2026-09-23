<?php

namespace App\Repositories\Interfaces;

use App\Models\ChargingStation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ChargingStationRepositoryInterface
{
    /**
     * Get all charging stations with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a charging station by its ID.
     *
     * @param int $id
     * @return ChargingStation|null
     */
    public function findById(int $id): ?ChargingStation;

    /**
     * Create a new charging station.
     *
     * @param array $data
     * @return ChargingStation
     */
    public function create(array $data): ChargingStation;

    /**
     * Update a charging station by its ID.
     *
     * @param int $id
     * @param array $data
     * @return ChargingStation|null
     */
    public function update(int $id, array $data): ?ChargingStation;

    /**
     * Delete a charging station by its ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}