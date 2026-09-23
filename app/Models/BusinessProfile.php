<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCreator;
use Illuminate\Support\Facades\Auth;


class BusinessProfile extends Model
{
    use HasFactory, HasCreator;
    /**
     * Auto-populate created_by_role based on the creator's primary role
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->created_by_role)) {
                $user = null;

                if (!empty($model->created_by)) {
                    $user = User::find($model->created_by);
                } elseif (!empty($model->created_by_id) && (!isset($model->created_by_type) || $model->created_by_type === User::class)) {
                    $user = User::find($model->created_by_id);
                } elseif (Auth::check()) {
                    $user = Auth::user();
                }

                if ($user && method_exists($user, 'hasRole')) {
                    $role = null;
                    if ($user->hasRole('admin')) {
                        $role = 'admin';
                    } elseif ($user->hasRole('integrator')) {
                        $role = 'integrator';
                    } elseif ($user->hasRole('operator')) {
                        $role = 'operator';
                    } elseif ($user->hasRole('partner')) {
                        $role = 'partner';
                    } else {
                        if (method_exists($user, 'getRoleNames')) {
                            $first = $user->getRoleNames()->first();
                            if ($first) {
                                $role = $first;
                            }
                        }
                    }

                    if ($role) {
                        $model->created_by_role = $role;
                    }
                }
            }
        });

        // Propager les changements aux bornes après mise à jour du Business Profile
        static::updated(function (self $model) {
            // Vérifier si les champs importants ont changé (pour éviter les propagations inutiles)
            $importantFields = [
                'transaction_fee_config',
                'charge_fee_config',
                'admin_fee_fixed',
                'admin_fee_percentage',
                'integrator_fee_fixed',
                'integrator_fee_percentage',
                'partner_fee_fixed',
                'partner_fee_percentage',
                'operator_commission',
                'integrator_commission',
                'owner_commission',
                'partner_commission'
            ];

            $hasImportantChanges = false;
            foreach ($importantFields as $field) {
                if ($model->wasChanged($field)) {
                    $hasImportantChanges = true;
                    break;
                }
            }

            if ($hasImportantChanges) {
                // Utiliser dispatch pour exécuter en arrière-plan (optionnel, pour améliorer les performances)
                try {
                    $propagationService = new \App\Services\BusinessProfilePropagationService();
                    // On log seulement, la propagation réelle est faite dans le contrôleur
                    \Illuminate\Support\Facades\Log::info('Business Profile important fields updated, propagation may be needed', [
                        'business_profile_id' => $model->id,
                        'changed_fields' => array_filter($importantFields, function($field) use ($model) {
                            return $model->wasChanged($field);
                        })
                    ]);
                } catch (\Exception $e) {
                    // Ignorer les erreurs dans l'événement
                    \Illuminate\Support\Facades\Log::warning('Error in BusinessProfile updated event', [
                        'business_profile_id' => $model->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });
    }


    protected $fillable = [
        'name',
        'description',
        'is_public',
        'is_active',
        'target_audience',
        'maintenance_fee_type',
        'maintenance_fee_amount',
        'transaction_fee_config',
        'charge_fee_config',
        'terminal_fee_amount',
        'terminal_fee_period',
        'base_fee_amount',
        'terminal_count',
        'operator_commission',
        'integrator_commission',
        'owner_commission',
        'partner_commission',
        'admin_fee_fixed',
        'admin_fee_percentage',
        'integrator_fee_fixed',
        'integrator_fee_percentage',
        'partner_fee_fixed',
        'partner_fee_percentage',
        'partner_id',
        'integrator_id',
        'created_by',
        'created_by_role',
        'created_by_type',
        'created_by_id',
        'pricing_plan_id',
        // Pricing columns for hierarchical transaction calculation
        'price_per_kwh',
        'price_per_hour',
        'fixed_price',
        // Nouveaux champs pour la gestion des transactions
        'transaction_management_config',
        'payment_processing_config',
        'wire_transfer_config',
        'admin_contact_name',
        'admin_contact_email',
        'admin_contact_phone',
        'integrator_contact_name',
        'integrator_contact_email',
        'integrator_contact_phone',
        'admin_bank_account',
        'integrator_bank_account',
        'operator_bank_account',
        'handles_admin_debits',
        'handles_integrator_debits',
        'handles_client_payments',
        'handles_wire_transfers',
        'min_transaction_amount',
        'max_transaction_amount',
        'daily_limit',
        'monthly_limit',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'transaction_fee_config' => 'json',
        'charge_fee_config' => 'json',
        'target_audience' => 'json',
        'transaction_management_config' => 'json',
        'payment_processing_config' => 'json',
        'wire_transfer_config' => 'json',
        'handles_admin_debits' => 'boolean',
        'handles_integrator_debits' => 'boolean',
        'handles_client_payments' => 'boolean',
        'handles_wire_transfers' => 'boolean',
        'min_transaction_amount' => 'decimal:2',
        'max_transaction_amount' => 'decimal:2',
        'daily_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2',
        // Pricing columns
        'price_per_kwh' => 'decimal:4',
        'price_per_hour' => 'decimal:2',
        'fixed_price' => 'decimal:2',
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function integrator()
    {
        return $this->belongsTo(Integrator::class);
    }

    /**
     * Get the partners associated with this business profile.
     */
    public function partners()
    {
        return $this->hasMany(Partner::class);
    }

