<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface AppPermissionRepositoryInterface
{
    public function all(): Collection;
    public function paginate(int $perPage = 15);
    public function find(int $id): ?Model;
    public function findOrFail(int $id): Model;
    public function create(array $data): Model;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function findBy(string $field, $value): ?Model;
    public function findWhere(array $criteria): Collection;
}