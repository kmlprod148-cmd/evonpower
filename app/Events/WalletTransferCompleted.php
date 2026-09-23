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

class WalletTransferCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sourceWallet;
    public $destinationWallet;
    public $sourceTransaction;
    public $destinationTransaction;

    /**
     * Create a new event instance.
     */
    public function __construct(
        Wallet $sourceWallet,
        Wallet $destinationWallet,
        WalletTransaction $sourceTransaction,
        WalletTransaction $destinationTransaction
    ) {
        $this->sourceWallet = $sourceWallet;
        $this->destinationWallet = $destinationWallet;
        $this->sourceTransaction = $sourceTransaction;
        $this->destinationTransaction = $destinationTransaction;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return [
            new PrivateChannel('wallet.' . $this->sourceWallet->id),
            new PrivateChannel('wallet.' . $this->destinationWallet->id),
        ];
    }
}
