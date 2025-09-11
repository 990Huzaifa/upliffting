<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class RideCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $title;
    public $rideId;
    public $reason; // extra ride info to send

    public function __construct($title, $rideId, $reason)
    {
        $this->title = $title;
        $this->rideId = $rideId;
        $this->reason = $reason;
    }

    public function broadcastOn()
    {
        return [ new PrivateChannel('ride.'.$this->rideId) ];
    }

    public function broadcastAs() 
    { 
        return 'RideCancelled'; 
    }

    public function broadcastWith()
    {
        return [
            'title' => $this->title,
            'rideId' => $this->rideId,
            'status' => 'Cancelled',
            'reason' => $this->reason,
        ];
    }
}
