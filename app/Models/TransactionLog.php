<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'action',
        'performed_by',
        'note',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the transaction this log belongs to
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the user who performed the action
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Log a withdrawal status change
     */
    public static function logWithdrawalStatusChange(
        int $transactionId,
        string $action,
        ?int $performedBy,
        string $note = null,
        array $metadata = []
    ): self {
        return self::create([
            'transaction_id' => $transactionId,
            'action' => $action,
            'performed_by' => $performedBy,
            'note' => $note,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
