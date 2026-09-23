<?php

namespace App\Core\Services;

use App\Core\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Exceptions\CustomServiceException; // Ensure this exception class exists or create it

/**
 * Abstract Class BaseService
 *
 * Provides common service operations and transaction management for resources.
 *
 * @package App\Core\Services
 */
abstract class BaseService
{
    /**
     * The repository instance.
     *
     * @var BaseRepositoryInterface
     */
    protected BaseRepositoryInterface $repository;

    /**
     * BaseService constructor.
     *
     * @param BaseRepositoryInterface $repository
     */
    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get the repository instance.
     *
     * @return BaseRepositoryInterface
     */
    public function getRepository(): BaseRepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Retrieve all resources.
     *
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->repository->all($columns, $relations);
    }

    /**
     * Find a resource by its ID.
     *
     * @param int $id
     * @param array $columns
     * @param array $relations
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->repository->find($id, $columns, $relations);
    }

    /**
     * Find a resource by its ID or throw an exception.
     *
     * @param int $id
     * @param array $columns
     * @param array $relations
     * @return Model
     * @throws CustomServiceException
     */
    public function findOrFail(int $id, array $columns = ['*'], array $relations = []): Model
    {
        $model = $this->repository->find($id, $columns, $relations);
        if (!$model) {
            throw new CustomServiceException("Resource with ID {$id} not found.");
        }
        return $model;
    }

    /**
     * Create a new resource.
     *
     * @param array $data
     * @return Model
     * @throws CustomServiceException
     */
    public function create(array $data): Model
    {
        DB::beginTransaction();
        try {
            $resource = $this->repository->create($data);
            DB::commit();
            return $resource;
        } catch (Throwable $e) {
            DB::rollBack();
            throw new CustomServiceException("Failed to create resource: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update an existing resource.
     *
     * @param int $id
     * @param array $data
     * @return Model
     * @throws CustomServiceException
     */
    public function update(int $id, array $data): Model
    {
        DB::beginTransaction();
        try {
            $resource = $this->findOrFail($id);
            $this->repository->update($id, $data);
            DB::commit();
            return $resource->fresh(); // Return the updated model
        } catch (Throwable $e) {
            DB::rollBack();
            throw new CustomServiceException("Failed to update resource with ID {$id}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete a resource by its ID.
     *
     * @param int $id
     * @return bool
     * @throws CustomServiceException
     */
    public function delete(int $id): bool
    {
        DB::beginTransaction();
        try {
            $this->findOrFail($id); // Ensure resource exists before attempting delete
            $result = $this->repository->delete($id);
            DB::commit();
            return $result;
        } catch (Throwable $e) {
            DB::rollBack();
            throw new CustomServiceException("Failed to delete resource with ID {$id}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get paginated resources.
     *
     * @param int $perPage
     * @param array $filters
     * @param array $columns
     * @param array $relations
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $filters = [], array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters, $columns, $relations);
    }
}