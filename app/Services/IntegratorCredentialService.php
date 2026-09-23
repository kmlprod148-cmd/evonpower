<?php

namespace App\Services;

use App\Models\Integrator;
use App\Models\IntegratorPaymentCredential;
use App\Services\Gateways\StripeGateway;
use App\Services\Gateways\CmiGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Integrator Credential Service
 * 
 * Manages secure storage and retrieval of payment gateway credentials
 * for third-party integrators. Provides encryption, validation,
 * and dynamic gateway selection.
 * 
 * Features:
 * - Secure credential storage with Laravel encryption
 * - Credential validation
 * - Gateway resolution for integrators
 * - PCI compliance helpers
 */
class IntegratorCredentialService
{
    /**
     * Cache TTL for resolved credentials (10 minutes)
     */
    protected const CACHE_TTL = 600;
    
    /**
     * Cache prefix for credentials
     */
    protected const CACHE_PREFIX = 'integrator_credentials_';
    
    /**
     * Get payment credentials for an integrator
     * 
     * @param Integrator $integrator
     * @param string $gatewayType
     * @param string $environment
     * @return IntegratorPaymentCredential|null
     */
    public function getCredentials(
        Integrator $integrator,
        string $gatewayType,
        string $environment = 'production'
    ): ?IntegratorPaymentCredential {
        $cacheKey = self::CACHE_PREFIX . $integrator->id . '_' . $gatewayType . '_' . $environment;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($integrator, $gatewayType, $environment) {
            return IntegratorPaymentCredential::where('integrator_id', $integrator->id)
                ->forGateway($gatewayType)
                ->forEnvironment($environment)
                ->active()
                ->first();
        });
    }
    
    /**
     * Store payment credentials for an integrator
     * 
     * @param Integrator $integrator
     * @param string $gatewayType
     * @param array $credentials
     * @param string $environment
     * @param array $publicData
     * @return IntegratorPaymentCredential
     */
    public function storeCredentials(
        Integrator $integrator,
        string $gatewayType,
        array $credentials,
        string $environment = 'production',
        array $publicData = []
    ): IntegratorPaymentCredential {
        // Validate credentials before storing
        $this->validateCredentials($gatewayType, $credentials);
        
        // Check if credentials already exist
        $credential = $this->getCredentials($integrator, $gatewayType, $environment);
        
        if (!$credential) {
            $credential = new IntegratorPaymentCredential([
                'integrator_id' => $integrator->id,
                'gateway_type' => $gatewayType,
                'environment' => $environment,
                'created_by' => auth()->id(),
            ]);
        }
        
        // Store encrypted credentials
        $credential->setCredentials($credentials);
        
        // Store public data
        $credential->public_key = $publicData['public_key'] ?? null;
        $credential->webhook_url = $publicData['webhook_url'] ?? null;
        $credential->merchant_id = $publicData['merchant_id'] ?? null;
        
        // Reset validation status
        $credential->is_validated = false;
        $credential->validation_error = null;
        
        $credential->save();
        
        // Clear cache
        $this->clearCache($integrator, $gatewayType, $environment);
        
        Log::info('Payment credentials stored for integrator', [
            'integrator_id' => $integrator->id,
            'gateway_type' => $gatewayType,
            'environment' => $environment,
            'has_credentials' => !empty($credentials),
        ]);
        
        return $credential;
    }
    
    /**
     * Validate payment credentials
     * 
     * @param string $gatewayType
     * @param array $credentials
     * @return array
     * @throws \InvalidArgumentException
     */
    public function validateCredentials(string $gatewayType, array $credentials): array
    {
        $validationRules = $this->getValidationRules($gatewayType);
        
        $validator = Validator::make($credentials, $validationRules);
        
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            
            Log::warning('Payment credentials validation failed', [
                'gateway_type' => $gatewayType,
                'errors' => $errors,
            ]);
            
            throw new \InvalidArgumentException(
                'Invalid credentials: ' . implode(', ', array_map(fn($e) => implode(', ', $e), $errors))
            );
        }
        
        return ['valid' => true];
    }
    
    /**
     * Get validation rules for gateway credentials
     * 
     * @param string $gatewayType
     * @return array
     */
    protected function getValidationRules(string $gatewayType): array
    {
        return match ($gatewayType) {
            'stripe' => [
                'secret_key' => 'required|string|min:20',
                'public_key' => 'nullable|string',
                'webhook_secret' => 'nullable|string|min:10',
            ],
            'cmi' => [
                'store_key' => 'required|string',
                'client_id' => 'required|string',
                'store_password' => 'nullable|string',
                'username' => 'nullable|string',
                'password' => 'nullable|string',
            ],
            default => throw new \InvalidArgumentException("Unsupported gateway type: {$gatewayType}"),
        };
    }
    
    /**
     * Test credentials by making a test request
     * 
     * @param IntegratorPaymentCredential $credential
     * @return array
     */
    public function testCredentials(IntegratorPaymentCredential $credential): array
    {
        try {
            $credentials = $credential->getCredentials();
            
            if ($credential->gateway_type === 'stripe') {
                return $this->testStripeCredentials($credentials, $credential->environment);
            }
            
            if ($credential->gateway_type === 'cmi') {
                return $this->testCmiCredentials($credentials);
            }
            
            return ['success' => false, 'error' => 'Unsupported gateway type'];
            
        } catch (\Exception $e) {
            Log::error('Credential test failed', [
                'credential_id' => $credential->id,
                'gateway_type' => $credential->gateway_type,
                'error' => $e->getMessage(),
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Test Stripe credentials
     * 
     * @param array $credentials
     * @param string $environment
     * @return array
     */
    protected function testStripeCredentials(array $credentials, string $environment): array
    {
        try {
            // Create a temporary gateway instance to test
            $gateway = new StripeGateway();
            
            // Use reflection to set test credentials
            $reflection = new \ReflectionClass($gateway);
            $property = $reflection->getProperty('secretKey');
            $property->setAccessible(true);
            $property->setValue($gateway, $credentials['secret_key'] ?? '');
            
            // Test would be done by making a minimal API call
            // For now, just validate the key format
            $isTestKey = str_starts_with($credentials['secret_key'] ?? '', 'sk_test_');
            $isProdKey = str_starts_with($credentials['secret_key'] ?? '', 'sk_live_');
            
            if (!$isTestKey && !$isProdKey) {
                return ['success' => false, 'error' => 'Invalid Stripe key format'];
            }
            
            if ($environment === 'production' && $isTestKey) {
                return ['success' => false, 'error' => 'Test key used in production environment'];
            }
            
            if ($environment === 'test' && $isProdKey) {
                return ['success' => false, 'error' => 'Production key used in test environment'];
            }
            
            return ['success' => true, 'message' => 'Credentials format valid'];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Test CMI credentials
     * 
     * @param array $credentials
     * @return array
     */
    protected function testCmiCredentials(array $credentials): array
    {
        // CMI credentials validation
        if (empty($credentials['store_key']) || empty($credentials['client_id'])) {
            return ['success' => false, 'error' => 'Missing required CMI credentials'];
        }
        
        return ['success' => true, 'message' => 'CMI credentials format valid'];
    }
    
    /**
     * Check if integrator has payment credentials
     * 
     * @param Integrator $integrator
     * @param string $gatewayType
     * @return bool
     */
    public function hasCredentials(Integrator $integrator, string $gatewayType): bool
    {
        // First check new table
        $credential = $this->getCredentials($integrator, $gatewayType);
        if ($credential && $credential->hasValidCredentials()) {
            return true;
        }
        
        // Fallback to old method (integrator metadata)
        return $this->hasLegacyCredentials($integrator, $gatewayType);
    }
    
    /**
     * Check legacy credentials in integrator metadata
     * 
     * @param Integrator $integrator
     * @param string $gatewayType
     * @return bool
     */
    protected function hasLegacyCredentials(Integrator $integrator, string $gatewayType): bool
    {
        $metadata = $integrator->metadata ?? [];
        
        // Check metadata payment_settings
        if (isset($metadata['payment_settings'][$gatewayType])) {
            $settings = $metadata['payment_settings'][$gatewayType];
            return !empty($settings['secret_key'] ?? $settings['store_key']);
        }
        
        // Check encrypted fields
        $field = "{$gatewayType}_credentials";
        if ($integrator->getAttribute($field)) {
            try {
                $decrypted = json_decode(decrypt($integrator->getAttribute($field)), true);
                return !empty($decrypted);
            } catch (\Exception $e) {
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Get credentials including legacy support
     * 
     * @param Integrator $integrator
     * @param string $gatewayType
     * @param string $environment
     * @return array
     */
    public function getAllCredentials(
        Integrator $integrator,
        string $gatewayType,
        string $environment = 'production'
    ): array {
        // First try new table
        $credential = $this->getCredentials($integrator, $gatewayType, $environment);
        
        if ($credential && $credential->hasValidCredentials()) {
            return $credential->getCredentials();
        }
        
        // Fallback to legacy
        return $this->getLegacyCredentials($integrator, $gatewayType);
    }
    
    /**
     * Get legacy credentials from integrator
     * 
     * @param Integrator $integrator
     * @param string $gatewayType
     * @return array
     */
    protected function getLegacyCredentials(Integrator $integrator, string $gatewayType): array
    {
        $metadata = $integrator->metadata ?? [];
        
        // Check metadata payment_settings
        if (isset($metadata['payment_settings'][$gatewayType])) {
            return $metadata['payment_settings'][$gatewayType];
        }
        
        // Check encrypted fields
        $field = "{$gatewayType}_credentials";
        $encrypted = $integrator->getAttribute($field);
        
        if ($encrypted) {
            try {
                return json_decode(decrypt($encrypted), true) ?? [];
            } catch (\Exception $e) {
                Log::error('Failed to decrypt legacy credentials', [
                    'integrator_id' => $integrator->id,
                    'gateway_type' => $gatewayType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return [];
    }
    
    /**
     * Clear credential cache
     * 
     * @param Integrator $integrator
     * @param string $gatewayType
     * @param string $environment
     * @return void
     */
    public function clearCache(
        Integrator $integrator,
        string $gatewayType,
        string $environment = 'production'
    ): void {
        Cache::forget(self::CACHE_PREFIX . $integrator->id . '_' . $gatewayType . '_' . $environment);
        
        // Also clear for both environments
        Cache::forget(self::CACHE_PREFIX . $integrator->id . '_' . $gatewayType . '_test');
        Cache::forget(self::CACHE_PREFIX . $integrator->id . '_' . $gatewayType . '_production');
    }
    
    /**
     * Get all credentials for an integrator
     * 
     * @param Integrator $integrator
     * @return array
     */
    public function getAllIntegratorCredentials(Integrator $integrator): array
    {
        $credentials = IntegratorPaymentCredential::where('integrator_id', $integrator->id)
            ->active()
            ->get();
        
        return $credentials->map(function ($cred) {
            return [
                'id' => $cred->id,
                'gateway_type' => $cred->gateway_type,
                'environment' => $cred->environment,
                'is_validated' => $cred->is_validated,
                'last_validated_at' => $cred->last_validated_at?->toIso8601String(),
                'public_key' => $cred->public_key,
                'merchant_id' => $cred->merchant_id,
                'webhook_url' => $cred->webhook_url,
            ];
        })->toArray();
    }
    
    /**
     * Delete credentials for an integrator
     * 
     * @param Integrator $integrator
     * @param string $gatewayType
     * @param string $environment
     * @return bool
     */
    public function deleteCredentials(
        Integrator $integrator,
        string $gatewayType,
        string $environment = 'production'
    ): bool {
        $credential = $this->getCredentials($integrator, $gatewayType, $environment);
        
        if ($credential) {
            $credential->delete();
            $this->clearCache($integrator, $gatewayType, $environment);
            
            Log::info('Payment credentials deleted for integrator', [
                'integrator_id' => $integrator->id,
                'gateway_type' => $gatewayType,
                'environment' => $environment,
            ]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Migrate legacy credentials to new table
     * 
     * @param Integrator $integrator
     * @return array
     */
    public function migrateLegacyCredentials(Integrator $integrator): array
    {
        $migrated = [];
        
        foreach (['stripe', 'cmi'] as $gatewayType) {
            $legacy = $this->getLegacyCredentials($integrator, $gatewayType);
            
            if (!empty($legacy)) {
                // Determine environment based on key
                $environment = 'production';
                if ($gatewayType === 'stripe' && isset($legacy['secret_key'])) {
                    $environment = str_starts_with($legacy['secret_key'], 'sk_test_') ? 'test' : 'production';
                }
                
                // Extract public data
                $publicData = [
                    'public_key' => $legacy['public_key'] ?? null,
                    'webhook_url' => $legacy['webhook_url'] ?? null,
                ];
                
                $this->storeCredentials($integrator, $gatewayType, $legacy, $environment, $publicData);
                
                $migrated[] = $gatewayType;
                
                Log::info('Legacy credentials migrated', [
                    'integrator_id' => $integrator->id,
                    'gateway_type' => $gatewayType,
                    'environment' => $environment,
                ]);
            }
        }
        
        return $migrated;
    }
}
