<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargePointCommand extends Model
{
    use HasFactory;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'charge_box_id',
        'command',
        'params',
        'status',
        'response',
        'triggered_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'params' => 'json',
        'response' => 'json',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * @return BelongsTo
     */
    public function chargingPoint(): BelongsTo
    {
        return $this->belongsTo(ChargingPoint::class, 'charge_box_id', 'steve_charging_point_id');
    }
}
