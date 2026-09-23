<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Transaction;
use App\Services\Commission\Strategies\CommissionCalculationStrategyInterface;
use App\Services\Commission\Strategies\CorrectedCommissionStrategy;
use App\Services\Commission\Strategies\UnifiedCommissionStrategy;
use App\Services\Commission\Strategies\FinalCommissionStrategy;
use App\Services\Commission\Validation\CommissionValidationService;
use App\Services\Commission\Cache\CommissionCacheService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service unifié de calcul des commissions avec Strategy Pattern
 * 
 * Ce service consolide tous les services de commission existants :
 * - CorrectedCommissionService
 * - UnifiedBusinessProfileCommissionService  
 * - FinalBusinessProfileCommissionService
 * - CorrectedBusinessProfileCommissionService
 */
class CommissionCalculationService
{
    protected array $strategies = [];
    protected string $defaultStrategy = 'corrected';
    protected CommissionValidationService $validationService;
    protected CommissionCacheService $cacheService;

    public function __construct(
        CommissionValidationService $validationService,
        CommissionCacheService $cacheService
    ) {
        $this->validationService = $validationService;
        $this->cacheService = $cacheService;
        $this->registerStrategies();
    }

    /**
     * Enregistre toutes les stratégies de calcul disponibles
     */
    private function registerStrategies(): void
    {
        $this->strategies = [
            'corrected' => new CorrectedCommissionStrategy(),
            'unified' => new UnifiedCommissionStrategy(),
            'final' => new FinalCommissionStrategy(),
        ];
    }

    /**
     * Calcule les commissions selon la stratégie spécifiée
     * 
     * @param BusinessProfile $businessProfile
     * @param float $totalAmount
     * @param string $strategy
     * @param array $options
     * @return array
     */
    public function calculateCommissions(
        BusinessProfile $businessProfile, 
        float $totalAmount, 
        string $strategy = null,
        array $options = []
    ): array {
        $strategy = $strategy ?? $this->defaultStrategy;
        
        // Validation robuste des paramètres
        $this->validationService->validateCompleteCalculation(
            $businessProfile, 
            $totalAmount, 
            $strategy, 
            $options
        );
        
        // Vérifier si la stratégie existe
        if (!isset($this->strategies[$strategy])) {
            throw new \InvalidArgumentException("Stratégie de commission '{$strategy}' non trouvée");
        }

        // Cache intelligent avec TTL adaptatif
        $cacheKey = $this->cacheService->generateCacheKey($businessProfile, $totalAmount, $strategy, $options);
        $ttl = $this->cacheService->determineCacheTTL($businessProfile, $totalAmount, $strategy);
        
        // Forcer le recalcul si demandé
        if ($options['force_recalculation'] ?? false) {
            $this->cacheService->invalidateBusinessProfileCache($businessProfile->id);
        }
        
        return $this->cacheService->remember($cacheKey, function () use ($businessProfile, $totalAmount, $strategy, $options) {
            $result = $this->strategies[$strategy]->calculate($businessProfile, $totalAmount, $options);
            
            // Validation du résultat
            $this->validationService->validateCalculationResult($result);
            
            return $result;
        }, $ttl);
    }

    /**
     * Calcule les commissions avec cache intelligent optimisé
     * 
     * @param BusinessProfile $businessProfile
     * @param float $totalAmount
     * @param string $strategy
     * @param array $options
     * @return array
     */
    public function calculateCommissionsWithCache(
        BusinessProfile $businessProfile, 
        float $totalAmount, 
        string $strategy = null,
        array $options = []
    ): array {
        // Utilise le service de cache intelligent
        return $this->calculateCommissions($businessProfile, $totalAmount, $strategy, $options);
    }

    /**
     * Calcule les commissions en batch pour optimiser les performances
     * 
     * @param array $calculations Array of ['business_profile' => BusinessProfile, 'amount' => float, 'strategy' => string]
     * @return array
     */
    public function calculateBatchCommissions(array $calculations): array
    {
        $results = [];
        
        // Optimisation du cache pour les calculs en batch
        $optimizedCalculations = $this->cacheService->optimizeBatchCache($calculations);
        
        // Grouper par stratégie pour optimiser
        $groupedCalculations = [];
        foreach ($calculations as $index => $calculation) {
            $strategy = $calculation['strategy'] ?? $this->defaultStrategy;
            $groupedCalculations[$strategy][] = $calculation;
        }

        // Traiter chaque groupe avec la même stratégie
        foreach ($groupedCalculations as $strategy => $group) {
            $strategyInstance = $this->strategies[$strategy];
            
            foreach ($group as $index => $calculation) {
                // Validation pour chaque calcul
                $this->validationService->validateCompleteCalculation(
                    $calculation['business_profile'],
                    $calculation['amount'],
                    $strategy,
                    $calculation['options'] ?? []
                );
                
                $result = $strategyInstance->calculate(
                    $calculation['business_profile'],
                    $calculation['amount'],
                    $calculation['options'] ?? []
                );
                
                // Validation du résultat
                $this->validationService->validateCalculationResult($result);
                
                $results[$index] = $result;
            }
        }

        return $results;
    }

    /**
     * Valide les paramètres de calcul (déprécié - utiliser CommissionValidationService)
     * 
     * @param BusinessProfile $businessProfile
     * @param float $totalAmount
     * @param string $strategy
     * @return bool
     * @deprecated Utiliser CommissionValidationService::validateCompleteCalculation()
     */
    public function validateCalculationParameters(
        BusinessProfile $businessProfile, 
        float $totalAmount, 
        string $strategy
    ): bool {
        return $this->validationService->validateCompleteCalculation(
            $businessProfile, 
            $totalAmount, 
            $strategy
        );
    }

    /**
     * Obtient les statistiques de calcul des commissions
     * 
     * @return array
     */
    public function getCalculationStatistics(): array
    {
        return [
            'available_strategies' => array_keys($this->strategies),
            'default_strategy' => $this->defaultStrategy,
            'cache_stats' => $this->cacheService->getCacheStatistics(),
            'validation_rules' => $this->validationService->getValidationRules(),
            'cache_available' => $this->cacheService->isCacheAvailable(),
        ];
    }

    /**
     * Nettoie le cache des commissions
     * 
     * @param int|null $businessProfileId
     * @return bool
     */
    public function clearCommissionCache(?int $businessProfileId = null): bool
    {
        if ($businessProfileId) {
            return $this->cacheService->invalidateBusinessProfileCache($businessProfileId);
        }
        
        return $this->cacheService->invalidateAllCommissionCache();
    }

    /**
     * Obtient le service de validation
     * 
     * @return CommissionValidationService
     */
    public function getValidationService(): CommissionValidationService
    {
        return $this->validationService;
    }

    /**
     * Obtient le service de cache
     * 
     * @return CommissionCacheService
     */
    public function getCacheService(): CommissionCacheService
    {
        return $this->cacheService;
    }
}