    /**
     * Get the integrators associated with this business profile.
     */
    public function integrators()
    {
        return $this->hasMany(Integrator::class);
    }


    /**
     * Get the transaction management configuration
     */
    public function getTransactionManagementConfig(): array
    {
        return $this->transaction_management_config ?? [
            'admin_debits' => [
                'enabled' => $this->handles_admin_debits ?? false,
                'contact' => [
                    'name' => $this->admin_contact_name,
                    'email' => $this->admin_contact_email,
                    'phone' => $this->admin_contact_phone
                ],
                'bank_account' => $this->admin_bank_account
            ],
            'integrator_debits' => [
                'enabled' => $this->handles_integrator_debits ?? false,
                'contact' => [
                    'name' => $this->integrator_contact_name,
                    'email' => $this->integrator_contact_email,
                    'phone' => $this->integrator_contact_phone
                ],
                'bank_account' => $this->integrator_bank_account
            ],
            'client_payments' => [
                'enabled' => $this->handles_client_payments ?? false,
                'bank_account' => $this->operator_bank_account
            ],
            'wire_transfers' => [
                'enabled' => $this->handles_wire_transfers ?? false,
                'limits' => [
                    'min_amount' => $this->min_transaction_amount,
                    'max_amount' => $this->max_transaction_amount,
                    'daily_limit' => $this->daily_limit,
                    'monthly_limit' => $this->monthly_limit
                ]
            ]
        ];
    }

    /**
     * Check if this business profile handles admin debits
     */
    public function handlesAdminDebits(): bool
    {
        return $this->handles_admin_debits ?? false;
    }

    /**
     * Check if this business profile handles integrator debits
     */
    public function handlesIntegratorDebits(): bool
    {
        return $this->handles_integrator_debits ?? false;
    }

    /**
     * Check if this business profile handles client payments
     */
    public function handlesClientPayments(): bool
    {
        return $this->handles_client_payments ?? false;
    }

    /**
     * Check if this business profile handles wire transfers
     */
    public function handlesWireTransfers(): bool
    {
        return $this->handles_wire_transfers ?? false;
    }

    /**
     * Get the responsible contact for admin debits
     */
    public function getAdminContact(): array
    {
        return [
            'name' => $this->admin_contact_name,
            'email' => $this->admin_contact_email,
            'phone' => $this->admin_contact_phone,
            'bank_account' => $this->admin_bank_account
        ];
    }

    /**
     * Get the responsible contact for integrator debits
     */
    public function getIntegratorContact(): array
    {
        return [
            'name' => $this->integrator_contact_name,
            'email' => $this->integrator_contact_email,
            'phone' => $this->integrator_contact_phone,
            'bank_account' => $this->integrator_bank_account
        ];
    }

    /**
     * Scope to filter business profiles based on user permissions.
     */
    public function scopeVisibleToUser($query, $user)
    {
        if ($user->hasRole('admin')) {
            return $query; // Admin sees all
        }

        if ($user->hasRole('integrator') && $user->integrator_id) {
            return $query->where('integrator_id', $user->integrator_id);
        }

        if ($user->hasRole('partner') && $user->partner_id) {
            return $query->where('partner_id', $user->partner_id);
        }

        // For other roles or no specific role, show only public profiles
        return $query->where('is_public', true);
    }

    /**
     * Get the business profile's account.
     */
    public function account(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Account::class, 'accountable');
    }

    /**
     * Get the pricing plan for the business profile.
     */
    public function pricingPlan()
    {
        return $this->belongsTo(PricingPlan::class);
    }

    /**
     * Get the pricing plans associated with this business profile (many-to-many).
     */
    public function pricingPlans()
    {
        return $this->belongsToMany(PricingPlan::class, 'business_profile_pricing_plan')
                    ->withTimestamps();
    }

    /**
     * Accessor for transaction_fee_config JSON field
     */
    public function getTransactionFeeConfigAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }

    /**
     * Accessor for charge_fee_config JSON field
     */
    public function getChargeFeeConfigAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }

    /**
     * Accessor for target_audience JSON field
     */
    public function getTargetAudienceAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }
}