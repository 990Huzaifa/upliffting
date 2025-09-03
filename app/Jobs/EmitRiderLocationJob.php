<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Events\RideLocationUpdated;
use App\Models\Rides;
use App\Models\User;

class EmitRiderLocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rideId;
    protected $userId;
    public function __construct($rideId, $userId)
    {
        $this->rideId = $rideId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $ride = Rides::find($this->rideId);
        $user = User::find($this->userId);

        if ($ride && $ride->status !== 'completed' && $ride->status !== 'cancelled') {
            // Broadcast rider's location
            broadcast(new RideLocationUpdated($ride->id, $user->lat, $user->lng));

            // Dispatch the job again after 5 seconds if status is not completed or cancelled
            if ($ride->status !== 'completed' && $ride->status !== 'cancelled') {
                EmitRiderLocationJob::dispatch($this->rideId, $this->userId)
                    ->delay(now()->addSeconds(5));
            }
        }
    }
}
