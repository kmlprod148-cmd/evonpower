<?php

namespace App\Services\Commission\Cache;

use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service de cache intelligent pour les calculs de commission
 * 
 * Gère le cache hiérarchique avec invalidation intelligente
 * et optimisation des performances
 */
class CommissionCacheService
{
    /**
     * Durée de cache par défaut (en secondes)
     */
    private const DEFAULT_CACHE_TTL = 3600; // 1 heure

    /**
     * Durée de cache pour les calculs fréquents (en secondes)
     */
    private const FREQUENT_CALCULATIONS_TTL = 1800; // 30 minutes

    /**
     * Durée de cache pour les calculs rares (en secondes)
     */
    private const RARE_CALCULATIONS_TTL = 7200; // 2 heures

    /**
     * Tags de cache pour l'invalidation
     */
    private const CACHE_TAGS = [
        'commissions',
        'business_profiles',
        'calculations'
    ];

    /**
     * Génère une clé de cache pour un calcul de commission
     * 
     * @param BusinessProfile $businessProfile
     * @param float $totalAmount
     * @param string $strategy
     * @param array $options
     * @return string
     */
    public function generateCacheKey(
        BusinessProfile $businessProfile,
        float $totalAmount,
        string $strategy,
        array $options = []
    ): string {
        $optionsHash = md5(serialize($options));
        $amountRounded = round($totalAmount, 2);
        
        return "commission:calc:{$businessProfile->id}:{$amountRounded}:{$strategy}:{$optionsHash}";
    }

    /**
     * Récupère un calcul de commission depuis le cache
     * 
     * @param string $cacheKey
     * @return mixed|null
     */
    public function get(string $cacheKey)
    {
        try {
            return Cache::get($cacheKey);
        } catch (\Exception $e) {
            Log::warning("Erreur lors de la récupération du cache", [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Stocke un calcul de commission dans le cache
     * 
     * @param string $cacheKey
     * @param mixed $data
     * @param int $ttl
     * @return bool
     */
    public function put(string $cacheKey, $data, int $ttl = self::DEFAULT_CACHE_TTL): bool
    {
        try {
            $tags = $this->getCacheTags($cacheKey);
            return Cache::tags($tags)->put($cacheKey, $data, $ttl);
        } catch (\Exception $e) {
            Log::warning("Erreur lors du stockage en cache", [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Récupère ou calcule et met en cache un résultat
     * 
     * @param string $cacheKey
     * @param callable $callback
     * @param int $ttl
     * @return mixed
     */
    public function remember(string $cacheKey, callable $callback, int $ttl = self::DEFAULT_CACHE_TTL)
    {
        try {
            $tags = $this->getCacheTags($cacheKey);
            return Cache::tags($tags)->remember($cacheKey, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning("Erreur lors du cache remember", [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            // Exécuter le callback même en cas d'erreur de cache
            return $callback();
        }
    }

    /**
     * Détermine la durée de cache appropriée selon le contexte
     * 
     * @param BusinessProfile $businessProfile
     * @param float $totalAmount
     * @param string $strategy
     * @return int
     */
    public function determineCacheTTL(
        BusinessProfile $businessProfile,
        float $totalAmount,
        string $strategy
    ): int {
        // Cache plus long pour les calculs fréquents (montants standards)
        if ($this->isFrequentCalculation($totalAmount)) {
            return self::FREQUENT_CALCULATIONS_TTL;
        }

        // Cache plus court pour les calculs rares (montants élevés)
        if ($this->isRareCalculation($totalAmount)) {
            return self::RARE_CALCULATIONS_TTL;
        }

        return self::DEFAULT_CACHE_TTL;
    }

    /**
     * Détermine si un calcul est fréquent
     * 
     * @param float $totalAmount
     * @return bool
     */
    private function isFrequentCalculation(float $totalAmount): bool
    {
        // Montants entre 10 et 100 EUR sont considérés comme fréquents
        return $totalAmount >= 10 && $totalAmount <= 100;
    }

    /**
     * Détermine si un calcul est rare
     * 
     * @param float $totalAmount
     * @return bool
     */
    private function isRareCalculation(float $totalAmount): bool
    {
        // Montants supérieurs à 500 EUR sont considérés comme rares
        return $totalAmount > 500;
    }

    /**
     * Obtient les tags de cache appropriés
     * 
     * @param string $cacheKey
     * @return array
     */
    private function getCacheTags(string $cacheKey): array
    {
        $tags = self::CACHE_TAGS;
        
        // Extraire l'ID du business profile de la clé
        if (preg_match('/commission:calc:(\d+):/', $cacheKey, $matches)) {
            $businessProfileId = $matches[1];
            $tags[] = "profile_{$businessProfileId}";
        }

        return $tags;
    }

    /**
     * Invalide le cache pour un business profile spécifique
     * 
     * @param int $businessProfileId
     * @return bool
     */
    public function invalidateBusinessProfileCache(int $businessProfileId): bool
    {
        try {
            return Cache::tags(["profile_{$businessProfileId}"])->flush();
        } catch (\Exception $e) {
            Log::warning("Erreur lors de l'invalidation du cache", [
                'business_profile_id' => $businessProfileId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalide tout le cache des commissions
     * 
     * @return bool
     */
    public function invalidateAllCommissionCache(): bool
    {
        try {
            return Cache::tags(['commissions'])->flush();
        } catch (\Exception $e) {
            Log::warning("Erreur lors de l'invalidation complète du cache", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Nettoie le cache expiré
     * 
     * @return bool
     */
    public function cleanExpiredCache(): bool
    {
        try {
            // Cette méthode dépend du driver de cache utilisé
            // Pour Redis, on peut utiliser des commandes spécifiques
            if (config('cache.default') === 'redis') {
                // Redis gère automatiquement l'expiration
                return true;
            }

            // Pour les autres drivers, on peut implémenter une logique de nettoyage
            return true;
        } catch (\Exception $e) {
            Log::warning("Erreur lors du nettoyage du cache", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtient les statistiques du cache
     * 
     * @return array
     */
    public function getCacheStatistics(): array
    {
        return [
            'cache_driver' => config('cache.default'),
            'cache_prefix' => config('cache.prefix'),
            'default_ttl' => self::DEFAULT_CACHE_TTL,
            'frequent_ttl' => self::FREQUENT_CALCULATIONS_TTL,
            'rare_ttl' => self::RARE_CALCULATIONS_TTL,
            'cache_tags' => self::CACHE_TAGS,
        ];
    }

    /**
     * Vérifie si le cache est disponible
     * 
     * @return bool
     */
    public function isCacheAvailable(): bool
    {
        try {
            $testKey = 'commission:test:' . time();
            Cache::put($testKey, 'test', 60);
            $result = Cache::get($testKey);
            Cache::forget($testKey);
            return $result === 'test';
        } catch (\Exception $e) {
            Log::warning("Cache non disponible", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Optimise le cache pour les calculs en batch
     * 
     * @param array $calculations
     * @return array
     */
    public function optimizeBatchCache(array $calculations): array
    {
        $optimized = [];
        
        // Grouper par business profile pour optimiser les requêtes
        $groupedByProfile = [];
        foreach ($calculations as $index => $calculation) {
            $profileId = $calculation['business_profile']->id;
            $groupedByProfile[$profileId][] = $calculation;
        }

        // Traiter chaque groupe
        foreach ($groupedByProfile as $profileId => $profileCalculations) {
            $optimized[$profileId] = $profileCalculations;
        }

        return $optimized;
    }
}
