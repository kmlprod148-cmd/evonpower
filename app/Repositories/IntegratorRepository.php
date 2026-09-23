<?php

namespace App\Repositories;

use App\Models\Integrator;
use App\Repositories\Interfaces\IntegratorRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class IntegratorRepository implements IntegratorRepositoryInterface
{
    protected Integrator $model;

    public function __construct(Integrator $model)
    {
        $this->model = $model;
    }

    /**
     * Find a model by field
     */
    public function findBy(string $column, $value, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        return $this->model->where($column, $value)->first($columns);
    }

    /**
     * Find models by criteria
     */
    public function findWhere(array $criteria): \Illuminate\Support\Collection
    {
        return $this->model->where($criteria)->get();
    }
    public function all(array $columns = ['*'], array $relations = [], array $appends = []): \Illuminate\Support\Collection
    {
        return $this->model->all($columns);
    }

    public function paginate(int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function find(int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        return $this->model->find($id, $columns);
    }

    public function findOrFail(int $id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        return $this->model->findOrFail($id, $columns);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
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

    /**
     * Get integrators with their relations.
     *
     * @param array $relations
     * @return \Illuminate\Support\Collection
     */
    public function getAllWithRelations(array $relations = []): \Illuminate\Support\Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Find an integrator by ID with relations.
     *
     * @param int $id
     * @param array $relations
     * @return \App\Models\Integrator|null
     */
    public function findWithRelations(int $id, array $relations = []): ?Integrator
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
     * @return \Illuminate\Support\Collection
     */
    public function getBy(string $column, $value, array $columns = ['*'], array $relations = [], array $appends = []): \Illuminate\Support\Collection
    {
        return $this->model->where($column, $value)->get($columns);
    }

    // Add any specific methods for IntegratorRepository here, e.g., filtering
    public function filterAndPaginate(array $filters, int $perPage = 10)
    {
        $query = $this->model->query();

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        if (isset($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get integrators accessible to the given user.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Support\Collection
     */
    public function getAccessibleIntegrators(\App\Models\User $user): \Illuminate\Support\Collection
    {
        if ($user->hasRole('admin')) {
            return $this->model->all();
        }

        // Assuming non-admin users might not have access to list all integrators
        // or access is handled at a higher level. Return empty for now.
        return collect();
    }

    /**
     * Retourne la classe du modèle associé à ce repository.
     */
    public function model()
    {
        return \App\Models\Integrator::class;
    }
}