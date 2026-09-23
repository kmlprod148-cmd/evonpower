<?php

namespace App\Services\Commission\Factory;

use App\Services\CommissionCalculationService;
use App\Services\Commission\Validation\CommissionValidationService;
use App\Services\Commission\Cache\CommissionCacheService;
use Illuminate\Support\Facades\App;

/**
 * Factory pour créer les services de commission
 * 
 * Centralise la création et la configuration des services
 * avec injection de dépendances appropriée
 */
class CommissionServiceFactory
{
    /**
     * Crée une instance du service de calcul de commission
     * 
     * @param array $config Configuration optionnelle
     * @return CommissionCalculationService
     */
    public static function createCommissionCalculationService(array $config = []): CommissionCalculationService
    {
        $validationService = new CommissionValidationService();
        $cacheService = new CommissionCacheService();
        
        return new CommissionCalculationService($validationService, $cacheService);
    }

    /**
     * Crée une instance du service de validation
     * 
     * @return CommissionValidationService
     */
    public static function createValidationService(): CommissionValidationService
    {
        return new CommissionValidationService();
    }

    /**
     * Crée une instance du service de cache
     * 
     * @return CommissionCacheService
     */
    public static function createCacheService(): CommissionCacheService
    {
        return new CommissionCacheService();
    }

    /**
     * Crée tous les services de commission avec configuration
     * 
     * @param array $config
     * @return array
     */
    public static function createAllServices(array $config = []): array
    {
        return [
            'calculation' => self::createCommissionCalculationService($config),
            'validation' => self::createValidationService(),
            'cache' => self::createCacheService(),
        ];
    }

    /**
     * Obtient une instance singleton du service de calcul
     * 
     * @return CommissionCalculationService
     */
    public static function getInstance(): CommissionCalculationService
    {
        return App::make(CommissionCalculationService::class);
    }
}
