<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Rides;
use App\Models\User;
use App\Events\RideAccepted;
use Illuminate\Support\Facades\Log;


class HandleRiderResponseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rideId;
    protected $riderId;
    protected $response;


    public function __construct($rideId, $riderId, $response)
    {
        $this->rideId = $rideId;
        $this->riderId = $riderId;
        $this->response = $response;
    }

    public function handle()
    {
        $ride = Rides::find($this->rideId);

        if (!$ride || $ride->status !== 'finding') {
            return;
        }

        if ($this->response == 'accept') {
            $this->handleAcceptance($ride);
        }
    }

    private function handleAcceptance($ride)
    {
        // Update ride status
        $ride->update([
            'rider_id' => $this->riderId,
            'status' => 'on a way'
        ]);

        Log::debug('HandleRiderResponseJob: yaha tk working', [
            'rideId' => $ride->id,
            'customer_id' => $ride->customer_id,
        ]);
        
        // Notify customer about acceptance
        $this->notifyCustomerRideAccepted($ride);
    }


    private function notifyCustomerRideAccepted($ride)
    {
        $customer = User::find($ride->customer_id);
        $rider = User::find($this->riderId);

        Log::debug('HandleRiderResponseJob: Customer check', [
            'rideId' => $ride->id,
            'customer_id' => $ride->customer_id,
        ]);

        if ($customer) {
            $title = 'Driver Found!';
            $body = "Your driver {$rider->first_name} is on the way";
            $data = [
                'rideId' => $ride->id,
                'status' => 'on a way',
                'riderId' => $this->riderId,
                'riderName' => $rider->first_name . ' ' . $rider->last_name,
                'riderPhone' => $rider->phone,
                'riderLat' => $rider->lat,
                'riderLng' => $rider->lng
            ];

            broadcast(new RideAccepted(
                    $title,
                    $ride->id,
                    $rider,
                    $data
                ));

            Log::debug('HandleRiderResponseJob: Driver found', [
                'rideId' => $this->rideId,
            ]);
            return; 
            
        }
    }

}
