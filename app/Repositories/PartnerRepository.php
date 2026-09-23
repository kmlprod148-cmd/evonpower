<?php

namespace App\Repositories;

use App\Models\Partner;
use App\Repositories\Interfaces\PartnerRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PartnerRepository implements PartnerRepositoryInterface
{
    protected Partner $model;

    public function __construct(Partner $model)
    {
        $this->model = $model;
    }

    public function all(array $columns = ['*'], array $relations = [], array $appends = []): Collection
    {
        return $this->model->with($relations)->get($columns)->append($appends);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function find(int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        return $this->model->with($relations)->find($id, $columns)->append($appends);
    }

    public function findOrFail(int $id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        return $this->model->with($relations)->findOrFail($id, $columns)->append($appends);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update($modelOrId, array $attributes): Model
    {
        // Si on reçoit un ID, on récupère le modèle
        if (is_numeric($modelOrId)) {
            $model = $this->model->findOrFail($modelOrId);
        } else {
            $model = $modelOrId;
        }
        $model->update($attributes);
        return $model->fresh();
    }

    public function delete($modelOrId): bool
    {
        // Si on reçoit un ID, on récupère le modèle
        if (is_numeric($modelOrId)) {
            $model = $this->model->findOrFail($modelOrId);
        } else {
            $model = $modelOrId;
        }
        return $model->delete();
    }

    /**
     * Get partners with their relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllWithRelations(array $relations = []): Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Find a partner by ID with relations.
     *
     * @param int $id
     * @param array $relations
     * @return Partner|null
     */
    public function findWithRelations(int $id, array $relations = []): ?Partner
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * Find a model by field
     */
    public function findBy(string $field, $value, array $columns = ['*'], array $relations = [], array $appends = []): ?\Illuminate\Database\Eloquent\Model
    {
        return $this->model->where($field, $value)->with($relations)->first($columns)->append($appends);
    }

    /**
     * Find models by criteria
     */
    public function findWhere(array $criteria): \Illuminate\Support\Collection
    {
        return $this->model->where($criteria)->get();
    }

    // Add any specific methods for PartnerRepository here

    /**
     * Get partners with their relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllWithRelations(array $relations = []): Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Find a partner by ID with relations.
     *
     * @param int $id
     * @param array $relations
     * @return Partner|null
     */
    public function findWithRelations(int $id, array $relations = []): ?Partner
    {
        return $this->model->with($relations)->find($id);
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
        return $this->model->where($column, $value)->with($relations)->get($columns)->append($appends);
    }

    /**
     * Get partners accessible to the given user.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Support\Collection
     */
    public function getAccessiblePartners(\App\Models\User $user): \Illuminate\Support\Collection
    {
        if ($user->hasRole('admin')) {
            return $this->model->all();
        }

        // Assuming non-admin users might not have access to list all partners
        // or access is handled at a higher level. Return empty for now.
        return collect();
    }
}