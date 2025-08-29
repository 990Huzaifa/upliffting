<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideSearchProgress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $title;
    public $rideId;
    public $customerId;
    public $currentRadius;
    public $maxRadius;

    public function __construct($title, $rideId, $customerId, $currentRadius, $maxRadius)
    {
        $this->title = $title;
        $this->rideId = $rideId;
        $this->customerId = $customerId;
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
            new PrivateChannel('customer.'.$this->customerId),
        ];
    }

    public function broadcastAs()
    {
        return 'RideSearchProgress'; // Socket event name
    }

    public function broadcastWith()
    {
        return [
            'title' => $this->title,
            'rideId' => $this->rideId,
            'currentRadius' => $this->currentRadius,
            'maxRadius' => $this->maxRadius,
        ];
    }
}
