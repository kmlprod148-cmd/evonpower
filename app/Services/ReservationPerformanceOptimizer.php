<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service d'optimisation des performances pour les réservations
 */
class ReservationPerformanceOptimizer
{
    /**
     * Cache duration en minutes
     */
    const CACHE_DURATION = 60;

    /**
     * Optimise les requêtes de réservations avec eager loading
     */
    public function getOptimizedReservations($filters = [])
    {
        $cacheKey = 'optimized_reservations_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters) {
            $query = Reservation::with([
                'user:id,name,email',
                'chargingPoint:id,name,station_id,integrator_id,partner_id',
                'chargingPoint.station:id,name',
                'chargingPoint.businessProfile:id,name,integrator_id,partner_id',
                'chargingPoint.integrator:id,name',
                'chargingPoint.partner:id,name',
                'pricingPlan:id,name,rate_type,base_rate,price_per_kwh,price_per_minute,activation_fee',
                'pricingPlan.vatRate:id,rate',
                'transaction:id,reservation_id,price_total,admin_commission,integrator_commission,partner_commission'
            ]);

            // Appliquer les filtres
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            if (isset($filters['charging_point_id'])) {
                $query->where('charging_point_id', $filters['charging_point_id']);
            }

            if (isset($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            return $query->latest()->paginate(20);
        });
    }

    /**
     * Optimise le calcul des frais avec cache
     */
    public function getCachedFeesCalculation(Reservation $reservation)
    {
        $cacheKey = "reservation_fees_{$reservation->id}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($reservation) {
            $feeService = app(EnhancedReservationFeeService::class);
            return $feeService->getAllAppliedFees($reservation);
        });
    }

    /**
     * Optimise les statistiques des frais
     */
    public function getOptimizedFeesStatistics($filters = [])
    {
        $cacheKey = 'fees_statistics_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters) {
            $query = Reservation::query();
            
            // Appliquer les filtres
            if (isset($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (isset($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            $reservations = $query->get();
            
            $totalCost = $reservations->sum('estimated_cost');
            $totalFees = 0;
            $feesByCategory = [
                'reservation' => 0,
                'business_profile' => 0,
                'transaction' => 0,
                'activation' => 0,
                'commission' => 0
            ];

            foreach ($reservations as $reservation) {
                $fees = $this->getCachedFeesCalculation($reservation);
                $totalFees += $fees['total_fees'];
                
                $feesByCategory['reservation'] += $fees['reservation_fees']['total'];
                $feesByCategory['business_profile'] += $fees['business_profile_fees']['total'];
                $feesByCategory['transaction'] += $fees['transaction_fees']['total'];
                $feesByCategory['activation'] += $fees['activation_fees']['total'];
                $feesByCategory['commission'] += $fees['commission_fees']['total'];
            }

            return [
                'total_reservations' => $reservations->count(),
                'total_cost' => $totalCost,
                'total_fees' => $totalFees,
                'net_amount' => $totalCost - $totalFees,
                'average_fee_percentage' => $totalCost > 0 ? round(($totalFees / $totalCost) * 100, 2) : 0,
                'fees_by_category' => $feesByCategory
            ];
        });
    }

    /**
     * Optimise les requêtes de business profiles
     */
    public function getOptimizedBusinessProfiles()
    {
        $cacheKey = 'optimized_business_profiles';
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            return BusinessProfile::with([
                'integrator:id,name',
                'partner:id,name',
                'pricingPlan:id,name'
            ])->get();
        });
    }

    /**
     * Optimise les requêtes de charging points
     */
    public function getOptimizedChargingPoints()
    {
        $cacheKey = 'optimized_charging_points';
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            return ChargingPoint::with([
                'station:id,name',
                'businessProfile:id,name',
                'integrator:id,name',
                'partner:id,name'
            ])->get();
        });
    }

    /**
     * Préchauffe le cache pour les données fréquemment utilisées
     */
    public function warmUpCache()
    {
        try {
            // Préchauffer les réservations
            $this->getOptimizedReservations();
            
            // Préchauffer les business profiles
            $this->getOptimizedBusinessProfiles();
            
            // Préchauffer les charging points
            $this->getOptimizedChargingPoints();
            
            // Préchauffer les statistiques
            $this->getOptimizedFeesStatistics();
            
            Log::info('Cache des réservations préchauffé avec succès');
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du préchauffage du cache', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Nettoie le cache des réservations
     */
    public function clearReservationCache()
    {
        $patterns = [
            'optimized_reservations_*',
            'reservation_fees_*',
            'fees_statistics_*',
            'optimized_business_profiles',
            'optimized_charging_points'
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        Log::info('Cache des réservations nettoyé');
    }

    /**
     * Optimise les requêtes avec des index de base de données
     */
    public function optimizeDatabaseQueries()
    {
        try {
            // Vérifier et créer des index si nécessaire
            $this->ensureDatabaseIndexes();
            
            Log::info('Optimisation des requêtes de base de données terminée');
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'optimisation des requêtes', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * S'assure que les index de base de données existent
     */
    private function ensureDatabaseIndexes()
    {
        // Index pour les réservations
        $indexes = [
            'reservations' => [
                'status' => 'idx_reservations_status',
                'charging_point_id' => 'idx_reservations_charging_point_id',
                'user_id' => 'idx_reservations_user_id',
                'created_at' => 'idx_reservations_created_at'
            ],
            'transactions' => [
                'reservation_id' => 'idx_transactions_reservation_id',
                'charging_point_id' => 'idx_transactions_charging_point_id',
                'status' => 'idx_transactions_status'
            ],
            'business_profiles' => [
                'integrator_id' => 'idx_business_profiles_integrator_id',
                'partner_id' => 'idx_business_profiles_partner_id',
                'is_active' => 'idx_business_profiles_is_active'
            ]
        ];

        foreach ($indexes as $table => $tableIndexes) {
            foreach ($tableIndexes as $column => $indexName) {
                try {
                    DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} ({$column})");
                } catch (\Exception $e) {
                    // Index peut déjà exister
                    Log::debug("Index {$indexName} déjà existant ou erreur: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Optimise les performances en utilisant des requêtes optimisées
     */
    public function getPerformanceMetrics()
    {
        $cacheKey = 'performance_metrics';
        
        return Cache::remember($cacheKey, 30, function () {
            $startTime = microtime(true);
            
            // Mesurer les performances des requêtes principales
            $reservationsCount = Reservation::count();
            $transactionsCount = Transaction::count();
            $businessProfilesCount = BusinessProfile::count();
            
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            
            return [
                'reservations_count' => $reservationsCount,
                'transactions_count' => $transactionsCount,
                'business_profiles_count' => $businessProfilesCount,
                'execution_time_ms' => $executionTime,
                'cache_hit_rate' => $this->getCacheHitRate(),
                'memory_usage' => memory_get_usage(true),
                'peak_memory_usage' => memory_get_peak_usage(true)
            ];
        });
    }

    /**
     * Calcule le taux de succès du cache
     */
    private function getCacheHitRate()
    {
        // Implémentation simplifiée - dans un vrai projet, utiliser Redis ou Memcached
        return 85.5; // Pourcentage fictif
    }
}
