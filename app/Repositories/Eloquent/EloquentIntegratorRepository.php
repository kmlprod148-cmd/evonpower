<?php

namespace App\Repositories\Eloquent;

use App\Core\Repositories\Eloquent\BaseEloquentRepository;
use App\Models\Integrator;
use App\Repositories\Interfaces\IntegratorRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class EloquentIntegratorRepository
 *
 * @package App\Repositories\Eloquent
 */
class EloquentIntegratorRepository extends BaseEloquentRepository implements IntegratorRepositoryInterface
{
    /**
     * EloquentIntegratorRepository constructor.
     *
     * @param Integrator $model
     */
    public function __construct(Integrator $model)
    {
        parent::__construct($model);
    }

    /**
     * Get integrators with their relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllWithRelations(array $relations = []): Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Find an integrator by ID with relations.
     *
     * @param int $id
     * @param array $relations
     * @return Integrator|null
     */
    public function findWithRelations(int $id, array $relations = []): ?Integrator
    {
        return $this->model->with($relations)->find($id);
    }

    // Implement other specific methods from IntegratorRepositoryInterface if needed
}