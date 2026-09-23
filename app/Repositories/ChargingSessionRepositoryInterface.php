<?php

namespace App\Repositories;

use App\Models\ChargingSession;
use Illuminate\Database\Eloquent\Collection;

interface ChargingSessionRepositoryInterface
{
    /**
     * Find a charging session by its ID.
     *
     * @param int $id The ID of the session.
     * @return ChargingSession|null The found session or null if not found.
     */
    public function findById(int $id): ?ChargingSession;

    /**
     * Find active charging sessions for a specific charging point.
     *
     * @param int $chargingPointId The ID of the charging point.
     * @return Collection A collection of active charging sessions.
     */
    public function findActiveByChargingPoint(int $chargingPointId): Collection;

    /**
     * Create a new charging session.
     *
     * @param array $data Data for the new session.
     * @return ChargingSession The created session.
     */
    public function create(array $data): ChargingSession;

    /**
     * Update an existing charging session.
     *
     * @param int $id The ID of the session to update.
     * @param array $data Data to update the session with.
     * @return bool True if update was successful, false otherwise.
     */
    public function update(int $id, array $data): bool;

    /**
     * Get all charging sessions.
     *
     * @return Collection A collection of all charging sessions.
     */
    public function all(): Collection;
}