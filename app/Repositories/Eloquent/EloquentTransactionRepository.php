<?php

namespace App\Repositories\Eloquent;

use App\Core\Repositories\Eloquent\BaseEloquentRepository;
use App\Models\Transaction;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class EloquentTransactionRepository
 *
 * @package App\Repositories\Eloquent
 */
class EloquentTransactionRepository extends BaseEloquentRepository implements TransactionRepositoryInterface
{
    /**
     * EloquentTransactionRepository constructor.
     *
     * @param Transaction $model
     */
    public function __construct(Transaction $model)
    {
        parent::__construct($model);
    }

    /**
     * Get transactions with their relations.
     *
     * @param array $relations
     * @return Collection
     */
    public function getAllWithRelations(array $relations = []): Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Find a transaction by ID with relations.
     *
     * @param int $id
     * @param array $relations
     * @return Transaction|null
     */
    public function findWithRelations(int $id, array $relations = []): ?Transaction
    {
        return $this->model->with($relations)->find($id);
    }

    // Implement other specific methods from TransactionRepositoryInterface if needed
}