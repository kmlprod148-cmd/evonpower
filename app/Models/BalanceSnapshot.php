<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Modèle pour les snapshots de balance
 * 
 * Enregistre des instantanés des balances à des moments précis
 * pour l'audit et la récupération de données historiques
 */
class BalanceSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_balance',
        'total_earnings',
        'total_commissions',
        'pending_amount',
        'paid_amount',
        'transaction_count',
        'last_transaction_date',
        'hierarchy_data',
        'commission_breakdown',
        'performance_metrics',
        'snapshot_date'
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'total_commissions' => 'decimal:2',
        'pending_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'hierarchy_data' => 'array',
        'commission_breakdown' => 'array',
        'performance_metrics' => 'array',
        'last_transaction_date' => 'datetime',
        'snapshot_date' => 'datetime'
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
     * Scope pour filtrer par période
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Carbon $start
     * @param Carbon $end
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPeriod($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('snapshot_date', [$start, $end]);
    }

    /**
     * Scope pour le snapshot le plus récent
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('snapshot_date', 'desc');
    }

    /**
     * Scope pour les snapshots d'un jour spécifique
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Carbon $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnDate($query, Carbon $date)
    {
        return $query->whereDate('snapshot_date', $date);
    }

    /**
     * Scope pour les snapshots d'une semaine
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Carbon $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInWeek($query, Carbon $date)
    {
        $startOfWeek = $date->copy()->startOfWeek();
        $endOfWeek = $date->copy()->endOfWeek();
        
        return $query->whereBetween('snapshot_date', [$startOfWeek, $endOfWeek]);
    }

    /**
     * Scope pour les snapshots d'un mois
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Carbon $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInMonth($query, Carbon $date)
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        
        return $query->whereBetween('snapshot_date', [$startOfMonth, $endOfMonth]);
    }

    /**
     * Récupère le snapshot le plus récent d'un utilisateur
     * 
     * @param int $userId
     * @return static|null
     */
    public static function getLatestForUser(int $userId): ?static
    {
        return static::forUser($userId)
            ->latest()
            ->first();
    }

    /**
     * Récupère le snapshot d'un utilisateur à une date donnée
     * 
     * @param int $userId
     * @param Carbon $date
     * @return static|null
     */
    public static function getForUserOnDate(int $userId, Carbon $date): ?static
    {
        return static::forUser($userId)
            ->onDate($date)
            ->latest()
            ->first();
    }

    /**
     * Récupère l'historique des snapshots d'un utilisateur
     * 
     * @param int $userId
     * @param Carbon $start
     * @param Carbon $end
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserHistory(int $userId, Carbon $start, Carbon $end)
    {
        return static::forUser($userId)
            ->byPeriod($start, $end)
            ->orderBy('snapshot_date', 'desc')
            ->get();
    }

    /**
     * Récupère les statistiques d'évolution d'un utilisateur
     * 
     * @param int $userId
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    public static function getUserEvolution(int $userId, Carbon $start, Carbon $end): array
    {
        $snapshots = static::getUserHistory($userId, $start, $end);
        
        if ($snapshots->isEmpty()) {
            return [
                'balance_evolution' => 0,
                'earnings_evolution' => 0,
                'commissions_evolution' => 0,
                'transaction_count_evolution' => 0,
                'snapshots_count' => 0
            ];
        }

        $first = $snapshots->last();
        $last = $snapshots->first();

        return [
            'balance_evolution' => (float) $last->current_balance - (float) $first->current_balance,
            'earnings_evolution' => (float) $last->total_earnings - (float) $first->total_earnings,
            'commissions_evolution' => (float) $last->total_commissions - (float) $first->total_commissions,
            'transaction_count_evolution' => (int) $last->transaction_count - (int) $first->transaction_count,
            'snapshots_count' => $snapshots->count()
        ];
    }

    /**
     * Récupère les statistiques globales pour une période
     * 
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    public static function getGlobalStatistics(Carbon $start, Carbon $end): array
    {
        $snapshots = static::byPeriod($start, $end)
            ->selectRaw('
                COUNT(*) as total_snapshots,
                COUNT(DISTINCT user_id) as unique_users,
                SUM(current_balance) as total_balance,
                SUM(total_earnings) as total_earnings,
                SUM(total_commissions) as total_commissions,
                SUM(transaction_count) as total_transactions,
                AVG(current_balance) as average_balance,
                AVG(total_earnings) as average_earnings
            ')
            ->first();

        return [
            'total_snapshots' => (int) ($snapshots->total_snapshots ?? 0),
            'unique_users' => (int) ($snapshots->unique_users ?? 0),
            'total_balance' => (float) ($snapshots->total_balance ?? 0),
            'total_earnings' => (float) ($snapshots->total_earnings ?? 0),
            'total_commissions' => (float) ($snapshots->total_commissions ?? 0),
            'total_transactions' => (int) ($snapshots->total_transactions ?? 0),
            'average_balance' => (float) ($snapshots->average_balance ?? 0),
            'average_earnings' => (float) ($snapshots->average_earnings ?? 0)
        ];
    }

    /**
     * Crée un snapshot pour un utilisateur
     * 
     * @param int $userId
     * @param array $balanceData
     * @param Carbon|null $date
     * @return static
     */
    public static function createSnapshot(int $userId, array $balanceData, ?Carbon $date = null): static
    {
        return static::create([
            'user_id' => $userId,
            'current_balance' => $balanceData['current_balance'] ?? 0,
            'total_earnings' => $balanceData['total_earnings'] ?? 0,
            'total_commissions' => $balanceData['total_commissions'] ?? 0,
            'pending_amount' => $balanceData['pending_amount'] ?? 0,
            'paid_amount' => $balanceData['paid_amount'] ?? 0,
            'transaction_count' => $balanceData['transaction_count'] ?? 0,
            'last_transaction_date' => $balanceData['last_transaction_date'] ?? null,
            'hierarchy_data' => $balanceData['hierarchy_data'] ?? null,
            'commission_breakdown' => $balanceData['commission_breakdown'] ?? null,
            'performance_metrics' => $balanceData['performance_metrics'] ?? null,
            'snapshot_date' => $date ?? now()
        ]);
    }

    /**
     * Accessor pour le solde formaté
     * 
     * @return string
     */
    public function getFormattedCurrentBalanceAttribute(): string
    {
        return number_format($this->current_balance, 2) . ' EUR';
    }

    /**
     * Accessor pour les gains totaux formatés
     * 
     * @return string
     */
    public function getFormattedTotalEarningsAttribute(): string
    {
        return number_format($this->total_earnings, 2) . ' EUR';
    }

    /**
     * Accessor pour les commissions totales formatées
     * 
     * @return string
     */
    public function getFormattedTotalCommissionsAttribute(): string
    {
        return number_format($this->total_commissions, 2) . ' EUR';
    }

    /**
     * Accessor pour le montant en attente formaté
     * 
     * @return string
     */
    public function getFormattedPendingAmountAttribute(): string
    {
        return number_format($this->pending_amount, 2) . ' EUR';
    }

    /**
     * Accessor pour le montant payé formaté
     * 
     * @return string
     */
    public function getFormattedPaidAmountAttribute(): string
    {
        return number_format($this->paid_amount, 2) . ' EUR';
    }

    /**
     * Accessor pour la date de snapshot formatée
     * 
     * @return string
     */
    public function getFormattedSnapshotDateAttribute(): string
    {
        return $this->snapshot_date->format('d/m/Y H:i:s');
    }

    /**
     * Accessor pour la date de dernière transaction formatée
     * 
     * @return string|null
     */
    public function getFormattedLastTransactionDateAttribute(): ?string
    {
        return $this->last_transaction_date ? $this->last_transaction_date->format('d/m/Y H:i:s') : null;
    }

    /**
     * Accessor pour les données hiérarchiques formatées
     * 
     * @return array
     */
    public function getFormattedHierarchyDataAttribute(): array
    {
        if (!$this->hierarchy_data) {
            return [];
        }

        $data = $this->hierarchy_data;
        
        // Formater les montants dans les données hiérarchiques
        if (isset($data['integrators'])) {
            foreach ($data['integrators'] as &$integrator) {
                if (isset($integrator['total_commissions'])) {
                    $integrator['formatted_total_commissions'] = number_format($integrator['total_commissions'], 2) . ' EUR';
                }
            }
        }

        if (isset($data['operators'])) {
            foreach ($data['operators'] as &$operator) {
                if (isset($operator['total_earnings'])) {
                    $operator['formatted_total_earnings'] = number_format($operator['total_earnings'], 2) . ' EUR';
                }
            }
        }

        if (isset($data['charging_points'])) {
            foreach ($data['charging_points'] as &$point) {
                if (isset($point['total_revenue'])) {
                    $point['formatted_total_revenue'] = number_format($point['total_revenue'], 2) . ' EUR';
                }
            }
        }

        return $data;
    }
}
