<?php

namespace App\Models;

use App\DTO\Connector\ConnectorDTO;
use App\DTO\Connector\ConnectorStatusDTO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle Connector
 * 
 * Représente un connecteur physique d'un point de charge.
 * Les statuts suivent les spécifications OCPP 1.6.
 * 
 * @property int $id
 * @property int $charging_point_id
 * @property int $connector_id
 * @property string|null $type
 * @property string $status
 * @property float|null $power
 * @property string|null $format
 * @property string|null $tariff_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read ChargingPoint $chargingPoint
 */
class Connector extends Model
{
    use HasFactory;

    // =========================================================================
    // CONSTANTES DE STATUT OCPP 1.6
    // =========================================================================

    /**
     * Connecteur disponible pour une nouvelle session
     */
    public const STATUS_AVAILABLE = 'Available';

    /**
     * Véhicule branché, en attente d'autorisation ou de démarrage
     */
    public const STATUS_PREPARING = 'Preparing';

    /**
     * Session de charge en cours
     */
    public const STATUS_CHARGING = 'Charging';

    /**
     * Charge suspendue par le point de charge (EVSE)
     */
    public const STATUS_SUSPENDED_EVSE = 'SuspendedEVSE';

    /**
     * Charge suspendue par le véhicule électrique (EV)
     */
    public const STATUS_SUSPENDED_EV = 'SuspendedEV';

    /**
     * Session terminée, véhicule toujours branché
     */
    public const STATUS_FINISHING = 'Finishing';

    /**
     * Connecteur réservé
     */
    public const STATUS_RESERVED = 'Reserved';

    /**
     * Connecteur indisponible (maintenance, hors service)
     */
    public const STATUS_UNAVAILABLE = 'Unavailable';

    /**
     * Connecteur en erreur
     */
    public const STATUS_FAULTED = 'Faulted';

    /**
     * Statut inconnu
     */
    public const STATUS_UNKNOWN = 'Unknown';

