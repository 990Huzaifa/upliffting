<?php

namespace App\Console\Commands;

use App\Models\Rider;
use App\Models\Rides;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Console\Command;

class NotifyRiders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:nearby-riders {rideId}';
    protected $description = 'Notify nearby riders for a ride request';

    protected $firebaseService;


    public function __construct(FirebaseService $firebaseService)
    {
        parent::__construct();
        $this->firebaseService = $firebaseService; // Inject the FirebaseService
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ride = Rides::find($this->argument('rideId'));

        if (!$ride) {
            $this->error('Ride request not found.');
            return;
        }

        $lat = $ride->pickup_lat;
        $lng = $ride->pickup_lng;
        $vehicleTypeRateId = $ride->vehicle_type_rate_id; // Assuming it's available in the RideRequest
        $radius = 1; // Start with a 1 km radius
        $maxRadius = 10; // Maximum search radius

        // Start the process of notifying riders
        $result = notifyNearbyRiders($vehicleTypeRateId, $lat, $lng, $radius, $maxRadius, $ride);

        if (is_array($result)) {
            // Riders found, notify them
            $riders = $result['riders'];
            $ride = $result['ride'];
            $this->sendNotification($riders, $ride);
        } else {
            // No riders found
            $this->info($result);  // This will return "No riders found within the maximum search radius."
        }
    }

    public function sendNotification($riders, $ride)
    {

        // Step 1: Retrieve all FCM tokens from the riders
        $fcmTokens = $riders->pluck('fcm_token')->toArray();  // Get the fcm_token for each rider

        // Step 2: Check if there are any FCM tokens
        if (empty($fcmTokens)) {
            $this->info("No FCM tokens found for riders.");
            return;
        }
        $customer = User::find($ride->customer_id)->first();
         // Get the customer's name
        $customerName = $customer ? $customer->first_name . ' ' . $customer->last_name : 'Customer';
         // Step 3: Prepare the notification details
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

        // Step 4: Send the notification to multiple devices at once
        $this->sendToMultipleDevices($appType, $fcmTokens, $title, $body, $data);
        $this->info("Notification sent to " . count($fcmTokens) . " riders.");
        }


}
