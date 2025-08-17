<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurgeRate;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class SurgeRateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = SurgeRate::select('surge_rates.*', 'vehicle_types.title as vehicle_type_title')
                ->join('vehicle_type_rates', 'surge_rates.vehicle_type_rate_id', '=', 'vehicle_type_rates.id')
                ->join('vehicle_types', 'vehicle_type_rates.vehicle_type_id', '=', 'vehicle_types.id')
                ->orderBy('id', 'desc');

            $perPage = $request->query('per_page', 25);
            $searchQuery = $request->query('search');

            if (!empty($searchQuery)) {
                $query->where('vehicle_type_rates.title', 'like', '%' . $searchQuery . '%');
            }

            // Execute the query with pagination
            $data = $query->paginate($perPage);

            return response()->json($data, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Updated validation for multiple days
            $validator = Validator::make($request->all(), [
                'vehicle_type_rate_id' => 'required|exists:vehicle_type_rates,id',
                'surge_rate' => 'required',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i',
                'day_of_week' => 'required|array', // Ensure this is an array
                'day_of_week.*' => 'in:mon,tue,wed,thu,fri,sat,sun', // Validate each element in the array
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->errors()->first(), 400);
            }

            $data = SurgeRate::create([
                'vehicle_type_rate_id' => $request->vehicle_type_rate_id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'surge_rate' => $request->surge_rate,
                'day_of_week' => implode(',', $request->day_of_week), // Store as a comma-separated string
            ]);

            DB::commit();
            return response()->json($data, 201);
        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json(['DB error' => $e->getMessage()], 403);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $data = SurgeRate::where('id', $id)->first();
            return response()->json($data, 200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 400);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data = SurgeRate::where('id', $id)->first();
        return view('admin.surge_rates.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validator = Validator::make($request->all(),[
                'vehicle_type_rate_id' => 'required|exists:vehicle_type_rates,id',
                'start_time' => 'required',
                'end_time' => 'required',
                'surge_rate' => 'required',
                'day_of_week' => 'required|in:mon,tue,wed,thu,fri,sat,sun',
            ]);

            if ($validator->fails()) throw new Exception($validator->errors()->first(), 400);

            $data = SurgeRate::where('id', $id)->update([
                'vehicle_type_rate_id' => $request->vehicle_type_rate_id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'surge_rate' => $request->surge_rate,
                'day_of_week' => $request->day_of_week
            ]);

            Session::flash('success', [
                'text' => 'Surge rate updated successfully'
            ]);
            return redirect()->back();
        } catch (Exception $e) {
            Session::flash('error', [
                'text' => $e->getMessage(),
            ]);
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