    /**
     * Liste de tous les statuts valides OCPP
     */
    public const VALID_STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_PREPARING,
        self::STATUS_CHARGING,
        self::STATUS_SUSPENDED_EVSE,
        self::STATUS_SUSPENDED_EV,
        self::STATUS_FINISHING,
        self::STATUS_RESERVED,
        self::STATUS_UNAVAILABLE,
        self::STATUS_FAULTED,
    ];

    /**
     * Statuts indiquant que le connecteur est occupé
     */
    public const BUSY_STATUSES = [
        self::STATUS_PREPARING,
        self::STATUS_CHARGING,
        self::STATUS_SUSPENDED_EVSE,
        self::STATUS_SUSPENDED_EV,
        self::STATUS_FINISHING,
    ];

    // =========================================================================
    // CONSTANTES DE TYPE DE CONNECTEUR
    // =========================================================================

    public const TYPE_CHADEMO = 'CHAdeMO';
    public const TYPE_CCS1 = 'CCS1';
    public const TYPE_CCS2 = 'CCS2';
    public const TYPE_TYPE1 = 'Type1';
    public const TYPE_TYPE2 = 'Type2';
    public const TYPE_TYPE3 = 'Type3';
    public const TYPE_TESLA = 'Tesla';
    public const TYPE_GB_T = 'GB/T';

    public const VALID_TYPES = [
        self::TYPE_CHADEMO,
        self::TYPE_CCS1,
        self::TYPE_CCS2,
        self::TYPE_TYPE1,
        self::TYPE_TYPE2,
        self::TYPE_TYPE3,
        self::TYPE_TESLA,
        self::TYPE_GB_T,
    ];

    // =========================================================================
    // CONFIGURATION DU MODÈLE
    // =========================================================================

    protected $fillable = [
        'charging_point_id',
        'connector_id',
        'type',
        'status',
        'power',
        'format',
        'tariff_id',
    ];

    protected $casts = [
        'power' => 'decimal:2',
        'connector_id' => 'integer',
        'charging_point_id' => 'integer',
    ];

    protected $attributes = [
        'status' => self::STATUS_AVAILABLE,
    ];

    // =========================================================================
    // RELATIONS
    // =========================================================================

    /**
     * Get the charging point that owns the connector.
     */
    public function chargingPoint(): BelongsTo
    {
        return $this->belongsTo(ChargingPoint::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope pour les connecteurs disponibles
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    /**
     * Scope pour les connecteurs en charge
     */
    public function scopeCharging(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CHARGING);
    }

    /**
     * Scope pour les connecteurs occupés (en charge ou préparation)
     */
    public function scopeBusy(Builder $query): Builder
    {
        return $query->whereIn('status', self::BUSY_STATUSES);
    }

    /**
     * Scope pour les connecteurs en erreur
     */
    public function scopeFaulted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAULTED);
    }

    /**
     * Scope pour les connecteurs réservés
     */
    public function scopeReserved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RESERVED);
    }

    /**
     * Scope pour les connecteurs indisponibles
     */
    public function scopeUnavailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_UNAVAILABLE);
    }

    /**
     * Scope pour les connecteurs pouvant démarrer une session
     */
    public function scopeCanStartSession(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_AVAILABLE, self::STATUS_PREPARING]);
    }

    /**
     * Scope pour un type de connecteur spécifique
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour les connecteurs avec une puissance minimale
     */
    public function scopeMinPower(Builder $query, float $minPower): Builder
    {
        return $query->where('power', '>=', $minPower);
    }

    // =========================================================================
    // MÉTHODES DE STATUT
    // =========================================================================

    /**
     * Vérifie si le connecteur est disponible
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    /**
     * Vérifie si le connecteur est en charge
     */
    public function isCharging(): bool
    {
        return $this->status === self::STATUS_CHARGING;
    }

    /**
     * Vérifie si le connecteur est occupé
     */
    public function isBusy(): bool
    {
        return in_array($this->status, self::BUSY_STATUSES);
    }

    /**
     * Vérifie si le connecteur est en préparation
     */
    public function isPreparing(): bool
    {
        return $this->status === self::STATUS_PREPARING;
    }

    /**
     * Vérifie si le connecteur est réservé
     */
    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    /**
     * Vérifie si le connecteur est en erreur
     */
    public function isFaulted(): bool
    {
        return $this->status === self::STATUS_FAULTED;
    }

    /**
     * Vérifie si le connecteur est indisponible
     */
    public function isUnavailable(): bool
    {
        return $this->status === self::STATUS_UNAVAILABLE;
    }

    /**
     * Vérifie si le connecteur peut démarrer une session
     */
    public function canStartSession(): bool
    {
        return in_array($this->status, [self::STATUS_AVAILABLE, self::STATUS_PREPARING]);
    }

    // =========================================================================
    // MÉTHODES DE MISE À JOUR
    // =========================================================================

    /**
     * Met à jour le connecteur depuis un ConnectorStatusDTO
     */
    public function updateFromDTO(ConnectorStatusDTO $dto): bool
    {
        return $this->update([
            'status' => $dto->status,
        ]);
    }

    /**
     * Met à jour le connecteur depuis un ConnectorDTO complet
     */
    public function updateFromConnectorDTO(ConnectorDTO $dto): bool
    {
        $data = [
            'status' => $dto->status,
        ];

        if ($dto->type !== null) {
            $data['type'] = $dto->type;
        }

        if ($dto->power !== null) {
            $data['power'] = $dto->power;
        }

        if ($dto->format !== null) {
            $data['format'] = $dto->format;
        }

        if ($dto->tariffId !== null) {
            $data['tariff_id'] = $dto->tariffId;
        }

        return $this->update($data);
    }

    /**
     * Met à jour le statut du connecteur
     */
    public function updateStatus(string $status): bool
    {
        if (!in_array($status, self::VALID_STATUSES) && $status !== self::STATUS_UNKNOWN) {
            return false;
        }

        return $this->update(['status' => $status]);
    }

    /**
     * Synchronise le connecteur depuis SteVe
     */
    public function syncFromSteve(): bool
    {
        $service = app(\App\Services\ConnectorService::class);
        return $service->updateLocalConnectorStatus($this);
    }

    // =========================================================================
    // MÉTHODES D'AFFICHAGE
    // =========================================================================

    /**
     * Obtient le libellé du statut en français
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_AVAILABLE => 'Disponible',
            self::STATUS_PREPARING => 'En préparation',
            self::STATUS_CHARGING => 'En charge',
            self::STATUS_SUSPENDED_EVSE => 'Suspendu (EVSE)',
            self::STATUS_SUSPENDED_EV => 'Suspendu (EV)',
            self::STATUS_FINISHING => 'Finalisation',
            self::STATUS_RESERVED => 'Réservé',
            self::STATUS_UNAVAILABLE => 'Indisponible',
            self::STATUS_FAULTED => 'En erreur',
            self::STATUS_UNKNOWN => 'Inconnu',
        ];

        return $labels[$this->status] ?? 'Inconnu';
    }

    /**
     * Obtient la couleur CSS associée au statut
     */
    public function getStatusColorAttribute(): string
    {
        $colors = [
            self::STATUS_AVAILABLE => 'green',
            self::STATUS_PREPARING => 'blue',
            self::STATUS_CHARGING => 'blue',
            self::STATUS_SUSPENDED_EVSE => 'yellow',
            self::STATUS_SUSPENDED_EV => 'yellow',
            self::STATUS_FINISHING => 'blue',
            self::STATUS_RESERVED => 'orange',
            self::STATUS_UNAVAILABLE => 'gray',
            self::STATUS_FAULTED => 'red',
            self::STATUS_UNKNOWN => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Obtient le libellé du type de connecteur
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type ?? 'Non spécifié';
    }

    /**
     * Obtient la puissance formatée
     */
    public function getFormattedPowerAttribute(): string
    {
        if ($this->power === null) {
            return 'Non spécifié';
        }

        return number_format($this->power, 1) . ' kW';
    }

    // =========================================================================
    // MÉTHODES DE CONVERSION
    // =========================================================================

    /**
     * Convertit le modèle en ConnectorDTO
     */
    public function toDTO(): ConnectorDTO
    {
        return ConnectorDTO::fromModel($this);
    }

    /**
     * Convertit le modèle en tableau pour API
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'connectorId' => $this->connector_id,
            'type' => $this->type,
            'status' => $this->status,
            'statusLabel' => $this->status_label,
            'statusColor' => $this->status_color,
            'power' => $this->power,
            'formattedPower' => $this->formatted_power,
            'format' => $this->format,
            'tariffId' => $this->tariff_id,
            'isAvailable' => $this->isAvailable(),
            'canStartSession' => $this->canStartSession(),
        ];
    }
}