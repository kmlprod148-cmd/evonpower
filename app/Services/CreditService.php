<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditService
{
    protected function getCreditClientRoleNames(): array
    {
        return ['user', 'client'];
    }

    protected function isCreditClient(User $user): bool
    {
        $roles = $user->getRoleNames()
            ->map(fn ($role) => strtolower((string) $role))
            ->toArray();

        $systemRoles = ['admin', 'super_admin', 'integrator', 'operator', 'partner'];
        $clientRoles = $this->getCreditClientRoleNames();

        $hasSystemRole = !empty(array_intersect($roles, $systemRoles));
        $hasClientRole = !empty(array_intersect($roles, $clientRoles));

        return !$hasSystemRole && ($hasClientRole || empty($roles));
    }

    protected function getCreditClientIds(): array
    {
        return User::query()
            ->where(function ($query) {
                $query->whereDoesntHave('roles')
                    ->orWhereHas('roles', function ($roleQuery) {
                        $roleQuery->whereIn('name', $this->getCreditClientRoleNames());
                    });
            })
            ->pluck('id')
            ->all();
    }

    /**
     * Ajouter du credit a un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param float $amount Montant du credit
     * @param string $type Type de credit (bonus, manuel, automatique)
     * @param string|null $commentaire Description/commentaire
     * @param int $createdBy ID de l'utilisateur qui cree le credit
     * @return array
     */
    public function addCredit(int $userId, float $amount, string $type, ?string $commentaire, int $createdBy): array
    {
        try {
            DB::beginTransaction();

            // Verifier que l'utilisateur existe
            $user = User::findOrFail($userId);

            // Verifier que l'utilisateur est bien un client final
            if (!$this->isCreditClient($user)) {
                throw new \Exception('Le credit ne peut etre ajoute qu\'a un client identifie.');
            }

            // Verifier que le createur a les permissions
            $creator = User::findOrFail($createdBy);
            if (!$creator->hasRole(['admin', 'super_admin', 'integrator', 'operator', 'partner'])) {
                throw new \Exception('Seuls les administrateurs, integrateurs, operateurs et partenaires peuvent ajouter du credit.');
            }

            // Valider le montant
            if ($amount <= 0) {
                throw new \InvalidArgumentException('Le montant doit etre superieur a 0.');
            }

            // Valider le type
            if (!in_array($type, ['bonus', 'manuel', 'automatique'], true)) {
                throw new \InvalidArgumentException('Type de credit invalide.');
            }

            // Creer la transaction de credit
            $creditTransaction = CreditTransaction::create([
                'user_id' => $userId,
                'amount' => $amount,
                'type' => $type,
                'commentaire' => $commentaire,
                'created_by' => $createdBy,
            ]);

            // Obtenir ou creer le wallet de l'utilisateur
            $wallet = $user->getOrCreateWallet();

            // Ajouter le credit au wallet
            $walletTransaction = $wallet->credit(
                $amount,
                "Credit {$type}: " . ($commentaire ?: 'Credit ajoute manuellement'),
                [
                    'credit_transaction_id' => $creditTransaction->id,
                    'type' => $type,
                    'created_by' => $createdBy,
                ]
            );

            DB::commit();

            app(\App\Services\ClientBalanceService::class)->invalidate($user);

            Log::info('Credit ajoute avec succes', [
                'user_id' => $userId,
                'amount' => $amount,
                'type' => $type,
                'created_by' => $createdBy,
                'credit_transaction_id' => $creditTransaction->id,
                'wallet_transaction_id' => $walletTransaction->id,
            ]);

            return [
                'success' => true,
                'credit_transaction' => $creditTransaction,
                'wallet_transaction' => $walletTransaction,
                'new_balance' => $wallet->fresh()->balance,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'ajout de credit', [
                'user_id' => $userId,
                'amount' => $amount,
                'type' => $type,
                'created_by' => $createdBy,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Recuperer l'historique des credits pour un utilisateur
     *
     * @param int|null $userId ID de l'utilisateur (null pour tous)
     * @param array $filters Filtres optionnels
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getCreditHistory(?int $userId = null, array $filters = [])
    {
        $query = CreditTransaction::with(['user', 'creator']);

        if ($userId) {
            $user = User::find($userId);
            if ($user && $this->isCreditClient($user)) {
                $query->where('user_id', $userId);
            } else {
                $query->whereRaw('1 = 0');
            }
        } else {
            $clientIds = $this->getCreditClientIds();

            if (empty($clientIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('user_id', $clientIds);
            }
        }

        if (isset($filters['type']) && $filters['type']) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['created_by']) && $filters['created_by']) {
            $query->where('created_by', $filters['created_by']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Obtenir les statistiques des credits
     *
     * @param int|null $userId ID de l'utilisateur (null pour tous)
     * @return array
     */
    public function getCreditStatistics(?int $userId = null): array
    {
        $query = CreditTransaction::query();

        if ($userId) {
            $user = User::find($userId);
            if ($user && $this->isCreditClient($user)) {
                $query->where('user_id', $userId);
            } else {
                $query->whereRaw('1 = 0');
            }
        } else {
            $clientIds = $this->getCreditClientIds();

            if (empty($clientIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('user_id', $clientIds);
            }
        }

        $baseQuery = clone $query;

        return [
            'total_credits' => (float) $baseQuery->sum('amount'),
            'total_transactions' => $baseQuery->count(),
            'by_type' => $query->selectRaw('type, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('type')
                ->get()
                ->pluck('total', 'type')
                ->map(function ($value) {
                    return (float) $value;
                })
                ->toArray(),
        ];
    }
}
