<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveChargingPointsHistory extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'reference_date',
        'integrator_id',
        'partner_id',
        'group_id',
        'total_charging_points',
        'active_charging_points',
        'offline_charging_points',
        'maintenance_charging_points',
        'record_type', // 'daily', 'monthly', etc.
        'snapshot_date', // When the snapshot was taken
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array
     */
    protected $casts = [
        'reference_date' => 'date',
        'snapshot_date' => 'datetime',
        'total_charging_points' => 'integer',
        'active_charging_points' => 'integer',
        'offline_charging_points' => 'integer',
        'maintenance_charging_points' => 'integer',
    ];

    /**
     * Relation avec l'intégrateur
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function integrator()
    {
        return $this->belongsTo(Integrator::class);
    }

    /**
     * Relation avec le partenaire
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Relation avec le groupe
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Scope pour les enregistrements quotidiens
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDaily($query)
    {
        return $query->where('record_type', 'daily');
    }

    /**
     * Scope pour les enregistrements mensuels
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMonthly($query)
    {
        return $query->where('record_type', 'monthly');
    }

    /**
     * Scope pour filtrer par intégrateur
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $integratorId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForIntegrator($query, $integratorId)
    {
        return $query->where('integrator_id', $integratorId);
    }

    /**
     * Scope pour filtrer par partenaire
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $partnerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPartner($query, $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }

    /**
     * Scope pour filtrer par groupe
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $groupId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    /**
     * Scope pour filtrer par période
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('reference_date', [$startDate, $endDate]);
    }
}