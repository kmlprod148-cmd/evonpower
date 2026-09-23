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

class PostpaidSessionCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $invoice;
    public $payment;

    /**
     * Create a new event instance.
     */
    public function __construct(ChargingSession $session, array $invoice = [], array $payment = [])
    {
        $this->session = $session;
        $this->invoice = $invoice;
        $this->payment = $payment;
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
            'ended_at' => $this->session->ended_at?->toISOString(),
            'energy_consumed_kwh' => $this->session->energy_consumed ?? 0,
            'duration_minutes' => $this->session->duration ?? 0,
            'final_cost' => $this->session->cost ?? 0,
            'invoice' => $this->invoice,
            'payment' => $this->payment,
        ];
    }
}

