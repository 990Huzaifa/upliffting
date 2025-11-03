<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NoRider
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $title;
    public $rideId;
    public $riderIds; // array of user ids (riders)

    public function __construct($title, $rideId, array $riderIds)
    {
        $this->title = $title;
        $this->rideId = (int)$rideId;
        $this->riderIds = $riderIds;
    }

    public function broadcastOn()
    {
        // Emit to rider rooms and the ride room
        // $channels = [new PrivateChannel('ride.'.$this->rideId)];
        foreach ($this->riderIds as $rid) {
            $channels[] = new PrivateChannel('driver.'.$rid);
        }
        return $channels;

    }

    public function broadcastAs() 
    { 
        return 'RideNotifyer';  // Socket event name
    }

    public function broadcastWith()
    {
        return array_merge([
            'title' => $this->title,
            'rideId' => $this->rideId,
        ]); // extra data added here
    }
}
