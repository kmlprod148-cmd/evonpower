<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Modèle pour les logs de démarrage automatique OCPP
 * 
 * Permet d'accéder et d'analyser l'historique des tentatives de démarrage
 * automatique, avec des relations, scopes et accesseurs pratiques.
 * 
 * @property int $id
 * @property int|null $reservation_id
 * @property int|null $charging_point_id
 * @property int|null $user_id
 * @property int|null $connector_id
 * @property string $status
 * @property string|null $code
 * @property string|null $message
 * @property int $attempt_number
 * @property int $max_attempts
 * @property bool $is_final_attempt
 * @property bool $auto_started
 * @property string|null $ocpp_tag
 * @property int|null $connector_number
 * @property string|null $charge_box_id
 * @property string|null $transaction_id
 * @property Carbon|null $start_time
 * @property Carbon|null $processed_at
 * @property int|null $processing_duration_ms
 * @property array|null $validation_checks
 * @property array|null $eligibility_data
 * @property array|null $ocpp_response
 * @property int|null $ocpp_status_code
 * @property string|null $ocpp_status
 * @property string|null $error_message
 * @property string|null $error_trace
 * @property string|null $error_type
 * @property array|null $metadata
 * @property string $triggered_by
 * @property string $processing_mode
 * @property string|null $server_hostname
 * @property string|null $job_id
 * @property bool $user_notified
 * @property bool $admin_alerted
 * @property bool $requires_manual_action
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read Reservation|null $reservation
 * @property-read ChargingPoint|null $chargingPoint
 * @property-read User|null $user
 * @property-read Connector|null $connector
 */
class AutoRemoteStartLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'charging_point_id',
        'user_id',
        'connector_id',
        'status',
        'code',
        'message',
        'attempt_number',
        'max_attempts',
        'is_final_attempt',
        'auto_started',
        'ocpp_tag',
        'connector_number',
        'charge_box_id',
        'transaction_id',
        'start_time',
        'processed_at',
        'processing_duration_ms',
        'validation_checks',
        'eligibility_data',
        'ocpp_response',
        'ocpp_status_code',
        'ocpp_status',
        'error_message',
        'error_trace',
        'error_type',
        'metadata',
        'triggered_by',
        'processing_mode',
        'server_hostname',
        'job_id',
        'user_notified',
        'admin_alerted',
        'requires_manual_action',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'processed_at' => 'datetime',
        'processing_duration_ms' => 'integer',
        'is_final_attempt' => 'boolean',
        'auto_started' => 'boolean',
        'user_notified' => 'boolean',
        'admin_alerted' => 'boolean',
        'requires_manual_action' => 'boolean',
        'validation_checks' => 'array',
        'eligibility_data' => 'array',
        'ocpp_response' => 'array',
        'metadata' => 'array',
    ];

    protected $dates = [
        'start_time',
        'processed_at',
        'created_at',
        'updated_at',
    ];

    // ====================================================================
    // RELATIONS
    // ====================================================================

    /**
     * Relation avec la réservation
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Relation avec le point de charge
     */
    public function chargingPoint(): BelongsTo
    {
        return $this->belongsTo(ChargingPoint::class);
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le connecteur
     */
    public function connector(): BelongsTo
    {
        return $this->belongsTo(Connector::class);
    }

    // ====================================================================
    // SCOPES
    // ====================================================================

    /**
     * Scope pour les logs de succès
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope pour les logs d'échec
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope pour les logs d'erreur
     */
    public function scopeError($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope pour les logs ignorés
     */
    public function scopeSkipped($query)
    {
        return $query->where('status', 'skipped');
    }

    /**
     * Scope pour les logs automatiques
     */
    public function scopeAutoStarted($query)
    {
        return $query->where('auto_started', true);
    }

    /**
     * Scope pour les logs nécessitant une action manuelle
     */
    public function scopeRequiresAction($query)
    {
        return $query->where('requires_manual_action', true);
    }

    /**
     * Scope pour une période donnée
     */
    public function scopePeriod($query, $from, $to = null)
    {
        $query->where('processed_at', '>=', $from);
        
        if ($to) {
            $query->where('processed_at', '<=', $to);
        }
        
        return $query;
    }

    /**
     * Scope pour les dernières 24 heures
     */
    public function scopeLastDay($query)
    {
        return $query->where('processed_at', '>=', now()->subDay());
    }

    /**
     * Scope pour les 7 derniers jours
     */
    public function scopeLastWeek($query)
    {
        return $query->where('processed_at', '>=', now()->subWeek());
    }

    /**
     * Scope pour le mois en cours
     */
    public function scopeThisMonth($query)
    {
        return $query->whereYear('processed_at', now()->year)
            ->whereMonth('processed_at', now()->month);
    }

    /**
     * Scope pour un utilisateur spécifique
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour un point de charge spécifique
     */
    public function scopeForChargingPoint($query, $chargingPointId)
    {
        return $query->where('charging_point_id', $chargingPointId);
    }

    /**
     * Scope pour une réservation spécifique
     */
    public function scopeForReservation($query, $reservationId)
    {
        return $query->where('reservation_id', $reservationId);
    }

    // ====================================================================
    // ACCESSEURS & MUTATEURS
    // ====================================================================

    /**
     * Vérifier si c'est un succès
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Vérifier si c'est un échec
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Vérifier si c'est une erreur
     */
    public function isError(): bool
    {
        return $this->status === 'error';
    }

    /**
     * Obtenir la durée de traitement en secondes
     */
    public function getDurationSeconds(): float
    {
        return $this->processing_duration_ms ? $this->processing_duration_ms / 1000 : 0;
    }

    /**
     * Obtenir le message d'erreur formaté
     */
    public function getFormattedError(): ?string
    {
        if ($this->error_message) {
            return $this->error_message;
        }
        
        if ($this->message) {
            return $this->message;
        }
        
        return null;
    }

    /**
     * Obtenir un résumé du log
     */
    public function getSummary(): string
    {
        $status = match($this->status) {
            'success' => '✅',
            'failed' => '❌',
            'error' => '⚠️',
            'skipped' => '⏭️',
            default => '❓'
        };

        return "{$status} Réservation #{$this->reservation_id} - {$this->message}";
    }

    /**
     * Obtenir les informations de timing
     */
    public function getTimingInfo(): array
    {
        return [
            'start_time' => $this->start_time?->toISOString(),
            'processed_at' => $this->processed_at->toISOString(),
            'delay_minutes' => $this->start_time ? $this->processed_at->diffInMinutes($this->start_time) : null,
            'processing_duration_ms' => $this->processing_duration_ms,
            'processing_duration_s' => $this->getDurationSeconds(),
        ];
    }

    // ====================================================================
    // MÉTHODES STATIQUES
    // ====================================================================

    /**
     * Obtenir les statistiques globales
     */
    public static function getGlobalStats(array $filters = []): array
    {
        $query = static::query();

        if (isset($filters['date_from'])) {
            $query->where('processed_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('processed_at', '<=', $filters['date_to']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['charging_point_id'])) {
            $query->where('charging_point_id', $filters['charging_point_id']);
        }

        $total = $query->count();
        $success = (clone $query)->where('status', 'success')->count();
        $failed = (clone $query)->where('status', 'failed')->count();
        $errors = (clone $query)->where('status', 'error')->count();
        $skipped = (clone $query)->where('status', 'skipped')->count();

        $successRate = $total > 0 ? round(($success / $total) * 100, 2) : 0;
        $avgDuration = (clone $query)->avg('processing_duration_ms');

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
            'skipped' => $skipped,
            'success_rate' => $successRate,
            'avg_duration_ms' => round($avgDuration ?? 0, 2),
            'filters' => $filters
        ];
    }

    /**
     * Nettoyage des anciens logs
     */
    public static function cleanup(int $days = 30): int
    {
        $cutoffDate = now()->subDays($days);
        
        return static::where('processed_at', '<', $cutoffDate)
            ->where('requires_manual_action', false)
            ->delete();
    }

    /**
     * Obtenir les logs récents
     */
    public static function recent(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['reservation', 'chargingPoint', 'user'])
            ->orderBy('processed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtenir les logs nécessitant une action
     */
    public static function requiresManualAction(): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['reservation', 'chargingPoint', 'user'])
            ->where('requires_manual_action', true)
            ->orderBy('processed_at', 'desc')
            ->get();
    }

    /**
     * Créer un log de succès
     */
    public static function logSuccess(Reservation $reservation, array $data): self
    {
        return static::create([
            'reservation_id' => $reservation->id,
            'charging_point_id' => $reservation->charging_point_id,
            'user_id' => $reservation->user_id,
            'connector_id' => $reservation->connector_id,
            'status' => 'success',
            'code' => 'SUCCESS',
            'message' => $data['message'] ?? 'Transaction démarrée avec succès',
            'attempt_number' => $data['attempt_number'] ?? 1,
            'max_attempts' => $data['max_attempts'] ?? 3,
            'is_final_attempt' => $data['is_final_attempt'] ?? false,
            'auto_started' => $data['auto_started'] ?? true,
            'transaction_id' => $data['transaction_id'] ?? null,
            'ocpp_response' => $data['ocpp_response'] ?? null,
            'ocpp_status' => 'ACCEPTED',
            'processing_duration_ms' => $data['duration_ms'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    /**
     * Créer un log d'échec
     */
    public static function logFailure(Reservation $reservation, array $data): self
    {
        return static::create([
            'reservation_id' => $reservation->id,
            'charging_point_id' => $reservation->charging_point_id,
            'user_id' => $reservation->user_id,
            'connector_id' => $reservation->connector_id,
            'status' => 'failed',
            'code' => $data['code'] ?? 'FAILED',
            'message' => $data['message'] ?? 'Échec du démarrage',
            'error_message' => $data['error'] ?? null,
            'error_type' => $data['error_type'] ?? 'unknown',
            'attempt_number' => $data['attempt_number'] ?? 1,
            'max_attempts' => $data['max_attempts'] ?? 3,
            'is_final_attempt' => $data['is_final_attempt'] ?? false,
            'auto_started' => $data['auto_started'] ?? true,
            'processing_duration_ms' => $data['duration_ms'] ?? null,
            'requires_manual_action' => $data['is_final_attempt'] ?? false,
            'metadata' => $data['metadata'] ?? [],
        ]);
    }
}

