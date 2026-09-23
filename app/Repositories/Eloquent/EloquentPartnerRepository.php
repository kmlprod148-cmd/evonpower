<?php

namespace App\Repositories\Eloquent;

use App\Core\Repositories\Eloquent\BaseEloquentRepository;
use App\Models\Partner;
use App\Repositories\Interfaces\PartnerRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class EloquentPartnerRepository
 *
 * @package App\Repositories\Eloquent
 */
class EloquentPartnerRepository extends BaseEloquentRepository implements PartnerRepositoryInterface
{
    /**
     * EloquentPartnerRepository constructor.
     *
     * @param Partner $model
     */
    public function __construct(Partner $model)
    {
        parent::__construct($model);
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
     * Paginate the model results.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    // Implement other specific methods from PartnerRepositoryInterface if needed
}