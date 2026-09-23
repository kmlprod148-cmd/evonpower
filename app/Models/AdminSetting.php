<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modèle pour les paramètres admin
 */
class AdminSetting extends Model
{
    use HasFactory;

    protected $table = 'admin_settings';

    protected $fillable = [
        'category',
        'key',
        'value',
        'type',
        'description',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Scope pour les paramètres actifs
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour une catégorie spécifique
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope pour une clé spécifique
     */
    public function scopeKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    /**
     * Obtient la valeur typée selon le type
     */
    public function getTypedValueAttribute()
    {
        switch ($this->type) {
            case 'boolean':
                return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
            case 'integer':
                return is_numeric($this->value) ? (float) $this->value : 0;
            case 'json':
                return json_decode($this->value, true);
            default:
                return $this->value;
        }
    }

    /**
     * Définit la valeur selon le type
     */
    public function setTypedValue($value): void
    {
        switch ($this->type) {
            case 'boolean':
                $this->value = $value ? '1' : '0';
                break;
            case 'json':
                $this->value = json_encode($value);
                break;
            default:
                $this->value = (string) $value;
        }
    }

    /**
     * Obtient tous les paramètres d'une catégorie
     */
    public static function getCategorySettings(string $category): array
    {
        return static::active()
            ->category($category)
            ->get()
            ->keyBy('key')
            ->toArray();
    }

    /**
     * Obtient un paramètre spécifique
     */
    public static function getSetting(string $category, string $key, $default = null)
    {
        $setting = static::active()
            ->category($category)
            ->key($key)
            ->first();

        return $setting ? $setting->typed_value : $default;
    }

    /**
     * Met à jour un paramètre
     */
    public static function updateSetting(string $category, string $key, $value, string $type = 'text'): bool
    {
        $setting = static::updateOrCreate(
            [
                'category' => $category,
                'key' => $key,
            ],
            [
                'type' => $type,
                'is_active' => true,
            ]
        );

        $setting->setTypedValue($value);
        return $setting->save();
    }

    /**
     * Désactive un paramètre
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Active un paramètre
     */
    public function activate(): bool
    {
        $this->is_active = true;
        return $this->save();
    }
}
