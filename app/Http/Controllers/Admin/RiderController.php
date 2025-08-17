<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NotifyMail;
use App\Models\Rider;
use App\Models\User;
use App\Models\UserAccount;
use App\Models\UserBank;
use App\Models\Vehicle;
use App\Models\VehicleInspection;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class RiderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try{
            $admin = Auth::guard('admin')->user();
            $query = User::select('users.*')
            ->join('riders', 'users.id', '=', 'riders.user_id')->orderBy('id', 'desc');

            $perPage = $request->query('per_page', 25);
            $searchQuery = $request->query('search');

            if (!empty($searchQuery)) {
                $customerIds = Rider::where('username', 'like', '%' . $searchQuery . '%')
                        ->orWhere('email', 'like', '%' . $searchQuery . '%')
                        ->pluck('id')
                        ->toArray();
    
                    // Filter orders by the found Customers IDs
                    $query = $query->whereIn('users.id', $customerIds);
            }
            // Execute the query with pagination
            $data = $query->paginate($perPage);

            return response()->json($data, 200);

        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
    public function show(string $id): JsonResponse
    {
        try{
            $admin = Auth::guard('admin')->user();
            $data = User::select('users.*','riders.status as online_status','riders.*','users.id as user_id', 'users.status as account_status')
            ->join('riders', 'users.id', '=', 'riders.user_id')->where('users.id', $id)->first();

            $vehicles = Vehicle::select('vehicles.*','vehicle_types.title as vehicle_type')
            ->join('vehicle_type_rates', 'vehicles.vehicle_type_rate_id', '=', 'vehicle_type_rates.id')
            ->join('vehicle_types', 'vehicle_type_rates.vehicle_type_id', '=', 'vehicle_types.id')
            ->where('vehicle_of', $data->user_id)->get();

            UserBank::where('user_id', $data->user_id)->get();

            UserAccount::where('user_id', $data->user_id)->get();

            // verification doc list
            $list = [];

            if ($data->avatar != null) {
                $list['avatar'] = 'Profile Photo';
            }

            if ($data->driving_licence != null) {
                $list['driving_licence'] = 'Driving Licence';
            }
            


            return response()->json(['data' => $data,'vehicles' => $vehicles,'verification_docs' => $list], 200);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try{
            $data = User::select('users.*','riders.status as online_status','riders.*','users.id as user_id')
            ->join('riders', 'users.id', '=', 'riders.user_id')->where('users.id', $id)->first();

            $vehicles = Vehicle::select('vehicles.*','vehicle_type_rates.title as vehicle_type')
            ->join('vehicle_type_rates', 'vehicles.vehicle_type_rate_id', '=', 'vehicle_type_rates.id')
            ->where('vehicle_of', $data->user_id)->get();

            UserBank::where('user_id', $data->user_id)->get();

            UserAccount::where('user_id', $data->user_id)->get();

            // verification doc list
            $list = [];

            if ($data->avatar != null) {
                $list['avatar'] = 'Profile Photo';
            }

            if ($data->driving_licence != null) {
                $list['driving_licence'] = 'Driving Licence';
            }


            return view('admin.riders.show', compact('data','vehicles'));
        }catch(Exception $e){
            Session::flash('error', [
                'text' => "something went wrong. Please try again" . $e->getMessage(),
            ]);
            return redirect()->back();
        }
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

    public function updateStatus(Request $request): JsonResponse
    {
        try{
            $data = User::find($request->rider_id);
            if (!$data) throw new Exception('Rider not found');
            if($request->status ==1){
                $data->status = 'active';
            }else{
                $data->status = 'inactive';
            }
            $data->save();
            return response()->json(['success' => 'Status updated successfully'], 200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function approvedStatus(string $id, string $status): JsonResponse
    {
        try{

            $data = User::find($id);
            if (!$data) throw new Exception('Rider not found');
            // Validate the status
            $validator = Validator::make([
                'status' => $status,
            ], [
                'status.required' => 'Status is required',
                'status.in:approved,suspended,pending' => 'Status must be approved, suspended or pending',
            ]);
            if ($validator->fails()) throw new Exception($validator->errors()->first());

            $data->is_approved = $status;
            $data->save();

            return response()->json(['success' => 'Status updated successfully'], 200);
        }catch(Exception $e){
            return response()->json(['error' => 'Something went wrong'], 400);
        }
    }

    public function InspectionList(string $id): JsonResponse
    {
        try{
            $data = VehicleInspection::where('vehicle_id', $id)->get();
            
            return response()->json(['data' => $data],200);            
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function InspectionUpdate(Request $request, string $id): JsonResponse
    {
        try{
            $validator = Validator::make($request->all(), [
                "is_"
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }

            $inspection = VehicleInspection::where('vehicle_id', $id)->first();
            if (!$inspection) {
                return response()->json(['error' => 'Inspection not found'], 404);
            }

            $inspection->status = $request->status;
            $inspection->reason = $request->reason;
            $inspection->save();

            return response()->json(['success' => 'Inspection status updated successfully'], 200);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function sendMail(Request $request, string $id): JsonResponse
    {
        try{
            $rider = User::find($id);
            Mail::to($rider->email)->send(new NotifyMail([
                'name' => $rider->first_name . ' ' . $rider->last_name,
                'message' => $request->reason,
            ]));
            return response()->json(['success' => 'Notify Rider successfully'], 200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

}
