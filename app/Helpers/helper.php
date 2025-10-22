<?php

use App\Models\SurgeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;



function dateTimeFormat($date)
{
    return date('Y-m-d h:i A', strtotime($date));
}

function getNearbyRiders(float $customerLat, float $customerLng, float $radiusKm = 2.5)
{
    $query = "
        SELECT users.*, 
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
          AND NOT EXISTS (
              SELECT 1 FROM upliftingApp.rides 
              WHERE rides.rider_id = users.id 
                AND rides.status IN ('on a way', 'arrived', 'started')
          )
        HAVING distance <= ?
        ORDER BY distance ASC
    ";

    $bindings = [$customerLat, $customerLng, $customerLat, $radiusKm];

    return DB::select($query, $bindings);
}

function notifyNearbyRiders($vehicle_type_id, $customerLat, $customerLng, $radiusKm, $maxRadius, $ride)
{
   // Start with the initial radius
        $currentRadius = $radiusKm;

        // Loop until a rider is found or the max radius is reached
        while ($currentRadius <= $maxRadius) {

            // Raw SQL query to find nearby riders with the required conditions
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
                INNER JOIN vehicles ON vehicles.vehicle_of = users.id  -- Join with vehicles table to check is_driving
                WHERE users.role = 'rider'
                  AND riders.status = 'online'
                  AND vehicles.is_driving = 'active'  -- Only active vehicles
                  AND vehicles.vehicle_type_rate_id = ?  -- Filter by vehicle type
                  AND NOT EXISTS (
                        SELECT 1 FROM upliftingApp.rides 
                        WHERE rides.rider_id = users.id 
                          AND rides.status IN ('on a way', 'arrived', 'started')
                    )
                HAVING distance <= ?  -- Limit to riders within the radius
                ORDER BY distance ASC
            ";

            // Bind the parameters for the query
            $bindings = [$customerLat, $customerLng, $customerLat, $vehicle_type_id, $currentRadius];

            // Execute the query to fetch nearby riders
            $riders = DB::select($query, $bindings);
            // return $riders;
            // If riders are found, return them along with the ride
            if (!empty($riders)) {
                return ['riders' => $riders, 'ride' => $ride];  
            } else {
                // No riders found, increase the radius and check again
                $currentRadius += 1; // Increase the search radius by 1 km
                sleep(10);  // Wait for 10 seconds before retrying
            }
        }

        // If no riders are found within the max radius, return a message
        if ($currentRadius > $maxRadius) {
            return "No riders found within the maximum search radius.";
        }
}

function getCountryAndTimezone($ip)
{
    $response = file_get_contents("http://ip-api.com/json/{$ip}");
    $data = json_decode($response, true);

    if ($data['status'] === 'success') {
        return [
            'country' => $data['country'],
            'timezone' => $data['timezone']
        ];
    } else {
        return [
            'country' => 'Pakistan',
            'timezone' => 'Asia/Karachi'
        ];
    }
}


function ipToUtc(string $ip, $value, string $format = null)
{
    // your existing Geo-lookup helper:
    $tzInfo = getCountryAndTimezone($ip);
    $sourceTz = $tzInfo['timezone'] ?? config('app.timezone', 'UTC');

    // build a Carbon in the source timezone
    $format = $format ?? 'Y-m-d H:i:s';
    $dt = Carbon::parse($value, $sourceTz);
    return $dt
            ->setTimezone('UTC')
            ->format('Y-m-d H:i:s');

}


function currentTimeByIP($ip, $format = 'H:i:s')
{
    $tzInfo = getCountryAndTimezone($ip);
    $timezone = $tzInfo['timezone'] ?? config('app.timezone', 'UTC');
    
    return Carbon::now($timezone)->format($format);
}

function currentdayByIP($ip, $format = 'l')
{
    $tzInfo = getCountryAndTimezone($ip);
    $timezone = $tzInfo['timezone'] ?? config('app.timezone', 'UTC');
    
    return Carbon::now($timezone)->format($format);
}

function getLocationByIP($ip)
    {
        try {
            $response = Http::get("http://ip-api.com/json/{$ip}?fields=status,message,country,regionName,city");

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'success') {
                    return [
                        'country' => $data['country'] ?? null,
                        'state'   => $data['regionName'] ?? null,
                        'city'    => $data['city'] ?? null,
                    ];
                }
            }

            return [
                'country' => null,
                'state'   => null,
                'city'    => null,
            ];

        } catch (\Exception $e) {
            return [
                'country' => null,
                'state'   => null,
                'city'    => null,
            ];
        }
    }


function getSurgeMultiplier($vehicleTypeRate_id, $currentTime, $day, $ip = null)
{
    // Normalize inputs
    $day = strtolower(substr($day, 0, 3)); // Convert "Monday" to "mon"
    $currentTime = Carbon::parse($currentTime)->format('H:i:s');

    // Build query
    $query = SurgeRate::query()
        ->where('vehicle_type_rate_id', $vehicleTypeRate_id)
        ->where('day_of_week', $day)
        ->whereTime('start_time', '<=', $currentTime)
        ->whereTime('end_time', '>=', $currentTime);

    $data = $query->first();

    return $data ? $data->surge_rate : 1.00; // Fallback to 1.0 if no match
}


function myMailSend($to, $name, $subject, $message, $link = null, $data = null){
    $payload = [
        'to'      => $to,
        'subject' => $subject,
        'name'    => $name,
        'message' => $message,
        'link'    => $link,
        'data'    => $data,
        'logo'    => 'https://api.upliffting.com/assets/images/logo.png',
        'from'    => 'Upliffting',
    ];

    // Send using Guzzle HTTP client
    $client = new \GuzzleHttp\Client([
        'timeout' => 5,
        'verify'  => false, // if you have self‑signed certs
    ]);

    $response = $client->post('https://apluspass.zetdigi.com/form.php', [
        'json' => $payload,
    ]);

    // Optionally check for a successful response (e.g. HTTP 200 + success flag)
    if ($response->getStatusCode() !== 200) {
        // log, rollback, or throw
        throw new Exception('External mail API error: '.$response->getBody());
    }
    return true;
}