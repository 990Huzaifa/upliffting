<?php

namespace App\Http\Controllers\Rider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\VehicleInspection;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VehicleInspectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function storeOrUpdate(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            DB::beginTransaction();

            // Validate incoming files
            $rules = [];
            $fields = [
                'headlights', 'airlights', 'indicators', 'stop_lights',
                'windshield', 'windshield_wipers', 'safty_belt', 'tires', 'speedometer'
            ];
            foreach ($fields as $field) {
                $rules["{$field}"] = 'nullable|array';
                $rules["{$field}.*"] = 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048';
            }

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first(), 400);
            }

            // Retrieve vehicle and inspection record
            $vehicle = Vehicle::where('vehicle_of', $user->id)->firstOrFail();
            $inspection = VehicleInspection::firstOrNew(['vehicle_id' => $vehicle->id]);

            // Loop through each image group
            foreach ($fields as $field) {
                if ($request->has($field)) {
                    // Delete existing images if any
                    $existing = json_decode($inspection->$field, true) ?: [];
                    foreach ($existing as $path) {
                        $fullPath = public_path($path);
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                    }

                    // Upload new images
                    $uploaded = $this->uploadImages($request->$field, $user);
                    $inspection->$field = json_encode($uploaded);
                }
            }

            // Save or update inspection
            $inspection->save();

            DB::commit();
            return response()->json(['user' => $user], 200);

        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json(['DB error' => $e->getMessage()], 500);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], is_int($e->getCode()) ? $e->getCode() : 500);
        }
    }

    private function uploadImages($images, $user)
    {
        $uploadedImages = [];
        foreach ($images as $image) {
            $imageName = rand(1000, 9999)
                . 'vehicle-inspection'
                . $user->id
                . '-' . time()
                . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('vehicle-inspection'), $imageName);
            $uploadedImages[] = 'vehicle-inspection/' . $imageName;
        }
        return $uploadedImages;
    }

    
}
