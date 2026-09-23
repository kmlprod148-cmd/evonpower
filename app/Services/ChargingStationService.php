<?php

namespace App\Services;

use App\Models\ChargingStation;
use App\Repositories\Interfaces\ChargingStationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ChargingStationService
{
    protected ChargingStationRepositoryInterface $chargingStationRepository;

    public function __construct(ChargingStationRepositoryInterface $chargingStationRepository)
    {
        $this->chargingStationRepository = $chargingStationRepository;
    }

    /**
     * Get all charging stations with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllChargingStationsPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->chargingStationRepository->getAllPaginated($perPage);
    }

    /**
     * Get a charging station by its ID.
     *
     * @param int $id
     * @return ChargingStation|null
     */
    public function getChargingStationById(int $id): ?ChargingStation
    {
        return $this->chargingStationRepository->findById($id);
    }

    /**
     * Create a new charging station.
     *
     * @param array $data
     * @return ChargingStation
     */
    public function createChargingStation(array $data): ChargingStation
    {
        return $this->chargingStationRepository->create($data);
    }

    /**
     * Update a charging station by its ID.
     *
     * @param int $id
     * @param array $data
     * @return ChargingStation|null
     */
    public function updateChargingStation(int $id, array $data): ?ChargingStation
    {
        return $this->chargingStationRepository->update($id, $data);
    }

    /**
     * Delete a charging station by its ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteChargingStation(int $id): bool
    {
        return $this->chargingStationRepository->delete($id);
    }
}