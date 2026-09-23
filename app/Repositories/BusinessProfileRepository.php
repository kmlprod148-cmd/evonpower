<?php

namespace App\Repositories;

use App\Models\BusinessProfile;
use App\Repositories\Interfaces\BusinessProfileRepositoryInterface;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class BusinessProfileRepository implements BusinessProfileRepositoryInterface
{
    protected BusinessProfile $model;

    public function __construct(BusinessProfile $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function create(array $data): Model
    {
        Log::info('BusinessProfileRepository::create called', $data);
        
        $model = $this->model->create($data);
        
        Log::info('BusinessProfileRepository::create model created', ['model_id' => $model->id]);
        
        return $model;
    }

    public function update(int $id, array $data): ?Model
    {
        $model = $this->find($id);
        if ($model) {
            $model->update($data);
            return $model->fresh();
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $model = $this->find($id);
        if ($model) {
            return $model->delete();
        }
        return false;
    }

    public function getFilteredProfiles(
        ?string $search = null,
        ?string $visibility = null,
        ?string $creator = null,
        int $perPage = 10
    ) {
        $query = $this->model->query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($visibility !== null) {
            $query->where('is_public', $visibility);
        }

        if ($creator) {
            $query->whereHas('creator', function ($q) use ($creator) {
                $q->where('name', 'like', '%' . $creator . '%');
            });
        }

        return $query->paginate($perPage);
    }

    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function findBy(string $field, $value): ?\Illuminate\Database\Eloquent\Model
    {
        return $this->model->where($field, $value)->first();
    }

    public function findWhere(array $criteria): \Illuminate\Support\Collection
    {
        $query = $this->model->query();
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }
        return $query->get();
    }

    public function getTotalCount(): int
    {
        return $this->model->count();
    }

    public function getPublicCount(): int
    {
        return $this->model->where('is_public', true)->count();
    }

    public function getActiveCount(): int
    {
        return $this->model->where('is_active', true)->count();
    }
}