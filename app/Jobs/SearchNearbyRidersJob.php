<?php

// 1. Main Job - SearchNearbyRidersJob.php
namespace App\Jobs;

use App\Models\Rides;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SearchNearbyRidersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rideId;
    protected $currentRadius;
    protected $maxRadius;

    public function __construct($rideId, $currentRadius = 1, $maxRadius = 10)
    {
        $this->rideId = $rideId;
        $this->currentRadius = $currentRadius;
        $this->maxRadius = $maxRadius;
    }

    public function handle(FirebaseService $firebaseService)
    {
        $ride = Rides::find($this->rideId);

        if (!$ride || $ride->status !== 'finding') {
            return "cancelled"; // Ride cancelled or already assigned
        }

        // Notify customer about search progress
        $this->notifyCustomerSearchProgress($ride, $firebaseService);
        
        // Search for riders in current radius
        $riders = $this->findNearbyRiders($ride);
        
        if (!empty($riders) && $this->currentRadius < $this->maxRadius) {
            // Found riders - notify them and wait for response
            // Change ride status to 'pending' when riders are notified
            $ride->update(['status' => 'pending']);
            
            NotifyRidersJob::dispatch($this->rideId, $riders, $this->currentRadius);
        } else {
            // No riders found - increase radius or give up
            $this->handleNoRidersFound($ride, $firebaseService);
        }
    }

    private function findNearbyRiders($ride)
    {
        $query = "
            SELECT users.*, vehicles.vehicle_type_rate_id, 
                (
                    6371 * acos(
                        LEAST(1.0,
                            cos(radians(?)) *
                            cos(radians(users.lat)) *
                            cos(radians(users.lng) - radians(?)) +
                            sin(radians(?)) *
                            sin(radians(users.lat))
                        )
                    )
                ) AS distance 
            FROM users
            INNER JOIN riders ON riders.user_id = users.id
            INNER JOIN vehicles ON vehicles.vehicle_of = users.id
            WHERE users.role = 'rider'
              AND riders.status = 'online'
              AND vehicles.is_driving = 'active'
              AND vehicles.vehicle_type_rate_id = ?
            HAVING distance <= ?
            ORDER BY distance ASC
        ";

        $bindings = [
            $ride->pickup_lat, 
            $ride->pickup_lng, 
            $ride->pickup_lat, 
            $ride->vehicle_type_rate_id, 
            $this->currentRadius
        ];

        return DB::select($query, $bindings);
    }

    private function notifyCustomerSearchProgress($ride, $firebaseService)
    {
        
        $customer = User::find($ride->customer_id);
        if ($customer && $customer->fcm_id) {
            $title = 'Searching for Driver';
            $body = "Looking for drivers within {$this->currentRadius} km radius...";
            $data = [
                'rideId' => $ride->id,
                'status' => 'searching',
                'currentRadius' => $this->currentRadius,
                'maxRadius' => $this->maxRadius
            ];
            $firebaseService->sendToDevice(
                'customer', 
                $customer->fcm_id, 
                $title, 
                $body, 
                $data
            );
        }
    }

    private function handleNoRidersFound($ride, $firebaseService)
    {
        if ($this->currentRadius < $this->maxRadius) {
            // Increase radius and try again after 10 seconds
            SearchNearbyRidersJob::dispatch($this->rideId, $this->currentRadius + 1, $this->maxRadius)
                ->delay(now()->addSeconds(10));
        } else {
            // No riders found within max radius
            $this->notifyCustomerNoRidersFound($ride, $firebaseService);
            
            // Update ride status
            $ride->update(['status' => 'cancelled']);
        }
    }

    private function notifyCustomerNoRidersFound($ride, $firebaseService)
    {
        $customer = User::find($ride->customer_id);
        if ($customer && $customer->fcm_id) {
            $title = 'No Drivers Available';
            $body = 'Sorry, no drivers are available in your area right now. Please try again later.';
            $data = [
                'rideId' => $ride->id,
                'status' => 'cancelled'
            ];
            $data = $firebaseService->sendToDevice(
                'customer', 
                $customer->fcm_id, 
                $title, 
                $body, 
                $data
            );
        }
    }
}

