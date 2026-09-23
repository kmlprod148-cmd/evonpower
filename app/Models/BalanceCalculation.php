<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Modèle pour les calculs de balance
 * 
 * Enregistre les opérations de calcul de balance
 * pour le suivi et l'audit des performances
 */
class BalanceCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'calculation_type',
        'user_id',
        'role',
        'parameters',
        'results',
        'processed_users',
        'errors_count',
        'errors',
        'execution_time',
        'status',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'parameters' => 'array',
        'results' => 'array',
        'errors' => 'array',
        'execution_time' => 'decimal:3',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Relation avec l'utilisateur
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope pour filtrer par type de calcul
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('calculation_type', $type);
    }

    /**
     * Scope pour filtrer par statut
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour filtrer par rôle
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope pour filtrer par utilisateur
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour les calculs récents
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hours
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope pour les calculs réussis
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope pour les calculs échoués
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope pour les calculs en cours
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope pour les calculs en attente
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Récupère les statistiques des calculs
     * 
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    public static function getStatistics(Carbon $start, Carbon $end): array
    {
        $stats = static::whereBetween('started_at', [$start, $end])
            ->selectRaw('
                COUNT(*) as total_calculations,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful_calculations,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_calculations,
                SUM(CASE WHEN status = "running" THEN 1 ELSE 0 END) as running_calculations,
                SUM(processed_users) as total_processed_users,
                SUM(errors_count) as total_errors,
                AVG(execution_time) as average_execution_time,
                MAX(execution_time) as max_execution_time,
                MIN(execution_time) as min_execution_time
            ')
            ->first();

        return [
            'total_calculations' => (int) ($stats->total_calculations ?? 0),
            'successful_calculations' => (int) ($stats->successful_calculations ?? 0),
            'failed_calculations' => (int) ($stats->failed_calculations ?? 0),
            'running_calculations' => (int) ($stats->running_calculations ?? 0),
            'total_processed_users' => (int) ($stats->total_processed_users ?? 0),
            'total_errors' => (int) ($stats->total_errors ?? 0),
            'success_rate' => $stats->total_calculations > 0 
                ? round(($stats->successful_calculations / $stats->total_calculations) * 100, 2) 
                : 0,
            'average_execution_time' => (float) ($stats->average_execution_time ?? 0),
            'max_execution_time' => (float) ($stats->max_execution_time ?? 0),
            'min_execution_time' => (float) ($stats->min_execution_time ?? 0)
        ];
    }

    /**
     * Récupère les calculs récents d'un utilisateur
     * 
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecentForUser(int $userId, int $limit = 10)
    {
        return static::forUser($userId)
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Récupère les calculs en cours
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRunningCalculations()
    {
        return static::running()
            ->orderBy('started_at', 'asc')
            ->get();
    }

    /**
     * Récupère les calculs échoués récents
     * 
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecentFailures(int $limit = 10)
    {
        return static::failed()
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Crée un nouveau calcul
     * 
     * @param string $type
     * @param int|null $userId
     * @param string|null $role
     * @param array $parameters
     * @return static
     */
    public static function createCalculation(
        string $type,
        ?int $userId = null,
        ?string $role = null,
        array $parameters = []
    ): static {
        return static::create([
            'calculation_type' => $type,
            'user_id' => $userId,
            'role' => $role,
            'parameters' => $parameters,
            'status' => 'pending',
            'started_at' => now()
        ]);
    }

    /**
     * Marque le calcul comme démarré
     * 
     * @return void
     */
    public function markAsRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now()
        ]);
    }

    /**
     * Marque le calcul comme terminé avec succès
     * 
     * @param array $results
     * @param int $processedUsers
     * @param float $executionTime
     * @return void
     */
    public function markAsCompleted(array $results, int $processedUsers, float $executionTime): void
    {
        $this->update([
            'status' => 'completed',
            'results' => $results,
            'processed_users' => $processedUsers,
            'execution_time' => $executionTime,
            'completed_at' => now()
        ]);
    }

    /**
     * Marque le calcul comme échoué
     * 
     * @param array $errors
     * @param float $executionTime
     * @return void
     */
    public function markAsFailed(array $errors, float $executionTime): void
    {
        $this->update([
            'status' => 'failed',
            'errors' => $errors,
            'errors_count' => count($errors),
            'execution_time' => $executionTime,
            'completed_at' => now()
        ]);
    }

    /**
     * Accessor pour le statut formaté
     * 
     * @return string
     */
    public function getFormattedStatusAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'running' => 'En cours',
            'completed' => 'Terminé',
            'failed' => 'Échoué',
            default => ucfirst($this->status)
        };
    }

    /**
     * Accessor pour le type de calcul formaté
     * 
     * @return string
     */
    public function getFormattedCalculationTypeAttribute(): string
    {
        return match($this->calculation_type) {
            'user' => 'Utilisateur',
            'role' => 'Rôle',
            'global' => 'Global',
            'manual' => 'Manuel',
            default => ucfirst($this->calculation_type)
        };
    }

    /**
     * Accessor pour le temps d'exécution formaté
     * 
     * @return string
     */
    public function getFormattedExecutionTimeAttribute(): string
    {
        if (!$this->execution_time) {
            return 'N/A';
        }

        if ($this->execution_time < 1) {
            return round($this->execution_time * 1000, 2) . ' ms';
        }

        return round($this->execution_time, 2) . ' s';
    }

    /**
     * Accessor pour la durée totale formatée
     * 
     * @return string
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->started_at || !$this->completed_at) {
            return 'N/A';
        }

        $duration = $this->completed_at->diffInSeconds($this->started_at);
        
        if ($duration < 60) {
            return $duration . ' s';
        }

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;
        
        return $minutes . 'm ' . $seconds . 's';
    }

    /**
     * Accessor pour le taux de succès formaté
     * 
     * @return string
     */
    public function getFormattedSuccessRateAttribute(): string
    {
        if ($this->processed_users === 0) {
            return 'N/A';
        }

        $successRate = (($this->processed_users - $this->errors_count) / $this->processed_users) * 100;
        return round($successRate, 2) . '%';
    }

    /**
     * Accessor pour les paramètres formatés
     * 
     * @return string
     */
    public function getFormattedParametersAttribute(): string
    {
        if (!$this->parameters) {
            return 'Aucun';
        }

        $formatted = [];
        foreach ($this->parameters as $key => $value) {
            $formatted[] = $key . ': ' . (is_array($value) ? json_encode($value) : $value);
        }

        return implode(', ', $formatted);
    }
}
