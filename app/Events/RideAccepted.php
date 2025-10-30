<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class RideAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $title;
    public $rideId;
    public $data; // extra ride info to send

    public function __construct($title, $rideId, array $data = [])
    {
        $this->title = $title;
        $this->rideId = $rideId;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return [ new PrivateChannel('ride.'.$this->rideId) ];
    }

    public function broadcastAs() 
    { 
        return 'RideAccepted'; 
    }

    public function broadcastWith()
    {
        return array_merge([
            'title' => $this->title,
            'rideId' => $this->rideId,
        ], $this->data); // extra data added here
    }
}
