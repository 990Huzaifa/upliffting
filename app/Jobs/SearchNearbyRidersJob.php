<?php

// 1. Main Job - SearchNearbyRidersJob.php
namespace App\Jobs;

use App\Events\RideAccepted;
use App\Events\RidersNotified;
use App\Events\RideSearchProgress;
use App\Models\Rides;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SearchNearbyRidersJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rideId;
    protected $currentRadius;
    protected $maxRadius;
    public int $uniqueFor = 20; // 10s delay + buffer

    public function uniqueId(): string
    {
        return "ride:{$this->rideId}:r{$this->currentRadius}";
    }


    public function __construct($rideId, $currentRadius = 1, $maxRadius = 6)
    {
        $this->rideId = $rideId;
        $this->currentRadius = $currentRadius;
        $this->maxRadius = $maxRadius;
    }

    public function handle()
    {
        $ride = Rides::find($this->rideId);

        if (!$ride || $ride->status !== 'finding') {
            return "cancelled"; // Ride cancelled or already assigned
        }

        // 1) Progress notify (throttled per radius)
        if ($this->currentRadius < $this->maxRadius) {
            $this->notifyCustomerSearchProgress($ride);
        }

        // 2) Find riders in current radius
        $riders = $this->findNearbyRiders($ride);

        if (!empty($riders) && $this->currentRadius < $this->maxRadius) {
            // Found riders - notify them and wait for response
            $ride->update(['status' => 'pending']);

            // NotifyRidersJob::dispatch($this->rideId, $riders, $this->currentRadius, $this->maxRadius);

            Log::debug('SearchNearbyRidersJob: riders found', [
                'rideId' => $this->rideId,
                'radius' => $this->currentRadius,
                'count' => count($riders),
            ]);
            return;            
        }
        $this->handleNoRidersFound($ride);
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
        // throttle: one progress ping per ride+radius (12s TTL)
        $key = "ride_progress:{$ride->id}:r{$this->currentRadius}";
        if (!Cache::add($key, 1, now()->addSeconds(12))) {
            return;
        }

        $customer = User::find($ride->customer_id);
        if ($customer) {
            $title = 'Searching for Driver';
            
            // Fire the event for customer channel
            broadcast(new RideSearchProgress(
                $title,
                $ride->id,
                $customer->id, // <- customerId add in constructor
                $this->currentRadius,
                $this->maxRadius
            ));

        }
    }

    private function handleNoRidersFound($ride)
    {
        if ($this->currentRadius < $this->maxRadius) {
            // Increase radius and try again after 10 seconds
            SearchNearbyRidersJob::dispatch($this->rideId, $this->currentRadius + 1, $this->maxRadius)
                ->delay(now()->addSeconds(10));

            Log::debug('SearchNearbyRidersJob: expanding radius', [
                'rideId' => $this->rideId,
                'nextRadius' => $this->currentRadius + 1,
            ]);
            return;
        }
        $this->notifyCustomerNoRidersFound($ride);
        $ride->update(['status' => 'cancelled']);
        Log::debug('SearchNearbyRidersJob: cancelled (max radius)', ['rideId' => $this->rideId]);
    }

    private function notifyCustomerNoRidersFound($ride)
    {
        $customer = User::find($ride->customer_id);
        if ($customer) {
            $title = 'No Drivers Available';
            // Send notification
            event(new RideSearchProgress(
                $title,
                $ride->id,
                $customer->id, // <- customerId add in constructor
                $this->currentRadius,
                $this->maxRadius
            ));
        }
    }
}

