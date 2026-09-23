<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcppTagHistory extends Model
{
    protected $table = 'ocpp_tag_history';

    protected $fillable = [
        'ocpp_tag',
        'ocpp_tag_id',
        'client_user_id',
        'action',
        'status_before',
        'status_after',
        'notes',
        'performed_by',
        'action_date',
    ];

    protected $casts = [
        'action_date' => 'datetime',
    ];

    /**
     * Relation avec le tag OCPP
     */
    public function ocppTag(): BelongsTo
    {
        return $this->belongsTo(OcppTag::class);
    }

    /**
     * Relation avec le client
     */
    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(ClientUser::class);
    }

    /**
     * Relation avec l'utilisateur qui a performed l'action
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Scope pour les associations
     */
    public function scopeAssociated($query)
    {
        return $query->where('action', 'associated');
    }

    /**
     * Scope pour les dissociations
     */
    public function scopeDissociated($query)
    {
        return $query->where('action', 'dissociated');
    }

    /**
     * Scope pour les blocages
     */
    public function scopeBlocked($query)
    {
        return $query->where('action', 'blocked');
    }

    /**
     * Scope pour les débloquer
     */
    public function scopeUnblocked($query)
    {
        return $query->where('action', 'unblocked');
    }

    /**
     * Record an action
     */
    public static function record(
        OcppTag $ocppTag,
        ClientUser $clientUser,
        string $action,
        ?string $statusBefore = null,
        ?string $statusAfter = null,
        ?string $notes = null,
        ?int $performedBy = null
    ): self {
        return self::create([
            'ocpp_tag' => $ocppTag->ocpp_tag,
            'ocpp_tag_id' => $ocppTag->id,
            'client_user_id' => $clientUser->id,
            'action' => $action,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'notes' => $notes,
            'performed_by' => $performedBy,
            'action_date' => now(),
        ]);
    }
}
