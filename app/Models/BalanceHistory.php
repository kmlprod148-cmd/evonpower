<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Modèle pour l'historique des balances
 * 
 * Enregistre tous les changements de balance des utilisateurs
 * pour un audit complet et une traçabilité
 */
class BalanceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'previous_balance',
        'new_balance',
        'amount_changed',
        'change_type',
        'source',
        'source_id',
        'description',
        'metadata',
        'calculated_at'
    ];

    protected $casts = [
        'previous_balance' => 'decimal:2',
        'new_balance' => 'decimal:2',
        'amount_changed' => 'decimal:2',
        'metadata' => 'array',
        'calculated_at' => 'datetime'
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
     * Scope pour filtrer par type de changement
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByChangeType($query, string $type)
    {
        return $query->where('change_type', $type);
    }

    /**
     * Scope pour filtrer par source
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $source
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
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
        return $query->whereBetween('calculated_at', [$start, $end]);
    }

    /**
     * Scope pour les crédits
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCredits($query)
    {
        return $query->where('amount_changed', '>', 0);
    }

    /**
     * Scope pour les débits
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDebits($query)
    {
        return $query->where('amount_changed', '<', 0);
    }

    /**
     * Scope pour les ajustements
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAdjustments($query)
    {
        return $query->where('change_type', 'adjustment');
    }

    /**
     * Scope pour les recalculs
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecalculations($query)
    {
        return $query->where('change_type', 'recalculation');
    }

    /**
     * Scope pour les transactions
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTransactions($query)
    {
        return $query->where('source', 'transaction');
    }

    /**
     * Scope pour les commissions
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommissions($query)
    {
        return $query->where('source', 'commission');
    }

    /**
     * Scope pour les ajustements manuels
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeManual($query)
    {
        return $query->where('source', 'manual');
    }

    /**
     * Scope pour les ajustements système
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystem($query)
    {
        return $query->where('source', 'system');
    }

    /**
     * Récupère l'historique d'un utilisateur pour une période
     * 
     * @param int $userId
     * @param Carbon $start
     * @param Carbon $end
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserHistory(int $userId, Carbon $start, Carbon $end)
    {
        return static::where('user_id', $userId)
            ->byPeriod($start, $end)
            ->orderBy('calculated_at', 'desc')
            ->get();
    }

    /**
     * Récupère le solde d'un utilisateur à une date donnée
     * 
     * @param int $userId
     * @param Carbon $date
     * @return float
     */
    public static function getBalanceAtDate(int $userId, Carbon $date): float
    {
        $history = static::where('user_id', $userId)
            ->where('calculated_at', '<=', $date)
            ->orderBy('calculated_at', 'desc')
            ->first();

        return $history ? (float) $history->new_balance : 0.0;
    }

    /**
     * Récupère le total des mouvements pour un utilisateur
     * 
     * @param int $userId
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    public static function getUserMovements(int $userId, Carbon $start, Carbon $end): array
    {
        $movements = static::where('user_id', $userId)
            ->byPeriod($start, $end)
            ->selectRaw('
                change_type,
                source,
                SUM(amount_changed) as total_amount,
                COUNT(*) as transaction_count
            ')
            ->groupBy('change_type', 'source')
            ->get();

        return $movements->map(function($movement) {
            return [
                'change_type' => $movement->change_type,
                'source' => $movement->source,
                'total_amount' => (float) $movement->total_amount,
                'transaction_count' => (int) $movement->transaction_count
            ];
        })->toArray();
    }

    /**
     * Récupère les statistiques d'un utilisateur
     * 
     * @param int $userId
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    public static function getUserStatistics(int $userId, Carbon $start, Carbon $end): array
    {
        $stats = static::where('user_id', $userId)
            ->byPeriod($start, $end)
            ->selectRaw('
                SUM(CASE WHEN amount_changed > 0 THEN amount_changed ELSE 0 END) as total_credits,
                SUM(CASE WHEN amount_changed < 0 THEN ABS(amount_changed) ELSE 0 END) as total_debits,
                COUNT(*) as total_transactions,
                AVG(amount_changed) as average_change,
                MIN(amount_changed) as min_change,
                MAX(amount_changed) as max_change
            ')
            ->first();

        return [
            'total_credits' => (float) ($stats->total_credits ?? 0),
            'total_debits' => (float) ($stats->total_debits ?? 0),
            'net_change' => (float) (($stats->total_credits ?? 0) - ($stats->total_debits ?? 0)),
            'total_transactions' => (int) ($stats->total_transactions ?? 0),
            'average_change' => (float) ($stats->average_change ?? 0),
            'min_change' => (float) ($stats->min_change ?? 0),
            'max_change' => (float) ($stats->max_change ?? 0)
        ];
    }

    /**
     * Crée un enregistrement d'historique
     * 
     * @param int $userId
     * @param float $previousBalance
     * @param float $newBalance
     * @param string $changeType
     * @param string $source
     * @param int|null $sourceId
     * @param string|null $description
     * @param array|null $metadata
     * @return static
     */
    public static function createHistory(
        int $userId,
        float $previousBalance,
        float $newBalance,
        string $changeType,
        string $source,
        ?int $sourceId = null,
        ?string $description = null,
        ?array $metadata = null
    ): static {
        return static::create([
            'user_id' => $userId,
            'previous_balance' => $previousBalance,
            'new_balance' => $newBalance,
            'amount_changed' => $newBalance - $previousBalance,
            'change_type' => $changeType,
            'source' => $source,
            'source_id' => $sourceId,
            'description' => $description,
            'metadata' => $metadata,
            'calculated_at' => now()
        ]);
    }

    /**
     * Accessor pour le montant formaté
     * 
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount_changed, 2) . ' EUR';
    }

    /**
     * Accessor pour le solde précédent formaté
     * 
     * @return string
     */
    public function getFormattedPreviousBalanceAttribute(): string
    {
        return number_format($this->previous_balance, 2) . ' EUR';
    }

    /**
     * Accessor pour le nouveau solde formaté
     * 
     * @return string
     */
    public function getFormattedNewBalanceAttribute(): string
    {
        return number_format($this->new_balance, 2) . ' EUR';
    }

    /**
     * Accessor pour le type de changement formaté
     * 
     * @return string
     */
    public function getFormattedChangeTypeAttribute(): string
    {
        return match($this->change_type) {
            'credit' => 'Crédit',
            'debit' => 'Débit',
            'adjustment' => 'Ajustement',
            'recalculation' => 'Recalcul',
            default => ucfirst($this->change_type)
        };
    }

    /**
     * Accessor pour la source formatée
     * 
     * @return string
     */
    public function getFormattedSourceAttribute(): string
    {
        return match($this->source) {
            'transaction' => 'Transaction',
            'commission' => 'Commission',
            'manual' => 'Manuel',
            'system' => 'Système',
            default => ucfirst($this->source)
        };
    }
}
