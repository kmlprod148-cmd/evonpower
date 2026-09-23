<?php

namespace App\Repositories;

use App\Models\PricingPlan;
use App\Repositories\Interfaces\PricingPlanRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class PricingPlanRepository implements PricingPlanRepositoryInterface
{
    public function find(int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        $query = PricingPlan::select($columns);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        $model = $query->find($id);
        
        if ($model && !empty($appends)) {
            $model->append($appends);
        }
        
        return $model;
    }

    public function findOrFail(int $id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        $query = PricingPlan::select($columns);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        $model = $query->findOrFail($id);
        
        if (!empty($appends)) {
            $model->append($appends);
        }
        
        return $model;
    }

    public function all(array $columns = ['*'], array $relations = [], array $appends = []): Collection
    {
        $query = PricingPlan::select($columns);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        $models = $query->get();
        
        if (!empty($appends)) {
            $models->each(function ($model) use ($appends) {
                $model->append($appends);
            });
        }
        
        return $models;
    }

    public function create(array $attributes): Model
    {
        return PricingPlan::create($attributes);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);
        return $model->fresh();
    }

    public function delete(Model $model): ?bool
    {
        return $model->delete();
    }

    public function findBy(string $column, $value, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        $query = PricingPlan::select($columns)->where($column, $value);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        $model = $query->first();
        
        if ($model && !empty($appends)) {
            $model->append($appends);
        }
        
        return $model;
    }

    public function getBy(string $column, $value, array $columns = ['*'], array $relations = [], array $appends = []): Collection
    {
        $query = PricingPlan::select($columns)->where($column, $value);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        $models = $query->get();
        
        if (!empty($appends)) {
            $models->each(function ($model) use ($appends) {
                $model->append($appends);
            });
        }
        
        return $models;
    }

    public function getDefaultPlanForChargingPoint($chargingPointId)
    {
        // Implement your logic here
        return PricingPlan::where('is_default', true)->first();
    }

    public function getAvailablePlansForChargingPoint($chargingPoint)
    {
        // Implement your logic here
        $pricingPlans = PricingPlan::where('is_active', true)->get();
        \Log::info('Fetched available pricing plans', ['count' => $pricingPlans->count()]);
        return $pricingPlans;
    }

    /**
     * Get pricing plans with their relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllWithRelations(array $relations = []): Collection
    {
        return PricingPlan::with($relations)->get();
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
        return PricingPlan::with($relations)->find($id);
    }
}