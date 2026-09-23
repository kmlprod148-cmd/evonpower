<?php

namespace App\Events;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletBalanceHigh
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $wallet;
    public $transaction;

    /**
     * Create a new event instance.
     */
    public function __construct(Wallet $wallet, WalletTransaction $transaction)
    {
        $this->wallet = $wallet;
        $this->transaction = $transaction;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new PrivateChannel('wallet.' . $this->wallet->id);
    }
}
