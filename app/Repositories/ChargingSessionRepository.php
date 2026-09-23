<?php

namespace App\Repositories;

use App\Models\ChargingSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ChargingSessionRepository implements ChargingSessionRepositoryInterface
{
    /**
     * Find a charging session by its ID.
     *
     * @param int $id The ID of the session.
     * @return ChargingSession|null The found session or null if not found.
     */
    public function findById(int $id): ?ChargingSession
    {
        return ChargingSession::find($id);
    }

    /**
     * Find active charging sessions for a specific charging point.
     * Assumes an 'is_active' boolean field or similar status field exists.
     *
     * @param int $chargingPointId The ID of the charging point.
     * @return Collection A collection of active charging sessions.
     */
    public function findActiveByChargingPoint(int $chargingPointId): Collection
    {
        // Adjust the 'where' clause based on your actual status field name and value
        return ChargingSession::where('charging_point_id', $chargingPointId)
                              ->where('status', 'active') // Example: assuming 'status' field
                              ->get();
    }

    /**
     * Create a new charging session.
     *
     * @param array $data Data for the new session.
     * @return ChargingSession The created session.
     */
    public function create(array $data): ChargingSession
    {
        return ChargingSession::create($data);
    }

    /**
     * Update an existing charging session.
     *
     * @param int $id The ID of the session to update.
     * @param array $data Data to update the session with.
     * @return bool True if update was successful, false otherwise.
     */
    public function update(int $id, array $data): bool
    {
        $session = $this->findById($id);
        if (!$session) {
            return false; // Or throw an exception
        }
        return $session->update($data);
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
}