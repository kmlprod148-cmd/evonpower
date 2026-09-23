<?php

namespace App\Repositories\Interfaces;

use App\Models\Station;
use Illuminate\Support\Collection;

/**
 * Interface StationRepositoryInterface
 *
 * @package App\Repositories\Interfaces
 */
interface StationRepositoryInterface
{
    /**
     * Find a station by charging point ID and station ID.
     *
     * @param int $chargingPointId
     * @param int $stationId
     * @return Station|null
     */
    public function findByChargingPointAndId(int $chargingPointId, int $stationId): ?Station;

    /**
     * Update the status of a station.
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool;

    /**
     * Get all stations.
     *
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Find a station by ID.
     *
     * @param int $id
     * @param array $relations
     * @return Station|null
     */
    public function find(int $id, array $relations = []): ?Station;

    /**
     * Create a new station.
     *
     * @param array $data
     * @return Station
     */
    public function create(array $data): Station;

    /**
     * Update an existing station.
     *
     * @param int $id
     * @param array $data
     * @return Station
     */
    public function update(int $id, array $data): Station;

    /**
     * Delete a station.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
} 