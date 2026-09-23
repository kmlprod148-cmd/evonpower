<?php

namespace App\Events;

use App\Models\ChargePointCommand;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OcppCommandSucceeded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var ChargePointCommand
     */
    public ChargePointCommand $command;

    /**
     * Create a new event instance.
     *
     * @param ChargePointCommand $command
     */
    public function __construct(ChargePointCommand $command)
    {
        $this->command = $command;
    }
}
