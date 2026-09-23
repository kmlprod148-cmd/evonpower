<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseRepository
{
    /**
     * The model instance
     */
    protected Model $model;

    /**
     * Create a new repository instance
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get a new instance of the model
     *
     * @return Model
     * @throws \Exception
     */
    public function makeModel(): Model
    {
        $model = app($this->model->getMorphClass());

        if (!$model instanceof Model) {
            throw new \Exception("Class {$this->model->getMorphClass()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $model;
    }

    /**
     * Find a model by ID
     */
    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Find a model by ID or fail
     */
    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Get all models
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Create a new model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Met à jour un modèle existant (signature interface)
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes);
        $model->save();
        return $model;
    }

    /**
     * Met à jour un modèle par son ID (ancienne logique)
     */
    public function updateById(int $id, array $data): ?Model
    {
        $model = $this->find($id);
        if ($model) {
            $model->update($data);
            return $model->fresh();
        }
        return null;
    }

    /**
     * Supprime un modèle existant (signature interface)
     */
    public function delete(Model $model): ?bool
    {
        return $model->delete();
    }

    /**
     * Supprime un modèle par son ID (ancienne logique)
     */
    public function deleteById(int $id): bool
    {
        $model = $this->find($id);
        if ($model) {
            return $model->delete();
        }
        return false;
    }

    /**
     * Paginate models
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Count models
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Check if model exists
     */
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Get a new query builder instance
     */
    protected function newQuery()
    {
        return $this->model->newQuery();
    }
}