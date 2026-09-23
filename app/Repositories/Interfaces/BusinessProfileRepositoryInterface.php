<?php

namespace App\Repositories\Interfaces;

use App\Models\BusinessProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface BusinessProfileRepositoryInterface
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
    public function getFilteredProfiles(
        ?string $search = null,
        ?string $visibility = null,
        ?string $creator = null,
        int $perPage = 10
    );
    public function getTotalCount(): int;
    public function getPublicCount(): int;
    public function getActiveCount(): int;
}