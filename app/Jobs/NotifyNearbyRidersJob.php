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
    protected $firebaseService;

    public function __construct($rideId, $currentRadius = 1, $maxRadius = 10)
    {
        $this->rideId = $rideId;
        $this->currentRadius = $currentRadius;
        $this->maxRadius = $maxRadius;
        $this->firebaseService = app(FirebaseService::class);
    }

    public function handle()
    {
        $ride = Rides::find($this->rideId);
        
        if (!$ride || $ride->status !== 'pending') {
            return; // Ride cancelled or already assigned
        }

        // Notify customer about search progress
        $this->notifyCustomerSearchProgress($ride);

        // Search for riders in current radius
        $riders = $this->findNearbyRiders($ride);

        if (!empty($riders)) {
            // Found riders - notify them and wait for response
            NotifyRidersJob::dispatch($this->rideId, $riders, $this->currentRadius);
        } else {
            // No riders found - increase radius or give up
            $this->handleNoRidersFound($ride);
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

    private function notifyCustomerSearchProgress($ride)
    {
        $customer = User::find($ride->customer_id);
        if ($customer && $customer->fcm_token) {
            $title = 'Searching for Driver';
            $body = "Looking for drivers within {$this->currentRadius} km radius...";
            $data = [
                'rideId' => $ride->id,
                'status' => 'searching',
                'currentRadius' => $this->currentRadius,
                'maxRadius' => $this->maxRadius
            ];

            $this->firebaseService->sendToDevice(
                'customer', 
                $customer->fcm_token, 
                $title, 
                $body, 
                $data
            );
        }
    }

    private function handleNoRidersFound($ride)
    {
        if ($this->currentRadius < $this->maxRadius) {
            // Increase radius and try again after 10 seconds
            SearchNearbyRidersJob::dispatch($this->rideId, $this->currentRadius + 1, $this->maxRadius)
                ->delay(now()->addSeconds(10));
        } else {
            // No riders found within max radius
            $this->notifyCustomerNoRidersFound($ride);
            
            // Update ride status
            $ride->update(['status' => 'no_riders_found']);
        }
    }

    private function notifyCustomerNoRidersFound($ride)
    {
        $customer = User::find($ride->customer_id);
        if ($customer && $customer->fcm_token) {
            $title = 'No Drivers Available';
            $body = 'Sorry, no drivers are available in your area right now. Please try again later.';
            $data = [
                'rideId' => $ride->id,
                'status' => 'no_riders_found'
            ];

            $this->firebaseService->sendToDevice(
                'customer', 
                $customer->fcm_token, 
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

    protected $firebaseService;

    public function __construct($rideId, $riders, $currentRadius)
    {
        $this->rideId = $rideId;
        $this->riders = $riders;
        $this->currentRadius = $currentRadius;
        $this->firebaseService = app(FirebaseService::class);
    }

    public function handle()
    {
        $ride = Rides::find($this->rideId);
        
        if (!$ride || $ride->status !== 'pending') {
            return;
        }

        // Store notification record for tracking
        $this->storeRideNotifications($ride);

        // Send notification to all found riders
        $this->sendNotificationToRiders($ride);

        // Schedule timeout job - if no response in 30 seconds, search with increased radius
        HandleRiderTimeoutJob::dispatch($this->rideId, $this->currentRadius)
            ->delay(now()->addSeconds(30));
    }

    private function storeRideNotifications($ride)
    {
        foreach ($this->riders as $rider) {
            DB::table('ride_notifications')->insert([
                'ride_id' => $ride->id,
                'rider_id' => $rider->id,
                'status' => 'sent',
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    private function sendNotificationToRiders($ride)
    {
        $fcmTokens = collect($this->riders)->pluck('fcm_token')->filter()->toArray();
        
        if (empty($fcmTokens)) {
            return;
        }

        $customer = User::find($ride->customer_id);
        $customerName = $customer ? $customer->first_name . ' ' . $customer->last_name : 'Customer';
        
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

        $this->firebaseService->sendToMultipleDevices('rider', $fcmTokens, $title, $body, $data);
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
        
        if (!$ride || $ride->status !== 'pending') {
            return; // Ride already handled
        }

        // Check if any rider accepted
        $acceptedNotification = DB::table('ride_notifications')
            ->where('ride_id', $this->rideId)
            ->where('status', 'accepted')
            ->first();

        if (!$acceptedNotification) {
            // No one accepted - continue searching with increased radius
            SearchNearbyRidersJob::dispatch($this->rideId, $this->currentRadius + 1);
        }
    }
}

// 4. Handle Rider Response Job - HandleRiderResponseJob.php
class HandleRiderResponseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rideId;
    protected $riderId;
    protected $response; // 'accept' or 'reject'

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
        } else {
            $this->handleRejection($ride);
        }
    }

    private function handleAcceptance($ride, $firebaseService)
    {
        // Update ride status
        $ride->update([
            'rider_id' => $this->riderId,
            'status' => 'accepted',
            'accepted_at' => now()
        ]);

        // Update notification status
        DB::table('ride_notifications')
            ->where('ride_id', $this->rideId)
            ->where('rider_id', $this->riderId)
            ->update(['status' => 'accepted', 'responded_at' => now()]);

        // Notify customer about acceptance
        $this->notifyCustomerRideAccepted($ride, $firebaseService);

        // Notify other riders that ride is no longer available
        $this->notifyOtherRidersRideTaken($ride, $firebaseService);
    }

    private function handleRejection($ride)
    {
        // Update notification status
        DB::table('ride_notifications')
            ->where('ride_id', $this->rideId)
            ->where('rider_id', $this->riderId)
            ->update(['status' => 'rejected', 'responded_at' => now()]);

        // Check if all notified riders have rejected
        $totalNotifications = DB::table('ride_notifications')
            ->where('ride_id', $this->rideId)
            ->count();

        $rejectedCount = DB::table('ride_notifications')
            ->where('ride_id', $this->rideId)
            ->where('status', 'rejected')
            ->count();

        if ($totalNotifications === $rejectedCount) {
            // All riders rejected - continue with next radius immediately
            SearchNearbyRidersJob::dispatch($this->rideId, $this->getCurrentRadius() + 1);
        }
    }

    private function notifyCustomerRideAccepted($ride, $firebaseService)
    {
        $customer = User::find($ride->customer_id);
        $rider = User::find($this->riderId);

        if ($customer && $customer->fcm_token) {
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

            $firebaseService->sendToSingleDevice('customer', $customer->fcm_token, $title, $body, $data);
        }
    }

    private function notifyOtherRidersRideTaken($ride, $firebaseService)
    {
        $otherRiderIds = DB::table('ride_notifications')
            ->where('ride_id', $this->rideId)
            ->where('rider_id', '!=', $this->riderId)
            ->where('status', 'sent')
            ->pluck('rider_id');

        $otherRiders = User::whereIn('id', $otherRiderIds)
            ->whereNotNull('fcm_token')
            ->get();

        foreach ($otherRiders as $rider) {
            $firebaseService->sendToSingleDevice(
                'rider',
                $rider->fcm_token,
                'Ride Request Expired',
                'This ride request has been accepted by another driver',
                [
                    'rideId' => $ride->id,
                    'status' => 'expired'
                ]
            );
        }

        // Update their notification status
        DB::table('ride_notifications')
            ->where('ride_id', $this->rideId)
            ->where('rider_id', '!=', $this->riderId)
            ->where('status', 'sent')
            ->update(['status' => 'expired']);
    }

    private function getCurrentRadius()
    {
        // Get current radius from the most recent notification batch
        return DB::table('ride_notifications')
            ->where('ride_id', $this->rideId)
            ->max('created_at');
    }
}