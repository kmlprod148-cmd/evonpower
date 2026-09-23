<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcppCommandOutbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'command',
        'charge_box_id',
        'connector_id',
        'payload',
        'status',
        'reservation_id',
        'charging_session_id',
        'correlation_id',
        'sent_at',
        'acknowledged_at',
        'responded_at',
        'response',
        'retry_count',
        'max_retries',
        'next_retry_at',
        'last_error_at',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'responded_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'last_error_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function chargingSession(): BelongsTo
    {
        return $this->belongsTo(ChargingSession::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isAcknowledged(): bool
    {
        return $this->status === 'acknowledged';
    }

    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retries;
    }

    public function markSent(array $response = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'response' => $response,
        ]);
    }

    public function markAcknowledged(): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);
    }

    public function markResponded(array $response): void
    {
        $this->update([
            'status' => 'responded',
            'responded_at' => now(),
            'response' => $response,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'last_error' => $error,
            'last_error_at' => now(),
        ]);
    }

    public function incrementRetry(): void
    {
        $this->increment('retry_count');
        $this->update([
            'next_retry_at' => now()->addSeconds($this->calculateBackoff()),
            'status' => 'pending',
        ]);
    }

    protected function calculateBackoff(): int
    {
        return (int) pow(2, $this->retry_count) * 10; // 10s, 20s, 40s
    }
}