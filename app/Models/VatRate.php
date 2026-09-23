<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VatRate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'rate', 'is_default', 'is_active'];

    protected $casts = [
        'rate' => 'float',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public static function getDefault()
    {
        return self::where('is_default', true)->first() ?? self::first();
    }

    public static function getActiveRates()
    {
        return self::where('is_active', true)->orderBy('rate')->get();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('rate');
    }
    /**
     * Get plans using this VAT rate
     */
    public function plans()
    {
        return $this->hasMany(Plan::class);
    }

    /**
     * Get additional rates using this VAT rate
     */
    public function additionalRates()
    {
        return $this->hasMany(AdditionalRate::class);
    }
}
