<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OcppIncomingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_type',
        'charge_box_id',
        'connector_id',
        'action',
        'payload',
        'processed',
        'received_at',
        'processed_at',
        'processing_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function markProcessed(): void
    {
        $this->update([
            'processed' => true,
            'processed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'processing_error' => $error,
            'processed_at' => now(),
        ]);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('message_type', $type);
    }
}