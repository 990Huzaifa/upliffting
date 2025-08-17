<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\VehicleType;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class VehicleTypeController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        try{            
            $admin = Auth::guard('admin')->user();
            $query = VehicleType::orderBy('id', 'desc');

            $perPage = $request->query('per_page', 25);
            $searchQuery = $request->query('search');

            if (!empty($searchQuery)) {
                $vehicleTypeRateIds = VehicleType::Where('title', 'like', '%' . $searchQuery . '%')
                        ->pluck('id')
                        ->toArray();
    
                    // Filter orders by the found Customers IDs
                    $query = $query->whereIn('id', $vehicleTypeRateIds);
            }
            // Execute the query with pagination
            $data = $query->paginate($perPage);

            return response()->json($data, 200);

        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {   
        try{
            DB::beginTransaction();

            $validator = Validator::make($request->all(),[
                'title' => 'required',
                'description' => 'required',
            ],[
                'title.required' => 'Title is required',
                'description.required' => 'Description is required',
            ]);
            if ($validator->fails()) throw new Exception($validator->errors()->first(), 400);
            $icon = null;
            // move photos to storage
            if ($request->hasFile('icon')) {
                $image = $request->file('icon');
                $image_name = 'v-icon' . time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('vehicle-icon'), $image_name);
                $icon = 'vehicle-icon/' . $image_name;
            }
            $data = VehicleType::create([
                'title' => $request->title,
                'description' => $request->description,
                'icon' => $icon
            ]);

            DB::commit();
            return response()->json($data, 200);

        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id, Request $request): JsonResponse
    {
        try{
            $data = VehicleType::find($id);
            return response()->json($data,200);

        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try{
            DB::beginTransaction();
            
            $validator = Validator::make($request->all(),[
                'title' => 'required',
                'icon' => 'nullable',
                'description' => 'required',
            ],[
                'title.required' => 'Title is required',
                'description.required' => 'Description is required',
            ]);

            if ($validator->fails())throw new Exception($validator->errors()->first(),400);

            $data = VehicleType::find($id);
            if(empty($data)) throw new Exception("data not found", 400);

            $icon = $data->icon;
            if ($request->hasFile('icon')) {
                //  first unlink the old one
                if ($data->icon && file_exists(public_path($data->icon))) {
                    @unlink(public_path($data->icon));
                }
                // upload new 
                $image = $request->file('icon');
                $image_name = 'v-icon' . time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('vehicle-icon'), $image_name);
                $icon = 'vehicle-icon/' . $image_name;
            }
            
            $data->update([
                'title' => $request->title,
                'description' => $request->description,
                'icon' => $icon
            ]);

            DB::commit();
            return response()->json($data, 200);

        }catch(QueryException $e){
            DB::rollBack();
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    public function list(): JsonResponse
    {
        try {
            $vehicleTypes = VehicleType::all();
            return response()->json($vehicleTypes, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
