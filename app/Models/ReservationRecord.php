<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_role',
        'charging_point_id',
        'original_transaction_id',
        'reservation_id',
        'session_id',
        'record_type',
        'amount',
        'start_timestamp',
        'stop_timestamp',
        'energy_delivered',
        'duration',
        'status',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'energy_delivered' => 'decimal:4',
        'start_timestamp' => 'datetime',
        'stop_timestamp' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec la borne de recharge
     */
    public function chargingPoint(): BelongsTo
    {
        return $this->belongsTo(ChargingPoint::class);
    }

    /**
     * Relation avec la transaction originale
     */
    public function originalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'original_transaction_id');
    }

    /**
     * Relation avec la réservation
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Scope pour filtrer par rôle utilisateur
     */
    public function scopeByUserRole($query, $role)
    {
        return $query->where('user_role', $role);
    }

    /**
     * Scope pour filtrer par type d'enregistrement
     */
    public function scopeByRecordType($query, $type)
    {
        return $query->where('record_type', $type);
    }

    /**
     * Scope pour les enregistrements visibles à un utilisateur
     */
    public function scopeVisibleToUser($query, $user)
    {
        if ($user->hasRole('admin')) {
            return $query; // Admin voit tout
        }

        if ($user->hasRole('integrator')) {
            // Integrator voit ses propres enregistrements et ceux de ses opérateurs
            return $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere(function($subQ) use ($user) {
                      $subQ->where('user_role', 'operator')
                           ->whereHas('user', function($userQ) use ($user) {
                               $userQ->where('creator_id', $user->id);
                           });
                  });
            });
        }

        if ($user->hasRole('operator')) {
            // Operator voit seulement ses propres enregistrements
            return $query->where('user_id', $user->id);
        }

        return $query->whereNull('id'); // Aucun accès par défaut
    }

    /**
     * Obtenir le libellé du type d'enregistrement
     */
    public function getRecordTypeLabel(): string
    {
        $types = [
            'admin_oversight' => 'Supervision Admin',
            'integrator_management' => 'Gestion Intégrateur',
            'operator_activity' => 'Activité Opérateur',
            'charging_session' => 'Session de Recharge',
            'reservation' => 'Réservation',
        ];

        return $types[$this->record_type] ?? $this->record_type;
    }

    /**
     * Obtenir le libellé du rôle utilisateur
     */
    public function getUserRoleLabel(): string
    {
        $roles = [
            'admin' => 'Administrateur',
            'integrator' => 'Intégrateur',
            'operator' => 'Opérateur',
            'partner' => 'Partenaire',
        ];

        return $roles[$this->user_role] ?? $this->user_role;
    }

    /**
     * Obtenir le niveau hiérarchique
     */
    public function getHierarchyLevel(): int
    {
        return $this->metadata['hierarchy_level'] ?? 0;
    }

    /**
     * Vérifier si l'enregistrement est terminé
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Obtenir la durée formatée
     */
    public function getFormattedDuration(): string
    {
        if (!$this->duration) {
            return 'N/A';
        }

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm %02ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %02ds', $minutes, $seconds);
        } else {
            return sprintf('%ds', $seconds);
        }
    }

    /**
     * Obtenir l'énergie formatée
     */
    public function getFormattedEnergyDelivered(): string
    {
        if (!$this->energy_delivered) {
            return 'N/A';
        }

        return number_format($this->energy_delivered, 2) . ' kWh';
    }

    /**
     * Obtenir le montant formaté
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2) . ' €';
    }
}
