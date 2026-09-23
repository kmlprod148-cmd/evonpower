<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\MoneyService;

class CreditPack extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'amount',
        'price',
        'currency',
        'order',
        'is_active',
        'is_featured',
        'is_client_only',
        'icon',
        'color',
        'bonus',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_client_only' => 'boolean',
        'bonus' => 'array',
    ];

    /**
     * Relation avec les recharges
     */
    public function recharges(): HasMany
    {
        return $this->hasMany(CreditRecharge::class);
    }

    /**
     * Scope pour les packs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les packs mis en avant
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope pour trier par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('amount');
    }

    /**
     * Scope pour les packs réservés aux clients
     */
    public function scopeClientOnly($query)
    {
        if ($this->hasColumn('credit_packs', 'is_client_only')) {
            return $query->where('is_client_only', true);
        }
        // Si la colonne n'existe pas, retourner une requête vide
        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope pour les packs publics (non réservés aux clients)
     */
    public function scopePublic($query)
    {
        if ($this->hasColumn('credit_packs', 'is_client_only')) {
            return $query->where('is_client_only', false);
        }
        // Si la colonne n'existe pas, retourner tous les résultats (comportement par défaut)
        return $query;
    }

    /**
     * Check if a column exists in a table (database-agnostic)
     */
    protected function hasColumn(string $table, string $column): bool
    {
        try {
            $driver = DB::getDriverName();
            
            if ($driver === 'sqlite') {
                // Use proper SQLite syntax with quotes
                $columns = DB::select("PRAGMA table_info('{$table}')");
                foreach ($columns as $col) {
                    if (isset($col->name) && $col->name === $column) {
                        return true;
                    }
                }
                return false;
            } else {
                // MySQL/MariaDB
                return Schema::hasColumn($table, $column);
            }
        } catch (\Exception $e) {
            // If schema check fails, assume column doesn't exist
            return false;
        }
    }

    /**
     * Obtient le montant formaté
     */
    public function getFormattedAmountAttribute(): string
    {
        return MoneyService::format($this->amount, $this->currency);
    }

    /**
     * Obtient le prix formaté
     */
    public function getFormattedPriceAttribute(): string
    {
        return MoneyService::format($this->price, $this->currency);
    }

    /**
     * Calcule le bonus en pourcentage (si applicable)
     */
    public function getBonusPercentageAttribute(): ?float
    {
        if (!$this->bonus || !isset($this->bonus['type']) || $this->bonus['type'] !== 'percentage') {
            return null;
        }

        return (float) ($this->bonus['value'] ?? 0);
    }

    /**
     * Calcule le montant total avec bonus
     */
    public function getTotalAmountWithBonusAttribute(): float
    {
        $bonusPercentage = $this->bonus_percentage;
        
        if ($bonusPercentage) {
            return $this->amount * (1 + $bonusPercentage / 100);
        }

        return $this->amount;
    }

    /**
     * Obtient le montant de bonus
     */
    public function getBonusAmountAttribute(): float
    {
        $totalWithBonus = $this->total_amount_with_bonus;
        return $totalWithBonus - $this->amount;
    }

    /**
     * Vérifie si le pack a un bonus
     */
    public function hasBonus(): bool
    {
        return $this->bonus_percentage !== null && $this->bonus_percentage > 0;
    }

    /**
     * Obtient la description du bonus
     */
    public function getBonusDescriptionAttribute(): ?string
    {
        if (!$this->hasBonus()) {
            return null;
        }

        $percentage = $this->bonus_percentage;
        $bonusAmount = $this->bonus_amount;
        
        return "+{$percentage}% de bonus (" . MoneyService::format($bonusAmount, $this->currency) . ")";
    }
}