// 2. Notify Riders Job - NotifyRidersJob.php
class NotifyRidersJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rideId;
    protected $riders;
    protected $currentRadius;
    protected $maxRadius;

    public int $uniqueFor = 40; // 30s timeout + buffer

    public function uniqueId(): string
    {
        return "notify:ride:{$this->rideId}:r{$this->currentRadius}";
    }


    public function __construct($rideId, $riders, $currentRadius, $maxRadius)
    {
        $this->rideId = $rideId;
        $this->riders = $riders;
        $this->currentRadius = $currentRadius;
        $this->maxRadius = $maxRadius;
    }

    public function handle()
    {
        $ride = Rides::find($this->rideId);

        if (!$ride || !in_array($ride->status, ['finding', 'pending'])) {
             Log::debug('NotifyRidersJob: ride not acceptable', [
                'rideId' => $this->rideId,
                'status' => optional($ride)->status,
            ]);
            return;
        }

        // Send notification to all found riders
        $this->sendNotificationToRiders($ride);

        // Schedule timeout job - if no response in 30 seconds, search with increased radius
        HandleRiderTimeoutJob::dispatch($this->rideId, $this->currentRadius, $this->maxRadius)
            ->delay(now()->addSeconds(30));
    }

    private function sendNotificationToRiders($ride)
    {
        $riderIds = collect($this->riders)->pluck('id')->toArray();

        if (empty($riderIds)) {
            return;
        }

        $customer = User::find($ride->customer_id);
        $customerName = $customer ? $customer->first_name . ' ' . $customer->last_name : 'Customer';
        $title = 'New Ride Request Nearby';
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

        broadcast(new RidersNotified(
            $title,
            $ride->id,
            $riderIds,
            $data
        ));

        Log::debug('NotifyRidersJob: notified riders', [
            'rideId' => $this->rideId,
            'radius' => $this->currentRadius,
            'count' => count($riderIds),
        ]);
    }
}


// 3. Handle Rider Timeout Job - HandleRiderTimeoutJob.php
class HandleRiderTimeoutJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rideId;
    protected $currentRadius;
    protected $maxRadius;
    public int $uniqueFor = 40;

    public function uniqueId(): string
    {
        return "timeout:ride:{$this->rideId}:r{$this->currentRadius}";
    }

    public function __construct($rideId, $currentRadius, $maxRadius)
    {
        $this->rideId = $rideId;
        $this->currentRadius = $currentRadius;
        $this->maxRadius = $maxRadius;
    }

    public function handle()
    {
        $ride = Rides::find($this->rideId);

        // Agar ride already accepted ho gai hai ya cancelled hai to kuch nahi karna
        if (!$ride || !in_array($ride->status, ['pending'])) {
            return;
        }

        // No accept within 30s:
        if ($this->currentRadius < $this->maxRadius) {
            $ride->update(['status' => 'finding']);

            // Next search step (immediate; we already waited 30s)
            SearchNearbyRidersJob::dispatch(
                $this->rideId,
                $this->currentRadius + 1,
                $this->maxRadius
            );

            Log::debug('HandleRiderTimeoutJob: radius increased', [
                'rideId' => $this->rideId,
                'nextRadius' => $this->currentRadius + 1,
            ]);
        }else {
            // Max reached → cancel & notify customer
            $ride->update(['status' => 'cancelled']);

            $customer = User::find($ride->customer_id);
            if ($customer) {

                $title = 'No Drivers Available';
                // Send notification
                event(new RideSearchProgress(
                    $title,
                    $ride->id,
                    $customer->id, // <- customerId add in constructor
                    $this->currentRadius,
                    $this->maxRadius
                ));
            }

            Log::debug('HandleRiderTimeoutJob: cancelled at max radius', [
                'rideId' => $this->rideId,
                'radius' => $this->currentRadius,
            ]);
        }
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

    public function handle()
    {
        $ride = Rides::find($this->rideId);

        if (!$ride || $ride->status !== 'pending') {
            return;
        }

        if ($this->response === 'accept') {
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

        // Notify customer about acceptance
        $this->notifyCustomerRideAccepted($ride);
    }


    private function notifyCustomerRideAccepted($ride)
    {
        $customer = User::find($ride->customer_id);
        $rider = User::find($this->riderId);

        if ($customer) {
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

            event(new RideAccepted(
                    $title,
                    $ride->id,
                    $rider,
                    $data
                ));

            
        }
    }

}