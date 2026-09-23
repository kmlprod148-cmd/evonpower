<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface GroupRepositoryInterface
{
    public function find(int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model;
    public function findOrFail(int $id, array $columns = ['*'], array $relations = [], array $appends = []): Model;
    public function findWithRelations(int $id, array $relations = []): ?\App\Models\Group;
    public function create(array $data): Model;
    public function update(Model $model, array $attributes): Model;
    public function delete(Model $model): ?bool;
    public function getFilteredForUser(\App\Models\User $user, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    public function paginate(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    public function visibleToUser(\App\Models\User $user): \Illuminate\Database\Eloquent\Builder;
}