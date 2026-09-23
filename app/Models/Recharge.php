<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recharge extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'station_id',
        'user_id',
        'kwh',
        'start_time',
        'end_time',
        'status',
        'amount',
        'payment_status'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'kwh' => 'float',
        'amount' => 'float',
    ];
    
    /**
     * Get the station that owns the recharge.
     */
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
    
    /**
     * Get the user that owns the recharge.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}