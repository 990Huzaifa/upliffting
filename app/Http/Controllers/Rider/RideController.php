<?php

namespace App\Http\Controllers\Rider;

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
    public function acceptRide(string $id): JsonResponse
    {
        try{
            $user = Auth::user();
            $ride = Rides::find($id);
            if($ride->status != 'finding') throw new Exception('Ride not available', 400);
            $vehicle_id = Vehicle::where('vehicle_of',$user->id)->where('is_driving','active')->value('id');
            if(!$vehicle_id) throw new Exception('You have no active vehicle', 400);
            
            // update ride data 
            $ride->update([
                'rider_id' => $user->id,
                'status' => 'on a way',
                'vehicle_id' => $vehicle_id,
            ]);

            HandleRiderResponseJob::dispatch($ride->id, $user->id, 'accept');

            return response()->json(['message' => 'Ride accepted successfully'], 200);
        } catch (QueryException $e) {
            return response()->json(['DB error' => $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        

    }
}
