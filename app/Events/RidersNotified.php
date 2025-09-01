<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RidersNotified
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $title;
    public $rideId;
    public $riderIds; // array of user ids (riders)
    public $data; // extra ride info to send

    public function __construct($title, $rideId, array $riderIds, array $data = [])
    {
        $this->title = $title;
        $this->rideId = (int)$rideId;
        $this->riderIds = $riderIds;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        // Emit to rider rooms and the ride room
        $channels = [new PrivateChannel('ride.'.$this->rideId)];
        foreach ($this->riderIds as $rid) {
            $channels[] = new PrivateChannel('rider.'.$rid);
        }
        return $channels;
    }

    public function broadcastAs() 
    { 
        return 'RideRequests';  // Socket event name
    }

    public function broadcastWith()
    {
        return array_merge([
            'title' => $this->title,
            'rideId' => $this->rideId,
            'timeout' => 30,
        ], $this->data); // extra data added here
    }
}
