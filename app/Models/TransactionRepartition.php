<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionRepartition extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'admin_amount',
        'integrator_amount',
        'operator_amount',
        // 'total_amount' - Retiré car la colonne n'existe pas dans la base de données
        // Le total peut être calculé dynamiquement via getTotalAmount()
        'integrator_fee',
        'admin_fee',
        'created_at',
        'updated_at'
    ];

    /**
     * Liste des attributs à ignorer lors de la sauvegarde
     * Empêche Laravel d'essayer d'insérer total_amount qui n'existe pas en base
     */
    protected $guarded = [];

    /**
     * Surcharger setAttribute pour ignorer total_amount
     */
    public function setAttribute($key, $value)
    {
        // Ignorer total_amount car la colonne n'existe pas dans la base de données
        if ($key === 'total_amount') {
            return $this;
        }
        return parent::setAttribute($key, $value);
    }

    protected $casts = [
        'admin_amount' => 'decimal:2',
        'integrator_amount' => 'decimal:2',
        'operator_amount' => 'decimal:2',
        // 'total_amount' => 'decimal:2', - Retiré car la colonne n'existe pas
        'integrator_fee' => 'decimal:2',
        'admin_fee' => 'decimal:2',
    ];

    /**
     * Get the transaction that owns this repartition
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the total distributed
     */
    public function getTotalDistributedAttribute(): float
    {
        return $this->admin_amount + $this->integrator_amount + $this->operator_amount;
    }

    /**
     * Get the repartition summary
     */
    public function getSummaryAttribute(): string
    {
        return sprintf(
            'Admin: %s | Integrator: %s | Operator: %s',
            number_format($this->admin_amount, 2),
            number_format($this->integrator_amount, 2),
            number_format($this->operator_amount, 2)
        );
    }

    /**
     * Check if repartition is balanced
     */
    public function isBalanced(): bool
    {
        // total_amount n'existe plus dans la base de données, utiliser getTotalAmount() qui calcule dynamiquement
        $total = $this->getTotalAmount();
        $distributed = $this->getTotalDistributedAttribute();
        return abs($total - $distributed) < 0.01;
    }

    /**
     * Check if repartition is consistent (total matches sum of parts)
     */
    public function isConsistent(): bool
    {
        $sum = (float) $this->admin_amount + (float) $this->integrator_amount + (float) $this->operator_amount;
        $total = (float) ($this->total_amount ?? $sum);
        return abs($total - $sum) < 0.01;
    }

    /**
     * Get total amount
     */
    public function getTotalAmount(): float
    {
        return (float) ($this->total_amount ?? ($this->admin_amount + $this->integrator_amount + $this->operator_amount));
    }

    /**
     * Crée une répartition à partir d'un calcul
     *
     * @param Transaction $transaction
     * @param array $calculation
     * @return TransactionRepartition
     */
    public static function createFromCalculation(Transaction $transaction, array $calculation): TransactionRepartition
    {
        // Ne pas inclure total_amount car la colonne n'existe pas dans la base de données
        // Le total peut être calculé dynamiquement via getTotalAmount()
        // Filtrer explicitement toutes les clés non autorisées
        $allowedKeys = ['transaction_id', 'admin_amount', 'integrator_amount', 'operator_amount', 'integrator_fee', 'admin_fee'];
        $data = array_intersect_key($calculation, array_flip($allowedKeys));
        
        // S'assurer que transaction_id est présent
        $data['transaction_id'] = $transaction->id;
        
        // S'assurer que les montants sont définis
        $data['admin_amount'] = $data['admin_amount'] ?? $calculation['admin_amount'] ?? 0;
        $data['integrator_amount'] = $data['integrator_amount'] ?? $calculation['integrator_amount'] ?? 0;
        $data['operator_amount'] = $data['operator_amount'] ?? $calculation['operator_amount'] ?? 0;
        
        // Retirer explicitement total_amount s'il est présent
        unset($data['total_amount']);
        
        try {
            return self::create($data);
        } catch (\Illuminate\Database\QueryException $e) {
            // Si l'erreur est due à total_amount, réessayer sans cette colonne
            if (str_contains($e->getMessage(), 'total_amount')) {
                \Log::warning('Tentative de création de répartition avec total_amount (colonne inexistante)', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                    'data_keys' => array_keys($data)
                ]);
                // Réessayer sans total_amount et avec seulement les clés autorisées
                $cleanData = [
                    'transaction_id' => $transaction->id,
                    'admin_amount' => $calculation['admin_amount'] ?? 0,
                    'integrator_amount' => $calculation['integrator_amount'] ?? 0,
                    'operator_amount' => $calculation['operator_amount'] ?? 0,
                ];
                return self::create($cleanData);
            }
            throw $e;
        }
    }
}