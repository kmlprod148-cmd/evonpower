<?php

namespace App\Repositories;

use App\Models\Station;
use App\Models\ChargingPoint;
use App\Repositories\Interfaces\StationRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Repository pour la gestion des stations
 */
class StationRepository implements StationRepositoryInterface
{
    /**
     * Find a station by charging point ID and station ID.
     *
     * @param int $chargingPointId
     * @param int $stationId
     * @return Station|null
     */
    public function findByChargingPointAndId(int $chargingPointId, int $stationId): ?Station
    {
        // Vérifier d'abord que le point de recharge existe
        $chargingPoint = ChargingPoint::find($chargingPointId);
        if (!$chargingPoint) {
            return null;
        }

        // Vérifier que la station existe et que le point de recharge lui appartient
        $station = Station::find($stationId);
        if (!$station) {
            return null;
        }

        // Vérifier que le point de recharge appartient à cette station
        if ($chargingPoint->station_id != $stationId) {
            return null;
        }

        return $station;
    }

    /**
     * Update the status of a station.
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        $station = Station::find($id);
        if (!$station) {
            return false;
        }

        // Mettre à jour le statut de la station
        $station->status = $status;
        return $station->save();
    }

    /**
     * Get all stations.
     *
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        $query = Station::select($columns);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->get();
    }

    /**
     * Find a station by ID.
     *
     * @param int $id
     * @param array $relations
     * @return Station|null
     */
    public function find(int $id, array $relations = []): ?Station
    {
        $query = Station::query();
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->find($id);
    }

    /**
     * Create a new station.
     *
     * @param array $data
     * @return Station
     */
    public function create(array $data): Station
    {
        return Station::create($data);
    }

    /**
     * Update an existing station.
     *
     * @param int $id
     * @param array $data
     * @return Station
     */
    public function update(int $id, array $data): Station
    {
        $station = Station::findOrFail($id);
        $station->update($data);
        return $station;
    }

    /**
     * Delete a station.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $station = Station::find($id);
        if (!$station) {
            return false;
        }
        
        return $station->delete();
    }
} 