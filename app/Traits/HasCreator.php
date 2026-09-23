<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasCreator
{
    /**
     * Boot the trait
     */
    protected static function bootHasCreator()
    {
        // Automatically set created_by when creating a model
        static::creating(function (Model $model) {
            if (Auth::check() && !$model->created_by) {
                $model->created_by = Auth::id();
                $model->created_by_type = get_class(Auth::user());
                $model->created_by_id = Auth::id();
            }
        });
    }

    /**
     * Get the user who created this model
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Scope to get models created by a specific user
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope to get models created by the current user
     */
    public function scopeCreatedByMe($query)
    {
        return $query->where('created_by', Auth::id());
    }

    /**
     * Check if the model was created by a specific user
     */
    public function wasCreatedBy($userId)
    {
        return $this->created_by == $userId;
    }

    /**
     * Check if the model was created by the current user
     */
    public function wasCreatedByMe()
    {
        return $this->created_by == Auth::id();
    }
}