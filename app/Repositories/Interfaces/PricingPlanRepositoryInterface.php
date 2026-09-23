<?php

namespace App\Repositories\Interfaces;

use App\Core\Repositories\Interfaces\BaseRepositoryInterface;
use App\Models\PricingPlan;
use Illuminate\Support\Collection;

/**
 * Interface PricingPlanRepositoryInterface
 *
 * @package App\Repositories\Interfaces
 */
interface PricingPlanRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get pricing plans with their relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllWithRelations(array $relations = []): Collection;

    /**
     * Find a pricing plan by ID with relations.
     *
     * @param int $id
     * @param array $relations
     * @return PricingPlan|null
     */
    public function findWithRelations(int $id, array $relations = []): ?PricingPlan;

    // Add specific methods for PricingPlan data access if needed
}