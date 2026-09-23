<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface AdminNotificationRepositoryInterface
{
    public function all(): Collection;
    public function paginate(int $perPage = 15);
    public function find(int $id): ?Model;
    public function findOrFail(int $id): Model;
    public function create(array $data): Model;
    public function update(int $id, array $data): ?Model;
    public function delete(int $id): bool;
    public function findBy(string $field, $value): ?Model;
    public function findWhere(array $criteria): Collection;
    public function getAllForRoles(array $roles): Collection;
    public function getUnreadForRoles(array $roles): Collection;
    public function markAsRead(int $id): bool;
    public function markAllAsRead(array $roles): int;
}