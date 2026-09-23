<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionHistoryCacheService
{
    /**
     * Durée de cache en minutes
     */
    private const CACHE_DURATION = 15;

    /**
     * Préfixe des clés de cache
     */
    private const CACHE_PREFIX = 'transaction_history_';

    /**
     * Récupère les transactions avec cache
     */
    public function getTransactionsWithCache(User $user, array $filters, int $perPage = 15)
    {
        $cacheKey = $this->generateCacheKey('transactions', $user, $filters, $perPage);
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user, $filters, $perPage) {
            return $this->getTransactionsFromDatabase($user, $filters, $perPage);
        });
    }

    /**
     * Récupère les statistiques avec cache
     */
    public function getStatisticsWithCache(User $user, array $filters): array
    {
        $cacheKey = $this->generateCacheKey('statistics', $user, $filters);
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user, $filters) {
            return $this->getStatisticsFromDatabase($user, $filters);
        });
    }

    /**
     * Récupère les données de graphiques avec cache
     */
    public function getChartDataWithCache(User $user, array $filters): array
    {
        $cacheKey = $this->generateCacheKey('chart_data', $user, $filters);
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user, $filters) {
            return $this->getChartDataFromDatabase($user, $filters);
        });
    }

    /**
     * Invalide le cache pour un utilisateur
     */
    public function invalidateUserCache(User $user): void
    {
        $pattern = self::CACHE_PREFIX . $user->id . '_*';
        
        // Récupérer toutes les clés de cache correspondant au pattern
        $keys = Cache::getRedis()->keys($pattern);
        
        foreach ($keys as $key) {
            Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
        }
    }

    /**
     * Invalide le cache global des transactions
     */
    public function invalidateGlobalCache(): void
    {
        $pattern = self::CACHE_PREFIX . '*';
        
        $keys = Cache::getRedis()->keys($pattern);
        
        foreach ($keys as $key) {
            Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
        }
    }

    /**
     * Génère une clé de cache unique
     */
    private function generateCacheKey(string $type, User $user, array $filters, int $perPage = null): string
    {
        $key = self::CACHE_PREFIX . $type . '_' . $user->id;
        
        // Ajouter les filtres à la clé
        $filterHash = md5(serialize($filters));
        $key .= '_' . $filterHash;
        
        if ($perPage !== null) {
            $key .= '_' . $perPage;
        }
        
        return $key;
    }

    /**
     * Récupère les transactions depuis la base de données avec requêtes optimisées
     */
    private function getTransactionsFromDatabase(User $user, array $filters, int $perPage)
    {
        $query = Transaction::select([
            'id',
            'user_id',
            'charging_point_id',
            'business_profile_id',
            'transaction_type',
            'transaction_category',
            'status',
            'price_total',
            'currency',
            'reason',
            'created_at',
            'updated_at'
        ])
        ->with([
            'user:id,name,email',
            'chargingPoint:id,name,station_id',
            'chargingPoint.station:id,name',
            'businessProfile:id,name'
        ]);

        // Appliquer les filtres optimisés
        $this->applyOptimizedFilters($query, $filters);
        
        // Appliquer les permissions optimisées
        $this->applyOptimizedPermissions($query, $user);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Récupère les statistiques depuis la base de données avec requêtes optimisées
     */
    private function getStatisticsFromDatabase(User $user, array $filters): array
    {
        $baseQuery = Transaction::query();
        
        $this->applyOptimizedFilters($baseQuery, $filters);
        $this->applyOptimizedPermissions($baseQuery, $user);

        // Utiliser des requêtes optimisées avec des agrégations
        $stats = $baseQuery->selectRaw('
            COUNT(*) as total_transactions,
            SUM(price_total) as total_amount,
            SUM(CASE WHEN transaction_category IN ("charging", "commission", "deposit", "activation") THEN price_total ELSE 0 END) as credits,
            SUM(CASE WHEN transaction_category IN ("debit", "integrator_debit", "periodic_fee", "withdrawal") THEN price_total ELSE 0 END) as debits,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_transactions,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_transactions,
            SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_transactions
        ')->first();

        return [
            'total_transactions' => $stats->total_transactions ?? 0,
            'total_amount' => $stats->total_amount ?? 0,
            'debits' => $stats->debits ?? 0,
            'credits' => $stats->credits ?? 0,
            'net_balance' => ($stats->credits ?? 0) - ($stats->debits ?? 0),
            'completed_transactions' => $stats->completed_transactions ?? 0,
            'pending_transactions' => $stats->pending_transactions ?? 0,
            'cancelled_transactions' => $stats->cancelled_transactions ?? 0,
        ];
    }

    /**
     * Récupère les données de graphiques depuis la base de données avec requêtes optimisées
     */
    private function getChartDataFromDatabase(User $user, array $filters): array
    {
        $baseQuery = Transaction::query();
        
        $this->applyOptimizedFilters($baseQuery, $filters);
        $this->applyOptimizedPermissions($baseQuery, $user);

        return [
            'transactionsByDay' => $this->getOptimizedTransactionsByDay($baseQuery, 7),
            'transactionsByType' => $this->getOptimizedTransactionsByType($baseQuery),
            'amountsByCategory' => $this->getOptimizedAmountsByCategory($baseQuery),
            'transactionsByStatus' => $this->getOptimizedTransactionsByStatus($baseQuery),
            'monthlyTrends' => $this->getOptimizedMonthlyTrends($baseQuery, 6),
        ];
    }

    /**
     * Applique les filtres de manière optimisée
     */
    private function applyOptimizedFilters($query, array $filters): void
    {
        // Filtres de date avec index
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        // Filtres simples avec index
        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }
        if (!empty($filters['transaction_category'])) {
            $query->where('transaction_category', $filters['transaction_category']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtres de montant avec index
        if (!empty($filters['min_amount'])) {
            $query->where('price_total', '>=', $filters['min_amount']);
        }
        if (!empty($filters['max_amount'])) {
            $query->where('price_total', '<=', $filters['max_amount']);
        }

        // Filtres spécifiques
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['charging_point_id'])) {
            $query->where('charging_point_id', $filters['charging_point_id']);
        }
        if (!empty($filters['business_profile_id'])) {
            $query->where('business_profile_id', $filters['business_profile_id']);
        }

        // Recherche textuelle optimisée
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->where('id', 'like', "%{$searchTerm}%")
                  ->orWhere('reason', 'like', "%{$searchTerm}%")
                  ->orWhereHas('user', function($userQuery) use ($searchTerm) {
                      $userQuery->where('name', 'like', "%{$searchTerm}%")
                               ->orWhere('email', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('chargingPoint', function($cpQuery) use ($searchTerm) {
                      $cpQuery->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }
    }

    /**
     * Applique les permissions de manière optimisée
     */
    private function applyOptimizedPermissions($query, User $user): void
    {
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return; // Pas de restriction pour les admins
        }

        if ($user->hasRole('integrator')) {
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('user', function($userQuery) use ($user) {
                      $userQuery->where('created_by', $user->id);
                  })
                  ->orWhereHas('chargingPoint', function($cpQuery) use ($user) {
                      $cpQuery->where('integrator_id', $user->integrator_id);
                  });
            });
        } elseif ($user->hasRole('operator')) {
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('user', function($userQuery) use ($user) {
                      $userQuery->where('created_by', $user->id);
                  })
                  ->orWhereHas('chargingPoint', function($cpQuery) use ($user) {
                      $cpQuery->where('user_id', $user->id);
                  });
            });
        } else {
            $query->where('user_id', $user->id);
        }
    }

    /**
     * Transactions par jour optimisées
     */
    private function getOptimizedTransactionsByDay($query, int $days): array
    {
        $labels = [];
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d/m');
            
            $count = (clone $query)->whereDate('created_at', $date->format('Y-m-d'))->count();
            $data[] = $count;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Transactions par type optimisées
     */
    private function getOptimizedTransactionsByType($query): array
    {
        $result = (clone $query)
            ->selectRaw('transaction_type, COUNT(*) as count')
            ->groupBy('transaction_type')
            ->get();

        $labels = [];
        $data = [];

        foreach ($result as $row) {
            $labels[] = $this->getTransactionTypeLabel($row->transaction_type);
            $data[] = $row->count;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Montants par catégorie optimisés
     */
    private function getOptimizedAmountsByCategory($query): array
    {
        $result = (clone $query)
            ->selectRaw('transaction_category, SUM(price_total) as total')
            ->groupBy('transaction_category')
            ->having('total', '>', 0)
            ->get();

        $labels = [];
        $data = [];

        foreach ($result as $row) {
            $labels[] = $this->getTransactionCategoryLabel($row->transaction_category);
            $data[] = round($row->total, 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Transactions par statut optimisées
     */
    private function getOptimizedTransactionsByStatus($query): array
    {
        $result = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $labels = [];
        $data = [];

        foreach ($result as $row) {
            $labels[] = $this->getStatusLabel($row->status);
            $data[] = $row->count;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Tendance mensuelle optimisée
     */
    private function getOptimizedMonthlyTrends($query, int $months): array
    {
        $labels = [];
        $credits = [];
        $debits = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');
            
            $monthQuery = (clone $query)->whereMonth('created_at', $date->month)
                                       ->whereYear('created_at', $date->year);
            
            $credits[] = round($monthQuery->whereIn('transaction_category', ['charging', 'commission', 'deposit', 'activation'])->sum('price_total'), 2);
            $debits[] = round($monthQuery->whereIn('transaction_category', ['debit', 'integrator_debit', 'periodic_fee', 'withdrawal'])->sum('price_total'), 2);
        }

        return ['labels' => $labels, 'credits' => $credits, 'debits' => $debits];
    }

    /**
     * Méthodes utilitaires pour les labels
     */
    private function getTransactionTypeLabel(string $type): string
    {
        $labels = [
            'client' => 'Client',
            'admin' => 'Administration',
            'activation_fee' => 'Frais d\'activation'
        ];
        return $labels[$type] ?? 'Inconnu';
    }

    private function getTransactionCategoryLabel(string $category): string
    {
        $labels = [
            'charging' => 'Recharge',
            'debit' => 'Débit Admin',
            'integrator_debit' => 'Débit Intégrateur',
            'commission' => 'Commission',
            'activation' => 'Activation'
        ];
        return $labels[$category] ?? 'Inconnu';
    }

    private function getStatusLabel(string $status): string
    {
        $labels = [
            'completed' => 'Terminé',
            'pending' => 'En attente',
            'cancelled' => 'Annulé',
            'failed' => 'Échoué'
        ];
        return $labels[$status] ?? 'Inconnu';
    }
}
