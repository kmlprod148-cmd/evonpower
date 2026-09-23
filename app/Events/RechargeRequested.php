<?php

namespace App\Events;

use App\Models\EnhancedUser;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RechargeRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public EnhancedUser $operator;
    public array $rechargeData;

    /**
     * Create a new event instance.
     */
    public function __construct(EnhancedUser $operator, array $rechargeData = [])
    {
        $this->operator = $operator;
        $this->rechargeData = $rechargeData;
    }
}
