<?php

namespace App\Repositories\Eloquent;

use App\Core\Repositories\Eloquent\BaseEloquentRepository;
use App\Models\PricingPlan;
use App\Repositories\Interfaces\PricingPlanRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class EloquentPricingPlanRepository
 *
 * @package App\Repositories\Eloquent
 */
class EloquentPricingPlanRepository extends BaseEloquentRepository implements PricingPlanRepositoryInterface
{
    /**
     * EloquentPricingPlanRepository constructor.
     *
     * @param PricingPlan $model
     */
    public function __construct(PricingPlan $model)
    {
        parent::__construct($model);
    }

    /**
     * Get pricing plans with their relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllWithRelations(array $relations = []): Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Find a pricing plan by ID with relations.
     *
     * @param int $id
     * @param array $relations
     * @return PricingPlan|null
     */
    public function findWithRelations(int $id, array $relations = []): ?PricingPlan
    {
        return $this->model->with($relations)->find($id);
    }

    // Implement other specific methods from PricingPlanRepositoryInterface if needed
}