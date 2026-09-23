<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class WireTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_reference',
        'sender_id',
        'recipient_id',
        'amount',
        'currency',
        'transfer_type',
        'status',
        'description',
        'notes',
        'processed_by',
        'processed_at',
        'completed_at',
        'rejected_at',
        'rejection_reason',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'metadata' => 'json',
    ];

    // Statuts possibles
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    // Types de virement
    const TYPE_ADMIN_TO_INTEGRATOR = 'admin_to_integrator';
    const TYPE_INTEGRATOR_TO_OPERATOR = 'integrator_to_operator';
    const TYPE_OPERATOR_TO_CLIENT = 'operator_to_client';
    const TYPE_ADMIN_TO_OPERATOR = 'admin_to_operator';
    const TYPE_INTEGRATOR_TO_CLIENT = 'integrator_to_client';
    const TYPE_MANUAL = 'manual';

    /**
     * Générer une référence de virement unique
     */
    public static function generateTransferReference(): string
    {
        return 'WIRE-' . date('Ymd') . '-' . strtoupper(uniqid());
    }

    /**
     * Boot method pour générer automatiquement la référence
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transfer) {
            if (empty($transfer->transfer_reference)) {
                $transfer->transfer_reference = self::generateTransferReference();
            }
            if (empty($transfer->currency)) {
                $transfer->currency = 'EUR';
            }
            if (empty($transfer->status)) {
                $transfer->status = self::STATUS_PENDING;
            }
        });
    }

    /**
     * Relation avec l'expéditeur
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Relation avec le destinataire
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Relation avec l'utilisateur qui a traité le virement
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Relation avec les transactions liées
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'wire_transfer_id');
    }

    /**
     * Vérifier si le virement peut être traité
     */
    public function canBeProcessed(): bool
    {
        return $this->status === self::STATUS_PENDING && 
               $this->sender && 
               $this->sender->hasSufficientBalance($this->amount);
    }

    /**
     * Traiter le virement
     */
    public function process(User $processor = null): array
    {
        if (!$this->canBeProcessed()) {
            return [
                'success' => false,
                'message' => 'Le virement ne peut pas être traité'
            ];
        }

        try {
            DB::beginTransaction();

            // Mettre à jour le statut
            $this->status = self::STATUS_PROCESSING;
            $this->processed_by = $processor ? $processor->id : null;
            $this->processed_at = now();
            $this->save();

            // Débiter l'expéditeur
            $sender = $this->sender;
            $recipient = $this->recipient;

            if (!$sender->subtractBalance($this->amount)) {
                throw new \Exception('Solde insuffisant pour l\'expéditeur');
            }

            // Créditer le destinataire
            $recipient->addBalance($this->amount);

            // Créer les transactions de débit et crédit
            $this->createTransferTransactions();

            // Finaliser le virement
            $this->status = self::STATUS_COMPLETED;
            $this->completed_at = now();
            $this->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Virement traité avec succès',
                'sender_balance' => $sender->balance,
                'recipient_balance' => $recipient->balance,
                'amount_transferred' => $this->amount
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->status = self::STATUS_REJECTED;
            $this->rejection_reason = $e->getMessage();
            $this->rejected_at = now();
            $this->save();

            return [
                'success' => false,
                'message' => 'Erreur lors du traitement: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Créer les transactions de débit et crédit
     */
    private function createTransferTransactions(): void
    {
        // Transaction de débit pour l'expéditeur
        Transaction::create([
            'user_id' => $this->sender_id,
            'amount' => -$this->amount, // Montant négatif pour débit
            'currency' => $this->currency,
            'transaction_type' => 'wire_transfer_debit',
            'transaction_category' => 'transfer',
            'status' => 'completed',
            'payment_status' => 'completed',
            'description' => "Virement sortant vers {$this->recipient->name}",
            'wire_transfer_id' => $this->id,
            'business_profile_id' => $this->sender->business_profile_id,
        ]);

        // Transaction de crédit pour le destinataire
        Transaction::create([
            'user_id' => $this->recipient_id,
            'amount' => $this->amount, // Montant positif pour crédit
            'currency' => $this->currency,
            'transaction_type' => 'wire_transfer_credit',
            'transaction_category' => 'transfer',
            'status' => 'completed',
            'payment_status' => 'completed',
            'description' => "Virement entrant de {$this->sender->name}",
            'wire_transfer_id' => $this->id,
            'business_profile_id' => $this->recipient->business_profile_id,
        ]);
    }

    /**
     * Rejeter le virement
     */
    public function reject(string $reason, User $processor = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_REJECTED;
        $this->rejection_reason = $reason;
        $this->rejected_at = now();
        $this->processed_by = $processor ? $processor->id : null;
        
        return $this->save();
    }

    /**
     * Annuler le virement
     */
    public function cancel(User $processor = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_CANCELLED;
        $this->processed_by = $processor ? $processor->id : null;
        
        return $this->save();
    }

    /**
     * Obtenir le montant formaté
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Obtenir le statut formaté
     */
    public function getFormattedStatus(): string
    {
        $statuses = [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En cours',
            self::STATUS_COMPLETED => 'Terminé',
            self::STATUS_REJECTED => 'Rejeté',
            self::STATUS_CANCELLED => 'Annulé',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Obtenir le type formaté
     */
    public function getFormattedType(): string
    {
        $types = [
            self::TYPE_ADMIN_TO_INTEGRATOR => 'Admin → Intégrateur',
            self::TYPE_INTEGRATOR_TO_OPERATOR => 'Intégrateur → Opérateur',
            self::TYPE_OPERATOR_TO_CLIENT => 'Opérateur → Client',
            self::TYPE_ADMIN_TO_OPERATOR => 'Admin → Opérateur',
            self::TYPE_INTEGRATOR_TO_CLIENT => 'Intégrateur → Client',
            self::TYPE_MANUAL => 'Manuel',
        ];

        return $types[$this->transfer_type] ?? $this->transfer_type;
    }

    /**
     * Scope pour les virements en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope pour les virements terminés
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope pour les virements d'un utilisateur (en tant qu'expéditeur ou destinataire)
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
              ->orWhere('recipient_id', $userId);
        });
    }

    /**
     * Scope pour les virements par type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('transfer_type', $type);
    }
}
