<?php

namespace App\Repositories\Interfaces;

use App\Core\Repositories\Interfaces\BaseRepositoryInterface;
use App\Models\Partner;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface PartnerRepositoryInterface
 *
 * @package App\Repositories\Interfaces
 */
interface PartnerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get partners with their relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllWithRelations(array $relations = []): Collection;

    /**
     * Find a partner by ID with relations.
     *
     * @param int $id
     * @param array $relations
     * @return Partner|null
     */
    public function findWithRelations(int $id, array $relations = []): ?Partner;

    /**
     * Paginate the model results.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    // Add specific methods for Partner data access if needed
}