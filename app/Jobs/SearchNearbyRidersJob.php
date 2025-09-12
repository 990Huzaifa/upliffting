<?php

// 1. Main Job - SearchNearbyRidersJob.php
namespace App\Jobs;

use App\Events\RideAccepted;
use App\Events\RidersNotified;
use App\Events\RideSearchProgress;
use App\Models\Rides;
use App\Models\RidesDropOff;
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

        if (!$ride || $ride->status !== 'finding' || $ride->status === 'cancelled') {
             Log::debug('SearchNearbyRidersJob: ride not acceptable', [
                'rideId' => $this->rideId,
                'status' => optional($ride)->status,
            ]);
            return "cancelled"; // Ride cancelled or already assigned
        }

        // 1) Progress notify (throttled per radius)
        if ($this->currentRadius < $this->maxRadius) {
            $this->notifyCustomerSearchProgress($ride);
        }

        // 2) Find riders in current radius
        $riders = $this->findNearbyRiders($ride);

        if (!empty($riders) && $this->currentRadius < $this->maxRadius) {

            NotifyRidersJob::dispatch($this->rideId, $riders, $this->currentRadius, $this->maxRadius);

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
            AND riders.online_status = 'online'
            AND vehicles.is_driving = 'active'
            AND vehicles.vehicle_type_rate_id = ?
            AND NOT EXISTS (
                SELECT 1 FROM rides 
                WHERE rides.rider_id = users.id 
                    AND rides.status IN ('on a way', 'arrived', 'started')
            )
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

        $result = DB::select($query, $bindings);
        Log::debug('yaha mily riders', [
                'rideId' => $this->rideId,
                'count' => count($result),
            ]);
        return $result;
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
                $this->maxRadius,
                'searching'
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
                $this->maxRadius,
                'expired'
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
            ->delay(now()->addSeconds(10));
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
        $dropoffs = RidesDropOff::select('drop_location', 'ride_id')->where('ride_id', $ride->id)->get()->toArray();
        $data = [
            'rideId' => $ride->id,
            'rideStatus' => $ride->status,
            'customerName' => $customerName,
            'customerAvatar' => $customer ? $customer->avatar : null,
            'customerPhone' => $customer->phone,
            'baseFare' => $ride->base_fare,
            'pickupLat' => $ride->pickup_lat,
            'pickupLng' => $ride->pickup_lng,
            'pickupAddress' => $ride->pickup_location,
            'dropoffAddress' => $dropoffs,
            'estimatedDistance' => $ride->distance,
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
        if (!$ride || in_array($ride->status, ['cancelled', 'on a way'])) {
            return;
        }

        // No accept within 30s:
        if ($this->currentRadius < $this->maxRadius) {
            // $ride->update(['status' => 'finding']);

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
                    $this->maxRadius,
                    'expired'
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
