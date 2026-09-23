<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionRepository implements TransactionRepositoryInterface
{
    protected Transaction $model;

    public function __construct(Transaction $model)
    {
        $this->model = $model;
    }

    /**
     * Get all transactions
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return $this->model->with(['chargingPoint', 'user', 'connector'])->get();
    }

    /**
     * Get paginated transactions
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['chargingPoint', 'user', 'connector'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a transaction by ID
     *
     * @param int $id
     * @return Model|null
     */
    public function find(int $id): ?Model
    {
        return $this->model
            ->with(['chargingPoint', 'user', 'connector', 'commissionPlan'])
            ->find($id);
    }

    /**
     * Find a transaction by ID or fail
     *
     * @param int $id
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Model
    {
        return $this->model
            ->with(['chargingPoint', 'user', 'connector', 'commissionPlan'])
            ->findOrFail($id);
    }

    /**
     * Create a new transaction
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a transaction
     *
     * @param int $id
     * @param array $data
     * @return Model|null
     */
    public function update(int $id, array $data): ?Model
    {
        $model = $this->find($id);
        if ($model) {
            $model->update($data);
            return $model->fresh(['chargingPoint', 'user', 'connector', 'commissionPlan']);
        }
        return null;
    }

    /**
     * Delete a transaction
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $model = $this->find($id);
        if ($model) {
            return $model->delete();
        }
        return false;
    }

    /**
     * Find a transaction by field
     *
     * @param string $field
     * @param mixed $value
     * @return Model|null
     */
    public function findBy(string $field, $value): ?Model
    {
        return $this->model
            ->with(['chargingPoint', 'user', 'connector'])
            ->where($field, $value)
            ->first();
    }

    /**
     * Find transactions by criteria
     *
     * @param array $criteria
     * @return Collection
     */
    public function findWhere(array $criteria): Collection
    {
        $query = $this->model->with(['chargingPoint', 'user', 'connector']);
        
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->get();
    }

    /**
     * Find a transaction by its transaction ID (string identifier).
     *
     * @param string $transactionId
     * @return Transaction|null
     */
    public function findByTransactionId(string $transactionId): ?Transaction
    {
        return $this->model
            ->with(['chargingPoint', 'user', 'connector'])
            ->where('transaction_id', $transactionId)
            ->first();
    }

    /**
     * Get active transactions for a user.
     *
     * @param int $userId
     * @return Collection<int, Transaction>
     */
    public function getActiveByUserId(int $userId): Collection
    {
        return $this->model
            ->with(['chargingPoint', 'connector'])
            ->where('user_id', $userId)
            ->whereIn('status', ['in_progress', 'ongoing', 'pending'])
            ->whereNull('stop_timestamp')
            ->get();
    }

    /**
     * Get transaction history for a user with pagination.
     *
     * @param int $userId
     * @param int $limit
     * @return LengthAwarePaginator<Transaction>
     */
    public function getHistoryByUserId(int $userId, int $limit): LengthAwarePaginator
    {
        return $this->model
            ->with(['chargingPoint', 'connector'])
            ->where('user_id', $userId)
            ->whereNotNull('stop_timestamp')
            ->latest('stop_timestamp')
            ->paginate($limit);
    }

    /**
     * Get transactions by charging point
     *
     * @param int $chargingPointId
     * @param int $limit
     * @return LengthAwarePaginator<Transaction>
     */
    public function getByChargingPointId(int $chargingPointId, int $limit = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['user', 'connector'])
            ->where('charging_point_id', $chargingPointId)
            ->latest()
            ->paginate($limit);
    }

    /**
     * Get transactions by status
     *
     * @param string $status
     * @param int $limit
     * @return LengthAwarePaginator<Transaction>
     */
    public function getByStatus(string $status, int $limit = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['chargingPoint', 'user', 'connector'])
            ->where('status', $status)
            ->latest()
            ->paginate($limit);
    }

    /**
     * Get transactions by date range
     *
     * @param string $startDate
     * @param string $endDate
     * @param int $limit
     * @return LengthAwarePaginator<Transaction>
     */
    public function getByDateRange(string $startDate, string $endDate, int $limit = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['chargingPoint', 'user', 'connector'])
            ->whereBetween('start_timestamp', [$startDate, $endDate])
            ->latest()
            ->paginate($limit);
    }

    /**
     * Mark a transaction as complete and update its details.
     *
     * @param int $id
     * @param array $data
     * @return Transaction|null
     */
    public function complete(int $id, array $data): ?Transaction
    {
        $transaction = $this->find($id);
        if ($transaction) {
            $transaction->update(array_merge($data, ['status' => 'completed']));
            return $transaction->fresh(['chargingPoint', 'user', 'connector', 'commissionPlan']);
        }
        return null;
    }

    /**
     * Get transactions pending commission payment
     *
     * @param string $commissionType ('admin', 'integrator', 'partner')
     * @return Collection<int, Transaction>
     */
    public function getPendingCommissions(string $commissionType = 'admin'): Collection
    {
        $field = "{$commissionType}_commission_paid";
        
        return $this->model
            ->with(['chargingPoint', 'user', 'commissionPlan'])
            ->where('status', 'completed')
            ->where($field, false)
            ->whereNotNull("{$commissionType}_commission")
            ->where("{$commissionType}_commission", '>', 0)
            ->get();
    }

    /**
     * Get total revenue by date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return float
     */
    public function getTotalRevenue(string $startDate, string $endDate): float
    {
        return $this->model
            ->where('status', 'completed')
            ->whereBetween('start_timestamp', [$startDate, $endDate])
            ->sum('price_total') ?? 0.0;
    }

    /**
     * Get total energy delivered by date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return float
     */
    public function getTotalEnergyDelivered(string $startDate, string $endDate): float
    {
        return $this->model
            ->where('status', 'completed')
            ->whereBetween('start_timestamp', [$startDate, $endDate])
            ->sum('energy_delivered') ?? 0.0;
    }

    /**
     * Get transaction statistics
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getStatistics(string $startDate, string $endDate): array
    {
        $baseQuery = $this->model->whereBetween('start_timestamp', [$startDate, $endDate]);
        
        return [
            'total_transactions' => $baseQuery->count(),
            'completed_transactions' => $baseQuery->where('status', 'completed')->count(),
            'ongoing_transactions' => $baseQuery->where('status', 'in_progress')->count(),
            'failed_transactions' => $baseQuery->where('status', 'failed')->count(),
            'total_revenue' => $baseQuery->where('status', 'completed')->sum('price_total') ?? 0.0,
            'total_energy' => $baseQuery->where('status', 'completed')->sum('energy_delivered') ?? 0.0,
            'average_duration' => $baseQuery->where('status', 'completed')->avg('duration') ?? 0,
            'average_energy_per_session' => $baseQuery->where('status', 'completed')->avg('energy_delivered') ?? 0,
        ];
    }

    /**
     * Search transactions
     *
     * @param string $query
     * @param int $limit
     * @return LengthAwarePaginator<Transaction>
     */
    public function search(string $query, int $limit = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['chargingPoint', 'user', 'connector'])
            ->where(function ($q) use ($query) {
                $q->where('transaction_id', 'like', "%{$query}%")
                  ->orWhere('session_id', 'like', "%{$query}%")
                  ->orWhereHas('user', function ($userQuery) use ($query) {
                      $userQuery->where('name', 'like', "%{$query}%")
                               ->orWhere('email', 'like', "%{$query}%");
                  })
                  ->orWhereHas('chargingPoint', function ($cpQuery) use ($query) {
                      $cpQuery->where('name', 'like', "%{$query}%");
                  });
            })
            ->latest()
            ->paginate($limit);
    }
}