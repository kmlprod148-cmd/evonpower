<?php

namespace App\Repositories\Eloquent;

use App\Core\Repositories\Eloquent\BaseEloquentRepository;
use App\Models\Group;
use App\Repositories\Interfaces\GroupRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class EloquentGroupRepository
 *
 * @package App\Repositories\Eloquent
 */
class EloquentGroupRepository extends BaseEloquentRepository implements GroupRepositoryInterface
{
    /**
     * EloquentGroupRepository constructor.
     *
     * @param Group $model
     */
    public function __construct(Group $model)
    {
        parent::__construct($model);
    }

    /**
     * Get groups with their relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllWithRelations(array $relations = []): Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Find a group by ID with relations.
     *
     * @param int $id
     * @param array $relations
     * @return Group|null
     */
    public function findWithRelations(int $id, array $relations = []): ?Group
    {
        return $this->model->with($relations)->find($id);
    }

    // Implement other specific methods from GroupRepositoryInterface if needed
}