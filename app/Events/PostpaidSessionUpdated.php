<?php

namespace App\Events;

use App\Models\ChargingSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostpaidSessionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $updates;

    /**
     * Create a new event instance.
     */
    public function __construct(ChargingSession $session, array $updates = [])
    {
        $this->session = $session;
        $this->updates = $updates;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('postpaid-session.' . $this->session->session_id),
            new PrivateChannel('user.' . $this->session->user_id . '.sessions'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->session_id,
            'status' => $this->session->status,
            'duration_minutes' => $this->session->duration ?? 0,
            'energy_consumed_kwh' => $this->session->energy_consumed ?? 0,
            'current_cost' => $this->session->cost ?? 0,
            'updates' => $this->updates,
            'updated_at' => $this->session->updated_at?->toISOString(),
        ];
    }
}

