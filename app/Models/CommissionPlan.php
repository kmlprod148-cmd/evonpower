<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionPlan extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'admin_percentage',     // Pourcentage pour l'administrateur
        'integrator_percentage', // Pourcentage pour l'intégrateur
        'partner_percentage',    // Pourcentage pour le partenaire
        'min_transaction_value', // Valeur minimale de transaction pour ce plan
        'max_transaction_value', // Valeur maximale de transaction pour ce plan (null = pas de limite)
        'is_default',           // Plan par défaut
        'is_active',            // Plan actif
        'created_by_type',      // 'admin', 'integrator'
        'created_by_id',        // ID de l'entité qui a créé ce plan
        'applies_to_type',      // 'global', 'integrator', 'partner', 'group'
        'applies_to_id',        // ID de l'entité à laquelle ce plan s'applique
        'priority',             // Priorité du plan (valeur plus élevée = priorité plus élevée)
        'exclusions',           // Règles d'exclusion JSON (ex: types de plans tarifaires exclus)
        'valid_from',           // Date de début de validité
        'valid_until',          // Date de fin de validité
        'transaction_manager',  // Qui gère les transactions ('admin', 'integrator', 'partner', 'shared')
        'transaction_permissions', // Permissions spécifiques pour chaque partie (JSON)
        'requires_approval',    // Si les modifications nécessitent une approbation
        'approval_workflow',    // Flux d'approbation (JSON)
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array
     */
    protected $casts = [
        'admin_percentage' => 'float',
        'integrator_percentage' => 'float',
        'partner_percentage' => 'float',
        'min_transaction_value' => 'float',
        'max_transaction_value' => 'float',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'exclusions' => 'json',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'transaction_permissions' => 'json',
        'requires_approval' => 'boolean',
        'approval_workflow' => 'json',
    ];

    /**
     * Obtenir l'entité qui a créé ce plan de commission.
     */
    public function createdBy()
    {
        if ($this->created_by_type === 'admin') {
            return $this->belongsTo(User::class, 'created_by_id')->where('role', 'admin');
        } elseif ($this->created_by_type === 'integrator') {
            return $this->belongsTo(Integrator::class, 'created_by_id');
        }
        
        return null;
    }

    /**
     * Obtenir l'entité à laquelle ce plan s'applique.
     */
    public function appliesTo()
    {
        if ($this->applies_to_type === 'integrator') {
            return $this->belongsTo(Integrator::class, 'applies_to_id');
        } elseif ($this->applies_to_type === 'partner') {
            return $this->belongsTo(Partner::class, 'applies_to_id');
        } elseif ($this->applies_to_type === 'group') {
            return $this->belongsTo(Group::class, 'applies_to_id');
        }
        
        return null;
    }

    /**
     * Les transactions associées à ce plan de commission.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'commission_plan_id');
    }

    /**
     * Scope pour les plans actifs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now());
            })
            ->where(function($q) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', now());
            });
    }

    /**
     * Vérifier si ce plan de commission est applicable à une transaction spécifique.
     *
     * @param float $transactionValue
     * @param array $context Contexte de la transaction
     * @return boolean
     */
    public function isApplicable($transactionValue, array $context = [])
    {
        // Vérifier si la transaction est dans la plage de valeurs
        if ($this->min_transaction_value !== null && $transactionValue < $this->min_transaction_value) {
            return false;
        }
        
        if ($this->max_transaction_value !== null && $transactionValue > $this->max_transaction_value) {
            return false;
        }
        
        // Vérifier les exclusions
        if ($this->exclusions && isset($context['pricing_plan_type'])) {
            $exclusions = $this->exclusions;
            if (isset($exclusions['excluded_pricing_plan_types']) && 
                in_array($context['pricing_plan_type'], $exclusions['excluded_pricing_plan_types'])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Calcule les commissions pour une transaction.
     *
     * @param float $transactionValue
     * @return array
     */
    public function calculateCommissions($transactionValue)
    {
        $adminCommission = round($transactionValue * ($this->admin_percentage / 100), 2);
        $integratorCommission = round($transactionValue * ($this->integrator_percentage / 100), 2);
        $partnerCommission = round($transactionValue * ($this->partner_percentage / 100), 2);
        
        return [
            'admin' => $adminCommission,
            'integrator' => $integratorCommission,
            'partner' => $partnerCommission,
            'total' => $adminCommission + $integratorCommission + $partnerCommission
        ];
    }

    /**
     * Obtenir le plan de commission par défaut.
     *
     * @return self
     */
    public static function getDefault()
    {
        return self::where('is_default', true)->where('is_active', true)->first()
            ?? self::where('is_active', true)->orderBy('created_at')->first();
    }
    
    /**
     * Vérifie si une entité a la permission de gérer les transactions pour ce plan.
     *
     * @param string $entityType 'admin', 'integrator', ou 'partner'
     * @param int|null $entityId ID de l'entité (si applicable)
     * @param string $permission Type de permission ('view', 'edit', 'approve', etc.)
     * @return boolean
     */
    public function hasTransactionPermission(string $entityType, ?int $entityId = null, string $permission = 'manage')
    {
        // Si l'entité est le gestionnaire principal des transactions, elle a toutes les permissions
        if ($this->transaction_manager === $entityType) {
            return true;
        }
        
        // Si le mode est partagé, vérifier les permissions spécifiques
        if ($this->transaction_manager === 'shared' && $this->transaction_permissions) {
            $permissions = $this->transaction_permissions;
            
            // Vérifier si l'entité a la permission spécifique
            if (isset($permissions[$entityType])) {
                if (is_array($permissions[$entityType])) {
                    // Si les permissions sont un tableau, vérifier si la permission spécifique existe
                    return in_array($permission, $permissions[$entityType]);
                } else {
                    // Si c'est une valeur booléenne, elle représente toutes les permissions
                    return (bool) $permissions[$entityType];
                }
            }
        }
        
        return false;
    }
    
    /**
     * Vérifie si une modification nécessite une approbation selon le workflow.
     *
     * @param string $action Type d'action ('create_transaction', 'edit_commission', etc.)
     * @return boolean
     */
    public function requiresApprovalFor(string $action)
    {
        if (!$this->requires_approval) {
            return false;
        }
        
        if ($this->approval_workflow) {
            $workflow = $this->approval_workflow;
            
            // Vérifier si l'action spécifique nécessite une approbation
            if (isset($workflow['actions']) && is_array($workflow['actions'])) {
                return in_array($action, $workflow['actions']);
            }
            
            // Si aucune action spécifique n'est définie, toutes les actions nécessitent une approbation
            return true;
        }
        
        // Par défaut, si requires_approval est true mais aucun workflow n'est défini,
        // toutes les actions nécessitent une approbation
        return true;
    }
    
    /**
     * Obtient les approbateurs pour une action spécifique.
     *
     * @param string $action Type d'action
     * @return array Liste des types d'entités qui doivent approuver ('admin', 'integrator', 'partner')
     */
    public function getApproversFor(string $action)
    {
        if (!$this->requires_approval || !$this->approval_workflow) {
            return [];
        }
        
        $workflow = $this->approval_workflow;
        
        // Vérifier s'il y a des approbateurs spécifiques pour cette action
        if (isset($workflow['approvers'][$action]) && is_array($workflow['approvers'][$action])) {
            return $workflow['approvers'][$action];
        }
        
        // Sinon, utiliser les approbateurs par défaut
        if (isset($workflow['default_approvers']) && is_array($workflow['default_approvers'])) {
            return $workflow['default_approvers'];
        }
        
        // Si aucun approbateur n'est défini, utiliser le gestionnaire de transactions
        return [$this->transaction_manager];
    }
}