<?php

namespace App\Http\Controllers\Rider;

use App\Events\AddStopRequest;
use App\Events\RideAccepted;
use App\Events\RideCancelled;
use App\Http\Controllers\Controller;
use App\Jobs\HandleRiderResponseJob;
use App\Models\Rides;
use App\Models\RidesDropOff;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use App\Jobs\EmitRiderLocationJob;
use Illuminate\Support\Facades\Validator;
use App\Services\FirebaseService;

class RideController extends Controller
{
    public function acceptRide(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $ride = Rides::find($id);


            // here we manage cases
            $status = $request->input('status');
            switch ($status) {
                case 'on a way':
                    if ($ride->status != 'finding')
                        throw new Exception('Ride not available', 400);
                    break;
                case 'arrived':
                    if ($ride->status != 'on a way')
                        throw new Exception('Ride not available', 400);
                    break;
                case 'started':
                    if ($ride->status != 'arrived')
                        throw new Exception('Ride not available', 400);
                    break;
                default:
                    throw new Exception('Invalid status', 400);
            }


            $vehicle = Vehicle::where('vehicle_of', $user->id)->where('is_driving', 'active')->first();
            if (!$vehicle)
                throw new Exception('You have no active vehicle', 400);
            // if($vehicle->approved_at == null) throw new Exception('Your vehicle is not approved', 400);
            // retrive fcm of customer
            $customer_fcm = User::where('id', $ride->customer_id)->value('fcm_id');
            $firebase = new FirebaseService();

            // update ride data 
            $ride->update([
                'rider_id' => $user->id,
                'status' => $status,
                'vehicle_id' => $vehicle->id,
            ]);

            // fire event

            $data = [
                'rideId' => $id,
                'status' => $status,
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
            if ($status == 'on a way') {
                $title = 'Driver Found!';
                broadcast(new RideAccepted(
                    $title,
                    $ride->id,
                    $data
                ));

                EmitRiderLocationJob::dispatch($id, $user->id);
            } elseif ($status == 'arrived') {
                
                $title = 'Driver Arrived!';
                $firebase->sendToDevice(
                    'customer',$customer_fcm,$title,"Driver is waiting for you",['rideId' => $id,'status' => $status,]);

                broadcast(new RideAccepted(
                    $title,
                    $ride->id,
                    $data
                ));
            }elseif ($status == 'started') {
                $title = 'Ride Started!';
                $firebase->sendToDevice(
                    'customer',$customer_fcm,$title,"Your ride has started now",['rideId' => $id,'status' => $status,]);
                broadcast(new RideAccepted(
                    $title,
                    $ride->id,
                    $data
                ));
            }

            return response()->json(['message' => 'Ride updated successfully'], 200);
        } catch (QueryException $e) {
            return response()->json(['DB error' => $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }


    }

    public function cancelRide(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $ride = Rides::find($id);
            if ($ride->rider_id !== $user->id) {
                throw new Exception('You are not authorized to cancel this ride.', 403);
            }

            if ($ride->status === 'completed' || $ride->status === 'cancelled' || $ride->status === 'finding') {
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
                'cancel_by_role' => 'rider',
                'cancelled_by' => $user->id,
                'status' => "cancelled",
                "reason" => $request->reason ?? null
            ]);

            broadcast(new RideCancelled(
                'This ride has been cancelled',
                $ride->id,
                $request->reason
            ));
            
            return response()->json(['message' => 'Ride cancelled successfully'], 200);
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
            if ($ride->rider_id !== $user->id) {
                throw new Exception('You are not authorized to stop in this ride.', 403);
            }
            if ($ride->status === 'completed' || $ride->status === 'cancelled') {
                throw new Exception('Invalid request', 400);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:accepted,delined',
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
                'status.required' => 'Status is required.',
                'status.string' => 'Status must be a string.',
                'status.in' => 'Status must be either accepted or delined.',
            ]);
            if ($validator->fails()) throw new Exception($validator->errors()->first(), 400);

            // if status delined
            

            if ($request->status == 'delined') {
                $data = [
                    'drop_offs' => $request->drop_offs,
                    'base_fare' => $request->base_fare,
                ];
                broadcast(new AddStopRequest(
                    $ride->id,
                    $data,
                    'delined'
                ));
                return response()->json(['message' => 'Drop offs delined'], 200);
            }


            // if status accepted

            // here we update drop offs(removing old and adding new)
            $rideDropOff = RidesDropOff::where('ride_id', $ride->id)->get();
            if ($rideDropOff->count() > 0) {
                RidesDropOff::where('ride_id', $ride->id)->delete();
            }
            foreach ($request->drop_offs as $drop_off) {
                RidesDropOff::create([
                    'ride_id' => $ride->id,
                    'location' => $drop_off,
                ]);
            }
            $ride->update([
                'base_fare' => $request->base_fare,
            ]);

            // broadcast event 
                $data = [
                    'drop_offs' => $request->drop_offs,
                    'base_fare' => $request->base_fare,
                ];
                broadcast(new AddStopRequest(
                    $ride->id,
                    $data,
                    'accepted'
                ));

            return response()->json(['message' => 'Drop offs added successfully'], 200);
        } catch (QueryException $e) {
            return response()->json(['DB error' => $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    public function requestRideInfo(): JsonResponse
    {
        try{
            $user = Auth::user();
            $ride = Rides::where('rider_id', $user->id)->whereIn('status', ['finding', 'on a way', 'arrived', 'started'])->first();
            return response()->json($ride, 200);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
