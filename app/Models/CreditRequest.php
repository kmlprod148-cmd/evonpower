<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditRequest extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'owner_id',
        'charging_point_id',
        'reservation_id',
        'amount',
        'status',
        'request_type',
        'reason',
        'owner_response',
        'processed_at',
        'payment_transaction_id',
        'payment_method',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the client (user who requested credit)
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Get the owner (charging point owner who will approve/reject)
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the charging point
     */
    public function chargingPoint(): BelongsTo
    {
        return $this->belongsTo(ChargingPoint::class);
    }

    /**
     * Get the reservation
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for requests by client
     */
    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope for requests to owner
     */
    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if request is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if request is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Approve the credit request
     */
    public function approve(string $response = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status = 'approved';
        $this->owner_response = $response;
        $this->processed_at = now();
        
        $saved = $this->save();

        if ($saved) {
            // Add credit to client account
            $this->client->addCredit($this->amount, "Demande de crédit approuvée #{$this->id}");
            
            // Invalider le cache du solde client après l'ajout de crédit
            app(\App\Services\ClientBalanceService::class)->invalidate($this->client);
            
            // Send notification to client
            $this->client->notify(new \App\Notifications\CreditRequestApproved($this));
        }

        return $saved;
    }

    /**
     * Reject the credit request
     */
    public function reject(string $response = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status = 'rejected';
        $this->owner_response = $response;
        $this->processed_at = now();
        
        $saved = $this->save();

        if ($saved) {
            // Send notification to client
            $this->client->notify(new \App\Notifications\CreditRequestRejected($this));
        }

        return $saved;
    }

    /**
     * Cancel the credit request (by client)
     */
    public function cancel(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status = 'cancelled';
        $this->processed_at = now();
        
        return $this->save();
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => __('En attente'),
            'approved' => __('Approuvée'),
            'rejected' => __('Rejetée'),
            'cancelled' => __('Annulée'),
            default => __('Inconnu'),
        };
    }
}
