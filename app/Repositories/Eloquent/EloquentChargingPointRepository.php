<?php

namespace App\Repositories\Eloquent;

use App\Core\Repositories\Eloquent\BaseEloquentRepository;
use App\Models\ChargingPoint;
use App\Repositories\Interfaces\ChargingPointRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class EloquentChargingPointRepository
 *
 * @package App\Repositories\Eloquent
 */
class EloquentChargingPointRepository extends BaseEloquentRepository implements ChargingPointRepositoryInterface
{
    /**
     * EloquentChargingPointRepository constructor.
     *
     * @param ChargingPoint $model
     */
    public function __construct(ChargingPoint $model)
    {
        parent::__construct($model);
    }

    /**
     * Get charging points with their relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllWithRelations(array $relations = []): Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Find a charging point by ID with relations.
     *
     * @param int $id
     * @param array $relations
     * @return ChargingPoint|null
     */
    public function findWithRelations(int $id, array $relations = []): ?ChargingPoint
    {
        return $this->model->with($relations)->find($id);
    }

    // Implement other specific methods from ChargingPointRepositoryInterface if needed
}