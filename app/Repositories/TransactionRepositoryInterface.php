<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    /**
     * Find a transaction by its ID.
     *
     * @param int $id
     * @return Transaction|null
     */
    public function findById(int $id): ?Transaction;

    /**
     * Find a transaction by its transaction ID (string identifier).
     *
     * @param string $transactionId
     * @return Transaction|null
     */
    public function findByTransactionId(string $transactionId): ?Transaction;

    /**
     * Get all transactions.
     *
     * @return Collection<int, Transaction>
     */
    public function getAll(): Collection;

    /**
     * Get all transactions with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator<Transaction>
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

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
     * Create a new transaction.
     *
     * @param array $data
     * @return Transaction
     */
    public function create(array $data): Transaction;

    /**
     * Update an existing transaction.
     *
     * @param int $id
     * @param array $data
     * @return Transaction|null
     */
    public function update(int $id, array $data): ?Transaction;

    /**
     * Delete a transaction by its ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Mark a transaction as complete and update its details.
     *
     * @param int $id
     * @param array $data
     * @return Transaction|null
     */
    public function complete(int $id, array $data): ?Transaction;
}