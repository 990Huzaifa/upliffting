<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideSearchProgress
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

     public $rideId;
    public $currentRadius;
    public $maxRadius;

    public function __construct($rideId, $currentRadius, $maxRadius)
    {
        $this->rideId = $rideId;
        $this->currentRadius = $currentRadius;
        $this->maxRadius = $maxRadius;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ride.'.$this->rideId),
        ];
    }

    public function broadcastAs()
    {
        return 'ride.progress'; // Socket event name
    }

    public function broadcastWith()
    {
        return [
            'rideId' => $this->rideId,
            'currentRadius' => $this->currentRadius,
            'maxRadius' => $this->maxRadius,
            'status' => 'searching',
        ];
    }
}
