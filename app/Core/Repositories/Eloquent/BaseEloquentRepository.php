<?php

namespace App\Core\Repositories\Eloquent;

use App\Core\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Abstract Class BaseEloquentRepository
 *
 * Provides common Eloquent repository operations.
 *
 * @package App\Core\Repositories\Eloquent
 */
abstract class BaseEloquentRepository implements BaseRepositoryInterface
{
    /**
     * The Eloquent model instance.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * BaseEloquentRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Find a model by its primary key.
     *
     * @param int $id
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        return $this->model->select($columns)->with($relations)->find($id)?->append($appends);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param int $id
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Model
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        return $this->model->select($columns)->with($relations)->findOrFail($id)->append($appends);
    }

    /**
     * Get all models.
     *
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Collection
     */
    public function all(array $columns = ['*'], array $relations = [], array $appends = []): Collection
    {
        return $this->model->select($columns)->with($relations)->get()->append($appends);
    }

    /**
     * Create a new model instance.
     *
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes): Model
    {
        return $this->model->create($attributes);
    }

    /**
     * Update a model instance.
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
     * Delete a model instance.
     *
     * @param Model $model
     * @return bool|null
     */
    public function delete(Model $model): ?bool
    {
        return $model->delete();
    }

    /**
     * Find a model by a specific column and value.
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
        return $this->model->select($columns)->with($relations)->where($column, $value)->first()?->append($appends);
    }

    /**
     * Get models by a specific column and value.
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
        return $this->model->select($columns)->with($relations)->where($column, $value)->get()->append($appends);
    }

    // Add more common Eloquent methods as needed
}