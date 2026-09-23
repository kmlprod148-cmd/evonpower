<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Integrator;
use App\Models\Partner;
use App\Services\Gateways\Contracts\GatewayInterface;
use App\Services\Gateways\CmiGateway;
use App\Services\Gateways\StripeGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Payment Gateway Service - Factory/Strategy Pattern
 * 
 * Resolves which payment gateway to use for a given charge point based on:
 * 1. Charge Point → Partner → Integrator settings
 * 2. If integrator has own credentials → use those
 * 3. Otherwise → use default gateway from admin config
 * 
 * Now supports the new IntegratorCredentialService for secure
 * credential storage with encryption.
 */
class PaymentGatewayService
{
    /**
     * Default gateway type from admin config
     */
    protected string $defaultGateway;

    /**
     * Cache TTL for gateway resolution (5 minutes)
     */
    protected const CACHE_TTL = 300;

    /**
     * Cache prefix for gateway resolution
     */
    protected const CACHE_PREFIX = 'payment_gateway_';

    /**
     * Credential service for integrators
     */
    protected ?IntegratorCredentialService $credentialService;

    public function __construct(?IntegratorCredentialService $credentialService = null)
    {
        $this->defaultGateway = $this->getDefaultGatewayFromConfig();
        $this->credentialService = $credentialService;
    }

