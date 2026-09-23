<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalRateDay extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'additional_rate_id',
        'day_of_week',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'day_of_week' => 'integer',
    ];

    /**
     * Get the additional rate that owns this day.
     */
    public function additionalRate()
    {
        return $this->belongsTo(AdditionalRate::class);
    }

    /**
     * Get the day name in French.
     *
     * @return string
     */
    public function getDayNameAttribute()
    {
        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

        return $days[$this->day_of_week] ?? 'Inconnu';
    }

    /**
     * Get the day name in English.
     *
     * @return string
     */
    public function getDayNameEnAttribute()
    {
        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        return $days[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Scope a query to filter by day of week.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $dayOfWeek
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope a query to filter by weekdays only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWeekdays($query)
    {
        return $query->whereIn('day_of_week', [1, 2, 3, 4, 5]);
    }

    /**
     * Scope a query to filter by weekends only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWeekends($query)
    {
        return $query->whereIn('day_of_week', [6, 7]);
    }
}
