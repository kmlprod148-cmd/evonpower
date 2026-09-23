<?php

namespace App\Repositories;

use App\Models\ChargingPoint;
use App\Repositories\Interfaces\ChargingPointRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ChargingPointRepository implements ChargingPointRepositoryInterface
{
    /**
     * Get all ChargingPoints.
     *
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Collection
     */
    public function all(array $columns = ['*'], array $relations = [], array $appends = []): Collection
    {
        $query = ChargingPoint::select($columns);
        
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

    /**
     * Paginate ChargingPoints.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return ChargingPoint::paginate($perPage);
    }

    /**
     * Find a ChargingPoint by ID.
     *
     * @param int $id
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        $query = ChargingPoint::select($columns);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        $model = $query->find($id);
        
        if ($model && !empty($appends)) {
            $model->append($appends);
        }
        
        return $model;
    }

    /**
     * Find a ChargingPoint by ID or throw an exception.
     *
     * @param int $id
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        $query = ChargingPoint::select($columns);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        $model = $query->findOrFail($id);
        
        if (!empty($appends)) {
            $model->append($appends);
        }
        
        return $model;
    }

    /**
     * Create a new ChargingPoint.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return ChargingPoint::create($data);
    }

    /**
     * Update an existing ChargingPoint.
     *
     * @param Model $model
     * @param array $attributes
     * @return Model
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);
        return $model;
    }

    /**
     * Delete a ChargingPoint.
     *
     * @param Model $model
     * @return bool|null
     */
    public function delete(Model $model): ?bool
    {
        return $model->delete();
    }

    /**
     * Find a ChargingPoint by a specific column and value.
     *
     * @param string $column
     * @param mixed $value
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Model|null
     */
    public function findBy(string $column, $value, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        $query = ChargingPoint::select($columns);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        $model = $query->where($column, $value)->first();
        
        if ($model && !empty($appends)) {
            $model->append($appends);
        }
        
        return $model;
    }

    /**
     * Get ChargingPoints by a specific column and value.
     *
     * @param string $column
     * @param mixed $value
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Collection
     */
    public function getBy(string $column, $value, array $columns = ['*'], array $relations = [], array $appends = []): Collection
    {
        $query = ChargingPoint::select($columns);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        $models = $query->where($column, $value)->get();
        
        if (!empty($appends)) {
            $models->each(function ($model) use ($appends) {
                $model->append($appends);
            });
        }
        
        return $models;
    }

    /**
     * Find ChargingPoints matching the given criteria.
     *
     * @param array $criteria
     * @return Collection
     */
    public function findWhere(array $criteria): Collection
    {
        $query = ChargingPoint::query();
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }
        return $query->get();
    }

    /**
     * Get all charging points with their relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllWithRelations(array $relations = []): Collection
    {
        return ChargingPoint::with($relations)->get();
    }

    /**
     * Find a ChargingPoint by ID with specified relations.
     *
     * @param int $id
     * @param array $relations
     * @return ChargingPoint|null
     */
    public function findWithRelations(int $id, array $relations = []): ?ChargingPoint
    {
        return ChargingPoint::withTrashed()->with($relations)->find($id);
    }

    /**
     * Get ChargingPoints visible to a user.
     *
     * @param mixed $user
     * @return Collection
     */
    public function getByUser($user): Collection
    {
        // This implementation assumes a scope 'visibleToUser' exists on the ChargingPoint model
        return ChargingPoint::visibleToUser($user)->get();
    }

    /**
     * Update the status of a ChargingPoint.
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        $chargingPoint = ChargingPoint::find($id);
        if ($chargingPoint) {
            // Assuming an updateStatus method exists on the ChargingPoint model
            return $chargingPoint->updateStatus($status);
        }
        return false;
    }
}