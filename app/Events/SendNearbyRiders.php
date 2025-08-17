<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendNearbyRiders implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;

    public $riders;
    /**
     * Create a new event instance.
     */
    public function __construct($userId, $riders)
    {
        $this->userId = $userId;
        $this->riders = $riders;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('nearbyriders.'.$this->userId),
        ];
    }

    public function broadcastWith()
    {
        return [
            'riders' => $this->riders
        ];
    }

    public function broadcastAs()
    {
        return 'SendNearbyRiders';
    }
}
