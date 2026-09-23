<?php

namespace App\Events;

use App\Models\ChargePointCommand;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OcppCommandFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var ChargePointCommand
     */
    public ChargePointCommand $command;

    /**
     * @var string|null
     */
    public ?string $error;

    /**
     * Create a new event instance.
     *
     * @param ChargePointCommand $command
     * @param string|null $error
     */
    public function __construct(ChargePointCommand $command, ?string $error = null)
    {
        $this->command = $command;
        $this->error = $error;
    }
}
