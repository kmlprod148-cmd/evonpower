<?php

namespace App\Repositories\Interfaces;

use App\Core\Repositories\Interfaces\BaseRepositoryInterface;
use App\Models\Integrator;
use Illuminate\Support\Collection;

/**
 * Interface IntegratorRepositoryInterface
 *
 * @package App\Repositories\Interfaces
 */
interface IntegratorRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get integrators with their relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllWithRelations(array $relations = []): Collection;

    /**
     * Find an integrator by ID with relations.
     *
     * @param int $id
     * @param array $relations
     * @return Integrator|null
     */
    public function findWithRelations(int $id, array $relations = []): ?Integrator;

    // Add specific methods for Integrator data access if needed
}