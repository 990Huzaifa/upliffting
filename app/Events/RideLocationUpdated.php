<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $rideId;
    public $lat;
    public $lng;
    public $status;

    public function __construct($rideId, $lat, $lng, $status)
    {
        $this->rideId = $rideId;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->status = $status;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
            return [ new PrivateChannel('ride.'.$this->rideId) ];
    }

    public function broadcastAs() 
    { 
        return 'RideInfo'; 
    }

    public function broadcastWith()
    {
        return [
            'rideId' => $this->rideId,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'rideStatus' => $this->status
        ];
    }
}
