<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUniqueIds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Notification extends Model
{
    use HasFactory, HasUniqueIds;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Determine if the model uses unique ids.
     */
    public function usesUniqueIds(): bool
    {
        return true;
    }

    /**
     * Generate a new UUID for the model.
     */
    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Get the columns that should receive a unique identifier.
     */
    public function uniqueIds(): array
    {
        return [$this->getKeyName()];
    }

    /**
     * Get the notifiable entity that owns the notification
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * Get the user that owns the notification (for backward compatibility)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notifiable_id')->where('notifiable_type', User::class);
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope for notifications by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for recent notifications
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if notification is read
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if notification is unread
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): bool
    {
        if ($this->isRead()) {
            return true;
        }

        return $this->update(['read_at' => now()]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(): bool
    {
        return $this->update(['read_at' => null]);
    }

    /**
     * Get formatted creation date
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get notification icon based on type
     */
    public function getIconAttribute(): string
    {
        $icons = [
            'info' => 'info-circle',
            'success' => 'check-circle',
            'warning' => 'exclamation-triangle',
            'error' => 'times-circle',
            'security' => 'shield-alt',
            'financial' => 'dollar-sign',
            'system' => 'cog'
        ];

        return $icons[$this->type] ?? 'bell';
    }

    /**
     * Get notification color based on type
     */
    public function getColorAttribute(): string
    {
        $colors = [
            'info' => 'blue',
            'success' => 'green',
            'warning' => 'yellow',
            'error' => 'red',
            'security' => 'purple',
            'financial' => 'green',
            'system' => 'gray'
        ];

        return $colors[$this->type] ?? 'gray';
    }

    /**
     * Get notification priority
     */
    public function getPriorityAttribute(): int
    {
        $priorities = [
            'error' => 5,
            'security' => 4,
            'financial' => 3,
            'warning' => 2,
            'success' => 1,
            'info' => 1,
            'system' => 1
        ];

        return $priorities[$this->type] ?? 1;
    }

    /**
     * Convert notification to array for API responses
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'is_read' => $this->isRead(),
            'icon' => $this->icon,
            'color' => $this->color,
            'priority' => $this->priority,
            'created_at' => $this->created_at->toISOString(),
            'formatted_created_at' => $this->formatted_created_at,
            'read_at' => $this->read_at?->toISOString(),
        ];
    }
}