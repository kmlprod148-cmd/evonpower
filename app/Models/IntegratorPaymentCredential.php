<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Integrator Payment Credential Model
 * 
 * Stores encrypted payment gateway credentials for each integrator.
 * Supports both Stripe and CMI credentials.
 * 
 * @property int $id
 * @property int $integrator_id
 * @property string $gateway_type
 * @property string $environment
 * @property string $encrypted_credentials
 * @property string|null $public_key
 * @property string|null $webhook_url
 * @property string|null $merchant_id
 * @property bool $is_active
 * @property bool $is_validated
 * @property \Carbon\Carbon|null $last_validated_at
 * @property string|null $validation_error
 * @property array|null $settings
 * @property int|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class IntegratorPaymentCredential extends Model
{
    /**
     * Gateway types
     */
    public const GATEWAY_STRIPE = 'stripe';
    public const GATEWAY_CMI = 'cmi';
    
    /**
     * Environments
     */
    public const ENV_TEST = 'test';
    public const ENV_PRODUCTION = 'production';
    
    /**
     * The table associated with the model.
     */
    protected $table = 'integrator_payment_credentials';
    
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'integrator_id',
        'gateway_type',
        'environment',
        'encrypted_credentials',
        'public_key',
        'webhook_url',
        'merchant_id',
        'is_active',
        'is_validated',
        'last_validated_at',
        'validation_error',
        'settings',
        'created_by',
    ];
    
    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_validated' => 'boolean',
        'last_validated_at' => 'datetime',
        'settings' => 'array',
    ];
    
    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'encrypted_credentials',
    ];
    
    /**
     * Relation to integrator
     */
    public function integrator(): BelongsTo
    {
        return $this->belongsTo(Integrator::class);
    }
    
    /**
     * Relation to creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Store encrypted credentials
     * 
     * @param array $credentials
     * @return self
     */
    public function setCredentials(array $credentials): self
    {
        $this->encrypted_credentials = Crypt::encryptString(json_encode($credentials));
        return $this;
    }
    
    /**
     * Get decrypted credentials
     * 
     * @return array
     */
    public function getCredentials(): array
    {
        if (empty($this->encrypted_credentials)) {
            return [];
        }
        
        try {
            return json_decode(Crypt::decryptString($this->encrypted_credentials), true) ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to decrypt payment credentials', [
                'integrator_id' => $this->integrator_id,
                'gateway_type' => $this->gateway_type,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
    
    /**
     * Check if credentials are valid
     * 
     * @return bool
     */
    public function hasValidCredentials(): bool
    {
        $credentials = $this->getCredentials();
        
        if ($this->gateway_type === self::GATEWAY_STRIPE) {
            return !empty($credentials['secret_key']);
        }
        
        if ($this->gateway_type === self::GATEWAY_CMI) {
            return !empty($credentials['store_key']) && !empty($credentials['client_id']);
        }
        
        return false;
    }
    
    /**
     * Mark credentials as validated
     * 
     * @param bool $success
     * @param string|null $error
     * @return self
     */
    public function markAsValidated(bool $success, ?string $error = null): self
    {
        $this->is_validated = $success;
        $this->last_validated_at = now();
        $this->validation_error = $error;
        return $this;
    }
    
    /**
     * Scope for active credentials
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope for validated credentials
     */
    public function scopeValidated($query)
    {
        return $query->where('is_validated', true);
    }
    
    /**
     * Scope for specific gateway
     */
    public function scopeForGateway($query, string $gateway)
    {
        return $query->where('gateway_type', $gateway);
    }
    
    /**
     * Scope for specific environment
     */
    public function scopeForEnvironment($query, string $environment)
    {
        return $query->where('environment', $environment);
    }
    
    /**
     * Get Stripe-specific credential keys (sensitive)
     */
    public static function getStripeSensitiveKeys(): array
    {
        return [
            'secret_key',
            'webhook_secret',
            'client_secret',
        ];
    }
    
    /**
     * Get CMI-specific credential keys (sensitive)
     */
    public static function getCmiSensitiveKeys(): array
    {
        return [
            'store_key',
            'store_password',
            'client_id',
            'username',
            'password',
        ];
    }
    
    /**
     * Get public credential keys (non-sensitive)
     */
    public static function getPublicKeys(string $gateway): array
    {
        return match ($gateway) {
            self::GATEWAY_STRIPE => ['public_key', 'webhook_url'],
            self::GATEWAY_CMI => ['merchant_id', 'webhook_url'],
            default => [],
        };
    }
    
    /**
     * Mask sensitive data for logging
     * 
     * @param array $credentials
     * @return array
     */
    public static function maskSensitiveData(array $credentials): array
    {
        $masked = $credentials;
        
        foreach (self::getStripeSensitiveKeys() as $key) {
            if (isset($masked[$key]) && strlen($masked[$key]) > 4) {
                $masked[$key] = '****' . substr($masked[$key], -4);
            }
        }
        
        foreach (self::getCmiSensitiveKeys() as $key) {
            if (isset($masked[$key]) && strlen($masked[$key]) > 4) {
                $masked[$key] = '****' . substr($masked[$key], -4);
            }
        }
        
        return $masked;
    }
}
