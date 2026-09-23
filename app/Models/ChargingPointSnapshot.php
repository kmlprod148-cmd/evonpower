<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargingPointSnapshot extends Model
{
    protected $fillable = [
        'reference_date',
        'integrator_id',
        'partner_id',
        'group_id',
        'total_charging_points',
        'active_charging_points',
        'offline_charging_points',
        'maintenance_charging_points',
        'record_type',
        'snapshot_date',
    ];
}