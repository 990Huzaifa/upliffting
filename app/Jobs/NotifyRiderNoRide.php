<?php

namespace App\Jobs;

use App\Events\NoRider;
use App\Events\RidersNotified;
use App\Models\Rides;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class NotifyRiderNoRide implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rideId;

    public function __construct($rideId,)
    {
        $this->rideId = $rideId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $ride = Rides::find($this->rideId);

        if (!$ride || $ride->status !== 'finding' || $ride->status === 'cancelled') {
            $riders = $this->findNearbyRiders($ride);
            $riderIds = collect($riders)->pluck('id')->toArray();
            $riderIds = collect($riders)->pluck('id')->toArray();
            $acceptedRiderId =  $ride->rider_id;
            // remove accpepted riders id from array
            $riderIds = array_diff($riderIds, [$acceptedRiderId]);

            $data = [
                'rideId' => $ride->id,
                'rideStatus' => $ride->status,
            ];
            broadcast(new NoRider(
                "No Rides Nearby",
                $ride->id,
                $riderIds
            ));
            Log::debug('NotifyRidersNoRide: riders found', [
                'rideId' => $this->rideId,
            ]);
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
            AND riders.online_status = 'online'
            AND vehicles.is_driving = 'active'
            AND vehicles.vehicle_type_rate_id = ?
            AND NOT EXISTS (
                SELECT 1 FROM rides 
                WHERE rides.rider_id = users.id 
                    AND rides.status IN ('on a way', 'arrived', 'started', 'completed','payment success')
            )
            HAVING distance <= ?
            ORDER BY distance ASC
        ";

        $bindings = [
            $ride->pickup_lat,
            $ride->pickup_lng,
            $ride->pickup_lat,
            $ride->vehicle_type_rate_id,
            5
        ];

        $result = DB::select($query, $bindings);
        
        return $result;
    }
}
