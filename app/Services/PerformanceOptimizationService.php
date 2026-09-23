<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Service d'optimisation des performances
 * 
 * Ce service implémente plusieurs stratégies d'optimisation :
 * - Cache intelligent avec tags
 * - Requêtes optimisées avec eager loading
 * - Calculs en batch
 * - Cache des hiérarchies utilisateur
 */
class PerformanceOptimizationService
{
    protected array $cacheConfig = [
        'commission_calculation' => 3600, // 1 heure
        'user_hierarchy' => 7200,        // 2 heures
        'business_profile' => 1800,       // 30 minutes
        'transaction_stats' => 900,       // 15 minutes
    ];

    /**
     * Optimise le chargement des charging points avec eager loading
     * 
     * @param User $user
     * @param array $filters
     * @return Collection
     */
    public function getOptimizedChargingPoints(User $user, array $filters = []): Collection
    {
        $cacheKey = "charging_points:user_{$user->id}:" . md5(serialize($filters));
        
        return Cache::tags(['charging_points', "user_{$user->id}"])
            ->remember($cacheKey, $this->cacheConfig['business_profile'], function () use ($user, $filters) {
                return $this->loadChargingPointsWithOptimizations($user, $filters);
            });
    }

    /**
     * Charge les charging points avec optimisations
     * 
     * @param User $user
     * @param array $filters
     * @return Collection
     */
    private function loadChargingPointsWithOptimizations(User $user, array $filters = []): Collection
    {
        $query = \App\Models\ChargingPoint::visibleToUser($user)
            ->with([
                'user:id,name,email',
                'integrator:id,name',
                'partner:id,name',
                'group:id,name,location',
                'pricingPlan:id,name,price_per_minute,price_per_kwh',
                'businessProfile:id,name,integrator_commission,owner_commission',
                'station:id,name,location',
                'connectors:id,charging_point_id,connector_type,power_output'
            ]);

        // Appliquer les filtres optimisés
        $this->applyOptimizedFilters($query, $filters);

        return $query->get();
    }

