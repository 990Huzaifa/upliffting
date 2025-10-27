<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleInspection;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try{
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search', '');
            $vehicles = Vehicle::select('vehicles.*','users.first_name', 'users.last_name')
                ->join('users', 'vehicles.vehicle_of', '=', 'users.id')
                ->orderBy('vehicles.created_at', 'desc');
            if ($search) {
                $vehicles->where(function($query) use ($search) {
                    $query->where('vehicles.registration_number', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%");
                });
            }
            $data = $vehicles->paginate($perPage);
            return response()->json($data, 200);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function show(string $id): JsonResponse
    {
        try{
            $vehicle = Vehicle::select('vehicles.*', 'users.first_name', 'users.last_name')
                ->join('users', 'vehicles.vehicle_of', '=', 'users.id')
                ->findOrFail($id);
            return response()->json($vehicle, 200);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateInspection (Request $request, string $id): JsonResponse
    {
        try{
            $validator = Validator::make($request->all(), [
                'is_headlights' => 'required|in:0,1,2',
                'is_airlights' => 'required|in:0,1,2',
                'is_indicators' => 'required|in:0,1,2',
                'is_stop_lights' => 'required|in:0,1,2',
                'is_windshield' => 'required|in:0,1,2',
                'is_windshield_wipers' => 'required|in:0,1,2',
                'is_safty_belt' => 'required|in:0,1,2',
                'is_speedometer' => 'required|in:0,1,2',
                'is_tires' => 'required|in:0,1,2',
            ],[
                'is_headlights.required' => 'Headlights status is required.',
                'is_airlights.required' => 'Airlights status is required.',
                'is_indicators.required' => 'Indicators status is required.',
                'is_stop_lights.required' => 'Stop lights status is required.',
                'is_windshield.required' => 'Windshield status is required.',
                'is_windshield_wipers.required' => 'Windshield wipers status is required.',
                'is_safty_belt.required' => 'Safety belt status is required.',
                'is_speedometer.required' => 'Speedometer status is required.',
                'is_tires.required' => 'Tires status is required.',
            ]);

            if ($validator->fails()) throw new Exception($validator->errors()->first());

            $vehicleInspection = VehicleInspection::find($id);

            $vehicleInspection->is_headlights = $request->is_headlights;
            if($request->is_headlights == 0){
                // unlink image
                $this->unlinkImage($vehicleInspection->headlights);
                $request->healights = null;

            }
            $vehicleInspection->is_airlights = $request->is_airlights;
            if($request->is_airlights == 0){
                $this->unlinkImage($vehicleInspection->airlights);
                $request->airlights = null; // Assuming you want to set it to null if 0 is selected
            }
            $vehicleInspection->is_indicators = $request->is_indicators;
            if($request->is_indicators == 0){
                $this->unlinkImage($vehicleInspection->indicators);
                $request->indicators = null; // Assuming you want to set it to null if 0 is selected
            }
            $vehicleInspection->is_stop_lights = $request->is_stop_lights;
            if($request->is_stop_lights == 0){
                $this->unlinkImage($vehicleInspection->stop_lights);
                $request->stop_lights = null; // Assuming you want to set it to null if 0 is selected
            }
            $vehicleInspection->is_windshield = $request->is_windshield;
            if($request->is_windshield == 0){
                $this->unlinkImage($request->windshield);
                $request->windshield = null; // Assuming you want to set it to null if 0 is selected
            }
            $vehicleInspection->is_windshield_wipers = $request->is_windshield_wipers;
            if($request->is_windshield_wipers == 0){
                $this->unlinkImage($request->windshield_wipers);
                $request->windshield_wipers = null; // Assuming you want to set it to null if 0 is selected
            }
            $vehicleInspection->is_safty_belt = $request->is_safty_belt;
            if($request->is_safty_belt == 0){
                $this->unlinkImage($request->safty_belt);
                $request->safty_belt = null; // Assuming you want to set it to null if 0 is selected
            }
            $vehicleInspection->is_speedometer = $request->is_speedometer;
            if($request->is_speedometer == 0){
                $this->unlinkImage($request->speedometer);
                $request->speedometer = null; // Assuming you want to set it to null if 0 is selected
            }
            $vehicleInspection->is_tires = $request->is_tires;
            if($request->is_tires == 0){
                $this->unlinkImage($request->tires);
                $request->tires = null; // Assuming you want to set it to null if 0 is selected
            }
            $vehicleInspection->save();

            return response()->json(['message' => 'Vehicle inspection updated successfully'], 200);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    private function unlinkImage($imagePath)
    {
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
}
