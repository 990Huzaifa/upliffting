<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class RideCancelled implements ShouldBroadcast
{
    use SerializesModels;

    public $rideId;
    public function __construct($rideId) { $this->rideId = (int)$rideId; }

    public function broadcastOn() { return [ new PrivateChannel('ride.'.$this->rideId) ]; }
    public function broadcastAs() { return 'ride.cancelled'; }
    public function broadcastWith()
    {
        return ['rideId' => $this->rideId, 'status' => 'cancelled'];
    }
}
