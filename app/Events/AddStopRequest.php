<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class AddStopRequest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $rideId;
    public $data; // extra ride info to send
    public $status;
    
    public function __construct($rideId, array $data = [], $status)
    {

        $this->rideId = $rideId;
        $this->data = $data;
        $this->status = $status;
    }

    public function broadcastOn()
    {
        return [ new PrivateChannel('ride.'.$this->rideId) ];
    }

    public function broadcastAs() 
    { 
        return 'AddStopRequest'; 
    }

    public function broadcastWith()
    {
        return array_merge([
            'title' => 'Add Stop Request',
            'rideId' => $this->rideId,
            'status' => $this->status,
        ], $this->data); // extra data added here
    }
}
