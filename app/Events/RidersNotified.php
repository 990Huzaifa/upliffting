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

     public $rideId;
    public $riderIds; // array of user ids (riders)

    public function __construct($rideId, array $riderIds)
    {
        $this->rideId = (int)$rideId;
        $this->riderIds = $riderIds;
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

    public function broadcastAs() { return 'ride.request'; }

    public function broadcastWith()
    {
        return [
            'rideId' => $this->rideId,
            'timeout' => 30,
        ];
    }
}
