<?php

namespace App\Core\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Interface BaseRepositoryInterface
 *
 * @package App\Core\Repositories\Interfaces
 */
interface BaseRepositoryInterface
{
    /**
     * Find a model by its primary key.
     *
     * @param int $id
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model;

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param int $id
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*'], array $relations = [], array $appends = []): Model;

    /**
     * Get all models.
     *
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Collection
     */
    public function all(array $columns = ['*'], array $relations = [], array $appends = []): Collection;

    /**
     * Create a new model instance.
     *
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes): Model;

    /**
     * Update a model instance.
     *
     * @param Model $model
     * @param array $attributes
     * @return Model
     */
    public function update(Model $model, array $attributes): Model;

    /**
     * Delete a model instance.
     *
     * @param Model $model
     * @return bool|null
     */
    public function delete(Model $model): ?bool;

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
    public function findBy(string $column, $value, array $columns = ['*'], array $relations = [], array $appends = []): ?Model;

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
    public function getBy(string $column, $value, array $columns = ['*'], array $relations = [], array $appends = []): Collection;

    // Add more common repository methods as needed (e.g., pagination, filtering)
}