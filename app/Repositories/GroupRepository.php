<?php

namespace App\Repositories;

use App\Models\Group;
use App\Models\User;
use App\Repositories\Interfaces\GroupRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class GroupRepository extends BaseRepository implements GroupRepositoryInterface
{
    public function __construct(Group $model)
    {
        parent::__construct($model);
    }

    public function find(int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        $model = $this->model->select($columns)->with($relations)->find($id);
        if ($model && !empty($appends)) {
            $model->append($appends);
        }
        return $model;
    }

    public function findOrFail(int $id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        $model = $this->model->select($columns)->with($relations)->findOrFail($id);
        if (!empty($appends)) {
            $model->append($appends);
        }
        return $model;
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes);
        $model->save();
        return $model;
    }

    // Pour garder la logique existante par ID si besoin
    public function updateById(int $id, array $data): ?Model
    {
        $model = $this->model->find($id);
        if (!$model) {
            return null;
        }
        $model->fill($data);
        $model->save();
        return $model;
    }

    public function delete(Model $model): ?bool
    {
        return $model->delete();
    }

    // Pour garder la logique existante par ID si besoin
    public function deleteById(int $id): bool
    {
        $model = $this->model->find($id);
        if (!$model) {
            return false;
        }
        return $model->delete();
    }

    public function getFilteredForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $this->visibleToUser($user);
        
        // Apply additional filters if provided
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if (isset($filters['type']) && !empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->paginate(15);
    }

    public function paginate(int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function visibleToUser(User $user): Builder
    {
        $query = $this->model->newQuery();

        if ($user->hasRole('admin')) {
            return $query; // Admin can see all groups
        }

        if ($user->hasRole('integrator')) {
            return $query->where('integrator_id', $user->integrator_id);
        }

        if ($user->hasRole('partner') || $user->hasRole('operator')) {
            return $query->where('partner_id', $user->partner_id);
        }

        // Default: user can only see their own groups
        return $query->where('user_id', $user->id);
    }

    public function all(array $columns = ['*'], array $relations = [], array $appends = []): Collection
    {
        $models = $this->model->select($columns)->with($relations)->get();
        if (!empty($appends)) {
            $models->each(function($model) use ($appends) {
                $model->append($appends);
            });
        }
        return $models;
    }

    public function findBy(string $column, $value, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        $model = $this->model->where($column, $value)->select($columns)->with($relations)->first();
        if ($model && !empty($appends)) {
            $model->append($appends);
        }
        return $model;
    }

    public function getBy(string $column, $value, array $columns = ['*'], array $relations = [], array $appends = []): Collection
    {
        $models = $this->model->where($column, $value)->select($columns)->with($relations)->get();
        if (!empty($appends)) {
            $models->each(function($model) use ($appends) {
                $model->append($appends);
            });
        }
        return $models;
    }

    public function getAllWithRelations(array $relations = []): Collection
    {
        return $this->all(columns: ['*'], relations: $relations);
    }

    public function findWithRelations(int $id, array $relations = []): ?Group
    {
        return $this->find(id: $id, relations: $relations);
    }

    public function model()
    {
        return \App\Models\Group::class;
    }
}