    /**
     * Resolve which gateway to use for a given charge point
     * 
     * Logic: chargePoint → partner → integrator → check if integrator has own credentials
     * - If integrator has CMI credentials → return CmiGateway
     * - If integrator has Stripe credentials → return StripeGateway
     * - Otherwise → return default gateway from admin config
     *
     * @param ChargingPoint $chargePoint The charge point to resolve gateway for
     * @return GatewayInterface
     */
    public function resolveGateway(ChargingPoint $chargePoint): GatewayInterface
    {
        $cacheKey = self::CACHE_PREFIX . 'chargepoint_' . $chargePoint->id;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($chargePoint) {
            // First, try to get gateway from integrator settings
            $gatewayType = $this->resolveGatewayType($chargePoint);

            Log::info('PaymentGatewayService: Resolving gateway', [
                'charge_point_id' => $chargePoint->id,
                'resolved_gateway' => $gatewayType,
            ]);

            return $this->createGateway($gatewayType, $chargePoint);
        });
    }

    /**
     * Resolve gateway type based on integrator settings
     *
     * @param ChargingPoint $chargePoint
     * @return string
     */
    protected function resolveGatewayType(ChargingPoint $chargePoint): string
    {
        // Get integrator from charge point (directly or through partner)
        $integrator = $this->getIntegrator($chargePoint);

        if (!$integrator) {
            Log::warning('PaymentGatewayService: No integrator found, using default gateway', [
                'charge_point_id' => $chargePoint->id,
            ]);
            return $this->defaultGateway;
        }

        // Check if integrator has custom payment credentials
        // First check CMI credentials
        if ($this->integratorHasCmiCredentials($integrator)) {
            return 'cmi';
        }

        // Then check Stripe credentials
        if ($this->integratorHasStripeCredentials($integrator)) {
            return 'stripe';
        }

        // Fall back to default gateway
        return $this->defaultGateway;
    }

    /**
     * Get integrator from charge point (directly or through partner)
     *
     * @param ChargingPoint $chargePoint
     * @return Integrator|null
     */
    protected function getIntegrator(ChargingPoint $chargePoint): ?Integrator
    {
        // First check if charge point has integrator directly
        if ($chargePoint->integrator_id) {
            return $chargePoint->integrator;
        }

        // Then check through partner
        if ($chargePoint->partner_id) {
            $partner = $chargePoint->partner;
            if ($partner && $partner->integrator_id) {
                return $partner->integrator;
            }
        }

        return null;
    }

    /**
     * Check if integrator has CMI credentials configured
     *
     * @param Integrator $integrator
     * @return bool
     */
    protected function integratorHasCmiCredentials(Integrator $integrator): bool
    {
        // Use new credential service if available
        if ($this->credentialService) {
            return $this->credentialService->hasCredentials($integrator, 'cmi');
        }
        
        // Legacy fallback
        $cmiSettings = $this->getIntegratorPaymentSettings($integrator, 'cmi');
        
        return !empty($cmiSettings['client_id']) && !empty($cmiSettings['store_key']);
    }

    /**
     * Check if integrator has Stripe credentials configured
     *
     * @param Integrator $integrator
     * @return bool
     */
    protected function integratorHasStripeCredentials(Integrator $integrator): bool
    {
        // Use new credential service if available
        if ($this->credentialService) {
            return $this->credentialService->hasCredentials($integrator, 'stripe');
        }
        
        // Legacy fallback
        $stripeSettings = $this->getIntegratorPaymentSettings($integrator, 'stripe');
        
        return !empty($stripeSettings['secret_key']);
    }

    /**
     * Get payment settings for an integrator
     *
     * @param Integrator $integrator
     * @param string $gateway
     * @return array
     */
    protected function getIntegratorPaymentSettings(Integrator $integrator, string $gateway): array
    {
        // Try to use new credential service if available
        if ($this->credentialService) {
            $credentials = $this->credentialService->getAllCredentials($integrator, $gateway);
            if (!empty($credentials)) {
                return $credentials;
            }
        }
        
        // Fallback to legacy method - Try to get from integrator's metadata JSON field
        $metadata = $integrator->metadata ?? [];
        
        if (isset($metadata['payment_settings'][$gateway])) {
            return $metadata['payment_settings'][$gateway];
        }

        // Try to get from separate columns (if they exist)
        $key = "{$gateway}_credentials";
        if ($integrator->getAttribute($key)) {
            try {
                return json_decode(decrypt($integrator->getAttribute($key)), true) ?? [];
            } catch (\Exception $e) {
                Log::error("Failed to decrypt {$gateway} credentials", [
                    'integrator_id' => $integrator->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [];
    }

    /**
     * Create a gateway instance based on type
     *
     * @param string $gatewayType
     * @param ChargingPoint|null $chargePoint
     * @return GatewayInterface
     */
    protected function createGateway(string $gatewayType, ?ChargingPoint $chargePoint = null): GatewayInterface
    {
        return match ($gatewayType) {
            'cmi' => new CmiGateway($chargePoint),
            'stripe' => new StripeGateway($chargePoint),
            default => $this->createDefaultGateway(),
        };
    }

    /**
     * Create the default gateway based on admin config
     *
     * @return GatewayInterface
     */
    protected function createDefaultGateway(): GatewayInterface
    {
        return match ($this->defaultGateway) {
            'cmi' => new CmiGateway(),
            'stripe' => new StripeGateway(),
            default => new CmiGateway(), // Default to CMI for Morocco
        };
    }

    /**
     * Get default gateway from admin config
     *
     * @return string
     */
    protected function getDefaultGatewayFromConfig(): string
    {
        // Try to get from admin settings
        $settings = app(AdminConfigurationService::class);
        $defaultMethod = $settings->getSetting(
            'payments',
            'default_payment_method',
            config('payments.default_method', 'cmi')
        );

        return in_array($defaultMethod, ['cmi', 'stripe'], true)
            ? $defaultMethod
            : 'cmi';
    }

    /**
     * Get gateway for a specific integrator (used for admin operations)
     *
     * @param Integrator $integrator
     * @return GatewayInterface
     */
    public function getGatewayForIntegrator(Integrator $integrator): GatewayInterface
    {
        if ($this->integratorHasCmiCredentials($integrator)) {
            return new CmiGateway(null, $integrator);
        }

        if ($this->integratorHasStripeCredentials($integrator)) {
            return new StripeGateway(null, $integrator);
        }

        return $this->createDefaultGateway();
    }

    /**
     * Force clear cache for a charge point (useful after integrator changes)
     *
     * @param int $chargePointId
     * @return void
     */
    public function clearCache(int $chargePointId): void
    {
        Cache::forget(self::CACHE_PREFIX . 'chargepoint_' . $chargePointId);
    }

    /**
     * Get all available gateways
     *
     * @return array
     */
    public function getAvailableGateways(): array
    {
        return [
            'cmi' => [
                'name' => 'CMI (Maroc)',
                'type' => 'cmi',
                'description' => 'CMI - Centre Monétique Interbancaire',
            ],
            'stripe' => [
                'name' => 'Stripe (International)',
                'type' => 'stripe',
                'description' => 'Stripe payment gateway',
            ],
        ];
    }
}
