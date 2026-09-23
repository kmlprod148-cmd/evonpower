<?php

namespace App\Repositories\Eloquent;

use App\Models\ChargingStation;
use App\Repositories\Interfaces\ChargingStationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentChargingStationRepository implements ChargingStationRepositoryInterface
{
    /**
     * Get all charging stations with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return ChargingStation::with(['group'])->paginate($perPage);
    }

    /**
     * Get a charging station by its ID.
     *
     * @param int $id
     * @return ChargingStation|null
     */
    public function findById(int $id): ?ChargingStation
    {
        return ChargingStation::find($id);
    }

    /**
     * Create a new charging station.
     *
     * @param array $data
     * @return ChargingStation
     */
    public function create(array $data): ChargingStation
    {
        return ChargingStation::create($data);
    }

    /**
     * Update a charging station by its ID.
     *
     * @param int $id
     * @param array $data
     * @return ChargingStation|null
     */
    public function update(int $id, array $data): ?ChargingStation
    {
        $chargingStation = $this->findById($id);

        if (!$chargingStation) {
            return null;
        }

        $chargingStation->update($data);

        return $chargingStation;
    }

    /**
     * Delete a charging station by its ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $chargingStation = $this->findById($id);

        if (!$chargingStation) {
            return false;
        }

        return (bool) $chargingStation->delete();
    }
}