<?php

namespace App\Http\Controllers\Customer;

use App\Events\AddStopRequest;
use App\Events\RideCancelled;
use App\Http\Controllers\Controller;
use App\Jobs\SearchNearbyRidersJob;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\Rider;
use App\Models\Rides;
use App\Models\RidesDropOff;
use App\Models\User;
use App\Models\UserAccount;
use App\Models\VehicleTypeRate;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class RideController extends Controller
{
    public function calculateFare(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'distance' => 'required|numeric|min:0',
                'time' => 'required|numeric|min:0',
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first(), 401);
            }

            // Get inputs
            $distance_km = $request->distance;
            // time in sec convert to min
            $time_min = $request->time / 60;

            // Get user's IP-based local time and day
            $ip = $request->ip();
            $currentTime = currentTimeByIP($ip, 'H:i:s');  // Example: '14:30:00'
            $day = currentdayByIP($ip, 'l');               // Example: 'Monday'

            // get country, state, city by ip
            $location = getLocationByIP($ip);
            $countryName = $location['country'];
            $stateName = $location['state'];
            $cityName = $location['city'];


            // Fetch all vehicle types and their pricing
            $vehicleTypeRates = VehicleTypeRate::select('vehicle_type_rates.*', 'vehicle_types.title', 'vehicle_types.icon')
                ->join('vehicle_types', 'vehicle_type_rates.vehicle_type_id', '=', 'vehicle_types.id')
                ->join('countries', 'vehicle_type_rates.country_id', '=', 'countries.id')
                ->join('states', 'vehicle_type_rates.state_id', '=', 'states.id')
                ->join('cities', 'vehicle_type_rates.city_id', '=', 'cities.id')
                ->when($countryName, function ($q) use ($countryName) {
                    $q->where('countries.name', 'LIKE', "%$countryName%");
                })
                ->when($stateName, function ($q) use ($stateName) {
                    $q->where('states.name', 'LIKE', "%$stateName%");
                })
                ->when($cityName, function ($q) use ($cityName) {
                    $q->where('cities.name', 'LIKE', "%$cityName%");
                })
                ->get();

            $fareList = [];

            foreach ($vehicleTypeRates as $rate) {
                // Get surge multiplier for this vehicle type, time and day
                $surge_multiplier = getSurgeMultiplier($rate->id, $currentTime, $day, $ip);

                // Fare formula
                $actual_fare = ($rate->base_fare +
                    ($distance_km * $rate->price_per_km) +
                    ($time_min * $rate->price_per_min)) * $surge_multiplier;

                $fareList[] = [
                    'id' => $rate->id,
                    'name' => $rate->title ?? '',         // Add 'name' column to VehicleTypeRate if not present
                    'image' => $rate->icon ?? '',       // Add 'image' column if not already there
                    'fare' => round($actual_fare, 2),
                ];
            }

            return response()->json(['fares' => $fareList], 200);

        } catch (QueryException $e) {
            return response()->json(['DB error' => $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validate input
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'pickup' => 'required|string|max:255',
                'drop_offs' => 'required|array',
                'drop_offs.*' => 'required|string|max:255',
                'vehicle_type_rate_id' => 'required|exists:vehicle_type_rates,id',
                'promocode' => 'nullable|exists:promo_codes,id',
                'distance' => 'required|numeric|min:0',
                'duration' => 'required|numeric|min:0',
                'base_fare' => 'required|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'lat' => 'required',
                'lng' => 'required',
            ], [
                'pickup.required' => 'Pickup location is required.',

                'drop_offs.*.required' => 'Each drop-off location is required.',
                'vehicle_type_rate_id.required' => 'Vehicle type is required.',
                'vehicle_type_rate_id.exists' => 'The selected vehicle type is invalid.',
                'promocode.exists' => 'The provided promo code does not exist.',
                'distance.required' => 'Distance is required.',
                'duration.required' => 'Duration is required.',
                'base_fare.required' => 'Base fare is required.',
                'discount_amount.numeric' => 'Discount amount must be a number.',
                'lat.required' => 'Latitude is required.',
                'lng.required' => 'Longitude is required.',
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first(), 401);
            }

            // Store ride logic here
            // 1:step promocode
            $promo = null;
            if ($request->promocode) {
                $promo = PromoCode::where('code', $request->promocode)
                    ->where('is_active', true)
                    ->where('expiry_date', '>=', now())
                    ->where('is_used', false)
                    ->firstOrFail();
            }
            // 2:step store ride data

            $ride = Rides::create([
                'customer_id' => $user->id,
                'vehicle_type_rate_id' => $request->vehicle_type_rate_id,
                'pickup_location' => $request->pickup,
                "pickup_lat" => $request->lat,
                "pickup_lng" => $request->lng,
                'status' => 'finding',
                'promo_code_id' => $promo ? $promo->id : null,
                'distance' => $request->distance,
                'duration' => $request->duration / 60, // Convert seconds to minutes
                'base_fare' => $request->base_fare - ($request->discount_amount ?? 0),
                'discount_amount' => $request->discount_amount ?? 0,
            ]);

            // 3: setp ride dropoffs
            foreach ($request->drop_offs as $drop_off) {
                RidesDropOff::create([
                    'ride_id' => $ride->id,
                    'drop_location' => $drop_off,
                ]);
            }

            // get active user account
            $payment_method_id = UserAccount::where('user_id', $user->id)
                ->where('is_default', true)
                ->value('id');

            if (!$payment_method_id) {
                return response()->json(['message' => 'No default payment method found. Please add a payment method first.', 'add_card' => 0], 200);
            }

            // 4: add payment details
            Payment::create([
                'ride_id' => $ride->id,
                'customer_id' => $user->id,
                'payment_method_id' => $payment_method_id,
                'amount' => $ride->base_fare - ($ride->discount_amount ?? 0),
                'status' => 'pending',
            ]);

            // 5: notify the nearby riders

            SearchNearbyRidersJob::dispatch($ride->id)->onQueue('high');


            return response()->json(['message' => 'Finding ride for you.'], 200);

        } catch (QueryException $e) {
            return response()->json(['DB error' => $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancelRide(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $ride = Rides::findOrFail($id);
            if ($ride->customer_id !== $user->id) {
                throw new Exception('You are not authorized to cancel this ride.', 403);
            }

            if ($ride->status === 'completed' || $ride->status === 'cancelled') {
                throw new Exception('This ride cannot be cancelled.', 400);
            }
            $validator = Validator::make($request->all(), [
                'reason' => 'nullable|string|max:255',
            ], [
                'reason.string' => 'Reason must be a string.',
                'reason.max' => 'Reason cannot exceed 255 characters.',
            ]);
            if ($validator->fails())
                throw new Exception($validator->errors()->first(), 401);

            $ride->update([
                'status' => 'cancelled',
                'cancel_by_role' => 'customer',
                'cancelled_by' => $user->id,
                'reason' => $request->reason ?? null,
            ]);

            // push event on cancel

            broadcast(new RideCancelled(
                'This ride has been cancelled',
                $ride->id,
                $request->reason
            ));

            return response()->json(['message' => 'Ride cancelled successfully.'], 200);

        } catch (QueryException $e) {
            return response()->json(['DB error' => $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function addStopRequest(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $ride = Rides::findOrFail($id);
            if ($ride->customer_id !== $user->id) {
                throw new Exception('You are not authorized to stop in this ride.', 403);
            }
            if ($ride->status === 'completed' || $ride->status === 'cancelled') {
                throw new Exception('Invalid request', 400);
            }

            $validator = Validator::make($request->all(), [
                'drop_offs' => 'required|array',
                'drop_offs.*' => 'required|string|max:255',
                'base_fare' => 'required|numeric|min:0',
            ], [
                'drop_offs.required' => 'Drop offs are required.',
                'drop_offs.array' => 'Drop offs must be an array.',
                'drop_offs.*.required' => 'Each drop off is required.',
                'drop_offs.*.string' => 'Each drop off must be a string.',
                'drop_offs.*.max' => 'Each drop off cannot exceed 255 characters.',
                'base_fare.required' => 'Base fare is required.',
                'base_fare.numeric' => 'Base fare must be a number.',
                'base_fare.min' => 'Base fare must be at least 0.',
            ]);
            if ($validator->fails())
                throw new Exception($validator->errors()->first(), 400);

            $data = [
                'drop_offs' => $request->drop_offs,
                'base_fare' => $request->base_fare,
            ];
            broadcast(new AddStopRequest(
                $ride->id,
                $data,
                'pending'
            ));
            return response()->json(['message' => 'Add Stop request sent successfully'], 200);
        } catch (QueryException $e) {
            return response()->json(['DB error' => $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function rateRide(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $ride = Rides::findOrFail($id);
            if ($ride->customer_id !== $user->id) {
                throw new Exception('You are not authorized to rate this ride.', 403);
            }

            if ($ride->status !== 'completed') {
                throw new Exception('You can only rate a completed ride.', 400);
            }

            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
            ], [
                'rating.required' => 'Rating is required.',
                'rating.integer' => 'Rating must be an integer.',
                'rating.min' => 'Rating must be at least 1.',
                'rating.max' => 'Rating cannot exceed 5.',
            ]);
            if ($validator->fails())
                throw new Exception($validator->errors()->first(), 401);

            $ride->update([
                'current_rating' => $request->rating,
            ]);

            // update rider overall rating
            $rider = Rider::find($ride->rider_id);
            if ($rider) {
                $totalRatings = Rides::where('rider_id', $rider->id)
                    ->where('status', 'completed')
                    ->where('current_rating', '>', 0)
                    ->count();

                $sumRatings = Rides::where('rider_id', $rider->id)
                    ->where('status', 'completed')
                    ->where('current_rating', '>', 0)
                    ->sum('current_rating');

                $averageRating = $totalRatings > 0 ? round($sumRatings / $totalRatings, 2) : 0;

                $rider->update(['overall_rating' => $averageRating]);
            }

            return response()->json(['message' => 'Ride rated successfully.'], 200);

        } catch (QueryException $e) {
            return response()->json(['DB error' => $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
