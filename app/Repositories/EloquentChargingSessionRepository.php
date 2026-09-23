<?php

namespace App\Repositories;

use App\Models\ChargingSession;
use Illuminate\Database\Eloquent\Collection;

class EloquentChargingSessionRepository implements ChargingSessionRepositoryInterface
{
    /**
     * Find a charging session by its ID.
     *
     * @param int $id
     * @return ChargingSession|null
     */
    public function findById(int $id): ?ChargingSession
    {
        return ChargingSession::find($id);
    }
    
        /**
         * Find active charging sessions for a specific charging point.
         *
         * @param int $chargingPointId The ID of the charging point.
         * @return Collection A collection of active charging sessions.
         */
        public function findActiveByChargingPoint(int $chargingPointId): Collection
        {
            return ChargingSession::where('charging_point_id', $chargingPointId)
                ->whereNull('end_at') // Assuming active sessions have a null end_at timestamp
                ->get();
        }
    
        /**
         * Get all charging sessions.
         *
         * @return Collection A collection of all charging sessions.
         */
        public function all(): Collection
        {
            return ChargingSession::all();
        }
    
        /**
         * Find a charging session by its session ID.
         *
         * @param string $sessionId
         * @return ChargingSession|null
         */
    public function findBySessionId(string $sessionId): ?ChargingSession
    {
        return ChargingSession::where('session_id', $sessionId)->first();
    }

    /**
     * Find the latest charging session for a connector.
     *
     * @param int $connectorId
     * @return ChargingSession|null
     */
    public function findLatestByConnectorId(int $connectorId): ?ChargingSession
    {
        return ChargingSession::where('connector_id', $connectorId)
            ->latest()
            ->first();
    }

    /**
     * Find all charging sessions for a charging point.
     *
     * @param int $chargingPointId
     * @return Collection|ChargingSession[]
     */
    public function findByChargingPointId(int $chargingPointId): Collection
    {
        return ChargingSession::where('charging_point_id', $chargingPointId)->get();
    }

    /**
     * Find all charging sessions for a user.
     *
     * @param int $userId
     * @return Collection|ChargingSession[]
     */
    public function findByUserId(int $userId): Collection
    {
        return ChargingSession::where('user_id', $userId)->get();
    }

    /**
     * Save a charging session.
     *
     * @param ChargingSession $chargingSession
     * @return ChargingSession
     */
    public function save(ChargingSession $chargingSession): ChargingSession
    {
        $chargingSession->save();
        return $chargingSession;
    }

    /**
     * Create a new charging session.
     *
     * @param array $data
     * @return ChargingSession
     */
    public function create(array $data): ChargingSession
    {
        return ChargingSession::create($data);
    }

    /**
     * Update a charging session.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $session = $this->findById($id);

        if (!$session) {
            return false;
        }

        return $session->update($data);
    }

    /**
     * Delete a charging session.
     *
     * @param ChargingSession $chargingSession
     * @return bool|null
     */
    public function delete(ChargingSession $chargingSession): ?bool
    {
        return $chargingSession->delete();
    }
}