    /**
     * Applique les filtres de manière optimisée
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     */
    private function applyOptimizedFilters($query, array $filters): void
    {
        // Filtres avec index optimisés
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['integrator_id'])) {
            $query->where('integrator_id', $filters['integrator_id']);
        }

        if (isset($filters['partner_id'])) {
            $query->where('partner_id', $filters['partner_id']);
        }

        if (isset($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }

        // Recherche textuelle optimisée
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Filtres de puissance avec index
        if (isset($filters['power_min'])) {
            $query->where('power_output', '>=', $filters['power_min']);
        }

        if (isset($filters['power_max'])) {
            $query->where('power_output', '<=', $filters['power_max']);
        }
    }

    /**
     * Calcule les statistiques de transaction en batch
     * 
     * @param array $businessProfileIds
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function calculateBatchTransactionStats(array $businessProfileIds, string $dateFrom, string $dateTo): array
    {
        $cacheKey = "transaction_stats:" . md5(implode(',', $businessProfileIds) . $dateFrom . $dateTo);
        
        return Cache::tags(['transaction_stats', 'business_profiles'])
            ->remember($cacheKey, $this->cacheConfig['transaction_stats'], function () use ($businessProfileIds, $dateFrom, $dateTo) {
                return $this->loadTransactionStatsOptimized($businessProfileIds, $dateFrom, $dateTo);
            });
    }

    /**
     * Charge les statistiques de transaction de manière optimisée
     * 
     * @param array $businessProfileIds
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    private function loadTransactionStatsOptimized(array $businessProfileIds, string $dateFrom, string $dateTo): array
    {
        // Requête optimisée avec agrégation SQL
        $stats = DB::table('transactions')
            ->select([
                'business_profile_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(price_total) as total_revenue'),
                DB::raw('SUM(admin_commission) as total_admin_commission'),
                DB::raw('SUM(integrator_commission) as total_integrator_commission'),
                DB::raw('SUM(partner_commission) as total_partner_commission'),
                DB::raw('AVG(price_total) as average_transaction_value')
            ])
            ->whereIn('business_profile_id', $businessProfileIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('business_profile_id')
            ->get()
            ->keyBy('business_profile_id');

        return $stats->toArray();
    }

    /**
     * Cache la hiérarchie utilisateur pour optimiser les accès
     * 
     * @param User $user
     * @return array
     */
    public function getCachedUserHierarchy(User $user): array
    {
        $cacheKey = "user_hierarchy:{$user->id}";
        
        return Cache::tags(['user_hierarchies', "user_{$user->id}"])
            ->remember($cacheKey, $this->cacheConfig['user_hierarchy'], function () use ($user) {
                return $this->buildUserHierarchy($user);
            });
    }

    /**
     * Construit la hiérarchie utilisateur
     * 
     * @param User $user
     * @return array
     */
    private function buildUserHierarchy(User $user): array
    {
        $hierarchy = [
            'user_id' => $user->id,
            'roles' => $user->getRoleNames()->toArray(),
            'integrator_id' => $user->integrator_id,
            'partner_id' => $user->partner_id,
            'created_by' => $user->created_by,
        ];

        // Charger les relations nécessaires
        if ($user->integrator_id) {
            $integrator = \App\Models\Integrator::find($user->integrator_id);
            $hierarchy['integrator'] = $integrator ? [
                'id' => $integrator->id,
                'name' => $integrator->name,
                'business_profile_id' => $integrator->business_profile_id,
            ] : null;
        }

        if ($user->partner_id) {
            $partner = \App\Models\Partner::find($user->partner_id);
            $hierarchy['partner'] = $partner ? [
                'id' => $partner->id,
                'name' => $partner->name,
                'integrator_id' => $partner->integrator_id,
            ] : null;
        }

        return $hierarchy;
    }

    /**
     * Optimise les calculs de commission en batch
     * 
     * @param array $calculations
     * @return array
     */
    public function optimizeBatchCommissionCalculations(array $calculations): array
    {
        // Grouper par business profile pour optimiser
        $groupedCalculations = collect($calculations)->groupBy('business_profile_id');
        
        $results = [];
        
        foreach ($groupedCalculations as $businessProfileId => $profileCalculations) {
            // Charger le business profile une seule fois
            $businessProfile = BusinessProfile::find($businessProfileId);
            
            if (!$businessProfile) {
                continue;
            }

            // Traiter tous les calculs pour ce business profile
            foreach ($profileCalculations as $index => $calculation) {
                $results[$index] = $this->calculateSingleCommission($businessProfile, $calculation);
            }
        }

        return $results;
    }

    /**
     * Calcule une commission unique avec cache
     * 
     * @param BusinessProfile $businessProfile
     * @param array $calculation
     * @return array
     */
    private function calculateSingleCommission(BusinessProfile $businessProfile, array $calculation): array
    {
        $cacheKey = "commission:{$businessProfile->id}:{$calculation['amount']}:{$calculation['strategy']}";
        
        return Cache::tags(['commissions', "profile_{$businessProfile->id}"])
            ->remember($cacheKey, $this->cacheConfig['commission_calculation'], function () use ($businessProfile, $calculation) {
                // Utiliser le service de commission consolidé
                $commissionService = app(CommissionCalculationService::class);
                return $commissionService->calculateCommissions(
                    $businessProfile,
                    $calculation['amount'],
                    $calculation['strategy'] ?? 'corrected'
                );
            });
    }

    /**
     * Nettoie le cache de manière sélective
     * 
     * @param string $tag
     * @param int|null $entityId
     * @return bool
     */
    public function clearSelectiveCache(string $tag, ?int $entityId = null): bool
    {
        if ($entityId) {
            return Cache::tags([$tag, "{$tag}_{$entityId}"])->flush();
        }
        
        return Cache::tags([$tag])->flush();
    }

    /**
     * Obtient les statistiques de performance
     * 
     * @return array
     */
    public function getPerformanceStatistics(): array
    {
        return [
            'cache_config' => $this->cacheConfig,
            'cache_driver' => config('cache.default'),
            'database_connections' => config('database.connections'),
            'optimization_enabled' => true,
        ];
    }

    /**
     * Optimise les requêtes de transaction avec pagination
     * 
     * @param User $user
     * @param int $perPage
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getOptimizedTransactions(User $user, int $perPage = 15, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $cacheKey = "transactions:user_{$user->id}:page_{$perPage}:" . md5(serialize($filters));
        
        return Cache::tags(['transactions', "user_{$user->id}"])
            ->remember($cacheKey, $this->cacheConfig['transaction_stats'], function () use ($user, $perPage, $filters) {
                return $this->loadTransactionsWithOptimizations($user, $perPage, $filters);
            });
    }

    /**
     * Charge les transactions avec optimisations
     * 
     * @param User $user
     * @param int $perPage
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    private function loadTransactionsWithOptimizations(User $user, int $perPage, array $filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Transaction::query()
            ->with([
                'user:id,name,email',
                'chargingPoint:id,name,serial_number',
                'businessProfile:id,name,integrator_commission,owner_commission',
                'pricingPlan:id,name,price_per_minute,price_per_kwh'
            ])
            ->select([
                'id', 'user_id', 'charging_point_id', 'business_profile_id',
                'price_total', 'admin_commission', 'integrator_commission', 'partner_commission',
                'status', 'created_at', 'updated_at'
            ]);

        // Appliquer les filtres de visibilité
        if ($user->hasRole('admin')) {
            // Admin voit tout
        } elseif ($user->hasRole('integrator')) {
            $query->whereHas('chargingPoint', function ($q) use ($user) {
                $q->where('integrator_id', $user->integrator_id);
            });
        } elseif ($user->hasRole('operator')) {
            $query->where('user_id', $user->id);
        }

        // Appliquer les filtres additionnels
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }
}
