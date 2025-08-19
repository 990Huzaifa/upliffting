<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class RideAccepted implements ShouldBroadcast
{
    use SerializesModels;

    public $rideId;
    public $riderId;
    public $rider; // minimal rider info array

    public function __construct($rideId, $riderId, array $rider)
    {
        $this->rideId = $rideId;
        $this->riderId = $riderId;
        $this->rider = $rider;
    }

    public function broadcastOn()
    {
        return [ new PrivateChannel('ride.'.$this->rideId) ];
    }

    public function broadcastAs() { return 'ride.accepted'; }

    public function broadcastWith()
    {
        return [
            'rideId' => $this->rideId,
            'riderId' => $this->riderId,
            'rider' => $this->rider,
            'status' => 'accepted',
        ];
    }
}
