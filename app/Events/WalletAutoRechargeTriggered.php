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

class WalletAutoRechargeTriggered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $wallet;
    public $triggerTransaction;

    /**
     * Create a new event instance.
     */
    public function __construct(Wallet $wallet, WalletTransaction $triggerTransaction)
    {
        $this->wallet = $wallet;
        $this->triggerTransaction = $triggerTransaction;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new PrivateChannel('wallet.' . $this->wallet->id);
    }
}
