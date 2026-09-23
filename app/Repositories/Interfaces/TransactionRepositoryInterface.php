<?php

namespace App\Repositories\Interfaces;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    /**
     * Get all transactions
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Get paginated transactions
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a transaction by ID
     *
     * @param int $id
     * @return Model|null
     */
    public function find(int $id): ?Model;

    /**
     * Find a transaction by ID or fail
     *
     * @param int $id
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Model;

    /**
     * Create a new transaction
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update a transaction
     *
     * @param int $id
     * @param array $data
     * @return Model|null
     */
    public function update(int $id, array $data): ?Model;

    /**
     * Delete a transaction
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Find a transaction by field
     *
     * @param string $field
     * @param mixed $value
     * @return Model|null
     */
    public function findBy(string $field, $value): ?Model;

    /**
     * Find transactions by criteria
     *
     * @param array $criteria
     * @return Collection
     */
    public function findWhere(array $criteria): Collection;

    /**
     * Find a transaction by its transaction ID (string identifier).
     *
     * @param string $transactionId
     * @return Transaction|null
     */
    public function findByTransactionId(string $transactionId): ?Transaction;

    /**
     * Get active transactions for a user.
     *
     * @param int $userId
     * @return Collection<int, Transaction>
     */
    public function getActiveByUserId(int $userId): Collection;

    /**
     * Get transaction history for a user with pagination.
     *
     * @param int $userId
     * @param int $limit
     * @return LengthAwarePaginator<Transaction>
     */
    public function getHistoryByUserId(int $userId, int $limit): LengthAwarePaginator;

    /**
     * Get transactions by charging point
     *
     * @param int $chargingPointId
     * @param int $limit
     * @return LengthAwarePaginator<Transaction>
     */
    public function getByChargingPointId(int $chargingPointId, int $limit = 15): LengthAwarePaginator;

    /**
     * Get transactions by status
     *
     * @param string $status
     * @param int $limit
     * @return LengthAwarePaginator<Transaction>
     */
    public function getByStatus(string $status, int $limit = 15): LengthAwarePaginator;

    /**
     * Get transactions by date range
     *
     * @param string $startDate
     * @param string $endDate
     * @param int $limit
     * @return LengthAwarePaginator<Transaction>
     */
    public function getByDateRange(string $startDate, string $endDate, int $limit = 15): LengthAwarePaginator;

    /**
     * Mark a transaction as complete and update its details.
     *
     * @param int $id
     * @param array $data
     * @return Transaction|null
     */
    public function complete(int $id, array $data): ?Transaction;

    /**
     * Get transactions pending commission payment
     *
     * @param string $commissionType
     * @return Collection<int, Transaction>
     */
    public function getPendingCommissions(string $commissionType = 'admin'): Collection;

    /**
     * Get total revenue by date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return float
     */
    public function getTotalRevenue(string $startDate, string $endDate): float;

    /**
     * Get total energy delivered by date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return float
     */
    public function getTotalEnergyDelivered(string $startDate, string $endDate): float;

    /**
     * Get transaction statistics
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getStatistics(string $startDate, string $endDate): array;

    /**
     * Search transactions
     *
     * @param string $query
     * @param int $limit
     * @return LengthAwarePaginator<Transaction>
     */
    public function search(string $query, int $limit = 15): LengthAwarePaginator;
}