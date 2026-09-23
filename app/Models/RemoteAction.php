<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemoteAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'charging_point_id',
        'action',
        'status',
        'response_json',
        'created_by',
    ];

    protected $casts = [
        'response_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec le point de charge
     */
    public function chargingPoint(): BelongsTo
    {
        return $this->belongsTo(ChargingPoint::class);
    }

    /**
     * Relation avec l'utilisateur qui a créé l'action
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur (alias)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
