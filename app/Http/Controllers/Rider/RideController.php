<?php

namespace App\Http\Controllers\Rider;

use App\Events\RideAccepted;
use App\Http\Controllers\Controller;
use App\Jobs\HandleRiderResponseJob;
use App\Models\Rides;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

class RideController extends Controller
{
    public function acceptRide(Request $request,string $id): JsonResponse
    {
        try{
            $user = Auth::user();
            $ride = Rides::find($id);


            // here we manage cases
            $status = $request->input('status');
            switch($status){
                case 'on a way':
                    if($ride->status != 'finding') throw new Exception('Ride not available', 400);
                    break;
                case 'arrived':
                    if($ride->status != 'on a way') throw new Exception('Ride not available', 400);
                    break;
                default:
                    throw new Exception('Invalid status', 400);
            }

            
            $vehicle = Vehicle::where('vehicle_of',$user->id)->where('is_driving','active')->first();
            if(!$vehicle) throw new Exception('You have no active vehicle', 400);
            // if($vehicle->approved_at == null) throw new Exception('Your vehicle is not approved', 400);
            
            // update ride data 
            $ride->update([
                'rider_id' => $user->id,
                'status' => 'on a way',
                'vehicle_id' => $vehicle->id,
            ]);

            // fire event
            $title = 'Driver Found!';
            $data = [
                'rideId' => $id,
                'status' => 'on a way',
                'riderId' => $user->id,
                'riderName' => $user->first_name . ' ' . $user->last_name,
                'riderAvatar' => $user->avatar,
                'riderPhone' => $user->phone,
                'riderLat' => $user->lat,
                'riderLng' => $user->lng,
                'vehicleInfo' => [
                    'id' => $vehicle->id,
                    'make' => $vehicle->make,
                    'model' => $vehicle->model,
                    'color' => $vehicle->color,
                    'plate_number' => $vehicle->registration_number,
                    'photos' => $vehicle->photos,
                ],
            ];
            broadcast(new RideAccepted(
                    $title,
                    $ride->id,
                    $data
                ));

            return response()->json(['message' => 'Ride accepted successfully'], 200);
        } catch (QueryException $e) {
            return response()->json(['DB error' => $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        

    }
}
