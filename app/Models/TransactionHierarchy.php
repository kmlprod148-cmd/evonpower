<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionHierarchy extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_transaction_id',
        'transaction_type',
        'payer_id',
        'payee_id',
        'amount',
        'fees_amount',
        'net_amount',
        'status',
        'processed_at',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fees_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relation avec la transaction originale
     */
    public function originalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'original_transaction_id');
    }

    /**
     * Relation avec l'utilisateur qui paie
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    /**
     * Relation avec l'utilisateur qui reçoit
     */
    public function payee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payee_id');
    }

    /**
     * Scope pour les transactions Admin-Intégrateur
     */
    public function scopeAdminIntegrator($query)
    {
        return $query->where('transaction_type', 'admin_integrator');
    }

    /**
     * Scope pour les transactions Intégrateur-Opérateur
     */
    public function scopeIntegratorOperator($query)
    {
        return $query->where('transaction_type', 'integrator_operator');
    }

    /**
     * Scope pour les transactions par statut
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Marquer comme terminé
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Marquer comme échoué
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Obtenir le label du type de transaction
     */
    public function getTransactionTypeLabel(): string
    {
        return match($this->transaction_type) {
            'admin_integrator' => 'Admin ↔ Intégrateur',
            'integrator_operator' => 'Intégrateur ↔ Opérateur',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir le label du statut
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'completed' => 'Terminé',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            default => 'Inconnu',
        };
    }

    /**
     * Calculer le montant net
     */
    public function calculateNetAmount(): float
    {
        $this->net_amount = $this->amount - $this->fees_amount;
        return $this->net_amount;
    }

    /**
     * Vérifier si la transaction est terminée
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Vérifier si la transaction est en attente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Obtenir les détails de la transaction pour affichage
     */
    public function getTransactionDetails(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->getTransactionTypeLabel(),
            'payer' => $this->payer->name ?? 'Inconnu',
            'payee' => $this->payee->name ?? 'Inconnu',
            'amount' => $this->amount,
            'fees' => $this->fees_amount,
            'net_amount' => $this->net_amount,
            'status' => $this->getStatusLabel(),
            'processed_at' => $this->processed_at?->format('d/m/Y H:i'),
            'description' => $this->description,
        ];
    }
}
