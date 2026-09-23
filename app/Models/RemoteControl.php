<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemoteControl extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'status',
        'station_id',
        'serial_number',
        'notes',
        'user_id',
    ];

    /**
     * The type options for remote controls.
     *
     * @var array
     */
    public static $types = [
        'rfid' => 'RFID',
        'mobile' => 'Mobile',
        'card' => 'Carte',
        'other' => 'Autre',
    ];

    /**
     * The status options for remote controls.
     *
     * @var array
     */
    public static $statuses = [
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'pending' => 'En attente',
    ];

    /**
     * Get the station that owns the remote control.
     */
    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * Get the user that owns the remote control.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the formatted type label.
     *
     * @return string
     */
    public function getTypeLabelAttribute()
    {
        return self::$types[$this->type] ?? $this->type;
    }

    /**
     * Get the formatted status label.
     *
     * @return string
     */
    public function getStatusLabelAttribute()
    {
        return self::$statuses[$this->status] ?? $this->status;
    }
}