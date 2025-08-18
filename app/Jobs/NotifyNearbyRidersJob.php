<?php

namespace App\Jobs;

use App\Models\Rides;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyNearbyRidersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rideId;

    public function __construct($rideId)
    {
        $this->rideId = $rideId;
    }

    public function handle(FirebaseService $firebaseService)
    {
        $ride = Rides::find($this->rideId);

        if (!$ride) {
            return;
        }

        $lat = $ride->pickup_lat;
        $lng = $ride->pickup_lng;
        $vehicleTypeRateId = $ride->vehicle_type_rate_id;
        $radius = 1;
        $maxRadius = 10;

        // Aapka existing logic yahan paste karein
        $result = notifyNearbyRiders($vehicleTypeRateId, $lat, $lng, $radius, $maxRadius, $ride);
        // return $result;
        if (is_array($result)) {
            $riders = $result['riders'];
            $ride = $result['ride'];
            $this->sendNotification($riders, $ride, $firebaseService);
        }
    }

    private function sendNotification($riders, $ride, $firebaseService)
    {
        // Aapka existing sendNotification logic yahan paste karein
        $fcmTokens = $riders->pluck('fcm_token')->toArray();

        if (empty($fcmTokens)) {
            return;
        }

        $customer = User::find($ride->customer_id);
        $customerName = $customer ? $customer->first_name . ' ' . $customer->last_name : 'Customer';
        
        $appType = 'rider';
        $title = 'New Ride Request Nearby';
        $body = 'A new ride request is waiting for you!';
        $data = [
            'rideId' => $ride->id,
            'rideStatus' => $ride->status,
            'customerName' => $customerName,
            'BaseFare' => $ride->base_fare,
            'pickupLat' => $ride->pickup_lat,
            'pickupLng' => $ride->pickup_lng,
        ];

        // Firebase service use karein notification bhejne ke liye
        $firebaseService->sendToMultipleDevices($appType, $fcmTokens, $title, $body, $data);
    }
}