// 2. Notify Riders Job - NotifyRidersJob.php
class NotifyRidersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rideId;
    protected $riders;
    protected $currentRadius;


    public function __construct($rideId, $riders, $currentRadius)
    {
        $this->rideId = $rideId;
        $this->riders = $riders;
        $this->currentRadius = $currentRadius;
    }

    public function handle(FirebaseService $firebaseService)
    {
        $ride = Rides::find($this->rideId);
        
        if (!$ride || !in_array($ride->status, ['finding', 'pending'])) {
            return;
        }

        // Send notification to all found riders
        $this->sendNotificationToRiders($ride);

        // Schedule timeout job - if no response in 30 seconds, search with increased radius
        HandleRiderTimeoutJob::dispatch($this->rideId, $this->currentRadius)
            ->delay(now()->addSeconds(30));
    }

    private function sendNotificationToRiders($ride)
    {
        $fcmTokens = collect($this->riders)->pluck('fcm_id')->filter()->toArray();
        
        if (empty($fcmTokens)) {
            return;
        }

        $customer = User::find($ride->customer_id);
        $customerName = $customer ? $customer->first_name . ' ' . $customer->last_name : 'Customer';
        $firebaseService = new FirebaseService();
        $title = 'New Ride Request Nearby';
        $body = "Pickup: {$ride->pickup_address}";
        $data = [
            'rideId' => $ride->id,
            'rideStatus' => $ride->status,
            'customerName' => $customerName,
            'baseFare' => $ride->base_fare,
            'pickupLat' => $ride->pickup_lat,
            'pickupLng' => $ride->pickup_lng,
            'pickupAddress' => $ride->pickup_address,
            'dropoffAddress' => $ride->dropoff_address,
            'estimatedDistance' => $ride->estimated_distance,
            'estimatedFare' => $ride->estimated_fare,
            'timeout' => 30 // seconds to respond
        ];

        $firebaseService->sendToMultipleDevices('rider', $fcmTokens, $title, $body, $data);
    }
}

// 3. Handle Timeout Job - HandleRiderTimeoutJob.php
class HandleRiderTimeoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rideId;
    protected $currentRadius;

    public function __construct($rideId, $currentRadius)
    {
        $this->rideId = $rideId;
        $this->currentRadius = $currentRadius;
    }

    public function handle()
    {
         $ride = Rides::find($this->rideId);
        
        // Agar ride already accepted ho gai hai ya cancelled hai to kuch nahi karna
        if (!$ride || !in_array($ride->status, ['pending'])) {
            return;
        }

        // Timeout ho gaya hai, ab dobara search karo with increased radius
        // Ride status ko wapas 'finding' kar do taake search continue ho sake
        $ride->update(['status' => 'finding']);
        
        // Continue searching with increased radius
        SearchNearbyRidersJob::dispatch($this->rideId, $this->currentRadius + 1);
    }
}

// 4. Handle Rider Response Job - HandleRiderResponseJob.php
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

    public function handle(FirebaseService $firebaseService)
    {
        $ride = Rides::find($this->rideId);

        if (!$ride || $ride->status !== 'pending') {
            return;
        }

        if ($this->response === 'accept') {
            $this->handleAcceptance($ride, $firebaseService);
        }
    }

    private function handleAcceptance($ride, $firebaseService)
    {
        // Update ride status
        $ride->update([
            'rider_id' => $this->riderId,
            'status' => 'on a way'
        ]);

        // Notify customer about acceptance
        $this->notifyCustomerRideAccepted($ride, $firebaseService);
    }


    private function notifyCustomerRideAccepted($ride, $firebaseService)
    {
        $customer = User::find($ride->customer_id);
        $rider = User::find($this->riderId);

        if ($customer && $customer->fcm_id) {
            $title = 'Driver Found!';
            $body = "Your driver {$rider->first_name} is on the way";
            $data = [
                'rideId' => $ride->id,
                'status' => 'accepted',
                'riderId' => $this->riderId,
                'riderName' => $rider->first_name . ' ' . $rider->last_name,
                'riderPhone' => $rider->phone,
                'riderLat' => $rider->lat,
                'riderLng' => $rider->lng
            ];

            $firebaseService->sendToDevice('customer', $customer->fcm_id, $title, $body, $data);
        }
    }

}