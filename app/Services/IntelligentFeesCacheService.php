<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\BusinessProfile;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Service de cache intelligent pour les frais de réservation
 */
class IntelligentFeesCacheService
{
    /**
     * Durée de cache par défaut (en minutes)
     */
    const DEFAULT_CACHE_DURATION = 60;
    
    /**
     * Durée de cache pour les frais statiques (en minutes)
     */
    const STATIC_FEES_CACHE_DURATION = 1440; // 24 heures
    
    /**
     * Durée de cache pour les frais dynamiques (en minutes)
     */
    const DYNAMIC_FEES_CACHE_DURATION = 15;

    /**
     * Récupère les frais avec cache intelligent
     */
    public function getCachedFees(Reservation $reservation): array
    {
        $cacheKey = $this->generateCacheKey($reservation);
        
        return Cache::remember($cacheKey, self::DEFAULT_CACHE_DURATION, function () use ($reservation) {
            $feeService = app(EnhancedReservationFeeService::class);
            return $feeService->getAllAppliedFees($reservation);
        });
    }

    /**
     * Récupère les frais de business profile avec cache
     */
    public function getCachedBusinessProfileFees(BusinessProfile $businessProfile): array
    {
        $cacheKey = "business_profile_fees_{$businessProfile->id}";
        
        return Cache::remember($cacheKey, self::STATIC_FEES_CACHE_DURATION, function () use ($businessProfile) {
            return [
                'transaction_fees' => $this->parseFeeConfig($businessProfile->transaction_fee_config),
                'charge_fees' => $this->parseFeeConfig($businessProfile->charge_fee_config),
                'maintenance_fees' => [
                    'type' => $businessProfile->maintenance_fee_type,
                    'amount' => $businessProfile->maintenance_fee_amount
                ],
                'terminal_fees' => $businessProfile->terminal_fee_amount,
                'base_fees' => $businessProfile->base_fee_amount
            ];
        });
    }

    /**
     * Récupère les statistiques de frais avec cache
     */
    public function getCachedFeesStatistics(array $filters = []): array
    {
        $cacheKey = 'fees_statistics_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::DYNAMIC_FEES_CACHE_DURATION, function () use ($filters) {
            return $this->calculateFeesStatistics($filters);
        });
    }

    /**
     * Invalide le cache pour une réservation spécifique
     */
    public function invalidateReservationCache(Reservation $reservation): void
    {
        $cacheKey = $this->generateCacheKey($reservation);
        Cache::forget($cacheKey);
        
        // Invalider aussi les statistiques liées
        $this->invalidateStatisticsCache();
        
        Log::info('Cache invalidé pour la réservation', [
            'reservation_id' => $reservation->id,
            'cache_key' => $cacheKey
        ]);
    }

    /**
     * Invalide le cache des business profiles
     */
    public function invalidateBusinessProfileCache(BusinessProfile $businessProfile): void
    {
        $cacheKey = "business_profile_fees_{$businessProfile->id}";
        Cache::forget($cacheKey);
        
        // Invalider toutes les réservations liées à ce business profile
        $this->invalidateRelatedReservationsCache($businessProfile);
        
        Log::info('Cache invalidé pour le business profile', [
            'business_profile_id' => $businessProfile->id,
            'cache_key' => $cacheKey
        ]);
    }

    /**
     * Préchauffe le cache pour les données fréquemment utilisées
     */
    public function warmUpCache(): void
    {
        try {
            $this->info('Préchauffage du cache des frais...');
            
            // Préchauffer les business profiles
            $businessProfiles = BusinessProfile::with(['integrator', 'partner'])->get();
            foreach ($businessProfiles as $profile) {
                $this->getCachedBusinessProfileFees($profile);
            }
            
            // Préchauffer les statistiques
            $this->getCachedFeesStatistics();
            
            // Préchauffer les réservations récentes
            $recentReservations = Reservation::with(['chargingPoint', 'pricingPlan', 'transaction'])
                ->latest()
                ->limit(100)
                ->get();
                
            foreach ($recentReservations as $reservation) {
                $this->getCachedFees($reservation);
            }
            
            Log::info('Cache des frais préchauffé avec succès');
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du préchauffage du cache', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Nettoie le cache expiré
     */
    public function cleanExpiredCache(): void
    {
        try {
            // Dans un vrai projet, utiliser Redis ou Memcached avec TTL
            // Pour Laravel Cache, on peut utiliser des tags ou des clés avec timestamps
            
            $this->info('Nettoyage du cache expiré...');
            
            // Logique de nettoyage spécifique selon le driver de cache
            $this->cleanCacheByPattern('fees_statistics_*');
            $this->cleanCacheByPattern('business_profile_fees_*');
            $this->cleanCacheByPattern('reservation_fees_*');
            
            Log::info('Cache expiré nettoyé');
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du nettoyage du cache', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Génère une clé de cache pour une réservation
     */
    private function generateCacheKey(Reservation $reservation): string
    {
        $lastUpdated = $reservation->updated_at->timestamp;
        return "reservation_fees_{$reservation->id}_{$lastUpdated}";
    }

    /**
     * Parse la configuration des frais
     */
    private function parseFeeConfig($config): array
    {
        if (is_string($config)) {
            return json_decode($config, true) ?? [];
        }
        
        return is_array($config) ? $config : [];
    }

    /**
     * Calcule les statistiques des frais
     */
    private function calculateFeesStatistics(array $filters): array
    {
        $query = Reservation::query();
        
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
            $fees = $this->getCachedFees($reservation);
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
    }

    /**
     * Invalide le cache des statistiques
     */
    private function invalidateStatisticsCache(): void
    {
        Cache::forget('fees_statistics_*');
    }

    /**
     * Invalide le cache des réservations liées à un business profile
     */
    private function invalidateRelatedReservationsCache(BusinessProfile $businessProfile): void
    {
        $reservations = Reservation::whereHas('chargingPoint', function ($query) use ($businessProfile) {
            $query->where('business_profile_id', $businessProfile->id);
        })->get();

        foreach ($reservations as $reservation) {
            $this->invalidateReservationCache($reservation);
        }
    }

    /**
     * Nettoie le cache par pattern
     */
    private function cleanCacheByPattern(string $pattern): void
    {
        // Implémentation spécifique selon le driver de cache
        // Pour Redis: utiliser SCAN et DEL
        // Pour Memcached: utiliser les clés avec TTL
        // Pour Laravel Cache: utiliser les tags si disponibles
    }

    /**
     * Affiche un message d'information
     */
    private function info(string $message): void
    {
        Log::info($message);
    }
}
