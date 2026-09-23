<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;
    public $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(Transaction $transaction, array $changes = [])
    {
        $this->transaction = $transaction;
        $this->changes = $changes;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Canal pour les admins
        $channels[] = new PrivateChannel('transaction-history.admin');

        // Canal pour les intégrateurs concernés
        if ($this->transaction->chargingPoint && $this->transaction->chargingPoint->integrator_id) {
            $channels[] = new PrivateChannel('transaction-history.integrator.' . $this->transaction->chargingPoint->integrator_id);
        }

        // Canal pour l'opérateur concerné
        if ($this->transaction->chargingPoint && $this->transaction->chargingPoint->user_id) {
            $channels[] = new PrivateChannel('transaction-history.operator.' . $this->transaction->chargingPoint->user_id);
        }

        // Canal pour l'utilisateur concerné
        if ($this->transaction->user_id) {
            $channels[] = new PrivateChannel('transaction-history.user.' . $this->transaction->user_id);
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'transaction' => [
                'id' => $this->transaction->id,
                'type' => $this->transaction->transaction_type,
                'category' => $this->transaction->transaction_category,
                'status' => $this->transaction->status,
                'amount' => $this->transaction->price_total,
                'currency' => $this->transaction->currency,
                'user_name' => $this->transaction->user->name ?? 'N/A',
                'charging_point_name' => $this->transaction->chargingPoint->name ?? 'N/A',
                'updated_at' => $this->transaction->updated_at->format('d/m/Y H:i'),
                'updated_at_human' => $this->transaction->updated_at->diffForHumans(),
            ],
            'changes' => $this->changes,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'transaction.updated';
    }
}
