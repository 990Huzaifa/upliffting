<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class RideAccepted implements ShouldBroadcast
{
    use SerializesModels;

    public $title;
    public $rideId;
    public $rider; // minimal rider info array
    public $data; // extra ride info to send

    public function __construct($title, $rideId, array $rider, array $data = [])
    {
        $this->title = $title;
        $this->rideId = $rideId;
        $this->rider = $rider;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return [ new PrivateChannel('ride.'.$this->rideId) ];
    }

    public function broadcastAs() { return 'ride.accepted'; }

    public function broadcastWith()
    {


        return array_merge([
            'title' => $this->title,
            'rideId' => $this->rideId,
            'rider' => $this->rider,
            'status' => 'accepted',
        ], $this->data); // extra data added here
    }
}
